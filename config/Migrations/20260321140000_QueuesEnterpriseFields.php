<?php
/**
 * Filas enterprise: tipo, nível operacional, hierarquia opcional, ativo.
 * Backfill a partir de codigo/support_levels; insere filas adicionais por empresa (idempotente).
 */
use Migrations\AbstractMigration;

class QueuesEnterpriseFields extends AbstractMigration {

	public function up() {
		if (!$this->hasTable('queues')) {
			return;
		}
		$adapter = $this->getAdapter()->getAdapterType();
		if ($adapter === 'pgsql') {
			$this->_upPgsql();
		} else {
			$this->_upGeneric();
		}
	}

	protected function _upPgsql(): void {
		$this->execute("ALTER TABLE queues ADD COLUMN IF NOT EXISTS nivel_da_fila VARCHAR(32) NULL");
		$this->execute("ALTER TABLE queues ADD COLUMN IF NOT EXISTS tipo_fila VARCHAR(32) NOT NULL DEFAULT 'incidente'");
		$this->execute('ALTER TABLE queues ADD COLUMN IF NOT EXISTS ativo BOOLEAN NOT NULL DEFAULT true');
		$this->execute('ALTER TABLE queues ADD COLUMN IF NOT EXISTS fila_pai_id INTEGER NULL');
		$this->execute('ALTER TABLE queues ADD COLUMN IF NOT EXISTS proxima_fila_id INTEGER NULL');

		$this->execute(
			'DO $$
BEGIN
	ALTER TABLE queues ADD CONSTRAINT fk_queues_fila_pai FOREIGN KEY (fila_pai_id) REFERENCES queues(id) ON DELETE SET NULL ON UPDATE CASCADE;
EXCEPTION WHEN duplicate_object THEN NULL; END $$'
		);
		$this->execute(
			'DO $$
BEGIN
	ALTER TABLE queues ADD CONSTRAINT fk_queues_proxima_fila FOREIGN KEY (proxima_fila_id) REFERENCES queues(id) ON DELETE SET NULL ON UPDATE CASCADE;
EXCEPTION WHEN duplicate_object THEN NULL; END $$'
		);

		$this->execute(
			"UPDATE queues SET tipo_fila = CASE LOWER(TRIM(codigo))
				WHEN 'noc' THEN 'evento'
				WHEN 'servico' THEN 'requisicao'
				WHEN 'mudanca' THEN 'mudanca'
				ELSE 'incidente'
			END"
		);

		if ($this->hasTable('support_levels')) {
			$this->execute(
				'UPDATE queues q SET nivel_da_fila = CASE sl.sort_order
					WHEN 1 THEN \'Operacional\'
					WHEN 2 THEN \'Técnico\'
					WHEN 3 THEN \'Especialista\'
					WHEN 4 THEN \'Monitoramento\'
					WHEN 5 THEN \'Atendimento\'
					ELSE q.nivel_da_fila
				END
				FROM support_levels sl
				WHERE q.support_level_id = sl.id AND (q.nivel_da_fila IS NULL OR TRIM(q.nivel_da_fila) = \'\')'
			);
		}

		$this->_seedExtraQueuesPgsql();
	}

	protected function _seedExtraQueuesPgsql(): void {
		if (!$this->hasTable('empresas')) {
			return;
		}
		$sl2 = '(SELECT id FROM support_levels WHERE sort_order = 2 ORDER BY id LIMIT 1)';
		$sl3 = '(SELECT id FROM support_levels WHERE sort_order = 3 ORDER BY id LIMIT 1)';
		$sl5 = '(SELECT id FROM support_levels WHERE sort_order = 5 ORDER BY id LIMIT 1)';
		$hasSl = $this->hasTable('support_levels');
		$sl2expr = $hasSl ? $sl2 : 'NULL';
		$sl3expr = $hasSl ? $sl3 : 'NULL';
		$sl5expr = $hasSl ? $sl5 : 'NULL';

		$sql = "
			INSERT INTO queues (name, idempresa, codigo, sort_order, tipo_fila, nivel_da_fila, ativo, created, modified, support_level_id)
			SELECT v.name, e.id, v.codigo, v.sort_order, v.tipo_fila, v.nivel_da_fila, true, NOW(), NOW(), CASE v.sl
				WHEN 2 THEN {$sl2expr}
				WHEN 3 THEN {$sl3expr}
				WHEN 5 THEN {$sl5expr}
				ELSE NULL
			END
			FROM empresas e
			CROSS JOIN (VALUES
				('N2 — Suporte avançado (remoto)', 'n2_remoto', 21, 'incidente', 'Técnico', 2),
				('N2 — Field Service (presencial)', 'n2_field', 22, 'incidente', 'Técnico', 2),
				('N3 — Infraestrutura', 'n3_infra', 31, 'incidente', 'Especialista', 3),
				('N3 — Especialistas', 'n3_especialistas', 32, 'incidente', 'Especialista', 3),
				('Gestão de mudanças', 'mudanca', 60, 'mudanca', 'Governança', 5)
			) AS v(name, codigo, sort_order, tipo_fila, nivel_da_fila, sl)
			WHERE NOT EXISTS (SELECT 1 FROM queues q WHERE q.idempresa = e.id AND LOWER(TRIM(q.codigo)) = LOWER(TRIM(v.codigo)))
		";
		if ($hasSl) {
			$this->execute($sql);
		} else {
			$sqlNoSl = "
				INSERT INTO queues (name, idempresa, codigo, sort_order, tipo_fila, nivel_da_fila, ativo, created, modified)
				SELECT v.name, e.id, v.codigo, v.sort_order, v.tipo_fila, v.nivel_da_fila, true, NOW(), NOW()
				FROM empresas e
				CROSS JOIN (VALUES
					('N2 — Suporte avançado (remoto)', 'n2_remoto', 21, 'incidente', 'Técnico'),
					('N2 — Field Service (presencial)', 'n2_field', 22, 'incidente', 'Técnico'),
					('N3 — Infraestrutura', 'n3_infra', 31, 'incidente', 'Especialista'),
					('N3 — Especialistas', 'n3_especialistas', 32, 'incidente', 'Especialista'),
					('Gestão de mudanças', 'mudanca', 60, 'mudanca', 'Governança')
				) AS v(name, codigo, sort_order, tipo_fila, nivel_da_fila)
				WHERE NOT EXISTS (SELECT 1 FROM queues q WHERE q.idempresa = e.id AND LOWER(TRIM(q.codigo)) = LOWER(TRIM(v.codigo)))
			";
			$this->execute($sqlNoSl);
		}
	}

	protected function _upGeneric(): void {
		$qt = $this->table('queues');
		if (!$qt->hasColumn('nivel_da_fila')) {
			$this->table('queues')->addColumn('nivel_da_fila', 'string', ['limit' => 32, 'null' => true])->update();
		}
		if (!$qt->hasColumn('tipo_fila')) {
			$this->table('queues')->addColumn('tipo_fila', 'string', ['limit' => 32, 'null' => false, 'default' => 'incidente'])->update();
		}
		if (!$qt->hasColumn('ativo')) {
			$this->table('queues')->addColumn('ativo', 'boolean', ['null' => false, 'default' => true])->update();
		}
		if (!$qt->hasColumn('fila_pai_id')) {
			$this->table('queues')->addColumn('fila_pai_id', 'integer', ['null' => true])->update();
		}
		if (!$qt->hasColumn('proxima_fila_id')) {
			$this->table('queues')->addColumn('proxima_fila_id', 'integer', ['null' => true])->update();
		}
	}

	public function down() {
	}
}
