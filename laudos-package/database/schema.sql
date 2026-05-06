-- =============================================================================
-- MÓDULO LAUDOS / PARECER TÉCNICO — Schema PostgreSQL
-- =============================================================================
-- Convenção de nomes:
--   - Prefixo `laudos_` em todas as tabelas do módulo
--   - snake_case
--   - PKs em SERIAL (compatível com BD antigo) — troque por IDENTITY se preferir
--
-- Pré-requisitos:
--   - Tabela `users` deve existir (sistema atual)
--   - Tabela `clientes` (ou `customers`) deve existir
--
-- Se os nomes das suas tabelas existentes forem diferentes, ajuste os
-- FOREIGN KEYs das constraints abaixo.
-- =============================================================================

BEGIN;

-- =============================================================================
-- 1) CONFIGURAÇÃO DA EMPRESA EMISSORA
-- =============================================================================
-- Multi-tenant ready: uma linha por empresa que emite pareceres.
-- Se for single-tenant, mantém apenas o registro id=1.
CREATE TABLE laudos_empresas (
    id SERIAL PRIMARY KEY,
    razao_social VARCHAR(200) NOT NULL,
    cnpj VARCHAR(18) NOT NULL,
    email VARCHAR(150),
    telefone VARCHAR(30),
    telefone2 VARCHAR(30),
    cep VARCHAR(10),
    endereco TEXT,
    site VARCHAR(150),

    -- visual
    logo_path VARCHAR(500),
    carimbo_path VARCHAR(500),
    assinatura_padrao_path VARCHAR(500),

    -- configurações
    numbering_format VARCHAR(50) DEFAULT '{seq:0000}/{year}',
    repair_threshold NUMERIC(3,2) DEFAULT 0.60,
    image_max_width INTEGER DEFAULT 1200,
    public_validation_url VARCHAR(200),  -- ex: https://pgm.inf.br/validar

    created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modified TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- =============================================================================
-- 2) CONTADOR DE NUMERAÇÃO POR ANO
-- =============================================================================
CREATE TABLE laudos_contadores (
    id SERIAL PRIMARY KEY,
    empresa_id INTEGER NOT NULL REFERENCES laudos_empresas(id) ON DELETE CASCADE,
    ano INTEGER NOT NULL,
    ultimo_numero INTEGER NOT NULL DEFAULT 0,
    created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modified TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (empresa_id, ano)
);

-- =============================================================================
-- 3) PARECER TÉCNICO (entidade principal)
-- =============================================================================
CREATE TABLE laudos_pareceres (
    id SERIAL PRIMARY KEY,
    empresa_id INTEGER NOT NULL REFERENCES laudos_empresas(id),

    -- identificação
    numero VARCHAR(20) NOT NULL,             -- ex: "0001/2026"
    titulo VARCHAR(200) NOT NULL,
    public_hash VARCHAR(20) NOT NULL UNIQUE, -- para QR Code

    -- status
    status VARCHAR(20) NOT NULL DEFAULT 'rascunho'
        CHECK (status IN ('rascunho', 'em_analise', 'aprovado', 'concluido', 'enviado')),

    -- responsável técnico
    tecnico_user_id INTEGER REFERENCES users(id),
    tecnico_nome VARCHAR(150),               -- snapshot (para evitar perda se user for deletado)
    tecnico_registro VARCHAR(50),            -- CRT, CRA, etc.

    -- requerente / cliente (vinculado ao seu cadastro existente)
    requester_client_id INTEGER,             -- FK opcional para `clientes`
    requester_attention_to VARCHAR(150),     -- "A/C: ..."
    requester_company_name VARCHAR(200),
    requester_cnpj VARCHAR(18),
    requester_phone VARCHAR(30),
    requester_email VARCHAR(150),
    requester_cep VARCHAR(10),
    requester_address TEXT,

    -- conteúdo
    objetivo TEXT,
    documentacao TEXT,                       -- "Documentação considerada" — texto livre
    conclusao TEXT,

    -- comparativo reparo × substituição
    estimated_new_equipment NUMERIC(12,2) DEFAULT 0,
    show_comparison BOOLEAN DEFAULT TRUE,

    -- assinatura
    assinatura_path VARCHAR(500),            -- caminho do PNG da assinatura

    -- emissão
    cidade VARCHAR(100) DEFAULT 'Bento Gonçalves',
    data_emissao DATE,

    -- soft delete
    deleted TIMESTAMP NULL,
    deleted_by INTEGER REFERENCES users(id),

    -- timestamps
    created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modified TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER REFERENCES users(id),
    modified_by INTEGER REFERENCES users(id)
);

