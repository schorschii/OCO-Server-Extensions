function paketeerDialogCreatePackage(software, name, links) {
	showDialogAjax(LANG['create_package'],
		'views/dialog-paketeer-create.php?'+urlencodeObject({
			'software': software,
			'name': name,
			'links': links,
		}),
		DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO,
		function(dialogContainer){
			dialogContainer.querySelectorAll('button[name=create]')[0].addEventListener('click', (e)=>{
				paketeerCreatePackage(
					dialogContainer,
					dialogContainer.querySelectorAll('input[name=software]')[0].value,
					dialogContainer.querySelectorAll('input[name=links]')[0].value,
					dialogContainer.querySelectorAll('input[name=package_family]')[0].value,
					dialogContainer.querySelectorAll('input[name=version]')[0].value,
					dialogContainer.querySelectorAll('textarea[name=notes]')[0].value,
				);
			});
		});
}
function paketeerCreatePackage(dialogContainer, software, links, family_name, version, notes) {
	let btnCloseDialog = dialogContainer.querySelectorAll('button.dialogClose')[0];
	let btnCreatePackage = dialogContainer.querySelectorAll('button[name=create]')[0];
	let prgDownload = dialogContainer.querySelectorAll('.progressbar-container')[0];
	let prgDownloadText = dialogContainer.querySelectorAll('.progressbar-container > .progresstext')[0];
	setInputsDisabled(dialogContainer, true);
	btnCloseDialog.classList.add('hidden');
	btnCreatePackage.classList.add('hidden');
	prgDownload.classList.remove('hidden');

	var xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		if(this.readyState == 3) {
			let lines = this.responseText.trim().split("\n");
			if(!lines.length) return;
			let progress = parseInt(lines[lines.length-1].trim());
			if(progress > 100) {
				prgDownload.classList.add('animated');
				prgDownloadText.innerText = LANG['in_progress'];
				prgDownload.style.setProperty('--progress', '100%');
			} else {
				prgDownload.classList.remove('animated');
				prgDownloadText.innerText = progress+'%';
				prgDownload.style.setProperty('--progress', progress+'%');
			}
		} else if(this.readyState == 4 && this.status == 200) {
			dialogContainer.close();
			let lines = this.responseText.trim().split("\n");
			let newPackageId = parseInt(lines[lines.length-1].trim());
			refreshContentExplorer('views/package-details.php?id='+newPackageId);
			emitMessage(LANG['package_created'], family_name+"\n"+version, MESSAGE_TYPE_SUCCESS);
		} else if(this.readyState == 4) {
			setInputsDisabled(dialogContainer, false);
			btnCloseDialog.classList.remove('hidden');
			btnCreatePackage.classList.remove('hidden');
			prgDownload.classList.add('hidden');
			emitMessage(LANG['error']+' '+this.status+' '+this.statusText, this.responseText, MESSAGE_TYPE_ERROR, null);
		}
	};
	xhttp.open('POST', 'ajax-handler/paketeer.php', true);
	xhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	xhttp.send(urlencodeObject({
		'software': software,
		'links': links,
		'family_name': family_name,
		'version': version,
		'notes': notes
	}));
}
