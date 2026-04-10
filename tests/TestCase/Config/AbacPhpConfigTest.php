<?php
namespace App\Test\TestCase\Config;

use PHPUnit\Framework\TestCase;

/**
 * Garante que ABAC em config/abac.php permanece ativo e com mapa mínimo esperado.
 */
class AbacPhpConfigTest extends TestCase {

	public function testAbacEnabledAndCoreTables() {
		$path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'abac.php';
		$this->assertFileExists($path);
		$wrapped = require $path;
		$this->assertArrayHasKey('Abac', $wrapped);
		$cfg = $wrapped['Abac'];
		$this->assertTrue($cfg['enabled']);
		$this->assertArrayHasKey('tables', $cfg);
		$tables = $cfg['tables'];
		foreach (['Clientes', 'Tickets', 'Users', 'Ordensservico'] as $key) {
			$this->assertArrayHasKey($key, $tables, 'Mapa ABAC deve incluir ' . $key);
			$this->assertArrayHasKey('empresa_column', $tables[$key]);
		}
		$this->assertTrue($tables['Clientes']['cliente_row_id']);
		$this->assertSame('idcliente', $tables['Tickets']['cliente_column']);
		$this->assertSame('id', $tables['Users']['user_id_column']);
		foreach (['Queues', 'Visitas', 'Clicontratos', 'Orcamentos', 'ContratosHoras'] as $key) {
			$this->assertArrayHasKey($key, $tables, 'Mapa ABAC deve incluir ' . $key);
			$this->assertArrayHasKey('empresa_column', $tables[$key]);
		}
		foreach (['FiscalNotas', 'FiscalNotasEntrada', 'FiscalCertificados', 'FiscalEmpresasConfig', 'FiscalNaturezaOperacao', 'FiscalAliquotas'] as $key) {
			$this->assertArrayHasKey($key, $tables, 'Mapa ABAC deve incluir ' . $key);
			$this->assertArrayHasKey('empresa_column', $tables[$key]);
		}
		$this->assertSame('idcliente', $tables['FiscalNotas']['cliente_column']);
		$this->assertSame('idcliente', $tables['FiscalNotasEntrada']['cliente_column']);
	}
}
