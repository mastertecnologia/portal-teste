<?php
declare(strict_types=1);

namespace App\Test\TestCase\Utility;

use App\Utility\PortalUrlPath;
use Cake\TestSuite\TestCase;

class PortalUrlPathTest extends TestCase {

	public function testSanitizeInternalRedirectAllowsAppPaths(): void {
		$this->assertSame('/portal/orcamentos-prototype/lista', PortalUrlPath::sanitizeInternalRedirect('/portal/orcamentos-prototype/lista'));
		$this->assertSame('/users/dashboard', PortalUrlPath::sanitizeInternalRedirect('/users/dashboard'));
	}

	public function testSanitizeInternalRedirectBlocksOpenAndProtocolRelative(): void {
		$this->assertNull(PortalUrlPath::sanitizeInternalRedirect('https://evil.example/'));
		$this->assertNull(PortalUrlPath::sanitizeInternalRedirect('//evil.example/path'));
		$this->assertNull(PortalUrlPath::sanitizeInternalRedirect('javascript:alert(1)'));
		$this->assertNull(PortalUrlPath::sanitizeInternalRedirect('orcamentos/lista'));
		$this->assertNull(PortalUrlPath::sanitizeInternalRedirect(''));
	}
}
