-- =============================================================================
-- PGM Portal — Verificação + correção idempotente (PostgreSQL)
-- Dashboard funcionários: tickets, SLA enterprise, ranking técnicos, datas.
--
-- USO (recomendado — IDs de situação automáticos):
--   1) Na raiz do repo:  php scripts/generate_pgm_sit_config.php
--   2) Um arquivo só (pgAdmin ou psql):  php scripts/build_pgm_postgres_full_sql.php
--      → gera/atualiza scripts/postgres/pgm_dashboard_verify_and_patch_FULL.sql (também versionado no repo com placeholders).
--   Ou psql em dois passos:
--      psql ... -f scripts/postgres/pgm_sit_config_generated.sql -f scripts/postgres/pgm_dashboard_verify_and_patch.sql
--
-- USO direto (sem PHP): edite o bloco entre >> BEGIN_PGM_SIT_CONFIG e << END_PGM_SIT_CONFIG.
--
--   psql:  psql -U seu_usuario -d seu_banco -v ON_ERROR_STOP=1 -f pgm_dashboard_verify_and_patch.sql
--   pgAdmin: abra este arquivo e execute tudo (Query Tool).
--
-- SEGURANÇA:
--   • Backup (pg_dump) antes em produção.
--   • Usuário com permissão CREATE/ALTER no schema public (ou ajuste search_path).
--   • Não executa DELETE em tickets/users; apenas DDL idempotente + UPDATEs de correção listados.
--   • Relatório da última execução: public.pgm_maintenance_report (é truncado no início).
--
-- APÓS RODAR (CakePHP):
--   bin/cake tickets_sla recalculate --empresa=ID
-- =============================================================================

SET client_min_messages TO notice;
SET search_path TO public;

-- >> BEGIN_PGM_SIT_CONFIG (substituído automaticamente por build_pgm_postgres_full_sql.php)
DO $cfg$
BEGIN
  PERFORM set_config('pgm.sit_resolvido', '2', false);
  PERFORM set_config('pgm.sit_fechado', '3', false);
END $cfg$;
-- << END_PGM_SIT_CONFIG

-- Relatório persistente (truncado a cada run — função pgm_log precisa de tabela não-temp)
CREATE TABLE IF NOT EXISTS public.pgm_maintenance_report (
  id           bigserial PRIMARY KEY,
  run_at       timestamptz NOT NULL DEFAULT now(),
  severidade   text NOT NULL,
  codigo       text NOT NULL,
  mensagem     text NOT NULL,
  detalhe      text
);

CREATE INDEX IF NOT EXISTS ix_pgm_maint_run_at ON public.pgm_maintenance_report (run_at DESC);

TRUNCATE public.pgm_maintenance_report;

CREATE OR REPLACE FUNCTION public.pgm_log(p_sev text, p_code text, p_msg text, p_det text DEFAULT NULL)
RETURNS void
LANGUAGE plpgsql
AS $$
BEGIN
  INSERT INTO public.pgm_maintenance_report (severidade, codigo, mensagem, detalhe)
  VALUES (p_sev, p_code, p_msg, p_det);
  IF p_sev = 'ERR' THEN
    RAISE WARNING '[ERR] %: % %', p_code, p_msg, COALESCE(p_det, '');
  ELSIF p_sev = 'WARN' OR p_sev = 'FIX' THEN
    RAISE NOTICE '[%] %: % %', p_sev, p_code, p_msg, COALESCE('— ' || p_det, '');
  ELSE
    RAISE NOTICE '[%] %: %', p_sev, p_code, p_msg;
  END IF;
END;
$$;

COMMENT ON TABLE public.pgm_maintenance_report IS 'Última execução do script pgm_dashboard_verify_and_patch.sql (truncado a cada run).';

-- -----------------------------------------------------------------------------
-- 1) Verificações de ambiente
-- -----------------------------------------------------------------------------
DO $sec$
DECLARE
  v_super bool;
