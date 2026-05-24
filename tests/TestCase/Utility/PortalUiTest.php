<?php
namespace App\Test\TestCase\Utility;

use App\Utility\PortalUi;
use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;

class PortalUiTest extends TestCase {

    public function setUp(): void {
        parent::setUp();
        Configure::write('PortalUi.premium_modules', []);
    }

    public function testIsPremiumModuleEmpty(): void {
        $this->assertFalse(PortalUi::isPremiumModule('clientes'));
    }

    public function testIsPremiumModuleEnabled(): void {
        Configure::write('PortalUi.premium_modules', ['clientes' => true]);
        $this->assertTrue(PortalUi::isPremiumModule('clientes'));
        $this->assertFalse(PortalUi::isPremiumModule('produtos'));
    }

    public function testRedirectToPrototypeWhenEnabled(): void {
        Configure::write('PortalUi.premium_modules', ['clientes' => true]);
        $route = PortalUi::redirectToPrototypeIfEnabled('clientes', 'ClientesPrototype', 'lista');
        $this->assertSame('ClientesPrototype', $route['controller']);
        $this->assertSame('lista', $route['action']);
    }

    public function testLegacyUiForced(): void {
        Configure::write('PortalUi.premium_modules', ['clientes' => true]);
        $req = new ServerRequest(['query' => ['legacy_ui' => '1']]);
        $this->assertTrue(PortalUi::isLegacyUiForced($req));
        $this->assertNull(PortalUi::redirectToPrototypeIfEnabled('clientes', 'ClientesPrototype', 'lista'));
    }
}
