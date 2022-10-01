<?php if(empty($cl) || !defined('ISC_DHCP_SERVER')) die(); ?>

<?php if($cl->checkPermission(null, get_class(new IscDhcpReservationsController()), false)) { ?>
<div id='divNodeIscDhcpReservations' class='node <?php if(count(ISC_DHCP_SERVER) > 1) echo "expandable"; ?>'>
	<a <?php echo explorerLink('views/isc-dhcp-reservations.php'); ?>><img src='img/dhcp.dyn.svg'><?php echo LANG('isc_dhcp_server_reservations'); ?></a>
	<div id='divNodeIscDhcpReservationsServers' class='subitems'>
		<?php
		if(count(ISC_DHCP_SERVER) > 1) foreach(ISC_DHCP_SERVER as $server) {
			$title = $server['TITLE'] ?? $server['ADDRESS'] ?? '???';
		?>
			<a <?php echo explorerLink('views/isc-dhcp-reservations.php?server='.urlencode($server['ADDRESS'])); ?>><img src='img/dhcp.dyn.svg'><?php echo htmlspecialchars($title); ?></a>
		<?php } ?>
	</div>
</div>
<?php } ?>
