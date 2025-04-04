<?php

namespace Paketeer\Software;

class Windows11_24H2 extends MicrosoftUpdateCatalog {

	function getDisplayName() {
		return 'Windows 11 24H2 Updates';
	}

	function getVersions() {
		return $this->getUpdates(
			'Cumulative Update for Windows 11 Version 24H2',
			['Windows 11', '24H2']
		);
	}

}
