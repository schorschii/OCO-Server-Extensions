<?php
$SUBVIEW = 1;
if(!isset($db) || !isset($cl)) die();

$tab = 'assignments';
if(!empty($_GET['tab'])) $tab = $_GET['tab'];

$group = null;
$subGroups = [];
$schedules = [];
$plans = [];
$permissionWrite = true; //todo
$permissionDelete = true; //todo
try {
	$wolcl = new WolShutdownCoreLogic($db, $currentSystemUser);
	if(!empty($_GET['id'])) {
		$group = $wolcl->getWolGroup($_GET['id']);
		$schedules = $wolcl->getWolSchedules($_GET['id']);
		$plans = $wolcl->getWolPlans($_GET['id']);
	}
	$subGroups = $wolcl->getWolGroups($_GET['id'] ?? null);
} catch(NotFoundException $e) {
	die("<div class='alert warning'>".LANG('not_found')."</div>");
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");
} catch(InvalidRequestException $e) {
	die("<div class='alert error'>".$e->getMessage()."</div>");
}
?>

<div class='details-header'>
	<h1><img src='img/img.d/scheduler.dyn.svg'><span id='page-title'><?php echo $group ? htmlspecialchars($wolcl->getWolGroupBreadcrumbString($group->id)) : LANG('wol_shutdown_scheduler'); ?></span></h1>
</div>
<?php if(empty($group)) {
	$permissionCreateGroup = $cl->checkPermission(new Models\WolGroup(), PermissionManager::METHOD_CREATE, false);
?>
	<div class='controls'>
		<button onclick='createWolGroup()' <?php if(!$permissionCreateGroup) echo 'disabled'; ?>><img src='img/folder-new.dyn.svg'>&nbsp;<?php echo LANG('new_group'); ?></button>
	</div>
<?php } else {
	$permissionCreateGroup = $cl->checkPermission($group, PermissionManager::METHOD_CREATE, false);
	$permissionWriteGroup = $cl->checkPermission($group, PermissionManager::METHOD_WRITE, false);
	$permissionDeleteGroup = $cl->checkPermission($group, PermissionManager::METHOD_DELETE, false);
?>
	<div class='controls'>
		<button onclick='createWolGroup(<?php echo $group->id; ?>)' <?php if(!$permissionCreateGroup) echo 'disabled'; ?>><img src='img/folder-new.dyn.svg'>&nbsp;<?php echo LANG('new_subgroup'); ?></button>
		<button onclick='renameWolGroup(<?php echo $group->id; ?>, this.getAttribute("oldName"))' oldName='<?php echo htmlspecialchars($group->name,ENT_QUOTES); ?>' <?php if(!$permissionWriteGroup) echo 'disabled'; ?>><img src='img/edit.dyn.svg'>&nbsp;<?php echo LANG('rename_group'); ?></button>
		<button onclick='confirmRemoveWolGroup([<?php echo $group->id; ?>], event, this.getAttribute("oldName"))' oldName='<?php echo htmlspecialchars($group->name,ENT_QUOTES); ?>' <?php if(!$permissionDeleteGroup) echo 'disabled'; ?>><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete_group'); ?></button>
	</div>
<?php } ?>

<?php if(!empty($subGroups) || $group != null) { ?>
<div class='controls subfolders'>
	<?php if($group != null) { ?>
		<?php if($group->parent_wol_group_id == null) { ?>
			<a class='box' <?php echo explorerLink('views/wol-shutdown-scheduler.php'); ?>><img src='img/layer-up.dyn.svg'>&nbsp;<?php echo LANG('wol_shutdown_scheduler'); ?></a>
		<?php } else { $subGroup = $wolcl->getWolGroup($group->parent_wol_group_id); ?>
			<a class='box' <?php echo explorerLink('views/wol-shutdown-scheduler.php?id='.$group->parent_wol_group_id); ?>><img src='img/layer-up.dyn.svg'>&nbsp;<?php echo htmlspecialchars($subGroup->name); ?></a>
		<?php } ?>
	<?php } ?>
	<?php foreach($subGroups as $g) { ?>
		<a class='box' <?php echo explorerLink('views/wol-shutdown-scheduler.php?id='.$g->id); ?>><img src='img/folder.dyn.svg'>&nbsp;<?php echo htmlspecialchars($g->name); ?></a>
	<?php } ?>
</div>
<?php } ?>


