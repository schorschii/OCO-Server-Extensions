<?php

use PHPUnit\Framework\TestCase;

class ExtensionConfigurationTest extends TestCase {

	public function testConfiguredResourcesExist(): void {
		$configuration = require __DIR__.'/../index.php';

		$this->assertSame('computer-package-comparison', $configuration['id']);
		$this->assertSame('1.2.2', $configuration['oco-version-min']);
		$this->assertDirectoryExists($configuration['autoload']);

		foreach(['frontend-views', 'frontend-js', 'frontend-css'] as $resourceType) {
			foreach($configuration[$resourceType] as $resource)
				$this->assertFileExists($resource);
		}
		$this->assertDirectoryExists($configuration['translation-dir']);
	}

	public function testTranslationsProvideTheSameExtensionKeys(): void {
		$german = require __DIR__.'/../lang/de.php';
		$english = require __DIR__.'/../lang/en.php';
		$french = require __DIR__.'/../lang/fr.php';

		$this->assertSame(array_keys($german), array_keys($english));
		$this->assertSame(array_keys($german), array_keys($french));
		foreach(array_keys($german) as $key)
			$this->assertStringStartsWith('cpc_', $key);
	}
}
