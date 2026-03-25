<?php
/**
 * contratos_horas: colunas do formulário ContratosHoras (add/edit) + campos usados pelo timer
 * (subtrairHorasContrato) e pelo ContratoHorasService. Restrição única (idcliente, idempresa).
 *
 * Rodar: bin/cake migrations migrate
 *
 * Se falhar ao criar o UNIQUE, há mais de uma linha para o mesmo (idcliente, idempresa).
 * Ver comentário opcional de deduplicação em config/sql/create_contratos_horas.sql
 */
use Migrations\AbstractMigration;

class ContratosHorasCompleto extends AbstractMigration {

	public function up() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		if (!$this->hasTable('contratos_horas')) {
			$this->execute(<<<'SQL'
CREATE TABLE contratos_horas (
	id SERIAL PRIMARY KEY,
	idcliente INTEGER NOT NULL,
	idempresa INTEGER NOT NULL,
	minutos_contratados INTEGER NOT NULL DEFAULT 0,
	minutos_consumidos INTEGER NOT NULL DEFAULT 0,
	data_inicio DATE NULL,
	data_fim DATE NULL,
	horas_contratadas NUMERIC(14,4) NULL,
	saldo_horas NUMERIC(14,4) NULL,
	horas_utilizadas NUMERIC(14,4) NOT NULL DEFAULT 0,
	ativo BOOLEAN NOT NULL DEFAULT TRUE,
	valor_hora_comercial NUMERIC(14,4) NULL,
	valor_hora_adicional_comercial NUMERIC(14,4) NULL,
	valor_hora_especial NUMERIC(14,4) NULL,
	contatos_email_relatorio TEXT NULL,
	segundos_consumidos BIGINT NULL,
	horas_consumidas NUMERIC(14,4) NULL,
	saldo NUMERIC(14,4) NULL,
	saldo_minutos INTEGER NULL,
	created TIMESTAMP WITHOUT TIME ZONE NULL,
	modified TIMESTAMP WITHOUT TIME ZONE NULL,
	CONSTRAINT contratos_horas_idcliente_idempresa_key UNIQUE (idcliente, idempresa)
);
CREATE INDEX IF NOT EXISTS idx_contratos_horas_idcliente ON contratos_horas (idcliente);
CREATE INDEX IF NOT EXISTS idx_contratos_horas_idempresa ON contratos_horas (idempresa);
SQL
			);

			return;
		}

		$alters = [
			'ALTER TABLE contratos_horas ADD COLUMN IF NOT EXISTS data_inicio DATE NULL',
			'ALTER TABLE contratos_horas ADD COLUMN IF NOT EXISTS data_fim DATE NULL',
			'ALTER TABLE contratos_horas ADD COLUMN IF NOT EXISTS horas_contratadas NUMERIC(14,4) NULL',
			'ALTER TABLE contratos_horas ADD COLUMN IF NOT EXISTS saldo_horas NUMERIC(14,4) NULL',
			'ALTER TABLE contratos_horas ADD COLUMN IF NOT EXISTS horas_utilizadas NUMERIC(14,4) NOT NULL DEFAULT 0',
			'ALTER TABLE contratos_horas ADD COLUMN IF NOT EXISTS ativo BOOLEAN NOT NULL DEFAULT TRUE',
			'ALTER TABLE contratos_horas ADD COLUMN IF NOT EXISTS valor_hora_comercial NUMERIC(14,4) NULL',
			'ALTER TABLE contratos_horas ADD COLUMN IF NOT EXISTS valor_hora_adicional_comercial NUMERIC(14,4) NULL',
			'ALTER TABLE contratos_horas ADD COLUMN IF NOT EXISTS valor_hora_especial NUMERIC(14,4) NULL',
			'ALTER TABLE contratos_horas ADD COLUMN IF NOT EXISTS contatos_email_relatorio TEXT NULL',
			'ALTER TABLE contratos_horas ADD COLUMN IF NOT EXISTS segundos_consumidos BIGINT NULL',
			'ALTER TABLE contratos_horas ADD COLUMN IF NOT EXISTS horas_consumidas NUMERIC(14,4) NULL',
			'ALTER TABLE contratos_horas ADD COLUMN IF NOT EXISTS saldo NUMERIC(14,4) NULL',
			'ALTER TABLE contratos_horas ADD COLUMN IF NOT EXISTS saldo_minutos INTEGER NULL',
			'ALTER TABLE contratos_horas ADD COLUMN IF NOT EXISTS created TIMESTAMP WITHOUT TIME ZONE NULL',
			'ALTER TABLE contratos_horas ADD COLUMN IF NOT EXISTS modified TIMESTAMP WITHOUT TIME ZONE NULL',
		];
		foreach ($alters as $sql) {
			$this->execute($sql);
		}
		$this->execute('CREATE INDEX IF NOT EXISTS idx_contratos_horas_idcliente ON contratos_horas (idcliente)');
		$this->execute('CREATE INDEX IF NOT EXISTS idx_contratos_horas_idempresa ON contratos_horas (idempresa)');

		$this->execute(<<<'SQL'
DO $$
BEGIN
	IF NOT EXISTS (
		SELECT 1
		FROM pg_constraint c
		JOIN pg_class t ON t.oid = c.conrelid
		JOIN pg_namespace n ON n.oid = t.relnamespace
		WHERE n.nspname = 'public'
			AND t.relname = 'contratos_horas'
			AND c.conname = 'contratos_horas_idcliente_idempresa_key'
	) THEN
		ALTER TABLE public.contratos_horas
			ADD CONSTRAINT contratos_horas_idcliente_idempresa_key UNIQUE (idcliente, idempresa);
	END IF;
END $$;
SQL
		);
	}

	public function down() {
	}
}
