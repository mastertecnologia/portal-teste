<?php
/**
 * Anexos do lançamento financeiro (detalhe da fatura).
 *
 * Rodar: bin/cake migrations migrate
 */
use Migrations\AbstractMigration;

class FinanceiroLancamentoAnexos extends AbstractMigration {

	public function up() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}

		if (!$this->hasTable('financeiro_lancamento_anexos')) {
			$this->execute(<<<'SQL'
CREATE TABLE financeiro_lancamento_anexos (
	id               SERIAL PRIMARY KEY,
	idlancamento     INTEGER NOT NULL,
	idempresa        INTEGER NOT NULL,
	arquivo          VARCHAR(255) NOT NULL,
	nome_original    VARCHAR(255) NOT NULL,
	iduser           INTEGER NULL,
	created          TIMESTAMP WITHOUT TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_fin_anex_idlanc ON financeiro_lancamento_anexos (idlancamento);
CREATE INDEX IF NOT EXISTS idx_fin_anex_idemp  ON financeiro_lancamento_anexos (idempresa);
SQL
			);
		}
	}

	public function down() {
		// Não destrói dados em produção via rollback automático
	}
}
