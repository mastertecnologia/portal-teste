-- Filas por empresa + vínculo técnico ↔ fila + ticket na fila atual.
-- PostgreSQL (ajuste schema se necessário).
--
-- Se `queues` já existir sem name/idempresa/codigo/sort_order, rode também:
--   config/schema/postgres_queues_patch_idempotent.sql

CREATE TABLE IF NOT EXISTS public.queues (
	id SERIAL PRIMARY KEY,
	name VARCHAR(255) NOT NULL,
	idempresa INTEGER NOT NULL,
	codigo VARCHAR(32) NULL,
	sort_order INTEGER NOT NULL DEFAULT 0
);

CREATE INDEX IF NOT EXISTS ix_queues_idempresa ON public.queues (idempresa);

CREATE TABLE IF NOT EXISTS public.queues_users (
	id SERIAL PRIMARY KEY,
	queue_id INTEGER NOT NULL REFERENCES public.queues (id) ON DELETE CASCADE,
	user_id INTEGER NOT NULL REFERENCES public.users (id) ON DELETE CASCADE,
	UNIQUE (queue_id, user_id)
);

CREATE INDEX IF NOT EXISTS ix_queues_users_user ON public.queues_users (user_id);

ALTER TABLE public.tickets
	ADD COLUMN IF NOT EXISTS queue_id INTEGER NULL REFERENCES public.queues (id) ON DELETE SET NULL;

CREATE INDEX IF NOT EXISTS ix_tickets_queue_id ON public.tickets (queue_id);

-- Espelho do responsável (alinhado a idtecnico_responsavel; preenchido via app / migration)
ALTER TABLE public.tickets
	ADD COLUMN IF NOT EXISTS owner_id INTEGER NULL REFERENCES public.users (id) ON DELETE SET NULL;

COMMENT ON TABLE public.queues IS 'Filas de atendimento por empresa (Master/PGM etc.)';
COMMENT ON COLUMN public.queues.codigo IS 'Opcional: n1, n2, n3… para alinhar a filtros legados (fila_suporte)';
COMMENT ON TABLE public.queues_users IS 'Técnicos habilitados por fila';
COMMENT ON COLUMN public.tickets.queue_id IS 'Fila em que o chamado está';
COMMENT ON COLUMN public.tickets.owner_id IS 'Espelho de idtecnico_responsavel (sincronizado no app; não usar como fonte de verdade)';
