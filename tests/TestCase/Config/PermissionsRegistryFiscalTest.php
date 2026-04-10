<?php
namespace App\Test\TestCase\Config;

use PHPUnit\Framework\TestCase;

/**
 * Garante que o catálogo RBAC inclui o bloco fiscal e alinha controller por código.
 */
class PermissionsRegistryFiscalTest extends TestCase {

	public function testFiscalPermissionsInRegistry() {
		$path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'permissions_registry.php';
		$this->assertFileExists($path);
		$rows = require $path;
		$byCode = [];
		foreach ($rows as $row) {
			if (isset($row['code'])) {
				$byCode[$row['code']] = $row;
			}
		}

		$expected = [
			'fiscal.dashboard' => 'Fiscal',
			'fiscal.notas' => 'FiscalNotas',
			'fiscal.notas_entrada' => 'FiscalNotasEntrada',
			'fiscal.certificados' => 'FiscalCertificados',
			'fiscal.config' => 'FiscalConfig',
			'fiscal.relatorios' => 'FiscalRelatorios',
		];
		foreach ($expected as $code => $controller) {
			$this->assertArrayHasKey($code, $byCode, 'Deve existir permissão ' . $code);
			$this->assertSame($controller, $byCode[$code]['controller'], $code . ' → controller');
			$this->assertSame('empresa', $byCode[$code]['abac_scope'], $code . ' → abac_scope');
		}
	}
}
