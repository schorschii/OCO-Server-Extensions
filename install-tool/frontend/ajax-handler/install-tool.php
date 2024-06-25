<?php
$SUBVIEW = 1;
if(!isset($db) || !isset($cl)) die();

if(!$cl->checkPermission(null, 'InstallTool', false))
	die('<div class="alert warning">Sie sind nicht berechtigt diese Anwendung zu verwenden.</div>');

$settings = json_decode($db->settings->get('install-tool'), true);
if(empty($settings)) die('<div class="alert warning">Konfiguration nicht gefunden.</div>');

$error = false;
$infos = [];

switch($_POST['action'] ?? '(leer)') {

	case 'manual-install':
		try {
			// check inputs
			$hostname = strtoupper(trim($_POST['hostname'] ?? ''));
			if(empty($hostname)) {
				throw new RuntimeException('Der eingegebene Hostname ist ungültig');
			}
			if(!str_contains_only($hostname, 'abcdefghijklmnopqrstuvwxyz1234567890-', false)) {
				throw new RuntimeException('Ihr eingegebener Hostname '.$hostname.' ist ungültig. Bitte verwenden Sie nur Buchstaben und Ziffern.');
			}

			if(empty($_POST['identifier']) || !str_contains_only($_POST['identifier'], 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890_-:', false)) {
				throw new RuntimeException('Der Identifier (MAC/UUID/Seriennummer) enthält ungültige Zeichen.');
			}
			$identifier = $_POST['identifier'];
			if(filter_var($identifier, FILTER_VALIDATE_MAC)) {
				// if it is a mac address, make sure that it is the correct format using dashes and not dots
				$identifier = formatMac(trim($identifier));
			}

			$packageGroupId = intval($_POST['packagegroup'] ?? 0);
			$cl->getPackageGroup($packageGroupId); // permission check

			// init objects
			$ocoHandle = new Install\OcoOperations($db, $cl, $currentSystemUser, $settings['oco']);

			// remove old entries if requested
			if(!empty($_POST['removeold'])) {
				try {
					if($ocoHandle->removeAutounattendXml($identifier)) {
						$infos[] = ['info'=>'[ OK ] OCO: Windows Setup XML entfernt', 'infoclass'=>'success'];
					} else {
						$infos[] = ['info'=>'[WARN] OCO: keine passende Windows Setup XML gefunden', 'infoclass'=>'warning'];
					}

					$ocoHandle->removeOcoComputer($identifier);
					$infos[] = ['info'=>'[ OK ] OCO: Computerobjekt entfernt', 'infoclass'=>'success'];
				} catch(Exception $e) { // wrap additional context around the error message
					throw new RuntimeException('OCO-Computerobjekt konnte nicht entfernt werden: '.$e->getMessage());
				}
			}

			// OCO: create computer object
			try {
				$ocoHandle->createOcoComputer($hostname, $identifier, $packageGroupId);
				$infos[] = ['info'=>'[ OK ] OCO: Computer-Vorregistrierung & Basis-Softwarejobs', 'infoclass'=>'success'];

				$ocoHandle->createAutounattendXml($hostname, $identifier);
				$infos[] = ['info'=>'[ OK ] OCO: Windows Setup XML', 'infoclass'=>'success'];
			} catch(Exception $e) {
				throw new RuntimeException('OCO-Computerobjekt, Basis-Softwarejobs und Windows Setup XML konnten nicht angelegt werden: '.$e->getMessage());
			}

		} catch(Exception $e) {
			$error = true;
			$infos[] = ['info'=>$e->getMessage(), 'infoclass'=>'error'];
			$infos[] = ['info'=>'Der Vorgang war nicht erfolgreich. Bitte korrigieren Sie die Fehler und evtl. bereits angelegte Einträge manuell!', 'infoclass'=>'warning'];
		}
		break;


	case 'remove-install':
		try {
			// check inputs
			#$hostname = trim($_POST['hostname'] ?? '');
			#if(empty($hostname)) {
			#	throw new RuntimeException('Kein Hostname eingegeben');
			#}
			#if(!str_contains_only($hostname, 'abcdefghijklmnopqrstuvwxyz1234567890-', false)) {
			#	throw new RuntimeException('Ihr eingegebener Hostname '.$hostname.' ist ungültig. Bitte verwenden Sie nur Buchstaben und Ziffern.');
			#}

			if(empty($_POST['identifier']) || !str_contains_only($_POST['identifier'], 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890_-:', false)) {
				throw new RuntimeException('Der Identifier (MAC/UUID/Seriennummer) enthält ungültige Zeichen.');
			}
			$identifier = $_POST['identifier'];
			if(filter_var($identifier, FILTER_VALIDATE_MAC)) {
				// if it is a mac address, make sure that it is the correct format using dashes and not dots
				$identifier = formatMac(trim($identifier));
			}

			// init objects
			$ocoHandle = new Install\OcoOperations($db, $cl, $currentSystemUser, $settings['oco']);

			// OCO: remove computer object
			try {
				if($ocoHandle->removeAutounattendXml($identifier)) {
					$infos[] = ['info'=>'[ OK ] OCO: Windows Setup XML entfernt', 'infoclass'=>'success'];
				} else {
					$infos[] = ['info'=>'[WARN] OCO: keine passende Windows Setup XML gefunden', 'infoclass'=>'warning'];
				}

				$ocoHandle->removeOcoComputer($identifier);
				$infos[] = ['info'=>'[ OK ] OCO: Computerobjekt entfernt', 'infoclass'=>'success'];
			} catch(Exception $e) {
				throw new RuntimeException('OCO-Computerobjekt konnte nicht entfernt werden: '.$e->getMessage());
			}

		} catch(Exception $e) {
			$error = true;
			$infos[] = ['info'=>$e->getMessage(), 'infoclass'=>'error'];
			$infos[] = ['info'=>'Der Vorgang war nicht erfolgreich. Bitte korrigieren Sie die Fehler und evtl. bereits angelegte Einträge manuell!', 'infoclass'=>'warning'];
		}
		break;


	default:
		header('HTTP/1.1 400 Invalid Action');
		die('<div class="alert warning">Unbekannte Aktion '.htmlspecialchars($_POST['action'] ?? '(leer)').'</div>');
}

// show info boxes
foreach($infos as $info) {
	echo "<div class='alert ".$info['infoclass']."'>".htmlspecialchars($info['info'])."</div>";
}

//////////////////////////////////////////////////////////

function str_contains_only($string, $validChars, $caseSensitive = true) {
	foreach(str_split($string) as $char) {
		$found = false;
		foreach(str_split($validChars) as $char2) {
			if($caseSensitive) {
				if($char === $char2) {
					$found = true; break;
				}
			} else {
				if(strtoupper($char) === strtoupper($char2)) {
					$found = true; break;
				}
			}
		}
		if($found == false) return false;
	}
	return true;
}
function removeMacSeparator($mac, $separator = array(':', '-')) {
	return str_replace($separator, '', $mac);
}
function addMacSeparator($mac, $separator = ':') {
	$result = '';
	while(strlen($mac) > 0) {
		$sub = substr($mac, 0, 2);
		$result .= $sub . $separator;
		$mac = substr($mac, 2, strlen($mac));
	}
	// remove trailing colon
	$result = substr($result, 0, strlen($result) - 1);
	return $result;
}
function formatMac($mac) {
	return strtolower(addMacSeparator(removeMacSeparator($mac)));
}