CREATE INDEX idx_laudos_pareceres_empresa ON laudos_pareceres(empresa_id);
CREATE INDEX idx_laudos_pareceres_status ON laudos_pareceres(status);
CREATE INDEX idx_laudos_pareceres_tecnico ON laudos_pareceres(tecnico_user_id);
CREATE INDEX idx_laudos_pareceres_client ON laudos_pareceres(requester_client_id);
CREATE INDEX idx_laudos_pareceres_numero ON laudos_pareceres(numero);
CREATE INDEX idx_laudos_pareceres_deleted ON laudos_pareceres(deleted);
CREATE INDEX idx_laudos_pareceres_emissao ON laudos_pareceres(data_emissao DESC);

-- Busca por texto (use GIN para acelerar buscas por título/CNPJ)
CREATE INDEX idx_laudos_pareceres_busca ON laudos_pareceres
    USING gin(to_tsvector('portuguese',
        coalesce(titulo,'') || ' ' ||
        coalesce(requester_company_name,'') || ' ' ||
        coalesce(requester_cnpj,'')
    ));

-- =============================================================================
-- 4) PRODUTOS / EQUIPAMENTOS
-- =============================================================================
CREATE TABLE laudos_produtos (
    id SERIAL PRIMARY KEY,
    parecer_id INTEGER NOT NULL REFERENCES laudos_pareceres(id) ON DELETE CASCADE,
    ordem INTEGER NOT NULL DEFAULT 0,        -- para ordenação na tela

    -- identificação
    nome VARCHAR(200),
    tipo VARCHAR(50),                        -- 'Servidor', 'Desktop', etc.
    serial_number VARCHAR(100),
    especificacoes TEXT,

    -- análise
    diagnostico TEXT,
    componentes_disponibilidade TEXT,
    licenciamento_obs TEXT,

    -- recomendação
    recomendacao VARCHAR(20) DEFAULT 'replace'
        CHECK (recomendacao IN ('repair', 'replace', 'partial')),

    created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modified TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_laudos_produtos_parecer ON laudos_produtos(parecer_id);

-- =============================================================================
-- 5) IMAGENS DOS PRODUTOS
-- =============================================================================
CREATE TABLE laudos_produto_imagens (
    id SERIAL PRIMARY KEY,
    produto_id INTEGER NOT NULL REFERENCES laudos_produtos(id) ON DELETE CASCADE,

    -- arquivo
    nome_original VARCHAR(255),
    file_path VARCHAR(500) NOT NULL,
    mime_type VARCHAR(50) DEFAULT 'image/jpeg',
    file_size INTEGER,
    width INTEGER,
    height INTEGER,

    -- metadados
    descricao VARCHAR(500),
    ordem INTEGER NOT NULL DEFAULT 0,

    created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_laudos_imagens_produto ON laudos_produto_imagens(produto_id);

-- =============================================================================
-- 6) PEÇAS SUGERIDAS POR PRODUTO
-- =============================================================================
CREATE TABLE laudos_produto_pecas (
    id SERIAL PRIMARY KEY,
    produto_id INTEGER NOT NULL REFERENCES laudos_produtos(id) ON DELETE CASCADE,
    catalogo_id INTEGER REFERENCES laudos_catalogo_pecas(id),  -- FK opcional ao catálogo

    -- snapshot do item (não muda mesmo se catálogo mudar)
    nome VARCHAR(200) NOT NULL,
    codigo VARCHAR(50),
    quantidade NUMERIC(10,2) NOT NULL DEFAULT 1,
    unidade VARCHAR(10) DEFAULT 'un',
    preco_unitario NUMERIC(12,2) NOT NULL DEFAULT 0,

    ordem INTEGER NOT NULL DEFAULT 0,

    created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_laudos_pecas_produto ON laudos_produto_pecas(produto_id);

-- =============================================================================
-- 7) SERVIÇOS PRESTADOS POR PRODUTO
-- =============================================================================
CREATE TABLE laudos_produto_servicos (
    id SERIAL PRIMARY KEY,
    produto_id INTEGER NOT NULL REFERENCES laudos_produtos(id) ON DELETE CASCADE,
    catalogo_id INTEGER REFERENCES laudos_catalogo_servicos(id),

    -- snapshot
    descricao VARCHAR(300) NOT NULL,
    horas NUMERIC(6,2) NOT NULL DEFAULT 1,
    valor_hora NUMERIC(10,2) NOT NULL DEFAULT 0,

    ordem INTEGER NOT NULL DEFAULT 0,

    created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_laudos_servicos_produto ON laudos_produto_servicos(produto_id);

-- =============================================================================
-- 8) ANEXOS (NF, garantia, e-mails, etc)
-- =============================================================================
CREATE TABLE laudos_anexos (
    id SERIAL PRIMARY KEY,
    parecer_id INTEGER NOT NULL REFERENCES laudos_pareceres(id) ON DELETE CASCADE,

    nome_original VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    mime_type VARCHAR(100),
    file_size INTEGER,
    descricao VARCHAR(300),

    created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER REFERENCES users(id)
);

