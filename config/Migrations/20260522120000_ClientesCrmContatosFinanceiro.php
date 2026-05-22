<?php
/**
 * CRM Clientes: contatos múltiplos + limite de crédito / score interno.
 *
 * PostgreSQL apenas. Rodar: bin/cake migrations migrate
 */
use Migrations\AbstractMigration;

class ClientesCrmContatosFinanceiro extends AbstractMigration {

	public function up() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}

		if ($this->hasTable('clientes')) {
			$t = $this->table('clientes');
			if (!$t->hasColumn('limite_credito')) {
				$this->execute('ALTER TABLE clientes ADD COLUMN limite_credito NUMERIC(15,2) NULL');
			}
			if (!$t->hasColumn('score_interno')) {
				$this->execute('ALTER TABLE clientes ADD COLUMN score_interno NUMERIC(4,2) NULL');
			}
			if (!$t->hasColumn('observacoes_financeiras')) {
				$this->execute('ALTER TABLE clientes ADD COLUMN observacoes_financeiras TEXT NULL');
			}
		}

		if (!$this->hasTable('clientes_contatos')) {
			$this->execute(<<<'SQL'
CREATE TABLE clientes_contatos (
	id SERIAL PRIMARY KEY,
	idcliente INTEGER NOT NULL,
	idempresa INTEGER NOT NULL,
	nome VARCHAR(120) NOT NULL,
	cargo VARCHAR(80) NULL,
	email VARCHAR(255) NULL,
	fone VARCHAR(30) NULL,
	principal BOOLEAN NOT NULL DEFAULT FALSE,
	created TIMESTAMP WITHOUT TIME ZONE NULL,
	modified TIMESTAMP WITHOUT TIME ZONE NULL
);
CREATE INDEX IF NOT EXISTS idx_clientes_contatos_idcliente ON clientes_contatos (idcliente);
CREATE INDEX IF NOT EXISTS idx_clientes_contatos_idempresa ON clientes_contatos (idempresa);
SQL
			);
			if ($this->hasTable('clientes')) {
				try {
					$this->execute(
						'ALTER TABLE clientes_contatos ADD CONSTRAINT fk_clientes_contatos_cliente FOREIGN KEY (idcliente) REFERENCES clientes (id) ON DELETE CASCADE'
					);
				} catch (\Throwable $e) {
				}
			}
			if ($this->hasTable('empresas')) {
				try {
					$this->execute(
						'ALTER TABLE clientes_contatos ADD CONSTRAINT fk_clientes_contatos_empresa FOREIGN KEY (idempresa) REFERENCES empresas (id) ON DELETE CASCADE'
					);
				} catch (\Throwable $e) {
				}
			}
			$this->execute(<<<'SQL'
INSERT INTO clientes_contatos (idcliente, idempresa, nome, cargo, fone, principal, created, modified)
SELECT c.id, c.idempresa, TRIM(c.nomeresponsavel), 'Representante legal', COALESCE(NULLIF(TRIM(c.fone2), ''), NULLIF(TRIM(c.fone), '')), TRUE, NOW(), NOW()
FROM clientes c
WHERE TRIM(COALESCE(c.nomeresponsavel, '')) <> ''
  AND NOT EXISTS (SELECT 1 FROM clientes_contatos cc WHERE cc.idcliente = c.id AND cc.principal = TRUE);
SQL
			);
		}
	}

	public function down() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		if ($this->hasTable('clientes_contatos')) {
			$this->execute('DROP TABLE IF EXISTS clientes_contatos CASCADE');
		}
		if ($this->hasTable('clientes')) {
			$t = $this->table('clientes');
			if ($t->hasColumn('observacoes_financeiras')) {
				$this->execute('ALTER TABLE clientes DROP COLUMN IF EXISTS observacoes_financeiras');
			}
			if ($t->hasColumn('score_interno')) {
				$this->execute('ALTER TABLE clientes DROP COLUMN IF EXISTS score_interno');
			}
			if ($t->hasColumn('limite_credito')) {
				$this->execute('ALTER TABLE clientes DROP COLUMN IF EXISTS limite_credito');
			}
		}
	}
}
