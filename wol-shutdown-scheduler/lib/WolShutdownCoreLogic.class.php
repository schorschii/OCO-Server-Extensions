<?php

class WolShutdownCoreLogic extends CoreLogic {

	function __construct($db, $systemUser=null) {
		parent::__construct(new WolShutdownDatabaseController(), $systemUser);
	}

	public static function getShutdownCredentials() {
		$credentials = [];
		if(defined('WOL_SHUTDOWN_SCHEDULER_CREDENTIALS')) foreach(WOL_SHUTDOWN_SCHEDULER_CREDENTIALS as $credentialConfig) {
			if(!empty($credentialConfig['title'])) {
				$credentials[] = new Models\ShutdownCredential(
					$credentialConfig['title'],
					$credentialConfig['ssh-username'] ?? null,
					$credentialConfig['ssh-password'] ?? null,
					$credentialConfig['ssh-privkey-file'] ?? null,
					$credentialConfig['ssh-pubkey-file'] ?? null,
					$credentialConfig['ssh-port'] ?? null,
					$credentialConfig['ssh-command'] ?? null,
					$credentialConfig['winrpc-username'] ?? null,
					$credentialConfig['winrpc-password'] ?? null
				);
			}
		}
		return $credentials;
	}
	public static function getShutdownCredentialByTitle($title) {
		if(defined('WOL_SHUTDOWN_SCHEDULER_CREDENTIALS')) foreach(WOL_SHUTDOWN_SCHEDULER_CREDENTIALS as $credentialConfig) {
			if(!empty($credentialConfig['title'])) {
				return new Models\ShutdownCredential(
					$credentialConfig['title'],
					$credentialConfig['ssh-username'] ?? null,
					$credentialConfig['ssh-password'] ?? null,
					$credentialConfig['ssh-privkey-file'] ?? null,
					$credentialConfig['ssh-pubkey-file'] ?? null,
					$credentialConfig['ssh-port'] ?? null,
					$credentialConfig['ssh-command'] ?? null,
					$credentialConfig['winrpc-username'] ?? null,
					$credentialConfig['winrpc-password'] ?? null
				);
			}
		}
		throw new NotFoundException('Shutdown credential not found in config file');
	}

	public static function updateWolPlans() {
		$woldb = new WolShutdownDatabaseController();
		// set new schedule as active if start_time reached
		foreach($woldb->selectAllWolPlanByWolGroupId() as $plan) {
			if(!empty($plan->start_time) && strtotime($plan->start_time) < time()) {
				$computer_group_name = $woldb->getComputerGroupBreadcrumbString($plan->computer_group_id);
				echo "  set new schedule $plan->wol_schedule_name as active for computer group $computer_group_name\n";
				self::removeActiveWolPlan($woldb, $plan->computer_group_id, $plan->wol_schedule_id);
				$woldb->updateWolPlan($plan->id, $plan->wol_group_id, $plan->computer_group_id, $plan->wol_schedule_id, $plan->shutdown_credential, null, $plan->end_time, $plan->description);
			}
			if(!empty($plan->end_time) && strtotime($plan->end_time) < time()) {
				echo "  delete $plan->end_time expired plan #$plan->id (schedule $plan->wol_schedule_name)\n";
				$woldb->deleteWolPlan($plan->id);
			}
		}
	}
	private static function removeActiveWolPlan($woldb, $computer_group_id, $wol_schedule_id) {
		foreach($woldb->selectAllWolPlanByWolGroupId() as $plan) {
			if(empty($plan->start_time) && $plan->computer_group_id == $computer_group_id && $plan->wol_schedule_id == $wol_schedule_id) {
				$woldb->deleteWolPlan($plan->id);
			}
		}
	}

