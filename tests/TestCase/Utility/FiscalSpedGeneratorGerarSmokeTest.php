<?php
declare(strict_types=1);
namespace App\Test\TestCase\Utility;

use App\Utility\Fiscal\FiscalSpedGenerator;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use DateTimeImmutable;

/**
 * Substitui a carga ORM por notas em memória para exercitar gerar() ponta a ponta.
 *
 * @internal
 */
final class FiscalSpedGerarSmokeGenerator extends FiscalSpedGenerator {

    /** @var array<int, object> */
    public $stubNotas = [];

    protected function carregarNotas(): void {
        $this->notas = $this->stubNotas;
    }
}

class FiscalSpedGeneratorGerarSmokeTest extends TestCase {

    public function tearDown(): void {
        Configure::delete('Fiscal.sped.tipo_item_padrao');
        Configure::delete('Fiscal.sped.cod_ver_layout');
        Configure::delete('Fiscal.sped.registro_0100_modo');
        parent::tearDown();
    }

    public function testGerarMontaCadeiaMinimaBlocos0CEH9(): void {
        $configFiscal = [
            'uf' => 'SP',
            'codigo_municipio_ibge' => '3550308',
            'sped_0460_c190_json' => json_encode([
                'observacoes' => [['cod_obs' => 'TST01', 'txt' => 'Observação smoke']],
                'c190' => [['cst' => '000', 'cfop' => '5102', 'aliq_icms' => 18, 'cod_obs' => 'TST01']],
            ], JSON_UNESCAPED_UNICODE),
        ];
        $empresa = [
            'id' => 1,
            'razaosocial' => 'EMPRESA SMOKE LTDA',
            'cnpj' => '12345678000199',
            'cep' => '01310100',
            'endereco' => 'RUA',
            'numero' => '1',
            'bairro' => 'CENTRO',
            'telefone' => '',
            'email' => '',
            'fantasia' => 'SMOKE',
        ];

        $item = new \stdClass();
        $item->numero_item = 1;
        $item->codigo_produto = 'SKU1';
        $item->descricao = 'Produto smoke SPED';
        $item->quantidade = 1;
        $item->unidade = 'UN';
        $item->ncm = '84713012';
        $item->cest = '';
        $item->valor_total = 100.0;
        $item->valor_desconto = 0.0;
        $item->icms_cst = '00';
        $item->cfop = '5102';
        $item->fiscal_notas_impostos = [
            (object)['imposto' => 'ICMS', 'base_calculo' => 100.0, 'aliquota' => 18.0, 'valor' => 18.0],
        ];

        $nota = new \stdClass();
        $nota->id = 101;
        $nota->tipo_operacao = 1;
        $nota->modelo = '55';
        $nota->serie = 1;
        $nota->numero = 42;
        $nota->chave_acesso = '42240712345678000199550010000000421004626986';
        $nota->data_emissao = new DateTimeImmutable('2024-06-15');
        $nota->data_saida = null;
        $nota->valor_total = 100.0;
        $nota->valor_produtos = 100.0;
        $nota->valor_desconto = 0.0;
        $nota->valor_frete = 0.0;
        $nota->valor_seguro = 0.0;
        $nota->valor_outras_despesas = 0.0;
        $nota->valor_icms = 0.0;
        $nota->valor_icms_st = 0.0;
        $nota->valor_ipi = 0.0;
        $nota->valor_pis = 0.0;
        $nota->valor_cofins = 0.0;
        $nota->idcliente = 0;
        $nota->natureza_operacao = 'Venda smoke';
        $nota->natureza_operacao_id = 0;
        $nota->fiscal_natureza_operacao = null;
        $nota->informacoes_complementares = '';
        $nota->frete_modalidade = 9;
        $nota->fiscal_notas_pagamentos = [];
        $nota->fiscal_notas_itens = [$item];

        $gen = new FiscalSpedGerarSmokeGenerator($empresa, $configFiscal, '2024-06-01', '2024-06-30');
        $gen->stubNotas = [$nota];

        $out = $gen->gerar();

        $this->assertNotSame('', $out);
        $this->assertStringEndsWith("\r\n", $out);
        $this->assertStringContainsString('|0000|', $out);
        $this->assertStringContainsString('|0200|', $out);
        $this->assertStringContainsString('|0400|', $out);
        $this->assertStringContainsString('|0460|', $out);
        $this->assertStringContainsString('|TST01|', $out);
        $this->assertStringContainsString('|C100|', $out);
        $this->assertStringContainsString('|C170|', $out);
        $this->assertStringContainsString('|C190|', $out);
        $this->assertStringContainsString('|E100|', $out);
        $this->assertStringContainsString('|E110|', $out);
        $this->assertStringContainsString('|H001|', $out);
        $this->assertStringContainsString('|B001|', $out);
        $this->assertStringContainsString('|B990|', $out);
        $this->assertStringContainsString('|D001|', $out);
        $this->assertStringContainsString('|D990|', $out);
        $this->assertStringContainsString('|G001|', $out);
        $this->assertStringContainsString('|G990|', $out);
        $this->assertStringContainsString('|K001|', $out);
        $this->assertStringContainsString('|K990|', $out);
        $this->assertStringContainsString('|1001|', $out);
        $this->assertStringContainsString('|1990|', $out);
        $this->assertStringContainsString('|9001|', $out);
        $this->assertStringContainsString('|9999|', $out);

        $pH990 = strpos($out, '|H990|');
        $pK001 = strpos($out, '|K001|');
        $p1001 = strpos($out, '|1001|');
        $this->assertNotFalse($pH990);
        $this->assertNotFalse($pK001);
        $this->assertNotFalse($p1001);
        $this->assertLessThan($pK001, $pH990, 'H antes de K');
        $this->assertLessThan($p1001, $pK001, 'K antes do bloco 1');

        $nLinhas = substr_count($out, "\r\n");
        $this->assertGreaterThan(15, $nLinhas, 'arquivo com múltiplos registros');
    }
}
