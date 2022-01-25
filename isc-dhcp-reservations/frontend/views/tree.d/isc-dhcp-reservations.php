<?php if($currentSystemUser->checkPermission(null, 'dhcp_reservation_management', false)) { ?>
<div class='node'>
	<a <?php echo explorerLink('views/views.d/isc-dhcp-reservations.php'); ?>><img src='img/img.d/dhcp.dyn.svg'>ISC DHCP Server Reservations</a>
</div>
<?php } ?>
