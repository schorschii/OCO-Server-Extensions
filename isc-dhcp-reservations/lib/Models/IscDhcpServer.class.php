<?php

namespace Models;

class IscDhcpServer {

	const DEFAULT_RELOAD_COMMAND = 'sudo /usr/sbin/service isc-dhcp-server restart';

	public $id;
	public $title;
	public $address;
	public $port;
	public $user;
	public $privkey;
	public $pubkey;
	public $reservationsFile;
	public $reloadCommand;

	function __construct($title, $address, $port, $user, $privkey, $pubkey, $reservationsFile, $reloadCommand) {
		$this->title = $title;
		$this->address = $address;
		$this->id = $address; // for permission checks
		$this->port = $port;
		$this->user = $user;
		$this->privkey = $privkey;
		$this->pubkey = $pubkey;
		$this->reservationsFile = $reservationsFile;
		if(empty($reloadCommand)) {
			$this->reloadCommand = self::DEFAULT_RELOAD_COMMAND;
		} else {
			$this->reloadCommand = $reloadCommand;
		}
	}

}
