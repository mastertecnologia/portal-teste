<?php
use Migrations\AbstractMigration;

/**
 * Duplicatas (contas a receber/pagar) geradas a partir de notas fiscais.
 */
class CreateFiscalDuplicatas extends AbstractMigration {

    public function up() {
        $table = $this->table('fiscal_duplicatas');
        $table->addColumn('idempresa', 'integer', ['null' => false])
            ->addColumn('fiscal_nota_id', 'integer', ['null' => false])
            ->addColumn('idcliente', 'integer', ['null' => true])
            ->addColumn('numero_duplicata', 'string', ['limit' => 60, 'null' => true])
            ->addColumn('tipo', 'string', ['limit' => 10, 'null' => false, 'default' => 'receber', 'comment' => 'receber ou pagar'])
            ->addColumn('valor', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => false, 'default' => 0])
            ->addColumn('data_vencimento', 'date', ['null' => true])
            ->addColumn('data_pagamento', 'date', ['null' => true])
            ->addColumn('valor_pago', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => true])
            ->addColumn('status', 'string', ['limit' => 20, 'null' => false, 'default' => 'aberta', 'comment' => 'aberta, paga, cancelada'])
            ->addColumn('observacao', 'text', ['null' => true])
            ->addColumn('financeiro_lancamento_id', 'integer', ['null' => true, 'comment' => 'Vínculo com módulo financeiro existente'])
            ->addColumn('created', 'datetime', ['null' => true])
            ->addColumn('modified', 'datetime', ['null' => true])
            ->addIndex(['idempresa'])
            ->addIndex(['fiscal_nota_id'])
            ->addIndex(['idcliente'])
            ->addIndex(['status'])
            ->addIndex(['data_vencimento'])
            ->create();
    }

    public function down() {
        $this->table('fiscal_duplicatas')->drop()->save();
    }
}
