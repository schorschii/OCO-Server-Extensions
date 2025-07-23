<?php if(!empty($cl)) { ?>

<?php
$dhcpController = new IscDhcpReservationsController($db, $cl);
$servers = $dhcpController->getAllServers();
?>

<div id='divNodeIscDhcpReservations' class='node <?php if(count($servers) > 1) echo "expandable"; ?>'>
	<a <?php echo Html::explorerLink('views/isc-dhcp-reservations.php'); ?>><img src='img/dhcp.dyn.svg'><?php echo LANG('isc_dhcp_server_reservations'); ?></a>
	<div id='divNodeIscDhcpReservationsServers' class='subitems'>
		<?php foreach($servers as $server) { ?>
			<a <?php echo Html::explorerLink('views/isc-dhcp-reservations.php?server='.urlencode($server->address)); ?>><img src='img/dhcp.dyn.svg'><?php echo htmlspecialchars($server->title); ?></a>
		<?php } ?>
	</div>
</div>

<?php } ?>
