<?php
namespace App\Test\TestCase\Utility;

use App\Utility\Fiscal\FiscalRegimeHelper;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;

class FiscalRegimeHelperTest extends TestCase {

    public function tearDown(): void {
        Configure::delete('Fiscal.reforma_tributaria.habilitar_estudo_ibscbs');
        parent::tearDown();
    }

    public function testPisCofinsPadraoSimples(): void {
        $r = FiscalRegimeHelper::pisCofinsAliquotasPadraoReceita(['regime_tributario' => 1]);
        $this->assertEqualsWithDelta(0.65, $r['pis'], 0.0001);
        $this->assertEqualsWithDelta(3.00, $r['cofins'], 0.0001);
    }

    public function testPisCofinsPadraoLucroPresumido(): void {
        $r = FiscalRegimeHelper::pisCofinsAliquotasPadraoReceita([
            'regime_tributario' => 3,
            'regime_normal_enquadramento' => FiscalRegimeHelper::ENQUADRAMENTO_PRESUMIDO,
        ]);
        $this->assertEqualsWithDelta(0.65, $r['pis'], 0.0001);
        $this->assertEqualsWithDelta(3.00, $r['cofins'], 0.0001);
    }

    public function testPisCofinsPadraoLucroReal(): void {
        $r = FiscalRegimeHelper::pisCofinsAliquotasPadraoReceita([
            'regime_tributario' => 3,
            'regime_normal_enquadramento' => FiscalRegimeHelper::ENQUADRAMENTO_REAL,
        ]);
        $this->assertEqualsWithDelta(1.65, $r['pis'], 0.0001);
        $this->assertEqualsWithDelta(7.60, $r['cofins'], 0.0001);
    }

    public function testPisCofinsRegimeNormalSemEnquadramentoAssumeReal(): void {
        $r = FiscalRegimeHelper::pisCofinsAliquotasPadraoReceita(['regime_tributario' => 3]);
        $this->assertEqualsWithDelta(1.65, $r['pis'], 0.0001);
        $this->assertEqualsWithDelta(7.60, $r['cofins'], 0.0001);
    }

    public function testReformaTributariaFlag(): void {
        Configure::write('Fiscal.reforma_tributaria.habilitar_estudo_ibscbs', false);
        $this->assertFalse(FiscalRegimeHelper::reformaTributariaEstudoIbscbsAtivo());
        Configure::write('Fiscal.reforma_tributaria.habilitar_estudo_ibscbs', true);
        $this->assertTrue(FiscalRegimeHelper::reformaTributariaEstudoIbscbsAtivo());
    }

    public function testMensagensRegimeNormalIncompletoNaoVazias(): void {
        $this->assertStringContainsString('CRT 3', FiscalRegimeHelper::mensagemChecklistHomologacaoRegimeNormalIncompleto());
        $this->assertStringContainsString('CRT 3', FiscalRegimeHelper::mensagemBloqueioEmissaoRegimeNormalIncompleto());
        $this->assertStringContainsString('NF-e', FiscalRegimeHelper::mensagemBloqueioEmissaoRegimeNormalIncompleto());
    }

    public function testEmpresaRegimeNormalProntaParaNfe(): void {
        $this->assertTrue(FiscalRegimeHelper::empresaRegimeNormalProntaParaNfe(['regime_tributario' => 1]));
        $this->assertFalse(FiscalRegimeHelper::empresaRegimeNormalProntaParaNfe(['regime_tributario' => 3]));
        $this->assertTrue(FiscalRegimeHelper::empresaRegimeNormalProntaParaNfe([
            'regime_tributario' => 3,
            'regime_normal_enquadramento' => 1,
        ]));
    }

    public function testViewContextFromEmpresaConfigIncomplete(): void {
        $ctx = FiscalRegimeHelper::viewContextFromEmpresaConfig(['regime_tributario' => 3]);
        $this->assertTrue($ctx['fiscalConfigRegimeIncomplete']);
        $this->assertNull($ctx['fiscalRegimeNormalEnquadLabel']);
    }

    public function testViewContextFromEmpresaConfigRegimeNormalCompleto(): void {
        $prev = Configure::read('Fiscal.regime_normal_enquadramento');
        Configure::write('Fiscal.regime_normal_enquadramento', [
            1 => 'Lucro presumido (teste)',
        ]);
        try {
            $ctx = FiscalRegimeHelper::viewContextFromEmpresaConfig([
                'regime_tributario' => 3,
                'regime_normal_enquadramento' => 1,
            ]);
            $this->assertFalse($ctx['fiscalConfigRegimeIncomplete']);
            $this->assertSame('Lucro presumido (teste)', $ctx['fiscalRegimeNormalEnquadLabel']);
        } finally {
            if ($prev === null) {
                Configure::delete('Fiscal.regime_normal_enquadramento');
            } else {
                Configure::write('Fiscal.regime_normal_enquadramento', $prev);
            }
        }
    }
}
