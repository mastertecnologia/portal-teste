<?php
use Migrations\AbstractMigration;

/**
 * Estrutura de cobrança bancária CNAB sobre o financeiro existente.
 *
 * Escopo:
 * - Evolui `financeiro_bancos` com dados de convênio/carteira/layout.
 * - Evolui `financeiro_lancamentos` com campos próprios de cobrança,
 *   sem conflitar com `status` financeiro já existente.
 * - Cria tabelas de remessas e relacionamento remessa x títulos.
 *
 * Observações:
 * - Compatível com PostgreSQL usado no projeto.
 * - Não destrói dados existentes.
 * - Usa `financeiro_lancamentos` como tabela-base dos títulos.
 */
class FinanceiroCnabRemessasRetornos extends AbstractMigration
{
    public function up()
    {
        $this->ensureFinanceiroBancos();
        $this->ensureFinanceiroLancamentosCnabColumns();
        $this->ensureFinanceiroRemessas();
        $this->ensureFinanceiroRemessaTitulos();
    }

    public function down()
    {
        // Não remove estrutura automaticamente para evitar perda de dados operacionais.
    }

    /**
     * Adiciona campos de cobrança à tabela de bancos.
     */
    protected function ensureFinanceiroBancos()
    {
        if (!$this->hasTable('financeiro_bancos')) {
            return;
        }

        $table = $this->table('financeiro_bancos');

        $needsUpdate = false;

        if (!$table->hasColumn('convenio')) {
            $table->addColumn('convenio', 'string', [
                'limit' => 50,
                'null' => true,
                'default' => null,
                'comment' => 'Convênio/cedente para cobrança CNAB',
            ]);
            $needsUpdate = true;
        }

        if (!$table->hasColumn('carteira')) {
            $table->addColumn('carteira', 'string', [
                'limit' => 20,
                'null' => true,
                'default' => null,
                'comment' => 'Carteira bancária da cobrança',
            ]);
            $needsUpdate = true;
        }

        if (!$table->hasColumn('cnab_tipo')) {
            $table->addColumn('cnab_tipo', 'string', [
                'limit' => 10,
                'null' => false,
                'default' => '240',
                'comment' => 'Layout CNAB utilizado pelo banco (240/400)',
            ]);
            $needsUpdate = true;
        }

        if (!$table->hasColumn('proxima_remessa')) {
            $table->addColumn('proxima_remessa', 'integer', [
                'null' => false,
                'default' => 1,
                'comment' => 'Próximo número sequencial de remessa para o banco',
            ]);
            $needsUpdate = true;
        }

        if ($needsUpdate) {
            $table->update();
        }

        $indexes = $table->getIndexes();

        if (!isset($indexes['idx_fin_bancos_cnab_tipo'])) {
            $table->addIndex(['cnab_tipo'], [
                'name' => 'idx_fin_bancos_cnab_tipo',
            ]);
            $needsUpdate = true;
        }

        if (!isset($indexes['idx_fin_bancos_convenio'])) {
            $table->addIndex(['convenio'], [
                'name' => 'idx_fin_bancos_convenio',
            ]);
            $needsUpdate = true;
        }

        if ($needsUpdate) {
            $table->update();
        }
    }

    /**
     * Adiciona colunas CNAB à tabela financeira base.
     */
    protected function ensureFinanceiroLancamentosCnabColumns()
    {
        if (!$this->hasTable('financeiro_lancamentos')) {
            return;
        }

        $table = $this->table('financeiro_lancamentos');
        $needsUpdate = false;

        if (!$table->hasColumn('nosso_numero')) {
            $table->addColumn('nosso_numero', 'string', [
                'limit' => 40,
                'null' => true,
                'default' => null,
                'comment' => 'Nosso número gerado para cobrança bancária',
            ]);
            $needsUpdate = true;
        }

        if (!$table->hasColumn('status_cobranca')) {
            $table->addColumn('status_cobranca', 'string', [
                'limit' => 30,
                'null' => false,
                'default' => 'sem_cobranca',
                'comment' => 'Status da cobrança CNAB, separado do status financeiro',
            ]);
            $needsUpdate = true;
        }

        if (!$table->hasColumn('data_baixa')) {
            $table->addColumn('data_baixa', 'date', [
                'null' => true,
                'default' => null,
                'comment' => 'Data de baixa/liquidação vinda do retorno bancário',
            ]);
            $needsUpdate = true;
        }

        if (!$table->hasColumn('valor_pago')) {
            $table->addColumn('valor_pago', 'decimal', [
                'precision' => 15,
                'scale' => 2,
                'null' => true,
                'default' => null,
                'comment' => 'Valor efetivamente pago segundo retorno bancário',
            ]);
            $needsUpdate = true;
        }

        if (!$table->hasColumn('codigo_rejeicao')) {
            $table->addColumn('codigo_rejeicao', 'string', [
                'limit' => 20,
                'null' => true,
                'default' => null,
                'comment' => 'Código bruto de rejeição/ocorrência bancária',
            ]);
            $needsUpdate = true;
        }

        if (!$table->hasColumn('mensagem_rejeicao')) {
            $table->addColumn('mensagem_rejeicao', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'Mensagem legível da rejeição/ocorrência bancária',
            ]);
            $needsUpdate = true;
        }

