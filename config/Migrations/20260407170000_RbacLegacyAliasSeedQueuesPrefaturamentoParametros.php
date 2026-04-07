<?php
use Migrations\AbstractMigration;

/**
 * Aliases: prefaturamento.manage, queues.admin, bancosenhas.manage, feriados.manage, contratos.horas,
 * problemas.os_tipos, areas.os_status, tickets.api (parcial — vê nota no IMPLEMENTATION_LOG).
 */
class RbacLegacyAliasSeedQueuesPrefaturamentoParametros extends AbstractMigration {

	/** @var array<int, array{0:string,1:string}> */
	protected $_pairs = [
		['prefaturamento.manage', 'prefaturamento.queue'],
		['prefaturamento.manage', 'prefaturamento.conferencia'],
		['queues.admin', 'queues.admin.panel'],
		['queues.admin', 'queues.admin.form'],
		['queues.admin', 'queues.admin.technicians'],
		['queues.admin', 'queues.admin.delete'],
		['bancosenhas.manage', 'bancosenhas.view'],
		['bancosenhas.manage', 'bancosenhas.create'],
		['bancosenhas.manage', 'bancosenhas.update'],
		['bancosenhas.manage', 'bancosenhas.reveal'],
		['bancosenhas.manage', 'bancosenhas.delete'],
		['feriados.manage', 'feriados.view'],
		['feriados.manage', 'feriados.create'],
		['feriados.manage', 'feriados.update'],
		['feriados.manage', 'feriados.delete'],
		['contratos.horas', 'contratos.horas.view'],
		['contratos.horas', 'contratos.horas.create'],
		['contratos.horas', 'contratos.horas.update'],
		['contratos.horas', 'contratos.horas.delete'],
		['problemas.os_tipos', 'problemas.tipos.view'],
		['problemas.os_tipos', 'problemas.tipos.create'],
		['problemas.os_tipos', 'problemas.tipos.update'],
		['problemas.os_tipos', 'problemas.tipos.delete'],
		['areas.os_status', 'areas.status.view'],
		['areas.os_status', 'areas.status.create'],
		['areas.os_status', 'areas.status.update'],
		['areas.os_status', 'areas.status.delete'],
		['tickets.api', 'tickets.view'],
		['tickets.api', 'tickets.create'],
		['tickets.api', 'tickets.update'],
		['tickets.api', 'tickets.delete'],
		['tickets.api', 'tickets.assign'],
		['tickets.api', 'tickets.timer'],
		['tickets.api', 'tickets.email'],
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
				"SELECT '%s', '%s', 'Fase 2e — filas/pré-faturamento/parâmetros/tickets.api', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP " .
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
