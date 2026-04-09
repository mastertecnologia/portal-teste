<?php
declare(strict_types=1);
namespace App\Test\TestCase\Integration;

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Stack HTTP + Rbac sobre Financeiro::index.
 * Requer pdo_sqlite e tests/bootstrap_http.php (suite rbac-http).
 */
class RbacFinanceiroHttpTest extends TestCase {

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

	public function testGuestFinanceiroIndexRedirectsToLogin(): void {
		$this->get($this->financeiroIndexPath());
		$this->assertResponseCode(302);
		$this->assertRedirectContains('users/login');
	}

	public function testEquipeWithFinanceiroViewLoadsDashboard(): void {
		self::rbacHttpSqliteSeedUser(self::$conn, 100, [
			'admin' => 0,
			'role' => 0,
			'idempresa' => 1,
		]);

		self::$conn->execute(
			'INSERT INTO rbac_permissions (code, name, module, controller, action, sort_order) VALUES (?,?,?,?,?,?)',
			['financeiro.view', 'Financeiro ver', 'Financeiro', 'Financeiro', 'index', 0]
		);
		$pid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles (slug, name, is_system, active, sort_order, hierarchy_level) VALUES (?,?,?,?,?,?)',
			['http_financeiro_view', 'HTTP financeiro view', 0, 1, 1, 100]
		);
		$rid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles_permissions (role_id, permission_id) VALUES (?,?)',
			[$rid, $pid]
		);
		self::$conn->execute(
			'INSERT INTO rbac_users_roles (user_id, role_id) VALUES (?,?)',
			[100, $rid]
		);

		$this->session([
			'Auth' => [
				'User' => [
					'id' => 100,
					'username' => 'equipe_financeiro_http',
					'name' => 'Equipe financeiro',
					'role' => 0,
					'admin' => 0,
					'idcliente' => null,
					'idempresa' => 1,
					'setor' => null,
					'permissaoacesso' => null,
				],
			],
		]);

		$this->get($this->financeiroIndexPath());
		$this->assertResponseOk();
		$this->assertResponseContains('Contas a Receber');
		$this->assertResponseContains('Total Receitas');
		$this->assertResponseContains('Nenhum vencimento nos próximos 30 dias');
	}

	public function testEquipeWithOnlyMatrixPermissionDeniedOnFinanceiro(): void {
		self::rbacHttpSqliteSeedUser(self::$conn, 101, [
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
			['http_financeiro_denied', 'HTTP sem financeiro', 0, 1, 1, 100]
		);
		$rid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles_permissions (role_id, permission_id) VALUES (?,?)',
			[$rid, $pid]
		);
		self::$conn->execute(
			'INSERT INTO rbac_users_roles (user_id, role_id) VALUES (?,?)',
			[101, $rid]
		);

		$this->session([
			'Auth' => [
				'User' => [
					'id' => 101,
					'username' => 'equipe_sem_financeiro_http',
					'name' => 'Equipe sem financeiro',
					'role' => 0,
					'admin' => 0,
					'idcliente' => null,
					'idempresa' => 1,
					'setor' => null,
					'permissaoacesso' => null,
				],
			],
		]);

		$this->get($this->financeiroIndexPath());
		$this->assertResponseCode(302);
		$this->assertRedirectContains('access-denied');
	}

	protected function financeiroIndexPath(): string {
		$base = Configure::read('App.base');
		$suffix = '/financeiro';
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
