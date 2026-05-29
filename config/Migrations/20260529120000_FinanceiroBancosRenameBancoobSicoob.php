<?php
use Migrations\AbstractMigration;

/**
 * Renomeia BANCOOB para SICOOB (código FEBRABAN 756) no cadastro de contas.
 */
class FinanceiroBancosRenameBancoobSicoob extends AbstractMigration
{
    public function up()
    {
        if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
            return;
        }
        if (!$this->hasTable('financeiro_bancos')) {
            return;
        }

        $this->execute(<<<'SQL'
UPDATE financeiro_bancos
SET nome = 'SICOOB',
    modified = NOW()
WHERE UPPER(TRIM(nome)) = 'BANCOOB'
   OR (
        TRIM(codigo_banco) IN ('756', '0756')
        AND UPPER(TRIM(nome)) LIKE '%BANCOOB%'
   );
SQL
        );
    }

    public function down()
    {
        if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
            return;
        }
        if (!$this->hasTable('financeiro_bancos')) {
            return;
        }

        $this->execute(<<<'SQL'
UPDATE financeiro_bancos
SET nome = 'BANCOOB',
    modified = NOW()
WHERE TRIM(codigo_banco) IN ('756', '0756')
  AND UPPER(TRIM(nome)) = 'SICOOB';
SQL
        );
    }
}
