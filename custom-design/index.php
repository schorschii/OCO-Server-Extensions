<?php
return [
	'id' => 'custom-design',
	'name' => 'My Custom OCO Webdesign',
	'version' => '1.0',
	'author' => 'Schorschii',
	'oco-version-min' => '0.14.0',
	'oco-version-max' => '1.99.99',

	'frontend-js' => [
		// e.g. 'myjs.js' => __DIR__.'/frontend/js/myjs.js'
	],
	'frontend-css' => [
		'custom.css' => __DIR__.'/frontend/css/custom.css'
	],
	'frontend-img' => [ // frontend img dir is shared with self-service
		'custom-frontend-bg.jpg' => __DIR__.'/frontend/img/custom-frontend-bg.jpg',
		'custom-self-service-bg.jpg' => __DIR__.'/self-service/img/custom-self-service-bg.jpg',
	],

	'self-service-js' => [
		// e.g. 'myjs.js' => __DIR__.'/self-service/js/myjs.js'
	],
	'self-service-css' => [
		'custom.css' => __DIR__.'/self-service/css/custom.css'
	],
];
