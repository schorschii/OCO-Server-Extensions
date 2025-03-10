<?php

namespace Models;

class WolGroup extends HierarchicalGroup {

	protected const GET_OBJECT_FUNCTION = 'selectWolGroup';

	public $id;
	public $parent_wol_group_id;
	public $name;


	public function __construct($db=null) {
		parent::__construct($db);
	}

	public function getParentId() {
		return $this->parent_wol_group_id;
	}

}
