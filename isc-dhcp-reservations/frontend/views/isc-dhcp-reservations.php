<?php
$SUBVIEW = 1;
if(!isset($db) || !isset($cl) || !defined('ISC_DHCP_SERVER')) die();

if(!$cl->checkPermission(null, IscDhcpReservationsController::class, false))
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");
?>

<?php
// load reservations
$content = null;
$server = null;
$permissionWrite = false;

try {
	if(isset($_GET['server'])) {
		$defaultServerAddress = 'localhost';
		if(defined('ISC_DHCP_SERVER') && isset(ISC_DHCP_SERVER[0]) && isset(ISC_DHCP_SERVER[0]['ADDRESS']))
			$defaultServerAddress = ISC_DHCP_SERVER[0]['ADDRESS'];
		$serverAddress = $_GET['server'] ?? $defaultServerAddress;

		$server = IscDhcpReservationsController::getServerByAddress($_GET['server']);

		$cl->checkPermission($server, PermissionManager::METHOD_READ);
		$permissionWrite = $cl->checkPermission($server, PermissionManager::METHOD_WRITE, false);

		$controller = new IscDhcpReservationsController($server);
		$content = $controller->loadReservationsFile();
	}
} catch(NotFoundException $e) {
	die("<div class='alert warning'>".LANG('not_found')."</div>");
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");
} catch(Exception $e) {
	die('<div class="alert error">'.htmlspecialchars($e->getMessage()).'</div>');
}
?>

<?php if($server) { ?>

<h1><img src='img/img.d/dhcp.dyn.svg'><span id='page-title'><?php echo htmlspecialchars(LANG('isc_dhcp_server_reservations').' '.($server->title ?? $server->address ?? '')); ?></span></h1>

<div class='controls'>
	<input type='text' autocomplete='new-password' id='txtHostname' placeholder='<?php echo LANG('hostname'); ?>' <?php if(!$permissionWrite) echo 'disabled'; ?>></input>
	<input type='text' autocomplete='new-password' id='txtIpAddress' placeholder='<?php echo LANG('internet_protocol_address'); ?>' <?php if(!$permissionWrite) echo 'disabled'; ?>></input>
	<input type='text' autocomplete='new-password' id='txtMacAddress' placeholder='<?php echo LANG('media_access_control_address'); ?>' <?php if(!$permissionWrite) echo 'disabled'; ?>></input>
	<input type='hidden' id='txtServer' value='<?php echo htmlspecialchars($server->address); ?>'></input>
	<button id='btnAddReservation' onclick='addIscDhcpReservation()' <?php if(!$permissionWrite) echo 'disabled'; ?>><img src='img/add.dyn.svg'>&nbsp;<?php echo LANG('add'); ?></button>
</div>

<table class="list searchable sortable savesort">

	<thead>
		<tr>
			<th class='searchable sortable'><?php echo LANG('hostname'); ?></th>
			<th class='searchable sortable'><?php echo LANG('internet_protocol_address'); ?></th>
			<th class='searchable sortable'><?php echo LANG('media_access_control_address'); ?></th>
			<th class=''><?php echo LANG('action'); ?></th>
		</tr>
	</thead>

	<tbody>
	<?php
		$reservations = array();
		if(!empty(trim($content))) {
			$lines = preg_split("/((\r?\n)|(\r\n?))/", $content);

			$hostname = '?'; $mac = '?'; $ip = '?';
			foreach($lines as $line) {

			// begin of host block -> truncate temporary values
			if(substr($line,0,5) == "host ") {
				$hostname = explode(" ", $line)[1];
				$mac = '?'; $ip = '?';
			}

			// mac address definition
			if(startsWith(trim($line), "hardware ethernet")) {
				$mac = str_replace(";", "", explode(" ", trim($line))[2]);
			}
			// ip address definition
			if(startsWith(trim($line), "fixed-address")) {
				$ip = str_replace(";", "", explode(" ", trim($line))[1]);
			}

			// end of host block -> add host to result array
			if(trim($line) == "}") {
				$reservations[] = ['host'=>$hostname, 'mac'=>$mac, 'ip'=>$ip];
				$hostname = '?'; $mac = '?'; $ip = '?';
			}
		}
	}

	foreach($reservations as $subresult) {
		echo "<tr>\n";
		echo "<td><span>".htmlspecialchars($subresult['host'])."</span></td>\n";
		echo "<td><span>".htmlspecialchars($subresult['ip'])."</span></td>\n";
		echo "<td><span>".htmlspecialchars($subresult['mac'])."</span></td>\n";
		echo "<td>\n";
		echo "<button class='small' onclick='removeIscDhcpReservation(\"".htmlspecialchars($subresult['host'],ENT_QUOTES)."\", \"".htmlspecialchars($subresult['ip'],ENT_QUOTES)."\", \"".htmlspecialchars($subresult['mac'],ENT_QUOTES)."\")' ".($permissionWrite?'':'disabled').">".LANG('delete')."</button>\n";
		echo "</td>\n";
		echo "</tr>\n\n";
	}
	?>
	</tbody>

	<tfoot>
		<tr>
			<td class='total' colspan='4'><span><?php echo count($reservations); ?> <?php echo LANG('reservations'); ?></span></td>
		</tr>
	</tfoot>
</table>

<strings style='display:none'>
	<string id='str_reservation_added_successfully'><?php echo LANG('reservation_added_successfully'); ?></string>
	<string id='str_reservation_removed_successfully'><?php echo LANG('reservation_removed_successfully'); ?></string>
	<string id='str_really_delete_reservation'><?php echo LANG('really_delete_reservation_placeholder'); ?></string>
</string>

<?php } else { ?>

	<h1><img src='img/img.d/dhcp.dyn.svg'><span id='page-title'><?php echo LANG('isc_dhcp_server_reservations'); ?></span></h1>

	<div class='actionmenu'>
		<?php foreach(IscDhcpReservationsController::getAllServers() as $server) {
			if(!$cl->checkPermission($server, PermissionManager::METHOD_READ, false)) continue;
		?>
			<a <?php echo explorerLink('views/isc-dhcp-reservations.php?server='.urlencode($server->address)); ?>>&rarr;&nbsp;<?php echo htmlspecialchars($server->title); ?></a>
		<?php } ?>
	</div>

<?php } ?>
