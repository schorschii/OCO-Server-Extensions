<?php
const RESERVATIONS_FILE    = '/etc/dhcp/reservations.conf';
# /etc/sudoers: www-data ALL=(ALL:ALL) NOPASSWD:/usr/sbin/service isc-dhcp-server restart

function reloadDhcpConfig() {
	echo system('sudo /usr/sbin/service isc-dhcp-server restart 2>&1', $ret);
	if($ret != 0) {
		throw new RuntimeException("ERROR reloading DHCP configuration"."\n");
	}
}
function addReservation($hostname, $mac, $addr) {
	// syntax check
	if(!isValidDomainName($hostname)) {
		throw new RuntimeException("ERROR: invalid hostname ".$hostname."\n");
	}
	if(!filter_var($mac, FILTER_VALIDATE_MAC)) {
		throw new RuntimeException("ERROR: invalid mac ".$mac."\n");
	}
	if(!filter_var($addr, FILTER_VALIDATE_IP)) {
		throw new RuntimeException("ERROR: invalid addr ".$addr."\n");
	}

	// load file
	$content = file_get_contents(RESERVATIONS_FILE);

	// check occurences
	if(strpos($content, 'host {'.$hostname) !== false) {
		throw new RuntimeException("ERROR: host ".$hostname." already registered\n");
	}
	if(strpos($content, $mac.';') !== false) {
		throw new RuntimeException("ERROR: mac ".$mac." already registered\n");
	}
	if(strpos($content, $addr.';') !== false) {
		throw new RuntimeException("ERROR: address ".$addr." already registered\n");
	}

	// append new entry
	$content .= "host ".$hostname." {\n"
		#."  option host-name \"".$hostname."\";\n"
		."  hardware ethernet ".$mac.";\n"
		."  fixed-address ".$addr.";\n"
		."}\n";

	// save file
	return file_put_contents(RESERVATIONS_FILE, $content);
}
function removeReservation($hostname) {
	$found = [];
	$oldcontent = file_get_contents(RESERVATIONS_FILE);
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
	file_put_contents(RESERVATIONS_FILE, trim($newcontent)."\n");
	return empty($found) ? false : $found;
}
function isValidDomainName($domain_name) {
	return (preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $domain_name) //valid chars check
		&& preg_match("/^.{1,253}$/", $domain_name) //overall length check
		&& preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $domain_name)   ); //length of each label
}
