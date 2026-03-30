<?php
/**
 * Módulo Faturamento + Financeiro
 *
 * Fluxo: Orçamento → OS → Pré-faturamento → Faturamento → Financeiro
 *
 * Tabelas criadas:
 *   faturamento         — documentos de cobrança (NF-e, boleto, etc.)
 *   faturamento_itens   — itens/serviços do documento
 *   financeiro_lancamentos — lançamentos financeiros (receitas e despesas)
 *
 * Rodar: bin/cake migrations migrate
 */
use Migrations\AbstractMigration;

class FaturamentoFinanceiro extends AbstractMigration {

	public function up() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}

		/* ── faturamento ─────────────────────────────────────────────────── */
		if (!$this->hasTable('faturamento')) {
			$this->execute(<<<'SQL'
CREATE TABLE faturamento (
	id              SERIAL PRIMARY KEY,
	idempresa       INTEGER NOT NULL,
	idcliente       INTEGER NOT NULL,
	idordem         INTEGER NULL,       -- FK ordensservico
	idorcamento     INTEGER NULL,       -- FK orcamentosnovosdes
	numero          VARCHAR(30)  NULL,
	tipo            VARCHAR(30)  NOT NULL DEFAULT 'servico',
	-- tipos: servico | locacao | produto | misto
	status          VARCHAR(30)  NOT NULL DEFAULT 'rascunho',
	-- status: rascunho | pendente | enviado | pago | cancelado
	data_emissao    DATE         NOT NULL,
	data_vencimento DATE         NULL,
	valor_subtotal  NUMERIC(14,2) NOT NULL DEFAULT 0,
	valor_desconto  NUMERIC(14,2) NOT NULL DEFAULT 0,
	valor_total     NUMERIC(14,2) NOT NULL DEFAULT 0,
	descricao       TEXT NULL,
	observacoes     TEXT NULL,
	hash            VARCHAR(40)  NULL,
	idautor         INTEGER NULL,
	created         TIMESTAMP WITHOUT TIME ZONE NULL,
	modified        TIMESTAMP WITHOUT TIME ZONE NULL
);
CREATE INDEX IF NOT EXISTS idx_faturamento_idempresa ON faturamento (idempresa);
CREATE INDEX IF NOT EXISTS idx_faturamento_idcliente ON faturamento (idcliente);
CREATE INDEX IF NOT EXISTS idx_faturamento_idordem   ON faturamento (idordem);
CREATE INDEX IF NOT EXISTS idx_faturamento_status    ON faturamento (status);
SQL
			);
		} else {
			$alters = [
				'ALTER TABLE faturamento ADD COLUMN IF NOT EXISTS idordem INTEGER NULL',
				'ALTER TABLE faturamento ADD COLUMN IF NOT EXISTS idorcamento INTEGER NULL',
				'ALTER TABLE faturamento ADD COLUMN IF NOT EXISTS numero VARCHAR(30) NULL',
				'ALTER TABLE faturamento ADD COLUMN IF NOT EXISTS tipo VARCHAR(30) NOT NULL DEFAULT \'servico\'',
				'ALTER TABLE faturamento ADD COLUMN IF NOT EXISTS status VARCHAR(30) NOT NULL DEFAULT \'rascunho\'',
				'ALTER TABLE faturamento ADD COLUMN IF NOT EXISTS data_emissao DATE NOT NULL DEFAULT CURRENT_DATE',
				'ALTER TABLE faturamento ADD COLUMN IF NOT EXISTS data_vencimento DATE NULL',
				'ALTER TABLE faturamento ADD COLUMN IF NOT EXISTS valor_subtotal NUMERIC(14,2) NOT NULL DEFAULT 0',
				'ALTER TABLE faturamento ADD COLUMN IF NOT EXISTS valor_desconto NUMERIC(14,2) NOT NULL DEFAULT 0',
				'ALTER TABLE faturamento ADD COLUMN IF NOT EXISTS valor_total NUMERIC(14,2) NOT NULL DEFAULT 0',
				'ALTER TABLE faturamento ADD COLUMN IF NOT EXISTS descricao TEXT NULL',
				'ALTER TABLE faturamento ADD COLUMN IF NOT EXISTS observacoes TEXT NULL',
				'ALTER TABLE faturamento ADD COLUMN IF NOT EXISTS hash VARCHAR(40) NULL',
				'ALTER TABLE faturamento ADD COLUMN IF NOT EXISTS idautor INTEGER NULL',
				'ALTER TABLE faturamento ADD COLUMN IF NOT EXISTS created TIMESTAMP WITHOUT TIME ZONE NULL',
				'ALTER TABLE faturamento ADD COLUMN IF NOT EXISTS modified TIMESTAMP WITHOUT TIME ZONE NULL',
			];
			foreach ($alters as $sql) {
				$this->execute($sql);
			}
		}

		/* ── faturamento_itens ───────────────────────────────────────────── */
		if (!$this->hasTable('faturamento_itens')) {
			$this->execute(<<<'SQL'
CREATE TABLE faturamento_itens (
	id              SERIAL PRIMARY KEY,
	idfaturamento   INTEGER NOT NULL,
	descricao       VARCHAR(255) NOT NULL,
	quantidade      NUMERIC(14,4) NOT NULL DEFAULT 1,
	valor_unitario  NUMERIC(14,4) NOT NULL DEFAULT 0,
	valor_total     NUMERIC(14,4) NOT NULL DEFAULT 0,
	created         TIMESTAMP WITHOUT TIME ZONE NULL,
	modified        TIMESTAMP WITHOUT TIME ZONE NULL
);
CREATE INDEX IF NOT EXISTS idx_faturamento_itens_idfat ON faturamento_itens (idfaturamento);
SQL
			);
		}

		/* ── financeiro_lancamentos ──────────────────────────────────────── */
		if (!$this->hasTable('financeiro_lancamentos')) {
			$this->execute(<<<'SQL'
CREATE TABLE financeiro_lancamentos (
	id                SERIAL PRIMARY KEY,
	idempresa         INTEGER NOT NULL,
	idcliente         INTEGER NOT NULL,
	idfaturamento     INTEGER NULL,       -- FK faturamento
	tipo              VARCHAR(20) NOT NULL DEFAULT 'receita',
	-- tipos: receita | despesa
	descricao         VARCHAR(255) NOT NULL,
	valor             NUMERIC(14,2) NOT NULL DEFAULT 0,
	data_lancamento   DATE NOT NULL,
	data_vencimento   DATE NULL,
	data_recebimento  DATE NULL,
	status            VARCHAR(20) NOT NULL DEFAULT 'aberto',
	-- status: aberto | recebido | pago | vencido | cancelado
	observacoes       TEXT NULL,
	idautor           INTEGER NULL,
	created           TIMESTAMP WITHOUT TIME ZONE NULL,
	modified          TIMESTAMP WITHOUT TIME ZONE NULL
);
CREATE INDEX IF NOT EXISTS idx_financeiro_idempresa     ON financeiro_lancamentos (idempresa);
CREATE INDEX IF NOT EXISTS idx_financeiro_idcliente     ON financeiro_lancamentos (idcliente);
CREATE INDEX IF NOT EXISTS idx_financeiro_idfaturamento ON financeiro_lancamentos (idfaturamento);
CREATE INDEX IF NOT EXISTS idx_financeiro_status        ON financeiro_lancamentos (status);
CREATE INDEX IF NOT EXISTS idx_financeiro_data_venc     ON financeiro_lancamentos (data_vencimento);
SQL
			);
		}
	}

	public function down() {
		// Não destrói dados em produção via rollback automático
	}
}
