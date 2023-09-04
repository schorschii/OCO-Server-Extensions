<?php if(empty($currentSystemUser)) die(); ?>

<?php if($cl->checkPermission(null, 'InstallTool', false)) { ?>
<div id='divNodeInstallTool' class='node expandable'>
	<a <?php echo explorerLink('views/install-tool.php'); ?>><img src='img/install-tool.dyn.svg'>Install-Tool</a>
	<div id='divNodeInstallToolActions' class='subitems'>
		<a <?php echo explorerLink('views/install-tool.php?form=installation'); ?>><img src='img/automagical.png'>Automatische Installation</a>
		<a <?php echo explorerLink('views/install-tool.php?form=remove-installation'); ?>><img src='img/installation-remove.png'>Austragen</a>
	</div>
</div>
<?php } ?>
