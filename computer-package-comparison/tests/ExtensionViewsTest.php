<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../lib/ComputerPackageComparison/ComputerPackageComparator.class.php';

if(!function_exists('LANG')) {
	function LANG($key, $flags=null) {
		return $key;
	}
}

if(!class_exists('PermissionManager')) {
	class PermissionManager {
		const METHOD_READ = 'read';
		const METHOD_DEPLOY = 'deploy';
	}
}
if(!class_exists('NotFoundException')) {
	class NotFoundException extends Exception {}
}
if(!class_exists('PermissionException')) {
	class PermissionException extends Exception {}
}
if(!class_exists('InvalidRequestException')) {
	class InvalidRequestException extends Exception {}
}
if(!class_exists('Html')) {
	class Html {
		public static function explorerLink($url) {
			return "href='".htmlspecialchars($url, ENT_QUOTES)."'";
		}
	}
}

class ComputerPackageComparisonTestComputer {
	public $id;
	public $hostname;
	public $denyRead = false;

	public function __construct($id, $hostname, $denyRead=false) {
		$this->id = $id;
		$this->hostname = $hostname;
		$this->denyRead = $denyRead;
	}

	public function getIcon() {
		return 'img/computer.dyn.svg';
	}
}

class ComputerPackageComparisonTestCoreLogic {
	private $computers;

	public function __construct(array $computers) {
		foreach($computers as $computer)
			$this->computers[(string)$computer->id] = $computer;
	}

	public function getComputer($id) {
		if(!isset($this->computers[(string)$id])) throw new NotFoundException();
		$computer = $this->computers[(string)$id];
		if($computer->denyRead) throw new PermissionException();
		return $computer;
	}

	public function checkPermission($object, $method, $throw=true) {
		return !($method === PermissionManager::METHOD_READ && !empty($object->denyRead));
	}
}

class ComputerPackageComparisonTestDatabase {
	private $computers;

	public function __construct(array $computers) {
		$this->computers = $computers;
	}

	public function selectAllComputer() {
		return $this->computers;
	}

	public function selectAllComputerPackageByComputerId($computerId) {
		if((string)$computerId !== '2') return [];
		$package = new stdClass();
		$package->package_id = 101;
		$package->package_family_id = 10;
		$package->package_family_name = 'Browser';
		$package->package_version = '120';
		$package->installed = '2026-08-01 12:00:00';
		return [$package];
	}

	public function selectAllPackageByPackageFamilyId($familyId) {
		$older = new stdClass();
		$older->id = 101;
		$older->version = '120';
		$older->created = '2026-01-01 10:00:00';
		$newer = new stdClass();
		$newer->id = 102;
		$newer->version = '130';
		$newer->created = '2026-08-01 10:00:00';
		return [$older, $newer];
	}
}

class ExtensionViewsTest extends TestCase {

	private function renderView($filename, array $query) {
		$computers = [
			new ComputerPackageComparisonTestComputer(1, 'NEW-PC'),
			new ComputerPackageComparisonTestComputer(2, 'OLD-PC'),
			new ComputerPackageComparisonTestComputer(3, 'HIDDEN-PC', true),
		];
		$db = new ComputerPackageComparisonTestDatabase($computers);
		$cl = new ComputerPackageComparisonTestCoreLogic($computers);
		$_GET = $query;
		ob_start();
		include __DIR__.'/../frontend/views/'.$filename;
		return ob_get_clean();
	}

	public function testComputerSelectionUsesOcoSelectionPartialInSingleSelectionMode(): void {
		$view = file_get_contents(__DIR__.'/../frontend/views/dialog-computer-package-comparison-select.php');

		$this->assertStringContainsString('$SINGLE_SELECTION = 1;', $view);
		$this->assertStringContainsString("frontend/views/partial/computer-selection.php", $view);
		$this->assertStringContainsString("class='gallery computerSelection'", $view);
	}

	public function testComparisonViewOffersEveryVersionWithNewestSelected(): void {
		$html = $this->renderView('computer-package-comparison.php', [
			'target_computer_id' => 1,
			'source_computer_id' => 2,
		]);

		$this->assertStringContainsString("data-status='missing'", $html);
		$this->assertStringContainsString("value='101'", $html);
		$this->assertStringContainsString("value='102'", $html);
		$this->assertStringContainsString('130 — newest', $html);
		$this->assertMatchesRegularExpression("/value='102'[^>]*selected/", $html);
		$this->assertStringContainsString('cpc_deploy_selected_packages', $html);
		$this->assertMatchesRegularExpression("/cpc_this_computer.*NEW-PC.*cpc_comparison_computer.*OLD-PC/s", $html);
		$this->assertStringContainsString("class='oco-package-comparison-computer-button'", $html);
		$this->assertMatchesRegularExpression("/<th>NEW-PC<\\/th>.*<th>OLD-PC<\\/th>/s", $html);
	}
}
