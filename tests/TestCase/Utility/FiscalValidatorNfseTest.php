<?php
declare(strict_types=1);
namespace App\Test\TestCase\Utility;

use App\Utility\Fiscal\FiscalValidator;
use PHPUnit\Framework\TestCase;

class FiscalValidatorNfseTest extends TestCase {

    public function testModeloNfseRetornaErroSemValidarItensNfe(): void {
        $nota = [
            'idempresa' => 1,
            'idcliente' => 1,
            'modelo' => 'NFSE',
            'fiscal_notas_itens' => [],
        ];
        $config = ['uf' => 'SP'];
        $erros = FiscalValidator::validarNotaParaEmissao($nota, $config);
        $this->assertNotEmpty($erros);
        $this->assertStringContainsString('NFS-e', (string)$erros[0]);
        $this->assertStringNotContainsString('sem itens', (string)$erros[0]);
    }

    /**
     * Retorno antecipado do stub NFS-e não deve acionar validação de enquadramento CRT 3 (NF-e).
     */
    public function testNfseStubNaoExigeEnquadramentoRegimeNormal(): void {
        $nota = [
            'idempresa' => 1,
            'idcliente' => 1,
            'modelo' => 'NFSE',
            'fiscal_notas_itens' => [],
        ];
        $config = [
            'regime_tributario' => 3,
            'uf' => 'SP',
        ];
        $erros = FiscalValidator::validarNotaParaEmissao($nota, $config);
        $crt = array_filter($erros, function ($e) {
            return stripos((string)$e, 'CRT 3') !== false;
        });
        $this->assertSame([], array_values($crt));
    }
}
