<?php

namespace Paketeer\Software;

interface ISoftware {

	function getDisplayName();
	function getVersions();
	function createPackage(\CoreLogic $cl, array $links, string $familyName, string $version, string $notes);

}
