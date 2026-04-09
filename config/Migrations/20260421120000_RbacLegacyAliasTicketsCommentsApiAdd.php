<?php
use Migrations\AbstractMigration;

/**
 * tickets.comments.api_add (Ticketcomentarios::apiAdd) — alinhar papéis que já tinham tickets.update / portal / macro tickets.api.
 * Requer catálogo sincronizado com code tickets.comments.api_add.
 */
class RbacLegacyAliasTicketsCommentsApiAdd extends AbstractMigration {

	/** @var array<int, array{0:string,1:string}> */
	protected $_pairs = [
		['tickets.update', 'tickets.comments.api_add'],
		['tickets.api', 'tickets.comments.api_add'],
		['tickets.portal_cliente', 'tickets.comments.api_add'],
		['tickets.portal.view', 'tickets.comments.api_add'],
		['tickets.portal.update', 'tickets.comments.api_add'],
		['servicedesk.tickets', 'tickets.comments.api_add'],
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
				"SELECT '%s', '%s', 'Ticketcomentarios::apiAdd (RBAC api*)', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP " .
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
