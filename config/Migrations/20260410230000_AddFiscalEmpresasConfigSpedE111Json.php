<?php
use Migrations\AbstractMigration;

/**
 * SPED Fiscal — ajustes de apuração ICMS (E111) opcionais via JSON em config fiscal.
 */
class AddFiscalEmpresasConfigSpedE111Json extends AbstractMigration {

    public function change() {
        if (!$this->hasTable('fiscal_empresas_config')) {
            return;
        }
        $t = $this->table('fiscal_empresas_config');
        if ($t->hasColumn('sped_e111_ajustes_json')) {
            return;
        }

        $t->addColumn('sped_e111_ajustes_json', 'text', ['null' => true])->update();
    }
}
