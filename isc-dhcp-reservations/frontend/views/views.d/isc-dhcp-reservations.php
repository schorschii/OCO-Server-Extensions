<?php
$SUBVIEW = 1;
require_once('../../../lib/Loader.php');
require_once('../../session.php');
require_once('../../../lib/lib.d/isc-dhcp-reservations.php');

// remove reservation if requested
if(isset($_POST['remove_hostname'])) {
	try {
		if(empty($_POST['remove_hostname'])) {
			throw new UnexpectedValueException('Hostname cannot be empty!');
		}
		if(removeReservation($_POST['remove_hostname'])) {
			reloadDhcpConfig();
		} else {
			throw new UnexpectedValueException('No suitable reservation found!');
		}
	} catch(UnexpectedValueException $e) {
		header('HTTP/1.1 400 Invalid Request');
		die($e->getMessage());
	} catch(Exception $e) {
		header('HTTP/1.1 500 Internal Server Error');
		die($e->getMessage());
	}
}

// add reservation if requested
if(isset($_POST['add_hostname']) && isset($_POST['add_ip']) && isset($_POST['add_mac'])) {
	try {
		if(addReservation($_POST['add_hostname'], $_POST['add_mac'], $_POST['add_ip'])) {
			reloadDhcpConfig();
		}
	} catch(UnexpectedValueException $e) {
		header('HTTP/1.1 400 Invalid Request');
		die($e->getMessage());
	} catch(Exception $e) {
		header('HTTP/1.1 500 Internal Server Error');
		die($e->getMessage());
	}
}
?>

<h1><img src='img/img.d/dhcp.dyn.svg'><span id='page-title'>ISC-DHCP-Server Reservation Management</span></h1>

<?php
// load reservations
$content = file_get_contents(RESERVATIONS_FILE);
if($content === false) die('<div class="alert error">Unable to read reservations file '.htmlspecialchars(RESERVATIONS_FILE).'</div>');
?>

<div class='controls'>
	<input type='text' autocomplete='new-password' id='txtHostname' placeholder='Hostname'></input>
	<input type='text' autocomplete='new-password' id='txtIpAddress' placeholder='Internet Protocol Address'></input>
	<input type='text' autocomplete='new-password' id='txtMacAddress' placeholder='Media Access Control Address'></input>
	<button id='btnAddReservation' onclick='addIscDhcpReservation()'><img src='img/add.svg'>&nbsp;<?php echo LANG['add']; ?></button>
</div>

<table class="list searchable sortable savesort">

	<thead>
		<tr>
			<th class='searchable sortable'>Hostname</th>
			<th class='searchable sortable'>Internet Protocol Address</th>
			<th class='searchable sortable'>Media Access Control Address</th>
			<th class=''>Aktion</th>
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
		echo "<button onclick='removeIscDhcpReservation(\"".htmlspecialchars($subresult['host'],ENT_QUOTES)."\", \"".htmlspecialchars($subresult['ip'],ENT_QUOTES)."\", \"".htmlspecialchars($subresult['mac'],ENT_QUOTES)."\")'>".LANG['delete']."</button>\n";
		echo "</td>\n";
		echo "</tr>\n\n";
	}
	?>
	</tbody>

	<tfoot>
		<tr>
			<td class='total' colspan='4'><span><i>Total: <?php echo count($reservations); ?> Reservations</i></span></td>
		</tr>
	</tfoot>
</table>

<?php
function startsWith( $haystack, $needle ) {
	$length = strlen( $needle );
	return substr( $haystack, 0, $length ) === $needle;
}
