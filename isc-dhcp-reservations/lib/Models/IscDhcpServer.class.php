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
	public $reloadCommand = self::DEFAULT_RELOAD_COMMAND;

	function __construct($id, $title, $address, $port, $user, $privkey, $pubkey, $reservationsFile, $reloadCommand) {
		$this->title = $title;
		$this->address = $address;
		$this->id = $id; // for permission checks
		$this->port = $port;
		$this->user = $user;
		$this->privkey = $privkey;
		$this->pubkey = $pubkey;
		$this->reservationsFile = $reservationsFile;
		if(!empty($reloadCommand)) $this->reloadCommand = $reloadCommand;
	}

}
