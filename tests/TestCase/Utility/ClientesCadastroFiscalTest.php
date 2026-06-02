<?php
declare(strict_types=1);

namespace App\Test\TestCase\Utility;

use App\Utility\ClientesCadastroFiscal;
use Cake\TestSuite\TestCase;

class ClientesCadastroFiscalTest extends TestCase {

	public function testInferirRegimeFromReceitaRaw(): void {
		$this->assertSame(
			ClientesCadastroFiscal::REGIME_SIMPLES,
			ClientesCadastroFiscal::inferirRegimeFromReceitaRaw(['opcao_pelo_simples' => true])
		);
		$this->assertSame(
			ClientesCadastroFiscal::REGIME_PRESUMIDO,
			ClientesCadastroFiscal::inferirRegimeFromReceitaRaw(['opcao_pelo_simples' => false])
		);
		$this->assertSame(
			ClientesCadastroFiscal::REGIME_MEI,
			ClientesCadastroFiscal::inferirRegimeFromReceitaRaw(['porte' => 'MEI'])
		);
	}

	public function testFormatCnaeStored(): void {
		$this->assertSame('6201-5/00', ClientesCadastroFiscal::formatCnaeStored('6201500'));
	}
}
