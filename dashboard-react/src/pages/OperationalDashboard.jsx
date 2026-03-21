import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { fetchDashboardOperacional } from '../lib/api';

function StatCard({ label, value, tone }) {
  const toneCls =
    tone === 'critical'
      ? 'border-rose-200 bg-rose-50/80'
      : tone === 'warn'
        ? 'border-amber-200 bg-amber-50/50'
        : 'border-slate-200 bg-white';
  return (
    <div className={`rounded-xl border p-4 shadow-sm ${toneCls}`}>
      <p className="text-xs font-medium uppercase tracking-wide text-slate-500">{label}</p>
      <p className="mt-1 text-2xl font-bold tabular-nums text-slate-900">{value}</p>
    </div>
  );
}

function KeyValList({ title, obj }) {
  const entries = Object.entries(obj || {}).sort((a, b) => b[1] - a[1]);
  if (entries.length === 0) {
    return (
      <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <h3 className="text-sm font-semibold text-slate-800">{title}</h3>
        <p className="mt-2 text-sm text-slate-400">Sem dados</p>
      </div>
    );
  }
  return (
    <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <h3 className="text-sm font-semibold text-slate-800">{title}</h3>
      <ul className="mt-2 space-y-1 text-sm">
        {entries.map(([k, v]) => (
          <li key={k} className="flex justify-between gap-2">
            <span className="text-slate-600">{k}</span>
            <span className="font-semibold tabular-nums text-slate-900">{v}</span>
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
      <header className="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 className="text-lg font-semibold text-slate-900">Painel operacional</h2>
          <p className="text-sm text-slate-500">SLA, backlog e distribuição — empresa da sessão.</p>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          {portalNav ? (
            <a
              href={backHref}
              className="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            >
              {boot?.servicedesk ? '← Fila técnica' : '← Listagem de tickets'}
            </a>
          ) : (
            <Link
              to="/tecnico"
              className="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            >
              ← Painel técnico
            </Link>
          )}
          <button
            type="button"
            onClick={() => load()}
            disabled={loading}
            className="rounded-lg bg-teal-700 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-800 disabled:opacity-50"
          >
            {loading ? 'Atualizando…' : 'Atualizar'}
          </button>
        </div>
      </header>

      {err ? <p className="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{err}</p> : null}

      {loading && !dash ? (
        <p className="text-sm text-slate-500">Carregando indicadores…</p>
      ) : dash ? (
        <>
          <p className="mb-4 text-xs text-slate-400">
            Gerado em {dash.gerado_em ? new Date(dash.gerado_em).toLocaleString('pt-BR') : '—'}
            {!dash.colunas_sla_ativas ? ' · Colunas SLA ainda não disponíveis neste banco.' : ''}
          </p>
          <div className="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard label="Backlog (abertos)" value={dash.backlog ?? '—'} />
            <StatCard label="Resolvidos hoje" value={dash.resolvidos_hoje ?? '—'} />
            <StatCard
              label="P1 abertos"
              value={dash.p1_abertos ?? '—'}
              tone={dash.p1_abertos > 0 ? 'warn' : undefined}
            />
            <StatCard
              label="SLA violado (total)"
              value={violadosTotal !== null ? violadosTotal : '—'}
              tone={violadosTotal > 0 ? 'critical' : undefined}
            />
          </div>
          {(dash.alertas_sla_violado || []).length > 0 ? (
            <p className="mb-2 text-xs text-slate-500">
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
            <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
              <h3 className="border-b border-slate-100 px-4 py-3 text-sm font-semibold text-slate-800">
                Alertas — SLA violado
              </h3>
              <table className="min-w-full text-left text-sm">
                <thead className="bg-slate-50 text-xs uppercase text-slate-500">
                  <tr>
                    <th className="px-4 py-2">Ticket</th>
                    <th className="px-4 py-2">Prioridade</th>
                    <th className="px-4 py-2">% SLA</th>
                    <th className="px-4 py-2">Limite resolução</th>
                    <th className="px-4 py-2">Fila</th>
                    <th className="px-4 py-2" />
                  </tr>
                </thead>
                <tbody>
                  {dash.alertas_sla_violado.map((row) => (
                    <tr key={row.id} className="border-t border-slate-100">
                      <td className="px-4 py-2 font-mono text-xs">#{row.id}</td>
                      <td className="px-4 py-2">{row.prioridade ?? '—'}</td>
                      <td className="px-4 py-2 tabular-nums">{row.sla_percentual_consumido ?? '—'}</td>
                      <td className="px-4 py-2 text-slate-600">
                        {row.data_limite_resolucao
                          ? new Date(row.data_limite_resolucao).toLocaleString('pt-BR')
                          : '—'}
                      </td>
                      <td className="px-4 py-2">{row.fila_nome ?? row.queue_id ?? '—'}</td>
                      <td className="px-4 py-2">
                        <a href={ticketHref(row.id)} className="font-medium text-teal-700 hover:underline">
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
    return <div className="tickets-react-tech w-full overflow-visible px-2 pb-6 pt-1 text-slate-800 md:px-3">{inner}</div>;
  }

  return (
    <div className="min-h-screen bg-slate-100 text-slate-800">
      <div className="mx-auto max-w-6xl px-4 py-8">{inner}</div>
    </div>
  );
}
