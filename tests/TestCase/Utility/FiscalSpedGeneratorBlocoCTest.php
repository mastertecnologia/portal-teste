<?php
declare(strict_types=1);
namespace App\Test\TestCase\Utility;

use App\Utility\Fiscal\FiscalSpedGenerator;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use DateTimeImmutable;
use ReflectionClass;

class FiscalSpedGeneratorBlocoCTest extends TestCase {

    public function tearDown(): void {
        Configure::delete('Fiscal.sped.tipo_item_padrao');
        parent::tearDown();
    }

    public function testC100Nfce65DispensaTotaisStPisCofins(): void {
        $gen = new FiscalSpedGenerator(['id' => 1], [], '2024-01-01', '2024-01-31');
        $ref = new ReflectionClass(FiscalSpedGenerator::class);
        $m = $ref->getMethod('montarCamposC100');
        $m->setAccessible(true);

        $nota = $this->notaStub([
            'modelo' => '65',
            'valor_total' => 100.0,
            'valor_produtos' => 100.0,
            'valor_icms_st' => 10.0,
            'valor_ipi' => 5.0,
            'fiscal_notas_itens' => [],
        ]);

        $c = $m->invoke($gen, $nota);
        $this->assertSame('', $c[2], 'COD_PART vazio');
        $this->assertSame('', $c[21]);
        $this->assertSame('', $c[27]);
    }

    public function testC100Nfe55MantemStEIpi(): void {
        $gen = new FiscalSpedGenerator(['id' => 1], [], '2024-01-01', '2024-01-31');
        $ref = new ReflectionClass(FiscalSpedGenerator::class);
        $m = $ref->getMethod('montarCamposC100');
        $m->setAccessible(true);

        $item = $this->itemComImpostos([
            ['imposto' => 'ICMS', 'base_calculo' => 100, 'aliquota' => 18, 'valor' => 18],
            ['imposto' => 'ICMS_ST', 'base_calculo' => 200, 'aliquota' => 18, 'valor' => 20],
            ['imposto' => 'FCP', 'base_calculo' => 0, 'aliquota' => 0, 'valor' => 1.5],
            ['imposto' => 'IPI', 'base_calculo' => 100, 'aliquota' => 5, 'valor' => 5],
        ]);

        $nota = $this->notaStub([
            'modelo' => '55',
            'valor_total' => 150.0,
            'valor_produtos' => 100.0,
            'fiscal_notas_itens' => [$item],
        ]);

        $c = $m->invoke($gen, $nota);
        $this->assertSame('19,50', $c[20], 'ICMS 18 + FCP 1,5 no VL_ICMS');
        $this->assertSame('200,00', $c[21], 'BC ICMS ST');
        $this->assertSame('20,00', $c[22], 'VL ICMS ST');
        $this->assertSame('5,00', $c[23], 'VL IPI');
    }

    public function testC170UsaIcmsCstEImpostos(): void {
        $gen = new FiscalSpedGenerator(['id' => 1], [], '2024-01-01', '2024-01-31');
        $ref = new ReflectionClass(FiscalSpedGenerator::class);
        $m = $ref->getMethod('montarCamposC170');
        $m->setAccessible(true);

        $item = $this->itemComImpostos([
            ['imposto' => 'ICMS', 'base_calculo' => 100, 'aliquota' => 12, 'valor' => 12],
            ['imposto' => 'ICMS_ST', 'base_calculo' => 150, 'aliquota' => 18, 'valor' => 8],
            ['imposto' => 'PIS', 'base_calculo' => 100, 'aliquota' => 0.65, 'valor' => 0.65],
        ]);
        $item->icms_cst = '00';
        $item->pis_cst = '01';
        $item->numero_item = 1;
        $item->codigo_produto = 'P';
        $item->descricao = 'Prod';
        $item->quantidade = 1;
        $item->unidade = 'UN';
        $item->valor_total = 100;
        $item->cfop = '5102';

        $nota = $this->notaStub(['tipo_operacao' => 1]);
        $row = $m->invoke($gen, $nota, $item);
        $line = '|C170|' . implode('|', $row) . '|';
        $this->assertStringContainsString('|000|', $line);
        $this->assertStringContainsString('12,00', $line);
        $this->assertStringContainsString('8,00', $line);
    }

