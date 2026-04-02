<?php
namespace App\Test\TestCase\Utility;

use App\Utility\RbacChecker;
use PHPUnit\Framework\TestCase;

class RbacCheckerTest extends TestCase {

	public function testMatchActionWildcard() {
		$this->assertTrue(RbacChecker::matchAction('Foo', 'bar', ['controller' => 'Foo', 'action' => '*']));
		$this->assertTrue(RbacChecker::matchAction('Foo', 'bar', ['controller' => 'Foo', 'action' => '']));
	}

	public function testMatchActionExact() {
		$this->assertTrue(RbacChecker::matchAction('ContractManagement', 'index', [
			'controller' => 'ContractManagement',
			'action' => 'index',
		]));
		$this->assertFalse(RbacChecker::matchAction('ContractManagement', 'delete', [
			'controller' => 'ContractManagement',
			'action' => 'index',
		]));
	}

	public function testMatchActionCommaSeparated() {
		$row = [
			'controller' => 'ContractManagement',
			'action' => 'index, view ,exportar',
		];
		$this->assertTrue(RbacChecker::matchAction('ContractManagement', 'view', $row));
		$this->assertTrue(RbacChecker::matchAction('ContractManagement', 'exportar', $row));
		$this->assertFalse(RbacChecker::matchAction('ContractManagement', 'delete', $row));
	}

	public function testPortalContratosAlias() {
		$row = ['controller' => 'PortalAdvancedContracts', 'action' => 'index'];
		$this->assertTrue(RbacChecker::matchAction('PortalContratos', 'index', $row));
		$this->assertFalse(RbacChecker::matchAction('PortalContratos', 'admin', $row));
	}
}
