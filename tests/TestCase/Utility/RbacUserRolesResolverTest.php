<?php
namespace App\Test\TestCase\Utility;

use App\Utility\RbacUserRolesResolver;
use PHPUnit\Framework\TestCase;

/**
 * Caminhos que não executam queries (userId inválido).
 */
class RbacUserRolesResolverTest extends TestCase {

	public function testEffectiveRoleIdsZeroReturnsEmpty() {
		$this->assertSame([], RbacUserRolesResolver::effectiveRoleIds(0));
	}

	public function testEffectiveRoleIdsNegativeReturnsEmpty() {
		$this->assertSame([], RbacUserRolesResolver::effectiveRoleIds(-1));
	}
}
