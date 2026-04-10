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
}
