import { useCallback, useEffect, useMemo, useState } from 'react';
import { fetchWorkflowSlaLogs, postTicketSlaPause, postTicketSlaResume } from '../lib/api';

function formatMin(m) {
  if (m == null || !Number.isFinite(Number(m))) return '—';
  const n = Math.max(0, Math.floor(Number(m)));
  const h = Math.floor(n / 60);
  const mm = n % 60;
  if (h <= 0) return `${mm}m`;
  if (mm <= 0) return `${h}h`;
  return `${h}h ${mm}m`;
}

function formatTs(iso) {
  if (!iso) return '—';
  try {
    return new Date(iso).toLocaleString('pt-BR');
  } catch {
    return String(iso);
  }
}

/** Cartão do modal — usa tokens do tema (evita fundo #222834 fixo + texto escuro ilegível no tema claro). */
const SLA_MODAL_CARD =
  'max-h-[85vh] overflow-y-auto rounded-xl border border-[var(--pgm-border,#cbd5e1)] bg-[var(--pgm-bg-surface,#ffffff)] p-4 text-sm text-[var(--pgm-text,#0f172a)] shadow-xl';
const SLA_MODAL_TITLE = 'font-semibold text-[var(--pgm-primary,#1d9e75)]';
const SLA_MODAL_DT = 'text-[10px] font-semibold uppercase tracking-wide text-[var(--pgm-text-muted,#64748b)]';
const SLA_MODAL_DD = 'mt-0.5 font-medium text-[var(--pgm-text,#0f172a)]';
const SLA_MODAL_BTN_CLOSE =
  'text-[var(--pgm-text-muted,#64748b)] hover:text-[var(--pgm-text,#0f172a)]';
const SLA_MODAL_BTN_FOOTER =
  'mt-4 w-full rounded border border-[var(--pgm-border,#cbd5e1)] bg-[var(--pgm-bg-elevated,#f8fafc)] py-2 text-xs font-medium text-[var(--pgm-text,#0f172a)] hover:bg-[var(--pgm-bg-overlay,#f1f5f9)]';

/** Painel e ações — fallbacks alinhados ao tema claro (servicedesk / tickets-react-sd). */
const SLA_PANEL =
  'mt-3 rounded-xl border border-[var(--pgm-border,#cbd5e1)] bg-[var(--pgm-bg-surface,#ffffff)] p-3 text-[0.8125rem] text-[var(--pgm-text,#0f172a)] shadow-[var(--pgm-shadow-sm,0_1px_2px_rgba(15,23,42,0.06))]';
const SLA_PANEL_TITLE = 'text-sm font-semibold text-[var(--pgm-primary,#1d9e75)]';
const SLA_LABEL = 'text-[10px] uppercase tracking-wide text-[var(--pgm-text-muted,#64748b)]';
const SLA_BTN_ACTION =
  'inline-flex items-center rounded-md border border-[var(--pgm-border,#cbd5e1)] bg-[var(--pgm-bg-elevated,#f8fafc)] px-2.5 py-1 text-[11px] font-semibold text-[var(--pgm-text,#0f172a)] shadow-sm transition hover:border-[var(--pgm-primary,#1d9e75)] hover:bg-[var(--pgm-bg-overlay,#f1f5f9)] hover:text-[var(--pgm-primary,#1d9e75)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--pgm-primary,#1d9e75)] focus-visible:ring-offset-1 disabled:opacity-50';
const SLA_BTN_PAUSE =
  'inline-flex items-center rounded-md border border-amber-600/50 bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-900 shadow-sm transition hover:border-amber-600 hover:bg-amber-100 disabled:opacity-50';
const SLA_BTN_RESUME =
  'inline-flex items-center rounded-md border border-emerald-600/50 bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-900 shadow-sm transition hover:border-emerald-600 hover:bg-emerald-100 disabled:opacity-50';
const SLA_DETAILS =
  'mt-2 rounded-lg border border-[var(--pgm-border-subtle,#e2e8f0)] bg-[var(--pgm-bg-elevated,#f8fafc)] p-2';