    public function testC100PreencheCodPartQuandoMapaTemCliente(): void {
        $gen = new FiscalSpedGenerator(['id' => 1], [], '2024-01-01', '2024-01-31');
        $ref = new ReflectionClass(FiscalSpedGenerator::class);
        $pMap = $ref->getProperty('codPartPorClienteId');
        $pMap->setAccessible(true);
        $pMap->setValue($gen, [42 => 'C0001']);

        $m = $ref->getMethod('montarCamposC100');
        $m->setAccessible(true);
        $nota = $this->notaStub([
            'modelo' => '55',
            'idcliente' => 42,
            'fiscal_notas_itens' => [],
        ]);
        $c = $m->invoke($gen, $nota);
        $this->assertSame('C0001', $c[2]);
    }

    public function testC170NormalizaCfopParaQuatroDigitos(): void {
        $gen = new FiscalSpedGenerator(['id' => 1], [], '2024-01-01', '2024-01-31');
        $ref = new ReflectionClass(FiscalSpedGenerator::class);
        $m = $ref->getMethod('montarCamposC170');
        $m->setAccessible(true);

        $item = $this->itemComImpostos([
            ['imposto' => 'ICMS', 'base_calculo' => 50, 'aliquota' => 18, 'valor' => 9],
        ]);
        $item->icms_cst = '00';
        $item->numero_item = 1;
        $item->codigo_produto = 'X';
        $item->descricao = 'X';
        $item->quantidade = 1;
        $item->unidade = 'UN';
        $item->valor_total = 50;
        $item->cfop = '102';

        $nota = $this->notaStub(['tipo_operacao' => 1]);
        $row = $m->invoke($gen, $nota, $item);
        $this->assertSame('0102', $row[9], 'CFOP com 4 posições no C170');
    }

    public function testC190AgrupaMesmoCstCfopAliquota(): void {
        $gen = new FiscalSpedGenerator(['id' => 1], [], '2024-01-01', '2024-01-31');
        $ref = new ReflectionClass(FiscalSpedGenerator::class);
        $m = $ref->getMethod('montarLinhasC190');
        $m->setAccessible(true);

        $i1 = $this->itemComImpostos([
            ['imposto' => 'ICMS', 'base_calculo' => 50, 'aliquota' => 18, 'valor' => 9],
        ]);
        $i1->icms_cst = '00';
        $i1->cfop = '5102';
        $i1->valor_total = 50;
        $i1->valor_desconto = 0;

        $i2 = $this->itemComImpostos([
            ['imposto' => 'ICMS', 'base_calculo' => 50, 'aliquota' => 18, 'valor' => 9],
        ]);
        $i2->icms_cst = '00';
        $i2->cfop = '5102';
        $i2->valor_total = 50;
        $i2->valor_desconto = 0;

        $nota = $this->notaStub([
            'valor_frete' => 0.0,
            'fiscal_notas_itens' => [$i1, $i2],
        ]);
        $linhas = $m->invoke($gen, $nota);
        $this->assertCount(1, $linhas);
        $this->assertSame('000', $linhas[0][0]);
        $this->assertSame('5102', $linhas[0][1]);
        $this->assertSame('100,00', $linhas[0][3], 'VL_OPR = soma mercadorias + ST + IPI (sem frete)');
    }

    public function testC190RateiaFreteEntreBuckets(): void {
        $gen = new FiscalSpedGenerator(['id' => 1], [], '2024-01-01', '2024-01-31');
        $ref = new ReflectionClass(FiscalSpedGenerator::class);
        $m = $ref->getMethod('montarLinhasC190');
        $m->setAccessible(true);

        $i1 = $this->itemComImpostos([
            ['imposto' => 'ICMS', 'base_calculo' => 50, 'aliquota' => 18, 'valor' => 9],
        ]);
        $i1->icms_cst = '00';
        $i1->cfop = '5102';
        $i1->valor_total = 50;

        $i2 = $this->itemComImpostos([
            ['imposto' => 'ICMS', 'base_calculo' => 50, 'aliquota' => 12, 'valor' => 6],
        ]);
        $i2->icms_cst = '00';
        $i2->cfop = '5101';
        $i2->valor_total = 50;

        $nota = $this->notaStub([
            'valor_frete' => 20.0,
            'fiscal_notas_itens' => [$i1, $i2],
        ]);
        $linhas = $m->invoke($gen, $nota);
        $this->assertCount(2, $linhas);
        $this->assertSame('60,00', $linhas[0][3], '50 + metade do frete 10');
        $this->assertSame('60,00', $linhas[1][3]);
    }

