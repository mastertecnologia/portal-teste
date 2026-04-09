<?php
declare(strict_types=1);
namespace App\Test\TestCase\Integration;

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Stack HTTP + Rbac sobre Normasempresa::acessoremoto e ::index (normasempresa.read).
 * Requer pdo_sqlite e tests/bootstrap_http.php (suite rbac-http).
 */
class RbacNormasempresaHttpTest extends TestCase {

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

	public function testGuestNormasempresaAcessoremotoRedirectsToLogin(): void {
		$this->get($this->normasempresaAcessoremotoPath());
		$this->assertResponseCode(302);
		$this->assertRedirectContains('users/login');
	}

	public function testEquipeWithNormasempresaAcessoremotoPermissionLoadsPage(): void {
		self::rbacHttpSqliteSeedUser(self::$conn, 90, [
			'admin' => 0,
			'role' => 0,
			'idempresa' => 1,
		]);

		self::$conn->execute(
			'INSERT INTO rbac_permissions (code, name, module, controller, action, sort_order) VALUES (?,?,?,?,?,?)',
			['normasempresa.acessoremoto', 'Acesso remoto', 'Colaborador', 'Normasempresa', 'acessoremoto,acesso_remoto', 0]
		);
		$pid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles (slug, name, is_system, active, sort_order, hierarchy_level) VALUES (?,?,?,?,?,?)',
			['http_normas_acesso_view', 'HTTP normas acesso remoto', 0, 1, 1, 100]
		);
		$rid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles_permissions (role_id, permission_id) VALUES (?,?)',
			[$rid, $pid]
		);
		self::$conn->execute(
			'INSERT INTO rbac_users_roles (user_id, role_id) VALUES (?,?)',
			[90, $rid]
		);

		$this->session([
			'Auth' => [
				'User' => [
					'id' => 90,
					'username' => 'equipe_normas_http',
					'name' => 'Equipe normas',
					'role' => 0,
					'admin' => 0,
					'idcliente' => null,
					'idempresa' => 1,
					'setor' => null,
					'permissaoacesso' => null,
				],
			],
		]);

		$this->get($this->normasempresaAcessoremotoPath());
		$this->assertResponseOk();
		$this->assertResponseContains('Acesso Remoto');
		$this->assertResponseContains('Etapa 1: baixar o arquivo do programa');
	}

	public function testEquipeWithOnlyMatrixPermissionDeniedOnNormasempresaAcessoremoto(): void {
		self::rbacHttpSqliteSeedUser(self::$conn, 91, [
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
			['http_normas_denied', 'HTTP sem normas acesso', 0, 1, 1, 100]
		);
		$rid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles_permissions (role_id, permission_id) VALUES (?,?)',
			[$rid, $pid]
		);
		self::$conn->execute(
			'INSERT INTO rbac_users_roles (user_id, role_id) VALUES (?,?)',
			[91, $rid]
		);

		$this->session([
			'Auth' => [
				'User' => [
					'id' => 91,
					'username' => 'equipe_sem_normas_http',
					'name' => 'Equipe sem normas',
					'role' => 0,
					'admin' => 0,
					'idcliente' => null,
					'idempresa' => 1,
					'setor' => null,
					'permissaoacesso' => null,
				],
			],
		]);

		$this->get($this->normasempresaAcessoremotoPath());
		$this->assertResponseCode(302);
		$this->assertRedirectContains('access-denied');
	}

	public function testGuestNormasempresaIndexRedirectsToLogin(): void {
		$this->get($this->normasempresaIndexPath());
		$this->assertResponseCode(302);
		$this->assertRedirectContains('users/login');
	}

	public function testEquipeWithNormasempresaReadLoadsIndex(): void {
		self::rbacHttpSqliteSeedUser(self::$conn, 92, [
			'admin' => 0,
			'role' => 0,
			'idempresa' => 1,
		]);

		self::$conn->execute(
			'INSERT INTO rbac_permissions (code, name, module, controller, action, sort_order) VALUES (?,?,?,?,?,?)',
			[
				'normasempresa.read',
				'Normas leitura',
				'Colaborador',
				'Normasempresa',
				'index,contato,download,downloadFile,download_file',
				0,
			]
		);
		$pid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles (slug, name, is_system, active, sort_order, hierarchy_level) VALUES (?,?,?,?,?,?)',
			['http_normas_read_view', 'HTTP normas read', 0, 1, 1, 100]
		);
		$rid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles_permissions (role_id, permission_id) VALUES (?,?)',
			[$rid, $pid]
		);
		self::$conn->execute(
			'INSERT INTO rbac_users_roles (user_id, role_id) VALUES (?,?)',
			[92, $rid]
		);

		$this->session([
			'Auth' => [
				'User' => [
					'id' => 92,
					'username' => 'equipe_normas_read_http',
					'name' => 'Equipe normas read',
					'role' => 0,
					'admin' => 0,
					'idcliente' => null,
					'idempresa' => 1,
					'setor' => null,
					'permissaoacesso' => null,
				],
			],
		]);

		$this->get($this->normasempresaIndexPath());
		$this->assertResponseOk();
		$this->assertResponseContains('Normas da Empresa');
		$this->assertResponseContains('políticas internas da operadora');
	}

	public function testEquipeWithOnlyMatrixPermissionDeniedOnNormasempresaIndex(): void {
		self::rbacHttpSqliteSeedUser(self::$conn, 93, [
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
			['http_normas_index_denied', 'HTTP sem normas index', 0, 1, 1, 100]
		);
		$rid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles_permissions (role_id, permission_id) VALUES (?,?)',
			[$rid, $pid]
		);
		self::$conn->execute(
			'INSERT INTO rbac_users_roles (user_id, role_id) VALUES (?,?)',
			[93, $rid]
		);

		$this->session([
			'Auth' => [
				'User' => [
					'id' => 93,
					'username' => 'equipe_sem_normas_index_http',
					'name' => 'Equipe sem normas index',
					'role' => 0,
					'admin' => 0,
					'idcliente' => null,
					'idempresa' => 1,
					'setor' => null,
					'permissaoacesso' => null,
				],
			],
		]);

		$this->get($this->normasempresaIndexPath());
		$this->assertResponseCode(302);
		$this->assertRedirectContains('access-denied');
	}

	protected function normasempresaAcessoremotoPath(): string {
		$base = Configure::read('App.base');
		$suffix = '/normasempresa/acessoremoto';
		if ($base === false || $base === null || $base === '' || !is_string($base)) {
			return $suffix;
		}
		$base = rtrim($base, '/');
		if ($base === '') {
			return $suffix;
		}

		return $base . $suffix;
	}

	protected function normasempresaIndexPath(): string {
		$base = Configure::read('App.base');
		$suffix = '/normasempresa';
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
