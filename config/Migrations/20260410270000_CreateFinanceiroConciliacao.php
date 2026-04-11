<?php
use Migrations\AbstractMigration;

/**
 * Extrato bancário importado (OFX/CSV) para conciliação.
 */
class CreateFinanceiroConciliacao extends AbstractMigration {

    public function up() {
        if ($this->hasTable('financeiro_extrato_bancario')) {
            return;
        }

        $table = $this->table('financeiro_extrato_bancario');
        $table->addColumn('idempresa', 'integer', ['null' => false])
            ->addColumn('data', 'date', ['null' => false])
            ->addColumn('descricao', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('valor', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => false, 'default' => 0])
            ->addColumn('tipo', 'string', ['limit' => 10, 'null' => false, 'default' => 'credito', 'comment' => 'credito ou debito'])
            ->addColumn('fitid', 'string', ['limit' => 100, 'null' => true, 'comment' => 'ID transação OFX (dedup)'])
            ->addColumn('financeiro_lancamento_id', 'integer', ['null' => true, 'comment' => 'Conciliado com lançamento'])
            ->addColumn('conciliado', 'boolean', ['null' => false, 'default' => false])
            ->addColumn('conta_bancaria', 'string', ['limit' => 60, 'null' => true])
            ->addColumn('origem', 'string', ['limit' => 10, 'null' => false, 'default' => 'ofx', 'comment' => 'ofx ou csv'])
            ->addColumn('created', 'datetime', ['null' => true])
            ->addIndex(['idempresa'])
            ->addIndex(['fitid'])
            ->addIndex(['conciliado'])
            ->addIndex(['data'])
            ->create();
    }

    public function down() {
        $this->table('financeiro_extrato_bancario')->drop()->save();
    }
}
