const ocoPackageComparisonState = {
	targetComputerId: null,
	sourceComputerId: null,
	loading: false,
};

function ocoPackageComparisonMetadata() {
	return document.getElementById('metadata');
}

function ocoPackageComparisonPackagesTab() {
	return document.querySelector('#tabControlComputer > .tabcontents > div[name="packages"]');
}

function ocoPackageComparisonInitialize() {
	let metadataElement = ocoPackageComparisonMetadata();
	let packagesTab = ocoPackageComparisonPackagesTab();
	if(!metadataElement || !packagesTab) {
		ocoPackageComparisonState.targetComputerId = null;
		ocoPackageComparisonState.sourceComputerId = null;
		return;
	}

	let targetComputerId = metadataElement.getAttribute('computerId');
	if(ocoPackageComparisonState.targetComputerId !== targetComputerId) {
		ocoPackageComparisonState.targetComputerId = targetComputerId;
		ocoPackageComparisonState.sourceComputerId = null;
	}

	let heading = packagesTab.querySelector('.details-abreast .controls.heading');
	if(heading && !heading.querySelector('.oco-package-comparison-open')) {
		let button = document.createElement('button');
		button.type = 'button';
		button.classList.add('oco-package-comparison-open');
		button.title = LANG['cpc_compare_packages_help'];

		let icon = document.createElement('img');
		icon.src = 'img/copy.dyn.svg';
		button.appendChild(icon);
		button.appendChild(document.createTextNode('\u00a0'+LANG['cpc_compare_packages']));
		button.addEventListener('click', ocoPackageComparisonShowComputerDialog);

		let addButton = heading.querySelector('button[onclick*="showDialogAssignComputerPackage"]');
		heading.insertBefore(button, addButton??null);
	}

	if(ocoPackageComparisonState.sourceComputerId
	&& !packagesTab.querySelector('.oco-package-comparison-host')
	&& !ocoPackageComparisonState.loading) {
		ocoPackageComparisonLoad(ocoPackageComparisonState.sourceComputerId);
	}
}

function ocoPackageComparisonShowComputerDialog() {
	let metadataElement = ocoPackageComparisonMetadata();
	if(!metadataElement) return;

	let targetComputerId = metadataElement.getAttribute('computerId');
	showDialogAjax(
		LANG['cpc_select_comparison_computer'],
		'views/dialog-computer-package-comparison-select.php?target_computer_id='+encodeURIComponent(targetComputerId),
		DIALOG_BUTTONS_NONE,
		DIALOG_SIZE_LARGE,
		function(dialogContainer) {
			let computerSelection = dialogContainer.querySelector('.computerSelection');
			let compareButton = dialogContainer.querySelector('button[name="compare"]');
			initSelectionBox(computerSelection);
			compareButton.addEventListener('click', function() {
				let computerIds = getSelectedCheckBoxValues('computers', null, true, dialogContainer);
				if(!computerIds) return;
				dialogContainer.close();
				ocoPackageComparisonLoad(computerIds[0]);
			});
		}
	);
}

function ocoPackageComparisonLoad(sourceComputerId) {
	let metadataElement = ocoPackageComparisonMetadata();
	let packagesTab = ocoPackageComparisonPackagesTab();
	if(!metadataElement || !packagesTab) return;

	let targetComputerId = metadataElement.getAttribute('computerId');
	if(String(sourceComputerId) === String(targetComputerId)) {
		emitMessage(LANG['error'], LANG['cpc_comparison_computer_must_be_different'], MESSAGE_TYPE_ERROR);
		return;
	}

	ocoPackageComparisonState.targetComputerId = targetComputerId;
	ocoPackageComparisonState.sourceComputerId = sourceComputerId;
	ocoPackageComparisonState.loading = true;
	if(typeof toggleAutoRefresh === 'function') toggleAutoRefresh(false);

	let host = packagesTab.querySelector('.oco-package-comparison-host');
	if(!host) {
		host = document.createElement('div');
		host.classList.add('oco-package-comparison-host');
		let packageLists = packagesTab.querySelector('.details-abreast');
		packagesTab.insertBefore(host, packageLists??packagesTab.firstChild);
	}
	host.innerHTML = '<div class="alert info">'+LANG['in_progress']+'</div>';

	let url = 'views/computer-package-comparison.php?'+urlencodeObject({
		'target_computer_id': targetComputerId,
		'source_computer_id': sourceComputerId,
	});
	ajaxRequest(url, host, function() {
		ocoPackageComparisonState.loading = false;
		ocoPackageComparisonApplyFilters();
		ocoPackageComparisonUpdateSelection();
	}, false, false, function() {
		ocoPackageComparisonState.loading = false;
	});
}

function ocoPackageComparisonClose() {
	ocoPackageComparisonState.sourceComputerId = null;
	let host = document.querySelector('.oco-package-comparison-host');
	if(host) host.remove();
}

