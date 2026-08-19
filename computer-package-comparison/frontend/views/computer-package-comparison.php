<?php
use ComputerPackageComparison\ComputerPackageComparator;

$SUBVIEW = 1;
if(!isset($db) || !isset($cl)) die();

try {
	$targetComputer = $cl->getComputer($_GET['target_computer_id']??-1);
	$sourceComputer = $cl->getComputer($_GET['source_computer_id']??-1);
	if((string)$sourceComputer->id === (string)$targetComputer->id)
		throw new InvalidRequestException(LANG('cpc_comparison_computer_must_be_different'));

	$permissionDeploy = $cl->checkPermission($targetComputer, PermissionManager::METHOD_DEPLOY, false);
	$comparison = ComputerPackageComparator::compare(
		$db->selectAllComputerPackageByComputerId($sourceComputer->id),
		$db->selectAllComputerPackageByComputerId($targetComputer->id)
	);
	$counts = [
		ComputerPackageComparator::STATUS_MISSING => 0,
		ComputerPackageComparator::STATUS_DIFFERENT => 0,
		ComputerPackageComparator::STATUS_IDENTICAL => 0,
		ComputerPackageComparator::STATUS_TARGET_ONLY => 0,
	];
	$deployablePackageVersionsByFamily = [];
	foreach($comparison as $row) {
		$counts[$row['status']]++;
		if($row['status'] !== ComputerPackageComparator::STATUS_MISSING) continue;
		$sourcePackage = $row['source_package'];
		$familyId = (string)$sourcePackage->package_family_id;
		if(isset($deployablePackageVersionsByFamily[$familyId])) continue;

		$deployablePackageVersions = [];
		foreach($db->selectAllPackageByPackageFamilyId($sourcePackage->package_family_id) as $availablePackage) {
			if($cl->checkPermission($availablePackage, PermissionManager::METHOD_DEPLOY, false))
				$deployablePackageVersions[] = $availablePackage;
		}
		$deployablePackageVersionsByFamily[$familyId] = ComputerPackageComparator::sortNewestFirst($deployablePackageVersions);
	}
} catch(NotFoundException $e) {
	die("<div class='alert warning'>".LANG('not_found')."</div>");
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");
} catch(InvalidRequestException $e) {
	die("<div class='alert error'>".htmlspecialchars($e->getMessage())."</div>");
}

$statusLabels = [
	ComputerPackageComparator::STATUS_MISSING => LANG('cpc_missing'),
	ComputerPackageComparator::STATUS_DIFFERENT => LANG('cpc_different_version'),
	ComputerPackageComparator::STATUS_IDENTICAL => LANG('cpc_identical'),
	ComputerPackageComparator::STATUS_TARGET_ONLY => LANG('cpc_only_on_this_computer'),
];
?>

