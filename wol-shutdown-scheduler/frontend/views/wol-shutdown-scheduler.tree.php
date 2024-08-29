<?php if(empty($cl)) die(); ?>

<?php if(true) { ?>
<div id='divNodeWolShutdownScheduler' class='node expandable'>
	<a <?php echo explorerLink('views/wol-shutdown-scheduler.php'); ?>><img src='img/scheduler.dyn.svg'><?php echo LANG('wol_shutdown_scheduler'); ?></a>
	<div id='divNodeWolShutdownGroups' class='subitems'>
		<?php echo getWolGroupsHtml(new WolShutdownCoreLogic($db, $currentSystemUser)); ?>
	</div>
</div>
<?php } ?>

<?php
function getWolGroupsHtml(WolShutdownCoreLogic $wolcl, $parentId=null) {
	$html = '';
	$subgroups = $wolcl->getWolGroups($parentId);
	if(count($subgroups) == 0) return false;
	foreach($subgroups as $group) {
		$subHtml = getWolGroupsHtml($wolcl, $group->id);
		$html .= "<div id='divNodeWolGroup".$group->id."' class='subnode ".(empty($subHtml) ? '' : 'expandable')."'>";
		$html .= "<a ".explorerLink('views/wol-shutdown-scheduler.php?id='.$group->id)."><img src='img/folder.dyn.svg'>".htmlspecialchars($group->name)."</a>";
		$html .= "<div class='subitems'>";
		$html .= $subHtml;
		$html .= "</div>";
		$html .= "</div>";
	}
	return $html;
}
