<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.php');

$wolcl = new WolShutdownCoreLogic($db, $currentSystemUser);

$wolSchedule = null;
$wolGroupId = $_GET['wol_group_id'] ?? -1;
try {
	$wolSchedule = $wolcl->getWolSchedule($_GET['id'] ?? -1);
} catch(Exception $ignored) {}
?>

<input type='hidden' id='txtEditWolScheduleId' value='<?php echo $wolSchedule->id??-1; ?>'></input>
<input type='hidden' id='txtEditWolGroupId' value='<?php echo $wolGroupId; ?>'></input>
<table class='fullwidth aligned scheduleedit'>
	<tr>
		<th><?php echo LANG('name'); ?></th>
		<td colspan='3'><input type='text' class='fullwidth' autocomplete='new-password' id='txtEditWolScheduleName' autofocus='true' value='<?php echo $wolSchedule->name??''; ?>'></input></td>
	</tr>
	<tr>
		<th></th>
		<th><?php echo LANG('start'); ?></th>
		<th><?php echo LANG('shutdown'); ?></th>
	</tr>
	<tr>
		<th><?php echo LANG('monday'); ?></th>
		<td><input type='time' class='fullwidth' id='txtEditWolScheduleMondayStart' value='<?php echo $wolSchedule&&$wolSchedule->monday ? explode('-',$wolSchedule->monday)[0] : ''; ?>'></input></td>
		<td><input type='time' class='fullwidth' id='txtEditWolScheduleMondayEnd' value='<?php echo $wolSchedule&&$wolSchedule->monday ? explode('-',$wolSchedule->monday)[1] : ''; ?>'></input></td>
		<td><button onclick='txtEditWolScheduleMondayStart.value="";txtEditWolScheduleMondayEnd.value=""' title='<?php echo LANG('remove_time'); ?>' class='small'><img src='img/close.dyn.svg'></button></td>
	</tr>
	<tr>
		<th><?php echo LANG('tuesday'); ?></th>
		<td><input type='time' class='fullwidth' id='txtEditWolScheduleTuesdayStart' value='<?php echo $wolSchedule&&$wolSchedule->tuesday ? explode('-',$wolSchedule->tuesday)[0] : ''; ?>'></input></td>
		<td><input type='time' class='fullwidth' id='txtEditWolScheduleTuesdayEnd' value='<?php echo $wolSchedule&&$wolSchedule->tuesday ? explode('-',$wolSchedule->tuesday)[1] : ''; ?>'></input></td>
		<td><button onclick='txtEditWolScheduleTuesdayStart.value="";txtEditWolScheduleTuesdayEnd.value=""' title='<?php echo LANG('remove_time'); ?>' class='small'><img src='img/close.dyn.svg'></button></td>
	</tr>
	<tr>
		<th><?php echo LANG('wednesday'); ?></th>
		<td><input type='time' class='fullwidth' id='txtEditWolScheduleWednesdayStart' value='<?php echo $wolSchedule&&$wolSchedule->wednesday ? explode('-',$wolSchedule->wednesday)[0] : ''; ?>'></input></td>
		<td><input type='time' class='fullwidth' id='txtEditWolScheduleWednesdayEnd' value='<?php echo $wolSchedule&&$wolSchedule->wednesday ? explode('-',$wolSchedule->wednesday)[1] : ''; ?>'></input></td>
		<td><button onclick='txtEditWolScheduleWednesdayStart.value="";txtEditWolScheduleWednesdayEnd.value=""' title='<?php echo LANG('remove_time'); ?>' class='small'><img src='img/close.dyn.svg'></button></td>
	</tr>
	<tr>
		<th><?php echo LANG('thursday'); ?></th>
		<td><input type='time' class='fullwidth' id='txtEditWolScheduleThursdayStart' value='<?php echo $wolSchedule&&$wolSchedule->thursday ? explode('-',$wolSchedule->thursday)[0] : ''; ?>'></input></td>
		<td><input type='time' class='fullwidth' id='txtEditWolScheduleThursdayEnd' value='<?php echo $wolSchedule&&$wolSchedule->thursday ? explode('-',$wolSchedule->thursday)[1] : ''; ?>'></input></td>
		<td><button onclick='txtEditWolScheduleThursdayStart.value="";txtEditWolScheduleThursdayEnd.value=""' title='<?php echo LANG('remove_time'); ?>' class='small'><img src='img/close.dyn.svg'></button></td>
	</tr>
	<tr>
		<th><?php echo LANG('friday'); ?></th>
		<td><input type='time' class='fullwidth' id='txtEditWolScheduleFridayStart' value='<?php echo $wolSchedule&&$wolSchedule->friday ? explode('-',$wolSchedule->friday)[0] : ''; ?>'></input></td>
		<td><input type='time' class='fullwidth' id='txtEditWolScheduleFridayEnd' value='<?php echo $wolSchedule&&$wolSchedule->friday ? explode('-',$wolSchedule->friday)[1] : ''; ?>'></input></td>
		<td><button onclick='txtEditWolScheduleFridayStart.value="";txtEditWolScheduleFridayEnd.value=""' title='<?php echo LANG('remove_time'); ?>' class='small'><img src='img/close.dyn.svg'></button></td>
	</tr>
	<tr>
		<th><?php echo LANG('saturday'); ?></th>
		<td><input type='time' class='fullwidth' id='txtEditWolScheduleSaturdayStart' value='<?php echo $wolSchedule&&$wolSchedule->saturday ? explode('-',$wolSchedule->saturday)[0] : ''; ?>'></input></td>
		<td><input type='time' class='fullwidth' id='txtEditWolScheduleSaturdayEnd' value='<?php echo $wolSchedule&&$wolSchedule->saturday ? explode('-',$wolSchedule->saturday)[1] : ''; ?>'></input></td>
		<td><button onclick='txtEditWolScheduleSaturdayStart.value="";txtEditWolScheduleSaturdayEnd.value=""' title='<?php echo LANG('remove_time'); ?>' class='small'><img src='img/close.dyn.svg'></button></td>
	</tr>
	<tr>
		<th><?php echo LANG('sunday'); ?></th>
		<td><input type='time' class='fullwidth' id='txtEditWolScheduleSundayStart' value='<?php echo $wolSchedule&&$wolSchedule->sunday ? explode('-',$wolSchedule->sunday)[0] : ''; ?>'></input></td>
		<td><input type='time' class='fullwidth' id='txtEditWolScheduleSundayEnd' value='<?php echo $wolSchedule&&$wolSchedule->sunday ? explode('-',$wolSchedule->sunday)[1] : ''; ?>'></input></td>
		<td><button onclick='txtEditWolScheduleSundayStart.value="";txtEditWolScheduleSundayEnd.value=""' title='<?php echo LANG('remove_time'); ?>' class='small'><img src='img/close.dyn.svg'></button></td>
	</tr>
