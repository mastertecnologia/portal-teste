<?php
use Migrations\AbstractMigration;

/**
 * Aliases legado → canónico: clicontratos.manage, servicedesk.tickets, tickets.portal_cliente, ordensservico.full.
 * Idempotente.
 */
class RbacLegacyAliasSeedTicketsOsClicontratos extends AbstractMigration {

	/** @var array<int, array{0:string,1:string}> */
	protected $_pairs = [
		['clicontratos.manage', 'clientes.contratos.view'],
		['clicontratos.manage', 'clientes.contratos.create'],
		['clicontratos.manage', 'clientes.contratos.update'],
		['clicontratos.manage', 'clientes.contratos.delete'],
		['servicedesk.tickets', 'servicedesk.view'],
		['servicedesk.tickets', 'servicedesk.create'],
		['servicedesk.tickets', 'servicedesk.update'],
		['servicedesk.tickets', 'servicedesk.cancel'],
		['servicedesk.tickets', 'servicedesk.print'],
		['tickets.portal_cliente', 'tickets.portal.view'],
		['tickets.portal_cliente', 'tickets.portal.create'],
		['tickets.portal_cliente', 'tickets.portal.update'],
		['ordensservico.full', 'ordensservico.list'],
		['ordensservico.full', 'ordensservico.create'],
		['ordensservico.full', 'ordensservico.view'],
		['ordensservico.full', 'ordensservico.update'],
		['ordensservico.full', 'ordensservico.delete'],
		['ordensservico.full', 'ordensservico.reports'],
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
				"SELECT '%s', '%s', 'Fase 2 — tickets/OS/clicontratos', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP " .
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
