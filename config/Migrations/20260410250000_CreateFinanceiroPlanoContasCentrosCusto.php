<?php
use Migrations\AbstractMigration;

/**
 * A: Plano de Contas hierárquico (receita/despesa).
 * B: Centros de Custo por empresa.
 * Adiciona FK opcional em financeiro_lancamentos.
 */
class CreateFinanceiroPlanoContasCentrosCusto extends AbstractMigration {

    public function up() {
        // ── Plano de Contas ─────────────────────────────────────────
        if (!$this->hasTable('financeiro_plano_contas')) {
            $this->table('financeiro_plano_contas')
                ->addColumn('idempresa', 'integer', ['null' => false])
                ->addColumn('parent_id', 'integer', ['null' => true, 'default' => null, 'comment' => 'Conta pai (hierarquia)'])
                ->addColumn('codigo', 'string', ['limit' => 20, 'null' => false, 'comment' => 'Ex: 1, 1.1, 1.1.01'])
                ->addColumn('descricao', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('tipo', 'string', ['limit' => 10, 'null' => false, 'comment' => 'receita|despesa'])
                ->addColumn('natureza', 'string', ['limit' => 15, 'null' => false, 'default' => 'analitica', 'comment' => 'sintetica|analitica'])
                ->addColumn('ativo', 'boolean', ['null' => false, 'default' => true])
                ->addColumn('created', 'datetime', ['null' => true])
                ->addColumn('modified', 'datetime', ['null' => true])
                ->addIndex(['idempresa', 'codigo'], ['name' => 'ux_fin_plano_emp_cod', 'unique' => true])
                ->addIndex(['idempresa', 'tipo'], ['name' => 'ix_fin_plano_tipo'])
                ->addForeignKey('idempresa', 'empresas', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->create();
        }

        // ── Centros de Custo ────────────────────────────────────────
        if (!$this->hasTable('financeiro_centros_custo')) {
            $this->table('financeiro_centros_custo')
                ->addColumn('idempresa', 'integer', ['null' => false])
                ->addColumn('codigo', 'string', ['limit' => 20, 'null' => false])
                ->addColumn('descricao', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('ativo', 'boolean', ['null' => false, 'default' => true])
                ->addColumn('created', 'datetime', ['null' => true])
                ->addColumn('modified', 'datetime', ['null' => true])
                ->addIndex(['idempresa', 'codigo'], ['name' => 'ux_fin_cc_emp_cod', 'unique' => true])
                ->addForeignKey('idempresa', 'empresas', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->create();
        }

        // ── FK em financeiro_lancamentos ─────────────────────────────
        if ($this->hasTable('financeiro_lancamentos')) {
            $t = $this->table('financeiro_lancamentos');
            if (!$t->hasColumn('plano_conta_id')) {
                $t->addColumn('plano_conta_id', 'integer', ['null' => true, 'default' => null, 'after' => 'idcliente'])
                    ->addColumn('centro_custo_id', 'integer', ['null' => true, 'default' => null, 'after' => 'plano_conta_id'])
                    ->update();
            }
        }
    }

    public function down() {
        if ($this->hasTable('financeiro_lancamentos')) {
            $t = $this->table('financeiro_lancamentos');
            if ($t->hasColumn('plano_conta_id')) {
                $t->removeColumn('plano_conta_id')->removeColumn('centro_custo_id')->update();
            }
        }
        if ($this->hasTable('financeiro_centros_custo')) {
            $this->table('financeiro_centros_custo')->drop()->save();
        }
        if ($this->hasTable('financeiro_plano_contas')) {
            $this->table('financeiro_plano_contas')->drop()->save();
        }
    }
}
