<?php

class IscDhcpReservationsController {
	static function reloadDhcpConfig($server) {
		$cmd = 'sudo /usr/sbin/service isc-dhcp-server restart';
		if(!empty($server['RELOAD_COMMAND']))
			$cmd = $server['RELOAD_COMMAND'];

		if($server['ADDRESS'] == 'localhost') {
			echo system($cmd.' 2>&1', $ret);
			if($ret != 0) throw new RuntimeException(LANG('error_reloading_dhcp_configuration'));
		} else {
			$connection = @ssh2_connect($server['ADDRESS'], $server['PORT']);
			if(!$connection) throw new Exception(str_replace('%s', $server['ADDRESS'], LANG('ssh_connection_failed_placeholder')));
			$auth = @ssh2_auth_pubkey_file($connection, $server['USER'], $server['PUBKEY'], $server['PRIVKEY']);
			if(!$auth) throw new Exception(str_replace('%s', $server['USER'].'@'.$server['ADDRESS'], LANG('ssh_authentication_failed_placeholder')));
			$stdioStream = ssh2_exec($connection, $cmd);
			stream_set_blocking($stdioStream, true);
			echo stream_get_contents($stdioStream);
		}
	}
	static function addReservation($hostname, $mac, $addr, $server) {
		// syntax check
		if(!self::isValidDomainName($hostname)) {
			throw new UnexpectedValueException(str_replace('%s', $hostname, LANG('invalid_hostname_placeholder')));
		}
		if(!filter_var($mac, FILTER_VALIDATE_MAC)) {
			throw new UnexpectedValueException(str_replace('%s', $mac, LANG('invalid_mac_address_placeholder')));
		}
		if(!filter_var($addr, FILTER_VALIDATE_IP)) {
			throw new UnexpectedValueException(str_replace('%s', $addr, LANG('invalid_ip_address_placeholder')));
		}

		// load file
		$content = self::loadReservationsFile($server);

		// check occurences
		if(strpos(strtolower($content), strtolower($hostname).' {') !== false) {
			throw new UnexpectedValueException(str_replace('%s', $hostname, LANG('hostname_already_registered_placeholder')));
		}
		if(strpos(strtolower($content), strtolower($mac).';') !== false) {
			throw new UnexpectedValueException(str_replace('%s', $mac, LANG('mac_address_already_registered_placeholder')));
		}
		if(strpos(strtolower($content), strtolower($addr).';') !== false) {
			throw new UnexpectedValueException(str_replace('%s', $addr, LANG('ip_address_already_registered_placeholder')));
		}

		// append new entry
		$content .= "host ".$hostname." {\n"
			#."  option host-name \"".$hostname."\";\n"
			."  hardware ethernet ".$mac.";\n"
			."  fixed-address ".$addr.";\n"
			."}\n";

		self::saveReservationsFile($content, $server);
		return true;
	}
	static function removeReservation($hostname, $server) {
		// load file
		$oldcontent = self::loadReservationsFile($server);
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
		self::saveReservationsFile($newcontent, $server);
		return empty($found) ? false : $found;
	}
	static function getReservationServer($serverName) {
		if(defined('ISC_DHCP_SERVER')) foreach(ISC_DHCP_SERVER as $server) {
			if($server['ADDRESS'] == $serverName)
				return $server;
		}
		throw new Exception(str_replace('%s', $serverName, LANG('unknown_server_placeholder')));
	}
	static function loadReservationsFile($server) {
		$content = null;
		if($server['ADDRESS'] == 'localhost') {
			$content = file_get_contents($server['RESERVATIONS_FILE']);
		} else {
			try {
				$connection = @ssh2_connect($server['ADDRESS'], $server['PORT']);
				if(!$connection) throw new Exception(str_replace('%s', $server['ADDRESS'], LANG('ssh_connection_failed_placeholder')));
				$auth = @ssh2_auth_pubkey_file($connection, $server['USER'], $server['PUBKEY'], $server['PRIVKEY']);
				if(!$auth) throw new Exception(str_replace('%s', $server['USER'].'@'.$server['ADDRESS'], LANG('ssh_authentication_failed_placeholder')));
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
			throw new Exception(str_replace('%s', $server['ADDRESS'].':'.$server['RESERVATIONS_FILE'], LANG('unable_to_read_reservations_file_placeholder')));
		return $content;
	}
	static function saveReservationsFile($content, $server) {
		if($server['ADDRESS'] == 'localhost') {
			$status = file_put_contents($server['RESERVATIONS_FILE'], trim($content)."\n");
			if($status === false) throw new Exception(str_replace('%s', $server['ADDRESS'].':'.$server['RESERVATIONS_FILE'], LANG('unable_to_save_reservations_file_placeholder')));
		} else {
			$connection = @ssh2_connect($server['ADDRESS'], $server['PORT']);
			if(!$connection) throw new Exception(str_replace('%s', $server['ADDRESS'], LANG('ssh_connection_failed_placeholder')));
			$auth = @ssh2_auth_pubkey_file($connection, $server['USER'], $server['PUBKEY'], $server['PRIVKEY']);
			if(!$auth) throw new Exception(str_replace('%s', $server['USER'].'@'.$server['ADDRESS'], LANG('ssh_authentication_failed_placeholder')));
			$sftp = ssh2_sftp($connection);
			$remote = fopen('ssh2.sftp://'.intval($sftp).$server['RESERVATIONS_FILE'], 'w');
			if(fwrite($remote, $content) === false)
				throw new Exception(LANG('error_writing_remote_file'));
		}
	}
	static function isValidDomainName($domain_name) {
		return (preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $domain_name) //valid chars check
			&& preg_match("/^.{1,253}$/", $domain_name) //overall length check
			&& preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $domain_name)   ); //length of each label
	}
}
