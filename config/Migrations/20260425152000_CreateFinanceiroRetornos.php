<?php
use Migrations\AbstractMigration;

/**
 * Cria a persistência de arquivos e itens de retorno bancário CNAB.
 *
 * Escopo:
 * - `financeiro_retorno_arquivos`: cabeçalho do arquivo processado
 * - `financeiro_retorno_itens`: log detalhado por ocorrência/título
 *
 * Observações:
 * - Compatível com PostgreSQL usado no projeto
 * - Não remove dados existentes
 * - Mantém rollout seguro quando tabelas já existirem
 */
class CreateFinanceiroRetornos extends AbstractMigration
{
    public function up()
    {
        $this->ensureFinanceiroRetornoArquivos();
        $this->ensureFinanceiroRetornoItens();
    }

    public function down()
    {
        if ($this->hasTable('financeiro_retorno_itens')) {
            $this->table('financeiro_retorno_itens')->drop()->save();
        }

        if ($this->hasTable('financeiro_retorno_arquivos')) {
            $this->table('financeiro_retorno_arquivos')->drop()->save();
        }
    }

    /**
     * Cria/atualiza a tabela de arquivos de retorno processados.
     *
     * @return void
     */
    protected function ensureFinanceiroRetornoArquivos()
    {
        if (!$this->hasTable('financeiro_retorno_arquivos')) {
            $table = $this->table('financeiro_retorno_arquivos');

            $table
                ->addColumn('idempresa', 'integer', [
                    'null' => false,
                ])
                ->addColumn('financeiro_banco_id', 'integer', [
                    'null' => true,
                    'default' => null,
                ])
                ->addColumn('usuario_id', 'integer', [
                    'null' => true,
                    'default' => null,
                ])
                ->addColumn('financeiro_remessa_id', 'integer', [
                    'null' => true,
                    'default' => null,
                ])
                ->addColumn('nome_arquivo_original', 'string', [
                    'limit' => 255,
                    'null' => false,
                ])
                ->addColumn('nome_arquivo_salvo', 'string', [
                    'limit' => 255,
                    'null' => true,
                    'default' => null,
                ])
                ->addColumn('caminho_arquivo', 'string', [
                    'limit' => 255,
                    'null' => true,
                    'default' => null,
                ])
                ->addColumn('layout_cnab', 'string', [
                    'limit' => 10,
                    'null' => false,
                    'default' => '240',
                ])
                ->addColumn('status_processamento', 'string', [
                    'limit' => 30,
                    'null' => false,
                    'default' => 'processado',
                ])
                ->addColumn('observacoes', 'text', [
                    'null' => true,
                    'default' => null,
                ])
                ->addColumn('processados', 'integer', [
                    'null' => false,
                    'default' => 0,
                ])
                ->addColumn('baixados', 'integer', [
                    'null' => false,
                    'default' => 0,
                ])
                ->addColumn('rejeitados', 'integer', [
                    'null' => false,
                    'default' => 0,
                ])
                ->addColumn('ignorados', 'integer', [
                    'null' => false,
                    'default' => 0,
                ])
                ->addColumn('erros', 'integer', [
                    'null' => false,
                    'default' => 0,
                ])
                ->addColumn('data_processamento', 'datetime', [
                    'null' => false,
                ])
                ->addColumn('created', 'datetime', [
                    'null' => true,
                    'default' => null,
                ])
                ->addColumn('modified', 'datetime', [
                    'null' => true,
                    'default' => null,
                ])
                ->addIndex(['idempresa'], [
                    'name' => 'idx_fin_ret_arq_idempresa',
                ])
                ->addIndex(['financeiro_banco_id'], [
                    'name' => 'idx_fin_ret_arq_banco',
                ])
                ->addIndex(['financeiro_remessa_id'], [
                    'name' => 'idx_fin_ret_arq_remessa',
                ])
                ->addIndex(['usuario_id'], [
                    'name' => 'idx_fin_ret_arq_usuario',
                ])
                ->addIndex(['status_processamento'], [
                    'name' => 'idx_fin_ret_arq_status',
                ])
                ->addIndex(['data_processamento'], [
                    'name' => 'idx_fin_ret_arq_data_proc',
                ])
                ->addForeignKey('idempresa', 'empresas', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'CASCADE',
                    'constraint' => 'fk_fin_ret_arq_empresa',
                ])
                ->create();

            $this->ensureFinanceiroRetornoArquivosForeignKeys();

            return;
        }

        $table = $this->table('financeiro_retorno_arquivos');
        $needsUpdate = false;

