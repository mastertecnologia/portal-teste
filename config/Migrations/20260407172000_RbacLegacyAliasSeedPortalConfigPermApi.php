<?php
use Migrations\AbstractMigration;

/**
 * Aliases: permissoes.admin, config.manage, portal.notifications, portal.advanced.* (cliente),
 * api.produtos, api.clientes.
 */
class RbacLegacyAliasSeedPortalConfigPermApi extends AbstractMigration {

	/** @var array<int, array{0:string,1:string}> */
	protected $_pairs = [
		['permissoes.admin', 'permissoes.catalog.view'],
		['permissoes.admin', 'permissoes.registry.sync'],
		['permissoes.admin', 'permissoes.matrix.view'],
		['permissoes.admin', 'permissoes.matrix.grant_super'],
		['permissoes.admin', 'permissoes.users.list'],
		['permissoes.admin', 'permissoes.users.assign_roles'],
		['config.manage', 'config.index'],
		['config.manage', 'config.acessos'],
		['config.manage', 'config.emailsuporte'],
		['config.manage', 'config.pastas'],
		['config.manage', 'config.financeiro'],
		['config.manage', 'config.bootstrap'],
		['portal.notifications', 'portal.notifications.read'],
		['portal.notifications', 'portal.notifications.write'],
		['portal.advanced.contracts', 'portal.contracts.client.view'],
		['portal.advanced.contracts', 'portal.contracts.client.pdf'],
		['portal.advanced.invoices', 'portal.invoices.client.view'],
		['portal.advanced.invoices', 'portal.invoices.client.export'],
		['portal.advanced.attendance', 'portal.attendance.client.view'],
		['api.produtos', 'api.produtos.list'],
		['api.produtos', 'api.produtos.add'],
		['api.clientes', 'api.clientes.list'],
		['api.clientes', 'api.clientes.add'],
	];

	public function up() {
		if (!$this->hasTable('rbac_permission_legacy_aliases')) {
			return;
		}
		foreach ($this->_pairs as $pair) {
			$legacy = str_replace("'", "''", $pair[0]);
			$canonical = str_replace("'", "''", $pair[1]);
			$this->execute(sprintf(
				"INSERT INTO rbac_permission_legacy_aliases (legacy_code, canonical_code, notes, created, modified) " .
				"SELECT '%s', '%s', 'Fase 2f — portal/config/permissões/API', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP " .
				"WHERE NOT EXISTS (SELECT 1 FROM rbac_permission_legacy_aliases e WHERE e.legacy_code = '%s' AND e.canonical_code = '%s')",
				$legacy,
				$canonical,
				$legacy,
				$canonical
			));
		}
	}

	public function down() {
		if (!$this->hasTable('rbac_permission_legacy_aliases')) {
			return;
		}
		foreach ($this->_pairs as $pair) {
			$legacy = str_replace("'", "''", $pair[0]);
			$canonical = str_replace("'", "''", $pair[1]);
			$this->execute(sprintf(
				"DELETE FROM rbac_permission_legacy_aliases WHERE legacy_code = '%s' AND canonical_code = '%s'",
				$legacy,
				$canonical
			));
		}
	}
}