function ocoPackageComparisonFilter(status, sender) {
	let table = document.querySelector('.oco-package-comparison-table');
	if(!table) return;
	table.setAttribute('data-filter-status', status);
	sender.parentNode.querySelectorAll('button[data-status]').forEach(function(button) {
		button.classList.toggle('active', button === sender);
	});
	ocoPackageComparisonApplyFilters();
	let toggleAll = table.querySelector('thead input[type="checkbox"]');
	if(toggleAll) {
		toggleAll.checked = false;
		toggleAll.indeterminate = false;
	}
}

function ocoPackageComparisonApplyFilters() {
	let comparison = document.querySelector('.oco-package-comparison');
	if(!comparison) return;
	let table = comparison.querySelector('.oco-package-comparison-table');
	let searchInput = comparison.querySelector('.oco-package-comparison-search');
	let status = table.getAttribute('data-filter-status') || 'missing';
	let searchTerm = searchInput ? searchInput.value.trim().toLocaleLowerCase() : '';
	let statusRows = 0;
	let visibleRows = 0;

	table.querySelectorAll('tbody tr[data-status]').forEach(function(row) {
		let matchesStatus = status === 'all' || row.getAttribute('data-status') === status;
		let matchesSearch = searchTerm === '' || row.innerText.toLocaleLowerCase().includes(searchTerm);
		if(matchesStatus) statusRows++;
		if(matchesStatus && matchesSearch) visibleRows++;
		row.hidden = !matchesStatus || !matchesSearch;
	});

	table.hidden = visibleRows === 0;
	let emptyMessage = comparison.querySelector('.oco-package-comparison-empty');
	if(emptyMessage) emptyMessage.hidden = status !== 'missing' || statusRows !== 0;
	let noResultsMessage = comparison.querySelector('.oco-package-comparison-no-results');
	if(noResultsMessage) noResultsMessage.hidden = visibleRows !== 0 || (status === 'missing' && statusRows === 0);
	ocoPackageComparisonUpdateSelection();
}

function ocoPackageComparisonToggleSelection(checked) {
	let table = document.querySelector('.oco-package-comparison-table');
	if(!table) return;
	table.querySelectorAll('tbody tr:not([hidden]) input.oco-package-comparison-selection').forEach(function(checkbox) {
		checkbox.checked = checked;
	});
	ocoPackageComparisonUpdateSelection();
}

function ocoPackageComparisonUpdateSelection() {
	let comparison = document.querySelector('.oco-package-comparison');
	if(!comparison) return;
	let table = comparison.querySelector('.oco-package-comparison-table');
	let selected = table.querySelectorAll('input.oco-package-comparison-selection:checked').length;
	let selectedCounter = comparison.querySelector('.oco-package-comparison-selected-count');
	if(selectedCounter) selectedCounter.innerText = selected;
	let deployButton = comparison.querySelector('.oco-package-comparison-deploy');
	if(deployButton) deployButton.disabled = selected === 0;

	let visibleCheckboxes = Array.from(table.querySelectorAll('tbody tr:not([hidden]) input.oco-package-comparison-selection'));
	let visibleSelected = visibleCheckboxes.filter(function(checkbox) { return checkbox.checked; }).length;
	let toggleAll = table.querySelector('thead input[type="checkbox"]');
	if(toggleAll) {
		toggleAll.checked = visibleCheckboxes.length > 0 && visibleSelected === visibleCheckboxes.length;
		toggleAll.indeterminate = visibleSelected > 0 && visibleSelected < visibleCheckboxes.length;
	}
}

function ocoPackageComparisonDeploySelected() {
	let comparison = document.querySelector('.oco-package-comparison');
	if(!comparison) return;
	let packages = Array.from(comparison.querySelectorAll('input.oco-package-comparison-selection:checked')).map(function(checkbox) {
		let versionSelection = checkbox.closest('tr').querySelector('select.oco-package-comparison-version');
		let selectedOption = versionSelection.selectedOptions[0];
		return {
			'id': versionSelection.value,
			'name': versionSelection.getAttribute('data-package-name')+' ('+selectedOption.getAttribute('data-version')+')',
		};
	});
	if(packages.length === 0) {
		emitMessage(LANG['no_elements_selected'], '', MESSAGE_TYPE_WARNING);
		return;
	}

	refreshContentDeploy(packages, [], {
		'id': comparison.getAttribute('data-target-computer-id'),
		'name': comparison.getAttribute('data-target-computer-hostname'),
	});
}

window.addEventListener('DOMContentLoaded', function() {
	let explorerContent = document.getElementById('explorer-content');
	if(!explorerContent) return;
	let observer = new MutationObserver(function() {
		window.setTimeout(ocoPackageComparisonInitialize, 0);
	});
	observer.observe(explorerContent, {childList:true, subtree:true});
	ocoPackageComparisonInitialize();
});
