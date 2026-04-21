<?php
/**
 * Código público por tenant: public_code único (idempresa, public_code),
 * sequência portal P######## via tabela clientes_public_code_seq.
 *
 * PostgreSQL apenas (padrão do projeto). Outros drivers: no-op.
 */
use Migrations\AbstractMigration;

class ClientesPublicCode extends AbstractMigration {

	public function up() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		if (!$this->hasTable('clientes')) {
			return;
		}

		$t = $this->table('clientes');
		if (!$t->hasColumn('public_code')) {
			$this->execute('ALTER TABLE clientes ADD COLUMN public_code VARCHAR(32) NULL');
		}

		if (!$this->hasTable('clientes_public_code_seq')) {
			$this->execute(<<<'SQL'
CREATE TABLE clientes_public_code_seq (
	idempresa INTEGER NOT NULL PRIMARY KEY,
	next_val BIGINT NOT NULL DEFAULT 1
);
SQL
			);
			if ($this->hasTable('empresas')) {
				try {
					$this->execute(
						'ALTER TABLE clientes_public_code_seq ADD CONSTRAINT fk_clientes_public_code_seq_empresa FOREIGN KEY (idempresa) REFERENCES empresas (id) ON DELETE CASCADE'
					);
				} catch (\Throwable $e) {
				}
			}
		}

		$this->execute(<<<'SQL'
WITH ranked AS (
	SELECT id, idempresa,
		ROW_NUMBER() OVER (PARTITION BY idempresa ORDER BY id) AS rn
	FROM clientes
	WHERE public_code IS NULL
)
UPDATE clientes c
SET public_code = 'P' || LPAD(ranked.rn::text, 8, '0')
FROM ranked
WHERE c.id = ranked.id;
SQL
		);

		$this->execute(
			'CREATE UNIQUE INDEX IF NOT EXISTS uq_clientes_idempresa_public_code ON clientes (idempresa, public_code)'
		);

		$this->execute('ALTER TABLE clientes ALTER COLUMN public_code SET NOT NULL');

		$this->execute(<<<'SQL'
INSERT INTO clientes_public_code_seq (idempresa, next_val)
SELECT c.idempresa,
	COALESCE(MAX(
		CASE
			WHEN c.public_code ~ '^P[0-9]{8}$' THEN SUBSTRING(c.public_code FROM 2)::bigint
		END
	), 0)
FROM clientes c
GROUP BY c.idempresa
ON CONFLICT (idempresa) DO UPDATE SET next_val = EXCLUDED.next_val;
SQL
		);
	}

	public function down() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		if ($this->hasTable('clientes')) {
			try {
				$this->execute('DROP INDEX IF EXISTS uq_clientes_idempresa_public_code');
			} catch (\Throwable $e) {
			}
			$t = $this->table('clientes');
			if ($t->hasColumn('public_code')) {
				$this->execute('ALTER TABLE clientes DROP COLUMN public_code');
			}
		}
		if ($this->hasTable('clientes_public_code_seq')) {
			$this->execute('DROP TABLE clientes_public_code_seq');
		}
	}
}
