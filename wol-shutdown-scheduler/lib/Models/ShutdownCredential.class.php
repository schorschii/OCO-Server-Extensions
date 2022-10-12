<?php

namespace Models;

class ShutdownCredential {

	public $name;

	public $sshUsername;
	public $sshPassword;
	public $sshPrivKeyFile;
	public $sshPubKeyFile;
	public $sshPort;
	public $sshCommand;

	public $winRpcUsername;
	public $winRpcPassword;

	function __construct(
		$name,
		$sshUsername = null,
		$sshPassword = null,
		$sshPrivKeyFile = null,
		$sshPubKeyFile = null,
		$sshPort = null,
		$sshCommand = null,
		$winRpcUsername = null,
		$winRpcPassword = null
	) {
		$this->name = $name;
		$this->sshUsername = $sshUsername ?? null;
		$this->sshPassword = $sshPassword ?? null;
		$this->sshPrivKeyFile = $sshPrivKeyFile ?? null;
		$this->sshPubKeyFile = $sshPubKeyFile ?? null;
		$this->sshPort = $sshPort ?? 22;
		$this->sshCommand = $sshCommand ?? 'sudo poweroff';
		$this->winRpcUsername = $winRpcUsername ?? null;
		$this->winRpcPassword = $winRpcPassword ?? null;
	}

}