    public function testPrepararMapa0190E0200AlinhaComC170(): void {
        $gen = new FiscalSpedGenerator(['id' => 1], [], '2024-01-01', '2024-01-31');
        $ref = new ReflectionClass(FiscalSpedGenerator::class);

        $item = $this->itemComImpostos([
            ['imposto' => 'ICMS', 'base_calculo' => 100, 'aliquota' => 18, 'valor' => 18],
        ]);
        $item->codigo_produto = 'SKU1';
        $item->descricao = 'Produto teste';
        $item->unidade = 'UN';
        $item->ncm = '84713012';
        $item->cest = '';
        $item->numero_item = 1;
        $item->quantidade = 1;
        $item->valor_total = 100;
        $item->icms_cst = '00';
        $item->cfop = '5102';

        $nota = $this->notaStub(['fiscal_notas_itens' => [$item]]);
        $pNotas = $ref->getProperty('notas');
        $pNotas->setAccessible(true);
        $pNotas->setValue($gen, [$nota]);

        $mPrep = $ref->getMethod('prepararMapaItens0200e0190');
        $mPrep->setAccessible(true);
        $mPrep->invoke($gen);

        $pMapU = $ref->getProperty('mapaUnidades0190');
        $pMapU->setAccessible(true);
        $this->assertArrayHasKey('UN', $pMapU->getValue($gen));

        $pMapI = $ref->getProperty('mapaItens0200');
        $pMapI->setAccessible(true);
        $mapI = $pMapI->getValue($gen);
        $this->assertArrayHasKey('SKU1', $mapI);
        $this->assertSame('84713012', $mapI['SKU1']['ncm']);
        $this->assertSame(18.0, $mapI['SKU1']['aliq']);

        $mC170 = $ref->getMethod('montarCamposC170');
        $mC170->setAccessible(true);
        $row = $mC170->invoke($gen, $nota, $item);
        $this->assertSame('SKU1', $row[1]);
    }

    public function testCodigoItemSpedSemCodigoUsaDescricao(): void {
        $gen = new FiscalSpedGenerator(['id' => 1], [], '2024-01-01', '2024-01-31');
        $ref = new ReflectionClass(FiscalSpedGenerator::class);
        $m = $ref->getMethod('codigoItemSped');
        $m->setAccessible(true);

        $item = new \stdClass();
        $item->codigo_produto = '';
        $item->descricao = 'Só descrição';
        $item->numero_item = 3;
        $this->assertSame('Só descrição', $m->invoke($gen, $item));
    }

    public function testPreparar0400MapeiaCodigoNaturezaCadastro(): void {
        $gen = new FiscalSpedGenerator(['id' => 1], [], '2024-01-01', '2024-01-31');
        $ref = new ReflectionClass(FiscalSpedGenerator::class);
        $nota = $this->notaStub(['natureza_operacao' => 'Venda']);
        $nota->id = 5;
        $nota->fiscal_natureza_operacao = (object)[
            'codigo' => 'v-01',
            'descricao' => 'Venda de mercadoria',
        ];
        $pNotas = $ref->getProperty('notas');
        $pNotas->setAccessible(true);
        $pNotas->setValue($gen, [$nota]);

        $m = $ref->getMethod('prepararMapa0400Naturezas');
        $m->setAccessible(true);
        $m->invoke($gen);

        $pMap = $ref->getProperty('mapa0400Naturezas');
        $pMap->setAccessible(true);
        $this->assertSame('Venda de mercadoria', $pMap->getValue($gen)['V01'] ?? null);

        $mCod = $ref->getMethod('codNat0400ParaNota');
        $mCod->setAccessible(true);
        $this->assertSame('V01', $mCod->invoke($gen, $nota));
    }

