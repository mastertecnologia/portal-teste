<?php
declare(strict_types=1);
namespace App\Test\TestCase\Utility;

use App\Utility\Fiscal\FiscalSpedGenerator;
use Cake\TestSuite\TestCase;
use ReflectionClass;

class FiscalSpedGeneratorBlocoHTest extends TestCase {

    public function testSemInventarioH001Um(): void {
        $gen = $this->criarGerador([]);
        $this->invocarBlocoH($gen);
        $regs = $this->registros($this->linhas($gen));
        $this->assertArrayHasKey('H001', $regs);
        $this->assertStringContainsString('|H001|1|', $regs['H001']);
        $this->assertArrayNotHasKey('H005', $regs);
        $this->assertArrayNotHasKey('H010', $regs);
        $p990 = explode('|', $regs['H990']);
        $this->assertSame('2', $p990[2] ?? '', 'H001 + H990');
    }

    public function testComInventarioH005H010(): void {
        $json = json_encode([
            ['cod_item' => 'SKU1', 'unid' => 'UN', 'qtd' => 2, 'vl_unit' => 10, 'vl_item' => 20, 'ind_prop' => '0'],
            ['cod_item' => 'SKU2', 'unid' => 'UN', 'qtd' => 1, 'vl_unit' => 5, 'ind_prop' => '0'],
        ]);
        $gen = $this->criarGerador([
            'sped_inventario_declarar' => true,
            'sped_inventario_dt_inv' => '2024-12-31',
            'sped_inventario_mot_inv' => '01',
            'sped_inventario_itens_json' => $json,
        ]);
        $this->invocarBlocoH($gen);
        $linhas = $this->linhas($gen);
        $regs = $this->registros($linhas);
        $this->assertStringContainsString('|H001|0|', $regs['H001']);
        $this->assertArrayHasKey('H005', $regs);
        $this->assertStringContainsString('|H005|31122024|25,00|01|', $regs['H005']);
        $h010s = array_values(array_filter($linhas, static function ($ln) {
            return strpos($ln, '|H010|') === 0;
        }));
        $this->assertCount(2, $h010s);
        $this->assertStringContainsString('SKU1', $h010s[0]);
        $this->assertStringContainsString('SKU2', $h010s[1]);
        $p990 = explode('|', $regs['H990']);
        $this->assertSame('5', $p990[2] ?? '', 'H001+H005+2×H010+H990');
    }

    public function testDeclararSemJsonVoltaSemDados(): void {
        $gen = $this->criarGerador([
            'sped_inventario_declarar' => true,
            'sped_inventario_dt_inv' => '2024-12-31',
            'sped_inventario_mot_inv' => '01',
            'sped_inventario_itens_json' => '',
        ]);
        $this->invocarBlocoH($gen);
        $regs = $this->registros($this->linhas($gen));
        $this->assertStringContainsString('|H001|1|', $regs['H001']);
    }

    private function criarGerador(array $configFiscal): FiscalSpedGenerator {
        $base = ['uf' => 'SP', 'codigo_municipio_ibge' => '3550308'];

        return new FiscalSpedGenerator(
            ['id' => 1, 'razaosocial' => 'X', 'cnpj' => '1'],
            $configFiscal + $base,
            '2024-01-01',
            '2024-01-31'
        );
    }

    private function invocarBlocoH(FiscalSpedGenerator $gen): void {
        $ref = new ReflectionClass(FiscalSpedGenerator::class);
        $m = $ref->getMethod('blocoH');
        $m->setAccessible(true);
        $m->invoke($gen);
    }

    /**
     * @return array<int, string>
     */
    private function linhas(FiscalSpedGenerator $gen): array {
        $ref = new ReflectionClass(FiscalSpedGenerator::class);
        $p = $ref->getProperty('linhas');
        $p->setAccessible(true);

        return $p->getValue($gen);
    }

    /**
     * @param array<int, string> $linhas
     * @return array<string, string>
     */
    private function registros(array $linhas): array {
        $porReg = [];
        foreach ($linhas as $ln) {
            $p = explode('|', $ln);
            if (count($p) >= 3 && ($p[1] ?? '') !== '') {
                $porReg[$p[1]] = $ln;
            }
        }

        return $porReg;
    }
}
