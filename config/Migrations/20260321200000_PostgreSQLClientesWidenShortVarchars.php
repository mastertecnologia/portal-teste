<?php
/**
 * Alarga todas as colunas character varying / bpchar em clientes com limite <= 12.
 * cnpj/token podem já ter sido corrigidos manualmente; esta migração cobre rg, fone, IE, etc.
 */
use Migrations\AbstractMigration;

class PostgreSQLClientesWidenShortVarchars extends AbstractMigration {

	public function up() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		if (!$this->hasTable('clientes')) {
			return;
		}

		$this->execute(
			"DO \$\$
DECLARE
  r RECORD;
  newlen int;
BEGIN
  FOR r IN
    SELECT column_name, character_maximum_length, data_type
    FROM information_schema.columns
    WHERE table_schema = 'public'
      AND table_name = 'clientes'
      AND character_maximum_length IS NOT NULL
      AND character_maximum_length <= 12
      AND data_type IN ('character varying', 'character')
  LOOP
    newlen := CASE lower(r.column_name)
      WHEN 'cnpj' THEN 18
      WHEN 'cpf' THEN 14
      WHEN 'cep' THEN 16
      WHEN 'fone' THEN 32
      WHEN 'fone2' THEN 32
      WHEN 'rg' THEN 32
      WHEN 'nroendereco' THEN 32
      WHEN 'token' THEN 128
      WHEN 'inscricaoestadual' THEN 32
      WHEN 'inscricaomunicipal' THEN 32
      WHEN 'membrodesde' THEN 32
      ELSE 64
    END;
    EXECUTE format(
      'ALTER TABLE public.clientes ALTER COLUMN %I TYPE VARCHAR(%s)',
      r.column_name,
      newlen
    );
  END LOOP;
END \$\$"
		);
	}

	public function down() {
	}
}