BEGIN
  SELECT COALESCE(rolsuper, false) INTO v_super FROM pg_roles WHERE rolname = current_user;
  IF v_super THEN
    PERFORM public.pgm_log('WARN', 'SEC_SUPERUSER',
      'Sessão como superuser',
      'Em produção prefira usuário com privilégios mínimos no schema da aplicação.');
  ELSE
    PERFORM public.pgm_log('OK', 'SEC_USER', 'Usuário não é superuser', current_user);
  END IF;

  IF current_schema() IS DISTINCT FROM 'public' THEN
    PERFORM public.pgm_log('INFO', 'SEC_SCHEMA', 'search_path / schema atual', current_schema());
  END IF;
END $sec$;

-- -----------------------------------------------------------------------------
-- 2) Presença de tabelas
-- -----------------------------------------------------------------------------
DO $chk$
BEGIN
  IF to_regclass('public.tickets') IS NULL THEN
    PERFORM public.pgm_log('ERR', 'MISSING_TICKETS',
      'Tabela public.tickets não existe',
      'Crie o schema pela aplicação/migrations. Nenhum ALTER em tickets será aplicado.');
  ELSE
    PERFORM public.pgm_log('OK', 'TBL_TICKETS', 'Tabela tickets encontrada', NULL);
  END IF;

  IF to_regclass('public.users') IS NULL THEN
    PERFORM public.pgm_log('WARN', 'MISSING_USERS', 'Tabela public.users não encontrada', NULL);
  ELSE
    PERFORM public.pgm_log('OK', 'TBL_USERS', 'Tabela users encontrada', NULL);
  END IF;

  IF to_regclass('public.empresasusers') IS NULL THEN
    PERFORM public.pgm_log('WARN', 'MISSING_EMPUSERS', 'Tabela public.empresasusers não encontrada', NULL);
  ELSE
    PERFORM public.pgm_log('OK', 'TBL_EMPUSERS', 'Tabela empresasusers encontrada', NULL);
  END IF;

  IF to_regclass('public.empresas') IS NULL THEN
    PERFORM public.pgm_log('WARN', 'MISSING_EMPRESAS', 'Tabela public.empresas não encontrada', 'Seed sla_policies será ignorado.');
  ELSE
    PERFORM public.pgm_log('OK', 'TBL_EMPRESAS', 'Tabela empresas encontrada', NULL);
  END IF;
END $chk$;

