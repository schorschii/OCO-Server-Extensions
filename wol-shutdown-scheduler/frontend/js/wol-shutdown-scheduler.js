function createWolGroup(parent_id=0) {
	var newName = prompt(LANG['enter_name']);
	if(newName != null) {
		ajaxRequestPost('ajax-handler/wol-shutdown-scheduler.php', urlencodeObject({'create_group':newName, 'parent_id':parent_id}), null, function(text){
			refreshSidebar(); refreshContentExplorer('views/wol-shutdown-scheduler.php?id='+parseInt(text));
			emitMessage(LANG['group_created'], newName, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function renameWolGroup(id, oldName) {
	var newValue = prompt(LANG['enter_name'], oldName);
	if(newValue != null) {
		ajaxRequestPost('ajax-handler/wol-shutdown-scheduler.php', urlencodeObject({'rename_group_id':id, 'new_name':newValue}), null, function() {
			refreshContent(); refreshSidebar();
			emitMessage(LANG['group_renamed'], newValue, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function confirmRemoveWolGroup(ids, event=null, infoText='') {
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_group_id[]', 'value':entry});
	});
	if(event != null && event.shiftKey) {
		params.push({'key':'force', 'value':'1'});
	}
	var paramString = urlencodeArray(params);
	if(confirm(LANG['confirm_delete_group'])) {
		ajaxRequestPost('ajax-handler/wol-shutdown-scheduler.php', paramString, null, function() {
			refreshContentExplorer('views/wol-shutdown-scheduler.php'); refreshSidebar();
			emitMessage(LANG['group_deleted'], infoText, MESSAGE_TYPE_SUCCESS);
		});
	}
}

function showDialogEditWolSchedule(id=-1, groupId=-1) {
	showDialogAjax(id>0 ? LANG['change'] : LANG['create'],
		'views/dialog-wol-schedule-edit.php?id='+encodeURIComponent(id)+'&wol_group_id='+encodeURIComponent(groupId),
		DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO,
		function(dialogContainer){
			let txtId = dialogContainer.querySelectorAll('input[name=id]')[0];
			let txtWolGroupId = dialogContainer.querySelectorAll('input[name=wol_group_id]')[0];
			let txtName = dialogContainer.querySelectorAll('input[name=name]')[0];
			let txtMondayStart = dialogContainer.querySelectorAll('input[name=monday_start]')[0];
			let txtMondayEnd = dialogContainer.querySelectorAll('input[name=monday_end]')[0];
			let txtTuesdayStart = dialogContainer.querySelectorAll('input[name=tuesday_start]')[0];
			let txtTuesdayEnd = dialogContainer.querySelectorAll('input[name=tuesday_end]')[0];
			let txtWednesdayStart = dialogContainer.querySelectorAll('input[name=wednesday_start]')[0];
			let txtWednesdayEnd = dialogContainer.querySelectorAll('input[name=wednesday_end]')[0];
			let txtThursdayStart = dialogContainer.querySelectorAll('input[name=thursday_start]')[0];
			let txtThursdayEnd = dialogContainer.querySelectorAll('input[name=thursday_end]')[0];
			let txtFridayStart = dialogContainer.querySelectorAll('input[name=friday_start]')[0];
			let txtFridayEnd = dialogContainer.querySelectorAll('input[name=friday_end]')[0];
			let txtSaturdayStart = dialogContainer.querySelectorAll('input[name=saturday_start]')[0];
			let txtSaturdayEnd = dialogContainer.querySelectorAll('input[name=saturday_end]')[0];
			let txtSundayStart = dialogContainer.querySelectorAll('input[name=sunday_start]')[0];
			let txtSundayEnd = dialogContainer.querySelectorAll('input[name=sunday_end]')[0];
			let btnsRemoveTime = dialogContainer.querySelectorAll('button.removeTime');
			for(let i=0; i<btnsRemoveTime.length; i++) {
				btnsRemoveTime[i].addEventListener('click', (e)=>{
					let txtsDateTime = e.srcElement.parentElement.parentElement.querySelectorAll('input');
					for(let n=0; n<txtsDateTime.length; n++) {
						txtsDateTime[n].value = '';
					}
				});
			}
			dialogContainer.querySelectorAll('button[name=edit]')[0].addEventListener('click', (e)=>{
				editWolSchedule(
					dialogContainer,
					txtId.value, txtWolGroupId.value,
					txtName.value,
					txtMondayStart.value+'-'+txtMondayEnd.value,
					txtTuesdayStart.value+'-'+txtTuesdayEnd.value,
					txtWednesdayStart.value+'-'+txtWednesdayEnd.value,
					txtThursdayStart.value+'-'+txtThursdayEnd.value,
					txtFridayStart.value+'-'+txtFridayEnd.value,
					txtSaturdayStart.value+'-'+txtSaturdayEnd.value,
					txtSundayStart.value+'-'+txtSundayEnd.value
				);
			});
		}
	);
}
function editWolSchedule(dialogContainer, id, wol_group_id, name, monday, tuesday, wednesday, thursday, friday, saturday, sunday) {
	ajaxRequestPost('ajax-handler/wol-shutdown-scheduler.php',
		urlencodeObject({
			'edit_wol_schedule_id': id,
			'wol_group_id': wol_group_id,
			'name': name,
			'monday': monday,
			'tuesday': tuesday,
			'wednesday': wednesday,
			'thursday': thursday,
			'friday': friday,
			'saturday': saturday,
			'sunday': sunday,
		}), null,
		function() {
			dialogContainer.close(); refreshContent();
			emitMessage(id>0 ? LANG['saved'] : LANG['created'], name, MESSAGE_TYPE_SUCCESS);
		},
		function(status, statusText, responseText) {
			emitMessage(LANG['error']+' '+status+' '+statusText, responseText, MESSAGE_TYPE_ERROR);
		}
	);
}
function removeSelectedWolSchedule(checkboxName, attributeName=null) {
	var ids = [];
	document.getElementsByName(checkboxName).forEach(function(entry) {
		if(entry.checked) {
			if(attributeName == null) {
				ids.push(entry.value);
			} else {
				ids.push(entry.getAttribute(attributeName));
			}
		}
	});
	if(ids.length == 0) {
		emitMessage(LANG['no_elements_selected'], '', MESSAGE_TYPE_WARNING);
		return;
	}
	confirmRemoveWolSchedule(ids);
}
function confirmRemoveWolSchedule(ids) {
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_wol_schedule_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	if(confirm(LANG['confirm_delete'])) {
		ajaxRequestPost('ajax-handler/wol-shutdown-scheduler.php', paramString, null, function() {
			refreshContent();
			emitMessage(LANG['object_deleted'], '', MESSAGE_TYPE_SUCCESS);
		});
	}
}

function showDialogEditWolPlan(id=-1, groupId=-1) {
	showDialogAjax(id>0 ? LANG['change'] : LANG['create'], 'views/dialog-wol-plan-edit.php?id='+encodeURIComponent(id)+'&wol_group_id='+encodeURIComponent(groupId), DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO, function(dialogContainer){
		let txtId = dialogContainer.querySelectorAll('input[name=id]')[0];
		let txtWolGroupId = dialogContainer.querySelectorAll('input[name=wol_group_id]')[0];
		let sltComputerGroup = dialogContainer.querySelectorAll('select[name=computer_group_id]')[0];
		let sltWolSchedule = dialogContainer.querySelectorAll('select[name=wol_schedule_id]')[0];
		let txtStartDate = dialogContainer.querySelectorAll('input[name=start_date]')[0];
		let txtEndDate = dialogContainer.querySelectorAll('input[name=end_date]')[0];
		let rdoStartModeUnlimited = dialogContainer.querySelectorAll('input[name=start_mode][value=unlimited]')[0];
		let rdoStartModeDate = dialogContainer.querySelectorAll('input[name=start_mode][value=date]')[0];
		let rdoEndModeUnlimited = dialogContainer.querySelectorAll('input[name=end_mode][value=unlimited]')[0];
		let rdoEndModeDate = dialogContainer.querySelectorAll('input[name=end_mode][value=date]')[0];
		let txtNotes = dialogContainer.querySelectorAll('textarea[name=notes]')[0];
		txtStartDate.addEventListener('change', (e)=>{
			rdoStartModeDate.checked = true;
		});
		txtEndDate.addEventListener('change', (e)=>{
			rdoEndModeDate.checked = true;
		});
		rdoStartModeUnlimited.addEventListener('click', (e)=>{
			txtStartDate.value = '';
		});
		rdoEndModeUnlimited.addEventListener('click', (e)=>{
			txtEndDate.value = '';
		});
		dialogContainer.querySelectorAll('button[name=edit]')[0].addEventListener('click', (e)=>{
			editWolPlan(
				dialogContainer,
				txtId.value, txtWolGroupId.value,
				sltComputerGroup.value,
				sltWolSchedule.value,
				(txtStartDate.value=='' ? '' : txtStartDate.value+' 00:00:00'),
				(txtEndDate.value=='' ? '' : txtEndDate.value+' 23:59:59'),
				txtNotes.value,
			);
		});
	});
}
function editWolPlan(dialogContainer, id, wol_group_id, computer_group_id, wol_schedule_id, start_time, end_time, description) {
	ajaxRequestPost('ajax-handler/wol-shutdown-scheduler.php',
		urlencodeObject({
			'edit_wol_plan_id': id,
			'wol_group_id': wol_group_id,
			'computer_group_id': computer_group_id,
			'wol_schedule_id': wol_schedule_id,
			'start_time': start_time,
			'end_time': end_time,
			'description': description,
		}), null,
		function() {
			dialogContainer.close(); refreshContent();
			emitMessage(id>0 ? LANG['saved'] : LANG['created'], description, MESSAGE_TYPE_SUCCESS);
		},
		function(status, statusText, responseText) {
			emitMessage(LANG['error']+' '+status+' '+statusText, responseText, MESSAGE_TYPE_ERROR);
		}
	);
}
function removeSelectedWolPlan(checkboxName, attributeName=null) {
	var ids = [];
	document.getElementsByName(checkboxName).forEach(function(entry) {
		if(entry.checked) {
			if(attributeName == null) {
				ids.push(entry.value);
			} else {
				ids.push(entry.getAttribute(attributeName));
			}
		}
	});
	if(ids.length == 0) {
		emitMessage(LANG['no_elements_selected'], '', MESSAGE_TYPE_WARNING);
		return;
	}
	confirmRemoveWolPlan(ids);
}
function confirmRemoveWolPlan(ids) {
	var params = [];
	ids.forEach(function(entry) {
		params.push({'key':'remove_wol_plan_id[]', 'value':entry});
	});
	var paramString = urlencodeArray(params);
	if(confirm(LANG['confirm_delete'])) {
		ajaxRequestPost('ajax-handler/wol-shutdown-scheduler.php', paramString, null, function() {
			refreshContent();
			emitMessage(LANG['object_deleted'], '', MESSAGE_TYPE_SUCCESS);
		});
	}
}
