<?php

namespace Install;

class OcoOperations {

	private $db;
	private $cl;
	private $systemUser;
	private $settings;

	function __construct($db, $cl, $systemUser, $ocoSettings) {
		$this->db = $db;
		$this->cl = $cl;
		$this->systemUser = $systemUser;
		$this->settings = $ocoSettings;
	}

	public function createOcoComputer($hostname, $mac, $primaryPackageGroupId) {
		$cid = $this->cl->createComputer($hostname);
		if(empty($cid)) throw new \Exception('createComputer() hat keine Computer-Insert-ID zurückgegeben!');
		if(!$this->addComputerMacAddress($cid, $mac)) throw new \Exception('Fehler beim Einfügen der MAC-Adresse');

		// create basic software jobs
		$this->cl->deploy(
			'Installation '.$hostname,
			'Automagically created by the glorious Install-Tool',
			[ $cid ], [], [],
			[], [ $primaryPackageGroupId ], [],
			date('Y-m-d H:i:s'), null,
			0/*wol*/, 0/*shutdown*/, 5/*timeout*/, 0/*force install*/,
			0/*sequence mode*/, 1/*priority*/, []/*ip ranges*/
		);

		// insert into group for base package deployment rule
		#$primaryComputerGroupId = $this->settings['computer-group-id-base-windows'];
		#$this->cl->addComputerToGroup($cid, $primaryComputerGroupId);
	}
	public function removeOcoComputer($mac) {
		#$computer = $this->db->selectComputerByHostname($hostname);
		#if(empty($computer)) throw new \Exception('Ein Computer mit diesem Hostnamen konnte nicht gefunden werden!');

		$cid = $this->getComputerIdByMacAddress($mac);
		if(empty($cid)) {
			$cid = $this->getComputerIdBySerial($mac);
			if(empty($cid)) {
				throw new \Exception('Es konnte kein Computer mit dieser MAC-Adresse oder Seriennummer in der Datenbank gefunden werden.');
			} else {
				$this->cl->removeComputer($cid, true);
			}
		} else {
			$this->cl->removeComputer($cid, true);
		}
	}

	private function addComputerMacAddress($computer_id, $mac) {
		$stmt = $this->db->getDbHandle()->prepare(
			'INSERT INTO computer_network (computer_id, nic_number, address, netmask, broadcast, mac, interface)
			VALUES (:computer_id, 0, "", "", "", :mac, "dummy")'
		);
		return $stmt->execute([':computer_id' => $computer_id, ':mac' => $mac]);
	}
	private function getComputerIdByMacAddress($mac) {
		$stmt = $this->db->getDbHandle()->prepare(
			'SELECT * FROM computer_network WHERE mac = :mac'
		);
		$stmt->execute([':mac' => $mac]);
		foreach($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
			return $row['computer_id'];
		}
	}
	private function getComputerIdBySerial($serialno) {
		$stmt = $this->db->getDbHandle()->prepare(
			'SELECT * FROM computer WHERE `serial` = :serialno'
		);
		$stmt->execute([':serialno' => $serialno]);
		foreach($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
			return $row['id'];
		}
	}

	public function createAutounattendXml($hostname, $mac) {
		$macFileName = str_replace(':', '-', $mac);
		$template = file_get_contents($this->settings['preseed-path-windows'].'/TEMPLATE.xml');
		$template = str_replace('$$HOSTNAME$$', $hostname, $template);
		file_put_contents($this->settings['preseed-path-windows'].'/'.$macFileName.'.xml', $template);
	}
	public function removeAutounattendXml($mac) {
		$macFileName = str_replace(':', '-', $mac);
		$filePath = $this->settings['preseed-path-windows'].'/'.$macFileName.'.xml';
		if(!file_exists($filePath)) return false;
		return unlink($filePath);
	}

}
