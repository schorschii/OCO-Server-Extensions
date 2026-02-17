<?php
$SUBVIEW = 1;
if(!isset($db) || !isset($cl)) die();

$wolcl = new WolShutdownCoreLogic($db, $currentSystemUser);

$wolPlan = null;
$wolGroupId = $_GET['wol_group_id'] ?? -1;
try {
	$wolPlan = $wolcl->getWolPlan($_GET['id'] ?? -1);
} catch(Exception $ignored) {}
?>

<input type='hidden' name='id' value='<?php echo $wolPlan->id??-1; ?>'></input>
<input type='hidden' name='wol_group_id' value='<?php echo $wolGroupId; ?>'></input>
<table class='fullwidth aligned scheduleedit'>
	<tr>
		<th><?php echo LANG('computer_group'); ?></th>
		<td colspan='2'>
			<select name='computer_group_id' class='fullwidth' autofocus='true'>
				<option value='' selected disabled><?php echo LANG('select_placeholder'); ?></option>
				<?php Html::buildGroupOptions($cl, new Models\ComputerGroup(), 0, $wolPlan->computer_group_id??-1); ?>
			</select>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('schedule'); ?></th>
		<td colspan='2'>
			<select name='wol_schedule_id' class='fullwidth'>
				<option value='' selected disabled><?php echo LANG('select_placeholder'); ?></option>
				<?php foreach($wolcl->getWolSchedules($wolGroupId) as $schedule) { ?>
					<option value='<?php echo $schedule->id; ?>' <?php if($schedule->id==($wolPlan->wol_schedule_id??-1)) echo 'selected'; ?>><?php echo htmlspecialchars($schedule->name); ?></option>
				<?php } ?>
			</select>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('valid_from'); ?></th>
		<td>
			<label><input type='radio' name='start_mode' value='unlimited' checked='true'/>sofort</label>
		</td>
		<td class='dualInput'>
			<input type='radio' name='start_mode' value='date' <?php if($wolPlan&&$wolPlan->start_time) echo 'checked'; ?>/>
			<label><input type='date' class='fullwidth' name='start_date' min='<?php echo date('Y-m-d'); ?>' value='<?php echo $wolPlan&&$wolPlan->start_time ? date('Y-m-d', strtotime($wolPlan->start_time)) : ''; ?>'></input></label>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('valid_until'); ?></th>
		<td>
			<label><input type='radio' name='end_mode' value='unlimited' checked='true'/>unbegrenzt</label>
		</td>
		<td class='dualInput'>
			<input type='radio' name='end_mode' value='date' <?php if($wolPlan&&$wolPlan->end_time) echo 'checked'; ?>/>
			<label><input type='date' class='fullwidth' name='end_date' min='<?php echo date('Y-m-d'); ?>' value='<?php echo $wolPlan&&$wolPlan->end_time ? date('Y-m-d', strtotime($wolPlan->end_time)) : ''; ?>'></input></label>
		</td>
	</tr>
	<tr>
		<th><?php echo LANG('description'); ?></th>
		<td colspan='2'><textarea class='fullwidth' placeholder='<?php echo LANG('optional'); ?>' name='notes'><?php echo htmlspecialchars($wolPlan->description??''); ?></textarea></td>
	</tr>
</table>

<div class='controls right'>
	<button class='dialogClose'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<button class='primary' name='edit'><img src='img/send.white.svg'>&nbsp;<?php echo $wolPlan ? LANG('change') : LANG('create'); ?></button>
</div>
