<?php if(!empty($cl) && defined('ISC_DHCP_SERVER')) { ?>

<?php if($cl->checkPermission(null, IscDhcpReservationsController::class, false)) { ?>
<div id='divNodeIscDhcpReservations' class='node <?php if(count(ISC_DHCP_SERVER) > 1) echo "expandable"; ?>'>
	<a <?php echo explorerLink('views/isc-dhcp-reservations.php'); ?>><img src='img/dhcp.dyn.svg'><?php echo LANG('isc_dhcp_server_reservations'); ?></a>
	<div id='divNodeIscDhcpReservationsServers' class='subitems'>
		<?php foreach(IscDhcpReservationsController::getAllServers() as $server) {
			if(!$cl->checkPermission($server, PermissionManager::METHOD_READ, false)) continue;
		?>
			<a <?php echo explorerLink('views/isc-dhcp-reservations.php?server='.urlencode($server->address)); ?>><img src='img/dhcp.dyn.svg'><?php echo htmlspecialchars($server->title); ?></a>
		<?php } ?>
	</div>
</div>
<?php } ?>

<?php } ?>
