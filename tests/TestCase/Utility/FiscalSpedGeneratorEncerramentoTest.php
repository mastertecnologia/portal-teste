<?php
namespace App\Test\TestCase\Utility;

use App\Utility\Fiscal\FiscalSpedGenerator;
use Cake\TestSuite\TestCase;
use ReflectionClass;

/**
 * Registros 0990, C990, E990, H990, K990 com QTD_LIN do bloco (EFD-ICMS/IPI).
 */
class FiscalSpedGeneratorEncerramentoTest extends TestCase {

    public function testEncerramentosComQuantidadeDeLinhasDoBloco() {
        $gen = new FiscalSpedGenerator(
            [
                'id' => 1,
                'razaosocial' => 'EMPRESA TESTE LTDA',
                'cnpj' => '12345678000199',
                'cep' => '01310100',
                'endereco' => 'RUA',
                'numero' => '1',
                'bairro' => 'CENTRO',
                'telefone' => '',
                'email' => '',
            ],
            ['uf' => 'SP', 'codigo_municipio' => '3550308'],
            '2024-01-01',
            '2024-01-31'
        );

        $ref = new ReflectionClass(FiscalSpedGenerator::class);
        $propNotas = $ref->getProperty('notas');
        $propNotas->setAccessible(true);
        $propNotas->setValue($gen, []);

        foreach (['bloco0', 'blocoC', 'blocoE', 'blocoH'] as $nomeMetodo) {
            $m = $ref->getMethod($nomeMetodo);
            $m->setAccessible(true);
            $m->invoke($gen);
        }

        $linhas = $ref->getProperty('linhas')->getValue($gen);

        $porReg = [];
        foreach ($linhas as $ln) {
            $p = explode('|', $ln);
            if (count($p) >= 3 && $p[1] !== '') {
                $porReg[$p[1]] = $ln;
            }
        }

        $this->assertSame('|0990|4|', $porReg['0990'] ?? '', '0000,0001,0005,0990 — sem 0100 (omitir_sem_dados)');
        $this->assertArrayNotHasKey('0100', $porReg);
        $this->assertSame('|C990|2|', $porReg['C990'] ?? '', 'C001,C990 sem notas');
        $this->assertSame('|E990|4|', $porReg['E990'] ?? '', 'E001,E100,E110,E990');
        $this->assertSame('|H990|2|', $porReg['H990'] ?? '', 'H001,H990');
    }

    public function testEncerramentosBlocosBDGK1Vazios(): void {
        $gen = new FiscalSpedGenerator(
            [
                'id' => 1,
                'razaosocial' => 'EMPRESA TESTE LTDA',
                'cnpj' => '12345678000199',
                'cep' => '01310100',
                'endereco' => 'RUA',
                'numero' => '1',
                'bairro' => 'CENTRO',
                'telefone' => '',
                'email' => '',
            ],
            ['uf' => 'SP', 'codigo_municipio' => '3550308'],
            '2024-01-01',
            '2024-01-31'
        );

        $ref = new ReflectionClass(FiscalSpedGenerator::class);
        $propNotas = $ref->getProperty('notas');
        $propNotas->setAccessible(true);
        $propNotas->setValue($gen, []);

        foreach (['blocoB', 'blocoD', 'blocoG', 'blocoK', 'bloco1'] as $nomeMetodo) {
            $m = $ref->getMethod($nomeMetodo);
            $m->setAccessible(true);
            $m->invoke($gen);
        }

        $linhas = $ref->getProperty('linhas')->getValue($gen);
        $porReg = [];
        foreach ($linhas as $ln) {
            $p = explode('|', $ln);
            if (count($p) >= 3 && $p[1] !== '') {
                $porReg[$p[1]] = $ln;
            }
        }

        $this->assertSame('|B990|2|', $porReg['B990'] ?? '', 'B001+B990');
        $this->assertSame('|D990|2|', $porReg['D990'] ?? '', 'D001+D990');
        $this->assertSame('|G990|2|', $porReg['G990'] ?? '', 'G001+G990');
        $this->assertSame('|K990|2|', $porReg['K990'] ?? '', 'K001+K990');
        $this->assertSame('|1990|2|', $porReg['1990'] ?? '', '1001+1990');
    }

    public function testContarLinhasRegistroPrefixo() {
        $gen = new FiscalSpedGenerator(['id' => 1], [], '2024-01-01', '2024-01-31');
        $ref = new ReflectionClass(FiscalSpedGenerator::class);
        $propLinhas = $ref->getProperty('linhas');
        $propLinhas->setAccessible(true);
        $propLinhas->setValue($gen, [
            '|0000|x|',
            '|0001|0|',
            '|C001|0|',
            '|C100|y|',
        ]);

        $m = $ref->getMethod('contarLinhasRegistroPrefixo');
        $m->setAccessible(true);

        $this->assertSame(2, $m->invoke($gen, '0'));
        $this->assertSame(2, $m->invoke($gen, 'C'));
        $this->assertSame(0, $m->invoke($gen, 'E'));
    }
}