        $columns = [
            'idempresa' => ['type' => 'integer', 'null' => false],
            'financeiro_banco_id' => ['type' => 'integer', 'null' => true, 'default' => null],
            'usuario_id' => ['type' => 'integer', 'null' => true, 'default' => null],
            'financeiro_remessa_id' => ['type' => 'integer', 'null' => true, 'default' => null],
            'nome_arquivo_original' => ['type' => 'string', 'limit' => 255, 'null' => false],
            'nome_arquivo_salvo' => ['type' => 'string', 'limit' => 255, 'null' => true, 'default' => null],
            'caminho_arquivo' => ['type' => 'string', 'limit' => 255, 'null' => true, 'default' => null],
            'layout_cnab' => ['type' => 'string', 'limit' => 10, 'null' => false, 'default' => '240'],
            'status_processamento' => ['type' => 'string', 'limit' => 30, 'null' => false, 'default' => 'processado'],
            'observacoes' => ['type' => 'text', 'null' => true, 'default' => null],
            'processados' => ['type' => 'integer', 'null' => false, 'default' => 0],
            'baixados' => ['type' => 'integer', 'null' => false, 'default' => 0],
            'rejeitados' => ['type' => 'integer', 'null' => false, 'default' => 0],
            'ignorados' => ['type' => 'integer', 'null' => false, 'default' => 0],
            'erros' => ['type' => 'integer', 'null' => false, 'default' => 0],
            'data_processamento' => ['type' => 'datetime', 'null' => false],
            'created' => ['type' => 'datetime', 'null' => true, 'default' => null],
            'modified' => ['type' => 'datetime', 'null' => true, 'default' => null],
        ];

        foreach ($columns as $name => $definition) {
            if (!$table->hasColumn($name)) {
                $type = $definition['type'];
                unset($definition['type']);
                $table->addColumn($name, $type, $definition);
                $needsUpdate = true;
            }
        }

        if ($needsUpdate) {
            $table->update();
        }

        $indexes = $table->getIndexes();
        $indexNeedsUpdate = false;

        if (!isset($indexes['idx_fin_ret_arq_idempresa'])) {
            $table->addIndex(['idempresa'], ['name' => 'idx_fin_ret_arq_idempresa']);
            $indexNeedsUpdate = true;
        }
        if (!isset($indexes['idx_fin_ret_arq_banco'])) {
            $table->addIndex(['financeiro_banco_id'], ['name' => 'idx_fin_ret_arq_banco']);
            $indexNeedsUpdate = true;
        }
        if (!isset($indexes['idx_fin_ret_arq_remessa'])) {
            $table->addIndex(['financeiro_remessa_id'], ['name' => 'idx_fin_ret_arq_remessa']);
            $indexNeedsUpdate = true;
        }
        if (!isset($indexes['idx_fin_ret_arq_usuario'])) {
            $table->addIndex(['usuario_id'], ['name' => 'idx_fin_ret_arq_usuario']);
            $indexNeedsUpdate = true;
        }
        if (!isset($indexes['idx_fin_ret_arq_status'])) {
            $table->addIndex(['status_processamento'], ['name' => 'idx_fin_ret_arq_status']);
            $indexNeedsUpdate = true;
        }
        if (!isset($indexes['idx_fin_ret_arq_data_proc'])) {
            $table->addIndex(['data_processamento'], ['name' => 'idx_fin_ret_arq_data_proc']);
            $indexNeedsUpdate = true;
        }

        if ($indexNeedsUpdate) {
            $table->update();
        }

