<?php
use Migrations\AbstractMigration;

/**
 * Renomeia BANCOOB para SICOOB (código FEBRABAN 756) no cadastro de contas.
 */
class FinanceiroBancosRenameBancoobSicoob extends AbstractMigration
{
    public function up()
    {
        if (!$this->hasTable('financeiro_bancos')) {
            return;
        }

        $this->execute(<<<'SQL'
UPDATE financeiro_bancos
SET nome = 'SICOOB',
    modified = NOW()
WHERE UPPER(TRIM(nome)) = 'BANCOOB'
   OR (
        REGEXP_REPLACE(COALESCE(codigo_banco, ''), '\D', '', 'g') = '756'
        AND UPPER(TRIM(nome)) LIKE '%BANCOOB%'
   );
SQL
        );
    }

    public function down()
    {
        if (!$this->hasTable('financeiro_bancos')) {
            return;
        }

        $this->execute(<<<'SQL'
UPDATE financeiro_bancos
SET nome = 'BANCOOB',
    modified = NOW()
WHERE REGEXP_REPLACE(COALESCE(codigo_banco, ''), '\D', '', 'g') = '756'
  AND UPPER(TRIM(nome)) = 'SICOOB';
SQL
        );
    }
}
