<?php
/**
 * Módulo Fiscal completo — NFe, NFSe, certificados, impostos, CFOP, NCM.
 *
 * Tabelas:
 *   fiscal_certificados       — Certificados digitais A1/A3
 *   fiscal_empresas_config    — Configuração fiscal por empresa (regime, série, etc.)
 *   fiscal_natureza_operacao  — Naturezas de operação
 *   fiscal_cfop               — Códigos CFOP
 *   fiscal_ncm                — Tabela NCM (Nomenclatura Comum do Mercosul)
 *   fiscal_aliquotas          — Alíquotas configuráveis por UF/NCM
 *   fiscal_notas              — Notas fiscais (NFe/NFSe/NFCe)
 *   fiscal_notas_itens        — Itens da nota fiscal
 *   fiscal_notas_impostos     — Impostos detalhados por item
 *   fiscal_notas_pagamentos   — Formas de pagamento da nota
 *   fiscal_notas_eventos      — Eventos (cancelamento, CCe, inutilização)
 *   fiscal_notas_xmls         — XMLs enviados/retornados (autorização, cancelamento, etc.)
 *
 * Rodar: bin/cake migrations migrate
 */
use Migrations\AbstractMigration;

class CreateFiscalModule extends AbstractMigration {

    public function up() {
        // ── fiscal_certificados ──────────────────────────────────────────
        if (!$this->hasTable('fiscal_certificados')) {
            $t = $this->table('fiscal_certificados');
            $t->addColumn('idempresa', 'integer', ['null' => false])
              ->addColumn('nome', 'string', ['limit' => 255, 'null' => false])
              ->addColumn('tipo', 'string', ['limit' => 10, 'null' => false, 'default' => 'A1', 'comment' => 'A1 ou A3'])
              ->addColumn('arquivo_pfx', 'binary', ['null' => true, 'comment' => 'Conteúdo do .pfx (A1) criptografado'])
              ->addColumn('senha_hash', 'string', ['limit' => 255, 'null' => true, 'comment' => 'Senha do certificado (criptografada)'])
              ->addColumn('serial_number', 'string', ['limit' => 100, 'null' => true])
              ->addColumn('cn_subject', 'string', ['limit' => 500, 'null' => true, 'comment' => 'CN do certificado'])
              ->addColumn('cnpj_certificado', 'string', ['limit' => 18, 'null' => true])
              ->addColumn('validade_inicio', 'datetime', ['null' => true])
              ->addColumn('validade_fim', 'datetime', ['null' => true])
              ->addColumn('ativo', 'boolean', ['null' => false, 'default' => true])
              ->addColumn('created', 'datetime', ['null' => true])
              ->addColumn('modified', 'datetime', ['null' => true])
              ->addForeignKey('idempresa', 'empresas', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
              ->addIndex(['idempresa'], ['name' => 'ix_fiscal_cert_empresa'])
              ->addIndex(['idempresa', 'ativo'], ['name' => 'ix_fiscal_cert_empresa_ativo'])
              ->create();
        }

        // ── fiscal_empresas_config ───────────────────────────────────────
        if (!$this->hasTable('fiscal_empresas_config')) {
            $t = $this->table('fiscal_empresas_config');
            $t->addColumn('idempresa', 'integer', ['null' => false])
              ->addColumn('regime_tributario', 'integer', ['null' => false, 'default' => 1, 'comment' => '1=Simples Nacional, 2=SN Exc.Sublimite, 3=Regime Normal'])
              ->addColumn('ambiente', 'integer', ['null' => false, 'default' => 2, 'comment' => '1=Produção, 2=Homologação'])
              ->addColumn('serie_nfe', 'integer', ['null' => false, 'default' => 1])
              ->addColumn('prox_numero_nfe', 'integer', ['null' => false, 'default' => 1])
              ->addColumn('serie_nfse', 'integer', ['null' => false, 'default' => 1])
              ->addColumn('prox_numero_nfse', 'integer', ['null' => false, 'default' => 1])
              ->addColumn('serie_nfce', 'integer', ['null' => false, 'default' => 1])
              ->addColumn('prox_numero_nfce', 'integer', ['null' => false, 'default' => 1])
              ->addColumn('inscricao_estadual', 'string', ['limit' => 20, 'null' => true])
              ->addColumn('inscricao_municipal', 'string', ['limit' => 20, 'null' => true])
              ->addColumn('cnae_fiscal', 'string', ['limit' => 10, 'null' => true])
              ->addColumn('codigo_municipio_ibge', 'string', ['limit' => 10, 'null' => true])
              ->addColumn('uf', 'string', ['limit' => 2, 'null' => true])
              ->addColumn('csc_id', 'string', ['limit' => 10, 'null' => true, 'comment' => 'ID do CSC para NFCe'])
              ->addColumn('csc_token', 'string', ['limit' => 100, 'null' => true, 'comment' => 'Token CSC para NFCe'])
              ->addColumn('aliquota_simples', 'decimal', ['precision' => 5, 'scale' => 2, 'null' => true, 'comment' => 'Alíquota efetiva do Simples Nacional (%)'])
              ->addColumn('certificado_id', 'integer', ['null' => true, 'comment' => 'Certificado digital ativo'])
              ->addColumn('nfse_provedor', 'string', ['limit' => 50, 'null' => true, 'comment' => 'Provedor NFSe: ginfes, betha, issnet, abrasf, etc.'])
              ->addColumn('nfse_usuario', 'string', ['limit' => 100, 'null' => true])
              ->addColumn('nfse_senha', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('created', 'datetime', ['null' => true])
              ->addColumn('modified', 'datetime', ['null' => true])
              ->addForeignKey('idempresa', 'empresas', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
              ->addIndex(['idempresa'], ['name' => 'ux_fiscal_config_empresa', 'unique' => true])
              ->create();
        }

        // ── fiscal_natureza_operacao ─────────────────────────────────────
        if (!$this->hasTable('fiscal_natureza_operacao')) {
            $t = $this->table('fiscal_natureza_operacao');
            $t->addColumn('idempresa', 'integer', ['null' => false])
              ->addColumn('codigo', 'string', ['limit' => 20, 'null' => false])
              ->addColumn('descricao', 'string', ['limit' => 255, 'null' => false])
              ->addColumn('tipo', 'string', ['limit' => 20, 'null' => false, 'default' => 'saida', 'comment' => 'entrada|saida'])
              ->addColumn('cfop_padrao', 'string', ['limit' => 5, 'null' => true])
              ->addColumn('gera_financeiro', 'boolean', ['null' => false, 'default' => true])
              ->addColumn('ativo', 'boolean', ['null' => false, 'default' => true])
              ->addColumn('created', 'datetime', ['null' => true])
              ->addColumn('modified', 'datetime', ['null' => true])
              ->addForeignKey('idempresa', 'empresas', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
              ->addIndex(['idempresa'], ['name' => 'ix_fiscal_natop_empresa'])
              ->create();
        }

        // ── fiscal_cfop ──────────────────────────────────────────────────
        if (!$this->hasTable('fiscal_cfop')) {
            $t = $this->table('fiscal_cfop');
            $t->addColumn('codigo', 'string', ['limit' => 5, 'null' => false])
              ->addColumn('descricao', 'string', ['limit' => 500, 'null' => false])
              ->addColumn('tipo', 'string', ['limit' => 20, 'null' => false, 'comment' => 'entrada|saida'])
              ->addColumn('aplicacao', 'text', ['null' => true])
              ->addIndex(['codigo'], ['name' => 'ux_fiscal_cfop_codigo', 'unique' => true])
              ->create();
        }

        // ── fiscal_ncm ──────────────────────────────────────────────────
        if (!$this->hasTable('fiscal_ncm')) {
            $t = $this->table('fiscal_ncm');
            $t->addColumn('codigo', 'string', ['limit' => 10, 'null' => false])
              ->addColumn('descricao', 'string', ['limit' => 500, 'null' => false])
              ->addColumn('aliquota_ipi', 'decimal', ['precision' => 5, 'scale' => 2, 'null' => true, 'default' => null])
              ->addColumn('ex_tipi', 'string', ['limit' => 5, 'null' => true])
              ->addIndex(['codigo'], ['name' => 'ux_fiscal_ncm_codigo', 'unique' => true])
              ->create();
        }

        // ── fiscal_aliquotas ────────────────────────────────────────────
        if (!$this->hasTable('fiscal_aliquotas')) {
            $t = $this->table('fiscal_aliquotas');
            $t->addColumn('idempresa', 'integer', ['null' => false])
              ->addColumn('uf_origem', 'string', ['limit' => 2, 'null' => false])
              ->addColumn('uf_destino', 'string', ['limit' => 2, 'null' => false])
              ->addColumn('ncm_codigo', 'string', ['limit' => 10, 'null' => true, 'comment' => 'NULL = alíquota genérica da UF'])
              ->addColumn('icms_aliquota', 'decimal', ['precision' => 5, 'scale' => 2, 'null' => true])
              ->addColumn('icms_reducao', 'decimal', ['precision' => 5, 'scale' => 2, 'null' => true, 'default' => '0.00'])
              ->addColumn('icms_st_mva', 'decimal', ['precision' => 5, 'scale' => 2, 'null' => true, 'comment' => 'MVA ST (%)'])
              ->addColumn('ipi_aliquota', 'decimal', ['precision' => 5, 'scale' => 2, 'null' => true])
              ->addColumn('pis_aliquota', 'decimal', ['precision' => 5, 'scale' => 4, 'null' => true, 'default' => '1.6500'])
              ->addColumn('cofins_aliquota', 'decimal', ['precision' => 5, 'scale' => 4, 'null' => true, 'default' => '7.6000'])
              ->addColumn('iss_aliquota', 'decimal', ['precision' => 5, 'scale' => 2, 'null' => true, 'comment' => 'Para NFSe'])
              ->addColumn('created', 'datetime', ['null' => true])
              ->addColumn('modified', 'datetime', ['null' => true])
              ->addForeignKey('idempresa', 'empresas', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
              ->addIndex(['idempresa', 'uf_origem', 'uf_destino'], ['name' => 'ix_fiscal_aliq_uf'])
              ->create();
        }

        // ── fiscal_notas ─────────────────────────────────────────────────
        if (!$this->hasTable('fiscal_notas')) {
            $t = $this->table('fiscal_notas');
            $t->addColumn('idempresa', 'integer', ['null' => false])
              ->addColumn('idcliente', 'integer', ['null' => true])
              ->addColumn('user_id', 'integer', ['null' => true, 'comment' => 'Usuário que emitiu'])
              ->addColumn('modelo', 'string', ['limit' => 5, 'null' => false, 'default' => '55', 'comment' => '55=NFe, 65=NFCe, NFSE=NFSe'])
              ->addColumn('serie', 'integer', ['null' => false, 'default' => 1])
              ->addColumn('numero', 'integer', ['null' => true])
              ->addColumn('chave_acesso', 'string', ['limit' => 50, 'null' => true])
              ->addColumn('natureza_operacao', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('natureza_operacao_id', 'integer', ['null' => true])
              ->addColumn('tipo_operacao', 'integer', ['null' => false, 'default' => 1, 'comment' => '0=Entrada, 1=Saída'])
              ->addColumn('finalidade', 'integer', ['null' => false, 'default' => 1, 'comment' => '1=Normal, 2=Complementar, 3=Ajuste, 4=Devolução'])
              ->addColumn('presenca', 'integer', ['null' => false, 'default' => 9, 'comment' => '0=Não se aplica, 1=Presencial, 9=Outros'])
              ->addColumn('data_emissao', 'datetime', ['null' => true])
              ->addColumn('data_saida', 'datetime', ['null' => true])
              ->addColumn('valor_produtos', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => false, 'default' => '0.00'])
              ->addColumn('valor_frete', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => false, 'default' => '0.00'])
              ->addColumn('valor_seguro', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => false, 'default' => '0.00'])
              ->addColumn('valor_desconto', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => false, 'default' => '0.00'])
              ->addColumn('valor_outras_despesas', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => false, 'default' => '0.00'])
              ->addColumn('valor_total_impostos', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => false, 'default' => '0.00'])
              ->addColumn('valor_total', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => false, 'default' => '0.00'])
              ->addColumn('valor_icms', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => false, 'default' => '0.00'])
              ->addColumn('valor_icms_st', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => false, 'default' => '0.00'])
              ->addColumn('valor_ipi', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => false, 'default' => '0.00'])
              ->addColumn('valor_pis', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => false, 'default' => '0.00'])
              ->addColumn('valor_cofins', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => false, 'default' => '0.00'])
              ->addColumn('valor_iss', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => false, 'default' => '0.00'])
              ->addColumn('informacoes_complementares', 'text', ['null' => true])
              ->addColumn('informacoes_fisco', 'text', ['null' => true])
              // Transporte
              ->addColumn('frete_modalidade', 'integer', ['null' => false, 'default' => 9, 'comment' => '0=Emitente,1=Destinatário,2=Terceiros,9=Sem frete'])
              ->addColumn('transportadora_cnpj', 'string', ['limit' => 18, 'null' => true])
              ->addColumn('transportadora_nome', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('veiculo_placa', 'string', ['limit' => 10, 'null' => true])
              ->addColumn('veiculo_uf', 'string', ['limit' => 2, 'null' => true])
              ->addColumn('volumes_quantidade', 'integer', ['null' => true])
              ->addColumn('volumes_especie', 'string', ['limit' => 60, 'null' => true])
              ->addColumn('volumes_peso_bruto', 'decimal', ['precision' => 12, 'scale' => 3, 'null' => true])
              ->addColumn('volumes_peso_liquido', 'decimal', ['precision' => 12, 'scale' => 3, 'null' => true])
              // Status SEFAZ
              ->addColumn('status', 'string', ['limit' => 30, 'null' => false, 'default' => 'rascunho', 'comment' => 'rascunho|pendente|autorizada|cancelada|denegada|rejeitada|inutilizada'])
              ->addColumn('protocolo_autorizacao', 'string', ['limit' => 20, 'null' => true])
              ->addColumn('data_autorizacao', 'datetime', ['null' => true])
              ->addColumn('motivo_rejeicao', 'text', ['null' => true])
              ->addColumn('codigo_retorno_sefaz', 'string', ['limit' => 10, 'null' => true])
              ->addColumn('mensagem_retorno_sefaz', 'text', ['null' => true])
              // Referências
              ->addColumn('faturamento_id', 'integer', ['null' => true, 'comment' => 'FK lógica para faturamento.id'])
              ->addColumn('ordem_servico_id', 'integer', ['null' => true, 'comment' => 'FK lógica para ordensservico.id'])
              ->addColumn('created', 'datetime', ['null' => true])
              ->addColumn('modified', 'datetime', ['null' => true])
              ->addForeignKey('idempresa', 'empresas', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
              ->addIndex(['idempresa'], ['name' => 'ix_fiscal_notas_empresa'])
              ->addIndex(['idempresa', 'status'], ['name' => 'ix_fiscal_notas_status'])
              ->addIndex(['chave_acesso'], ['name' => 'ux_fiscal_notas_chave', 'unique' => true])
              ->addIndex(['idempresa', 'modelo', 'serie', 'numero'], ['name' => 'ux_fiscal_notas_num', 'unique' => true])
              ->create();
        }

        // ── fiscal_notas_itens ───────────────────────────────────────────
        if (!$this->hasTable('fiscal_notas_itens')) {
            $t = $this->table('fiscal_notas_itens');
            $t->addColumn('fiscal_nota_id', 'integer', ['null' => false])
              ->addColumn('numero_item', 'integer', ['null' => false])
              ->addColumn('produto_id', 'integer', ['null' => true, 'comment' => 'FK lógica para produtos.id'])
              ->addColumn('codigo_produto', 'string', ['limit' => 60, 'null' => true])
              ->addColumn('descricao', 'string', ['limit' => 500, 'null' => false])
              ->addColumn('ncm', 'string', ['limit' => 10, 'null' => true])
              ->addColumn('cest', 'string', ['limit' => 10, 'null' => true])
              ->addColumn('cfop', 'string', ['limit' => 5, 'null' => false])
              ->addColumn('unidade', 'string', ['limit' => 10, 'null' => false, 'default' => 'UN'])
              ->addColumn('quantidade', 'decimal', ['precision' => 15, 'scale' => 4, 'null' => false])
              ->addColumn('valor_unitario', 'decimal', ['precision' => 15, 'scale' => 4, 'null' => false])
              ->addColumn('valor_total', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => false])
              ->addColumn('valor_desconto', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => false, 'default' => '0.00'])
              ->addColumn('valor_frete', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => false, 'default' => '0.00'])
              ->addColumn('valor_seguro', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => false, 'default' => '0.00'])
              ->addColumn('valor_outras_despesas', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => false, 'default' => '0.00'])
              // CST / CSOSN
              ->addColumn('icms_origem', 'string', ['limit' => 1, 'null' => true, 'default' => '0', 'comment' => '0=Nacional, 1=Importação direta, etc.'])
              ->addColumn('icms_cst', 'string', ['limit' => 3, 'null' => true, 'comment' => 'CST ICMS ou CSOSN'])
              ->addColumn('pis_cst', 'string', ['limit' => 3, 'null' => true])
              ->addColumn('cofins_cst', 'string', ['limit' => 3, 'null' => true])
              ->addColumn('ipi_cst', 'string', ['limit' => 3, 'null' => true])
              ->addColumn('informacoes_adicionais', 'text', ['null' => true])
              ->addColumn('created', 'datetime', ['null' => true])
              ->addColumn('modified', 'datetime', ['null' => true])
              ->addForeignKey('fiscal_nota_id', 'fiscal_notas', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
              ->addIndex(['fiscal_nota_id'], ['name' => 'ix_fiscal_nitens_nota'])
              ->create();
        }

        // ── fiscal_notas_impostos ────────────────────────────────────────
        if (!$this->hasTable('fiscal_notas_impostos')) {
            $t = $this->table('fiscal_notas_impostos');
            $t->addColumn('fiscal_nota_item_id', 'integer', ['null' => false])
              ->addColumn('imposto', 'string', ['limit' => 20, 'null' => false, 'comment' => 'ICMS|ICMS_ST|IPI|PIS|COFINS|ISS|IRPJ|CSLL|II|DIFAL'])
              ->addColumn('base_calculo', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => false, 'default' => '0.00'])
              ->addColumn('aliquota', 'decimal', ['precision' => 7, 'scale' => 4, 'null' => false, 'default' => '0.0000'])
              ->addColumn('valor', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => false, 'default' => '0.00'])
              ->addColumn('reducao_base', 'decimal', ['precision' => 5, 'scale' => 2, 'null' => true, 'default' => '0.00'])
              ->addColumn('mva', 'decimal', ['precision' => 5, 'scale' => 2, 'null' => true, 'comment' => 'MVA para ICMS-ST'])
              ->addColumn('valor_retido', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => true, 'default' => '0.00'])
              ->addColumn('created', 'datetime', ['null' => true])
              ->addColumn('modified', 'datetime', ['null' => true])
              ->addForeignKey('fiscal_nota_item_id', 'fiscal_notas_itens', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
              ->addIndex(['fiscal_nota_item_id'], ['name' => 'ix_fiscal_impostos_item'])
              ->create();
        }

        // ── fiscal_notas_pagamentos ──────────────────────────────────────
        if (!$this->hasTable('fiscal_notas_pagamentos')) {
            $t = $this->table('fiscal_notas_pagamentos');
            $t->addColumn('fiscal_nota_id', 'integer', ['null' => false])
              ->addColumn('forma_pagamento', 'string', ['limit' => 3, 'null' => false, 'default' => '01', 'comment' => '01=Dinheiro,02=Cheque,03=Cartão Crédito,04=Cartão Débito,05=Crédito Loja,10=Vale,11=Vale Refeição,12=Vale Presente,13=Vale Combustível,14=Duplicata,15=Boleto,90=Sem Pagamento,99=Outros'])
              ->addColumn('valor', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => false])
              ->addColumn('bandeira_cartao', 'string', ['limit' => 3, 'null' => true, 'comment' => '01=Visa,02=Master,03=AmEx,04=Sorocred,05=Diners,06=Elo,07=Hiper,08=Aura,99=Outros'])
              ->addColumn('cnpj_operadora', 'string', ['limit' => 18, 'null' => true])
              ->addColumn('autorizacao', 'string', ['limit' => 30, 'null' => true])
              ->addColumn('numero_parcelas', 'integer', ['null' => true, 'default' => 1])
              ->addColumn('created', 'datetime', ['null' => true])
              ->addColumn('modified', 'datetime', ['null' => true])
              ->addForeignKey('fiscal_nota_id', 'fiscal_notas', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
              ->addIndex(['fiscal_nota_id'], ['name' => 'ix_fiscal_pag_nota'])
              ->create();
        }

        // ── fiscal_notas_eventos ─────────────────────────────────────────
        if (!$this->hasTable('fiscal_notas_eventos')) {
            $t = $this->table('fiscal_notas_eventos');
            $t->addColumn('fiscal_nota_id', 'integer', ['null' => false])
              ->addColumn('tipo_evento', 'string', ['limit' => 30, 'null' => false, 'comment' => 'cancelamento|carta_correcao|inutilizacao|manifestacao'])
              ->addColumn('sequencia', 'integer', ['null' => false, 'default' => 1])
              ->addColumn('codigo_evento', 'string', ['limit' => 10, 'null' => true, 'comment' => 'Código do evento SEFAZ (110111, 110110, etc.)'])
              ->addColumn('motivo', 'text', ['null' => true])
              ->addColumn('protocolo', 'string', ['limit' => 20, 'null' => true])
              ->addColumn('data_evento', 'datetime', ['null' => true])
              ->addColumn('status', 'string', ['limit' => 20, 'null' => false, 'default' => 'pendente', 'comment' => 'pendente|autorizado|rejeitado'])
              ->addColumn('codigo_retorno', 'string', ['limit' => 10, 'null' => true])
              ->addColumn('mensagem_retorno', 'text', ['null' => true])
              ->addColumn('user_id', 'integer', ['null' => true])
              ->addColumn('created', 'datetime', ['null' => true])
              ->addColumn('modified', 'datetime', ['null' => true])
              ->addForeignKey('fiscal_nota_id', 'fiscal_notas', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
              ->addIndex(['fiscal_nota_id'], ['name' => 'ix_fiscal_eventos_nota'])
              ->create();
        }

        // ── fiscal_notas_xmls ────────────────────────────────────────────
        if (!$this->hasTable('fiscal_notas_xmls')) {
            $t = $this->table('fiscal_notas_xmls');
            $t->addColumn('fiscal_nota_id', 'integer', ['null' => false])
              ->addColumn('tipo', 'string', ['limit' => 30, 'null' => false, 'comment' => 'autorizacao|cancelamento|inutilizacao|carta_correcao|evento|distribuicao'])
              ->addColumn('xml_envio', 'text', ['null' => true])
              ->addColumn('xml_retorno', 'text', ['null' => true])
              ->addColumn('created', 'datetime', ['null' => true])
              ->addForeignKey('fiscal_nota_id', 'fiscal_notas', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
              ->addIndex(['fiscal_nota_id'], ['name' => 'ix_fiscal_xmls_nota'])
              ->create();
        }
    }

    public function down() {
        $tables = [
            'fiscal_notas_xmls',
            'fiscal_notas_eventos',
            'fiscal_notas_pagamentos',
            'fiscal_notas_impostos',
            'fiscal_notas_itens',
            'fiscal_notas',
            'fiscal_aliquotas',
            'fiscal_ncm',
            'fiscal_cfop',
            'fiscal_natureza_operacao',
            'fiscal_empresas_config',
            'fiscal_certificados',
        ];
        foreach ($tables as $table) {
            if ($this->hasTable($table)) {
                $this->table($table)->drop()->save();
            }
        }
    }
}
