function createWolGroup(parent_id=0) {
	var newName = prompt(L__ENTER_NAME);
	if(newName != null) {
		ajaxRequestPost('ajax-handler/wol-shutdown-scheduler.php', urlencodeObject({'create_group':newName, 'parent_id':parent_id}), null, function(text){
			refreshSidebar(); refreshContentExplorer('views/wol-shutdown-scheduler.php?id='+parseInt(text));
			emitMessage(L__GROUP_CREATED, newName, MESSAGE_TYPE_SUCCESS);
		});
	}
}
function renameWolGroup(id, oldName) {
	var newValue = prompt(L__ENTER_NAME, oldName);
	if(newValue != null) {
		ajaxRequestPost('ajax-handler/wol-shutdown-scheduler.php', urlencodeObject({'rename_group_id':id, 'new_name':newValue}), null, function() {
			refreshContent(); refreshSidebar();
			emitMessage(L__GROUP_RENAMED, newValue, MESSAGE_TYPE_SUCCESS);
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
	if(confirm(L__CONFIRM_DELETE_GROUP)) {
		ajaxRequestPost('ajax-handler/wol-shutdown-scheduler.php', paramString, null, function() {
			refreshContentExplorer('views/wol-shutdown-scheduler.php'); refreshSidebar();
			emitMessage(L__GROUP_DELETED, infoText, MESSAGE_TYPE_SUCCESS);
		});
	}
}

function showDialogEditWolSchedule(id=-1, groupId=-1) {
	var method = L__CREATE;
	if(id > 0) method = L__CHANGE;
	showDialogAjax(method, 'views/dialog-wol-schedule-edit.php?id='+encodeURIComponent(id)+'&wol_group_id='+encodeURIComponent(groupId), DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO);
}
function editWolSchedule(id, wol_group_id, name, monday, tuesday, wednesday, thursday, friday, saturday, sunday) {
	var method = L__CREATED;
	if(id > 0) method = L__SAVED;
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
			emitMessage(L__ERROR+' '+status+' '+statusText, responseText, MESSAGE_TYPE_ERROR);
		}
	);
}
function confirmRemoveWolSchedule(id) {
	if(confirm(L__CONFIRM_DELETE)) {
		ajaxRequestPost('ajax-handler/wol-shutdown-scheduler.php', urlencodeObject({'remove_wol_schedule_id': id}), null, function() {
			refreshContent();
			emitMessage(L__OBJECT_DELETED, '', MESSAGE_TYPE_SUCCESS);
		});
	}
}

function showDialogEditWolPlan(id=-1, groupId=-1) {
	var method = L__CREATE;
	if(id > 0) method = L__CHANGE;
	showDialogAjax(method, 'views/dialog-wol-plan-edit.php?id='+encodeURIComponent(id)+'&wol_group_id='+encodeURIComponent(groupId), DIALOG_BUTTONS_NONE, DIALOG_SIZE_AUTO);
}
function editWolPlan(id, wol_group_id, computer_group_id, wol_schedule_id, shutdown_credential, start_time, end_time, description) {
	var method = L__CREATED;
	if(id > 0) method = L__SAVED;
	ajaxRequestPost('ajax-handler/wol-shutdown-scheduler.php',
		urlencodeObject({
			'edit_wol_plan_id': id,
			'wol_group_id': wol_group_id,
			'computer_group_id': computer_group_id,
			'wol_schedule_id': wol_schedule_id,
			'shutdown_credential': shutdown_credential,
			'start_time': start_time,
			'end_time': end_time,
			'description': description,
		}), null,
		function() {
			hideDialog(); refreshContent();
			emitMessage(method, description, MESSAGE_TYPE_SUCCESS);
		},
		function(status, statusText, responseText) {
			emitMessage(L__ERROR+' '+status+' '+statusText, responseText, MESSAGE_TYPE_ERROR);
		}
	);
}
function confirmRemoveWolPlan(id) {
	if(confirm(L__CONFIRM_DELETE)) {
		ajaxRequestPost('ajax-handler/wol-shutdown-scheduler.php', urlencodeObject({'remove_wol_plan_id': id}), null, function() {
			refreshContent();
			emitMessage(L__OBJECT_DELETED, '', MESSAGE_TYPE_SUCCESS);
		});
	}
}
