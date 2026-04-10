<?php
namespace App\Test\TestCase\Utility;

use App\Utility\Fiscal\FiscalRegimeHelper;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;

class FiscalRegimeHelperTest extends TestCase {

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
}