	public static function executeWolShutdown($db) {
		$currentTime = date('H:i');
		$woldb = new WolShutdownDatabaseController();
		foreach($woldb->selectAllWolPlanByWolGroupId() as $plan) {
			if(empty($plan->start_time)) { // execute all active plans
				$schedule = $woldb->selectWolSchedule($plan->wol_schedule_id);
				$timeFrame = null;
				$currentWeekDay = date('w');
				if($currentWeekDay == '0') $timeFrame = $schedule->sunday;
				if($currentWeekDay == '1') $timeFrame = $schedule->monday;
				if($currentWeekDay == '2') $timeFrame = $schedule->tuesday;
				if($currentWeekDay == '3') $timeFrame = $schedule->wednesday;
				if($currentWeekDay == '4') $timeFrame = $schedule->thursday;
				if($currentWeekDay == '5') $timeFrame = $schedule->friday;
				if($currentWeekDay == '6') $timeFrame = $schedule->saturday;
				$timeFrame = explode('-', $timeFrame);
				if(count($timeFrame) >= 1 && $timeFrame[0] === $currentTime) { // wol time
					echo 'Execute WOL for computer group #'.$plan->computer_group_id."\n";
					self::executeWol($woldb, $plan->computer_group_id);
					echo "\n";
				}
				if(count($timeFrame) >= 2 && $timeFrame[1] === $currentTime) { // shutdown time
					echo 'Execute shutdown for computer group #'.$plan->computer_group_id."\n";
					$credential = self::getShutdownCredentialByTitle($plan->shutdown_credential);
					self::executeShutdown($woldb, $plan->computer_group_id, $credential);
					echo "\n";
				}
			}
		}
	}
	private static function executeWol($woldb, $computer_group_id) {
		$wolMacAdresses = [];
		foreach($woldb->selectAllComputerByComputerGroupId($computer_group_id) as $c) {
			foreach($woldb->selectAllComputerNetworkByComputerId($c->id) as $n) {
				if(empty($n->mac) || $n->mac == '-' || $n->mac == '?') continue;
				$wolMacAdresses[] = $n->mac;
			}
		}
		$woldb->insertLogEntry(Models\Log::LEVEL_INFO, 'WOL-SHUTDOWN-SCHEDULER', null, 'oco.wol_shutdown_scheduler.wol', $wolMacAdresses);
		WakeOnLan::wol($wolMacAdresses, true);
	}
	private static function executeShutdown($woldb, $computer_group_id, $credential) {
		$actions = [];
		foreach($woldb->selectAllComputerByComputerGroupId($computer_group_id) as $c) {
			$address = $c->remote_address ?? $c->hostname;
			$identifier = $c->id.';'.$c->hostname.';'.$address;
			if(empty($address)) {
				$actions[$identifier] = 'ERROR: remote address is empty!';
				continue;
			}
			if(self::executeShutdownSsh($address, $credential)) {
				$actions[$identifier] = 'OK (SSH)';
				continue;
			}
			if(self::executeShutdownWinRpc($address, $credential)) {
				$actions[$identifier] = 'OK (WinRPC)';
				continue;
			}
			$actions[$identifier] = 'ERROR: SSH and WinRPC failed!';
		}
		$woldb->insertLogEntry(Models\Log::LEVEL_INFO, 'WOL-SHUTDOWN-SCHEDULER', null, 'oco.wol_shutdown_scheduler.shutdown', $actions);
	}
	private static function executeShutdownSsh(string $address, Models\ShutdownCredential $shutdownCredential) {
		$originalConnectionTimeout = ini_get('default_socket_timeout');
		ini_set('default_socket_timeout', 5);
		$c = @ssh2_connect($address, $shutdownCredential->sshPort);
		ini_set('default_socket_timeout', $originalConnectionTimeout);
		if(!$c) return false;
		if(!empty($shutdownCredential->sshPrivKeyFile)) {
			$a = @ssh2_auth_pubkey_file($c, $shutdownCredential->sshUsername, $shutdownCredential->sshPubKeyFile, $shutdownCredential->sshPrivKeyFile);
		} elseif(!empty($shutdownCredential->sshPrivKeyFile)) {
			$a = @ssh2_auth_password($c, $shutdownCredential->sshUsername, $shutdownCredential->sshPassword);
		}
		if(!$a) return false;
		$cmd = 'poweroff';
		if(!empty($shutdownCredential->sshCommand)) $cmd = $shutdownCredential->sshCommand;
		$stdioStream = ssh2_exec($c, $cmd);
		stream_set_blocking($stdioStream, true);
		$cmdOutput = @stream_get_contents($stdioStream);
		return true;
	}
	private static function executeShutdownWinRpc(string $address, Models\ShutdownCredential $shutdownCredential) {
		if(empty($shutdownCredential->winRpcUsername) || empty($shutdownCredential->winRpcPassword)) return false;
		$process = proc_open(
			'/usr/bin/net rpc shutdown -I '.escapeshellarg($address).' -U '.escapeshellarg($shutdownCredential->winRpcUsername).'%'.escapeshellarg($shutdownCredential->winRpcPassword),
			array(
				0 => array("pipe", "r"), // STDIN
				1 => array("pipe", "w"), // STDOUT
				2 => array("pipe", "w")  // STDERR
			 ),
			$pipes
		);
		if(!is_resource($process)) throw new \Exception('Unable to start net rpc process');
		//fwrite($pipes[0], "");
		fclose($pipes[0]);
		$stdOut = stream_get_contents($pipes[1]);
		fclose($pipes[1]);
		$stdErr = stream_get_contents($pipes[2]);
		fclose($pipes[2]);
		$returnCode = proc_close($process);
		return ($returnCode == 0);
	}