        $this->ensureFinanceiroRetornoArquivosForeignKeys();
    }

    /**
     * Garante FKs da tabela de arquivos.
     *
     * @return void
     */
    protected function ensureFinanceiroRetornoArquivosForeignKeys()
    {
        $table = $this->table('financeiro_retorno_arquivos');
        $needsUpdate = false;

        if (!$table->hasForeignKey('idempresa')) {
            $table->addForeignKey('idempresa', 'empresas', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_fin_ret_arq_empresa',
            ]);
            $needsUpdate = true;
        }

        if ($this->hasTable('financeiro_bancos') && !$table->hasForeignKey('financeiro_banco_id')) {
            $table->addForeignKey('financeiro_banco_id', 'financeiro_bancos', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
                'constraint' => 'fk_fin_ret_arq_banco',
            ]);
            $needsUpdate = true;
        }

        if ($this->hasTable('financeiro_remessas') && !$table->hasForeignKey('financeiro_remessa_id')) {
            $table->addForeignKey('financeiro_remessa_id', 'financeiro_remessas', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
                'constraint' => 'fk_fin_ret_arq_remessa',
            ]);
            $needsUpdate = true;
        }

        if ($this->hasTable('users') && !$table->hasForeignKey('usuario_id')) {
            $table->addForeignKey('usuario_id', 'users', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
                'constraint' => 'fk_fin_ret_arq_usuario',
            ]);
            $needsUpdate = true;
        }

        if ($needsUpdate) {
            $table->update();
        }
    }

    /**
     * Cria/atualiza a tabela de itens de retorno processados.
     *
     * @return void
     */
    protected function ensureFinanceiroRetornoItens()
    {
        if (!$this->hasTable('financeiro_retorno_itens')) {
            $table = $this->table('financeiro_retorno_itens');

            $table
                ->addColumn('financeiro_retorno_arquivo_id', 'integer', [
                    'null' => false,
                ])
                ->addColumn('financeiro_lancamento_id', 'integer', [
                    'null' => true,
                    'default' => null,
                ])
                ->addColumn('financeiro_remessa_id', 'integer', [
                    'null' => true,
                    'default' => null,
                ])
                ->addColumn('financeiro_remessa_titulo_id', 'integer', [
                    'null' => true,
                    'default' => null,
                ])
                ->addColumn('status_item', 'string', [
                    'limit' => 30,
                    'null' => false,
                    'default' => 'ignorado',
                ])
                ->addColumn('nosso_numero', 'string', [
                    'limit' => 40,
                    'null' => true,
                    'default' => null,
                ])
                ->addColumn('numero_documento', 'string', [
                    'limit' => 40,
                    'null' => true,
                    'default' => null,
                ])
                ->addColumn('codigo_ocorrencia', 'string', [
                    'limit' => 10,
                    'null' => true,
                    'default' => null,
                ])
                ->addColumn('mensagem_ocorrencia', 'text', [
                    'null' => true,
                    'default' => null,
                ])
                ->addColumn('valor_titulo', 'decimal', [
                    'precision' => 15,
                    'scale' => 2,
                    'null' => false,
                    'default' => 0,
                ])
                ->addColumn('valor_pago', 'decimal', [
                    'precision' => 15,
                    'scale' => 2,
                    'null' => true,
                    'default' => null,
                ])
                ->addColumn('data_vencimento', 'date', [
                    'null' => true,
                    'default' => null,
                ])
                ->addColumn('data_ocorrencia', 'datetime', [
                    'null' => true,
                    'default' => null,
                ])
                ->addColumn('linha_segmento_t', 'text', [
                    'null' => true,
                    'default' => null,
                ])
                ->addColumn('linha_segmento_u', 'text', [
                    'null' => true,
                    'default' => null,
                ])
                ->addColumn('payload_json', 'text', [
                    'null' => true,
                    'default' => null,
                ])
                ->addColumn('created', 'datetime', [
                    'null' => true,
                    'default' => null,
                ])
                ->addColumn('modified', 'datetime', [
                    'null' => true,
                    'default' => null,
                ])
                ->addIndex(['financeiro_retorno_arquivo_id'], [
                    'name' => 'idx_fin_ret_it_arq',
                ])
                ->addIndex(['financeiro_lancamento_id'], [
                    'name' => 'idx_fin_ret_it_lanc',
                ])
                ->addIndex(['financeiro_remessa_id'], [
                    'name' => 'idx_fin_ret_it_rem',
                ])
                ->addIndex(['financeiro_remessa_titulo_id'], [
                    'name' => 'idx_fin_ret_it_rem_tit',
                ])
                ->addIndex(['status_item'], [
                    'name' => 'idx_fin_ret_it_status',
                ])
                ->addIndex(['nosso_numero'], [
                    'name' => 'idx_fin_ret_it_nosso_numero',
                ])
                ->addIndex(['codigo_ocorrencia'], [
                    'name' => 'idx_fin_ret_it_ocorrencia',
                ])
                ->addForeignKey('financeiro_retorno_arquivo_id', 'financeiro_retorno_arquivos', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'CASCADE',
                    'constraint' => 'fk_fin_ret_it_arq',
                ])
                ->create();

            $this->ensureFinanceiroRetornoItensForeignKeys();

            return;
        }

        $table = $this->table('financeiro_retorno_itens');
        $needsUpdate = false;

        $columns = [
            'financeiro_retorno_arquivo_id' => ['type' => 'integer', 'null' => false],
            'financeiro_lancamento_id' => ['type' => 'integer', 'null' => true, 'default' => null],
            'financeiro_remessa_id' => ['type' => 'integer', 'null' => true, 'default' => null],
            'financeiro_remessa_titulo_id' => ['type' => 'integer', 'null' => true, 'default' => null],
            'status_item' => ['type' => 'string', 'limit' => 30, 'null' => false, 'default' => 'ignorado'],
            'nosso_numero' => ['type' => 'string', 'limit' => 40, 'null' => true, 'default' => null],
            'numero_documento' => ['type' => 'string', 'limit' => 40, 'null' => true, 'default' => null],
            'codigo_ocorrencia' => ['type' => 'string', 'limit' => 10, 'null' => true, 'default' => null],
            'mensagem_ocorrencia' => ['type' => 'text', 'null' => true, 'default' => null],
            'valor_titulo' => ['type' => 'decimal', 'precision' => 15, 'scale' => 2, 'null' => false, 'default' => 0],
            'valor_pago' => ['type' => 'decimal', 'precision' => 15, 'scale' => 2, 'null' => true, 'default' => null],
            'data_vencimento' => ['type' => 'date', 'null' => true, 'default' => null],
            'data_ocorrencia' => ['type' => 'datetime', 'null' => true, 'default' => null],
            'linha_segmento_t' => ['type' => 'text', 'null' => true, 'default' => null],
            'linha_segmento_u' => ['type' => 'text', 'null' => true, 'default' => null],
            'payload_json' => ['type' => 'text', 'null' => true, 'default' => null],
            'created' => ['type' => 'datetime', 'null' => true, 'default' => null],
            'modified' => ['type' => 'datetime', 'null' => true, 'default' => null],
        ];

        foreach ($columns as $name => $definition) {
            if (!$table->hasColumn($name)) {
                $type = $definition['type'];
                unset($definition['type']);
                $table->addColumn($name, $type, $definition);
                $needsUpdate = true;
            }
        }

        if ($needsUpdate) {
            $table->update();
        }

        $indexes = $table->getIndexes();
        $indexNeedsUpdate = false;

        if (!isset($indexes['idx_fin_ret_it_arq'])) {
            $table->addIndex(['financeiro_retorno_arquivo_id'], ['name' => 'idx_fin_ret_it_arq']);
            $indexNeedsUpdate = true;
        }
        if (!isset($indexes['idx_fin_ret_it_lanc'])) {
            $table->addIndex(['financeiro_lancamento_id'], ['name' => 'idx_fin_ret_it_lanc']);
            $indexNeedsUpdate = true;
        }
        if (!isset($indexes['idx_fin_ret_it_rem'])) {
            $table->addIndex(['financeiro_remessa_id'], ['name' => 'idx_fin_ret_it_rem']);
            $indexNeedsUpdate = true;
        }
        if (!isset($indexes['idx_fin_ret_it_rem_tit'])) {
            $table->addIndex(['financeiro_remessa_titulo_id'], ['name' => 'idx_fin_ret_it_rem_tit']);
            $indexNeedsUpdate = true;
        }
        if (!isset($indexes['idx_fin_ret_it_status'])) {
            $table->addIndex(['status_item'], ['name' => 'idx_fin_ret_it_status']);
            $indexNeedsUpdate = true;
        }
        if (!isset($indexes['idx_fin_ret_it_nosso_numero'])) {
            $table->addIndex(['nosso_numero'], ['name' => 'idx_fin_ret_it_nosso_numero']);
            $indexNeedsUpdate = true;
        }
        if (!isset($indexes['idx_fin_ret_it_ocorrencia'])) {
            $table->addIndex(['codigo_ocorrencia'], ['name' => 'idx_fin_ret_it_ocorrencia']);
            $indexNeedsUpdate = true;
        }

        if ($indexNeedsUpdate) {
            $table->update();
        }

        $this->ensureFinanceiroRetornoItensForeignKeys();
    }

    /**
     * Garante FKs da tabela de itens.
     *
     * @return void
     */
    protected function ensureFinanceiroRetornoItensForeignKeys()
    {
        $table = $this->table('financeiro_retorno_itens');
        $needsUpdate = false;

        if (!$table->hasForeignKey('financeiro_retorno_arquivo_id')) {
            $table->addForeignKey('financeiro_retorno_arquivo_id', 'financeiro_retorno_arquivos', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_fin_ret_it_arq',
            ]);
            $needsUpdate = true;
        }

        if ($this->hasTable('financeiro_lancamentos') && !$table->hasForeignKey('financeiro_lancamento_id')) {
            $table->addForeignKey('financeiro_lancamento_id', 'financeiro_lancamentos', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
                'constraint' => 'fk_fin_ret_it_lanc',
            ]);
            $needsUpdate = true;
        }

        if ($this->hasTable('financeiro_remessas') && !$table->hasForeignKey('financeiro_remessa_id')) {
            $table->addForeignKey('financeiro_remessa_id', 'financeiro_remessas', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
                'constraint' => 'fk_fin_ret_it_rem',
            ]);
            $needsUpdate = true;
        }

        if ($this->hasTable('financeiro_remessa_titulos') && !$table->hasForeignKey('financeiro_remessa_titulo_id')) {
            $table->addForeignKey('financeiro_remessa_titulo_id', 'financeiro_remessa_titulos', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
                'constraint' => 'fk_fin_ret_it_rem_tit',
            ]);
            $needsUpdate = true;
        }

        if ($needsUpdate) {
            $table->update();
        }
    }
}
