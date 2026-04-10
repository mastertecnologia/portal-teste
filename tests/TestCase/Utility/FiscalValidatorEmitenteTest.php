<?php
declare(strict_types=1);
namespace App\Test\TestCase\Utility;

use App\Utility\Fiscal\FiscalValidator;
use PHPUnit\Framework\TestCase;

class FiscalValidatorEmitenteTest extends TestCase {

    /**
     * Nota mínima coerente + config para isolar validação de CNPJ do emitente.
     *
     * @return array{0: array, 1: array}
     */
    private function baseNotaEConfig(): array {
        $nota = [
            'idempresa' => 1,
            'idcliente' => 1,
            'natureza_operacao' => 'Venda',
            'modelo' => '55',
            'valor_total' => 10.0,
            'fiscal_notas_itens' => [[
                'descricao' => 'Item teste',
                'cfop' => '5102',
                'quantidade' => 1,
                'valor_unitario' => 10,
                'ncm' => '84713012',
            ]],
            'fiscal_notas_pagamentos' => [],
        ];
        $config = [
            'inscricao_estadual' => '123456789',
            'uf' => 'SP',
            'codigo_municipio_ibge' => '3550308',
            'certificado_id' => 1,
        ];

        return [$nota, $config];
    }

    public function testSemTerceiroParametroNaoExigeCnpj(): void {
        [$nota, $config] = $this->baseNotaEConfig();
        $erros = FiscalValidator::validarNotaParaEmissao($nota, $config);
        $cnpjMsgs = array_filter($erros, function ($e) {
            return stripos((string)$e, 'cnpj') !== false;
        });
        $this->assertSame([], $cnpjMsgs);
    }

    public function testCnpjEmitenteCurtoGeraErro(): void {
        [$nota, $config] = $this->baseNotaEConfig();
        $erros = FiscalValidator::validarNotaParaEmissao($nota, $config, ['cnpj' => '123']);
        $this->assertNotEmpty($erros);
        $this->assertTrue(
            (bool)array_filter($erros, function ($e) {
                return stripos((string)$e, '14 dígitos') !== false;
            })
        );
    }

    public function testCnpjEmitenteValidoNaoGeraErroDeCnpj(): void {
        [$nota, $config] = $this->baseNotaEConfig();
        $erros = FiscalValidator::validarNotaParaEmissao($nota, $config, ['cnpj' => '11.222.333/0001-81']);
        $cnpjMsgs = array_filter($erros, function ($e) {
            return stripos((string)$e, 'cnpj') !== false;
        });
        $this->assertSame([], $cnpjMsgs);
    }

    public function testCnpjEmitente14DigitosMasDvInvalido(): void {
        [$nota, $config] = $this->baseNotaEConfig();
        $erros = FiscalValidator::validarNotaParaEmissao($nota, $config, ['cnpj' => '57457522000182']);
        $this->assertTrue(
            (bool)array_filter($erros, function ($e) {
                return stripos((string)$e, 'verificadores') !== false;
            })
        );
    }
}
