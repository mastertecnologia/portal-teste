<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Migration para criação do módulo Laudos / Parecer Técnico
 *
 * Para executar:
 *   bin/cake migrations migrate
 *
 * Para rollback:
 *   bin/cake migrations rollback
 *
 * NOTA: Este arquivo é uma alternativa ao schema.sql. Use UM dos dois.
 * Se preferir SQL puro, rode database/schema.sql diretamente.
 */
class CreateLaudosTables extends AbstractMigration
{
    public function up(): void
    {
        // 1) EMPRESAS
        $this->table('laudos_empresas')
            ->addColumn('razao_social', 'string', ['limit' => 200, 'null' => false])
            ->addColumn('cnpj', 'string', ['limit' => 18, 'null' => false])
            ->addColumn('email', 'string', ['limit' => 150, 'null' => true])
            ->addColumn('telefone', 'string', ['limit' => 30, 'null' => true])
            ->addColumn('telefone2', 'string', ['limit' => 30, 'null' => true])
            ->addColumn('cep', 'string', ['limit' => 10, 'null' => true])
            ->addColumn('endereco', 'text', ['null' => true])
            ->addColumn('site', 'string', ['limit' => 150, 'null' => true])
            ->addColumn('logo_path', 'string', ['limit' => 500, 'null' => true])
            ->addColumn('carimbo_path', 'string', ['limit' => 500, 'null' => true])
            ->addColumn('assinatura_padrao_path', 'string', ['limit' => 500, 'null' => true])
            ->addColumn('numbering_format', 'string', ['limit' => 50, 'default' => '{seq:0000}/{year}'])
            ->addColumn('repair_threshold', 'decimal', ['precision' => 3, 'scale' => 2, 'default' => 0.60])
            ->addColumn('image_max_width', 'integer', ['default' => 1200])
            ->addColumn('public_validation_url', 'string', ['limit' => 200, 'null' => true])
            ->addTimestamps('created', 'modified')
            ->create();

        // 2) CONTADORES
        $this->table('laudos_contadores')
            ->addColumn('empresa_id', 'integer', ['null' => false])
            ->addColumn('ano', 'integer', ['null' => false])
            ->addColumn('ultimo_numero', 'integer', ['default' => 0])
            ->addTimestamps('created', 'modified')
            ->addIndex(['empresa_id', 'ano'], ['unique' => true])
            ->addForeignKey('empresa_id', 'laudos_empresas', 'id', ['delete' => 'CASCADE'])
            ->create();

        // 3) PARECERES
        $this->table('laudos_pareceres')
            ->addColumn('empresa_id', 'integer', ['null' => false])
            ->addColumn('numero', 'string', ['limit' => 20, 'null' => false])
            ->addColumn('titulo', 'string', ['limit' => 200, 'null' => false])
            ->addColumn('public_hash', 'string', ['limit' => 20, 'null' => false])
            ->addColumn('status', 'string', ['limit' => 20, 'default' => 'rascunho'])
            ->addColumn('tecnico_user_id', 'integer', ['null' => true])
            ->addColumn('tecnico_nome', 'string', ['limit' => 150, 'null' => true])
            ->addColumn('tecnico_registro', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('requester_client_id', 'integer', ['null' => true])
            ->addColumn('requester_attention_to', 'string', ['limit' => 150, 'null' => true])
            ->addColumn('requester_company_name', 'string', ['limit' => 200, 'null' => true])
            ->addColumn('requester_cnpj', 'string', ['limit' => 18, 'null' => true])
            ->addColumn('requester_phone', 'string', ['limit' => 30, 'null' => true])
            ->addColumn('requester_email', 'string', ['limit' => 150, 'null' => true])
            ->addColumn('requester_cep', 'string', ['limit' => 10, 'null' => true])
            ->addColumn('requester_address', 'text', ['null' => true])
            ->addColumn('objetivo', 'text', ['null' => true])
            ->addColumn('documentacao', 'text', ['null' => true])
            ->addColumn('conclusao', 'text', ['null' => true])
            ->addColumn('estimated_new_equipment', 'decimal', ['precision' => 12, 'scale' => 2, 'default' => 0])
            ->addColumn('show_comparison', 'boolean', ['default' => true])
            ->addColumn('assinatura_path', 'string', ['limit' => 500, 'null' => true])
            ->addColumn('cidade', 'string', ['limit' => 100, 'default' => 'Bento Gonçalves'])
            ->addColumn('data_emissao', 'date', ['null' => true])
            ->addColumn('deleted', 'datetime', ['null' => true])
            ->addColumn('deleted_by', 'integer', ['null' => true])
            ->addColumn('created_by', 'integer', ['null' => true])
            ->addColumn('modified_by', 'integer', ['null' => true])
            ->addTimestamps('created', 'modified')
            ->addIndex(['public_hash'], ['unique' => true])
            ->addIndex(['empresa_id'])
            ->addIndex(['status'])
            ->addIndex(['tecnico_user_id'])
            ->addIndex(['requester_client_id'])
            ->addIndex(['numero'])
            ->addIndex(['deleted'])
            ->addIndex(['data_emissao'])
            ->addForeignKey('empresa_id', 'laudos_empresas', 'id')
            ->create();

        // 4) PRODUTOS
        $this->table('laudos_produtos')
            ->addColumn('parecer_id', 'integer', ['null' => false])
            ->addColumn('ordem', 'integer', ['default' => 0])
            ->addColumn('nome', 'string', ['limit' => 200, 'null' => true])
            ->addColumn('tipo', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('serial_number', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('especificacoes', 'text', ['null' => true])
            ->addColumn('diagnostico', 'text', ['null' => true])
            ->addColumn('componentes_disponibilidade', 'text', ['null' => true])
            ->addColumn('licenciamento_obs', 'text', ['null' => true])
            ->addColumn('recomendacao', 'string', ['limit' => 20, 'default' => 'replace'])
            ->addTimestamps('created', 'modified')
            ->addIndex(['parecer_id'])
            ->addForeignKey('parecer_id', 'laudos_pareceres', 'id', ['delete' => 'CASCADE'])
            ->create();

        // 5) IMAGENS
        $this->table('laudos_produto_imagens')
            ->addColumn('produto_id', 'integer', ['null' => false])
            ->addColumn('nome_original', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('file_path', 'string', ['limit' => 500, 'null' => false])
            ->addColumn('mime_type', 'string', ['limit' => 50, 'default' => 'image/jpeg'])
            ->addColumn('file_size', 'integer', ['null' => true])
            ->addColumn('width', 'integer', ['null' => true])
            ->addColumn('height', 'integer', ['null' => true])
            ->addColumn('descricao', 'string', ['limit' => 500, 'null' => true])
            ->addColumn('ordem', 'integer', ['default' => 0])
            ->addColumn('created', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['produto_id'])
            ->addForeignKey('produto_id', 'laudos_produtos', 'id', ['delete' => 'CASCADE'])
            ->create();

        // 10) CATÁLOGO PEÇAS (criado antes para FK das peças do produto)
        $this->table('laudos_catalogo_pecas')
            ->addColumn('empresa_id', 'integer', ['null' => false])
            ->addColumn('nome', 'string', ['limit' => 200, 'null' => false])
            ->addColumn('codigo', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('preco_default', 'decimal', ['precision' => 12, 'scale' => 2, 'default' => 0])
            ->addColumn('unidade', 'string', ['limit' => 10, 'default' => 'un'])
            ->addColumn('categoria', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('ativo', 'boolean', ['default' => true])
            ->addTimestamps('created', 'modified')
            ->addIndex(['empresa_id'])
            ->addIndex(['ativo'])
            ->addForeignKey('empresa_id', 'laudos_empresas', 'id')
            ->create();

        // CATÁLOGO SERVIÇOS
        $this->table('laudos_catalogo_servicos')
            ->addColumn('empresa_id', 'integer', ['null' => false])
            ->addColumn('descricao', 'string', ['limit' => 300, 'null' => false])
            ->addColumn('valor_hora_default', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0])
            ->addColumn('horas_default', 'decimal', ['precision' => 6, 'scale' => 2, 'default' => 1])
            ->addColumn('categoria', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('ativo', 'boolean', ['default' => true])
            ->addTimestamps('created', 'modified')
            ->addIndex(['empresa_id'])
            ->addIndex(['ativo'])
            ->addForeignKey('empresa_id', 'laudos_empresas', 'id')
            ->create();

        // 6) PEÇAS DO PRODUTO
        $this->table('laudos_produto_pecas')
            ->addColumn('produto_id', 'integer', ['null' => false])
            ->addColumn('catalogo_id', 'integer', ['null' => true])
            ->addColumn('nome', 'string', ['limit' => 200, 'null' => false])
            ->addColumn('codigo', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('quantidade', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 1])
            ->addColumn('unidade', 'string', ['limit' => 10, 'default' => 'un'])
            ->addColumn('preco_unitario', 'decimal', ['precision' => 12, 'scale' => 2, 'default' => 0])
            ->addColumn('ordem', 'integer', ['default' => 0])
            ->addColumn('created', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['produto_id'])
            ->addForeignKey('produto_id', 'laudos_produtos', 'id', ['delete' => 'CASCADE'])
            ->addForeignKey('catalogo_id', 'laudos_catalogo_pecas', 'id', ['delete' => 'SET_NULL'])
            ->create();

        // 7) SERVIÇOS DO PRODUTO
        $this->table('laudos_produto_servicos')
            ->addColumn('produto_id', 'integer', ['null' => false])
            ->addColumn('catalogo_id', 'integer', ['null' => true])
            ->addColumn('descricao', 'string', ['limit' => 300, 'null' => false])
            ->addColumn('horas', 'decimal', ['precision' => 6, 'scale' => 2, 'default' => 1])
            ->addColumn('valor_hora', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0])
            ->addColumn('ordem', 'integer', ['default' => 0])
            ->addColumn('created', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['produto_id'])
            ->addForeignKey('produto_id', 'laudos_produtos', 'id', ['delete' => 'CASCADE'])
            ->addForeignKey('catalogo_id', 'laudos_catalogo_servicos', 'id', ['delete' => 'SET_NULL'])
            ->create();

        // 8) ANEXOS
        $this->table('laudos_anexos')
            ->addColumn('parecer_id', 'integer', ['null' => false])
            ->addColumn('nome_original', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('file_path', 'string', ['limit' => 500, 'null' => false])
            ->addColumn('mime_type', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('file_size', 'integer', ['null' => true])
            ->addColumn('descricao', 'string', ['limit' => 300, 'null' => true])
            ->addColumn('created_by', 'integer', ['null' => true])
            ->addColumn('created', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['parecer_id'])
            ->addForeignKey('parecer_id', 'laudos_pareceres', 'id', ['delete' => 'CASCADE'])
            ->create();

        // 9) HISTÓRICO
        $this->table('laudos_historico')
            ->addColumn('parecer_id', 'integer', ['null' => false])
            ->addColumn('user_id', 'integer', ['null' => true])
            ->addColumn('user_name_snapshot', 'string', ['limit' => 150, 'null' => true])
            ->addColumn('action', 'string', ['limit' => 50, 'null' => false])
            ->addColumn('details', 'jsonb', ['null' => true])
            ->addColumn('created', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['parecer_id'])
            ->addIndex(['action'])
            ->addIndex(['created'])
            ->addForeignKey('parecer_id', 'laudos_pareceres', 'id', ['delete' => 'CASCADE'])
            ->create();

        // 11) TEMPLATES
        $this->table('laudos_templates')
            ->addColumn('empresa_id', 'integer', ['null' => false])
            ->addColumn('tipo', 'string', ['limit' => 20, 'null' => false])
            ->addColumn('nome', 'string', ['limit' => 150, 'null' => false])
            ->addColumn('conteudo', 'text', ['null' => false])
            ->addColumn('ordem', 'integer', ['default' => 0])
            ->addColumn('ativo', 'boolean', ['default' => true])
            ->addTimestamps('created', 'modified')
            ->addIndex(['empresa_id', 'tipo', 'ativo'])
            ->addForeignKey('empresa_id', 'laudos_empresas', 'id')
            ->create();
    }

    public function down(): void
    {
        $this->table('laudos_templates')->drop()->save();
        $this->table('laudos_historico')->drop()->save();
        $this->table('laudos_anexos')->drop()->save();
        $this->table('laudos_produto_servicos')->drop()->save();
        $this->table('laudos_produto_pecas')->drop()->save();
        $this->table('laudos_catalogo_servicos')->drop()->save();
        $this->table('laudos_catalogo_pecas')->drop()->save();
        $this->table('laudos_produto_imagens')->drop()->save();
        $this->table('laudos_produtos')->drop()->save();
        $this->table('laudos_pareceres')->drop()->save();
        $this->table('laudos_contadores')->drop()->save();
        $this->table('laudos_empresas')->drop()->save();
    }
}
