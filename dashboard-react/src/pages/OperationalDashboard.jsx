import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { fetchDashboardOperacional } from '../lib/api';

/* ── KPI Icon SVGs ── */
function IconBacklog() {
  return (
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
      <path d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12" />
    </svg>
  );
}
function IconResolved() {
  return (
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
      <path d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
  );
}
function IconWarning() {
  return (
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
      <path d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
    </svg>
  );
}
function IconCritical() {
  return (
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
      <path d="M11.42 15.17l-5.384-5.383M15.75 6a3.75 3.75 0 11-7.5 0M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
  );
}

const KPI_TONE = {
  default: {
    icon: 'bg-[var(--pgm-badge-teal-bg)] text-[var(--pgm-badge-teal-text)]',
    card: 'border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))]',
    border: '',
  },
  success: {
    icon: 'bg-[var(--pgm-badge-green-bg)] text-[var(--pgm-badge-green-text)]',
    card: 'border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))]',
    border: '',
  },
  warn: {
    icon: 'bg-[var(--pgm-badge-amber-bg)] text-[var(--pgm-badge-amber-text)]',
    card: 'border-l-[3px] border-l-[var(--pgm-badge-amber-text)] border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))]',
    border: '',
  },
  critical: {
    icon: 'bg-[var(--pgm-badge-red-bg)] text-[var(--pgm-badge-red-text)]',
    card: 'border-l-[3px] border-l-[var(--pgm-badge-red-text)] border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] sd-kpi-glow',
    border: '',
  },
};

