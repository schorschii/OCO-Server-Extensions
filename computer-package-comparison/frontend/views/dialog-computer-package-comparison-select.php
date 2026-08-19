<?php
$SUBVIEW = 1;
if(!isset($db) || !isset($cl)) die();

$SINGLE_SELECTION = 1;
$computerSelectionPartial = dirname(__DIR__, 4).'/frontend/views/partial/computer-selection.php';
if(!file_exists($computerSelectionPartial))
	die("<div class='alert error'>".LANG('requested_view_does_not_exist')."</div>");
?>

<div class='dialogStretch'>
	<div class='gallery computerSelection'>
		<div class='fillHeight'>
			<?php require($computerSelectionPartial); ?>
		</div>
	</div>

	<div class='controls right'>
		<button class='dialogClose'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
		<button class='primary' name='compare'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('cpc_compare'); ?></button>
	</div>
</div>
