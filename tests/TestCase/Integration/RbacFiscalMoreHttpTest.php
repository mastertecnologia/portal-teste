<?php
declare(strict_types=1);
namespace App\Test\TestCase\Integration;

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * RBAC HTTP — restantes rotas fiscais (certificados, config, relatórios, entrada, controle de séries).
 * Requer pdo_sqlite e tests/bootstrap_http.php (suite rbac-http).
 */
class RbacFiscalMoreHttpTest extends TestCase {

	use IntegrationTestTrait;
	use RbacHttpSqliteFixtureTrait;

	/** @var \Cake\Database\Connection */
	protected static $conn;

	/** @var bool */
	protected static $schemaReady = false;

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		if (!extension_loaded('pdo_sqlite')) {
			self::markTestSkipped('Extensão pdo_sqlite necessária para este teste.');
		}
		if (!defined('PGM_HTTP_TEST_DATASOURCE') || !PGM_HTTP_TEST_DATASOURCE) {
			self::markTestSkipped('Correr com bootstrap HTTP: phpunit --bootstrap tests/bootstrap_http.php --testsuite rbac-http');
		}
		self::$conn = ConnectionManager::get('default');
		if (!self::$schemaReady) {
			self::rbacHttpSqliteCreateBaseSchema(self::$conn);
			self::$schemaReady = true;
		}
		TableRegistry::clear();
	}

	public function setUp(): void {
		parent::setUp();
		$this->configApplication('App\Application', [ROOT . DS . 'config']);
		$this->useHttpServer(true);
		TableRegistry::clear();
		self::rbacHttpSqliteTruncate(self::$conn);
		self::rbacHttpSqliteSeedEmpresaMin(self::$conn);
	}

	/**
	 * @return array<string, array{0: string, 1: string, 2: string, 3: string, 4: int}>
	 */
	public static function allowedFiscalPagesProvider(): array {
		return [
			'certificados' => ['/fiscal-certificados', 'fiscal.certificados', 'FiscalCertificados', 'Certificados digitais', 201],
			'config' => ['/fiscal-config', 'fiscal.config', 'FiscalConfig', 'Configuração fiscal da empresa', 202],
			'relatorios' => ['/fiscal-relatorios', 'fiscal.relatorios', 'FiscalRelatorios', 'Relatórios fiscais', 203],
			'entrada' => ['/fiscal-notas-entrada', 'fiscal.notas_entrada', 'FiscalNotasEntrada', 'Notas fiscais de entrada', 204],
			'controle_series' => ['/fiscal-notas/controle-series', 'fiscal.notas', 'FiscalNotas', 'Controle e busca', 205],
			'inutilizar_numeracao' => ['/fiscal-notas/inutilizar-numeracao', 'fiscal.notas', 'FiscalNotas', 'Inutilizar numeração', 206],
			'inutilizar_numeracao_entrada' => ['/fiscal-notas-entrada/inutilizar-numeracao', 'fiscal.notas_entrada', 'FiscalNotasEntrada', 'Inutilizar numeração', 207],
		];
	}

	/**
	 * @dataProvider allowedFiscalPagesProvider
	 */
	public function testEquipeWithMatchingPermissionLoads(
		string $suffix,
		string $code,
		string $controller,
		string $needle,
		int $uid
	): void {
		$this->grantRbacPermission($uid, $code, $controller);
		$this->loginEquipeSession($uid);

		$this->get($this->absolutePath($suffix));
		$this->assertResponseOk();
		$this->assertResponseContains($needle);
	}

	/**
	 * @return array<int, array{0: string}>
	 */
	public static function deniedFiscalPagesProvider(): array {
		return [
			['/fiscal-certificados'],
			['/fiscal-config'],
			['/fiscal-relatorios'],
			['/fiscal-notas-entrada'],
			['/fiscal-notas/controle-series'],
			['/fiscal-notas/inutilizar-numeracao'],
			['/fiscal-notas-entrada/inutilizar-numeracao'],
		];
	}

	/**
	 * Só fiscal.dashboard não cobre as outras rotas do módulo.
	 *
	 * @dataProvider deniedFiscalPagesProvider
	 */
	public function testDashboardOnlyDenied(string $suffix): void {
		$uid = 210;
		$this->grantRbacPermission($uid, 'fiscal.dashboard', 'Fiscal');
		$this->loginEquipeSession($uid);

		$this->get($this->absolutePath($suffix));
		$this->assertResponseCode(302);
		$this->assertRedirectContains('access-denied');
	}

	public function testGuestsRedirectOnRemainingFiscalRoutes(): void {
		foreach (self::deniedFiscalPagesProvider() as $args) {
			$suffix = $args[0];
			$this->get($this->absolutePath($suffix));
			$this->assertResponseCode(302);
			$this->assertRedirectContains('users/login');
		}
	}

	protected function grantRbacPermission(int $uid, string $code, string $controller, string $action = '*'): void {
		self::rbacHttpSqliteSeedUser(self::$conn, $uid, [
			'admin' => 0,
			'role' => 0,
			'idempresa' => 1,
		]);
		self::$conn->execute(
			'INSERT INTO rbac_permissions (code, name, module, controller, action, sort_order) VALUES (?,?,?,?,?,?)',
			[$code, $code, 'Financeiro', $controller, $action, 0]
		);
		$pid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles (slug, name, is_system, active, sort_order, hierarchy_level) VALUES (?,?,?,?,?,?)',
			['http_fiscal_more_' . (string)$uid . '_' . $code, 'HTTP fiscal', 0, 1, 1, 100]
		);
		$rid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles_permissions (role_id, permission_id) VALUES (?,?)',
			[$rid, $pid]
		);
		self::$conn->execute(
			'INSERT INTO rbac_users_roles (user_id, role_id) VALUES (?,?)',
			[$uid, $rid]
		);
	}

	protected function loginEquipeSession(int $uid): void {
		$this->session([
			'Auth' => [
				'User' => [
					'id' => $uid,
					'username' => 'u' . (string)$uid,
					'name' => 'User ' . (string)$uid,
					'role' => 0,
					'admin' => 0,
					'idcliente' => null,
					'idempresa' => 1,
					'setor' => null,
					'permissaoacesso' => null,
				],
			],
		]);
	}

	protected function absolutePath(string $suffix): string {
		$base = Configure::read('App.base');
		if ($base === false || $base === null || $base === '' || !is_string($base)) {
			return $suffix;
		}
		$base = rtrim($base, '/');
		if ($base === '') {
			return $suffix;
		}

		return $base . $suffix;
	}
}
