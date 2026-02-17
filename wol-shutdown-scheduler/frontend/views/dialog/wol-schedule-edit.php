<?php
$SUBVIEW = 1;
if(!isset($db) || !isset($cl)) die();

$wolcl = new WolShutdownCoreLogic($db, $currentSystemUser);

$wolSchedule = null;
$wolGroupId = $_GET['wol_group_id'] ?? -1;
try {
	$wolSchedule = $wolcl->getWolSchedule($_GET['id'] ?? -1);
} catch(Exception $ignored) {}
?>

<input type='hidden' name='id' value='<?php echo $wolSchedule->id??-1; ?>'></input>
<input type='hidden' name='wol_group_id' value='<?php echo $wolGroupId; ?>'></input>
<table class='fullwidth aligned scheduleedit'>
	<tr>
		<th><?php echo LANG('name'); ?></th>
		<td colspan='3'><input type='text' class='fullwidth' autocomplete='new-password' name='name' autofocus='true' value='<?php echo $wolSchedule->name??''; ?>'></input></td>
	</tr>
	<tr>
		<th></th>
		<th><?php echo LANG('start'); ?></th>
		<th><?php echo LANG('shutdown'); ?></th>
	</tr>
	<tr>
		<th><?php echo LANG('monday'); ?></th>
		<td><input type='time' class='fullwidth' name='monday_start' value='<?php echo $wolSchedule&&$wolSchedule->monday ? explode('-',$wolSchedule->monday)[0] : ''; ?>'></input></td>
		<td><input type='time' class='fullwidth' name='monday_end' value='<?php echo $wolSchedule&&$wolSchedule->monday ? explode('-',$wolSchedule->monday)[1] : ''; ?>'></input></td>
		<td><button class='removeTime' title='<?php echo LANG('remove_time'); ?>' class='small'><img src='img/close.dyn.svg'></button></td>
	</tr>
	<tr>
		<th><?php echo LANG('tuesday'); ?></th>
		<td><input type='time' class='fullwidth' name='tuesday_start' value='<?php echo $wolSchedule&&$wolSchedule->tuesday ? explode('-',$wolSchedule->tuesday)[0] : ''; ?>'></input></td>
		<td><input type='time' class='fullwidth' name='tuesday_end' value='<?php echo $wolSchedule&&$wolSchedule->tuesday ? explode('-',$wolSchedule->tuesday)[1] : ''; ?>'></input></td>
		<td><button class='removeTime' title='<?php echo LANG('remove_time'); ?>' class='small'><img src='img/close.dyn.svg'></button></td>
	</tr>
	<tr>
		<th><?php echo LANG('wednesday'); ?></th>
		<td><input type='time' class='fullwidth' name='wednesday_start' value='<?php echo $wolSchedule&&$wolSchedule->wednesday ? explode('-',$wolSchedule->wednesday)[0] : ''; ?>'></input></td>
		<td><input type='time' class='fullwidth' name='wednesday_end' value='<?php echo $wolSchedule&&$wolSchedule->wednesday ? explode('-',$wolSchedule->wednesday)[1] : ''; ?>'></input></td>
		<td><button class='removeTime' title='<?php echo LANG('remove_time'); ?>' class='small'><img src='img/close.dyn.svg'></button></td>
	</tr>
	<tr>
		<th><?php echo LANG('thursday'); ?></th>
		<td><input type='time' class='fullwidth' name='thursday_start' value='<?php echo $wolSchedule&&$wolSchedule->thursday ? explode('-',$wolSchedule->thursday)[0] : ''; ?>'></input></td>
		<td><input type='time' class='fullwidth' name='thursday_end' value='<?php echo $wolSchedule&&$wolSchedule->thursday ? explode('-',$wolSchedule->thursday)[1] : ''; ?>'></input></td>
		<td><button class='removeTime' title='<?php echo LANG('remove_time'); ?>' class='small'><img src='img/close.dyn.svg'></button></td>
	</tr>
	<tr>
		<th><?php echo LANG('friday'); ?></th>
		<td><input type='time' class='fullwidth' name='friday_start' value='<?php echo $wolSchedule&&$wolSchedule->friday ? explode('-',$wolSchedule->friday)[0] : ''; ?>'></input></td>
		<td><input type='time' class='fullwidth' name='friday_end' value='<?php echo $wolSchedule&&$wolSchedule->friday ? explode('-',$wolSchedule->friday)[1] : ''; ?>'></input></td>
		<td><button class='removeTime' title='<?php echo LANG('remove_time'); ?>' class='small'><img src='img/close.dyn.svg'></button></td>
	</tr>
	<tr>
		<th><?php echo LANG('saturday'); ?></th>
		<td><input type='time' class='fullwidth' name='saturday_start' value='<?php echo $wolSchedule&&$wolSchedule->saturday ? explode('-',$wolSchedule->saturday)[0] : ''; ?>'></input></td>
		<td><input type='time' class='fullwidth' name='saturday_end' value='<?php echo $wolSchedule&&$wolSchedule->saturday ? explode('-',$wolSchedule->saturday)[1] : ''; ?>'></input></td>
		<td><button class='removeTime' title='<?php echo LANG('remove_time'); ?>' class='small'><img src='img/close.dyn.svg'></button></td>
	</tr>
	<tr>
		<th><?php echo LANG('sunday'); ?></th>
		<td><input type='time' class='fullwidth' name='sunday_start' value='<?php echo $wolSchedule&&$wolSchedule->sunday ? explode('-',$wolSchedule->sunday)[0] : ''; ?>'></input></td>
		<td><input type='time' class='fullwidth' name='sunday_end' value='<?php echo $wolSchedule&&$wolSchedule->sunday ? explode('-',$wolSchedule->sunday)[1] : ''; ?>'></input></td>
		<td><button class='removeTime' title='<?php echo LANG('remove_time'); ?>' class='small'><img src='img/close.dyn.svg'></button></td>
	</tr>
</table>

<div class='controls right'>
	<button class='dialogClose'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button class='primary' name='edit'><img src='img/send.white.svg'>&nbsp;<?php echo $wolSchedule ? LANG('change') : LANG('create'); ?></button>
</div>
