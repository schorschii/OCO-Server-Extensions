<?php
$SUBVIEW = 1;
if(!isset($db) || !isset($cl)) die();

try {

	$controller = new IscDhcpReservationsController($db, $cl);

	// remove reservation if requested
	if(isset($_POST['remove_hostname']) && isset($_POST['server'])) {
		$server = $controller->getServerByIdOrAddress($_POST['server']);
		$controller->setServer($server);
		if(empty($_POST['remove_hostname'])) {
			throw new UnexpectedValueException(LANG('hostname_cannot_be_empty'));
		}
		if($controller->removeReservation($_POST['remove_hostname'])) {
			$controller->reloadDhcpConfig();
		} else {
			throw new UnexpectedValueException(LANG('no_suitable_reservation_found'));
		}
		die();
	}

	// add reservation if requested
	if(isset($_POST['add_hostname']) && isset($_POST['add_ip']) && isset($_POST['add_mac']) && isset($_POST['server'])) {
		$server = $controller->getServerByIdOrAddress($_POST['server']);
		$controller->setServer($server);
		if($controller->addReservation($_POST['add_hostname'], $_POST['add_mac'], $_POST['add_ip'])) {
			$controller->reloadDhcpConfig();
		}
		die();
	}

} catch(UnexpectedValueException $e) {
	header('HTTP/1.1 400 Invalid Request');
	die(htmlspecialchars($e->getMessage()));
} catch(NotFoundException $e) {
	header('HTTP/1.1 404 Not Found');
	die(LANG('not_found'));
} catch(PermissionException $e) {
	header('HTTP/1.1 401 Forbidden');
	die(LANG('permission_denied'));
} catch(Exception $e) {
	header('HTTP/1.1 500 Internal Server Error');
	die(htmlspecialchars($e->getMessage()));
}

header('HTTP/1.1 400 Invalid Request');
die(LANG('unknown_method'));
