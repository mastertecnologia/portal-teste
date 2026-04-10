<?php
use Migrations\AbstractMigration;

/**
 * Bloco H (inventário SPED): H005/H010 opcionais via cadastro fiscal.
 */
class AddFiscalEmpresasConfigSpedInventario extends AbstractMigration {

    public function change() {
        if (!$this->hasTable('fiscal_empresas_config')) {
            return;
        }
        $t = $this->table('fiscal_empresas_config');
        if ($t->hasColumn('sped_inventario_declarar')) {
            return;
        }

        $t
            ->addColumn('sped_inventario_declarar', 'boolean', ['null' => false, 'default' => false])
            ->addColumn('sped_inventario_dt_inv', 'date', ['null' => true])
            ->addColumn('sped_inventario_mot_inv', 'string', ['limit' => 2, 'null' => true])
            ->addColumn('sped_inventario_itens_json', 'text', ['null' => true])
            ->update();
    }
}
