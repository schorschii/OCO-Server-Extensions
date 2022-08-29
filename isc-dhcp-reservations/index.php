<?php
return [
	'id' => 'isc-dhcp-reservations',
	'name' => 'ISC-DHCP-Server Reservation Editor',
	'version' => '1.3',
	'author' => 'Schorschii',
	'oco-version-min' => '0.14.0',
	'oco-version-max' => '1.99.99',

	'autoload' => __DIR__.'/lib',

	'frontend-tree' => __DIR__.'/frontend/views/isc-dhcp-reservations.tree.php',
	'frontend-views' => [
		'isc-dhcp-reservations.php' => __DIR__.'/frontend/views/isc-dhcp-reservations.php',
	],
	'frontend-ajax-handler' => [
		'isc-dhcp-reservations.php' => __DIR__.'/frontend/ajax-handler/isc-dhcp-reservations.php',
	],
	'frontend-js' => [
		'isc-dhcp-reservations.js' => __DIR__.'/frontend/js/isc-dhcp-reservations.js'
	],
	'frontend-css' => [
		'isc-dhcp-reservations.css' => __DIR__.'/frontend/css/isc-dhcp-reservations.css'
	],
	'frontend-img' => [
		'dhcp.dyn.svg' => __DIR__.'/frontend/img/dhcp.dyn.svg',
	],

	'translation-dir' => __DIR__.'/lang',
];
