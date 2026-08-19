<?php

use PHPUnit\Framework\TestCase;
use ComputerPackageComparison\ComputerPackageComparator;

require_once __DIR__.'/../lib/ComputerPackageComparison/ComputerPackageComparator.class.php';

class ComputerPackageComparatorTest extends TestCase {

	private function package($packageId, $familyId, $name, $version) {
		$package = new stdClass();
		$package->package_id = $packageId;
		$package->package_family_id = $familyId;
		$package->package_family_name = $name;
		$package->package_version = $version;
		return $package;
	}

	public function testClassifiesIdenticalMissingAndDifferentVersions(): void {
		$source = [
			$this->package(11, 1, 'Browser', '1.0'),
			$this->package(21, 2, 'Editor', '2.0'),
			$this->package(31, 3, 'VPN', '3.0'),
		];
		$target = [
			$this->package(11, 1, 'Browser', '1.0'),
			$this->package(22, 2, 'Editor', '2.1'),
		];

		$result = ComputerPackageComparator::compare($source, $target);

		$this->assertSame([
			ComputerPackageComparator::STATUS_MISSING,
			ComputerPackageComparator::STATUS_DIFFERENT,
			ComputerPackageComparator::STATUS_IDENTICAL,
		], array_column($result, 'status'));
		$this->assertSame(31, $result[0]['source_package']->package_id);
		$this->assertSame(22, $result[1]['target_packages'][0]->package_id);
	}

	public function testAddsPackagesWhichOnlyExistOnTarget(): void {
		$result = ComputerPackageComparator::compare(
			[$this->package(11, 1, 'Browser', '1.0')],
			[
				$this->package(11, 1, 'Browser', '1.0'),
				$this->package(41, 4, 'Office', '4.0'),
			]
		);

		$this->assertCount(2, $result);
		$this->assertSame(ComputerPackageComparator::STATUS_TARGET_ONLY, $result[1]['status']);
		$this->assertNull($result[1]['source_package']);
		$this->assertSame(41, $result[1]['target_packages'][0]->package_id);
	}

	public function testIdenticalPackageStillShowsAdditionalTargetVersion(): void {
		$result = ComputerPackageComparator::compare(
			[$this->package(11, 1, 'Browser', '1.0')],
			[
				$this->package(12, 1, 'Browser', '1.1'),
				$this->package(11, 1, 'Browser', '1.0'),
			]
		);

		$this->assertSame(ComputerPackageComparator::STATUS_IDENTICAL, $result[0]['status']);
		$this->assertSame([11, 12], array_map(function($package) {
			return $package->package_id;
		}, $result[0]['target_packages']));
	}

	public function testKeepsEachMissingSourcePackageAsReference(): void {
		$result = ComputerPackageComparator::compare([
			$this->package(51, 5, 'Runtime', '5.0'),
			$this->package(52, 5, 'Runtime', '5.1'),
		], []);

		$this->assertCount(2, $result);
		$this->assertSame([51, 52], array_map(function($row) {
			return $row['source_package']->package_id;
		}, $result));
	}

	public function testSortsNaturallyWithinAStatus(): void {
		$result = ComputerPackageComparator::compare([
			$this->package(62, 6, 'Tool 10', '1.0'),
			$this->package(61, 7, 'Tool 2', '1.0'),
		], []);

		$this->assertSame(['Tool 2', 'Tool 10'], array_map(function($row) {
			return $row['source_package']->package_family_name;
		}, $result));
	}

	public function testSortsAvailableVersionsByCreationDate(): void {
		$older = new stdClass();
		$older->id = 71;
		$older->version = '9.0';
		$older->created = '2026-01-01 10:00:00';
		$newer = new stdClass();
		$newer->id = 72;
		$newer->version = '1.0';
		$newer->created = '2026-02-01 10:00:00';

		$result = ComputerPackageComparator::sortNewestFirst([$older, $newer]);

		$this->assertSame([72, 71], array_map(function($package) {
			return $package->id;
		}, $result));
	}
}
