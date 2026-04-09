<?php
declare(strict_types=1);
namespace App\Test\TestCase\Integration;

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Stack HTTP + Rbac sobre ContratosHoras::index (pass idcliente).
 * Requer pdo_sqlite e tests/bootstrap_http.php (suite rbac-http).
 */
class RbacContratosHorasHttpTest extends TestCase {

	use IntegrationTestTrait;
	use RbacHttpSqliteFixtureTrait;

	private const IDCLIENTE_TEST = 301;

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

	public function testGuestContratosHorasIndexRedirectsToLogin(): void {
		$this->get($this->contratosHorasIndexPath(self::IDCLIENTE_TEST));
		$this->assertResponseCode(302);
		$this->assertRedirectContains('users/login');
	}

	public function testEquipeWithContratosHorasViewLoadsIndex(): void {
		self::rbacHttpSqliteSeedUser(self::$conn, 80, [
			'admin' => 0,
			'role' => 0,
			'idempresa' => 1,
		]);
		self::$conn->execute(
			'INSERT INTO contratos_horas (idcliente, idempresa, data_inicio, data_fim, horas_contratadas, saldo_horas, valor_hora_comercial, ativo) VALUES (?,?,?,?,?,?,?,?)',
			[
				self::IDCLIENTE_TEST,
				1,
				'2026-01-01',
				'2026-12-31',
				10.5,
				8.25,
				150.0,
				1,
			]
		);

		self::$conn->execute(
			'INSERT INTO rbac_permissions (code, name, module, controller, action, sort_order) VALUES (?,?,?,?,?,?)',
			['contratos.horas.view', 'Contratos horas listar', 'Operações', 'ContratosHoras', 'index', 0]
		);
		$pid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles (slug, name, is_system, active, sort_order, hierarchy_level) VALUES (?,?,?,?,?,?)',
			['http_contratos_horas_view', 'HTTP contratos horas view', 0, 1, 1, 100]
		);
		$rid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles_permissions (role_id, permission_id) VALUES (?,?)',
			[$rid, $pid]
		);
		self::$conn->execute(
			'INSERT INTO rbac_users_roles (user_id, role_id) VALUES (?,?)',
			[80, $rid]
		);

		$this->session([
			'Auth' => [
				'User' => [
					'id' => 80,
					'username' => 'equipe_contratos_horas_http',
					'name' => 'Equipe contratos horas',
					'role' => 0,
					'admin' => 0,
					'idcliente' => null,
					'idempresa' => 1,
					'setor' => null,
					'permissaoacesso' => null,
				],
			],
		]);

		$this->get($this->contratosHorasIndexPath(self::IDCLIENTE_TEST));
		$this->assertResponseOk();
		$this->assertResponseContains('Contratos de Horas Técnicas');
		$this->assertResponseContains('10,50');
		$this->assertResponseContains('8,25');
	}

	public function testEquipeWithOnlyMatrixPermissionDeniedOnContratosHoras(): void {
		self::rbacHttpSqliteSeedUser(self::$conn, 81, [
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
			['http_contratos_horas_denied', 'HTTP sem contratos horas', 0, 1, 1, 100]
		);
		$rid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles_permissions (role_id, permission_id) VALUES (?,?)',
			[$rid, $pid]
		);
		self::$conn->execute(
			'INSERT INTO rbac_users_roles (user_id, role_id) VALUES (?,?)',
			[81, $rid]
		);

		$this->session([
			'Auth' => [
				'User' => [
					'id' => 81,
					'username' => 'equipe_sem_contratos_horas_http',
					'name' => 'Equipe sem contratos horas',
					'role' => 0,
					'admin' => 0,
					'idcliente' => null,
					'idempresa' => 1,
					'setor' => null,
					'permissaoacesso' => null,
				],
			],
		]);

		$this->get($this->contratosHorasIndexPath(self::IDCLIENTE_TEST));
		$this->assertResponseCode(302);
		$this->assertRedirectContains('access-denied');
	}

	protected function contratosHorasIndexPath(int $idcliente): string {
		$base = Configure::read('App.base');
		$suffix = '/contratos-horas/index/' . (string)$idcliente;
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
