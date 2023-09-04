function installShowPage(page) {
	refreshContentExplorer('views/views.d/install-tool.php?form='+encodeURIComponent(page));
}
function installDoManualInstall() {
	ajaxRequestPost('ajax-handler/ajax-handler.d/install-tool.php',
		urlencodeObject({
			'action': 'manual-install',
			'hostname': txtInstallHostname.value,
			'identifier': txtInstallIdentifier.value,
			'packagegroup': sltPackageGroup.value,
			'removeold': chkInstallRemoveOld.checked ? '1' : '0',
		}), null,
		function(text) {
			divInstallStatus.innerHTML = text;
			installSetDone();
		},
		function(statusCode, statusText, text) {
			console.log('Error '+statusCode+' '+statusText);
			divInstallStatus.innerHTML = text;
			installSetDone();
		}
	);
	installSetBusy();
}
function installDoRemove() {
	ajaxRequestPost('ajax-handler/ajax-handler.d/install-tool.php',
		urlencodeObject({
			'action': 'remove-install',
			'identifier': txtRemoveIdentifier.value,
		}), null,
		function(text) {
			divInstallStatus.innerHTML = text;
			installSetDone();
		},
		function(statusCode, statusText, text) {
			console.log('Error '+statusCode+' '+statusText);
			divInstallStatus.innerHTML = text;
			installSetDone();
		}
	);
	installSetBusy();
}
function installSetBusy() {
	divInstallStatus.innerHTML = divInstallProgressBar.innerHTML;
	var inputs = document.querySelectorAll('#installForm input, #installForm select, #installForm button');
	for(var i = 0; i < inputs.length; i++) {
		inputs[i].disabled = true;
	}
}
function installSetDone() {
	var inputs = document.querySelectorAll('#installForm input, #installForm select, #installForm button');
	for(var i = 0; i < inputs.length; i++) {
		inputs[i].disabled = false;
	}
}

function focusNext(insideElement) {
	// add all elements we want to include in our selection
	var focussableElements = 'a:not([disabled]), button:not([disabled]), input[type=text]:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([disabled]):not([tabindex="-1"])';
	if(document.activeElement && insideElement) {
		var focussable = Array.prototype.filter.call(insideElement.querySelectorAll(focussableElements),
			function(element) { // check for visibility while always include the current activeElement 
			return element.offsetWidth > 0 || element.offsetHeight > 0 || element === document.activeElement
		});
		var index = focussable.indexOf(document.activeElement);
		if(index > -1) {
			var nextElement = focussable[index + 1] || focussable[0];
			nextElement.focus();
		}
	}
}
