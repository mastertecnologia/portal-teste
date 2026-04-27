-- =============================================================================
-- Tabela: contratos_horas (horas de contrato por cliente)
-- Banco: PostgreSQL
--
-- Vínculo: idcliente → clientes.id (tickets.idcliente no chamado).
--          idempresa → empresa logada (Auth).
-- Único contrato ativo por par (idcliente, idempresa) — constraint UNIQUE abaixo.
--
-- Instalação nova: execute este arquivo OU rode a migration
--   config/Migrations/20260321170000_ContratosHorasCompleto.php
--   (bin/cake migrations migrate)
--
-- Bases que já tinham só minutos_*: a migration acrescenta as colunas do formulário
-- (data_inicio, saldo_horas, valores, etc.) sem recriar a tabela.
--
-- Se a migration falhar ao criar o UNIQUE por dados duplicados, opcionalmente:
--   DELETE FROM contratos_horas a
--   USING contratos_horas b
--   WHERE a.id > b.id
--     AND a.idcliente = b.idcliente
--     AND a.idempresa = b.idempresa;
-- (revise antes de executar; faça backup)
-- =============================================================================

CREATE TABLE IF NOT EXISTS contratos_horas (
	id SERIAL PRIMARY KEY,
	idcliente INTEGER NOT NULL,
	idempresa INTEGER NOT NULL,
	minutos_contratados INTEGER NOT NULL DEFAULT 0,
	minutos_consumidos INTEGER NOT NULL DEFAULT 0,
	data_inicio DATE NULL,
	data_fim DATE NULL,
	horas_contratadas NUMERIC(14,4) NULL,
	horas_mensais NUMERIC(14,4) NULL,
	saldo_horas NUMERIC(14,4) NULL,
	horas_utilizadas NUMERIC(14,4) NOT NULL DEFAULT 0,
	ativo BOOLEAN NOT NULL DEFAULT TRUE,
	valor_hora_comercial NUMERIC(14,4) NULL,
	valor_hora_adicional_comercial NUMERIC(14,4) NULL,
	valor_hora_especial NUMERIC(14,4) NULL,
	contatos_email_relatorio TEXT NULL,
	segundos_consumidos BIGINT NULL,
	horas_consumidas NUMERIC(14,4) NULL,
	saldo NUMERIC(14,4) NULL,
	saldo_minutos INTEGER NULL,
	created TIMESTAMP WITHOUT TIME ZONE NULL,
	modified TIMESTAMP WITHOUT TIME ZONE NULL,
	CONSTRAINT contratos_horas_idcliente_idempresa_key UNIQUE (idcliente, idempresa)
);

CREATE INDEX IF NOT EXISTS idx_contratos_horas_idcliente ON contratos_horas (idcliente);
CREATE INDEX IF NOT EXISTS idx_contratos_horas_idempresa ON contratos_horas (idempresa);

COMMENT ON TABLE contratos_horas IS 'Horas de contrato por cliente: formulário ContratosHoras; timer debita minutos_consumidos ou saldo_horas/saldo/horas_consumidas conforme colunas preenchidas.';
