<?php

namespace ComputerPackageComparison;

class ComputerPackageComparator {

	const STATUS_MISSING = 'missing';
	const STATUS_DIFFERENT = 'different';
	const STATUS_IDENTICAL = 'identical';
	const STATUS_TARGET_ONLY = 'target_only';

	/**
	 * Compare centrally managed packages installed on a source computer with
	 * those installed on a target computer.
	 *
	 * Each result contains a status, one source package (or null), and all
	 * matching target packages. Package families are used to detect differing
	 * versions, while the source package remains available as a reference.
	 */
	public static function compare(array $sourcePackages, array $targetPackages) {
		$targetById = [];
		$targetByFamily = [];
		$sourceFamilies = [];

		foreach($targetPackages as $package) {
			$targetById[(string)$package->package_id][] = $package;
			$targetByFamily[(string)$package->package_family_id][] = $package;
		}
		foreach($targetByFamily as &$familyPackages)
			usort($familyPackages, [self::class, 'comparePackages']);
		unset($familyPackages);

		foreach($sourcePackages as $package)
			$sourceFamilies[(string)$package->package_family_id] = true;

		$result = [];
		foreach($sourcePackages as $sourcePackage) {
			$packageId = (string)$sourcePackage->package_id;
			$familyId = (string)$sourcePackage->package_family_id;

			if(isset($targetById[$packageId])) {
				$status = self::STATUS_IDENTICAL;
				$matchingTargetPackages = $targetByFamily[$familyId];
			} elseif(isset($targetByFamily[$familyId])) {
				$status = self::STATUS_DIFFERENT;
				$matchingTargetPackages = $targetByFamily[$familyId];
			} else {
				$status = self::STATUS_MISSING;
				$matchingTargetPackages = [];
			}

			$result[] = [
				'status' => $status,
				'source_package' => $sourcePackage,
				'target_packages' => $matchingTargetPackages,
			];
		}

		foreach($targetPackages as $targetPackage) {
			if(isset($sourceFamilies[(string)$targetPackage->package_family_id])) continue;
			$result[] = [
				'status' => self::STATUS_TARGET_ONLY,
				'source_package' => null,
				'target_packages' => [$targetPackage],
			];
		}

		usort($result, [self::class, 'compareRows']);
		return $result;
	}

	/**
	 * Return package versions with the most recently created OCO package first.
	 * Version strings are intentionally not interpreted because their format is
	 * package-specific.
	 */
	public static function sortNewestFirst(array $packages) {
		usort($packages, function($left, $right) {
			$createdComparison = strcmp((string)$right->created, (string)$left->created);
			if($createdComparison !== 0) return $createdComparison;
			return intval($right->id) <=> intval($left->id);
		});
		return $packages;
	}

	private static function compareRows($left, $right) {
		$priority = [
			self::STATUS_MISSING => 0,
			self::STATUS_DIFFERENT => 1,
			self::STATUS_IDENTICAL => 2,
			self::STATUS_TARGET_ONLY => 3,
		];

		$statusComparison = $priority[$left['status']] <=> $priority[$right['status']];
		if($statusComparison !== 0) return $statusComparison;

		$leftPackage = self::representativePackage($left);
		$rightPackage = self::representativePackage($right);
		$nameComparison = strnatcasecmp((string)$leftPackage->package_family_name, (string)$rightPackage->package_family_name);
		if($nameComparison !== 0) return $nameComparison;

		$versionComparison = strnatcasecmp((string)$leftPackage->package_version, (string)$rightPackage->package_version);
		if($versionComparison !== 0) return $versionComparison;

		return strnatcasecmp((string)$leftPackage->package_id, (string)$rightPackage->package_id);
	}

	private static function representativePackage($row) {
		if($row['source_package'] !== null) return $row['source_package'];
		return $row['target_packages'][0];
	}

	private static function comparePackages($left, $right) {
		$versionComparison = strnatcasecmp((string)$left->package_version, (string)$right->package_version);
		if($versionComparison !== 0) return $versionComparison;
		return strnatcasecmp((string)$left->package_id, (string)$right->package_id);
	}
}
