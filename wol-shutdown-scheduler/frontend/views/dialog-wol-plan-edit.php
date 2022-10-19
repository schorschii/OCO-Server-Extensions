<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.php');

$wolcl = new WolShutdownCoreLogic($db, $currentSystemUser);

$wolPlan = null;
$wolGroupId = $_GET['wol_group_id'] ?? -1;
try {
	$wolPlan = $wolcl->getWolPlan($_GET['id'] ?? -1);
} catch(Exception $ignored) {}
?>

<input type='hidden' id='txtEditWolPlanId' value='<?php echo $wolPlan->id??-1; ?>'></input>
<input type='hidden' id='txtEditWolGroupId' value='<?php echo $wolGroupId; ?>'></input>
<table class='fullwidth aligned scheduleedit'>
	<tr>
		<th><?php echo LANG('computer_group'); ?></th>
		<td colspan='2'>
			<select id='sltEditWolPlanComputerGroupId' class='fullwidth' autofocus='true'>
				<option value='' selected disabled><?php echo LANG('select_placeholder'); ?></option>
				<?php echoComputerGroupOptions($cl, null, 0, $wolPlan->computer_group_id??-1); ?>
			</select>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('schedule'); ?></th>
		<td colspan='2'>
			<select id='sltEditWolPlanWolScheduleId' class='fullwidth'>
				<option value='' selected disabled><?php echo LANG('select_placeholder'); ?></option>
				<?php foreach($wolcl->getWolSchedules($wolGroupId) as $schedule) { ?>
					<option value='<?php echo $schedule->id; ?>' <?php if($schedule->id==($wolPlan->wol_schedule_id??-1)) echo 'selected'; ?>><?php echo htmlspecialchars($schedule->name); ?></option>
				<?php } ?>
			</select>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('shutdown_credential'); ?></th>
		<td colspan='2'>
			<select id='sltEditWolPlanShutdownCredential' class='fullwidth'>
				<option value='' selected><?php echo LANG('no_credential_agent'); ?></option>
				<?php foreach(WolShutdownCoreLogic::getShutdownCredentials() as $credential) { ?>
					<option value='<?php echo htmlspecialchars($credential->name,ENT_QUOTES); ?>' <?php if($credential->name==($wolPlan->shutdown_credential??-1)) echo 'selected'; ?>><?php echo htmlspecialchars($credential->name); ?></option>
				<?php } ?>
			</select>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('valid_from'); ?></th>
		<td>
			<label><input type='radio' name='rdoEditWolPlanStartDate' value='unlimited' checked='true' onclick='txtEditWolPlanStartDate.value=""'/>sofort</label>
		</td>
		<td class='dualInput'>
			<input type='radio' id='rdoEditWolPlanStartDate' name='rdoEditWolPlanStartDate' value='date' <?php if($wolPlan&&$wolPlan->start_time) echo 'checked'; ?>/>
			<label for='rdoEditWolPlanStartDate'><input type='date' class='fullwidth' id='txtEditWolPlanStartDate' onchange='rdoEditWolPlanStartDate.checked=true' min='<?php echo date('Y-m-d'); ?>' value='<?php echo $wolPlan&&$wolPlan->start_time ? date('Y-m-d', strtotime($wolPlan->start_time)) : ''; ?>'></input></label>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('valid_until'); ?></th>
		<td>
			<label><input type='radio' name='rdoEditWolPlanEndDate' value='unlimited' checked='true' onclick='txtEditWolPlanEndDate.value=""'/>unbegrenzt</label>
		</td>
		<td class='dualInput'>
			<input type='radio' id='rdoEditWolPlanEndDate' name='rdoEditWolPlanEndDate' value='date' <?php if($wolPlan&&$wolPlan->end_time) echo 'checked'; ?>/>
			<label for='rdoEditWolPlanEndDate'><input type='date' class='fullwidth' id='txtEditWolPlanEndDate' onchange='rdoEditWolPlanEndDate.checked=true' min='<?php echo date('Y-m-d'); ?>' value='<?php echo $wolPlan&&$wolPlan->end_time ? date('Y-m-d', strtotime($wolPlan->end_time)) : ''; ?>'></input></label>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('description'); ?></th>
		<td colspan='2'><textarea class='fullwidth' placeholder='<?php echo LANG('optional'); ?>' id='txtEditWolPlanDescription'><?php echo htmlspecialchars($wolPlan->description??''); ?></textarea></td>
	</tr>
</table>

<div class='controls right'>
	<button onclick='hideDialog();showLoader(false);showLoader2(false);'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button id='btnEditWolPlan' class='primary' onclick='editWolPlan(
		txtEditWolPlanId.value, txtEditWolGroupId.value,
		sltEditWolPlanComputerGroupId.value,
		sltEditWolPlanWolScheduleId.value,
		sltEditWolPlanShutdownCredential.value,
		(txtEditWolPlanStartDate.value=="" ? "" : txtEditWolPlanStartDate.value+" 00:00:00"),
		(txtEditWolPlanEndDate.value=="" ? "" : txtEditWolPlanEndDate.value+" 23:59:59"),
		txtEditWolPlanDescription.value,
		)'><img src='img/send.white.svg'>&nbsp;<span id='spnBtnEditWolPlan'><?php echo $wolPlan ? LANG('change') : LANG('create'); ?></span></button>
</div>