<div class='oco-package-comparison'
	data-target-computer-id='<?php echo htmlspecialchars($targetComputer->id,ENT_QUOTES); ?>'
	data-target-computer-hostname='<?php echo htmlspecialchars($targetComputer->hostname,ENT_QUOTES); ?>'>
	<div class='controls heading'>
		<h2><?php echo LANG('cpc_package_comparison'); ?></h2>
		<div class='filler invisible'></div>
		<button type='button' onclick='ocoPackageComparisonShowComputerDialog()'><img src='img/computer.dyn.svg'>&nbsp;<?php echo LANG('cpc_change_comparison_computer'); ?></button>
		<button type='button' onclick='ocoPackageComparisonClose()' title='<?php echo LANG('cpc_close_comparison',ENT_QUOTES); ?>'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	</div>

	<div class='oco-package-comparison-computers'>
		<div>
			<span><?php echo LANG('cpc_comparison_computer'); ?></span>
			<strong><img src='<?php echo htmlspecialchars($sourceComputer->getIcon(),ENT_QUOTES); ?>'><?php echo htmlspecialchars($sourceComputer->hostname); ?></strong>
		</div>
		<img class='oco-package-comparison-arrow' src='img/arrow-right.dyn.svg'>
		<div>
			<span><?php echo LANG('cpc_this_computer'); ?></span>
			<strong><img src='<?php echo htmlspecialchars($targetComputer->getIcon(),ENT_QUOTES); ?>'><?php echo htmlspecialchars($targetComputer->hostname); ?></strong>
		</div>
	</div>

	<p><?php echo LANG('cpc_package_comparison_description'); ?></p>

	<div class='controls oco-package-comparison-filters'>
		<button type='button' class='active' data-status='missing' onclick='ocoPackageComparisonFilter("missing",this)'><?php echo LANG('cpc_missing'); ?> <span class='badge'><?php echo $counts[ComputerPackageComparator::STATUS_MISSING]; ?></span></button>
		<button type='button' data-status='different' onclick='ocoPackageComparisonFilter("different",this)'><?php echo LANG('cpc_different_version'); ?> <span class='badge'><?php echo $counts[ComputerPackageComparator::STATUS_DIFFERENT]; ?></span></button>
		<button type='button' data-status='identical' onclick='ocoPackageComparisonFilter("identical",this)'><?php echo LANG('cpc_identical'); ?> <span class='badge'><?php echo $counts[ComputerPackageComparator::STATUS_IDENTICAL]; ?></span></button>
		<button type='button' data-status='target_only' onclick='ocoPackageComparisonFilter("target_only",this)'><?php echo LANG('cpc_only_on_this_computer'); ?> <span class='badge'><?php echo $counts[ComputerPackageComparator::STATUS_TARGET_ONLY]; ?></span></button>
		<button type='button' data-status='all' onclick='ocoPackageComparisonFilter("all",this)'><?php echo LANG('cpc_all'); ?> <span class='badge'><?php echo count($comparison); ?></span></button>
		<div class='filler invisible'></div>
		<input type='search' class='oco-package-comparison-search' placeholder='<?php echo LANG('cpc_search_packages',ENT_QUOTES); ?>' oninput='ocoPackageComparisonApplyFilters()'>
	</div>

	<div class='oco-package-comparison-empty alert success' <?php if($counts[ComputerPackageComparator::STATUS_MISSING] > 0) echo 'hidden'; ?>><?php echo LANG('cpc_no_missing_packages'); ?></div>
	<div class='oco-package-comparison-no-results alert info' hidden><?php echo LANG('no_results'); ?></div>

	<div class='stickytable'>
		<table class='list oco-package-comparison-table' data-filter-status='missing' <?php if($counts[ComputerPackageComparator::STATUS_MISSING] === 0) echo 'hidden'; ?>>
			<thead>
				<tr>
					<th><input type='checkbox' onchange='ocoPackageComparisonToggleSelection(this.checked)' title='<?php echo LANG('select_all',ENT_QUOTES); ?>'></th>
					<th><?php echo LANG('package'); ?></th>
					<th><?php echo htmlspecialchars($sourceComputer->hostname); ?></th>
					<th><?php echo htmlspecialchars($targetComputer->hostname); ?></th>
					<th><?php echo LANG('cpc_version_to_deploy'); ?></th>
					<th><?php echo LANG('status'); ?></th>
					<th><?php echo LANG('installation_date'); ?> (<?php echo htmlspecialchars($sourceComputer->hostname); ?>)</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach($comparison as $row) {
					$sourcePackage = $row['source_package'];
					$targetPackages = $row['target_packages'];
					$displayPackage = $sourcePackage??$targetPackages[0];
					$status = $row['status'];
					$deployableVersions = $sourcePackage === null ? [] : ($deployablePackageVersionsByFamily[(string)$sourcePackage->package_family_id]??[]);
				?>
				<tr data-status='<?php echo htmlspecialchars($status,ENT_QUOTES); ?>' <?php if($status !== ComputerPackageComparator::STATUS_MISSING) echo 'hidden'; ?>>
					<td>
						<?php if($permissionDeploy && $status === ComputerPackageComparator::STATUS_MISSING && !empty($deployableVersions)) { ?>
							<input type='checkbox' class='oco-package-comparison-selection' onchange='ocoPackageComparisonUpdateSelection()'>
						<?php } ?>
					</td>
					<td><img src='img/package.dyn.svg'>&nbsp;<?php echo htmlspecialchars($displayPackage->package_family_name); ?></td>
					<td>
						<?php if($sourcePackage !== null) { ?>
							<a <?php echo Html::explorerLink('views/package-details.php?id='.$sourcePackage->package_id); ?>><?php echo htmlspecialchars($sourcePackage->package_version); ?></a>
						<?php } else echo '&ndash;'; ?>
					</td>
					<td>
						<?php if(empty($targetPackages)) echo '&ndash;';
						else foreach($targetPackages as $index => $targetPackage) {
							if($index > 0) echo '<br>';
							echo '<a '.Html::explorerLink('views/package-details.php?id='.$targetPackage->package_id).'>'.htmlspecialchars($targetPackage->package_version).'</a>';
						} ?>
					</td>
					<td>
						<?php if($status === ComputerPackageComparator::STATUS_MISSING && !empty($deployableVersions)) { ?>
							<select class='oco-package-comparison-version' data-package-name='<?php echo htmlspecialchars($sourcePackage->package_family_name,ENT_QUOTES); ?>' title='<?php echo LANG('cpc_version_to_deploy_help',ENT_QUOTES); ?>'>
								<?php foreach($deployableVersions as $index => $availablePackage) { ?>
									<option value='<?php echo htmlspecialchars($availablePackage->id,ENT_QUOTES); ?>' data-version='<?php echo htmlspecialchars($availablePackage->version,ENT_QUOTES); ?>' <?php if($index === 0) echo 'selected'; ?>><?php echo htmlspecialchars($availablePackage->version.($index === 0 ? ' — '.LANG('newest') : '')); ?></option>
								<?php } ?>
							</select>
						<?php } elseif($status === ComputerPackageComparator::STATUS_MISSING) echo htmlspecialchars(LANG('cpc_no_deployable_package_version'));
						else echo '&ndash;'; ?>
					</td>
					<td><span class='oco-package-comparison-status <?php echo htmlspecialchars($status,ENT_QUOTES); ?>'><?php echo htmlspecialchars($statusLabels[$status]); ?></span></td>
					<td><?php echo htmlspecialchars($sourcePackage->installed??''); ?></td>
				</tr>
				<?php } ?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan='999'>
						<div class='spread'>
							<div><span class='oco-package-comparison-selected-count'>0</span>&nbsp;<?php echo LANG('cpc_packages_selected'); ?></div>
							<div class='controls'>
								<button type='button' class='primary oco-package-comparison-deploy' onclick='ocoPackageComparisonDeploySelected()' disabled><img src='img/deploy.dyn.svg'>&nbsp;<?php echo LANG('cpc_deploy_selected_packages'); ?></button>
							</div>
						</div>
					</td>
				</tr>
			</tfoot>
		</table>
	</div>
</div>
