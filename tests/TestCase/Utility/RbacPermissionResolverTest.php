<?php
namespace App\Test\TestCase\Utility;

use App\Utility\RbacPermissionResolver;
use Cake\Database\Connection;
use PHPUnit\Framework\TestCase;

/**
 * Casos que não acedem à BD (retorno antecipado em expandPermissionIds / isLegacyBundleCode).
 */
class RbacPermissionResolverTest extends TestCase {

	public function testExpandPermissionIdsEmpty() {
		$this->assertSame([], RbacPermissionResolver::expandPermissionIds([]));
	}

	public function testExpandPermissionIdsFiltersNonPositive() {
		$this->assertSame([], RbacPermissionResolver::expandPermissionIds([0, -1, -99, 0, '0']));
	}

	public function testIsLegacyBundleCodeEmptyReturnsFalse() {
		$conn = $this->createMock(Connection::class);
		$this->assertFalse(RbacPermissionResolver::isLegacyBundleCode('', $conn));
		$this->assertFalse(RbacPermissionResolver::isLegacyBundleCode('   ', $conn));
	}

	public function testIsLegacyBundleCodeWithoutAliasTableReturnsFalse() {
		$schema = new class {
			public function listTables(): array {
				return ['users', 'rbac_permissions'];
			}
		};
		$conn = $this->createMock(Connection::class);
		$conn->expects($this->once())->method('getSchemaCollection')->willReturn($schema);
		$conn->expects($this->never())->method('execute');
		$this->assertFalse(RbacPermissionResolver::isLegacyBundleCode('clientes.manage', $conn));
	}

	public function testIsLegacyBundleCodeExecuteFailureReturnsFalse() {
		$schema = new class {
			public function listTables(): array {
				return ['rbac_permission_legacy_aliases'];
			}
		};
		$conn = $this->createMock(Connection::class);
		$conn->method('getSchemaCollection')->willReturn($schema);
		$conn->method('execute')->willThrowException(new \RuntimeException('connection failed'));
		$this->assertFalse(RbacPermissionResolver::isLegacyBundleCode('clientes.manage', $conn));
	}
}
