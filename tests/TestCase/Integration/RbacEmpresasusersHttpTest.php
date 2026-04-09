<?php
declare(strict_types=1);
namespace App\Test\TestCase\Integration;

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Stack HTTP + Rbac sobre Empresasusers::index.
 * Requer pdo_sqlite e tests/bootstrap_http.php (suite rbac-http).
 */
class RbacEmpresasusersHttpTest extends TestCase {

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

	public function testGuestEmpresasusersIndexRedirectsToLogin(): void {
		$this->get($this->empresasusersIndexPath());
		$this->assertResponseCode(302);
		$this->assertRedirectContains('users/login');
	}

	public function testEquipeWithEmpresasusersViewLoadsIndex(): void {
		self::rbacHttpSqliteSeedUser(self::$conn, 50, [
			'admin' => 0,
			'role' => 0,
			'idempresa' => 1,
		]);
		self::$conn->execute(
			'INSERT INTO empresas (id, nomefantasia) VALUES (?,?)',
			[1, 'Empresa vínculos HTTP']
		);
		self::$conn->execute(
			'INSERT INTO empresasusers (iduser, idempresa) VALUES (?,?)',
			[50, 1]
		);

		self::$conn->execute(
			'INSERT INTO rbac_permissions (code, name, module, controller, action, sort_order) VALUES (?,?,?,?,?,?)',
			['empresasusers.view', 'Vínculos listar', 'Empresas e equipe', 'Empresasusers', 'index', 0]
		);
		$pid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles (slug, name, is_system, active, sort_order, hierarchy_level) VALUES (?,?,?,?,?,?)',
			['http_empresasusers_view', 'HTTP vínculos view', 0, 1, 1, 100]
		);
		$rid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles_permissions (role_id, permission_id) VALUES (?,?)',
			[$rid, $pid]
		);
		self::$conn->execute(
			'INSERT INTO rbac_users_roles (user_id, role_id) VALUES (?,?)',
			[50, $rid]
		);

		$this->session([
			'Auth' => [
				'User' => [
					'id' => 50,
					'username' => 'equipe_vinculos_http',
					'name' => 'Equipe vínculos',
					'role' => 0,
					'admin' => 0,
					'idcliente' => null,
					'idempresa' => 1,
					'setor' => null,
					'permissaoacesso' => null,
				],
			],
		]);

		$this->get($this->empresasusersIndexPath());
		$this->assertResponseOk();
		$this->assertResponseContains('Lista de Relações');
		$this->assertResponseContains('Empresa vínculos HTTP');
		$this->assertResponseContains('Equipe vínculos');
	}

	public function testEquipeWithOnlyMatrixPermissionDeniedOnEmpresasusers(): void {
		self::rbacHttpSqliteSeedUser(self::$conn, 51, [
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
			['http_vinculos_denied', 'HTTP sem vínculos', 0, 1, 1, 100]
		);
		$rid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles_permissions (role_id, permission_id) VALUES (?,?)',
			[$rid, $pid]
		);
		self::$conn->execute(
			'INSERT INTO rbac_users_roles (user_id, role_id) VALUES (?,?)',
			[51, $rid]
		);

		$this->session([
			'Auth' => [
				'User' => [
					'id' => 51,
					'username' => 'equipe_sem_vinculos_http',
					'name' => 'Equipe sem vínculos',
					'role' => 0,
					'admin' => 0,
					'idcliente' => null,
					'idempresa' => 1,
					'setor' => null,
					'permissaoacesso' => null,
				],
			],
		]);

		$this->get($this->empresasusersIndexPath());
		$this->assertResponseCode(302);
		$this->assertRedirectContains('access-denied');
	}

	protected function empresasusersIndexPath(): string {
		$base = Configure::read('App.base');
		if ($base === false || $base === null || $base === '' || !is_string($base)) {
			return '/empresasusers';
		}
		$base = rtrim($base, '/');
		if ($base === '') {
			return '/empresasusers';
		}

		return $base . '/empresasusers';
	}

}
