<?php
namespace App\Test\TestCase\Utility;

use App\Utility\RbacClientePortal;
use PHPUnit\Framework\TestCase;

/**
 * Constante e saídas antecipadas sem tocar em TableRegistry.
 */
class RbacClientePortalTest extends TestCase {

	public function testRoleSlugConstant() {
		$this->assertSame('cliente_portal', RbacClientePortal::ROLE_SLUG);
	}

	public function testSyncUserIfEligibleNonPositiveReturnsImmediately() {
		$this->expectNotToPerformAssertions();
		RbacClientePortal::syncUserIfEligible(0);
		RbacClientePortal::syncUserIfEligible(-3);
	}
}
