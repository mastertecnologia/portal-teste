<?php
use Migrations\AbstractMigration;

/**
 * Módulo Financeiro > Bancos
 *
 * Cria a tabela `financeiro_bancos` e garante a coluna
 * `financeiro_banco_id` em `financeiro_lancamentos`.
 */
class FinanceiroBancosModule extends AbstractMigration
{
    public function up()
    {
        if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
            return;
        }

        if (!$this->hasTable('financeiro_bancos')) {
            $this->execute(<<<'SQL'
CREATE TABLE financeiro_bancos (
    id                    SERIAL PRIMARY KEY,
    idempresa             INTEGER NOT NULL,
    codigo_banco          VARCHAR(10) NULL,
    numero_banco          VARCHAR(10) NULL,
    cnab                  VARCHAR(10) NULL,
    nome                  VARCHAR(255) NOT NULL,
    numero_agencia        VARCHAR(20) NULL,
    digito_agencia        VARCHAR(5) NULL,
    numero_conta          VARCHAR(30) NULL,
    digito_conta          VARCHAR(5) NULL,
    codigo_banco_interno  VARCHAR(50) NULL,
    verifica_receber      VARCHAR(100) NULL,
    utiliza_endosso       VARCHAR(10) NULL,
    logotipo              VARCHAR(255) NULL,
    observacoes           TEXT NULL,
    ativo                 BOOLEAN NOT NULL DEFAULT TRUE,
    created               TIMESTAMP WITHOUT TIME ZONE NULL,
    modified              TIMESTAMP WITHOUT TIME ZONE NULL
);

CREATE INDEX IF NOT EXISTS idx_financeiro_bancos_idempresa
    ON financeiro_bancos (idempresa);

CREATE INDEX IF NOT EXISTS idx_financeiro_bancos_codigo
    ON financeiro_bancos (codigo_banco);

CREATE INDEX IF NOT EXISTS idx_financeiro_bancos_nome
    ON financeiro_bancos (nome);

CREATE INDEX IF NOT EXISTS idx_financeiro_bancos_ativo
    ON financeiro_bancos (ativo);

CREATE UNIQUE INDEX IF NOT EXISTS uq_financeiro_bancos_empresa_conta
    ON financeiro_bancos (
        idempresa,
        COALESCE(codigo_banco, ''),
        COALESCE(numero_agencia, ''),
        COALESCE(digito_agencia, ''),
        COALESCE(numero_conta, ''),
        COALESCE(digito_conta, '')
    );
SQL
            );
        } else {
            $alters = [
                "ALTER TABLE financeiro_bancos ADD COLUMN IF NOT EXISTS idempresa INTEGER NOT NULL DEFAULT 0",
                "ALTER TABLE financeiro_bancos ADD COLUMN IF NOT EXISTS codigo_banco VARCHAR(10) NULL",
                "ALTER TABLE financeiro_bancos ADD COLUMN IF NOT EXISTS numero_banco VARCHAR(10) NULL",
                "ALTER TABLE financeiro_bancos ADD COLUMN IF NOT EXISTS cnab VARCHAR(10) NULL",
                "ALTER TABLE financeiro_bancos ADD COLUMN IF NOT EXISTS nome VARCHAR(255) NULL",
                "ALTER TABLE financeiro_bancos ADD COLUMN IF NOT EXISTS numero_agencia VARCHAR(20) NULL",
                "ALTER TABLE financeiro_bancos ADD COLUMN IF NOT EXISTS digito_agencia VARCHAR(5) NULL",
                "ALTER TABLE financeiro_bancos ADD COLUMN IF NOT EXISTS numero_conta VARCHAR(30) NULL",
                "ALTER TABLE financeiro_bancos ADD COLUMN IF NOT EXISTS digito_conta VARCHAR(5) NULL",
                "ALTER TABLE financeiro_bancos ADD COLUMN IF NOT EXISTS codigo_banco_interno VARCHAR(50) NULL",
                "ALTER TABLE financeiro_bancos ADD COLUMN IF NOT EXISTS verifica_receber VARCHAR(100) NULL",
                "ALTER TABLE financeiro_bancos ADD COLUMN IF NOT EXISTS utiliza_endosso VARCHAR(10) NULL",
                "ALTER TABLE financeiro_bancos ADD COLUMN IF NOT EXISTS logotipo VARCHAR(255) NULL",
                "ALTER TABLE financeiro_bancos ADD COLUMN IF NOT EXISTS observacoes TEXT NULL",
                "ALTER TABLE financeiro_bancos ADD COLUMN IF NOT EXISTS ativo BOOLEAN NOT NULL DEFAULT TRUE",
                "ALTER TABLE financeiro_bancos ADD COLUMN IF NOT EXISTS created TIMESTAMP WITHOUT TIME ZONE NULL",
                "ALTER TABLE financeiro_bancos ADD COLUMN IF NOT EXISTS modified TIMESTAMP WITHOUT TIME ZONE NULL",
            ];

            foreach ($alters as $sql) {
                $this->execute($sql);
            }

            $this->execute("UPDATE financeiro_bancos SET nome = 'Banco sem nome' WHERE nome IS NULL");
            $this->execute("ALTER TABLE financeiro_bancos ALTER COLUMN nome SET NOT NULL");

            $this->execute("CREATE INDEX IF NOT EXISTS idx_financeiro_bancos_idempresa ON financeiro_bancos (idempresa)");
            $this->execute("CREATE INDEX IF NOT EXISTS idx_financeiro_bancos_codigo ON financeiro_bancos (codigo_banco)");
            $this->execute("CREATE INDEX IF NOT EXISTS idx_financeiro_bancos_nome ON financeiro_bancos (nome)");
            $this->execute("CREATE INDEX IF NOT EXISTS idx_financeiro_bancos_ativo ON financeiro_bancos (ativo)");
            $this->execute(<<<'SQL'
CREATE UNIQUE INDEX IF NOT EXISTS uq_financeiro_bancos_empresa_conta
    ON financeiro_bancos (
        idempresa,
        COALESCE(codigo_banco, ''),
        COALESCE(numero_agencia, ''),
        COALESCE(digito_agencia, ''),
        COALESCE(numero_conta, ''),
        COALESCE(digito_conta, '')
    )
SQL
            );
        }

        $this->execute(
            "ALTER TABLE financeiro_lancamentos ADD COLUMN IF NOT EXISTS financeiro_banco_id INTEGER NULL"
        );
        $this->execute(
            "CREATE INDEX IF NOT EXISTS idx_financeiro_lancamentos_banco_id ON financeiro_lancamentos (financeiro_banco_id)"
        );
    }

    public function down()
    {
        // Não remove estrutura automaticamente para evitar perda de dados.
    }
}