function KpiCard({ label, value, tone = 'default', icon: Icon }) {
  const t = KPI_TONE[tone] || KPI_TONE.default;
  return (
    <div
      className={`flex items-start gap-3 rounded-xl border bg-gradient-to-b from-[var(--pgm-bg-surface,#1a1f28)] to-[var(--pgm-bg-raised,#141820)] p-4 shadow-[var(--pgm-shadow-sm)] transition-all duration-200 hover:-translate-y-px hover:shadow-[var(--pgm-shadow-md)] hover:border-[var(--pgm-border,#3d4554)] ${t.card}`}
    >
      <div className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-lg ${t.icon}`}>
        {Icon ? <Icon /> : <IconBacklog />}
      </div>
      <div className="min-w-0">
        <p className="text-[0.65rem] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted,#9aa0a8)]">
          {label}
        </p>
        <p className="mt-0.5 font-[var(--pgm-font-mono,'DM_Mono',ui-monospace,monospace)] text-[1.75rem] font-bold leading-tight tabular-nums text-[var(--pgm-text,#e8eaed)]">
          {value}
        </p>
      </div>
    </div>
  );
}

function KeyValList({ title, obj }) {
  const entries = Object.entries(obj || {}).sort((a, b) => b[1] - a[1]);
  if (entries.length === 0) {
    return (
      <div className="overflow-hidden rounded-xl border border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-gradient-to-b from-[var(--pgm-bg-surface,#1a1f28)] to-[color-mix(in_srgb,var(--pgm-bg-surface,#1a1f28)_97%,rgba(255,255,255,0.03))] shadow-[var(--pgm-shadow-md)]">
        <div className="flex items-center justify-between border-b border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-[var(--pgm-bg-elevated,#222834)] px-4 py-3">
          <h3 className="text-[0.85rem] font-semibold text-[var(--pgm-text,#e8eaed)]">{title}</h3>
        </div>
        <div className="p-4">
          <p className="text-sm text-[var(--pgm-text-muted,#9aa0a8)]">Sem dados</p>
        </div>
      </div>
    );
  }
  return (
    <div className="overflow-hidden rounded-xl border border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-gradient-to-b from-[var(--pgm-bg-surface,#1a1f28)] to-[color-mix(in_srgb,var(--pgm-bg-surface,#1a1f28)_97%,rgba(255,255,255,0.03))] shadow-[var(--pgm-shadow-md)]">
      <div className="flex items-center justify-between border-b border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-[var(--pgm-bg-elevated,#222834)] px-4 py-3">
        <h3 className="text-[0.85rem] font-semibold text-[var(--pgm-text,#e8eaed)]">{title}</h3>
      </div>
      <ul className="divide-y divide-[var(--pgm-border-subtle,rgba(255,255,255,0.06))]">
        {entries.map(([k, v]) => (
          <li key={k} className="flex items-center justify-between gap-2 px-4 py-2.5">
            <span className="text-[0.8125rem] text-[var(--pgm-text-secondary,#c4c9d1)]">{k}</span>
            <span className="font-mono text-[0.8125rem] font-semibold tabular-nums text-[var(--pgm-text,#e8eaed)]">{v}</span>
          </li>
        ))}
      </ul>
    </div>
  );
}

export default function OperationalDashboard({ boot }) {
  const [dash, setDash] = useState(null);
  const [err, setErr] = useState(null);
  const [loading, setLoading] = useState(true);

  const load = useCallback(async () => {
    setLoading(true);
    setErr(null);
    const r = await fetchDashboardOperacional();
    if (!r.ok) {
      setErr(r.error || 'Falha ao carregar');
      setDash(null);
    } else {
      setDash(r.dashboard);
    }
    setLoading(false);
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  const shellEmbedded = Boolean(boot);
  const portalNav = Boolean(boot);
  const backHref = boot?.paths?.indexTecnico || '/tecnico';
  const editBase = boot?.paths?.editTicketBase;
  const editQ = boot?.paths?.ticketEditQuery || '';

  const ticketHref = (id) => (editBase ? `${editBase}${id}${editQ}` : `#`);

  const violadosTotal =
    dash?.por_sla_status && typeof dash.por_sla_status.violado === 'number'
      ? dash.por_sla_status.violado
      : null;

  const inner = (
    <>
      <header className="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
          <p className="text-[0.7rem] font-semibold uppercase tracking-[0.12em] text-[var(--pgm-primary,#1d9e75)]">
            Painel operacional
          </p>
          <h2 className="text-[1.35rem] font-bold text-[var(--pgm-text,#e8eaed)]">
            Indicadores &amp; SLA
          </h2>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          {portalNav ? (
            <a
              href={backHref}
              className="inline-flex items-center gap-1.5 rounded-lg border border-[var(--pgm-border,#3d4554)] bg-transparent px-3 py-2 text-[0.8125rem] font-medium text-[var(--pgm-text,#e8eaed)] transition hover:bg-[var(--pgm-bg-overlay,#2a3140)] hover:border-[var(--pgm-border-strong,#4f5869)]"
            >
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
              {boot?.servicedesk ? 'Fila técnica' : 'Listagem de tickets'}
            </a>
          ) : (
            <Link
              to="/tecnico"
              className="inline-flex items-center gap-1.5 rounded-lg border border-[var(--pgm-border,#3d4554)] bg-transparent px-3 py-2 text-[0.8125rem] font-medium text-[var(--pgm-text,#e8eaed)] transition hover:bg-[var(--pgm-bg-overlay,#2a3140)] hover:border-[var(--pgm-border-strong,#4f5869)]"
            >
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
              Painel técnico
            </Link>
          )}
          <button
            type="button"
            onClick={() => load()}
            disabled={loading}
            className="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-b from-[var(--pgm-primary,#1d9e75)] to-[#168a64] px-3 py-2 text-[0.8125rem] font-semibold text-white shadow-[var(--pgm-shadow-sm),inset_0_1px_0_rgba(255,255,255,0.12)] transition hover:-translate-y-px hover:shadow-[var(--pgm-shadow-md),0_0_16px_rgba(29,158,117,0.25)] active:translate-y-0 disabled:opacity-50"
          >
            {loading ? 'Atualizando…' : 'Atualizar'}
          </button>
        </div>
      </header>

      {err ? (
        <p className="mb-4 rounded-lg border border-[rgba(248,113,113,0.35)] bg-[var(--pgm-danger-bg)] px-3 py-2 text-sm text-[var(--pgm-danger-text)]">
          {err}
        </p>
      ) : null}

      {loading && !dash ? (
        <p className="text-sm text-[var(--pgm-text-muted,#9aa0a8)]">Carregando indicadores…</p>
      ) : dash ? (
        <>
          <p className="mb-4 text-xs text-[var(--pgm-text-muted,#9aa0a8)]">
            Gerado em {dash.gerado_em ? new Date(dash.gerado_em).toLocaleString('pt-BR') : '—'}
            {!dash.colunas_sla_ativas ? ' · Colunas SLA ainda não disponíveis neste banco.' : ''}
          </p>
          <div className="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <KpiCard label="Backlog" value={dash.backlog ?? '—'} icon={IconBacklog} />
            <KpiCard label="Resolvidos hoje" value={dash.resolvidos_hoje ?? '—'} tone="success" icon={IconResolved} />
            <KpiCard
              label="P1 abertos"
              value={dash.p1_abertos ?? '—'}
              tone={dash.p1_abertos > 0 ? 'warn' : 'default'}
              icon={IconWarning}
            />
            <KpiCard
              label="SLA violado"
              value={violadosTotal !== null ? violadosTotal : '—'}
              tone={violadosTotal > 0 ? 'critical' : 'default'}
              icon={IconCritical}
            />
          </div>
          {(dash.alertas_sla_violado || []).length > 0 ? (
            <p className="mb-2 text-xs text-[var(--pgm-text-muted,#9aa0a8)]">
              Lista abaixo: até 25 tickets mais urgentes por limite de resolução.
            </p>
          ) : null}
          <div className="mb-6 grid gap-4 md:grid-cols-3">
            <KeyValList title="Por prioridade" obj={dash.por_prioridade} />
            <KeyValList title="Por status SLA" obj={dash.por_sla_status} />
            <KeyValList title="Por situação (código)" obj={dash.por_situacao} />
          </div>
          <div className="mb-6">
            <KeyValList title="Por fila (id)" obj={dash.por_fila_id} />
          </div>
          {(dash.alertas_sla_violado || []).length > 0 ? (
            <div className="min-w-0 max-w-full overflow-hidden overflow-x-auto rounded-xl border border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-gradient-to-b from-[var(--pgm-bg-surface,#1a1f28)] to-[color-mix(in_srgb,var(--pgm-bg-surface,#1a1f28)_97%,rgba(255,255,255,0.03))] shadow-[var(--pgm-shadow-md)]">
              <div className="flex items-center justify-between border-b border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-[var(--pgm-bg-elevated,#222834)] px-4 py-3">
                <h3 className="text-[0.85rem] font-semibold text-[var(--pgm-text,#e8eaed)]">
                  Alertas — SLA violado
                </h3>
                <span className="inline-flex items-center gap-1 rounded-full border border-[var(--pgm-badge-red-ring)] bg-[var(--pgm-badge-red-bg)] px-2 py-0.5 text-[11px] font-semibold text-[var(--pgm-badge-red-text)]">
                  <span className="inline-block h-1.5 w-1.5 animate-pulse rounded-full bg-current" />
                  {dash.alertas_sla_violado.length}
                </span>
              </div>
              <table className="min-w-full text-left text-[0.8125rem]">
                <thead className="bg-[var(--pgm-bg-elevated,#222834)]">
                  <tr>
                    <th className="px-3 py-2 text-[0.65rem] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted,#9aa0a8)]">Ticket</th>
                    <th className="px-3 py-2 text-[0.65rem] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted,#9aa0a8)]">Prioridade</th>
                    <th className="px-3 py-2 text-[0.65rem] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted,#9aa0a8)]">% SLA</th>
                    <th className="px-3 py-2 text-[0.65rem] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted,#9aa0a8)]">Limite resolução</th>
                    <th className="px-3 py-2 text-[0.65rem] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted,#9aa0a8)]">Fila</th>
                    <th className="px-3 py-2" />
                  </tr>
                </thead>
                <tbody>
                  {dash.alertas_sla_violado.map((row) => (
                    <tr
                      key={row.id}
                      className="border-t border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] transition-colors hover:bg-[var(--pgm-bg-overlay,#2a3140)] hover:shadow-[inset_3px_0_0_var(--pgm-badge-red-text)]"
                    >
                      <td className="px-3 py-2 font-mono text-[11.5px] font-semibold text-[var(--pgm-badge-teal-text,#5cdbc0)]">#{row.id}</td>
                      <td className="px-3 py-2 text-[var(--pgm-text-secondary,#c4c9d1)]">{row.prioridade ?? '—'}</td>
                      <td className="px-3 py-2 font-mono tabular-nums text-[var(--pgm-badge-red-text,#ff9492)]">{row.sla_percentual_consumido ?? '—'}%</td>
                      <td className="px-3 py-2 font-mono text-xs text-[var(--pgm-text-muted,#9aa0a8)]">
                        {row.data_limite_resolucao
                          ? new Date(row.data_limite_resolucao).toLocaleString('pt-BR')
                          : '—'}
                      </td>
                      <td className="px-3 py-2 text-[var(--pgm-text-muted,#9aa0a8)]">{row.fila_nome ?? row.queue_id ?? '—'}</td>
                      <td className="px-3 py-2">
                        <a
                          href={ticketHref(row.id)}
                          className="font-medium text-[var(--pgm-accent,#5cdbc0)] transition hover:text-white hover:underline"
                        >
                          Abrir
                        </a>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : null}
        </>
      ) : null}
    </>
  );

  if (shellEmbedded) {
    return (
      <div className="tickets-react-tech w-full min-w-0 max-w-full overflow-visible px-3 pb-6 pt-3 text-[var(--pgm-text,#e8eaed)] md:px-4">
        {inner}
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-[var(--pgm-bg-base,#0c0f14)] text-[var(--pgm-text,#e8eaed)]">
      <div className="mx-auto max-w-6xl px-4 py-8">{inner}</div>
    </div>
  );
}
