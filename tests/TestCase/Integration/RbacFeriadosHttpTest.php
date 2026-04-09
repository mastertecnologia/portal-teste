<?php
declare(strict_types=1);
namespace App\Test\TestCase\Integration;

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Stack HTTP + Rbac sobre Feriados::index.
 * Requer pdo_sqlite e tests/bootstrap_http.php (suite rbac-http).
 */
class RbacFeriadosHttpTest extends TestCase {

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
	}

	public function testGuestFeriadosIndexRedirectsToLogin(): void {
		$this->get($this->feriadosIndexPath());
		$this->assertResponseCode(302);
		$this->assertRedirectContains('users/login');
	}

	public function testEquipeWithFeriadosViewLoadsIndex(): void {
		$year = (int)date('Y');
		$dataIso = sprintf('%d-07-15', $year);

		self::rbacHttpSqliteSeedUser(self::$conn, 70, [
			'admin' => 0,
			'role' => 0,
			'idempresa' => 1,
		]);
		self::$conn->execute(
			'INSERT INTO feriados (data, descricao, idempresa) VALUES (?,?,?)',
			[$dataIso, 'Feriado integração HTTP', 1]
		);

		self::$conn->execute(
			'INSERT INTO rbac_permissions (code, name, module, controller, action, sort_order) VALUES (?,?,?,?,?,?)',
			['feriados.view', 'Feriados listar', 'Suporte e operação', 'Feriados', 'index', 0]
		);
		$pid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles (slug, name, is_system, active, sort_order, hierarchy_level) VALUES (?,?,?,?,?,?)',
			['http_feriados_view', 'HTTP feriados view', 0, 1, 1, 100]
		);
		$rid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles_permissions (role_id, permission_id) VALUES (?,?)',
			[$rid, $pid]
		);
		self::$conn->execute(
			'INSERT INTO rbac_users_roles (user_id, role_id) VALUES (?,?)',
			[70, $rid]
		);

		$this->session([
			'Auth' => [
				'User' => [
					'id' => 70,
					'username' => 'equipe_feriados_http',
					'name' => 'Equipe feriados',
					'role' => 0,
					'admin' => 0,
					'idcliente' => null,
					'idempresa' => 1,
					'setor' => null,
					'permissaoacesso' => null,
				],
			],
		]);

		$this->get($this->feriadosIndexPath());
		$this->assertResponseOk();
		$this->assertResponseContains('Feriados (horário especial)');
		$this->assertResponseContains('Feriado integração HTTP');
	}

	public function testEquipeWithOnlyMatrixPermissionDeniedOnFeriados(): void {
		self::rbacHttpSqliteSeedUser(self::$conn, 71, [
			'admin' => 0,
			'role' => 0,
			'idempresa' => 1,
		]);
		self::$conn->execute(
			'INSERT INTO rbac_permissions (code, name, module, controller, action, sort_order) VALUES (?,?,?,?,?,?)',
			['permissoes.matrix.view', 'Só matriz', 'Painel administrativo', 'Permissoes', 'adminMatrix,admin_matrix', 0]
		);
		$pid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles (slug, name, is_system, active, sort_order, hierarchy_level) VALUES (?,?,?,?,?,?)',
			['http_feriados_denied', 'HTTP sem feriados', 0, 1, 1, 100]
		);
		$rid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles_permissions (role_id, permission_id) VALUES (?,?)',
			[$rid, $pid]
		);
		self::$conn->execute(
			'INSERT INTO rbac_users_roles (user_id, role_id) VALUES (?,?)',
			[71, $rid]
		);

		$this->session([
			'Auth' => [
				'User' => [
					'id' => 71,
					'username' => 'equipe_sem_feriados_http',
					'name' => 'Equipe sem feriados',
					'role' => 0,
					'admin' => 0,
					'idcliente' => null,
					'idempresa' => 1,
					'setor' => null,
					'permissaoacesso' => null,
				],
			],
		]);

		$this->get($this->feriadosIndexPath());
		$this->assertResponseCode(302);
		$this->assertRedirectContains('access-denied');
	}

	protected function feriadosIndexPath(): string {
		$base = Configure::read('App.base');
		if ($base === false || $base === null || $base === '' || !is_string($base)) {
			return '/feriados';
		}
		$base = rtrim($base, '/');
		if ($base === '') {
			return '/feriados';
		}

		return $base . '/feriados';
	}

}
