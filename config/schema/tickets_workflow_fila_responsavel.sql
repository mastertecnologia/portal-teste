-- Workflow: técnico responsável, fila de suporte e nível (N1–N3, NOC, requisições).
-- Execute no banco da aplicação (ajuste o schema se não for public).

-- PostgreSQL
ALTER TABLE public.tickets
	ADD COLUMN IF NOT EXISTS idtecnico_responsavel INTEGER NULL,
	ADD COLUMN IF NOT EXISTS fila_suporte VARCHAR(32) NOT NULL DEFAULT 'n1',
	ADD COLUMN IF NOT EXISTS nivel_atendimento SMALLINT NOT NULL DEFAULT 1;

COMMENT ON COLUMN public.tickets.idtecnico_responsavel IS 'Usuário (role técnico) responsável atual pelo atendimento';
COMMENT ON COLUMN public.tickets.fila_suporte IS 'Código: n1 | n2 | n3 | noc | servico';
COMMENT ON COLUMN public.tickets.nivel_atendimento IS '1=N1, 2=N2, 3=N3, 4=NOC, 5=Requisições';

-- Opcional: FK lógica para users (descomente se desejar integridade referencial)
-- ALTER TABLE public.tickets ADD CONSTRAINT fk_tickets_tecnico_resp FOREIGN KEY (idtecnico_responsavel) REFERENCES public.users(id);
