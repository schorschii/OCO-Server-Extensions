<?php

namespace Models;

class WolGroup implements IHierarchicalGroup {

	public $id;
	public $parent_wol_group_id;
	public $name;

	public function getParentId() {
		return $this->parent_wol_group_id;
	}

}
