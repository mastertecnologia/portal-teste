<?php
declare(strict_types=1);
namespace App\Test\TestCase\Integration;

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Stack HTTP + Rbac sobre Areas::index (fora do painel Permissões).
 * Requer pdo_sqlite e tests/bootstrap_http.php (suite rbac-http).
 */
class RbacAreasHttpTest extends TestCase {

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

	public function testGuestAreasIndexRedirectsToLogin(): void {
		$this->get($this->areasIndexPath());
		$this->assertResponseCode(302);
		$this->assertRedirectContains('users/login');
	}

	public function testEquipeWithAreasStatusViewLoadsIndex(): void {
		self::rbacHttpSqliteSeedUser(self::$conn, 20, [
			'admin' => 0,
			'role' => 0,
			'idempresa' => 1,
		]);
		self::$conn->execute(
			'INSERT INTO rbac_permissions (code, name, module, controller, action, sort_order) VALUES (?,?,?,?,?,?)',
			['areas.status.view', 'Áreas listar', 'Parâmetros OS', 'Areas', 'index', 0]
		);
		$pid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles (slug, name, is_system, active, sort_order, hierarchy_level) VALUES (?,?,?,?,?,?)',
			['http_areas_view', 'HTTP áreas view', 0, 1, 1, 100]
		);
		$rid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles_permissions (role_id, permission_id) VALUES (?,?)',
			[$rid, $pid]
		);
		self::$conn->execute(
			'INSERT INTO rbac_users_roles (user_id, role_id) VALUES (?,?)',
			[20, $rid]
		);
		self::$conn->execute(
			'INSERT INTO areas (idempresa, descricao) VALUES (?,?)',
			[1, 'Área integração HTTP']
		);

		$this->session([
			'Auth' => [
				'User' => [
					'id' => 20,
					'username' => 'equipe_areas_http',
					'name' => 'Equipe áreas',
					'role' => 0,
					'admin' => 0,
					'idcliente' => null,
					'idempresa' => 1,
					'setor' => null,
					'permissaoacesso' => null,
				],
			],
		]);

		$this->get($this->areasIndexPath());
		$this->assertResponseOk();
		$this->assertResponseContains('Status de OS');
		$this->assertResponseContains('Área integração HTTP');
	}

	public function testEquipeWithOnlyMatrixPermissionDeniedOnAreas(): void {
		self::rbacHttpSqliteSeedUser(self::$conn, 21, [
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
			['http_areas_denied', 'HTTP sem áreas', 0, 1, 1, 100]
		);
		$rid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles_permissions (role_id, permission_id) VALUES (?,?)',
			[$rid, $pid]
		);
		self::$conn->execute(
			'INSERT INTO rbac_users_roles (user_id, role_id) VALUES (?,?)',
			[21, $rid]
		);

		$this->session([
			'Auth' => [
				'User' => [
					'id' => 21,
					'username' => 'equipe_sem_areas_http',
					'name' => 'Equipe sem áreas',
					'role' => 0,
					'admin' => 0,
					'idcliente' => null,
					'idempresa' => 1,
					'setor' => null,
					'permissaoacesso' => null,
				],
			],
		]);

		$this->get($this->areasIndexPath());
		$this->assertResponseCode(302);
		$this->assertRedirectContains('access-denied');
	}

	protected function areasIndexPath(): string {
		$base = Configure::read('App.base');
		if ($base === false || $base === null || $base === '' || !is_string($base)) {
			return '/areas';
		}
		$base = rtrim($base, '/');
		if ($base === '') {
			return '/areas';
		}

		return $base . '/areas';
	}

}
