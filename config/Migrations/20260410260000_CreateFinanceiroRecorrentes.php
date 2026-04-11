<?php
use Migrations\AbstractMigration;

/**
 * Lançamentos recorrentes (mensalidades, aluguéis, assinaturas).
 * Um template gera novos lançamentos em financeiro_lancamentos via cron.
 */
class CreateFinanceiroRecorrentes extends AbstractMigration {

    public function up() {
        if ($this->hasTable('financeiro_recorrentes')) {
            return;
        }

        $table = $this->table('financeiro_recorrentes');
        $table->addColumn('idempresa', 'integer', ['null' => false])
            ->addColumn('descricao', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('tipo', 'string', ['limit' => 10, 'null' => false, 'default' => 'despesa', 'comment' => 'receita ou despesa'])
            ->addColumn('valor', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => false, 'default' => 0])
            ->addColumn('idcliente', 'integer', ['null' => true])
            ->addColumn('plano_conta_id', 'integer', ['null' => true])
            ->addColumn('centro_custo_id', 'integer', ['null' => true])
            ->addColumn('frequencia', 'string', ['limit' => 20, 'null' => false, 'default' => 'mensal', 'comment' => 'mensal, quinzenal, semanal, trimestral, anual'])
            ->addColumn('dia_vencimento', 'integer', ['null' => false, 'default' => 1, 'comment' => 'Dia do mês para vencimento (1-28)'])
            ->addColumn('data_inicio', 'date', ['null' => false])
            ->addColumn('data_fim', 'date', ['null' => true, 'comment' => 'NULL = sem prazo definido'])
            ->addColumn('ultima_geracao', 'date', ['null' => true, 'comment' => 'Último mês/data em que foi gerado'])
            ->addColumn('ativo', 'boolean', ['null' => false, 'default' => true])
            ->addColumn('observacoes', 'text', ['null' => true])
            ->addColumn('created', 'datetime', ['null' => true])
            ->addColumn('modified', 'datetime', ['null' => true])
            ->addIndex(['idempresa'])
            ->addIndex(['ativo'])
            ->create();
    }

    public function down() {
        $this->table('financeiro_recorrentes')->drop()->save();
    }
}