-- -----------------------------------------------------------------------------
-- 3) Colunas e índices em tickets (somente se a tabela existir)
-- -----------------------------------------------------------------------------
DO $alts$
BEGIN
  IF to_regclass('public.tickets') IS NULL THEN
    PERFORM public.pgm_log('WARN', 'SKIP_TICKET_DDL', 'Pulado ALTER/INDEX em tickets (tabela ausente)', NULL);
    RETURN;
  END IF;

  ALTER TABLE public.tickets ADD COLUMN IF NOT EXISTS idtecnico_responsavel integer NULL;

  -- CakePHP / legado: alguns bancos não têm modified em tickets
  ALTER TABLE public.tickets ADD COLUMN IF NOT EXISTS modified timestamptz NULL;

  ALTER TABLE public.tickets
    ADD COLUMN IF NOT EXISTS tipo_ticket varchar(32) NULL,
    ADD COLUMN IF NOT EXISTS categoria varchar(128) NULL,
    ADD COLUMN IF NOT EXISTS subcategoria varchar(128) NULL,
    ADD COLUMN IF NOT EXISTS prioridade varchar(8) NULL,
    ADD COLUMN IF NOT EXISTS impacto varchar(16) NULL,
    ADD COLUMN IF NOT EXISTS urgencia varchar(16) NULL,
    ADD COLUMN IF NOT EXISTS origem_ticket varchar(32) NULL DEFAULT 'portal',
    ADD COLUMN IF NOT EXISTS sla_policy_id integer NULL,
    ADD COLUMN IF NOT EXISTS sla_resposta_minutos integer NULL,
    ADD COLUMN IF NOT EXISTS sla_resolucao_minutos integer NULL,
    ADD COLUMN IF NOT EXISTS sla_resposta_pausado boolean NOT NULL DEFAULT false,
    ADD COLUMN IF NOT EXISTS sla_resolucao_pausado boolean NOT NULL DEFAULT false,
    ADD COLUMN IF NOT EXISTS sla_percentual_consumido numeric(6,2) NULL,
    ADD COLUMN IF NOT EXISTS sla_status varchar(16) NULL,
    ADD COLUMN IF NOT EXISTS data_primeira_resposta timestamptz NULL,
    ADD COLUMN IF NOT EXISTS data_resolucao timestamptz NULL,
    ADD COLUMN IF NOT EXISTS data_fechamento timestamptz NULL,
    ADD COLUMN IF NOT EXISTS data_limite_resposta timestamptz NULL,
    ADD COLUMN IF NOT EXISTS data_limite_resolucao timestamptz NULL,
    ADD COLUMN IF NOT EXISTS tempo_total_atendimento integer NULL,
    ADD COLUMN IF NOT EXISTS tempo_total_pausado integer NULL,
    ADD COLUMN IF NOT EXISTS causa_raiz text NULL,
    ADD COLUMN IF NOT EXISTS resolucao text NULL,
    ADD COLUMN IF NOT EXISTS fechado_automaticamente boolean NOT NULL DEFAULT false;

  ALTER TABLE public.tickets
    ADD COLUMN IF NOT EXISTS queue_id integer NULL,
    ADD COLUMN IF NOT EXISTS owner_id integer NULL;

  COMMENT ON COLUMN public.tickets.idtecnico_responsavel IS 'Técnico (users.id); ranking do dashboard';
  COMMENT ON COLUMN public.tickets.data_resolucao IS 'Preencher ao resolver; “fechados hoje” e gráficos';
  COMMENT ON COLUMN public.tickets.sla_status IS 'dentro_sla | em_risco | violado (job Cake tickets_sla)';

  PERFORM public.pgm_log('FIX', 'COL_TICKETS', 'Colunas em tickets verificadas/criadas (IF NOT EXISTS)', NULL);
END $alts$;

DO $idx$
BEGIN
  IF to_regclass('public.tickets') IS NULL THEN
    RETURN;
  END IF;
  CREATE INDEX IF NOT EXISTS ix_tickets_prioridade ON public.tickets (prioridade);
  CREATE INDEX IF NOT EXISTS ix_tickets_tipo_ticket ON public.tickets (tipo_ticket);
  CREATE INDEX IF NOT EXISTS ix_tickets_sla_status ON public.tickets (sla_status);
  CREATE INDEX IF NOT EXISTS ix_tickets_data_limite_resposta ON public.tickets (data_limite_resposta);
  CREATE INDEX IF NOT EXISTS ix_tickets_data_limite_resolucao ON public.tickets (data_limite_resolucao);
  CREATE INDEX IF NOT EXISTS ix_tickets_idempresa_created ON public.tickets (idempresa, created);
  CREATE INDEX IF NOT EXISTS ix_tickets_idempresa_data_resolucao ON public.tickets (idempresa, data_resolucao);
  CREATE INDEX IF NOT EXISTS ix_tickets_idtecnico_responsavel ON public.tickets (idtecnico_responsavel);
  PERFORM public.pgm_log('OK', 'IDX_TICKETS', 'Índices em tickets OK', NULL);
END $idx$;

-- -----------------------------------------------------------------------------
-- 4) sla_policies + seed P1–P4
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.sla_policies (
  id serial PRIMARY KEY,
  idempresa integer NOT NULL,
  nome varchar(128) NOT NULL,
  prioridade varchar(8) NOT NULL,
  tipo_ticket varchar(32) NULL,
  resposta_minutos integer NOT NULL,
  resolucao_minutos integer NOT NULL,
  ativo boolean NOT NULL DEFAULT true,
  created timestamptz NULL,
  modified timestamptz NULL
);

