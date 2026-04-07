<?php
use Migrations\AbstractMigration;

/**
 * Dados iniciais: rbac_permission_legacy_aliases (clientes + usuários equipe/portal).
 * Idempotente. Requer migration 20260407143000_RbacPermissionLegacyAliases.
 */
class RbacLegacyAliasSeedClientesUsuarios extends AbstractMigration {

	/** @var array<int, array{0:string,1:string}> */
	protected $_pairs = [
		// clientes.manage cobre apenas controller Clientes (não Cliacessos/Clicontratos).
		['clientes.manage', 'clientes.view'],
		['clientes.manage', 'clientes.create'],
		['clientes.manage', 'clientes.update'],
		['clientes.manage', 'clientes.delete'],
		['clientes.manage', 'clientes.activate'],
		['clientes.manage', 'clientes.inactivate'],
		['clientes.manage', 'clientes.token.regenerate'],
		['users.equipe', 'usuarios.view'],
		['users.equipe_add', 'usuarios.create'],
		['users.equipe_edit', 'usuarios.update'],
		['users.clientes_index', 'clientes.usuarios.view'],
		['users.cliente_add', 'clientes.usuarios.create'],
		['users.cliente_edit', 'clientes.usuarios.update'],
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
				"SELECT '%s', '%s', 'Fase 2 — seed clientes/usuários', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP " .
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
