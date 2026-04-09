<?php
namespace App\Test\TestCase\Utility;

use App\Utility\RbacEffectivePermissionIds;
use PHPUnit\Framework\TestCase;

/**
 * Caminhos sem consulta útil (evita depender de BD nos testes unitários).
 */
class RbacEffectivePermissionIdsTest extends TestCase {

	public function testRoleLinkPermissionIdsNonPositiveReturnsEmpty() {
		$this->assertSame([], RbacEffectivePermissionIds::roleLinkPermissionIds(0));
		$this->assertSame([], RbacEffectivePermissionIds::roleLinkPermissionIds(-3));
	}

	public function testEffectiveMapNonPositiveReturnsEmpty() {
		$this->assertSame([], RbacEffectivePermissionIds::effectivePermissionIdMapForUser(0));
	}
}