CREATE INDEX IF NOT EXISTS ix_sla_policies_idempresa ON public.sla_policies (idempresa);
CREATE INDEX IF NOT EXISTS ix_sla_policies_prioridade ON public.sla_policies (prioridade);

CREATE UNIQUE INDEX IF NOT EXISTS ux_sla_policies_emp_pri_default
  ON public.sla_policies (idempresa, prioridade)
  WHERE tipo_ticket IS NULL;

DO $fk$
BEGIN
  IF to_regclass('public.empresas') IS NULL THEN
    RETURN;
  END IF;
  BEGIN
    ALTER TABLE public.sla_policies
      ADD CONSTRAINT fk_sla_policies_empresa FOREIGN KEY (idempresa)
      REFERENCES public.empresas (id) ON UPDATE CASCADE ON DELETE CASCADE;
    PERFORM public.pgm_log('FIX', 'FK_SLA_EMP', 'FK sla_policies.idempresa → empresas', NULL);
  EXCEPTION
    WHEN duplicate_object THEN
      PERFORM public.pgm_log('INFO', 'FK_SLA_EMP', 'FK sla_policies↔empresas já existia', NULL);
  END;
END $fk$;

DO $seed$
BEGIN
  IF to_regclass('public.empresas') IS NULL THEN
    PERFORM public.pgm_log('WARN', 'SEED_SLA', 'Seed sla_policies não executado (tabela empresas ausente)', NULL);
  ELSE
    INSERT INTO public.sla_policies (idempresa, nome, prioridade, tipo_ticket, resposta_minutos, resolucao_minutos, ativo, created, modified)
    SELECT e.id, v.nome, v.prioridade, NULL, v.resp_min, v.res_min, true, now(), now()
    FROM public.empresas e
    CROSS JOIN (VALUES
      ('Padrão P1', 'P1', 15, 240),
      ('Padrão P2', 'P2', 30, 480),
      ('Padrão P3', 'P3', 60, 1440),
      ('Padrão P4', 'P4', 240, 4320)
    ) AS v(nome, prioridade, resp_min, res_min)
    WHERE NOT EXISTS (
      SELECT 1 FROM public.sla_policies s
      WHERE s.idempresa = e.id AND s.prioridade = v.prioridade AND s.tipo_ticket IS NULL
    );
    PERFORM public.pgm_log('OK', 'SLA_POLICIES', 'Tabela sla_policies e seeds P1–P4 verificados', NULL);
  END IF;
END $seed$;

DO $fkt$
BEGIN
  IF to_regclass('public.tickets') IS NULL OR to_regclass('public.sla_policies') IS NULL THEN
    RETURN;
  END IF;
  BEGIN
    ALTER TABLE public.tickets
      ADD CONSTRAINT fk_tickets_sla_policy FOREIGN KEY (sla_policy_id)
      REFERENCES public.sla_policies (id) ON UPDATE CASCADE ON DELETE SET NULL;
    PERFORM public.pgm_log('FIX', 'FK_TICKET_SLA', 'FK tickets.sla_policy_id criada', NULL);
  EXCEPTION
    WHEN duplicate_object THEN
      PERFORM public.pgm_log('INFO', 'FK_TICKET_SLA', 'FK tickets.sla_policy_id já existia', NULL);
  END;
END $fkt$;

-- -----------------------------------------------------------------------------
-- 5) Correções de dados
-- -----------------------------------------------------------------------------
DO $own$
DECLARE
  n int;
BEGIN
  IF to_regclass('public.tickets') IS NULL THEN
    RETURN;
  END IF;
  UPDATE public.tickets t
  SET owner_id = t.idtecnico_responsavel
  WHERE t.owner_id IS NULL
    AND t.idtecnico_responsavel IS NOT NULL;
  GET DIAGNOSTICS n = ROW_COUNT;
  IF n > 0 THEN
    PERFORM public.pgm_log('FIX', 'SYNC_OWNER', format('owner_id atualizado em %s linha(s)', n), NULL);
  ELSE
    PERFORM public.pgm_log('INFO', 'SYNC_OWNER', 'Nada a sincronizar owner_id ← idtecnico_responsavel', NULL);
  END IF;
