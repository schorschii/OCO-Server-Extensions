<?php

class IscDhcpReservationsController {

	private /*CoreLogic*/ $cl;
	private /*Models\IscDhcpServer*/ $server;

	function __construct(CoreLogic $cl, Models\IscDhcpServer $server) {
		$this->server = $server;
		$this->cl = $cl;
	}

	public static function getServerByAddress(CoreLogic $cl, string $address) {
		if(defined('ISC_DHCP_SERVER')) foreach(ISC_DHCP_SERVER as $configEntry) {
			if(!empty($configEntry['ADDRESS']) && $configEntry['ADDRESS'] == $address) {
				$server = new Models\IscDhcpServer(
					$configEntry['TITLE'],
					$configEntry['ADDRESS'],
					$configEntry['PORT'] ?? null,
					$configEntry['USER'] ?? null,
					$configEntry['PRIVKEY'] ?? null,
					$configEntry['PUBKEY'] ?? null,
					$configEntry['RESERVATIONS_FILE'],
					$configEntry['RELOAD_COMMAND'] ?? null
				);
				$cl->checkPermission($server, PermissionManager::METHOD_READ);
				return $server;
			}
		}
		throw new NotFoundException(str_replace('%s', $address, LANG('unknown_server_placeholder')));
	}

	public static function getAllServers(CoreLogic $cl) {
		$foundServers = [];
		if(defined('ISC_DHCP_SERVER')) foreach(ISC_DHCP_SERVER as $configEntry) {
			if(!empty($configEntry['ADDRESS']) && !empty($configEntry['TITLE']) && !empty($configEntry['RESERVATIONS_FILE'])) {
				$server = new Models\IscDhcpServer(
					$configEntry['TITLE'],
					$configEntry['ADDRESS'],
					$configEntry['PORT'] ?? null,
					$configEntry['USER'] ?? null,
					$configEntry['PRIVKEY'] ?? null,
					$configEntry['PUBKEY'] ?? null,
					$configEntry['RESERVATIONS_FILE'],
					$configEntry['RELOAD_COMMAND'] ?? null
				);
				if($cl->checkPermission($server, PermissionManager::METHOD_READ, false)) {
					$foundServers[] = $server;
				}
			}
		}
		return $foundServers;
	}

	function reloadDhcpConfig() {
		$this->cl->checkPermission($this->server, PermissionManager::METHOD_WRITE);
		if($this->server->address == 'localhost') {
			echo system($this->server->reloadCommand.' 2>&1', $ret);
			if($ret != 0) throw new RuntimeException(LANG('error_reloading_dhcp_configuration'));
		} else {
			$connection = @ssh2_connect($this->server->address, $this->server->port);
			if(!$connection) throw new Exception(str_replace('%s', $this->server->address, LANG('ssh_connection_failed_placeholder')));
			$auth = @ssh2_auth_pubkey_file($connection, $this->server->user, $this->server->pubkey, $this->server->privkey);
			if(!$auth) throw new Exception(str_replace('%s', $this->server->user.'@'.$this->server->address, LANG('ssh_authentication_failed_placeholder')));
			$stdioStream = ssh2_exec($connection, $this->server->reloadCommand);
			stream_set_blocking($stdioStream, true);
			echo stream_get_contents($stdioStream);
		}
	}

	public function addReservation($hostname, $mac, $addr) {
		$this->cl->checkPermission($this->server, PermissionManager::METHOD_WRITE);

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
		$content = $this->loadReservationsFile();

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

		$this->saveReservationsFile($content);
		return true;
	}

	public function removeReservation($hostname) {
		$this->cl->checkPermission($this->server, PermissionManager::METHOD_WRITE);

		// load file
		$oldcontent = $this->loadReservationsFile();
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
		$this->saveReservationsFile($newcontent);
		return empty($found) ? false : $found;
	}

	public function loadReservationsFile() {
		$this->cl->checkPermission($this->server, PermissionManager::METHOD_READ);

		$content = null;
		if($this->server->address == 'localhost') {
			$content = file_get_contents($this->server->reservationsFile);
		} else {
			try {
				$connection = @ssh2_connect($this->server->address, $this->server->port);
				if(!$connection) throw new Exception(str_replace('%s', $this->server->address, LANG('ssh_connection_failed_placeholder')));
				$auth = @ssh2_auth_pubkey_file($connection, $this->server->user, $this->server->pubkey, $this->server->privkey);
				if(!$auth) throw new Exception(str_replace('%s', $this->server->user.'@'.$this->server->address, LANG('ssh_authentication_failed_placeholder')));
				$sftp = ssh2_sftp($connection);
				$remote = fopen('ssh2.sftp://'.intval($sftp).$this->server->reservationsFile, 'rb');
				$content = '';
				if($remote) while(!feof($remote)) {
					$content .= fread($remote, 4096);
				}
			} catch(Exception $e) {
				error_log($e->getMessage());
			}
		}
		if($content === false || $content === null)
			throw new Exception(str_replace('%s', $this->server->address.':'.$this->server->reservationsFile, LANG('unable_to_read_reservations_file_placeholder')));
		return $content;
	}

	private function saveReservationsFile($content) {
		$this->cl->checkPermission($this->server, PermissionManager::METHOD_WRITE);

		if($this->server->address == 'localhost') {
			$status = file_put_contents($this->server->reservationsFile, trim($content)."\n");
			if($status === false) throw new Exception(str_replace('%s', $this->server->address.':'.$this->server->reservationsFile, LANG('unable_to_save_reservations_file_placeholder')));
		} else {
			$connection = @ssh2_connect($this->server->address, $this->server->port);
			if(!$connection) throw new Exception(str_replace('%s', $this->server->address, LANG('ssh_connection_failed_placeholder')));
			$auth = @ssh2_auth_pubkey_file($connection, $this->server->user, $this->server->pubkey, $this->server->privkey);
			if(!$auth) throw new Exception(str_replace('%s', $this->server->user.'@'.$this->server->address, LANG('ssh_authentication_failed_placeholder')));
			$sftp = ssh2_sftp($connection);
			$remote = fopen('ssh2.sftp://'.intval($sftp).$this->server->reservationsFile, 'w');
			if(fwrite($remote, $content) === false)
				throw new Exception(LANG('error_writing_remote_file'));
		}
	}

	static function isValidDomainName($domain_name) {
		return (preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $domain_name) // valid chars check
			&& preg_match("/^.{1,253}$/", $domain_name) // overall length check
			&& preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $domain_name)   ); // length of each label
	}

}
