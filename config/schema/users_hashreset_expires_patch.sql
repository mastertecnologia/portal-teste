-- Patch idempotente: janela de validade do link de redefinição de senha (10 minutos no aplicativo).
-- Executar no Postgres do portal antes/atualizar deploy que usa hashreset_expires.

ALTER TABLE public.users ADD COLUMN IF NOT EXISTS hashreset_expires TIMESTAMPTZ NULL;

COMMENT ON COLUMN public.users.hashreset_expires IS 'Expiração do token em users.hashreset para reset de senha; NULL = legado ou fluxo sem janela.';