END $own$;

DO $dr$
DECLARE
  n int;
  v_res int;
  v_fec int;
  v_coalesce text;
  v_src text;
BEGIN
  IF to_regclass('public.tickets') IS NULL THEN
    RETURN;
  END IF;
  BEGIN
    v_res := COALESCE(current_setting('pgm.sit_resolvido', true), '0')::int;
    v_fec := COALESCE(current_setting('pgm.sit_fechado', true), '0')::int;
  EXCEPTION WHEN OTHERS THEN
    PERFORM public.pgm_log('WARN', 'BACKFILL_CFG', 'Não foi possível ler pgm.sit_resolvido/fechado', SQLERRM);
    RETURN;
  END;

  IF v_res = 0 OR v_fec = 0 THEN
    PERFORM public.pgm_log('WARN', 'BACKFILL_CFG', 'Configure set_config pgm.sit_resolvido / pgm.sit_fechado no script', NULL);
    RETURN;
  END IF;

  SELECT string_agg('t.' || quote_ident(c.col), ', ' ORDER BY c.ord)
  INTO v_coalesce
  FROM (
    SELECT u.col, u.ord
    FROM unnest(ARRAY['modified', 'updated', 'updated_at', 'created']) WITH ORDINALITY AS u(col, ord)
    WHERE EXISTS (
      SELECT 1 FROM information_schema.columns x
      WHERE x.table_schema = 'public' AND x.table_name = 'tickets' AND x.column_name = u.col
    )
  ) c;

  IF v_coalesce IS NULL OR v_coalesce = '' THEN
    PERFORM public.pgm_log('WARN', 'BACKFILL_DATA_RESOLUCAO',
      'Nenhuma coluna de data (modified/updated/updated_at/created) em tickets', 'Backfill ignorado.');
    RETURN;
  END IF;

  v_src := 'COALESCE(' || v_coalesce || ')';
  EXECUTE 'UPDATE public.tickets t SET data_resolucao = ' || v_src
    || ' WHERE t.data_resolucao IS NULL AND (' || v_src || ') IS NOT NULL AND t.situacao IN ('
    || v_res::text || ', ' || v_fec::text || ')';
  GET DIAGNOSTICS n = ROW_COUNT;
  IF n > 0 THEN
    PERFORM public.pgm_log('FIX', 'BACKFILL_DATA_RESOLUCAO',
      format('%s ticket(s): data_resolucao ← %s (sit %s,%s)', n, v_src, v_res, v_fec),
      'Prioridade: modified, updated, updated_at, created. Confirme IDs de situação no PHP.');
  ELSE
    PERFORM public.pgm_log('INFO', 'BACKFILL_DATA_RESOLUCAO', 'Nenhum backfill de data_resolucao necessário', NULL);
  END IF;
END $dr$;

DO $qe$
DECLARE
  n int;
BEGIN
  IF to_regclass('public.tickets') IS NULL THEN
    RETURN;
  END IF;
  SELECT count(*) INTO n FROM public.tickets WHERE idempresa IS NULL;
  IF n > 0 THEN
    PERFORM public.pgm_log('WARN', 'DATA_IDEMPRESA_NULL', format('%s ticket(s) com idempresa NULL', n), 'Corrija na aplicação.');
  ELSE
    PERFORM public.pgm_log('OK', 'DATA_IDEMPRESA', 'Sem tickets com idempresa NULL', NULL);
  END IF;
END $qe$;

DO $eu$
DECLARE
  n int;
  v_has_inativo boolean;
