<?php
return [
	'id' => 'install-tool',
	'name' => 'Install-Tool',
	'version' => 'public-1.2',
	'author' => 'Schorschii',
	'oco-version-min' => '1.0.2',
	'oco-version-max' => '1.99.99',

	'autoload' => __DIR__.'/lib',

	'frontend-tree' => __DIR__.'/frontend/views/install-tool.tree.php',
	'frontend-views' => [
		'install-tool.php' => __DIR__.'/frontend/views/install-tool.php',
	],
	'frontend-ajax-handler' => [
		'install-tool.php' => __DIR__.'/frontend/ajax-handler/install-tool.php',
	],
	'frontend-js' => [
		'install-tool.js' => __DIR__.'/frontend/js/install-tool.js'
	],
	'frontend-img' => [
		'install-tool.dyn.svg' => __DIR__.'/frontend/img/install-tool.dyn.svg',
		'automagical.png' => __DIR__.'/frontend/img/automagical.png',
		'installation-remove.png' => __DIR__.'/frontend/img/installation-remove.png',
	],
];
