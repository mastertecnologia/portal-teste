<?php
declare(strict_types=1);
namespace App\Test\TestCase\Integration;

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Pedidos HTTP reais à stack (middleware + Auth + Rbac) em rotas admin de Permissoes (catálogo + matriz).
 * Requer pdo_sqlite; corre com tests/bootstrap_http.php (composer test-rbac inclui rbac-http).
 */
class RbacPermissoesHttpTest extends TestCase {

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

	/**
	 * Visitante: Auth redireciona para login.
	 */
	public function testGuestAdminMatrixRedirectsToLogin(): void {
		$this->get($this->matrixPath());
		$this->assertResponseCode(302);
		$this->assertRedirectContains('users/login');
	}

	/**
	 * Visitante no catálogo admin: mesma proteção Auth que a matriz.
	 */
	public function testGuestAdminCatalogRedirectsToLogin(): void {
		$this->get($this->permissoesPath('admin-index'));
		$this->assertResponseCode(302);
		$this->assertRedirectContains('users/login');
	}

	/**
	 * Com APP_BASE=/portal o pedido e o Location devem incluir o prefixo (alinhado a vhosts Apache).
	 */
	public function testGuestAdminMatrixRedirectsToLoginWithPortalBase(): void {
		$prevBase = Configure::read('App.base');
		try {
			Configure::write('App.base', '/portal');
			$this->get($this->permissoesPath('admin-matrix'));
			$this->assertResponseCode(302);
			$this->assertRedirectContains('users/login');
		} finally {
			Configure::write('App.base', $prevBase);
		}
	}

	/**
	 * Utilizador portal (role=1): Permissoes::isAuthorized nega → Auth redireciona (não acede à matriz).
	 */
	public function testPortalUserRedirectedFromAdminMatrix(): void {
		self::_seedUser(10, ['admin' => 0, 'role' => 1]);
		$this->session([
			'Auth' => [
				'User' => [
					'id' => 10,
					'username' => 'portal_matrix_http',
					'name' => 'Cliente HTTP',
					'role' => 1,
					'admin' => 0,
					'idcliente' => 1,
					'idempresa' => null,
					'setor' => null,
					'permissaoacesso' => 1,
				],
			],
		]);
		$this->get($this->matrixPath());
		$this->assertResponseCode(302);
		// Auth default: unauthorizedRedirect true → referer ou loginRedirect (dashboard), não loginAction.
		$this->assertRedirectContains('users/dashboard');
	}

	/**
	 * Equipe não-admin sem papéis em rbac_users_roles: com bloqueio “sem papéis” desligado (híbrido default
	 * do repo), Rbac não intercepta; isAuthorized exige código → Auth nega (dashboard). Com
	 * enforce_block_without_roles o destino seria access-denied — forçamos false aqui para o teste ser estável.
	 */
	public function testEquipeWithoutRbacRolesRedirectedFromAdminMatrix(): void {
		$prevRbac = Configure::read('Rbac');
		try {
			$rb = is_array($prevRbac) ? $prevRbac : [];
			$rb['enforce_block_without_roles'] = false;
			Configure::write('Rbac', $rb);

			self::_seedUser(11, ['admin' => 0, 'role' => 0]);
			$this->session([
				'Auth' => [
					'User' => [
						'id' => 11,
						'username' => 'equipe_sem_rbac_http',
						'name' => 'Equipe sem RBAC',
						'role' => 0,
						'admin' => 0,
						'idcliente' => null,
						'idempresa' => null,
						'setor' => null,
						'permissaoacesso' => null,
					],
				],
			]);
			$this->get($this->matrixPath());
			$this->assertResponseCode(302);
			$this->assertRedirectContains('users/dashboard');
		} finally {
			Configure::write('Rbac', $prevRbac);
		}
	}

	/**
	 * Admin legado: bypass RbacComponent + isAuthorized true; página carrega (matriz ou aviso rbacMissing).
	 */
	public function testAdminLegacyCanReachAdminMatrix(): void {
		self::_seedUser(1, ['admin' => 1, 'role' => 0]);
		$this->session([
			'Auth' => [
				'User' => [
					'id' => 1,
					'username' => 'admin_matrix_http',
					'name' => 'Admin HTTP',
					'role' => 0,
					'admin' => 1,
					'idcliente' => null,
					'idempresa' => null,
					'setor' => null,
					'permissaoacesso' => null,
				],
			],
		]);
		$this->get($this->matrixPath());
		$this->assertResponseOk();
		$this->assertResponseContains('Matriz papéis');
	}

