<?php
/**
 * Complementa tabelas queues / queues_users quando já existiam sem todas as colunas
 * (ex.: criadas manualmente). Destinado principalmente ao PostgreSQL.
 */
use Migrations\AbstractMigration;

class PostgreSQLQueuesSchemaPatch extends AbstractMigration {

	public function up() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		if (!$this->hasTable('queues')) {
			return;
		}

		$this->execute('ALTER TABLE public.queues ADD COLUMN IF NOT EXISTS name varchar(255)');
		$this->execute("UPDATE public.queues SET name = 'Fila' WHERE name IS NULL OR trim(name) = ''");
		$this->execute('ALTER TABLE public.queues ALTER COLUMN name SET NOT NULL');

		$this->execute('ALTER TABLE public.queues ADD COLUMN IF NOT EXISTS idempresa bigint');
		$this->execute(
			'UPDATE public.queues q SET idempresa = sub.m FROM (SELECT MIN(id) AS m FROM public.empresas) sub '
			. 'WHERE q.idempresa IS NULL AND sub.m IS NOT NULL'
		);
		$this->execute('UPDATE public.queues SET idempresa = 1 WHERE idempresa IS NULL');
		$this->execute('ALTER TABLE public.queues ALTER COLUMN idempresa SET NOT NULL');

		$this->execute('ALTER TABLE public.queues ADD COLUMN IF NOT EXISTS codigo varchar(32) NULL');
		$this->execute('ALTER TABLE public.queues ADD COLUMN IF NOT EXISTS sort_order integer NOT NULL DEFAULT 0');
		$this->execute('ALTER TABLE public.queues ADD COLUMN IF NOT EXISTS created timestamptz NULL');
		$this->execute('ALTER TABLE public.queues ADD COLUMN IF NOT EXISTS modified timestamptz NULL');

		$this->execute('CREATE INDEX IF NOT EXISTS ix_queues_idempresa ON public.queues (idempresa)');

		$this->execute(
			'DO $$
BEGIN
	ALTER TABLE public.queues ADD CONSTRAINT queues_idempresa_fkey '
			. 'FOREIGN KEY (idempresa) REFERENCES public.empresas (id) ON UPDATE CASCADE ON DELETE RESTRICT;
EXCEPTION
	WHEN duplicate_object THEN NULL;
	WHEN undefined_table THEN NULL;
END $$'
		);

		if ($this->hasTable('queues_users')) {
			$this->execute('ALTER TABLE public.queues_users ADD COLUMN IF NOT EXISTS created timestamptz NULL');
			$this->execute('ALTER TABLE public.queues_users ADD COLUMN IF NOT EXISTS modified timestamptz NULL');
		}
	}

	public function down() {
	}
}
