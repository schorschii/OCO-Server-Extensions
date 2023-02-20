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
	var method = LANG['create'];
	if(id > 0) method = LANG['change'];
	showDialogAjax(method, 'views/dialog-wol-schedule-edit.php?id='+encodeURIComponent(id)+'&wol_group_id='+encodeURIComponent(groupId), DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO);
}
function editWolSchedule(id, wol_group_id, name, monday, tuesday, wednesday, thursday, friday, saturday, sunday) {
	var method = LANG['created'];
	if(id > 0) method = LANG['saved'];
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
			hideDialog(); refreshContent();
			emitMessage(method, name, MESSAGE_TYPE_SUCCESS);
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
	var method = LANG['create'];
	if(id > 0) method = LANG['change'];
	showDialogAjax(method, 'views/dialog-wol-plan-edit.php?id='+encodeURIComponent(id)+'&wol_group_id='+encodeURIComponent(groupId), DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO);
}
function editWolPlan(id, wol_group_id, computer_group_id, wol_schedule_id, start_time, end_time, description) {
	var method = LANG['created'];
	if(id > 0) method = LANG['saved'];
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
			hideDialog(); refreshContent();
			emitMessage(method, description, MESSAGE_TYPE_SUCCESS);
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
