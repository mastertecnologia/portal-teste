import { useCallback, useMemo, useState } from 'react';
import { postTicketSlaPause, postTicketSlaResume } from '../lib/api';

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

/** @param {{ ticket: object, boot: object, onSlaUpdated?: (snap: object) => void }} props */
export default function TicketSlaPanel({ ticket, boot, onSlaUpdated }) {
  const d = ticket?.slaDetail;
  const [busy, setBusy] = useState(false);
  const [err, setErr] = useState(null);
  const [policyOpen, setPolicyOpen] = useState(false);

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

  const logsUrl = d?.urls?.workflowSlaLogs;
  const adminUrl = d?.urls?.workflowSlaAdmin;
  const role = boot?.role;
  const canAct = role === 0 && d?.actions;

  if (!visible) return null;

  const pol = d?.appliedPolicy;
  const wfSla = ticket?.workflow?.slaByState || ticket?.slaByState;

  return (
    <div className="mt-3 rounded-xl border border-[var(--pgm-border-subtle,rgba(255,255,255,0.08))] bg-[var(--pgm-bg-elevated,#1e2430)] p-3 text-[0.8125rem] text-[var(--pgm-text,#e8eaed)]">
      <div className="mb-2 flex flex-wrap items-center justify-between gap-2">
        <h3 className="text-sm font-semibold text-[var(--pgm-accent,#5cdbc0)]">SLA & Service Desk</h3>
        <div className="flex flex-wrap gap-1.5">
          {canAct?.canPause ? (
            <button
              type="button"
              disabled={busy}
              onClick={doPause}
              className="rounded border border-amber-500/40 bg-amber-500/15 px-2 py-1 text-[11px] font-medium text-amber-100 hover:bg-amber-500/25 disabled:opacity-50"
            >
              Pausar SLA
            </button>
          ) : null}
          {canAct?.canResume ? (
            <button
              type="button"
              disabled={busy}
              onClick={doResume}
              className="rounded border border-emerald-500/40 bg-emerald-500/15 px-2 py-1 text-[11px] font-medium text-emerald-100 hover:bg-emerald-500/25 disabled:opacity-50"
            >
              Retomar SLA
            </button>
          ) : null}
          {pol ? (
            <button
              type="button"
              onClick={() => setPolicyOpen(true)}
              className="rounded border border-[var(--pgm-border,#3d4554)] bg-transparent px-2 py-1 text-[11px] text-[var(--pgm-text-muted,#9aa0a8)] hover:bg-white/5"
            >
              Ver política
            </button>
          ) : null}
          {logsUrl ? (
            <a
              href={logsUrl}
              target="_blank"
              rel="noreferrer"
              className="rounded border border-[var(--pgm-border,#3d4554)] bg-transparent px-2 py-1 text-[11px] text-[var(--pgm-text-muted,#9aa0a8)] hover:bg-white/5"
            >
              Logs de SLA
            </a>
          ) : null}
          {adminUrl ? (
            <a
              href={adminUrl}
              target="_blank"
              rel="noreferrer"
              className="rounded border border-[var(--pgm-border,#3d4554)] bg-transparent px-2 py-1 text-[11px] text-[var(--pgm-text-muted,#9aa0a8)] hover:bg-white/5"
            >
              Cadastro SLA
            </a>
          ) : null}
        </div>
      </div>
      {err ? <p className="mb-2 text-xs text-red-300">{err}</p> : null}

      <div className="grid gap-2 sm:grid-cols-2">
        <div>
          <div className="text-[10px] uppercase tracking-wide text-[var(--pgm-text-muted,#9aa0a8)]">Contrato</div>
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
          <div className="text-[10px] uppercase tracking-wide text-[var(--pgm-text-muted,#9aa0a8)]">Política aplicada</div>
          <div className="font-medium">
            {pol ? `#${pol.id} ${pol.estado_nome || ''}` : d?.resolvedPolicyId ? `#${d.resolvedPolicyId}` : '—'}
          </div>
        </div>
        <div>
          <div className="text-[10px] uppercase tracking-wide text-[var(--pgm-text-muted,#9aa0a8)]">SLA resposta</div>
          <div>
            Meta {formatMin(d?.slaResposta?.targetMinutes)} · restante {formatMin(d?.slaResposta?.remainingMinutes)}
          </div>
          <div className="text-[11px] text-[var(--pgm-text-muted,#9aa0a8)]">até {formatTs(d?.slaResposta?.deadlineIso)}</div>
        </div>
        <div>
          <div className="text-[10px] uppercase tracking-wide text-[var(--pgm-text-muted,#9aa0a8)]">SLA resolução</div>
          <div>
            Meta {formatMin(d?.slaResolucao?.targetMinutes)} · restante {formatMin(d?.slaResolucao?.remainingMinutes)}
          </div>
          <div className="text-[11px] text-[var(--pgm-text-muted,#9aa0a8)]">até {formatTs(d?.slaResolucao?.deadlineIso)}</div>
        </div>
        <div>
          <div className="text-[10px] uppercase tracking-wide text-[var(--pgm-text-muted,#9aa0a8)]">Status SLA (workflow)</div>
          <div className="font-medium">{d?.status?.label || '—'}</div>
          {wfSla?.enabled ? (
            <div className="text-[11px] text-[var(--pgm-text-muted,#9aa0a8)]">
              Prazo resolução (linha): {formatTs(wfSla?.deadlineResolucao)}
            </div>
          ) : null}
        </div>
        <div>
          <div className="text-[10px] uppercase tracking-wide text-[var(--pgm-text-muted,#9aa0a8)]">Tempo pausado (ciclo)</div>
          <div className="font-medium">{pausedFmt}</div>
        </div>
        <div>
          <div className="text-[10px] uppercase tracking-wide text-[var(--pgm-text-muted,#9aa0a8)]">Fila atual</div>
          <div>{d?.queue?.name || '—'}</div>
        </div>
        <div>
          <div className="text-[10px] uppercase tracking-wide text-[var(--pgm-text-muted,#9aa0a8)]">Técnico atual</div>
          <div>{d?.technician?.name || '—'}</div>
        </div>
      </div>

      {d?.cycles?.length ? (
        <details className="mt-3 rounded border border-white/5 bg-black/20 p-2">
          <summary className="cursor-pointer text-xs font-semibold text-[var(--pgm-text-muted,#9aa0a8)]">
            Histórico de ciclos ({d.cycles.length})
          </summary>
          <ul className="mt-2 max-h-48 space-y-1 overflow-y-auto text-[11px]">
            {d.cycles.map((c) => (
              <li key={c.id} className="border-b border-white/5 pb-1">
                <span className="font-mono">#{c.cycle_number}</span> — {c.phase}
                {c.open ? ' · aberto' : ' · fechado'} · início {formatTs(c.started_at)}
                {c.ended_at ? ` · fim ${formatTs(c.ended_at)}` : ''}
              </li>
            ))}
          </ul>
        </details>
      ) : null}

      {d?.events?.length ? (
        <details className="mt-2 rounded border border-white/5 bg-black/20 p-2">
          <summary className="cursor-pointer text-xs font-semibold text-[var(--pgm-text-muted,#9aa0a8)]">
            Eventos de SLA ({d.events.length})
          </summary>
          <ul className="mt-2 max-h-48 space-y-1 overflow-y-auto text-[11px]">
            {d.events.map((ev) => (
              <li key={ev.id} className="border-b border-white/5 pb-1">
                <span className="text-[var(--pgm-accent,#5cdbc0)]">{ev.event_type}</span> · {formatTs(ev.created_at)}
                {ev.ticket_sla_cycle_id ? ` · ciclo ${ev.ticket_sla_cycle_id}` : ''}
              </li>
            ))}
          </ul>
        </details>
      ) : null}

      {d?.escalationLogs?.length ? (
        <details className="mt-2 rounded border border-white/5 bg-black/20 p-2">
          <summary className="cursor-pointer text-xs font-semibold text-[var(--pgm-text-muted,#9aa0a8)]">
            Escalonamentos registrados ({d.escalationLogs.length})
          </summary>
          <ul className="mt-2 max-h-40 space-y-1 overflow-y-auto text-[11px]">
            {d.escalationLogs.map((lg) => (
              <li key={lg.id} className="border-b border-white/5 pb-1">
                {lg.reason_code || '—'} · {formatTs(lg.created_at)}
              </li>
            ))}
          </ul>
        </details>
      ) : null}

      {policyOpen && pol ? (
        <div
          className="fixed inset-0 z-[80] flex items-center justify-center bg-black/60 p-4"
          role="dialog"
          aria-modal="true"
          aria-label="Política SLA"
        >
          <div className="max-h-[85vh] w-full max-w-lg overflow-y-auto rounded-xl border border-[var(--pgm-border)] bg-[#222834] p-4 text-sm shadow-xl">
            <div className="mb-2 flex items-center justify-between">
              <h4 className="font-semibold text-[var(--pgm-accent)]">Política #{pol.id}</h4>
              <button type="button" className="text-[var(--pgm-text-muted)] hover:text-white" onClick={() => setPolicyOpen(false)}>
                ✕
              </button>
            </div>
            <dl className="space-y-1 text-[13px]">
              <div>
                <dt className="text-[10px] uppercase text-[var(--pgm-text-muted)]">Estado</dt>
                <dd>{pol.estado_nome || pol.workflow_state_id}</dd>
              </div>
              <div>
                <dt className="text-[10px] uppercase text-[var(--pgm-text-muted)]">Resposta / resolução (min)</dt>
                <dd>
                  {pol.resposta_minutos ?? '—'} / {pol.resolucao_minutos ?? '—'}
                </dd>
              </div>
              <div>
                <dt className="text-[10px] uppercase text-[var(--pgm-text-muted)]">Pausa automática no estado</dt>
                <dd>{pol.pausa_sla ? 'Sim' : 'Não'}</dd>
              </div>
              <div>
                <dt className="text-[10px] uppercase text-[var(--pgm-text-muted)]">Estado final</dt>
                <dd>{pol.is_final ? 'Sim' : 'Não'}</dd>
              </div>
              <div>
                <dt className="text-[10px] uppercase text-[var(--pgm-text-muted)]">Autoescalar</dt>
                <dd>{pol.auto_escalar ? 'Sim' : 'Não'}</dd>
              </div>
              {(pol.contract_id || pol.contract_service_id) && (
                <div>
                  <dt className="text-[10px] uppercase text-[var(--pgm-text-muted)]">Escopo contrato</dt>
                  <dd>
                    contrato {pol.contract_id ?? '—'} · serviço {pol.contract_service_id ?? '—'}
                  </dd>
                </div>
              )}
            </dl>
            <button
              type="button"
              className="mt-4 w-full rounded border border-[var(--pgm-border)] py-2 text-xs text-[var(--pgm-text-muted)] hover:bg-white/5"
              onClick={() => setPolicyOpen(false)}
            >
              Fechar
            </button>
          </div>
        </div>
      ) : null}
    </div>
  );
}
