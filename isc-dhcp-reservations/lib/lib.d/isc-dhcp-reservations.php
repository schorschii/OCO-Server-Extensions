<?php
require_once(__DIR__.'/isc-dhcp-reservations.conf.php');

function reloadDhcpConfig($server) {
	$cmd = 'sudo /usr/sbin/service isc-dhcp-server restart';
	if(!empty($server['RELOAD_COMMAND']))
		$cmd = $server['RELOAD_COMMAND'];

	if($server['ADDRESS'] == 'localhost') {
		echo system($cmd.' 2>&1', $ret);
		if($ret != 0) throw new RuntimeException("ERROR reloading DHCP configuration");
	} else {
		$connection = @ssh2_connect($server['ADDRESS'], $server['PORT']);
		if(!$connection) throw new Exception('SSH Connection to '.$server['ADDRESS'].' failed');
		$auth = @ssh2_auth_pubkey_file($connection, $server['USER'], $server['PUBKEY'], $server['PRIVKEY']);
		if(!$auth) throw new Exception('SSH Authentication with '.$server['USER'].'@'.$server['ADDRESS'].' failed');
		$stdioStream = ssh2_exec($connection, $cmd);
		stream_set_blocking($stdioStream, true);
		echo stream_get_contents($stdioStream);
	}
}
function addReservation($hostname, $mac, $addr, $server) {
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
	$content = loadReservationsFile($server);

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

	saveReservationsFile($content, $server);
	return true;
}
function removeReservation($hostname, $server) {
	// load file
	$oldcontent = loadReservationsFile($server);
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
	saveReservationsFile($newcontent, $server);
	return empty($found) ? false : $found;
}
function getReservationServer($serverName) {
	foreach(ISC_DHCP_SERVER as $server) {
		if($server['ADDRESS'] == $serverName)
			return $server;
	}
	throw new Exception('Unknown Server '.$serverName);
}
function loadReservationsFile($server) {
	$content = null;
	if($server['ADDRESS'] == 'localhost') {
		$content = file_get_contents($server['RESERVATIONS_FILE']);
	} else {
		try {
			$connection = @ssh2_connect($server['ADDRESS'], $server['PORT']);
			if(!$connection) throw new Exception('SSH Connection to '.$server['ADDRESS'].' failed');
			$auth = @ssh2_auth_pubkey_file($connection, $server['USER'], $server['PUBKEY'], $server['PRIVKEY']);
			if(!$auth) throw new Exception('SSH Authentication with '.$server['USER'].'@'.$server['ADDRESS'].' failed');
			$sftp = ssh2_sftp($connection);
			$remote = fopen('ssh2.sftp://'.intval($sftp).$server['RESERVATIONS_FILE'], 'rb');
			$content = '';
			if($remote) while(!feof($remote)) {
				$content .= fread($remote, 4096);
			}
		} catch(Exception $e) {
			error_log($e->getMessage());
		}
	}
	if($content === false || $content === null)
		throw new Exception('Unable to read reservations file '.htmlspecialchars($server['ADDRESS'].':'.$server['RESERVATIONS_FILE']));
	return $content;
}
function saveReservationsFile($content, $server) {
	if($server['ADDRESS'] == 'localhost') {
		$status = file_put_contents($server['RESERVATIONS_FILE'], trim($content)."\n");
		if($status === false) throw new Exception('Unable to save reservation file '.$server['RESERVATIONS_FILE']);
	} else {
		$connection = @ssh2_connect($server['ADDRESS'], $server['PORT']);
		if(!$connection) throw new Exception('SSH Connection to '.$server['ADDRESS'].' failed');
		$auth = @ssh2_auth_pubkey_file($connection, $server['USER'], $server['PUBKEY'], $server['PRIVKEY']);
		if(!$auth) throw new Exception('SSH Authentication with '.$server['USER'].'@'.$server['ADDRESS'].' failed');
		$sftp = ssh2_sftp($connection);
		$remote = fopen('ssh2.sftp://'.intval($sftp).$server['RESERVATIONS_FILE'], 'w');
		if(fwrite($remote, $content) === false)
			throw new Exception('Error writing in remote file');
	}
}
function isValidDomainName($domain_name) {
	return (preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $domain_name) //valid chars check
		&& preg_match("/^.{1,253}$/", $domain_name) //overall length check
		&& preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $domain_name)   ); //length of each label
}
