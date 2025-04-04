<?php
return [
	'id' => 'paketeer',
	'name' => 'Paketeer',
	'version' => '0.1',
	'author' => 'Schorschii',
	'oco-version-min' => '1.1.7',
	'oco-version-max' => '1.99.99',

	'autoload' => __DIR__.'/lib',

	'frontend-tree' => __DIR__.'/frontend/views/paketeer.tree.php',
	'frontend-views' => [
		'paketeer.php' => __DIR__.'/frontend/views/paketeer.php',
		'dialog-paketeer-create.php' => __DIR__.'/frontend/views/dialog-paketeer-create.php',
	],
	'frontend-ajax-handler' => [
		'paketeer.php' => __DIR__.'/frontend/ajax-handler/paketeer.php',
	],
	'frontend-js' => [
		'paketeer.js' => __DIR__.'/frontend/js/paketeer.js'
	],
	'frontend-img' => [
		'paketeer.dyn.svg' => __DIR__.'/frontend/img/paketeer.dyn.svg',
	],

	'translation-dir' => __DIR__.'/lang',
];
