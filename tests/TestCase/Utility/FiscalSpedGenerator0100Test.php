<?php
declare(strict_types=1);
namespace App\Test\TestCase\Utility;

use App\Utility\Fiscal\FiscalSpedGenerator;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use ReflectionClass;

class FiscalSpedGenerator0100Test extends TestCase {

    public function tearDown(): void {
        Configure::delete('Fiscal.sped.registro_0100_modo');
        Configure::delete('Fiscal.sped.cod_ver_layout');
        parent::tearDown();
    }

    public function testOmitirQuandoCadastroIncompleto(): void {
        $gen = $this->criarGerador([]);
        $this->invocarBloco0($gen);
        $regs = $this->registrosPorLinha($this->linhasDo($gen));
        $this->assertArrayNotHasKey('0100', $regs);
    }

    public function testSempreStub(): void {
        Configure::write('Fiscal.sped.registro_0100_modo', 'sempre_stub');
        $gen = $this->criarGerador([]);
        $this->invocarBloco0($gen);
        $regs = $this->registrosPorLinha($this->linhasDo($gen));
        $this->assertArrayHasKey('0100', $regs);
        $parts = explode('|', $regs['0100']);
        $this->assertSame('', $parts[2] ?? 'x', 'primeiro campo NOME vazio no stub');
    }

    public function test0000RespeitaCodVerLayout(): void {
        Configure::write('Fiscal.sped.cod_ver_layout', '016');
        $gen = $this->criarGerador([]);
        $this->invocarBloco0($gen);
        $regs = $this->registrosPorLinha($this->linhasDo($gen));
        $parts = explode('|', $regs['0000']);
        $this->assertSame('016', $parts[2] ?? '', 'COD_VER no registro 0000');
    }

    public function test0000CodVerInvalidoUsa015(): void {
        Configure::write('Fiscal.sped.cod_ver_layout', 'bad');
        $gen = $this->criarGerador([]);
        $this->invocarBloco0($gen);
        $regs = $this->registrosPorLinha($this->linhasDo($gen));
        $parts = explode('|', $regs['0000']);
        $this->assertSame('015', $parts[2] ?? '');
    }

    public function testCadastroCompleto(): void {
        $gen = $this->criarGerador([
            'sped_contabilista_nome' => 'Contador Silva',
            'sped_contabilista_cpf' => '123.456.789-01',
            'sped_contabilista_crc' => '1SP123456',
            'sped_contabilista_email' => 'c@exemplo.br',
            'sped_contabilista_cod_municipio' => '3550308',
        ]);
        $this->invocarBloco0($gen);
        $regs = $this->registrosPorLinha($this->linhasDo($gen));
        $this->assertArrayHasKey('0100', $regs);
        $this->assertStringContainsString('CONTADOR SILVA', $regs['0100']);
        $this->assertStringContainsString('12345678901', $regs['0100']);
    }

    private function criarGerador(array $configFiscal): FiscalSpedGenerator {
        $base = ['uf' => 'SP', 'codigo_municipio_ibge' => '3550308'];

        return new FiscalSpedGenerator(
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
            $configFiscal + $base,
            '2024-01-01',
            '2024-01-31'
        );
    }

    private function invocarBloco0(FiscalSpedGenerator $gen): void {
        $ref = new ReflectionClass(FiscalSpedGenerator::class);
        $m = $ref->getMethod('bloco0');
        $m->setAccessible(true);
        $m->invoke($gen);
    }

    /**
     * @return array<int, string>
     */
    private function linhasDo(FiscalSpedGenerator $gen): array {
        $ref = new ReflectionClass(FiscalSpedGenerator::class);
        $p = $ref->getProperty('linhas');
        $p->setAccessible(true);

        return $p->getValue($gen);
    }

    /**
     * @param array<int, string> $linhas
     * @return array<string, string>
     */
    private function registrosPorLinha(array $linhas): array {
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