CREATE INDEX idx_laudos_anexos_parecer ON laudos_anexos(parecer_id);

-- =============================================================================
-- 9) HISTÓRICO / AUDITORIA
-- =============================================================================
CREATE TABLE laudos_historico (
    id SERIAL PRIMARY KEY,
    parecer_id INTEGER NOT NULL REFERENCES laudos_pareceres(id) ON DELETE CASCADE,
    user_id INTEGER REFERENCES users(id),
    user_name_snapshot VARCHAR(150),         -- caso user seja deletado depois

    action VARCHAR(50) NOT NULL,             -- ex: 'parecer.status_changed'
    details JSONB,                           -- dados estruturados do evento

    created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_laudos_historico_parecer ON laudos_historico(parecer_id);
CREATE INDEX idx_laudos_historico_action ON laudos_historico(action);
CREATE INDEX idx_laudos_historico_created ON laudos_historico(created DESC);

-- =============================================================================
-- 10) CATÁLOGOS REUTILIZÁVEIS
-- =============================================================================
CREATE TABLE laudos_catalogo_pecas (
    id SERIAL PRIMARY KEY,
    empresa_id INTEGER NOT NULL REFERENCES laudos_empresas(id),
    nome VARCHAR(200) NOT NULL,
    codigo VARCHAR(50),
    preco_default NUMERIC(12,2) DEFAULT 0,
    unidade VARCHAR(10) DEFAULT 'un',
    categoria VARCHAR(100),                  -- 'Memória', 'Armazenamento', etc.
    ativo BOOLEAN DEFAULT TRUE,
    created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modified TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_catalogo_pecas_busca ON laudos_catalogo_pecas
    USING gin(to_tsvector('portuguese', coalesce(nome,'') || ' ' || coalesce(codigo,'')));
CREATE INDEX idx_catalogo_pecas_empresa ON laudos_catalogo_pecas(empresa_id);
CREATE INDEX idx_catalogo_pecas_ativo ON laudos_catalogo_pecas(ativo);

CREATE TABLE laudos_catalogo_servicos (
    id SERIAL PRIMARY KEY,
    empresa_id INTEGER NOT NULL REFERENCES laudos_empresas(id),
    descricao VARCHAR(300) NOT NULL,
    valor_hora_default NUMERIC(10,2) DEFAULT 0,
    horas_default NUMERIC(6,2) DEFAULT 1,
    categoria VARCHAR(100),
    ativo BOOLEAN DEFAULT TRUE,
    created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modified TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_catalogo_servicos_empresa ON laudos_catalogo_servicos(empresa_id);
CREATE INDEX idx_catalogo_servicos_ativo ON laudos_catalogo_servicos(ativo);

-- =============================================================================
-- 11) TEMPLATES DE TEXTO
-- =============================================================================
CREATE TABLE laudos_templates (
    id SERIAL PRIMARY KEY,
    empresa_id INTEGER NOT NULL REFERENCES laudos_empresas(id),
    tipo VARCHAR(20) NOT NULL CHECK (tipo IN ('diagnostico', 'conclusao', 'objetivo', 'documentacao')),
    nome VARCHAR(150) NOT NULL,
    conteudo TEXT NOT NULL,
    ordem INTEGER DEFAULT 0,
    ativo BOOLEAN DEFAULT TRUE,
    created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modified TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_templates_empresa_tipo ON laudos_templates(empresa_id, tipo, ativo);

-- =============================================================================
-- 12) TRIGGER PARA `modified` AUTOMÁTICO
-- =============================================================================
CREATE OR REPLACE FUNCTION laudos_set_modified()
RETURNS TRIGGER AS $$
BEGIN
    NEW.modified = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_laudos_pareceres_modified BEFORE UPDATE ON laudos_pareceres
    FOR EACH ROW EXECUTE FUNCTION laudos_set_modified();

CREATE TRIGGER trg_laudos_produtos_modified BEFORE UPDATE ON laudos_produtos
    FOR EACH ROW EXECUTE FUNCTION laudos_set_modified();

CREATE TRIGGER trg_laudos_empresas_modified BEFORE UPDATE ON laudos_empresas
    FOR EACH ROW EXECUTE FUNCTION laudos_set_modified();

CREATE TRIGGER trg_laudos_catalogo_pecas_modified BEFORE UPDATE ON laudos_catalogo_pecas
    FOR EACH ROW EXECUTE FUNCTION laudos_set_modified();

CREATE TRIGGER trg_laudos_catalogo_servicos_modified BEFORE UPDATE ON laudos_catalogo_servicos
    FOR EACH ROW EXECUTE FUNCTION laudos_set_modified();

CREATE TRIGGER trg_laudos_templates_modified BEFORE UPDATE ON laudos_templates
    FOR EACH ROW EXECUTE FUNCTION laudos_set_modified();

CREATE TRIGGER trg_laudos_contadores_modified BEFORE UPDATE ON laudos_contadores
    FOR EACH ROW EXECUTE FUNCTION laudos_set_modified();

-- =============================================================================
-- 13) FUNÇÃO: PRÓXIMO NÚMERO DE PARECER (atomic)
-- =============================================================================
CREATE OR REPLACE FUNCTION laudos_proximo_numero(p_empresa_id INTEGER)
RETURNS VARCHAR AS $$
DECLARE
    v_ano INTEGER;
    v_seq INTEGER;
