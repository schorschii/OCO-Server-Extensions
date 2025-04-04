function paketeerDialogCreatePackage(software, name, links) {
	showDialogAjax(LANG['create_package'],
		'views/dialog-paketeer-create.php?'+urlencodeObject({
			'software': software,
			'name': name,
			'links': links,
		}),
		DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO);
}
function paketeerCreatePackage(software, links, family_name, version, notes) {
	setInputsDisabled(frmPaketeerCreatePackage, true);
	btnCloseDialog.classList.add('hidden');
	btnPaketeerCreatePackage.classList.add('hidden');
	prgPaketeerCreatePackage.classList.remove('hidden');

	var xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		if(this.readyState == 3) {
			let lines = this.responseText.trim().split("\n");
			if(!lines.length) return;
			let progress = parseInt(lines[lines.length-1].trim());
			if(progress > 100) {
				prgPaketeerCreatePackage.classList.add('animated');
				prgPaketeerCreatePackageText.innerText = LANG['in_progress'];
				prgPaketeerCreatePackage.style.setProperty('--progress', '100%');
			} else {
				prgPaketeerCreatePackage.classList.remove('animated');
				prgPaketeerCreatePackageText.innerText = progress+'%';
				prgPaketeerCreatePackage.style.setProperty('--progress', progress+'%');
			}
		} else if(this.readyState == 4 && this.status == 200) {
			hideDialog();
			let lines = this.responseText.trim().split("\n");
			let newPackageId = parseInt(lines[lines.length-1].trim());
			refreshContentExplorer('views/package-details.php?id='+newPackageId);
			emitMessage(LANG['package_created'], family_name+"\n"+version, MESSAGE_TYPE_SUCCESS);
		} else if(this.readyState == 4) {
			setInputsDisabled(frmPaketeerCreatePackage, false);
			btnCloseDialog.classList.remove('hidden');
			btnPaketeerCreatePackage.classList.remove('hidden');
			prgPaketeerCreatePackage.classList.add('hidden');
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