const SLA_DETAILS_SUMMARY =
  'cursor-pointer text-xs font-semibold text-[var(--pgm-text-secondary,#334155)] hover:text-[var(--pgm-text,#0f172a)]';

/** @param {{ ticket: object, boot: object, onSlaUpdated?: (snap: object) => void }} props */
export default function TicketSlaPanel({ ticket, boot, onSlaUpdated }) {
  const d = ticket?.slaDetail;
  const [busy, setBusy] = useState(false);
  const [err, setErr] = useState(null);
  const [policyOpen, setPolicyOpen] = useState(false);
  const [logsOpen, setLogsOpen] = useState(false);
  const [logsLoading, setLogsLoading] = useState(false);
  const [logsErr, setLogsErr] = useState(null);
  const [escalationApiLogs, setEscalationApiLogs] = useState([]);

  const visible = Boolean(d?.schemaReady && (d?.hasSlaFeature || (d?.cycles?.length ?? 0) > 0 || (d?.events?.length ?? 0) > 0));

  const pausedSec = Number(d?.pausedSecondsTotal ?? 0);
  const pausedFmt = useMemo(() => formatMin(Math.floor(pausedSec / 60)), [pausedSec]);

  const applySnap = useCallback(
    (snap) => {
      onSlaUpdated?.(snap);
    },
    [onSlaUpdated],
  );

  const doPause = useCallback(async () => {
    if (!ticket?.id || busy) return;
    setBusy(true);
    setErr(null);
    const r = await postTicketSlaPause(ticket.id);
    setBusy(false);
    if (!r.ok) {
      setErr(r.message || r.error || 'Falha ao pausar');
      return;
    }
    applySnap({ workflow: r.workflow, slaByState: r.slaByState, slaDetail: r.slaDetail });
  }, [ticket?.id, busy, applySnap]);

  const doResume = useCallback(async () => {
    if (!ticket?.id || busy) return;
    setBusy(true);
    setErr(null);
    const r = await postTicketSlaResume(ticket.id);
    setBusy(false);
    if (!r.ok) {
      setErr(r.message || r.error || 'Falha ao retomar');
      return;
    }
    applySnap({ workflow: r.workflow, slaByState: r.slaByState, slaDetail: r.slaDetail });
  }, [ticket?.id, busy, applySnap]);

  const adminUrl = d?.urls?.workflowSlaAdmin;

  useEffect(() => {
    if (!logsOpen || !ticket?.id) {
      return;
    }
    let cancelled = false;
    setLogsLoading(true);
    setLogsErr(null);
    (async () => {
      const r = await fetchWorkflowSlaLogs(100, ticket.id);
      if (cancelled) return;
      setLogsLoading(false);
      if (!r.ok) {
        setLogsErr(r.error || 'Não foi possível carregar os logs.');
        setEscalationApiLogs([]);
        return;
      }
      setEscalationApiLogs(Array.isArray(r.logs) ? r.logs : []);
    })();
    return () => {
      cancelled = true;
    };
  }, [logsOpen, ticket?.id]);
  const role = boot?.role;
  const canAct = role === 0 && d?.actions;

  if (!visible) return null;

  const pol = d?.appliedPolicy;
  const wfSla = ticket?.workflow?.slaByState || ticket?.slaByState;

  return (
    <div className={`pgm-sla-panel ${SLA_PANEL}`}>
      <div className="mb-2 flex flex-wrap items-center justify-between gap-2">
        <h3 className={`pgm-sla-panel__title ${SLA_PANEL_TITLE}`}>SLA & Service Desk</h3>
        <div className="pgm-sla-panel__actions tickets-sla-panel-actions flex flex-wrap gap-2">
          {canAct?.canPause ? (
            <button
              type="button"
              disabled={busy}
              onClick={doPause}
              className={`pgm-sla-panel__btn pgm-sla-panel__btn--pause ${SLA_BTN_PAUSE}`}
            >
              Pausar SLA
            </button>
          ) : null}
          {canAct?.canResume ? (
            <button
              type="button"
              disabled={busy}
              onClick={doResume}
              className={`pgm-sla-panel__btn pgm-sla-panel__btn--resume ${SLA_BTN_RESUME}`}
            >
              Retomar SLA
            </button>
          ) : null}
          {pol ? (
            <button
              type="button"
              onClick={() => setPolicyOpen(true)}
              className={`pgm-sla-panel__btn ${SLA_BTN_ACTION}`}
            >
              Ver política
            </button>
          ) : null}
          <button type="button" onClick={() => setLogsOpen(true)} className={`pgm-sla-panel__btn ${SLA_BTN_ACTION}`}>
            Logs de SLA
          </button>
          {adminUrl ? (
            <a
              href={adminUrl}
              data-turbo="false"
              target="_blank"
              rel="noreferrer"
              className={`pgm-sla-panel__btn ${SLA_BTN_ACTION}`}
            >
              Cadastro SLA
            </a>
          ) : null}
        </div>
      </div>
      {err ? <p className="mb-2 text-xs font-medium text-red-600">{err}</p> : null}

      <div className="grid gap-2 sm:grid-cols-2">
        <div>
          <div className={SLA_LABEL}>Contrato</div>
          <div className="font-medium">
            {d?.contract ? (
              <>
                {d.contract.code ? `${d.contract.code} — ` : ''}
                {d.contract.name || `ID ${d.contract.id}`}
              </>
            ) : (
              '—'
            )}
          </div>
        </div>
        <div>
          <div className={SLA_LABEL}>Política aplicada</div>
          <div className="font-medium">
            {pol ? `#${pol.id} ${pol.estado_nome || ''}` : d?.resolvedPolicyId ? `#${d.resolvedPolicyId}` : '—'}
          </div>
        </div>
        <div>
          <div className={SLA_LABEL}>SLA resposta</div>
          <div>
            Meta {formatMin(d?.slaResposta?.targetMinutes)} · restante {formatMin(d?.slaResposta?.remainingMinutes)}
          </div>
          <div className="text-[11px] text-[var(--pgm-text-muted,#64748b)]">até {formatTs(d?.slaResposta?.deadlineIso)}</div>
        </div>
        <div>
          <div className={SLA_LABEL}>SLA resolução</div>
          <div>
            Meta {formatMin(d?.slaResolucao?.targetMinutes)} · restante {formatMin(d?.slaResolucao?.remainingMinutes)}
          </div>
          <div className="text-[11px] text-[var(--pgm-text-muted,#64748b)]">até {formatTs(d?.slaResolucao?.deadlineIso)}</div>
        </div>
        <div>
          <div className={SLA_LABEL}>Status SLA (workflow)</div>
          <div className="font-medium">{d?.status?.label || '—'}</div>
          {wfSla?.enabled ? (
            <div className="text-[11px] text-[var(--pgm-text-muted,#64748b)]">
              Prazo resolução (linha): {formatTs(wfSla?.deadlineResolucao)}
            </div>
          ) : null}
        </div>
        <div>
          <div className={SLA_LABEL}>Tempo pausado (ciclo)</div>
          <div className="font-medium">{pausedFmt}</div>
        </div>
        <div>
          <div className={SLA_LABEL}>Fila atual</div>
          <div>{d?.queue?.name || '—'}</div>
        </div>
        <div>
          <div className={SLA_LABEL}>Técnico atual</div>
          <div>{d?.technician?.name || '—'}</div>
        </div>
      </div>

      {d?.cycles?.length ? (
        <details className={SLA_DETAILS}>
          <summary className={SLA_DETAILS_SUMMARY}>
            Histórico de ciclos ({d.cycles.length})
          </summary>
          <ul className="mt-2 max-h-48 space-y-1 overflow-y-auto text-[11px]">
            {d.cycles.map((c) => (
              <li key={c.id} className="border-b border-[var(--pgm-border-subtle,#e2e8f0)] pb-1">
                <span className="font-mono">#{c.cycle_number}</span> — {c.phase}
                {c.open ? ' · aberto' : ' · fechado'} · início {formatTs(c.started_at)}
                {c.ended_at ? ` · fim ${formatTs(c.ended_at)}` : ''}
              </li>
            ))}
          </ul>
        </details>
      ) : null}

      {d?.events?.length ? (
        <details className={SLA_DETAILS}>
          <summary className={SLA_DETAILS_SUMMARY}>
            Eventos de SLA ({d.events.length})
          </summary>
          <ul className="mt-2 max-h-48 space-y-1 overflow-y-auto text-[11px]">
            {d.events.map((ev) => (
              <li key={ev.id} className="border-b border-[var(--pgm-border-subtle,#e2e8f0)] pb-1">
                <span className="font-medium text-[var(--pgm-primary,#1d9e75)]">{ev.event_type}</span> · {formatTs(ev.created_at)}
                {ev.ticket_sla_cycle_id ? ` · ciclo ${ev.ticket_sla_cycle_id}` : ''}
              </li>
            ))}
          </ul>
        </details>
      ) : null}

      {d?.escalationLogs?.length ? (
        <details className={SLA_DETAILS}>
          <summary className={SLA_DETAILS_SUMMARY}>
            Escalonamentos registrados ({d.escalationLogs.length})
          </summary>
          <ul className="mt-2 max-h-40 space-y-1 overflow-y-auto text-[11px]">
            {d.escalationLogs.map((lg) => (
              <li key={lg.id} className="border-b border-[var(--pgm-border-subtle,#e2e8f0)] pb-1">
                {lg.reason_code || '—'} · {formatTs(lg.created_at)}
              </li>
            ))}
          </ul>
        </details>
      ) : null}

      {logsOpen ? (
        <div
          className="fixed inset-0 z-[80] flex items-center justify-center bg-black/60 p-4"
          role="dialog"
          aria-modal="true"
          aria-label="Logs de SLA"
        >
          <div className={`w-full max-w-2xl ${SLA_MODAL_CARD}`}>
            <div className="mb-3 flex items-center justify-between gap-2">
              <h4 className={SLA_MODAL_TITLE}>
                Logs de SLA {ticket?.id ? `· ticket #${ticket.id}` : ''}
              </h4>
              <button type="button" className={SLA_MODAL_BTN_CLOSE} onClick={() => setLogsOpen(false)}>
                ✕
              </button>
            </div>
            {logsErr ? <p className="mb-2 text-xs font-medium text-red-600">{logsErr}</p> : null}
            {logsLoading ? <p className="text-xs text-[var(--pgm-text-muted)]">Carregando…</p> : null}

            <section className="mb-4">
              <h5 className="mb-1 text-[11px] font-semibold uppercase tracking-wide text-[var(--pgm-text-muted)]">
                Escalonamentos (API)
              </h5>
              {escalationApiLogs.length === 0 && !logsLoading ? (
                <p className="text-xs text-[var(--pgm-text-muted)]">Nenhum escalonamento registrado na API para este ticket.</p>
              ) : (
                <ul className="max-h-40 space-y-1 overflow-y-auto text-[11px]">
                  {escalationApiLogs.map((lg) => (
                    <li key={lg.id} className="border-b border-[var(--pgm-border-subtle,#e2e8f0)] pb-1">
                      <span className="font-medium text-[var(--pgm-primary,#1d9e75)]">{lg.reason_code || '—'}</span>
                      {lg.workflow_state_from != null || lg.workflow_state_to != null
                        ? ` · estado ${lg.workflow_state_from ?? '—'} → ${lg.workflow_state_to ?? '—'}`
                        : ''}
                      {' · '}
                      {formatTs(lg.created_at)}
                    </li>
                  ))}
                </ul>
              )}
            </section>

            <section className="mb-4">
              <h5 className="mb-1 text-[11px] font-semibold uppercase tracking-wide text-[var(--pgm-text-muted)]">
                Escalonamentos (ticket)
              </h5>
              {(d?.escalationLogs?.length ?? 0) === 0 ? (
                <p className="text-xs text-[var(--pgm-text-muted)]">—</p>
              ) : (
                <ul className="max-h-32 space-y-1 overflow-y-auto text-[11px]">
                  {d.escalationLogs.map((lg) => (
                    <li key={lg.id} className="border-b border-[var(--pgm-border-subtle,#e2e8f0)] pb-1">
                      {lg.reason_code || '—'} · {formatTs(lg.created_at)}
                    </li>
                  ))}
                </ul>
              )}
            </section>

            <section>
              <h5 className="mb-1 text-[11px] font-semibold uppercase tracking-wide text-[var(--pgm-text-muted)]">
                Eventos de SLA (ticket)
              </h5>
              {(d?.events?.length ?? 0) === 0 ? (
                <p className="text-xs text-[var(--pgm-text-muted)]">Nenhum evento registrado.</p>
              ) : (
                <ul className="max-h-48 space-y-1 overflow-y-auto text-[11px]">
                  {d.events.map((ev) => (
                    <li key={ev.id} className="border-b border-[var(--pgm-border-subtle,#e2e8f0)] pb-1">
                      <span className="font-medium text-[var(--pgm-primary,#1d9e75)]">{ev.event_type}</span> · {formatTs(ev.created_at)}
                      {ev.ticket_sla_cycle_id ? ` · ciclo ${ev.ticket_sla_cycle_id}` : ''}
                    </li>
                  ))}
                </ul>
              )}
            </section>

            <button type="button" className={SLA_MODAL_BTN_FOOTER} onClick={() => setLogsOpen(false)}>
              Fechar
            </button>
          </div>
        </div>
      ) : null}

      {policyOpen && pol ? (
        <div
          className="fixed inset-0 z-[80] flex items-center justify-center bg-black/60 p-4"
          role="dialog"
          aria-modal="true"
          aria-label="Política SLA"
        >
          <div className={`w-full max-w-lg ${SLA_MODAL_CARD}`}>
            <div className="mb-2 flex items-center justify-between">
              <h4 className={SLA_MODAL_TITLE}>Política #{pol.id}</h4>
              <button type="button" className={SLA_MODAL_BTN_CLOSE} onClick={() => setPolicyOpen(false)}>
                ✕
              </button>
            </div>
            <dl className="space-y-2 text-[13px]">
              <div>
                <dt className={SLA_MODAL_DT}>Estado</dt>
                <dd className={SLA_MODAL_DD}>{pol.estado_nome || pol.workflow_state_id}</dd>
              </div>
              <div>
                <dt className={SLA_MODAL_DT}>Resposta / resolução (min)</dt>
                <dd className={SLA_MODAL_DD}>
                  {pol.resposta_minutos ?? '—'} / {pol.resolucao_minutos ?? '—'}
                </dd>
              </div>
              <div>
                <dt className={SLA_MODAL_DT}>Pausa automática no estado</dt>
                <dd className={SLA_MODAL_DD}>{pol.pausa_sla ? 'Sim' : 'Não'}</dd>
              </div>
              <div>
                <dt className={SLA_MODAL_DT}>Estado final</dt>
                <dd className={SLA_MODAL_DD}>{pol.is_final ? 'Sim' : 'Não'}</dd>
              </div>
              <div>
                <dt className={SLA_MODAL_DT}>Autoescalar</dt>
                <dd className={SLA_MODAL_DD}>{pol.auto_escalar ? 'Sim' : 'Não'}</dd>
              </div>
              {(pol.contract_id || pol.contract_service_id) && (
                <div>
                  <dt className={SLA_MODAL_DT}>Escopo contrato</dt>
                  <dd className={SLA_MODAL_DD}>
                    contrato {pol.contract_id ?? '—'} · serviço {pol.contract_service_id ?? '—'}
                  </dd>
                </div>
              )}
            </dl>
            <button type="button" className={SLA_MODAL_BTN_FOOTER} onClick={() => setPolicyOpen(false)}>
              Fechar
            </button>
          </div>
        </div>
      ) : null}
    </div>
  );
}
