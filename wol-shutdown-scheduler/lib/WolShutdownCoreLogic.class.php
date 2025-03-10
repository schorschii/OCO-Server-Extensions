<?php

class WolShutdownCoreLogic extends CoreLogic {

	const AGENT_SHUTDOWN_TIME_FRAME = 60*5; /*5 minutes*/

	function __construct($db, $systemUser=null) {
		parent::__construct(new WolShutdownDatabaseController(), $systemUser);
	}

	public static function updateWolPlans() {
		$woldb = new WolShutdownDatabaseController();
		// delete expired schedules
		foreach($woldb->selectAllWolPlanByWolGroupId() as $plan) {
			if(!empty($plan->end_time) && strtotime($plan->end_time) < time()) {
				echo "  delete $plan->end_time expired plan #$plan->id (schedule $plan->wol_schedule_name)\n";
				$woldb->deleteWolPlan($plan->id);
			}
		}
		// delete expired shutdown flags
		$woldb->deleteExpiredWolShutdownFlag();
	}

	public static function executeWolShutdown($db) {
		$currentTime = date('H:i');
		$woldb = new WolShutdownDatabaseController();
		foreach($woldb->selectAllWolPlanByWolGroupId() as $plan) {
			// execute all active plans
			if(!empty($plan->start_time) && strtotime($plan->start_time) > time()) continue;
			if(!empty($plan->end_time) && strtotime($plan->end_time) < time()) continue;

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
				self::executeShutdown($woldb, $plan->computer_group_id);
				echo "\n";
			}
		}
	}
	private static function executeWol($woldb, $computer_group_id) {
		$actions = [];
		$wolMacAdresses = [];
		foreach($woldb->selectAllComputerByComputerGroupId($computer_group_id) as $c) {
			$address = $c->remote_address ?? $c->hostname;
			$identifier = $c->id.';'.$c->hostname.';'.$address;
			$computerMacs = [];
			foreach($woldb->selectAllComputerNetworkByComputerId($c->id) as $n) {
				if(empty($n->mac) || filter_var($n->mac, FILTER_VALIDATE_MAC) === false) continue;
				$wolMacAdresses[] = $n->mac;
				$computerMacs[] = $n->mac;
			}
			$actions[$identifier] = $computerMacs;
		}
		$woldb->insertLogEntry(Models\Log::LEVEL_INFO, 'WOL-SHUTDOWN-SCHEDULER', null, 'oco.wol_shutdown_scheduler.wol', $actions);
		$wolController = new WakeOnLan($woldb);
		$wolController->wol($wolMacAdresses, true);
	}
	private static function executeShutdown($woldb, $computer_group_id) {
		$actions = [];
		foreach($woldb->selectAllComputerByComputerGroupId($computer_group_id) as $c) {
			$address = $c->remote_address ?? $c->hostname;
			$identifier = $c->id.';'.$c->hostname.';'.$address;
			// set shutdown flag for oco agent
			// we have to limit this for a specific time window in case of failure,
			// so that the computer will not directly be shutted down tomorrow
			$until = date('Y-m-d H:i:s', time() + self::AGENT_SHUTDOWN_TIME_FRAME);
			$woldb->insertWolShutdownFlag($c->id, $until);
			$actions[$identifier] = 'INFO: shutdown flag set for agent until '.$until;
			continue;
		}
		$woldb->insertLogEntry(Models\Log::LEVEL_INFO, 'WOL-SHUTDOWN-SCHEDULER', null, 'oco.wol_shutdown_scheduler.shutdown', $actions);
	}

	public static function injectComputerShutdownInAgentRespone($resdata, Models\Computer $computer) {
		// only modify agent_hello responses
		if(!isset($resdata['result']['params']['software-jobs'])
		|| !is_array($resdata['result']['params']['software-jobs'])) return $resdata;

		if(empty($computer)) return $resdata;
		$woldb = new WolShutdownDatabaseController();
		$flag = $woldb->selectActiveWolShutdownFlagByComputerId($computer->id);
		if(!empty($flag)) {
			// inject fake job with shutdown flag
			$resdata['result']['params']['software-jobs'][] = [
				'id' => -1,
				'container-id' => -1,
				'package-id' => -1,
				'download' => false,
				'procedure' => 'echo Planned Shutdown...',
				'sequence-mode' => 0,
				'restart' => null,
				'shutdown' => 0,
				'exit' => null,
			];
		}
		return $resdata;
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
	public function createWolPlan($wol_group_id, $computer_group_id, $wol_schedule_id, $start_time, $end_time, $description) {
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

		$insertId = $this->db->insertWolPlan($wol_group_id, $computer_group_id, $wol_schedule_id, $start_time, $end_time, $description);
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $insertId, 'oco.wol_plan.create', [
			'wol_group_id'=>$wol_group_id,
			'computer_group_id'=>$computer_group_id,
			'wol_schedule_id'=>$wol_schedule_id,
			'start_time'=>$start_time,
			'end_time'=>$end_time,
			'description'=>$description,
		]);
	}
	public function editWolPlan($id, $wol_group_id, $computer_group_id, $wol_schedule_id, $start_time, $end_time, $description) {
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

		$this->db->updateWolPlan($id, $wol_group_id, $computer_group_id, $wol_schedule_id, $start_time, $end_time, $description);
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $plan->id, 'oco.wol_plan.update', [
			'wol_group_id'=>$wol_group_id,
			'computer_group_id'=>$computer_group_id,
			'wol_schedule_id'=>$wol_schedule_id,
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
