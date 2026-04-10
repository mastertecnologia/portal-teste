<?php
/**
 * Números de série por item de NF (controle, busca e relatórios).
 */
use Migrations\AbstractMigration;

class AddFiscalNotasItensSeries extends AbstractMigration {

    public function up() {
        if (!$this->hasTable('fiscal_notas_itens_series')) {
            $t = $this->table('fiscal_notas_itens_series');
            $t->addColumn('fiscal_nota_item_id', 'integer', ['null' => false])
              ->addColumn('numero_serie', 'string', ['limit' => 120, 'null' => false])
              ->addColumn('created', 'datetime', ['null' => true])
              ->addColumn('modified', 'datetime', ['null' => true])
              ->addForeignKey('fiscal_nota_item_id', 'fiscal_notas_itens', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
              ->addIndex(['fiscal_nota_item_id'], ['name' => 'ix_fiscal_serie_item'])
              ->addIndex(['numero_serie'], ['name' => 'ix_fiscal_serie_numero'])
              ->create();
        }
    }

    public function down() {
        if ($this->hasTable('fiscal_notas_itens_series')) {
            $this->table('fiscal_notas_itens_series')->drop()->save();
        }
    }
}
