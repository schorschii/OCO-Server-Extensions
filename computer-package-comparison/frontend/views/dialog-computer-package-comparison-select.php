<?php
$SUBVIEW = 1;
if(!isset($db) || !isset($cl)) die();

try {
	$targetComputer = $cl->getComputer($_GET['target_computer_id']??-1);
	$computers = [];
	foreach($db->selectAllComputer() as $computer) {
		if((string)$computer->id === (string)$targetComputer->id) continue;
		if(!$cl->checkPermission($computer, PermissionManager::METHOD_READ, false)) continue;
		$computers[] = $computer;
	}
	usort($computers, function($left, $right) {
		return strnatcasecmp((string)$left->hostname, (string)$right->hostname);
	});
} catch(NotFoundException $e) {
	die("<div class='alert warning'>".LANG('not_found')."</div>");
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");
} catch(InvalidRequestException $e) {
	die("<div class='alert error'>".htmlspecialchars($e->getMessage())."</div>");
}
?>

<div class='dialogStretch oco-package-comparison-selection'>
	<div class='listSearch'>
		<input type='search' name='computer_search' placeholder='<?php echo LANG('cpc_search_computers',ENT_QUOTES); ?>' autofocus>
	</div>

	<div class='box listItems fillHeight'>
		<?php foreach($computers as $computer) { ?>
			<label class='blockListItem item' data-hostname='<?php echo htmlspecialchars($computer->hostname,ENT_QUOTES); ?>'>
				<input type='radio' name='comparison_computer_id' value='<?php echo htmlspecialchars($computer->id,ENT_QUOTES); ?>'>
				<img src='<?php echo htmlspecialchars($computer->getIcon(),ENT_QUOTES); ?>'>
				<span><?php echo htmlspecialchars($computer->hostname); ?></span>
			</label>
		<?php } ?>
		<?php if(empty($computers)) { ?>
			<div class='alert info'><?php echo LANG('no_computers_found'); ?></div>
		<?php } ?>
	</div>

	<div class='controls right'>
		<button class='dialogClose'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
		<button class='primary' name='compare' disabled><img src='img/send.white.svg'>&nbsp;<?php echo LANG('cpc_compare'); ?></button>
	</div>
</div>
