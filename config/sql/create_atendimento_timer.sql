-- =============================================================================
-- Tabela: atendimento_timer (timer de horas técnicas por ticket)
-- Banco: PostgreSQL (banco do portal)
-- Execute no pgAdmin, psql ou outro cliente. Pode rodar mais de uma vez:
-- CREATE TABLE IF NOT EXISTS evita erro se a tabela já existir.
-- =============================================================================

CREATE TABLE IF NOT EXISTS atendimento_timer (
    id SERIAL PRIMARY KEY,
    idticket INTEGER NOT NULL,
    idusuario INTEGER NOT NULL,
    idempresa INTEGER NOT NULL,
    hora_inicio TIMESTAMP WITHOUT TIME ZONE NOT NULL,
    hora_pausa TIMESTAMP WITHOUT TIME ZONE NULL,
    hora_fim TIMESTAMP WITHOUT TIME ZONE NULL,
    duracao_calculada INTEGER NULL,
    created TIMESTAMP WITHOUT TIME ZONE NULL,
    modified TIMESTAMP WITHOUT TIME ZONE NULL
);

-- Índices para buscas por ticket e usuário
CREATE INDEX IF NOT EXISTS idx_atendimento_timer_idticket ON atendimento_timer (idticket);
CREATE INDEX IF NOT EXISTS idx_atendimento_timer_idusuario ON atendimento_timer (idusuario);
CREATE INDEX IF NOT EXISTS idx_atendimento_timer_hora_fim ON atendimento_timer (hora_fim);

COMMENT ON TABLE atendimento_timer IS 'Registros do timer de horas técnicas por ticket (início/pausa/fim).';
