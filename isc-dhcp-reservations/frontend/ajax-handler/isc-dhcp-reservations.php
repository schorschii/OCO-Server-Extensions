<?php
$SUBVIEW = 1;
if(!isset($db) || !isset($cl)) die();

if(!$cl->checkPermission(null, get_class(new IscDhcpReservationsController()), false))
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");

// remove reservation if requested
if(isset($_POST['remove_hostname']) && isset($_POST['server'])) {
	try {
		$updateServer = IscDhcpReservationsController::getReservationServer($_POST['server']);
		if(empty($_POST['remove_hostname'])) {
			throw new UnexpectedValueException(LANG('hostname_cannot_be_empty'));
		}
		if(IscDhcpReservationsController::removeReservation($_POST['remove_hostname'], $updateServer)) {
			IscDhcpReservationsController::reloadDhcpConfig($updateServer);
		} else {
			throw new UnexpectedValueException(LANG('no_suitable_reservation_found'));
		}
		die();
	} catch(UnexpectedValueException $e) {
		header('HTTP/1.1 400 Invalid Request');
		die(htmlspecialchars($e->getMessage()));
	} catch(Exception $e) {
		header('HTTP/1.1 500 Internal Server Error');
		die(htmlspecialchars($e->getMessage()));
	}
}

// add reservation if requested
if(isset($_POST['add_hostname']) && isset($_POST['add_ip']) && isset($_POST['add_mac']) && isset($_POST['server'])) {
	try {
		$updateServer = IscDhcpReservationsController::getReservationServer($_POST['server']);
		if(IscDhcpReservationsController::addReservation($_POST['add_hostname'], $_POST['add_mac'], $_POST['add_ip'], $updateServer)) {
			IscDhcpReservationsController::reloadDhcpConfig($updateServer);
		}
		die();
	} catch(UnexpectedValueException $e) {
		header('HTTP/1.1 400 Invalid Request');
		die(htmlspecialchars($e->getMessage()));
	} catch(Exception $e) {
		header('HTTP/1.1 500 Internal Server Error');
		die(htmlspecialchars($e->getMessage()));
	}
}

header('HTTP/1.1 400 Invalid Request');
die(LANG('unknown_method'));