    public function testC170IncluiCodNat(): void {
        $gen = new FiscalSpedGenerator(['id' => 1], [], '2024-01-01', '2024-01-31');
        $ref = new ReflectionClass(FiscalSpedGenerator::class);
        $pMap = $ref->getProperty('mapa0400Naturezas');
        $pMap->setAccessible(true);
        $pMap->setValue($gen, ['NATX' => 'Descrição natureza']);
        $pPor = $ref->getProperty('codNat0400PorChaveNota');
        $pPor->setAccessible(true);
        $nota = $this->notaStub(['tipo_operacao' => 1]);
        $nota->id = 12;
        $pPor->setValue($gen, ['ID:12' => 'NATX']);

        $m = $ref->getMethod('montarCamposC170');
        $m->setAccessible(true);
        $item = $this->itemComImpostos([
            ['imposto' => 'ICMS', 'base_calculo' => 100, 'aliquota' => 12, 'valor' => 12],
        ]);
        $item->icms_cst = '00';
        $item->numero_item = 1;
        $item->codigo_produto = 'P';
        $item->descricao = 'Prod';
        $item->quantidade = 1;
        $item->unidade = 'UN';
        $item->valor_total = 100;
        $item->cfop = '5102';

        $row = $m->invoke($gen, $nota, $item);
        $this->assertSame('NATX', $row[10] ?? '', 'COD_NAT após CFOP no C170');
    }

    public function testPreparar0450MapeiaInfComplementarNfe55(): void {
        $gen = new FiscalSpedGenerator(['id' => 1], [], '2024-01-01', '2024-01-31');
        $ref = new ReflectionClass(FiscalSpedGenerator::class);
        $nota = $this->notaStub([
            'modelo' => '55',
            'informacoes_complementares' => 'Observação fiscal',
        ]);
        $nota->id = 99;
        $pNotas = $ref->getProperty('notas');
        $pNotas->setAccessible(true);
        $pNotas->setValue($gen, [$nota]);

        $m = $ref->getMethod('prepararMapa0450Informacoes');
        $m->setAccessible(true);
        $m->invoke($gen);

        $pMap = $ref->getProperty('mapa0450Informacoes');
        $pMap->setAccessible(true);
        $this->assertCount(1, $pMap->getValue($gen));

        $mCod = $ref->getMethod('codInf0450ParaNota');
        $mCod->setAccessible(true);
        $this->assertSame('Z00001', $mCod->invoke($gen, $nota));
    }

    public function testE111SomaCampoE110EEmitelinha(): void {
        $cfg = [
            'sped_e111_ajustes_json' => json_encode([
                ['cod_aj_apur' => 'SP000001', 'vl_aj_apur' => 2.5, 'e110_campo' => 'VL_AJ_DEBITOS', 'descr_compl_aj' => 'Teste'],
            ], JSON_UNESCAPED_UNICODE),
        ];
        $gen = new FiscalSpedGenerator(['id' => 1], $cfg, '2024-01-01', '2024-01-31');
        $ref = new ReflectionClass(FiscalSpedGenerator::class);

        $itemS = $this->itemComImpostos([
            ['imposto' => 'ICMS', 'base_calculo' => 100, 'aliquota' => 18, 'valor' => 18],
        ]);
        $notaS = $this->notaStub(['tipo_operacao' => 1, 'fiscal_notas_itens' => [$itemS]]);

        $pNotas = $ref->getProperty('notas');
        $pNotas->setAccessible(true);
        $pNotas->setValue($gen, [$notaS]);

        $pLinhas = $ref->getProperty('linhas');
        $pLinhas->setAccessible(true);
        $pLinhas->setValue($gen, []);

        $pCont = $ref->getProperty('contadores');
        $pCont->setAccessible(true);
        $pCont->setValue($gen, []);

        $m = $ref->getMethod('blocoE');
        $m->setAccessible(true);
        $m->invoke($gen);

        $linhas = $pLinhas->getValue($gen);
        $e110 = null;
        $e111 = null;
        foreach ($linhas as $ln) {
            if (strpos($ln, '|E110|') === 0) {
                $e110 = $ln;
            }
            if (strpos($ln, '|E111|') === 0) {
                $e111 = $ln;
            }
        }
        $this->assertNotNull($e110);
        $this->assertNotNull($e111);
        $parts = explode('|', $e110);
        $this->assertSame('2,50', $parts[3] ?? '', 'VL_AJ_DEBITOS');
        $this->assertStringContainsString('SP000001', $e111);
        $this->assertStringContainsString('2,50', $e111);
    }