	public function getWolGroupBreadcrumbString($id) {
		$currentGroupId = $id;
		$groupStrings = [];
		while(true) {
			$currentGroup = $this->db->selectWolGroup($currentGroupId);
			$groupStrings[] = $currentGroup->name;
			if($currentGroup->parent_wol_group_id === null) {
				break;
			} else {
				$currentGroupId = $currentGroup->parent_wol_group_id;
			}
		}
		$groupStrings = array_reverse($groupStrings);
		return implode($groupStrings, ' » ');
	}

	public function getWolGroups($parentId=null) {
		$groupsFiltered = [];
		foreach($this->db->selectAllWolGroupByParentWolGroupId($parentId) as $group) {
			if($this->checkPermission($group, PermissionManager::METHOD_READ, false, $this->getParentWolGroupsRecursively($group)))
				$groupsFiltered[] = $group;
		}
		return $groupsFiltered;
	}
	public function getWolGroup($id) {
		$group = $this->db->selectWolGroup($id);
		if(empty($group)) throw new NotFoundException();
		$this->checkPermission($group, PermissionManager::METHOD_READ, true, $this->getParentWolGroupsRecursively($group));
		return $group;
	}
	public function createWolGroup($name, $parentGroupId=null) {
		if($parentGroupId == null) {
			$this->checkPermission(new Models\WolGroup(), PermissionManager::METHOD_CREATE);
		} else {
			$group = $this->db->selectWolGroup($parentGroupId);
			if(empty($group)) throw new NotFoundException();
			$this->checkPermission($group, PermissionManager::METHOD_CREATE, true, $this->getParentWolGroupsRecursively($group));
		}

		if(empty(trim($name))) {
			throw new InvalidRequestException(LANG('name_cannot_be_empty'));
		}
		$insertId = $this->db->insertWolGroup($name, $parentGroupId);
		if(!$insertId) throw new Exception(LANG('unknown_error'));
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $insertId, 'oco.wol_group.create', ['name'=>$name, 'parent_wol_group_id'=>$parentGroupId]);
		return $insertId;
	}
	public function renameWolGroup($id, $newName) {
		$group = $this->db->selectWolGroup($id);
		if(empty($group)) throw new NotFoundException();
		$this->checkPermission($group, PermissionManager::METHOD_WRITE, true, $this->getParentWolGroupsRecursively($group));

		if(empty(trim($newName))) {
			throw new InvalidRequestException(LANG('name_cannot_be_empty'));
		}
		$this->db->updateWolGroup($group->id, $newName, $group->parent_wol_group_id);
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $group->id, 'oco.wol_group.update', ['name'=>$newName]);
	}
	public function removeWolGroup($id, $force=false) {
		$group = $this->db->selectWolGroup($id);
		if(empty($group)) throw new NotFoundException();
		$this->checkPermission($group, PermissionManager::METHOD_DELETE, true, $this->getParentWolGroupsRecursively($group));

		if(!$force) {
			$subgroups = $this->db->selectAllWolGroupByParentWolGroupId($id);
			if(count($subgroups) > 0) throw new InvalidRequestException(LANG('delete_failed_subgroups'));
		}
		$result = $this->db->deleteWolGroup($id);
		if(!$result) throw new Exception(LANG('unknown_error'));
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $group->id, 'oco.wol_group.delete', []);
		return $result;
	}
	public function getParentWolGroupsRecursively($groupRessource) {
		if(!$groupRessource instanceof Models\WolGroup) {
			$groupRessource = $this->db->selectWolGroup($groupRessource);
		}
		$parentGroups = [$groupRessource];
		while($groupRessource->parent_wol_group_id != null) {
			$parentGroup = $this->db->selectWolGroup($groupRessource->parent_wol_group_id);
			$parentGroups[] = $parentGroup;
			$groupRessource = $parentGroup;
		}
		return $parentGroups;
	}

	public function getWolSchedules($groupId=null) {
		$schedulesFiltered = [];
		foreach($this->db->selectAllWolScheduleByWolGroupId($groupId) as $schedule) {
			if($this->checkPermission($schedule, PermissionManager::METHOD_READ, false, $this->getParentWolGroupsRecursively($schedule->wol_group_id)))
				$schedulesFiltered[] = $schedule;
		}
		return $schedulesFiltered;
	}
	public function getWolSchedule($id) {
		$schedule = $this->db->selectWolSchedule($id);
		if(empty($schedule)) throw new NotFoundException();
		$this->checkPermission($schedule, PermissionManager::METHOD_READ, true, $this->getParentWolGroupsRecursively($schedule->wol_group_id));
		return $schedule;
	}
	public function createWolSchedule($wol_group_id, $name, $monday, $tuesday, $wednesday, $thursday, $friday, $saturday, $sunday) {
		$this->checkPermission(new Models\WolSchedule(), PermissionManager::METHOD_CREATE);
		$group = $this->getWolGroup($wol_group_id);
		$this->checkPermission($group, PermissionManager::METHOD_WRITE, true, $this->getParentWolGroupsRecursively($group));

		if(empty($name)) {
			throw new InvalidRequestException(LANG('name_cannot_be_empty'));
		}

		$insertId = $this->db->insertWolSchedule($wol_group_id, $name, $monday, $tuesday, $wednesday, $thursday, $friday, $saturday, $sunday);
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $insertId, 'oco.wol_schedule.create', [
			'wol_group_id'=>$wol_group_id,
			'name'=>$name,
			'monday'=>$monday,
			'tuesday'=>$tuesday,
			'wednesday'=>$wednesday,
			'thursday'=>$thursday,
			'friday'=>$friday,
			'saturday'=>$saturday,
			'sunday'=>$sunday,
		]);
	}
	public function editWolSchedule($id, $wol_group_id, $name, $monday, $tuesday, $wednesday, $thursday, $friday, $saturday, $sunday) {
		$schedule = $this->db->selectWolSchedule($id);
		if(empty($schedule)) throw new NotFoundException();
		$this->checkPermission($schedule, PermissionManager::METHOD_WRITE, true, $this->getParentWolGroupsRecursively($schedule->wol_group_id));
		$group = $this->getWolGroup($wol_group_id);
		$this->checkPermission($group, PermissionManager::METHOD_WRITE, true, $this->getParentWolGroupsRecursively($group));

		if(empty($name)) {
			throw new InvalidRequestException(LANG('name_cannot_be_empty'));
		}

		$this->db->updateWolSchedule($id, $wol_group_id, $name, $monday, $tuesday, $wednesday, $thursday, $friday, $saturday, $sunday);
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $schedule->id, 'oco.wol_schedule.update', [
			'wol_group_id'=>$wol_group_id,
			'name'=>$name,
			'monday'=>$monday,
			'tuesday'=>$tuesday,
			'wednesday'=>$wednesday,
			'thursday'=>$thursday,
			'friday'=>$friday,
			'saturday'=>$saturday,
			'sunday'=>$sunday,
		]);
	}
	public function removeWolSchedule($id) {
		$schedule = $this->db->selectWolSchedule($id);
		if(empty($schedule)) throw new NotFoundException();
		$this->checkPermission($schedule, PermissionManager::METHOD_DELETE, true, $this->getParentWolGroupsRecursively($schedule->wol_group_id));

		foreach($this->db->selectAllWolPlanByWolGroupId() as $plan) {
			if($plan->wol_schedule_id === $schedule->id)
				throw new InvalidRequestException(LANG('delete_failed_schedule_still_in_use'));
		}

		$result = $this->db->deleteWolSchedule($schedule->id);
		if(!$result) throw new Exception(LANG('unknown_error'));
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $schedule->id, 'oco.wol_schedule.delete', json_encode($schedule));
		return $result;
	}

	public function getWolPlans($groupId=null) {
		$plansFiltered = [];
		foreach($this->db->selectAllWolPlanByWolGroupId($groupId) as $plan) {
			if($this->checkPermission($plan, PermissionManager::METHOD_READ, false, $this->getParentWolGroupsRecursively($plan->wol_group_id)))
				$plansFiltered[] = $plan;
		}
		return $plansFiltered;
	}
	public function getWolPlan($id) {
		$plan = $this->db->selectWolPlan($id);
		if(empty($plan)) throw new NotFoundException();
		$this->checkPermission($plan, PermissionManager::METHOD_READ, true, $this->getParentWolGroupsRecursively($plan->wol_group_id));
		return $plan;
	}
	public function createWolPlan($wol_group_id, $computer_group_id, $wol_schedule_id, $shutdown_credential, $start_time, $end_time, $description) {
		$this->checkPermission(new Models\WolPlan(), PermissionManager::METHOD_CREATE);
		$this->checkPermission($this->getComputerGroup($computer_group_id), PermissionManager::METHOD_READ /* recursive folder check already implemented */);
		$schedule = $this->getWolSchedule($wol_schedule_id);
		$this->checkPermission($schedule, PermissionManager::METHOD_READ, true, $this->getParentWolGroupsRecursively($schedule->wol_group_id));
		$group = $this->getWolGroup($wol_group_id);
		$this->checkPermission($group, PermissionManager::METHOD_WRITE, true, $this->getParentWolGroupsRecursively($group));

		if(empty($start_time)) {
			$start_time = null;
		} else {
			$start_time_object = DateTime::createFromFormat('Y-m-d H:i:s', $start_time);
			if($start_time_object === false) {
				throw new InvalidRequestException(LANG('date_parse_error'));
			}
			if($start_time_object < new DateTime()) {
				$start_time = null;
			}
		}
		if($start_time == null) foreach($this->db->selectAllWolPlanByWolGroupId() as $p) {
			if($p->computer_group_id == $computer_group_id && $p->start_time == null) {
				throw new InvalidRequestException(LANG('active_schedule_already_exists_for_this_computer_group'));
			}
		}

		if(empty($end_time)) {
			$end_time = null;
		} else {
			$end_time_object = DateTime::createFromFormat('Y-m-d H:i:s', $end_time);
			if($end_time_object === false) {
				throw new InvalidRequestException(LANG('date_parse_error'));
			}
			if($end_time_object < new DateTime()) {
				throw new InvalidRequestException(LANG('date_is_in_the_past'));
			}
		}

		$insertId = $this->db->insertWolPlan($wol_group_id, $computer_group_id, $wol_schedule_id, $shutdown_credential, $start_time, $end_time, $description);
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $insertId, 'oco.wol_plan.create', [
			'wol_group_id'=>$wol_group_id,
			'computer_group_id'=>$computer_group_id,
			'wol_schedule_id'=>$wol_schedule_id,
			'shutdown_credential'=>$shutdown_credential,
			'start_time'=>$start_time,
			'end_time'=>$end_time,
			'description'=>$description,
		]);
	}
	public function editWolPlan($id, $wol_group_id, $computer_group_id, $wol_schedule_id, $shutdown_credential, $start_time, $end_time, $description) {
		$plan = $this->db->selectWolPlan($id);
		if(empty($plan)) throw new NotFoundException();
		$this->checkPermission($plan, PermissionManager::METHOD_WRITE, true, $this->getParentWolGroupsRecursively($plan->wol_group_id));
		$this->checkPermission($this->getComputerGroup($computer_group_id), PermissionManager::METHOD_READ /* recursive folder check already implemented */);
		$schedule = $this->getWolSchedule($wol_schedule_id);
		$this->checkPermission($schedule, PermissionManager::METHOD_READ, true, $this->getParentWolGroupsRecursively($schedule->wol_group_id));
		$group = $this->getWolGroup($wol_group_id);
		$this->checkPermission($group, PermissionManager::METHOD_WRITE, true, $this->getParentWolGroupsRecursively($group));

		if(empty($start_time)) {
			$start_time = null;
		} else {
			$start_time_object = DateTime::createFromFormat('Y-m-d H:i:s', $start_time);
			if($start_time_object === false) {
				throw new InvalidRequestException(LANG('date_parse_error'));
			}
			if($start_time_object < new DateTime()) {
				$start_time = null;
			}
		}
		if($start_time == null) foreach($this->db->selectAllWolPlanByWolGroupId() as $p) {
			if($plan->id != $p->id && $p->computer_group_id == $computer_group_id && $p->start_time == null) {
				throw new InvalidRequestException(LANG('active_schedule_already_exists_for_this_computer_group'));
			}
		}

		if(empty($end_time)) {
			$end_time = null;
		} else {
			$end_time_object = DateTime::createFromFormat('Y-m-d H:i:s', $end_time);
			if($end_time_object === false) {
				throw new InvalidRequestException(LANG('date_parse_error'));
			}
			if($end_time_object < new DateTime()) {
				throw new InvalidRequestException(LANG('date_is_in_the_past'));
			}
		}

		$this->db->updateWolPlan($id, $wol_group_id, $computer_group_id, $wol_schedule_id, $shutdown_credential, $start_time, $end_time, $description);
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $plan->id, 'oco.wol_plan.update', [
			'wol_group_id'=>$wol_group_id,
			'computer_group_id'=>$computer_group_id,
			'wol_schedule_id'=>$wol_schedule_id,
			'shutdown_credential'=>$shutdown_credential,
			'start_time'=>$start_time,
			'end_time'=>$end_time,
			'description'=>$description,
		]);
	}
	public function removeWolPlan($id) {
		$plan = $this->db->selectWolPlan($id);
		if(empty($plan)) throw new NotFoundException();
		$this->checkPermission($plan, PermissionManager::METHOD_DELETE, true, $this->getParentWolGroupsRecursively($plan->wol_group_id));

		$result = $this->db->deleteWolPlan($plan->id);
		if(!$result) throw new Exception(LANG('unknown_error'));
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $plan->id, 'oco.wol_plan.delete', json_encode($plan));
		return $result;
	}

}