BEGIN
    v_ano := EXTRACT(YEAR FROM CURRENT_DATE);

    -- Atomic increment com upsert
    INSERT INTO laudos_contadores (empresa_id, ano, ultimo_numero)
        VALUES (p_empresa_id, v_ano, 1)
        ON CONFLICT (empresa_id, ano)
        DO UPDATE SET ultimo_numero = laudos_contadores.ultimo_numero + 1
        RETURNING ultimo_numero INTO v_seq;

    RETURN LPAD(v_seq::TEXT, 4, '0') || '/' || v_ano::TEXT;
END;
$$ LANGUAGE plpgsql;

-- Uso: SELECT laudos_proximo_numero(1);

-- =============================================================================
-- 14) VIEW DE TOTAIS
-- =============================================================================
CREATE OR REPLACE VIEW laudos_totais_view AS
SELECT
    p.id AS parecer_id,
    p.numero,
    COALESCE(SUM(pe.quantidade * pe.preco_unitario), 0) AS total_pecas,
    COALESCE(SUM(s.horas * s.valor_hora), 0) AS total_servicos,
    COALESCE(SUM(pe.quantidade * pe.preco_unitario), 0)
        + COALESCE(SUM(s.horas * s.valor_hora), 0) AS total_geral,
    p.estimated_new_equipment AS total_novo,
    CASE
        WHEN p.estimated_new_equipment > 0 THEN
            ROUND((COALESCE(SUM(pe.quantidade * pe.preco_unitario), 0)
                 + COALESCE(SUM(s.horas * s.valor_hora), 0))
                 / p.estimated_new_equipment * 100, 2)
        ELSE NULL
    END AS percentual_reparo
FROM laudos_pareceres p
LEFT JOIN laudos_produtos pr ON pr.parecer_id = p.id
LEFT JOIN laudos_produto_pecas pe ON pe.produto_id = pr.id
LEFT JOIN laudos_produto_servicos s ON s.produto_id = pr.id
WHERE p.deleted IS NULL
GROUP BY p.id;

COMMIT;

-- =============================================================================
-- COMENTÁRIOS / DOCUMENTAÇÃO INLINE
-- =============================================================================
COMMENT ON TABLE laudos_pareceres IS 'Pareceres técnicos emitidos. Soft delete via campo `deleted`.';
COMMENT ON COLUMN laudos_pareceres.public_hash IS 'Hash de 12 chars usado no QR Code de validação pública';
COMMENT ON COLUMN laudos_pareceres.numero IS 'Formato: NNNN/AAAA, sequencial por ano via laudos_contadores';
COMMENT ON COLUMN laudos_pareceres.requester_client_id IS 'FK opcional para tabela `clientes` do sistema';

COMMENT ON TABLE laudos_produtos IS 'Equipamentos avaliados em cada parecer. Um parecer pode ter N produtos.';
COMMENT ON TABLE laudos_produto_imagens IS 'Fotos do equipamento. Armazenadas em filesystem, path em file_path.';
COMMENT ON TABLE laudos_historico IS 'Log imutável de eventos. Não permitir UPDATE/DELETE em produção.';

COMMENT ON FUNCTION laudos_proximo_numero IS 'Gera próximo número sequencial atomicamente. Use SELECT laudos_proximo_numero(empresa_id).';