    public function testE110SeparaDebitoCreditoPorTipoOperacao(): void {
        $gen = new FiscalSpedGenerator(['id' => 1], [], '2024-01-01', '2024-01-31');
        $ref = new ReflectionClass(FiscalSpedGenerator::class);

        $itemS = $this->itemComImpostos([
            ['imposto' => 'ICMS', 'base_calculo' => 100, 'aliquota' => 18, 'valor' => 18],
        ]);
        $itemE = $this->itemComImpostos([
            ['imposto' => 'ICMS', 'base_calculo' => 100, 'aliquota' => 12, 'valor' => 12],
        ]);

        $notaS = $this->notaStub(['tipo_operacao' => 1, 'fiscal_notas_itens' => [$itemS]]);
        $notaE = $this->notaStub(['tipo_operacao' => 0, 'fiscal_notas_itens' => [$itemE]]);

        $pNotas = $ref->getProperty('notas');
        $pNotas->setAccessible(true);
        $pNotas->setValue($gen, [$notaS, $notaE]);

        $pLinhas = $ref->getProperty('linhas');
        $pLinhas->setAccessible(true);
        $pLinhas->setValue($gen, []);

        $pCont = $ref->getProperty('contadores');
        $pCont->setAccessible(true);
        $pCont->setValue($gen, []);

        $m = $ref->getMethod('blocoE');
        $m->setAccessible(true);
        $m->invoke($gen);

        $linhas = $pLinhas->getValue($gen);
        $e110 = null;
        foreach ($linhas as $ln) {
            if (strpos($ln, '|E110|') === 0) {
                $e110 = $ln;
                break;
            }
        }
        $this->assertNotNull($e110);
        $parts = explode('|', $e110);
        $this->assertSame('18,00', $parts[2] ?? '', 'VL_TOT_DEBITOS');
        $this->assertSame('12,00', $parts[6] ?? '', 'VL_TOT_CREDITOS');
        $this->assertSame('6,00', $parts[11] ?? '', 'VL_SLD_APURADO');
        $this->assertSame('6,00', $parts[13] ?? '', 'VL_ICMS_RECOLHER');
        $this->assertSame('0,00', $parts[14] ?? '', 'VL_SLD_CREDOR_TRANSPORTAR');
    }

    /**
     * @param array<string, mixed> $props
     */
    private function notaStub(array $props): object {
        $defaults = [
            'tipo_operacao' => 1,
            'modelo' => '55',
            'serie' => 1,
            'numero' => 1,
            'chave_acesso' => '4424',
            'data_emissao' => new DateTimeImmutable('2024-06-10'),
            'data_saida' => null,
            'valor_total' => 0.0,
            'valor_produtos' => 0.0,
            'valor_desconto' => 0.0,
            'valor_frete' => 0.0,
            'valor_seguro' => 0.0,
            'valor_outras_despesas' => 0.0,
            'valor_icms' => 0.0,
            'valor_icms_st' => 0.0,
            'valor_ipi' => 0.0,
            'valor_pis' => 0.0,
            'valor_cofins' => 0.0,
            'frete_modalidade' => 9,
            'fiscal_notas_itens' => [],
            'fiscal_notas_pagamentos' => [],
        ];
        $o = (object)array_merge($defaults, $props);
        if (!($o->data_emissao instanceof \DateTimeInterface)) {
            $o->data_emissao = new DateTimeImmutable((string)$o->data_emissao);
        }

        return $o;
    }

    /**
     * @param list<array<string, float|int|string>> $impostos
     */
    private function itemComImpostos(array $impostos): object {
        $rows = [];
        foreach ($impostos as $r) {
            $rows[] = (object)$r;
        }
        $item = new \stdClass();
        $item->fiscal_notas_impostos = $rows;

        return $item;
    }
}