	/**
	 * Admin legado: export CSV da matriz (UTF-8 BOM + cabeçalho).
	 */
	public function testAdminLegacyAdminMatrixExportCsvOk(): void {
		self::_seedUser(1, ['admin' => 1, 'role' => 0]);
		$this->session([
			'Auth' => [
				'User' => [
					'id' => 1,
					'username' => 'admin_csv_http',
					'name' => 'Admin CSV',
					'role' => 0,
					'admin' => 1,
					'idcliente' => null,
					'idempresa' => null,
					'setor' => null,
					'permissaoacesso' => null,
				],
			],
		]);
		$this->get($this->permissoesPath('admin-matrix-export-csv'));
		$this->assertResponseOk();
		$this->assertHeaderContains('Content-Type', 'csv');
		$body = (string)$this->_response->getBody();
		$this->assertStringStartsWith("\xEF\xBB\xBF", $body);
		$this->assertStringContainsString('module', $body);
		$this->assertStringContainsString('permission_id', $body);
	}

	/**
	 * Admin legado: catálogo adminIndex (permissoes.catalog.view bypass via admin).
	 */
	public function testAdminLegacyAdminCatalogLoads(): void {
		self::_seedUser(1, ['admin' => 1, 'role' => 0]);
		$this->session([
			'Auth' => [
				'User' => [
					'id' => 1,
					'username' => 'admin_catalog_http',
					'name' => 'Admin catálogo HTTP',
					'role' => 0,
					'admin' => 1,
					'idcliente' => null,
					'idempresa' => null,
					'setor' => null,
					'permissaoacesso' => null,
				],
			],
		]);
		$this->get($this->permissoesPath('admin-index'));
		$this->assertResponseOk();
		$this->assertResponseContains('Permissões do sistema');
	}

