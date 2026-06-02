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
			ClientesCadastroFiscal::REGIME_SIMPLES,
			ClientesCadastroFiscal::inferirRegimeFromReceitaRaw(['simples' => ['optante' => true]])
		);
		$this->assertSame(
			ClientesCadastroFiscal::REGIME_PRESUMIDO,
			ClientesCadastroFiscal::inferirRegimeFromReceitaRaw(['simples' => ['optante' => false]])
		);
		$this->assertSame(
			ClientesCadastroFiscal::REGIME_MEI,
			ClientesCadastroFiscal::inferirRegimeFromReceitaRaw(['porte' => 'MEI'])
		);
	}

	public function testFormatCnaeStored(): void {
		$this->assertSame('6201-5/00', ClientesCadastroFiscal::formatCnaeStored('6201500'));
		$this->assertSame('3101-2/00', ClientesCadastroFiscal::formatCnaeStored('3101200'));
	}

	public function testEnriquecerDadosConsulta(): void {
		$out = ClientesCadastroFiscal::enriquecerDadosConsulta([
			'regime_tributario' => ClientesCadastroFiscal::REGIME_PRESUMIDO,
			'cnae_principal' => ['codigo' => '3101200', 'descricao' => 'Fabricação de móveis'],
			'data_abertura' => '2010-05-15',
		]);
		$this->assertSame('3101-2/00', $out['cnae_principal_formatado']);
		$this->assertTrue($out['cnae_principal_valido']);
		$this->assertSame('Fabricação de móveis', $out['cnae_principal_descricao']);
	}
}
