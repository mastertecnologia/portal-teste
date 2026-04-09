<?php
namespace App\Test\TestCase\Config;

use App\Test\TestCase\AppCompatTestCase;

/**
 * Garante que os defaults de rollout em config/rbac.php permanecem alinhados ao IMPLEMENTATION_LOG.
 *
 * Isola variáveis `RBAC_*` (getenv / $_ENV / $_SERVER) para o `require` de `config/rbac.php` não herdar
 * `.env` ou ambiente CI — alinhado a `config/bootstrap.php`, que espelha variáveis nestes três sítios.
 */
class RbacPhpConfigTest extends AppCompatTestCase {

	private const RBAC_ENV_KEYS = [
		'RBAC_MODE',
		'RBAC_MENU_FILTER_CONFIG',
		'RBAC_MENU_FILTER_SIDEBAR',
		'RBAC_LOG_UNASSIGNED_USERS',
		'RBAC_AUDIT_DECISIONS_DB',
		'RBAC_ENFORCE_BLOCK_WITHOUT_ROLES',
		'RBAC_EVALUATE_POLICIES',
		'RBAC_WARN_FLASH',
	];

	/** @var array<string, array{getenv: string|false, env: mixed, server: mixed}> */
	private $rbacEnvBackup = [];

	protected function setUp(): void {
		parent::setUp();
		$this->rbacEnvBackup = [];
		foreach (self::RBAC_ENV_KEYS as $key) {
			$this->rbacEnvBackup[$key] = [
				'getenv' => getenv($key),
				'env' => array_key_exists($key, $_ENV) ? $_ENV[$key] : null,
				'server' => array_key_exists($key, $_SERVER) ? $_SERVER[$key] : null,
			];
			putenv($key . '=');
			unset($_ENV[$key], $_SERVER[$key]);
		}
	}

	protected function tearDown(): void {
		foreach (self::RBAC_ENV_KEYS as $key) {
			if (!isset($this->rbacEnvBackup[$key])) {
				continue;
			}
			$b = $this->rbacEnvBackup[$key];
			putenv($key . '=');
			unset($_ENV[$key], $_SERVER[$key]);
			if ($b['getenv'] !== false && $b['getenv'] !== '') {
				putenv($key . '=' . $b['getenv']);
			}
			if ($b['env'] !== null) {
				$_ENV[$key] = $b['env'];
			}
			if ($b['server'] !== null) {
				$_SERVER[$key] = $b['server'];
			}
		}
		$this->rbacEnvBackup = [];
		parent::tearDown();
	}

	public function testRepositoryRolloutDefaults() {
		$path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'rbac.php';
		$this->assertFileExists($path);
		$wrapped = require $path;
		$this->assertArrayHasKey('Rbac', $wrapped);
		$cfg = $wrapped['Rbac'];
		$this->assertSame('enforce', $cfg['mode']);
		$this->assertTrue($cfg['menu_filter_config']);
		$this->assertTrue($cfg['menu_filter_sidebar']);
		$this->assertTrue($cfg['audit_decisions_db']);
		$this->assertTrue($cfg['evaluate_permission_policies']);
		$this->assertSame(90, $cfg['audit_retention_days']);
		$this->assertFalse($cfg['enforce_block_without_roles']);
		$this->assertTrue($cfg['expand_legacy_aliases']);
		$this->assertTrue($cfg['expand_group_roles']);
		$this->assertTrue($cfg['enforce_block_without_roles_equipe_only']);
		$this->assertFalse($cfg['legacy_permission_log']);
		$this->assertIsArray($cfg['skip_action_prefixes']);
		$this->assertContains('api', $cfg['skip_action_prefixes']);
		$this->assertIsArray($cfg['whitelist']);
		$this->assertContains('users#accessdenied', $cfg['whitelist']);
		$this->assertIsArray($cfg['rbac_api_enforced_actions']);
		$this->assertContains('tickets#apiindex', $cfg['rbac_api_enforced_actions']);
		$this->assertIsArray($cfg['menu_sidebar_gates']);
		$this->assertArrayHasKey('dashboard', $cfg['menu_sidebar_gates']);
		$this->assertArrayHasKey('clientes', $cfg['menu_sidebar_gates']);
	}
}
