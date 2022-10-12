<?php

class WolShutdownDatabaseController extends DatabaseController {

	private $stmt;

	function __construct() {
		parent::__construct();
	}

	public function selectAllWolGroupByParentWolGroupId($parent_wol_group_id=null) {
		if($parent_wol_group_id === null) {
			$this->stmt = $this->dbh->prepare(
				'SELECT * FROM ext_wol_group WHERE parent_wol_group_id IS NULL ORDER BY name ASC'
			);
			$this->stmt->execute();
		} else {
			$this->stmt = $this->dbh->prepare(
				'SELECT * FROM ext_wol_group WHERE parent_wol_group_id = :parent_wol_group_id ORDER BY name ASC'
			);
			$this->stmt->execute([':parent_wol_group_id' => $parent_wol_group_id]);
		}
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\WolGroup');
	}
	public function selectWolGroup($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM ext_wol_group WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\WolGroup') as $row) {
			return $row;
		}
	}
	public function insertWolGroup($name, $parent_wol_group_id=null) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO ext_wol_group (parent_wol_group_id, name)
			VALUES (:parent_wol_group_id, :name)'
		);
		$this->stmt->execute([
			':parent_wol_group_id' => $parent_wol_group_id,
			':name' => $name,
		]);
		return $this->dbh->lastInsertId();
	}
	public function updateWolGroup($id, $name, $parent_wol_group_id) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE ext_wol_group
			SET parent_wol_group_id = :parent_wol_group_id, name = :name
			WHERE id = :id'
		);
		return $this->stmt->execute([
			':id' => $id,
			':parent_wol_group_id' => $parent_wol_group_id,
			':name' => $name,
		]);
	}
	public function deleteWolGroup($id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM ext_wol_group WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		return ($this->stmt->rowCount() == 1);
	}

	public function selectAllWolScheduleByWolGroupId($wol_group_id=null) {
		if($wol_group_id === null) {
			$this->stmt = $this->dbh->prepare(
				'SELECT * FROM ext_wol_schedule ORDER BY name ASC'
			);
			$this->stmt->execute();
		} else {
			$this->stmt = $this->dbh->prepare(
				'SELECT * FROM ext_wol_schedule WHERE wol_group_id = :wol_group_id ORDER BY name ASC'
			);
			$this->stmt->execute([':wol_group_id' => $wol_group_id]);
		}
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\WolSchedule');
	}
	public function selectWolSchedule($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM ext_wol_schedule WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\WolSchedule') as $row) {
			return $row;
		}
	}
	public function insertWolSchedule($wol_group_id, $name, $monday, $tuesday, $wednesday, $thursday, $friday, $saturday, $sunday) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO ext_wol_schedule (wol_group_id, name, monday, tuesday, wednesday, thursday, friday, saturday, sunday)
			VALUES (:wol_group_id, :name, :monday, :tuesday, :wednesday, :thursday, :friday, :saturday, :sunday)'
		);
		$this->stmt->execute([
			':wol_group_id' => $wol_group_id,
			':name' => $name,
			':monday' => $monday,
			':tuesday' => $tuesday,
			':wednesday' => $wednesday,
			':thursday' => $thursday,
			':friday' => $friday,
			':saturday' => $saturday,
			':sunday' => $sunday,
		]);
		return $this->dbh->lastInsertId();
	}
	public function updateWolSchedule($id, $wol_group_id, $name, $monday, $tuesday, $wednesday, $thursday, $friday, $saturday, $sunday) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE ext_wol_schedule
			SET wol_group_id = :wol_group_id, name = :name, monday = :monday, tuesday = :tuesday, wednesday = :wednesday, thursday = :thursday, friday = :friday, saturday = :saturday, sunday = :sunday
			WHERE id = :id'
		);
		return $this->stmt->execute([
			':id' => $id,
			':wol_group_id' => $wol_group_id,
			':name' => $name,
			':monday' => $monday,
			':tuesday' => $tuesday,
			':wednesday' => $wednesday,
			':thursday' => $thursday,
			':friday' => $friday,
			':saturday' => $saturday,
			':sunday' => $sunday,
		]);
	}
	public function deleteWolSchedule($id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM ext_wol_schedule WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		return ($this->stmt->rowCount() == 1);
	}

	public function selectAllWolPlanByWolGroupId($wol_group_id=null) {
		if($wol_group_id === null) {
			$this->stmt = $this->dbh->prepare(
				'SELECT wp.*, ws.name AS "wol_schedule_name" FROM ext_wol_plan wp INNER JOIN ext_wol_schedule ws ON wp.wol_schedule_id = ws.id ORDER BY start_time ASC'
			);
			$this->stmt->execute();
		} else {
			$this->stmt = $this->dbh->prepare(
				'SELECT wp.*, ws.name AS "wol_schedule_name" FROM ext_wol_plan wp INNER JOIN ext_wol_schedule ws ON wp.wol_schedule_id = ws.id WHERE wp.wol_group_id = :wol_group_id ORDER BY start_time ASC'
			);
			$this->stmt->execute([':wol_group_id' => $wol_group_id]);
		}
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\WolPlan');
	}
	public function selectWolPlan($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT wp.*, ws.name AS "wol_schedule_name" FROM ext_wol_plan wp INNER JOIN ext_wol_schedule ws ON wp.wol_schedule_id = ws.id WHERE wp.id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\WolPlan') as $row) {
			return $row;
		}
	}
	public function insertWolPlan($wol_group_id, $computer_group_id, $wol_schedule_id, $shutdown_credential, $start_time, $end_time, $description) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO ext_wol_plan (wol_group_id, computer_group_id, wol_schedule_id, shutdown_credential, start_time, end_time, description)
			VALUES (:wol_group_id, :computer_group_id, :wol_schedule_id, :shutdown_credential, :start_time, :end_time, :description)'
		);
		$this->stmt->execute([
			':wol_group_id' => $wol_group_id,
			':computer_group_id' => $computer_group_id,
			':wol_schedule_id' => $wol_schedule_id,
			':shutdown_credential' => $shutdown_credential,
			':start_time' => $start_time,
			':end_time' => $end_time,
			':description' => $description,
		]);
		return $this->dbh->lastInsertId();
	}
	public function updateWolPlan($id, $wol_group_id, $computer_group_id, $wol_schedule_id, $shutdown_credential, $start_time, $end_time, $description) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE ext_wol_plan
			SET wol_group_id = :wol_group_id, computer_group_id = :computer_group_id, wol_schedule_id = :wol_schedule_id, shutdown_credential = :shutdown_credential, start_time = :start_time, end_time = :end_time, description = :description
			WHERE id = :id'
		);
		return $this->stmt->execute([
			':id' => $id,
			':wol_group_id' => $wol_group_id,
			':computer_group_id' => $computer_group_id,
			':wol_schedule_id' => $wol_schedule_id,
			':shutdown_credential' => $shutdown_credential,
			':start_time' => $start_time,
			':end_time' => $end_time,
			':description' => $description,
		]);
	}
	public function deleteWolPlan($id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM ext_wol_plan WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		return ($this->stmt->rowCount() == 1);
	}

}
