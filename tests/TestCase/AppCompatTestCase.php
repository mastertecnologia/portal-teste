<?php
namespace App\Test\TestCase;

use PHPUnit\Framework\TestCase;

/**
 * Compatibilidade PHPUnit 6.x (servidores com vendor antigo) vs 8.5+ (composer.json).
 * Estende este TestCase em suites que usam assertIsArray, assertStringContainsString, etc.
 */
abstract class AppCompatTestCase extends TestCase {

	/** @var bool|null */
	private static $phpUnit8OrNewer;

	protected function isPhpUnit8OrNewer(): bool {
		if (self::$phpUnit8OrNewer !== null) {
			return self::$phpUnit8OrNewer;
		}
		if (!class_exists(\PHPUnit\Runner\Version::class)) {
			return self::$phpUnit8OrNewer = false;
		}

		return self::$phpUnit8OrNewer = version_compare(\PHPUnit\Runner\Version::id(), '8.0.0', '>=');
	}

	protected function assertIsArray($actual, $message = ''): void {
		if ($this->isPhpUnit8OrNewer()) {
			parent::assertIsArray($actual, $message);

			return;
		}
		static::assertInternalType('array', $actual, $message);
	}

	protected function assertStringContainsString($needle, $haystack, $message = ''): void {
		if ($this->isPhpUnit8OrNewer()) {
			parent::assertStringContainsString($needle, $haystack, $message);

			return;
		}
		if ($message === '') {
			$message = sprintf("Failed asserting that '%s' contains '%s'.", $haystack, $needle);
		}
		static::assertTrue(strpos((string)$haystack, (string)$needle) !== false, $message);
	}

	protected function assertEqualsCanonicalizing($expected, $actual, $message = ''): void {
		if ($this->isPhpUnit8OrNewer()) {
			parent::assertEqualsCanonicalizing($expected, $actual, $message);

			return;
		}
		static::assertTrue(is_array($expected) && is_array($actual), $message);
		$e = $expected;
		$a = $actual;
		sort($e);
		sort($a);
		static::assertEquals($e, $a, $message);
	}

	public function expectNotToPerformAssertions(): void {
		if (method_exists(TestCase::class, 'expectNotToPerformAssertions')) {
			parent::expectNotToPerformAssertions();

			return;
		}
		static::assertTrue(true);
	}
}
