<?php
$SUBVIEW = 1;
if(!isset($db) || !isset($cl)) die();

$wolcl = new WolShutdownCoreLogic($db, $currentSystemUser);

try {

	if(isset($_POST['create_group'])) {
		die(
			$wolcl->createWolGroup($_POST['create_group'], empty($_POST['parent_id']) ? null : intval($_POST['parent_id']))
		);
	}

	if(!empty($_POST['rename_group_id']) && isset($_POST['new_name'])) {
		$wolcl->renameWolGroup($_POST['rename_group_id'], $_POST['new_name']);
		die();
	}

	if(!empty($_POST['remove_group_id']) && is_array($_POST['remove_group_id'])) {
		foreach($_POST['remove_group_id'] as $id) {
			$wolcl->removeWolGroup($id, !empty($_POST['force']));
		}
		die();
	}

	if(!empty($_POST['edit_wol_schedule_id'])
	&& isset($_POST['wol_group_id'])
	&& isset($_POST['name'])
	&& isset($_POST['monday'])
	&& isset($_POST['tuesday'])
	&& isset($_POST['wednesday'])
	&& isset($_POST['thursday'])
	&& isset($_POST['friday'])
	&& isset($_POST['saturday'])
	&& isset($_POST['sunday'])) {
		if($_POST['edit_wol_schedule_id'] == '-1') {
			die($wolcl->createWolSchedule(
				$_POST['wol_group_id'],
				$_POST['name'],
				$_POST['monday'],
				$_POST['tuesday'],
				$_POST['wednesday'],
				$_POST['thursday'],
				$_POST['friday'],
				$_POST['saturday'],
				$_POST['sunday']
			));
		} else {
			$wolcl->editWolSchedule($_POST['edit_wol_schedule_id'],
				$_POST['wol_group_id'],
				$_POST['name'],
				$_POST['monday'],
				$_POST['tuesday'],
				$_POST['wednesday'],
				$_POST['thursday'],
				$_POST['friday'],
				$_POST['saturday'],
				$_POST['sunday']
			);
		}
		die();
	}

	if(!empty($_POST['remove_wol_schedule_id']) && is_array($_POST['remove_wol_schedule_id'])) {
		foreach($_POST['remove_wol_schedule_id'] as $id) {
			$wolcl->removeWolSchedule($id);
		}
		die();
	}

	if(!empty($_POST['edit_wol_plan_id'])
	&& isset($_POST['wol_group_id'])
	&& isset($_POST['computer_group_id'])
	&& isset($_POST['wol_schedule_id'])
	&& isset($_POST['shutdown_credential'])
	&& isset($_POST['start_time'])
	&& isset($_POST['end_time'])
	&& isset($_POST['description'])) {
		if($_POST['edit_wol_plan_id'] == '-1') {
			die($wolcl->createWolPlan(
				$_POST['wol_group_id'],
				$_POST['computer_group_id'],
				$_POST['wol_schedule_id'],
				$_POST['shutdown_credential'],
				$_POST['start_time'],
				$_POST['end_time'],
				$_POST['description']
			));
		} else {
			$wolcl->editWolPlan($_POST['edit_wol_plan_id'],
				$_POST['wol_group_id'],
				$_POST['computer_group_id'],
				$_POST['wol_schedule_id'],
				$_POST['shutdown_credential'],
				$_POST['start_time'],
				$_POST['end_time'],
				$_POST['description']
			);
		}
		die();
	}

	if(!empty($_POST['remove_wol_plan_id']) && is_array($_POST['remove_wol_plan_id'])) {
		foreach($_POST['remove_wol_plan_id'] as $id) {
			$wolcl->removeWolPlan($id);
		}
		die();
	}

} catch(UnexpectedValueException $e) {
	header('HTTP/1.1 400 Invalid Request');
	die(htmlspecialchars($e->getMessage()));
} catch(PermissionException $e) {
	header('HTTP/1.1 403 Forbidden');
	die(LANG('permission_denied'));
} catch(NotFoundException $e) {
	header('HTTP/1.1 404 Not Found');
	die(LANG('not_found'));
} catch(Exception $e) {
	header('HTTP/1.1 400 Invalid Request');
	die(htmlspecialchars($e->getMessage()));
}

header('HTTP/1.1 400 Invalid Request');
die(LANG('unknown_method'));
