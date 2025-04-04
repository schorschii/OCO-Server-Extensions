<?php

namespace Paketeer\Software;

class Windows10_22H2 extends MicrosoftUpdateCatalog {

	function getDisplayName() {
		return 'Windows 10 22H2 Updates';
	}

	function getVersions() {
		return $this->getUpdates(
			'Cumulative Update for Windows 10 Version 22H2',
			['Windows 10', '22H2']
		);
	}

}
