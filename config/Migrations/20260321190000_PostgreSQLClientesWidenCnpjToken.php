<?php
/**
 * Corrige VARCHAR(12) insuficiente em clientes: CNPJ tem 14 dígitos; token (SHA1 binário) tem 20 bytes.
 * Erro típico: SQLSTATE[22001] value too long for type character varying(12)
 */
use Migrations\AbstractMigration;

class PostgreSQLClientesWidenCnpjToken extends AbstractMigration {

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
  ml int;
BEGIN
  SELECT character_maximum_length INTO ml
  FROM information_schema.columns
  WHERE table_schema = 'public' AND table_name = 'clientes' AND column_name = 'cnpj';
  IF ml IS NOT NULL AND ml < 18 THEN
    EXECUTE 'ALTER TABLE public.clientes ALTER COLUMN cnpj TYPE varchar(18)';
  END IF;
END \$\$"
		);

		$this->execute(
			"DO \$\$
DECLARE
  ml int;
BEGIN
  SELECT character_maximum_length INTO ml
  FROM information_schema.columns
  WHERE table_schema = 'public' AND table_name = 'clientes' AND column_name = 'token';
  IF ml IS NOT NULL AND ml < 128 THEN
    EXECUTE 'ALTER TABLE public.clientes ALTER COLUMN token TYPE varchar(128)';
  END IF;
END \$\$"
		);
	}

	public function down() {
	}
}
