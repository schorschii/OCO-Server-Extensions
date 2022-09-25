<?php
return [
	'id' => 'laps-launcher',
	'name' => 'Local Administrator Password Solution Launcher',
	'version' => '1.0',
	'author' => 'Schorschii',
	'oco-version-min' => '0.14.0',
	'oco-version-max' => '1.99.99',

	'computer-commands' => [
		['icon'=>'img/laps.png', 'name'=>'LAPS', 'description'=>'Local Administrator Password Solution', 'command'=>'laps://$$TARGET$$', 'new_tab'=>false]
	],
	'frontend-img' => [
		'laps.png' => __DIR__.'/frontend/img/laps.png',
	],
];