BEGIN
  IF to_regclass('public.empresasusers') IS NULL OR to_regclass('public.users') IS NULL THEN
    PERFORM public.pgm_log('INFO', 'TECH_EU', 'Checagem empresasusers/users ignorada (tabela ausente)', NULL);
    RETURN;
  END IF;
  SELECT EXISTS (
    SELECT 1 FROM information_schema.columns c
    WHERE c.table_schema = 'public' AND c.table_name = 'users' AND c.column_name = 'inativo'
  ) INTO v_has_inativo;

  IF v_has_inativo THEN
    EXECUTE $q$
      SELECT count(*) FROM public.users u
      WHERE u.role = 0 AND COALESCE(u.inativo, 0) = 0
        AND NOT EXISTS (SELECT 1 FROM public.empresasusers eu WHERE eu.iduser = u.id)
    $q$ INTO n;
  ELSE
    EXECUTE $q$
      SELECT count(*) FROM public.users u
      WHERE u.role = 0
        AND NOT EXISTS (SELECT 1 FROM public.empresasusers eu WHERE eu.iduser = u.id)
    $q$ INTO n;
  END IF;

  IF n > 0 THEN
    PERFORM public.pgm_log('WARN', 'TECH_SEM_EMPRESA',
      format('%s técnico(s) role=0 sem empresasusers', n),
      'Ranking do dashboard usa vínculo empresa↔usuário.');
  ELSE
    PERFORM public.pgm_log('OK', 'TECH_EU', 'Sem técnicos órfãos de empresasusers (amostragem)', NULL);
  END IF;
END $eu$;

DO $fkr$
BEGIN
  IF to_regclass('public.tickets') IS NULL OR to_regclass('public.users') IS NULL THEN
    RETURN;
  END IF;
  BEGIN
    ALTER TABLE public.tickets
      ADD CONSTRAINT fk_tickets_tecnico_resp FOREIGN KEY (idtecnico_responsavel)
      REFERENCES public.users (id) ON DELETE SET NULL ON UPDATE CASCADE;
    PERFORM public.pgm_log('FIX', 'FK_TECNICO', 'FK tickets.idtecnico_responsavel → users', NULL);
  EXCEPTION
    WHEN duplicate_object THEN
      PERFORM public.pgm_log('INFO', 'FK_TECNICO', 'FK idtecnico_responsavel já existia', NULL);
    WHEN foreign_key_violation THEN
      PERFORM public.pgm_log('WARN', 'FK_TECNICO',
        'FK não criada: valores órfãos em idtecnico_responsavel',
        'Corrija dados e rode de novo ou crie FK manualmente.');
  END;
END $fkr$;

-- -----------------------------------------------------------------------------
-- 6) ANALYZE
-- -----------------------------------------------------------------------------
DO $an$
BEGIN
  IF to_regclass('public.tickets') IS NOT NULL THEN
    EXECUTE 'ANALYZE public.tickets';
  END IF;
  IF to_regclass('public.sla_policies') IS NOT NULL THEN
    EXECUTE 'ANALYZE public.sla_policies';
  END IF;
  PERFORM public.pgm_log('OK', 'ANALYZE', 'ANALYZE tickets / sla_policies (se existirem)', NULL);
END $an$;

-- -----------------------------------------------------------------------------
-- Relatório + limpeza função auxiliar (opcional manter pgm_log para reuso)
-- -----------------------------------------------------------------------------
SELECT '========== RELATÓRIO (public.pgm_maintenance_report) ==========' AS info;

SELECT id, severidade, codigo, mensagem, detalhe
FROM public.pgm_maintenance_report
ORDER BY
  CASE severidade
    WHEN 'ERR' THEN 0 WHEN 'WARN' THEN 1 WHEN 'FIX' THEN 2 WHEN 'INFO' THEN 3 ELSE 4
  END,
  id;

SELECT 'Próximo: bin/cake tickets_sla recalculate --empresa=ID' AS lembrete;
SELECT 'Ajuste no script os set_config pgm.sit_resolvido / pgm.sit_fechado se necessário.' AS lembrete;

-- Descomente para remover a função auxiliar após validar:
-- DROP FUNCTION IF EXISTS public.pgm_log(text, text, text, text);
