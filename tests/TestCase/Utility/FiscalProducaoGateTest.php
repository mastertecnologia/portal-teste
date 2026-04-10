<?php
declare(strict_types=1);
namespace App\Test\TestCase\Utility;

use App\Utility\Fiscal\FiscalProducaoGate;
use PHPUnit\Framework\TestCase;

class FiscalProducaoGateTest extends TestCase {

    /**
     * @return iterable<string, array{int, int, bool}>
     */
    public static function ambienteProvider(): iterable {
        yield 'homolog_homolog' => [2, 2, false];
        yield 'prod_global' => [1, 2, true];
        yield 'prod_empresa' => [2, 1, true];
        yield 'prod_both' => [1, 1, true];
    }

    /** @dataProvider ambienteProvider */
    public function testAmbienteEhProducao(int $global, int $empresa, bool $expected): void {
        $this->assertSame($expected, FiscalProducaoGate::ambienteEhProducao($global, $empresa));
    }

    public function testConfirmacaoProducaoMarcadaNullData(): void {
        $this->assertFalse(FiscalProducaoGate::confirmacaoProducaoMarcada(null));
    }

    public function testConfirmacaoProducaoMarcadaEmpty(): void {
        $this->assertFalse(FiscalProducaoGate::confirmacaoProducaoMarcada([]));
    }

    public function testConfirmacaoProducaoMarcadaPresent(): void {
        $this->assertTrue(FiscalProducaoGate::confirmacaoProducaoMarcada(['confirmar_producao' => '1']));
    }

    public function testConfirmacaoProducaoMarcadaZeroStringIgnored(): void {
        $this->assertFalse(FiscalProducaoGate::confirmacaoProducaoMarcada(['confirmar_producao' => '0']));
    }
}
