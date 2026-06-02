<?php
declare(strict_types=1);

namespace App\Test\TestCase\Config;

use PHPUnit\Framework\TestCase;

/**
 * Catálogo RBAC do módulo Licenciamento.
 */
class PermissionsRegistryLicencasTest extends TestCase {

	public function testLicencasPermissionsInRegistry(): void {
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
			'licencas.view' => 'LicencasPrototype',
			'licencas.manage' => 'LicencasPrototype',
			'licencas.cofre.view' => 'LicencasPrototype',
			'licencas.cofre.secret' => 'LicencasPrototype',
		];
		foreach ($expected as $code => $controller) {
			$this->assertArrayHasKey($code, $byCode, 'Deve existir permissão ' . $code);
			$this->assertSame($controller, $byCode[$code]['controller'], $code . ' → controller');
			$this->assertSame('empresa', $byCode[$code]['abac_scope'], $code . ' → abac_scope');
		}
	}
}
