-- Patch idempotente: garante colunas esperadas pelo portal em `queues` e `queues_users`.
-- Uso: psql -U ... -d pgm -f config/schema/postgres_queues_patch_idempotent.sql
-- Requer PostgreSQL 9.5+ (ADD COLUMN IF NOT EXISTS a partir do 9.1 para algumas versões;
-- para máxima compatibilidade usamos blocos DO onde IF NOT EXISTS não existir.)
--
-- Esperado pelo código (QueuesController / Tickets):
--   queues: id, name, idempresa, codigo, sort_order, created, modified
--   queues_users: id, queue_id, user_id, created, modified (timestamps opcionais)

-- ---------------------------------------------------------------------------
-- queues.name
-- ---------------------------------------------------------------------------
ALTER TABLE public.queues ADD COLUMN IF NOT EXISTS name varchar(255);
UPDATE public.queues SET name = 'Fila' WHERE name IS NULL OR trim(name) = '';
ALTER TABLE public.queues ALTER COLUMN name SET NOT NULL;

-- ---------------------------------------------------------------------------
-- queues.idempresa (empresa da fila — mesmo conceito de company_id)
-- ---------------------------------------------------------------------------
ALTER TABLE public.queues ADD COLUMN IF NOT EXISTS idempresa bigint;
UPDATE public.queues q
SET idempresa = sub.m
FROM (SELECT MIN(id) AS m FROM public.empresas) sub
WHERE q.idempresa IS NULL AND sub.m IS NOT NULL;
-- Fallback se não houver linha em empresas (evita NULL eterno)
-- Só use 1 se existir em empresas; senão crie uma empresa antes ou ajuste manualmente.
UPDATE public.queues SET idempresa = 1 WHERE idempresa IS NULL;
ALTER TABLE public.queues ALTER COLUMN idempresa SET NOT NULL;

-- ---------------------------------------------------------------------------
-- queues.codigo, sort_order, timestamps
-- ---------------------------------------------------------------------------
ALTER TABLE public.queues ADD COLUMN IF NOT EXISTS codigo varchar(32) NULL;
ALTER TABLE public.queues ADD COLUMN IF NOT EXISTS sort_order integer NOT NULL DEFAULT 0;
ALTER TABLE public.queues ADD COLUMN IF NOT EXISTS created timestamptz NULL;
ALTER TABLE public.queues ADD COLUMN IF NOT EXISTS modified timestamptz NULL;

-- ---------------------------------------------------------------------------
-- Índice por empresa
-- ---------------------------------------------------------------------------
CREATE INDEX IF NOT EXISTS ix_queues_idempresa ON public.queues (idempresa);

-- ---------------------------------------------------------------------------
-- FK queues.idempresa -> empresas.id (ignora se já existir ou empresas inexistente)
-- ---------------------------------------------------------------------------
DO $$
BEGIN
	ALTER TABLE public.queues
		ADD CONSTRAINT queues_idempresa_fkey
		FOREIGN KEY (idempresa) REFERENCES public.empresas (id)
		ON UPDATE CASCADE ON DELETE RESTRICT;
EXCEPTION
	WHEN duplicate_object THEN NULL;
	WHEN undefined_table THEN NULL;
END $$;

-- ---------------------------------------------------------------------------
-- queues_users: timestamps (opcionais para o ORM)
-- ---------------------------------------------------------------------------
ALTER TABLE public.queues_users ADD COLUMN IF NOT EXISTS created timestamptz NULL;
ALTER TABLE public.queues_users ADD COLUMN IF NOT EXISTS modified timestamptz NULL;