        if ($needsUpdate) {
            $table->update();
        }

        $indexes = $table->getIndexes();
        $indexNeedsUpdate = false;

        if (!isset($indexes['idx_fin_lanc_nosso_numero'])) {
            $table->addIndex(['nosso_numero'], [
                'name' => 'idx_fin_lanc_nosso_numero',
            ]);
            $indexNeedsUpdate = true;
        }

        if (!isset($indexes['idx_fin_lanc_status_cobranca'])) {
            $table->addIndex(['status_cobranca'], [
                'name' => 'idx_fin_lanc_status_cobranca',
            ]);
            $indexNeedsUpdate = true;
        }

        if (!isset($indexes['idx_fin_lanc_baixa'])) {
            $table->addIndex(['data_baixa'], [
                'name' => 'idx_fin_lanc_baixa',
            ]);
            $indexNeedsUpdate = true;
        }

        if ($indexNeedsUpdate) {
            $table->update();
        }
    }

    /**
     * Cria a tabela de remessas geradas.
     */
    protected function ensureFinanceiroRemessas()
    {
        if (!$this->hasTable('financeiro_remessas')) {
            $table = $this->table('financeiro_remessas');

            $table
                ->addColumn('idempresa', 'integer', [
                    'null' => false,
                ])
                ->addColumn('financeiro_banco_id', 'integer', [
                    'null' => false,
                ])
                ->addColumn('usuario_id', 'integer', [
                    'null' => true,
                    'default' => null,
                ])
                ->addColumn('cnab_layout', 'string', [
                    'limit' => 10,
                    'null' => false,
                    'default' => '240',
                ])
                ->addColumn('sequencial_arquivo', 'integer', [
                    'null' => false,
                    'default' => 1,
                ])
                ->addColumn('numero_remessa', 'string', [
                    'limit' => 30,
                    'null' => true,
                    'default' => null,
                ])
                ->addColumn('data_geracao', 'date', [
                    'null' => false,
                ])
                ->addColumn('status', 'string', [
                    'limit' => 30,
                    'null' => false,
                    'default' => 'gerada',
                ])
                ->addColumn('nome_arquivo', 'string', [
                    'limit' => 255,
                    'null' => false,
                ])
                ->addColumn('caminho_arquivo', 'string', [
                    'limit' => 255,
                    'null' => true,
                    'default' => null,
                ])
                ->addColumn('quantidade_titulos', 'integer', [
                    'null' => false,
                    'default' => 0,
                ])
                ->addColumn('valor_total', 'decimal', [
                    'precision' => 15,
                    'scale' => 2,
                    'null' => false,
                    'default' => 0,
                ])
                ->addColumn('observacoes', 'text', [
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
                ->addIndex(['idempresa'], ['name' => 'idx_fin_rem_idempresa'])
                ->addIndex(['financeiro_banco_id'], ['name' => 'idx_fin_rem_banco'])
                ->addIndex(['status'], ['name' => 'idx_fin_rem_status'])
                ->addIndex(
                    ['idempresa', 'financeiro_banco_id', 'cnab_layout', 'sequencial_arquivo'],
                    [
                        'name' => 'ux_fin_rem_emp_banco_layout_seq',
                        'unique' => true,
                    ],
                )
                ->addForeignKey('idempresa', 'empresas', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'CASCADE',
                    'constraint' => 'fk_fin_rem_empresa',
                ])
                ->addForeignKey('financeiro_banco_id', 'financeiro_bancos', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'CASCADE',
                    'constraint' => 'fk_fin_rem_banco',
                ])
                ->create();

            if ($this->hasTable('users')) {
                $table = $this->table('financeiro_remessas');
                if (!$table->hasForeignKey('usuario_id')) {
                    $table->addForeignKey('usuario_id', 'users', 'id', [
                        'delete' => 'SET_NULL',
                        'update' => 'CASCADE',
                        'constraint' => 'fk_fin_rem_usuario',
                    ])->update();
                }
            }

            return;
        }

        $table = $this->table('financeiro_remessas');
        $needsUpdate = false;

        $columns = [
            'idempresa' => ['type' => 'integer', 'null' => false],
            'financeiro_banco_id' => ['type' => 'integer', 'null' => false],
            'usuario_id' => ['type' => 'integer', 'null' => true, 'default' => null],
            'cnab_layout' => ['type' => 'string', 'limit' => 10, 'null' => false, 'default' => '240'],
            'sequencial_arquivo' => ['type' => 'integer', 'null' => false, 'default' => 1],
            'numero_remessa' => ['type' => 'string', 'limit' => 30, 'null' => true, 'default' => null],
            'data_geracao' => ['type' => 'date', 'null' => false],
            'status' => ['type' => 'string', 'limit' => 30, 'null' => false, 'default' => 'gerada'],
            'nome_arquivo' => ['type' => 'string', 'limit' => 255, 'null' => false],
            'caminho_arquivo' => ['type' => 'string', 'limit' => 255, 'null' => true, 'default' => null],
            'quantidade_titulos' => ['type' => 'integer', 'null' => false, 'default' => 0],
            'valor_total' => ['type' => 'decimal', 'precision' => 15, 'scale' => 2, 'null' => false, 'default' => 0],
            'observacoes' => ['type' => 'text', 'null' => true, 'default' => null],
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

        if (!isset($indexes['idx_fin_rem_idempresa'])) {
            $table->addIndex(['idempresa'], ['name' => 'idx_fin_rem_idempresa']);
            $indexNeedsUpdate = true;
        }
        if (!isset($indexes['idx_fin_rem_banco'])) {
            $table->addIndex(['financeiro_banco_id'], ['name' => 'idx_fin_rem_banco']);
            $indexNeedsUpdate = true;
        }
        if (!isset($indexes['idx_fin_rem_status'])) {
            $table->addIndex(['status'], ['name' => 'idx_fin_rem_status']);
            $indexNeedsUpdate = true;
        }
        if (!isset($indexes['ux_fin_rem_emp_banco_layout_seq'])) {
            $table->addIndex(
                ['idempresa', 'financeiro_banco_id', 'cnab_layout', 'sequencial_arquivo'],
                [
                    'name' => 'ux_fin_rem_emp_banco_layout_seq',
                    'unique' => true,
                ],
            );
            $indexNeedsUpdate = true;
        }

        if ($indexNeedsUpdate) {
            $table->update();
        }

        if (!$table->hasForeignKey('idempresa')) {
            $table->addForeignKey('idempresa', 'empresas', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_fin_rem_empresa',
            ]);
            $needsUpdate = true;
        }

        if (!$table->hasForeignKey('financeiro_banco_id')) {
            $table->addForeignKey('financeiro_banco_id', 'financeiro_bancos', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_fin_rem_banco',
            ]);
            $needsUpdate = true;
        }

        if ($this->hasTable('users') && !$table->hasForeignKey('usuario_id')) {
            $table->addForeignKey('usuario_id', 'users', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
                'constraint' => 'fk_fin_rem_usuario',
            ]);
            $needsUpdate = true;
        }

        if ($needsUpdate) {
            $table->update();
        }
    }

    /**
     * Cria a tabela de itens/títulos pertencentes à remessa.
     */
    protected function ensureFinanceiroRemessaTitulos()
    {
        if (!$this->hasTable('financeiro_remessa_titulos')) {
            $table = $this->table('financeiro_remessa_titulos');

            $table
                ->addColumn('financeiro_remessa_id', 'integer', [
                    'null' => false,
                ])
                ->addColumn('financeiro_lancamento_id', 'integer', [
                    'null' => false,
                ])
                ->addColumn('nosso_numero_remessa', 'string', [
                    'limit' => 40,
                    'null' => true,
                    'default' => null,
                ])
                ->addColumn('numero_documento', 'string', [
                    'limit' => 40,
                    'null' => true,
                    'default' => null,
                ])
                ->addColumn('valor_titulo', 'decimal', [
                    'precision' => 15,
                    'scale' => 2,
                    'null' => false,
                    'default' => 0,
                ])
                ->addColumn('data_vencimento', 'date', [
                    'null' => true,
                    'default' => null,
                ])
                ->addColumn('status_item', 'string', [
                    'limit' => 30,
                    'null' => false,
                    'default' => 'incluido',
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
                ->addColumn('created', 'datetime', [
                    'null' => true,
                    'default' => null,
                ])
                ->addColumn('modified', 'datetime', [
                    'null' => true,
                    'default' => null,
                ])
                ->addIndex(['financeiro_remessa_id'], ['name' => 'idx_fin_rem_tit_rem'])
                ->addIndex(['financeiro_lancamento_id'], ['name' => 'idx_fin_rem_tit_lanc'])
                ->addIndex(['status_item'], ['name' => 'idx_fin_rem_tit_status'])
                ->addIndex(
                    ['financeiro_remessa_id', 'financeiro_lancamento_id'],
                    [
                        'name' => 'ux_fin_rem_tit_rem_lanc',
                        'unique' => true,
                    ],
                )
                ->addForeignKey('financeiro_remessa_id', 'financeiro_remessas', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'CASCADE',
                    'constraint' => 'fk_fin_rem_tit_rem',
                ])
                ->addForeignKey('financeiro_lancamento_id', 'financeiro_lancamentos', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'CASCADE',
                    'constraint' => 'fk_fin_rem_tit_lanc',
                ])
                ->create();

            return;
        }

        $table = $this->table('financeiro_remessa_titulos');
        $needsUpdate = false;

        $columns = [
            'financeiro_remessa_id' => ['type' => 'integer', 'null' => false],
            'financeiro_lancamento_id' => ['type' => 'integer', 'null' => false],
            'nosso_numero_remessa' => ['type' => 'string', 'limit' => 40, 'null' => true, 'default' => null],
            'numero_documento' => ['type' => 'string', 'limit' => 40, 'null' => true, 'default' => null],
            'valor_titulo' => ['type' => 'decimal', 'precision' => 15, 'scale' => 2, 'null' => false, 'default' => 0],
            'data_vencimento' => ['type' => 'date', 'null' => true, 'default' => null],
            'status_item' => ['type' => 'string', 'limit' => 30, 'null' => false, 'default' => 'incluido'],
            'codigo_ocorrencia' => ['type' => 'string', 'limit' => 10, 'null' => true, 'default' => null],
            'mensagem_ocorrencia' => ['type' => 'text', 'null' => true, 'default' => null],
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

        if (!isset($indexes['idx_fin_rem_tit_rem'])) {
            $table->addIndex(['financeiro_remessa_id'], ['name' => 'idx_fin_rem_tit_rem']);
            $indexNeedsUpdate = true;
        }
        if (!isset($indexes['idx_fin_rem_tit_lanc'])) {
            $table->addIndex(['financeiro_lancamento_id'], ['name' => 'idx_fin_rem_tit_lanc']);
            $indexNeedsUpdate = true;
        }
        if (!isset($indexes['idx_fin_rem_tit_status'])) {
            $table->addIndex(['status_item'], ['name' => 'idx_fin_rem_tit_status']);
            $indexNeedsUpdate = true;
        }
        if (!isset($indexes['ux_fin_rem_tit_rem_lanc'])) {
            $table->addIndex(
                ['financeiro_remessa_id', 'financeiro_lancamento_id'],
                [
                    'name' => 'ux_fin_rem_tit_rem_lanc',
                    'unique' => true,
                ],
            );
            $indexNeedsUpdate = true;
        }

        if ($indexNeedsUpdate) {
            $table->update();
        }

        if (!$table->hasForeignKey('financeiro_remessa_id')) {
            $table->addForeignKey('financeiro_remessa_id', 'financeiro_remessas', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_fin_rem_tit_rem',
            ]);
            $needsUpdate = true;
        }

        if (!$table->hasForeignKey('financeiro_lancamento_id')) {
            $table->addForeignKey('financeiro_lancamento_id', 'financeiro_lancamentos', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_fin_rem_tit_lanc',
            ]);
            $needsUpdate = true;
        }

        if ($needsUpdate) {
            $table->update();
        }
    }
}
