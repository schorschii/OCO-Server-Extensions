<?php
return [
	'id' => 'wol-shutdown-scheduler',
	'name' => 'WOL/Shutdown Scheduler',
	'version' => '1.2',
	'author' => 'Schorschii',
	'oco-version-min' => '0.15.3',
	'oco-version-max' => '1.99.99',

	'autoload' => __DIR__.'/lib',

	'frontend-tree' => __DIR__.'/frontend/views/wol-shutdown-scheduler.tree.php',
	'frontend-views' => [
		'wol-shutdown-scheduler.php' => __DIR__.'/frontend/views/wol-shutdown-scheduler.php',
		'dialog-wol-schedule-edit.php' => __DIR__.'/frontend/views/dialog-wol-schedule-edit.php',
		'dialog-wol-plan-edit.php' => __DIR__.'/frontend/views/dialog-wol-plan-edit.php',
	],
	'frontend-ajax-handler' => [
		'wol-shutdown-scheduler.php' => __DIR__.'/frontend/ajax-handler/wol-shutdown-scheduler.php',
	],
	'frontend-js' => [
		'wol-shutdown-scheduler.js' => __DIR__.'/frontend/js/wol-shutdown-scheduler.js'
	],
	'frontend-css' => [
		'wol-shutdown-scheduler.css' => __DIR__.'/frontend/css/wol-shutdown-scheduler.css'
	],
	'frontend-img' => [
		'scheduler.dyn.svg' => __DIR__.'/frontend/img/scheduler.dyn.svg',
	],

	'translation-dir' => __DIR__.'/lang',

	'housekeeping-function' => 'WolShutdownCoreLogic::updateWolPlans',

	'console-methods' => [
		'execplannedwolshutdown' => 'WolShutdownCoreLogic::executeWolShutdown',
	],

	'agent-response-filter' => 'WolShutdownCoreLogic::injectComputerShutdownInAgentRespone',
];
