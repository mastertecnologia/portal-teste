<?php
declare(strict_types=1);

namespace App\Test\TestCase\Utility;

use App\Utility\Fiscal\Nfse\NfseEmissorResolver;
use App\Utility\Fiscal\Nfse\NfseEmissorStub;
use Cake\Core\Configure;
use PHPUnit\Framework\TestCase;

class NfseEmissorResolverTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        $wrapped = require dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'fiscal.php';
        Configure::write($wrapped);
    }

    protected function tearDown(): void {
        Configure::delete('Fiscal');
        parent::tearDown();
    }

    public function testSemMapaPersonalizadoRetornaStub(): void {
        $this->assertTrue(NfseEmissorResolver::isStubOnly(['nfse_provedor' => '']));
        $this->assertInstanceOf(NfseEmissorStub::class, NfseEmissorResolver::forConfig(['nfse_provedor' => 'stub']));
    }

    public function testSlugMapeadoUsaImplementacaoConcreta(): void {
        Configure::write('Fiscal.nfse_emissor_map', [
            '' => NfseEmissorStub::class,
            'stub' => NfseEmissorStub::class,
            'test_fake' => FakeNfseEmissorParaTeste::class,
        ]);
        $this->assertFalse(NfseEmissorResolver::isStubOnly(['nfse_provedor' => 'test_fake']));
        $this->assertInstanceOf(FakeNfseEmissorParaTeste::class, NfseEmissorResolver::forConfig(['nfse_provedor' => 'test_fake']));
    }

    public function testPilotoGinfesGissNaoEhStub(): void {
        $this->assertFalse(NfseEmissorResolver::isStubOnly(['nfse_provedor' => 'ginfes_giss']));
        $this->assertInstanceOf(
            \App\Utility\Fiscal\Nfse\NfseEmissorGinfes::class,
            NfseEmissorResolver::forConfig(['nfse_provedor' => 'ginfes_giss'])
        );
    }
}
