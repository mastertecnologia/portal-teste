<?php
use Migrations\AbstractMigration;

class AddFiscalEmpresasConfigSped0460C190Json extends AbstractMigration {

    public function change() {
        $t = $this->table('fiscal_empresas_config');
        if ($t->hasColumn('sped_0460_c190_json')) {
            return;
        }
        $t->addColumn('sped_0460_c190_json', 'text', ['null' => true])->update();
    }
}
