<?php
declare(strict_types=1);

namespace App\Test\TestCase\Utility;

use App\Utility\PortalUi;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;

class PortalUiTest extends TestCase {

	public function tearDown(): void {
		Configure::delete('PortalUi.premium_modules');
		parent::tearDown();
	}

	public function testRedirectToPrototypeWhenClientesPremium(): void {
		Configure::write('PortalUi.premium_modules', ['clientes' => true]);
		$route = PortalUi::redirectToPrototypeIfEnabled('clientes', 'ClientesPrototype', 'lista');
		$this->assertNotNull($route);
		$this->assertSame('ClientesPrototype', $route['controller']);
		$this->assertSame('lista', $route['action']);
	}

	public function testRedirectToPrototypeSkippedWhenNotPremium(): void {
		Configure::write('PortalUi.premium_modules', []);
		$this->assertNull(PortalUi::redirectToPrototypeIfEnabled('clientes', 'ClientesPrototype', 'lista'));
	}

	public function testListRouteSwitchesWithPremium(): void {
		Configure::write('PortalUi.premium_modules', ['clientes' => true]);
		$this->assertSame('ClientesPrototype', PortalUi::listRoute('clientes')['controller']);
		Configure::write('PortalUi.premium_modules', []);
		$this->assertSame('Clientes', PortalUi::listRoute('clientes')['controller']);
	}

	public function testClientesListNavActiveIncludesPrototype(): void {
		$this->assertTrue(PortalUi::isClientesListNavActive('ClientesPrototype', 'lista'));
		$this->assertTrue(PortalUi::isClientesListNavActive('ClientesPrototype', 'visao360'));
		$this->assertFalse(PortalUi::isClientesListNavActive('Clientes', 'add'));
		$this->assertTrue(PortalUi::isClientesListNavActive('Clientes', 'index'));
	}

	public function testVisao360RouteSwitchesWithPremium(): void {
		Configure::write('PortalUi.premium_modules', ['clientes' => true]);
		$route = PortalUi::visao360Route(42, ['tab' => 'historico']);
		$this->assertSame('ClientesPrototype', $route['controller']);
		$this->assertSame('visao360', $route['action']);
		$this->assertSame(42, $route[0]);
		$this->assertSame(['tab' => 'historico'], $route['?']);
		Configure::write('PortalUi.premium_modules', []);
		$legacy = PortalUi::visao360Route(7);
		$this->assertSame('Clientes', $legacy['controller']);
		$this->assertSame(7, $legacy[0]);
	}
}
