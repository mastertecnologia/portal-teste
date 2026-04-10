<?php
use Migrations\AbstractMigration;

/**
 * Último NSU usado na Distribuição DF-e (nacional) — continuação de consultas.
 */
class AddFiscalEmpresasConfigDfeUltNsu extends AbstractMigration {

    public function up() {
        if (!$this->hasTable('fiscal_empresas_config')) {
            return;
        }
        $t = $this->table('fiscal_empresas_config');
        if (!$t->hasColumn('dfe_ult_nsu')) {
            $t->addColumn('dfe_ult_nsu', 'string', [
                'limit' => 15,
                'null' => true,
                'default' => null,
                'comment' => 'Último ultNSU retornado pela AN (Distribuição DF-e); 15 dígitos',
            ])->update();
        }
    }

    public function down() {
        if (!$this->hasTable('fiscal_empresas_config')) {
            return;
        }
        $t = $this->table('fiscal_empresas_config');
        if ($t->hasColumn('dfe_ult_nsu')) {
            $t->removeColumn('dfe_ult_nsu')->update();
        }
    }
}