<?php if(!empty($group)) { ?>

<div id='tabControlSchedule' class='tabcontainer'>
<div class='tabbuttons'>
	<a href='#' name='assignments' class='<?php if($tab=='assignments') echo 'active'; ?>' onclick='event.preventDefault();openTab(tabControlSchedule,this.getAttribute("name"))'><?php echo LANG('assignments').' ('.count($plans).')'; ?></a>
	<a href='#' name='schedules' class='<?php if($tab=='schedules') echo 'active'; ?>' onclick='event.preventDefault();openTab(tabControlSchedule,this.getAttribute("name"))'><?php echo LANG('schedules').' ('.count($schedules).')'; ?></a>
</div>
<div class='tabcontents'>

	<div name='assignments' class='<?php if($tab=='assignments') echo 'active'; ?>'>
		<div class='details-abreast'>
			<div>
				<div class='controls'>
					<button onclick='showDialogEditWolPlan(-1, <?php echo $group->id; ?>)'><img src='img/add.dyn.svg'>&nbsp;<?php echo LANG('add'); ?></button>
					<div class='filler'></div>
				</div>
				<table class='list searchable sortable savesort actioncolumn'>
					<thead>
						<tr>
							<th class='searchable sortable'><?php echo LANG('computer_group'); ?></th>
							<th class='searchable sortable'><?php echo LANG('schedule'); ?></th>
							<th class='searchable sortable'><?php echo LANG('shutdown_credential'); ?></th>
							<th class='searchable sortable'><?php echo LANG('valid_from'); ?></th>
							<th class='searchable sortable'><?php echo LANG('valid_until'); ?></th>
							<th class='searchable sortable'><?php echo LANG('description'); ?></th>
							<th class=''><?php echo LANG('action'); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php $counter=0; foreach($plans as $plan) { $counter++; ?>
						<tr>
							<td><a <?php echo explorerLink('views/computers.php?id='.$plan->computer_group_id); ?>><?php echo htmlspecialchars($db->getComputerGroupBreadcrumbString($plan->computer_group_id)); ?></a></td>
							<td><a href='#' onclick='event.preventDefault();showDialogEditWolSchedule(<?php echo $plan->wol_schedule_id; ?>, <?php echo $group->id; ?>)'><?php echo htmlspecialchars($plan->wol_schedule_name); ?></td>
							<td><?php echo htmlspecialchars($plan->shutdown_credential); ?></td>
							<td><?php echo htmlspecialchars($plan->start_time ? $plan->start_time : LANG('currently_active')); ?></td>
							<td><?php echo htmlspecialchars($plan->end_time ? $plan->end_time : LANG('does_not_expire')); ?></td>
							<td><?php echo htmlspecialchars($plan->description); ?></td>
							<td>
								<button onclick='showDialogEditWolPlan(<?php echo $plan->id; ?>, <?php echo $group->id; ?>)' title='<?php echo LANG('edit'); ?>'><img src='img/edit.dyn.svg'>
								<button onclick='confirmRemoveWolPlan(<?php echo $plan->id; ?>)' title='<?php echo LANG('remove'); ?>'><img src='img/delete.dyn.svg'>
							</td>
						</tr>
					<?php } ?>
					</tbody>
					<tfoot>
						<tr>
							<td colspan='999'>
								<div class='spread'>
									<div><?php echo $counter; ?> <?php echo LANG('assignments'); ?></div>
									<div class='controls'></div>
								</div>
							</td>
						</tr>
					</tfoot>
				</table>
			</div>
		</div>
	</div>
	<div name='schedules' class='<?php if($tab=='schedules') echo 'active'; ?>'>
		<div class='details-abreast'>
			<div>
				<div class='controls'>
					<button onclick='showDialogEditWolSchedule(-1, <?php echo $group->id; ?>)'><img src='img/add.dyn.svg'>&nbsp;<?php echo LANG('add'); ?></button>
					<div class='filler'></div>
				</div>
				<table class='list searchable sortable savesort actioncolumn'>
					<thead>
						<tr>
							<th class='searchable sortable'><?php echo LANG('name'); ?></th>
							<th class='searchable sortable'><?php echo LANG('monday'); ?></th>
							<th class='searchable sortable'><?php echo LANG('tuesday'); ?></th>
							<th class='searchable sortable'><?php echo LANG('wednesday'); ?></th>
							<th class='searchable sortable'><?php echo LANG('thursday'); ?></th>
							<th class='searchable sortable'><?php echo LANG('friday'); ?></th>
							<th class='searchable sortable'><?php echo LANG('saturday'); ?></th>
							<th class='searchable sortable'><?php echo LANG('sunday'); ?></th>
							<th class=''><?php echo LANG('action'); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php $counter = 0; foreach($schedules as $schedule) { $counter++; ?>
						<tr>
							<td><?php echo htmlspecialchars($schedule->name); ?></td>
							<td><?php echo htmlspecialchars($schedule->monday); ?></td>
							<td><?php echo htmlspecialchars($schedule->tuesday); ?></td>
							<td><?php echo htmlspecialchars($schedule->wednesday); ?></td>
							<td><?php echo htmlspecialchars($schedule->thursday); ?></td>
							<td><?php echo htmlspecialchars($schedule->friday); ?></td>
							<td><?php echo htmlspecialchars($schedule->saturday); ?></td>
							<td><?php echo htmlspecialchars($schedule->sunday); ?></td>
							<td>
								<button onclick='showDialogEditWolSchedule(<?php echo $schedule->id; ?>, <?php echo $group->id; ?>)' title='<?php echo LANG('edit'); ?>'><img src='img/edit.dyn.svg'>
								<button onclick='confirmRemoveWolSchedule(<?php echo $schedule->id; ?>)' title='<?php echo LANG('remove'); ?>'><img src='img/delete.dyn.svg'>
							</td>
						</tr>
					<?php } ?>
					</tbody>
					<tfoot>
						<tr>
							<td colspan='999'>
								<div class='spread'>
									<span><?php echo $counter; ?> <?php echo LANG('schedules'); ?></span>
								</div>
								<div class='controls'></div>
							</td>
						</tr>
					</tfoot>
				</table>
			</div>
		</div>
	</div>
</div>
</div>

<?php } ?>