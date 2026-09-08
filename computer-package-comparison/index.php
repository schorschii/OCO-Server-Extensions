<?php
return [
	'id' => 'computer-package-comparison',
	'name' => 'Computer Package Comparison',
	'version' => '0.1.1',
	'author' => 'sunlover83',
	'oco-version-min' => '1.2.2',
	'oco-version-max' => '1.2.99',

	'autoload' => __DIR__.'/lib',

	'frontend-views' => [
		'computer-package-comparison.php' => __DIR__.'/frontend/views/computer-package-comparison.php',
	],
	'frontend-js' => [
		'computer-package-comparison.js' => __DIR__.'/frontend/js/computer-package-comparison.js',
	],
	'frontend-css' => [
		'computer-package-comparison.css' => __DIR__.'/frontend/css/computer-package-comparison.css',
	],

	'translation-dir' => __DIR__.'/lang',
];
