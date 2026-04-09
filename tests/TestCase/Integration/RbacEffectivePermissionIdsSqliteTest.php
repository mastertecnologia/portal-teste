<?php
declare(strict_types=1);
namespace App\Test\TestCase\Integration;

use App\Utility\RbacEffectivePermissionIds;
use Cake\Database\Connection;
use Cake\Database\Driver\Sqlite;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use PHPUnit\Framework\TestCase;

/**
 * Integração mínima ORM + SQLite (:memory:) para RbacEffectivePermissionIds.
 * Não requer PostgreSQL; útil em CI (pdo_sqlite).
 */
class RbacEffectivePermissionIdsSqliteTest extends TestCase {

	/** @var \Cake\Database\Connection */
	protected static $conn;

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		if (!extension_loaded('pdo_sqlite')) {
			self::markTestSkipped('Extensão pdo_sqlite necessária para este teste.');
		}
		$root = dirname(__DIR__, 3);
		require $root . '/vendor/autoload.php';
		require $root . '/config/paths.php';

		ConnectionManager::drop('default');
		ConnectionManager::config('default', [
			'className' => Connection::class,
			'driver' => Sqlite::class,
			'database' => ':memory:',
			'encoding' => 'utf8',
		]);
		self::$conn = ConnectionManager::get('default');
		self::_createSchema(self::$conn);
		TableRegistry::clear();
	}

	public static function tearDownAfterClass(): void {
		TableRegistry::clear();
		ConnectionManager::drop('default');
		parent::tearDownAfterClass();
	}

	protected function setUp(): void {
		parent::setUp();
		TableRegistry::clear();
		self::$conn->begin();
	}

	protected function tearDown(): void {
		self::$conn->rollback();
		TableRegistry::clear();
		parent::tearDown();
	}

	protected static function _createSchema(Connection $conn): void {
		$stmts = [
			"CREATE TABLE rbac_permissions (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				code VARCHAR(120) NOT NULL UNIQUE,
				name VARCHAR(255) NOT NULL,
				module VARCHAR(100) NOT NULL DEFAULT '',
				controller VARCHAR(80) NOT NULL DEFAULT '',
				action VARCHAR(80) NOT NULL DEFAULT '*',
				perm_type VARCHAR(16) NOT NULL DEFAULT 'rbac',
				abac_scope VARCHAR(32) NULL,
				description TEXT NULL,
				sort_order INTEGER NOT NULL DEFAULT 0,
				created DATETIME NULL,
				modified DATETIME NULL
			)",
			"CREATE TABLE rbac_roles (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				slug VARCHAR(64) NOT NULL UNIQUE,
				name VARCHAR(120) NOT NULL,
				description TEXT NULL,
				is_system INTEGER NOT NULL DEFAULT 1,
				active INTEGER NOT NULL DEFAULT 1,
				sort_order INTEGER NOT NULL DEFAULT 0,
				created DATETIME NULL,
				modified DATETIME NULL
			)",
			"CREATE TABLE rbac_roles_permissions (
				role_id INTEGER NOT NULL,
				permission_id INTEGER NOT NULL,
				PRIMARY KEY (role_id, permission_id)
			)",
			"CREATE TABLE rbac_users_roles (
				user_id INTEGER NOT NULL,
				role_id INTEGER NOT NULL,
				PRIMARY KEY (user_id, role_id)
			)",
		];
		foreach ($stmts as $sql) {
			$conn->execute($sql);
		}
	}

	public function testRoleLinkPermissionIdsWithUserRoleAndMatrixRow(): void {
		self::$conn->execute(
			'INSERT INTO rbac_permissions (code, name, module, controller, action, sort_order) VALUES (?,?,?,?,?,?)',
			['test.integration.view', 'Test', 'Test', 'Test', 'index', 0]
		);
		$pid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles (slug, name, is_system, active, sort_order) VALUES (?,?,?,?,?)',
			['test_role', 'Test role', 0, 1, 1]
		);
		$rid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles_permissions (role_id, permission_id) VALUES (?,?)',
			[$rid, $pid]
		);
		self::$conn->execute(
			'INSERT INTO rbac_users_roles (user_id, role_id) VALUES (?,?)',
			[9001, $rid]
		);

		$raw = RbacEffectivePermissionIds::roleLinkPermissionIds(9001);
		$this->assertSame([$pid], $raw);

		$map = RbacEffectivePermissionIds::effectivePermissionIdMapForUser(9001);
		$this->assertArrayHasKey($pid, $map);
		$this->assertTrue($map[$pid]);
	}

	public function testUserWithoutRolesReturnsEmptyMaps(): void {
		$this->assertSame([], RbacEffectivePermissionIds::roleLinkPermissionIds(42));
		$this->assertSame([], RbacEffectivePermissionIds::effectivePermissionIdMapForUser(42));
	}
}
