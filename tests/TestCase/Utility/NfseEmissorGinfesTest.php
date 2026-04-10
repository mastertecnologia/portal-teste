<?php
declare(strict_types=1);

namespace App\Test\TestCase\Utility;

use App\Utility\Fiscal\Nfse\NfseEmissorGinfes;
use Cake\Core\Configure;
use PHPUnit\Framework\TestCase;

class NfseEmissorGinfesTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        $wrapped = require dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'fiscal.php';
        Configure::write($wrapped);
        Configure::write('Fiscal.nfse_ginfes.wsdl', '');
        putenv('NFSE_GINFES_WSDL=');
        unset($_ENV['NFSE_GINFES_WSDL'], $_SERVER['NFSE_GINFES_WSDL']);
    }

    protected function tearDown(): void {
        Configure::delete('Fiscal');
        parent::tearDown();
    }

    public function testSemWsdlRetornaErroConfiguracao(): void {
        $e = new NfseEmissorGinfes();
        $r = $e->emitir(
            ['modelo' => 'NFSE', 'numero' => '1', 'serie' => '1', 'valor_total' => 10, 'cliente' => ['cnpj' => '11222333000181', 'razaosocial' => 'X']],
            ['inscricao_municipal' => '123456', 'codigo_municipio_ibge' => '3523909'],
            ['cnpj' => '11222333000181']
        );
        $this->assertFalse($r['success']);
        $this->assertStringContainsString('NFSE_GINFES_WSDL', (string)$r['mensagem']);
    }

    public function testSemInscricaoMunicipalRetornaErro(): void {
        Configure::write('Fiscal.nfse_ginfes.wsdl', 'https://example.invalid/wsdl');
        $e = new NfseEmissorGinfes();
        $r = $e->emitir(
            ['valor_total' => 1],
            ['inscricao_municipal' => ''],
            ['cnpj' => '11222333000181']
        );
        $this->assertFalse($r['success']);
        $this->assertStringContainsString('Inscrição municipal', (string)$r['mensagem']);
    }
}