</table>

<div class='controls right'>
	<button onclick='hideDialog();showLoader(false);showLoader2(false);'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button id='btnEditWolSchedule' class='primary' onclick='editWolSchedule(
		txtEditWolScheduleId.value, txtEditWolGroupId.value,
		txtEditWolScheduleName.value,
		txtEditWolScheduleMondayStart.value+"-"+txtEditWolScheduleMondayEnd.value,
		txtEditWolScheduleTuesdayStart.value+"-"+txtEditWolScheduleTuesdayEnd.value,
		txtEditWolScheduleWednesdayStart.value+"-"+txtEditWolScheduleWednesdayEnd.value,
		txtEditWolScheduleThursdayStart.value+"-"+txtEditWolScheduleThursdayEnd.value,
		txtEditWolScheduleFridayStart.value+"-"+txtEditWolScheduleFridayEnd.value,
		txtEditWolScheduleSaturdayStart.value+"-"+txtEditWolScheduleSaturdayEnd.value,
		txtEditWolScheduleSundayStart.value+"-"+txtEditWolScheduleSundayEnd.value
		)'><img src='img/send.white.svg'>&nbsp;<span id='spnBtnEditWolSchedule'><?php echo $wolSchedule ? LANG('change') : LANG('create'); ?></span></button>
</div>
