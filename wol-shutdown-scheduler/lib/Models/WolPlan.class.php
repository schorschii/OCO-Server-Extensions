<?php

namespace Models;

class WolPlan {

	public $id;
	public $wol_group_id;
	public $computer_group_id;
	public $wol_schedule_id;
	public $start_time;
	public $end_time;
	public $description;

	// joined attributes
	public $wol_schedule_name;

}
