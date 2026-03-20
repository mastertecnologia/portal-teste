-- Relato do técnico: o que foi feito no atendimento (ex.: reparo realizado).
-- PostgreSQL 13+ (Debian/pgdg). Execute uma vez no banco da aplicação.
--
-- Se a tabela estiver em outro schema, ajuste o prefixo, ex.:
--   ALTER TABLE meu_schema.tickets ADD COLUMN ...

BEGIN;

ALTER TABLE public.tickets
	ADD COLUMN IF NOT EXISTS descricao_atendimento TEXT;

COMMENT ON COLUMN public.tickets.descricao_atendimento IS
	'Relato do técnico sobre o que foi feito no atendimento (visível ao cliente e na impressão).';

COMMIT;
