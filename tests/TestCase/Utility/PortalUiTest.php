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
		Configure::write('PortalUi.premium_modules', [
			'clientes' => true,
			'orcamentos' => true,
			'produtos' => true,
			'servicedesk' => true,
		]);
		$this->assertSame('ServicedeskPrototype', PortalUi::listRoute('servicedesk')['controller']);
		$this->assertSame('ClientesPrototype', PortalUi::listRoute('clientes')['controller']);
		$this->assertSame('OrcamentosPrototype', PortalUi::listRoute('orcamentos')['controller']);
		$this->assertSame('ProdutosPrototype', PortalUi::listRoute('produtos')['controller']);
		Configure::write('PortalUi.premium_modules', []);
		$this->assertSame('Clientes', PortalUi::listRoute('clientes')['controller']);
		$this->assertSame('Orcamentos', PortalUi::listRoute('orcamentos')['controller']);
		$this->assertSame('Produtos', PortalUi::listRoute('produtos')['controller']);
	}

	public function testClientesListNavActiveIncludesPrototype(): void {
		$this->assertTrue(PortalUi::isClientesListNavActive('ClientesPrototype', 'lista'));
		$this->assertTrue(PortalUi::isClientesListNavActive('ClientesPrototype', 'visao360'));
		$this->assertFalse(PortalUi::isClientesListNavActive('Clientes', 'add'));
		$this->assertTrue(PortalUi::isClientesListNavActive('Clientes', 'index'));
	}

	public function testOrcamentosDetalheRouteSwitchesWithPremium(): void {
		Configure::write('PortalUi.premium_modules', ['orcamentos' => true]);
		$route = PortalUi::orcamentosDetalheRoute(99);
		$this->assertSame('OrcamentosPrototype', $route['controller']);
		$this->assertSame('detalhe', $route['action']);
		$this->assertSame(99, $route[0]);
		Configure::write('PortalUi.premium_modules', []);
		$legacy = PortalUi::orcamentosDetalheRoute(5);
		$this->assertSame('Orcamentos', $legacy['controller']);
		$this->assertSame('view', $legacy['action']);
	}

	public function testServicedeskRoutesSwitchWithPremium(): void {
		Configure::write('PortalUi.premium_modules', ['servicedesk' => true]);
		$this->assertSame('ServicedeskPrototype', PortalUi::servicedeskHomeRoute()['controller']);
		$this->assertSame('ticket', PortalUi::servicedeskTicketRoute(12)['action']);
		$this->assertSame(12, PortalUi::servicedeskTicketRoute(12)[0]);
		$this->assertSame('1', PortalUi::servicedeskLegacyFilaRoute()['?']['legacy']);
		Configure::write('PortalUi.premium_modules', []);
		$this->assertSame('Servicedesk', PortalUi::servicedeskHomeRoute()['controller']);
		$this->assertSame('edit', PortalUi::servicedeskTicketRoute(3)['action']);
	}

	public function testOrcamentosNovoRouteSwitchesWithPremium(): void {
		Configure::write('PortalUi.premium_modules', ['orcamentos' => true]);
		$this->assertSame('OrcamentosPrototype', PortalUi::orcamentosNovoRoute()['controller']);
		$this->assertSame('view', PortalUi::orcamentosNovoRoute()['action']);
		Configure::write('PortalUi.premium_modules', []);
		$this->assertSame('Orcamentos', PortalUi::orcamentosNovoRoute()['controller']);
		$this->assertSame('add', PortalUi::orcamentosNovoRoute()['action']);
	}

	public function testProdutosEstoqueRouteSwitchesWithPremium(): void {
		Configure::write('PortalUi.premium_modules', ['produtos' => true]);
		$this->assertSame('ProdutosPrototype', PortalUi::produtosEstoqueRoute()['controller']);
		Configure::write('PortalUi.premium_modules', []);
		$this->assertSame('Produtos', PortalUi::produtosEstoqueRoute()['controller']);
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
