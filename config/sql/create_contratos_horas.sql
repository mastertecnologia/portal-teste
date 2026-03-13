-- =============================================================================
-- Tabela: contratos_horas (horas de contrato por cliente – saldo e consumido)
-- Banco: PostgreSQL (banco do portal)
-- Use para exibir "Horas de contrato" na tela do ticket e subtrair ao finalizar o timer.
-- =============================================================================

CREATE TABLE IF NOT EXISTS contratos_horas (
    id SERIAL PRIMARY KEY,
    idcliente INTEGER NOT NULL,
    idempresa INTEGER NOT NULL,
    minutos_contratados INTEGER NOT NULL DEFAULT 0,
    minutos_consumidos INTEGER NOT NULL DEFAULT 0,
    created TIMESTAMP WITHOUT TIME ZONE NULL,
    modified TIMESTAMP WITHOUT TIME ZONE NULL,
    UNIQUE(idcliente, idempresa)
);

CREATE INDEX IF NOT EXISTS idx_contratos_horas_idcliente ON contratos_horas (idcliente);
CREATE INDEX IF NOT EXISTS idx_contratos_horas_idempresa ON contratos_horas (idempresa);

COMMENT ON TABLE contratos_horas IS 'Horas de contrato por cliente: minutos_contratados (total), minutos_consumidos (usado). Saldo = contratados - consumidos.';
