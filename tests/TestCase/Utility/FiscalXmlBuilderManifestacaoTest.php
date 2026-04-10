<?php
declare(strict_types=1);
namespace App\Test\TestCase\Utility;

use App\Utility\Fiscal\FiscalCalculator;
use App\Utility\Fiscal\FiscalXmlBuilder;
use PHPUnit\Framework\TestCase;

class FiscalXmlBuilderManifestacaoTest extends TestCase {

    public function testManifestacaoConfirmacaoContemTpEvento210200(): void {
        $chave = FiscalCalculator::gerarChaveAcesso('35', '2401', '11222333000181', '55', '1', '1', '1', '12345678');
        $builder = new FiscalXmlBuilder([], ['ambiente' => 2, 'uf' => 'SP'], [], []);
        $xml = $builder->buildManifestacaoDestinatario($chave, '11222333000181', 'confirmacao', 1, '');
        $this->assertStringContainsString('210200', $xml);
        $this->assertStringContainsString('Confirmacao da Operacao', $xml);
        $this->assertStringContainsString($chave, $xml);
    }

    public function testManifestacaoDesconhecimentoContemXJust(): void {
        $chave = FiscalCalculator::gerarChaveAcesso('35', '2401', '11222333000181', '55', '1', '2', '1', '87654321');
        $builder = new FiscalXmlBuilder([], ['ambiente' => 2, 'uf' => 'SP'], [], []);
        $xml = $builder->buildManifestacaoDestinatario($chave, '11222333000181', 'desconhecimento', 1, 'Justificativa com mais de quinze.');
        $this->assertStringContainsString('210220', $xml);
        $this->assertStringContainsString('Desconhecimento da Operacao', $xml);
        $this->assertStringContainsString('xJust', $xml);
    }
}
