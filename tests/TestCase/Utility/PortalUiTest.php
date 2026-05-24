<?php
declare(strict_types=1);

namespace App\Test\TestCase\Utility;

use App\Utility\PortalUi;
use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;

class PortalUiTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Configure::delete('PortalUi');
		PortalUi::clearEmpresaCache();
	}

	public function testMixedModeRequiresExplicitModules(): void {
		Configure::write('PortalUi', [
			'mode' => 'mixed',
			'premium_modules' => [],
			'legacy_actions' => [],
		]);
		$this->assertFalse(PortalUi::isPremiumModule('clientes'));
	}

	public function testPremiumModeEnablesAllWhenListEmpty(): void {
		Configure::write('PortalUi', [
			'mode' => 'premium',
			'premium_modules' => [],
			'default_premium_modules' => ['clientes' => true, 'orcamentos' => true],
		]);
		$this->assertTrue(PortalUi::isPremiumModule('clientes'));
		$this->assertTrue(PortalUi::isPremiumModule('orcamentos'));
	}

	public function testLegacyRedirectWhenModulePremium(): void {
		Configure::write('PortalUi', [
			'mode' => 'mixed',
			'premium_modules' => ['clientes' => true],
			'legacy_actions' => [
				'clientes' => [
					'module' => 'clientes',
					'actions' => [
						'index' => [
							'controller' => 'ClientesPrototype',
							'action' => 'lista',
						],
					],
				],
			],
		]);
		$request = new ServerRequest([
			'url' => '/clientes',
			'environment' => ['REQUEST_METHOD' => 'GET'],
		]);
		$request = $request->withParam('controller', 'Clientes')
			->withParam('action', 'index');
		$user = ['id' => 1, 'role' => 0];
		$route = PortalUi::legacyRedirectRoute($request, $user);
		$this->assertNotNull($route);
		$this->assertSame('ClientesPrototype', $route['controller']);
		$this->assertSame('lista', $route['action']);
	}

	public function testLegacyQueryOptOut(): void {
		Configure::write('PortalUi', [
			'mode' => 'premium',
			'premium_modules' => ['clientes' => true],
			'default_premium_modules' => ['clientes' => true],
			'legacy_actions' => [
				'clientes' => [
					'module' => 'clientes',
					'actions' => [
						'index' => [
							'controller' => 'ClientesPrototype',
							'action' => 'lista',
						],
					],
				],
			],
		]);
		$request = new ServerRequest([
			'url' => '/clientes?legacy=1',
			'query' => ['legacy' => '1'],
			'environment' => ['REQUEST_METHOD' => 'GET'],
		]);
		$request = $request->withParam('controller', 'Clientes')
			->withParam('action', 'index');
		$this->assertNull(PortalUi::legacyRedirectRoute($request, ['id' => 1, 'role' => 0]));
	}

	public function testPortalClientRoleNotRedirected(): void {
		Configure::write('PortalUi', [
			'mode' => 'premium',
			'premium_modules' => [],
			'default_premium_modules' => ['orcamentos' => true],
			'legacy_actions' => [
				'orcamentos' => [
					'module' => 'orcamentos',
					'actions' => [
						'index' => [
							'controller' => 'OrcamentosPrototype',
							'action' => 'lista',
						],
					],
				],
			],
		]);
		$request = new ServerRequest([
			'url' => '/orcamentos',
			'environment' => ['REQUEST_METHOD' => 'GET'],
		]);
		$request = $request->withParam('controller', 'Orcamentos')
			->withParam('action', 'index');
		$this->assertNull(PortalUi::legacyRedirectRoute($request, ['id' => 2, 'role' => 1]));
	}

	public function testEmpresaOverridesGlobalMode(): void {
		Configure::write('PortalUi', [
			'mode' => 'legacy',
			'premium_modules' => [],
			'default_premium_modules' => ['clientes' => true],
		]);
		$resolved = PortalUi::resolveSettings([
			'portal_ui_mode' => 'premium',
			'portal_ui_premium_modules' => null,
		]);
		$this->assertSame('premium', $resolved['mode']);
	}

	public function testEmpresaOverridesModuleList(): void {
		Configure::write('PortalUi', [
			'mode' => 'mixed',
			'premium_modules' => ['clientes' => true, 'orcamentos' => true],
			'default_premium_modules' => [],
		]);
		$resolved = PortalUi::resolveSettings([
			'portal_ui_mode' => 'mixed',
			'portal_ui_premium_modules' => 'servicedesk',
		]);
		$this->assertSame(['servicedesk' => true], $resolved['premium_modules']);
	}

	public function testParseModulesCsv(): void {
		$this->assertSame(
			['clientes' => true, 'orcamentos' => true],
			PortalUi::parseModulesCsv(' clientes, orcamentos '),
		);
	}
}
