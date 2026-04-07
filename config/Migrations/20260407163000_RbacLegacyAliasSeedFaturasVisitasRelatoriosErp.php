<?php
use Migrations\AbstractMigration;

/**
 * Aliases legado → canónico: faturas.locacao, empresasusers.manage, agenda.visitas, portal.relatorios,
 * erp.advanced.* (contracts/invoices/reports), erp.contracts.management, erp.contracts.templates.
 */
class RbacLegacyAliasSeedFaturasVisitasRelatoriosErp extends AbstractMigration {

	/** @var array<int, array{0:string,1:string}> */
	protected $_pairs = [
		['faturas.locacao', 'faturas.view'],
		['faturas.locacao', 'faturas.create'],
		['faturas.locacao', 'faturas.update'],
		['empresasusers.manage', 'empresasusers.view'],
		['empresasusers.manage', 'empresasusers.create'],
		['empresasusers.manage', 'empresasusers.delete'],
		['agenda.visitas', 'visitas.view'],
		['agenda.visitas', 'visitas.create'],
		['agenda.visitas', 'visitas.update'],
		['agenda.visitas', 'visitas.delete'],
		['agenda.visitas', 'visitas.portal.view'],
		['portal.relatorios', 'portal.relatorios.view'],
		['portal.relatorios', 'portal.relatorios.export'],
		['erp.advanced.reports', 'relatorios.indicadores.view'],
		['erp.advanced.reports', 'relatorios.indicadores.export'],
		['erp.advanced.contracts', 'erp.advanced.contracts.view'],
		['erp.advanced.invoices', 'erp.advanced.invoices.view'],
		['erp.advanced.invoices', 'erp.advanced.invoices.update'],
		['erp.advanced.invoices', 'erp.advanced.invoices.export'],
		['erp.contracts.management', 'erp.contracts.management.view'],
		['erp.contracts.management', 'erp.contracts.management.edit'],
		['erp.contracts.management', 'erp.contracts.management.signature'],
		['erp.contracts.management', 'erp.contracts.management.lifecycle'],
		['erp.contracts.management', 'erp.contracts.management.webhook'],
		['erp.contracts.templates', 'erp.contracts.templates.view'],
		['erp.contracts.templates', 'erp.contracts.templates.edit'],
		['erp.contracts.templates', 'erp.contracts.templates.delete'],
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
				"SELECT '%s', '%s', 'Fase 2d — faturas/visitas/relatórios/ERP', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP " .
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