	/**
	 * Equipe com papel RBAC sem permissão para Permissoes::adminMatrix: enforce → accessDenied.
	 */
	public function testEquipeWithRbacRoleButNoRoutePermissionRedirected(): void {
		self::_seedUser(2, ['admin' => 0, 'role' => 0]);
		self::$conn->execute(
			'INSERT INTO rbac_permissions (code, name, module, controller, action, sort_order) VALUES (?,?,?,?,?,?)',
			['clientes.index.only', 'X', 'X', 'Clientes', 'index', 0]
		);
		$pid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles (slug, name, is_system, active, sort_order, hierarchy_level) VALUES (?,?,?,?,?,?)',
			['http_test_role', 'HTTP test role', 0, 1, 1, 100]
		);
		$rid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles_permissions (role_id, permission_id) VALUES (?,?)',
			[$rid, $pid]
		);
		self::$conn->execute(
			'INSERT INTO rbac_users_roles (user_id, role_id) VALUES (?,?)',
			[2, $rid]
		);

		$this->session([
			'Auth' => [
				'User' => [
					'id' => 2,
					'username' => 'equipe_rbac_http',
					'name' => 'Equipe RBAC',
					'role' => 0,
					'admin' => 0,
					'idcliente' => null,
					'idempresa' => null,
					'setor' => null,
					'permissaoacesso' => null,
				],
			],
		]);
		$this->get($this->matrixPath());
		$this->assertResponseCode(302);
		$this->assertRedirectContains('access-denied');
	}

	/**
	 * Equipe não-admin: papel com permissoes.matrix.view + linha de rota → 200.
	 */
	public function testEquipeWithMatrixPermissionOk(): void {
		self::_seedUser(3, ['admin' => 0, 'role' => 0]);
		self::$conn->execute(
			'INSERT INTO rbac_permissions (code, name, module, controller, action, sort_order) VALUES (?,?,?,?,?,?)',
			['permissoes.matrix.view', 'Matriz', 'Painel administrativo', 'Permissoes', 'adminMatrix,admin_matrix,adminMatrixExportCsv,admin_matrix_export_csv', 0]
		);
		$pid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles (slug, name, is_system, active, sort_order, hierarchy_level) VALUES (?,?,?,?,?,?)',
			['http_matrix_ok', 'HTTP matrix ok', 0, 1, 1, 100]
		);
		$rid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles_permissions (role_id, permission_id) VALUES (?,?)',
			[$rid, $pid]
		);
		self::$conn->execute(
			'INSERT INTO rbac_users_roles (user_id, role_id) VALUES (?,?)',
			[3, $rid]
		);

		$this->session([
			'Auth' => [
				'User' => [
					'id' => 3,
					'username' => 'equipe_matrix_http',
					'name' => 'Equipe Matrix',
					'role' => 0,
					'admin' => 0,
					'idcliente' => null,
					'idempresa' => null,
					'setor' => null,
					'permissaoacesso' => null,
				],
			],
		]);
		$this->get($this->matrixPath());
		$this->assertResponseOk();
		$this->assertResponseContains('Matriz papéis');
	}

	/**
	 * Equipe só com permissoes.catalog.view: catálogo OK; matriz negada pelo Rbac (rota) antes do grant matrix.
	 */
	public function testEquipeCatalogViewOnlyLoadsCatalogButMatrixDenied(): void {
		self::_seedUser(12, ['admin' => 0, 'role' => 0]);
		self::$conn->execute(
			'INSERT INTO rbac_permissions (code, name, module, controller, action, sort_order) VALUES (?,?,?,?,?,?)',
			['permissoes.catalog.view', 'Catálogo', 'Painel administrativo', 'Permissoes', 'adminIndex,admin_index', 0]
		);
		$pid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles (slug, name, is_system, active, sort_order, hierarchy_level) VALUES (?,?,?,?,?,?)',
			['http_catalog_only', 'HTTP catálogo só', 0, 1, 1, 100]
		);
		$rid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles_permissions (role_id, permission_id) VALUES (?,?)',
			[$rid, $pid]
		);
		self::$conn->execute(
			'INSERT INTO rbac_users_roles (user_id, role_id) VALUES (?,?)',
			[12, $rid]
		);

		$this->session([
			'Auth' => [
				'User' => [
					'id' => 12,
					'username' => 'equipe_catalog_only_http',
					'name' => 'Equipe catálogo',
					'role' => 0,
					'admin' => 0,
					'idcliente' => null,
					'idempresa' => null,
					'setor' => null,
					'permissaoacesso' => null,
				],
			],
		]);

		$this->get($this->permissoesPath('admin-index'));
		$this->assertResponseOk();
		$this->assertResponseContains('Permissões do sistema');

		$this->get($this->matrixPath());
		$this->assertResponseCode(302);
		$this->assertRedirectContains('access-denied');
	}

	/**
	 * Equipe só com permissoes.matrix.view: matriz OK; catálogo negado (rota / isAuthorized).
	 */
	public function testEquipeMatrixViewOnlyLoadsMatrixButCatalogDenied(): void {
		self::_seedUser(13, ['admin' => 0, 'role' => 0]);
		self::$conn->execute(
			'INSERT INTO rbac_permissions (code, name, module, controller, action, sort_order) VALUES (?,?,?,?,?,?)',
			['permissoes.matrix.view', 'Matriz só', 'Painel administrativo', 'Permissoes', 'adminMatrix,admin_matrix,adminMatrixExportCsv,admin_matrix_export_csv', 0]
		);
		$pid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles (slug, name, is_system, active, sort_order, hierarchy_level) VALUES (?,?,?,?,?,?)',
			['http_matrix_only', 'HTTP matriz só', 0, 1, 1, 100]
		);
		$rid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles_permissions (role_id, permission_id) VALUES (?,?)',
			[$rid, $pid]
		);
		self::$conn->execute(
			'INSERT INTO rbac_users_roles (user_id, role_id) VALUES (?,?)',
			[13, $rid]
		);

		$this->session([
			'Auth' => [
				'User' => [
					'id' => 13,
					'username' => 'equipe_matrix_only_http',
					'name' => 'Equipe matriz',
					'role' => 0,
					'admin' => 0,
					'idcliente' => null,
					'idempresa' => null,
					'setor' => null,
					'permissaoacesso' => null,
				],
			],
		]);

		$this->get($this->matrixPath());
		$this->assertResponseOk();
		$this->assertResponseContains('Matriz papéis');

		$this->get($this->permissoesPath('admin-index'));
		$this->assertResponseCode(302);
		$this->assertRedirectContains('access-denied');
	}

	/**
	 * Caminho da matriz conforme App.base (ex.: APP_BASE=/portal no .env).
	 */
	protected function matrixPath(): string {
		return $this->permissoesPath('admin-matrix');
	}

	/**
	 * URL com prefixo opcional App.base + /admin/permissoes/{ação em kebab}.
	 *
	 * @param string $dashedAction ex.: admin-index, admin-matrix
	 */
	protected function permissoesPath(string $dashedAction): string {
		$dashedAction = trim($dashedAction, '/');
		$base = Configure::read('App.base');
		if ($base === false || $base === null || $base === '' || !is_string($base)) {
			return '/admin/permissoes/' . $dashedAction;
		}
		$base = rtrim($base, '/');
		if ($base === '') {
			return '/admin/permissoes/' . $dashedAction;
		}

		return $base . '/admin/permissoes/' . $dashedAction;
	}

	/**
	 * @param array{admin: int, role: int, idempresa?: int|null, idcliente?: int|null} $flags
	 */
	protected static function _seedUser(int $id, array $flags): void {
		self::rbacHttpSqliteSeedUser(self::$conn, $id, $flags);
	}

}
