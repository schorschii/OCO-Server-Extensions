<?php
require_once(__DIR__.'/isc-dhcp-reservations.conf.php');

function reloadDhcpConfig() {
	echo system('sudo /usr/sbin/service isc-dhcp-server restart 2>&1', $ret);
	if($ret != 0) {
		throw new RuntimeException("ERROR reloading DHCP configuration");
	}
}
function addReservation($hostname, $mac, $addr) {
	// syntax check
	if(!isValidDomainName($hostname)) {
		throw new UnexpectedValueException("Invalid Hostname: ".htmlspecialchars($hostname));
	}
	if(!filter_var($mac, FILTER_VALIDATE_MAC)) {
		throw new UnexpectedValueException("Invalid MAC Address: ".htmlspecialchars($mac));
	}
	if(!filter_var($addr, FILTER_VALIDATE_IP)) {
		throw new UnexpectedValueException("Invalid IP Address: ".htmlspecialchars($addr));
	}

	// load file
	$content = file_get_contents(ISC_DHCP_RESERVATIONS_FILE);
	if($content === false) throw new Exception('Unable to open reservation file '.ISC_DHCP_RESERVATIONS_FILE);

	// check occurences
	if(strpos(strtolower($content), strtolower($hostname).' {') !== false) {
		throw new UnexpectedValueException("Hostname ".htmlspecialchars($hostname)." Already Registered");
	}
	if(strpos(strtolower($content), strtolower($mac).';') !== false) {
		throw new UnexpectedValueException("MAC Address ".htmlspecialchars($mac)." Already Registered");
	}
	if(strpos(strtolower($content), strtolower($addr).';') !== false) {
		throw new UnexpectedValueException("IP Address ".htmlspecialchars($addr)." Already Registered");
	}

	// append new entry
	$content .= "host ".$hostname." {\n"
		#."  option host-name \"".$hostname."\";\n"
		."  hardware ethernet ".$mac.";\n"
		."  fixed-address ".$addr.";\n"
		."}\n";

	// save file
	$status = file_put_contents(ISC_DHCP_RESERVATIONS_FILE, $content);
	if($status === false) throw new Exception('Unable to save reservation file '.ISC_DHCP_RESERVATIONS_FILE);
	return true;
}
function removeReservation($hostname) {
	// load file
	$oldcontent = file_get_contents(ISC_DHCP_RESERVATIONS_FILE);
	if($oldcontent === false) throw new Exception('Unable to open reservation file '.ISC_DHCP_RESERVATIONS_FILE);
	// remove host block from content
	$found = [];
	$newcontent = "";
	$inhostblock = false;
	foreach(preg_split("/((\r?\n)|(\r\n?))/", $oldcontent) as $line) { // for each line
		if(strtoupper(trim($line)) == "HOST " . strtoupper($hostname) . " {") {
			$inhostblock = true;
			$found['hostname'] = $hostname;
		}
		if($inhostblock) {
			if(substr(trim($line), 0, 17) == "hardware ethernet") {
				$found['mac'] = trim( str_replace(["hardware ethernet",";"], "", trim($line)) );
			}
			if(substr(trim($line), 0, 13) == "fixed-address") {
				$found['addr'] = trim( str_replace(["fixed-address",";"], "", trim($line)) );
			}
		} else {
			$newcontent .= $line . "\n";
		}
		if(trim($line) == "}") {
			$inhostblock = false;
		}
	}
	// save file
	$status = file_put_contents(ISC_DHCP_RESERVATIONS_FILE, trim($newcontent)."\n");
	if($status === false) throw new Exception('Unable to save reservation file '.ISC_DHCP_RESERVATIONS_FILE);
	return empty($found) ? false : $found;
}
function isValidDomainName($domain_name) {
	return (preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $domain_name) //valid chars check
		&& preg_match("/^.{1,253}$/", $domain_name) //overall length check
		&& preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $domain_name)   ); //length of each label
}
