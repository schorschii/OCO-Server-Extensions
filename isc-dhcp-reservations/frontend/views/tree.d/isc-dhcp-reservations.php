<?php if(empty($currentSystemUser)) die(); ?>
<?php require_once(__DIR__.'/../../../lib/lib.d/isc-dhcp-reservations.php'); ?>

<?php if($currentSystemUser->checkPermission(null, 'dhcp_reservation_management', false)) { ?>
<div class='node'>
	<a <?php echo explorerLink('views/views.d/isc-dhcp-reservations.php'); ?>><img src='img/img.d/dhcp.dyn.svg'>ISC DHCP Server Reservations</a>
	<div class='subnode'>
		<?php
		foreach(ISC_DHCP_SERVER as $server) {
			$title = $server['TITLE'] ?? $server['ADDRESS'] ?? '???';
		?>
			<a <?php echo explorerLink('views/views.d/isc-dhcp-reservations.php?server='.urlencode($server['ADDRESS'])); ?>><img src='img/img.d/dhcp.dyn.svg'><?php echo htmlspecialchars($title); ?></a>
		<?php } ?>
	</div>
</div>
<?php } ?>
