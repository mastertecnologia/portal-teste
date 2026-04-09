<?php
use Migrations\AbstractMigration;

/**
 * agenda.alias: rotas usam Visitas, não AgendaController — alinha rbac_permissions e aliases legado.
 */
class RbacAgendaAliasVisitasController extends AbstractMigration {

	/** @var array<int, array{0:string,1:string}> */
	protected $_aliasPairs = [
		['agenda.alias', 'visitas.view'],
		['agenda.alias', 'visitas.create'],
		['agenda.alias', 'visitas.update'],
		['agenda.alias', 'visitas.delete'],
		['agenda.alias', 'visitas.portal.view'],
	];

	public function up() {
		if ($this->hasTable('rbac_permissions')) {
			$this->execute(
				"UPDATE rbac_permissions SET controller = 'Visitas', " .
				"name = 'Agenda (rota /agenda → Visitas)', " .
				"description = 'Mesmo controller que /agenda em routes.php (Visitas); código legado agenda.alias para matriz.' " .
				"WHERE code = 'agenda.alias'"
			);
		}
		if (!$this->hasTable('rbac_permission_legacy_aliases')) {
			return;
		}
		foreach ($this->_aliasPairs as $pair) {
			$legacy = str_replace("'", "''", $pair[0]);
			$canonical = str_replace("'", "''", $pair[1]);
			$this->execute(sprintf(
				"INSERT INTO rbac_permission_legacy_aliases (legacy_code, canonical_code, notes, created, modified) " .
				"SELECT '%s', '%s', 'agenda.alias → atómicos visitas', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP " .
				"WHERE NOT EXISTS (SELECT 1 FROM rbac_permission_legacy_aliases e WHERE e.legacy_code = '%s' AND e.canonical_code = '%s')",
				$legacy,
				$canonical,
				$legacy,
				$canonical
			));
		}
	}

	public function down() {
		if ($this->hasTable('rbac_permissions')) {
			$this->execute(
				"UPDATE rbac_permissions SET controller = 'Agenda', " .
				"name = 'Agenda (rota /agenda)', " .
				"description = 'Alias de agenda se utilizado.' " .
				"WHERE code = 'agenda.alias'"
			);
		}
		if (!$this->hasTable('rbac_permission_legacy_aliases')) {
			return;
		}
		foreach ($this->_aliasPairs as $pair) {
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
