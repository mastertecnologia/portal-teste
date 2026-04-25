import { useEffect, useState } from 'react';
import { fetchServicedeskData, fetchTicketTimeline } from '../lib/api';
import TicketTimeline from './TicketTimeline.jsx';

const BR = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

function contractPercentRing(percent) {
  const p = Math.min(100, Math.max(0, Number(percent) || 0));
  const r = 40;
  const c = 2 * Math.PI * r;
  const offset = c - (p / 100) * c;
  return (
    <svg width="100" height="100" viewBox="0 0 100 100" className="shrink-0" aria-hidden>
      <circle cx="50" cy="50" r={r} fill="none" stroke="var(--pgm-border)" strokeWidth="10" />
      <circle
        cx="50"
        cy="50"
        r={r}
        fill="none"
        stroke="#0056b3"
        strokeWidth="10"
        strokeLinecap="round"
        strokeDasharray={c}
        strokeDashoffset={offset}
        transform="rotate(-90 50 50)"
      />
      <text x="50" y="54" textAnchor="middle" className="fill-[var(--pgm-text)] text-[0.9rem] font-bold">
        {p.toFixed(0)}%
      </text>
    </svg>
  );
}

function eventDateKey(ev) {
  const raw = ev.created;
  if (!raw || typeof raw !== 'string') {
    return '';
  }
  return raw.length >= 10 ? raw.slice(0, 10) : '';
}

function fmtHms(sec) {
  const s = Math.max(0, Number(sec) || 0);
  const h = Math.floor(s / 3600);
  const m = Math.floor((s % 3600) / 60);
  const r = s % 60;
  return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(r).padStart(2, '0')}`;
}

export default function ServiceDeskTabPanels({ ticket, tab, boot = null }) {
  const id = ticket?.id;
  const [data, setData] = useState(null);
  const [histEvents, setHistEvents] = useState([]);
  const [filtro, setFiltro] = useState('todos');
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [ativosQ, setAtivosQ] = useState('');
  const [err, setErr] = useState(null);

  useEffect(() => {
    let c = false;
    (async () => {
      setErr(null);
      setData(null);
      if (tab === 'historico') {
        const tr = await fetchTicketTimeline(id);
        if (!c) {
          if (tr.ok) {
            setHistEvents(tr.events || []);
          } else {
            setErr(tr.error);
            setHistEvents([]);
          }
        }
        return;
      }
      const r = await fetchServicedeskData(id, tab);
      if (!c) {
        if (r.ok) {
          setData(r);
        } else {
          setErr(r.error);
        }
      }
    })();
    return () => {
      c = true;
    };
  }, [id, tab]);

  if (tab === 'historico') {
    const ev = histEvents.filter((e) => {
      if (filtro !== 'todos' && String(e.type || '').toLowerCase() !== filtro) {
        return false;
      }
      const dk = eventDateKey(e);
      if (dateFrom && dk && dk < dateFrom) {
        return false;
      }
      if (dateTo && dk && dk > dateTo) {
        return false;
      }
      return true;
    });
    return (
      <div className="space-y-3 px-1">
        <div className="flex flex-wrap items-end gap-2">
          <div>
            <label className="mb-0.5 block text-xs text-[var(--pgm-text-muted)]">De</label>
            <input
              type="date"
              value={dateFrom}
              onChange={(e) => setDateFrom(e.target.value)}
              className="rounded-md border border-[var(--pgm-border)] bg-[var(--pgm-bg-raised)] px-2 py-1 text-sm text-[var(--pgm-text)]"
            />
          </div>
          <div>
            <label className="mb-0.5 block text-xs text-[var(--pgm-text-muted)]">Até</label>
            <input
              type="date"
              value={dateTo}
              onChange={(e) => setDateTo(e.target.value)}
              className="rounded-md border border-[var(--pgm-border)] bg-[var(--pgm-bg-raised)] px-2 py-1 text-sm text-[var(--pgm-text)]"
            />
          </div>
          <div>
            <label className="mb-0.5 block text-xs text-[var(--pgm-text-muted)]">Tipo</label>
            <select
              value={filtro}
              onChange={(e) => setFiltro(e.target.value)}
              className="rounded-md border border-[var(--pgm-border)] bg-[var(--pgm-bg-raised)] px-2 py-1 text-sm text-[var(--pgm-text)]"
            >
              <option value="todos">Todos</option>
              <option value="comment">Comentário</option>
              <option value="audit">Auditoria</option>
              <option value="worklog">Horas</option>
              <option value="alert">Alerta</option>
              <option value="product_usage">Peça / produto</option>
              <option value="signature">Assinatura</option>
            </select>
          </div>
        </div>
        {err && <p className="text-sm text-red-300">{err}</p>}
        {ev.length === 0 ? (
          <p className="text-sm text-[var(--pgm-text-muted)]">Nenhum evento.</p>
        ) : (
          <TicketTimeline events={ev} />
        )}
      </div>
    );
  }

  if (err) {
    return <p className="text-sm text-red-300">{err}</p>;
  }
  if (!data?.ok) {
    return <p className="text-sm text-[var(--pgm-text-muted)]">A carregar…</p>;
  }

  if (data.tab === 'ativos') {
    const all = data.rows || [];
    const q = ativosQ.trim().toLowerCase();
    const rows = q
      ? all.filter((a) => {
          const s = `${a.descricao || ''} ${a.identificador || ''} ${a.codigo_qr || ''}`.toLowerCase();
          return s.includes(q);
        })
      : all;
    return (
      <div className="space-y-2">
        <div className="flex flex-wrap items-center gap-2">
          <input
            type="search"
            value={ativosQ}
            onChange={(e) => setAtivosQ(e.target.value)}
            placeholder="Pesquisar…"
            className="min-w-[12rem] flex-1 rounded-md border border-[var(--pgm-border)] bg-[var(--pgm-bg-raised)] px-2 py-1.5 text-sm text-[var(--pgm-text)]"
          />
          {boot?.classicEditUrl ? (
            <a
              href={boot.classicEditUrl}
              className="inline-flex items-center gap-1 rounded-md bg-emerald-700 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-emerald-600"
            >
              + Novo (clássico)
            </a>
          ) : null}
        </div>
        <div className="overflow-x-auto">
          <table className="w-full min-w-[40rem] text-left text-sm">
            <thead>
              <tr className="border-b border-[var(--pgm-border)] text-xs uppercase text-[var(--pgm-text-muted)]">
                <th className="py-2 pr-2">Nome</th>
                <th className="py-2 pr-2">Tipo</th>
                <th className="py-2 pr-2">Série / ID</th>
                <th className="py-2 pr-2">Estado</th>
                <th className="py-2 pr-2">Local</th>
              </tr>
            </thead>
            <tbody>
              {rows.map((a) => (
                <tr key={a.id} className="border-b border-[var(--pgm-border-subtle)]">
                  <td className="py-2 pr-2">{a.descricao || '—'}</td>
                  <td className="py-2 pr-2 text-[var(--pgm-text-muted)]">—</td>
                  <td className="py-2 pr-2 font-mono text-xs">{a.identificador || a.id}</td>
                  <td className="py-2 pr-2">{a.ativo ? 'Ativo' : 'Inativo'}</td>
                  <td className="py-2 pr-2 text-[var(--pgm-text-muted)]">—</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        {rows.length === 0 && <p className="text-sm text-[var(--pgm-text-muted)]">Nenhum ativo.</p>}
      </div>
    );
  }

  if (data.tab === 'pecas') {
    return (
      <div>
        <div className="mb-2 flex flex-wrap items-center justify-end gap-2">
          {boot?.paths?.apiAddTicketProduct && id ? (
            <span className="text-[0.65rem] text-[var(--pgm-text-muted)]">
              Registo via API / legado: use o ecrã clássico se disponível.
            </span>
          ) : null}
          {boot?.classicEditUrl ? (
            <a
              href={boot.classicEditUrl}
              className="inline-flex items-center gap-1 rounded-md bg-emerald-700 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-emerald-600"
            >
              + Peça / serviço
            </a>
          ) : null}
        </div>
        <table className="w-full min-w-[32rem] text-left text-sm">
          <thead>
            <tr className="border-b border-[var(--pgm-border)] text-xs text-[var(--pgm-text-muted)]">
              <th className="py-2">Data</th>
              <th className="py-2">Descrição</th>
              <th className="py-2">Qtd</th>
              <th className="py-2">Total</th>
            </tr>
          </thead>
          <tbody>
            {(data.rows || []).map((r) => (
              <tr key={r.id} className="border-b border-[var(--pgm-border-subtle)]">
                <td className="py-2">{r.data ? new Date(r.data).toLocaleString() : '—'}</td>
                <td className="py-2">{r.descricao}</td>
                <td className="py-2">{r.quantidade}</td>
                <td className="py-2">{BR.format(r.valorTotal || 0)}</td>
              </tr>
            ))}
          </tbody>
        </table>
        <p className="mt-2 font-semibold">Total: {BR.format(data.total || 0)}</p>
      </div>
    );
  }

  if (data.tab === 'laudos') {
    return (
      <div>
        <div className="mb-2 flex flex-wrap items-center justify-end gap-2">
          {boot?.paths?.apiPdfLaudo && id ? (
            <a
              href={`${String(boot.paths.apiPdfLaudo).replace(/\/$/, '')}/${id}`}
              target="_blank"
              rel="noreferrer"
              className="inline-flex items-center gap-1 rounded-md border border-[#0056b3]/50 bg-[#0056b3]/20 px-2.5 py-1.5 text-xs font-semibold text-[#7eb8ff] hover:bg-[#0056b3]/30"
            >
              PDF Laudo
            </a>
          ) : null}
          {boot?.classicEditUrl ? (
            <a
              href={boot.classicEditUrl}
              className="inline-flex items-center gap-1 rounded-md bg-emerald-700 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-emerald-600"
            >
              + Registo
            </a>
          ) : null}
        </div>
        <table className="w-full text-left text-sm">
        <tbody>
          {(data.rows || []).map((r) => (
            <tr key={r.id} className="border-b border-[var(--pgm-border-subtle)]">
              <td className="py-2 pr-2">{r.data ? new Date(r.data).toLocaleString() : '—'}</td>
              <td className="py-2 pr-2">{r.titulo}</td>
              <td className="py-2 pr-2">{r.tipo}</td>
            </tr>
          ))}
        </tbody>
      </table>
      </div>
    );
  }

  if (data.tab === 'financeiro') {
    const c = data.cards || {};
    return (
      <div className="space-y-4">
        <div className="grid grid-cols-2 gap-2 sm:grid-cols-4">
          <div className="rounded-lg border border-[var(--pgm-border)] bg-[var(--pgm-bg-elevated)] p-3 text-center">
            <div className="text-[0.65rem] text-[var(--pgm-text-muted)]">Total horas</div>
            <div className="font-mono text-lg">{fmtHms(c.totalHorasSeg)}</div>
          </div>
          <div className="rounded-lg border border-[var(--pgm-border)] bg-[var(--pgm-bg-elevated)] p-3 text-center">
            <div className="text-[0.65rem] text-[var(--pgm-text-muted)]">Peças</div>
            <div className="text-lg font-semibold">{BR.format(c.totalPecas || 0)}</div>
          </div>
          <div className="rounded-lg border border-[var(--pgm-border)] bg-[var(--pgm-bg-elevated)] p-3 text-center">
            <div className="text-[0.65rem] text-[var(--pgm-text-muted)]">Serviços</div>
            <div className="text-lg font-semibold">{BR.format(c.totalServicos || 0)}</div>
          </div>
          <div className="rounded-lg border border-[var(--pgm-border)] bg-[var(--pgm-bg-elevated)] p-3 text-center">
            <div className="text-[0.65rem] text-[var(--pgm-text-muted)]">Geral</div>
            <div className="text-lg font-semibold text-[#0056b3]">{BR.format(c.totalGeral || 0)}</div>
          </div>
        </div>
        <table className="w-full text-left text-sm">
          <thead>
            <tr className="text-xs text-[var(--pgm-text-muted)]">
              <th className="py-1">Data</th>
              <th className="py-1">Descrição</th>
              <th className="py-1">Tipo</th>
              <th className="py-1">Valor</th>
              <th className="py-1">Estado</th>
            </tr>
          </thead>
          <tbody>
            {(data.ledger || []).map((l, i) => (
              <tr key={i} className="border-t border-[var(--pgm-border-subtle)]">
                <td className="py-1">{l.data ? new Date(l.data).toLocaleString() : '—'}</td>
                <td className="py-1">{l.descricao}</td>
                <td className="py-1">{l.tipo}</td>
                <td className="py-1">{BR.format(l.valor || 0)}</td>
                <td className="py-1">
                  <span className="rounded bg-emerald-900/50 px-1.5 text-[0.65rem] text-emerald-200">{l.status}</span>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    );
  }

  if (data.tab === 'contrato') {
    const s = data.snapshot || {};
    const pct = s.percentUsed != null ? Number(s.percentUsed) : null;
    return (
      <div className="space-y-3 text-sm">
        <div className="flex flex-wrap items-center gap-4">
          {pct != null && !Number.isNaN(pct) ? contractPercentRing(pct) : null}
          <div>
            <p className="font-medium text-[var(--pgm-text)]">{s.label || 'Contrato'}</p>
            <p className="text-[var(--pgm-text-muted)]">
              {s.percentUsed != null ? `${Number(s.percentUsed).toFixed(1)}% usado` : '—'}
            </p>
          </div>
        </div>
        <h4 className="font-semibold">Débitos (worklog)</h4>
        <ul className="list-inside list-decimal space-y-1 text-[var(--pgm-text-secondary)]">
          {(data.debits || []).map((d, i) => (
            <li key={i}>
              {d.seconds ? fmtHms(d.seconds) : '—'} {d.data ? new Date(d.data).toLocaleString() : ''}
            </li>
          ))}
        </ul>
      </div>
    );
  }

  if (data.tab === 'alertas') {
    return (
      <ul className="space-y-2">
        {(data.rows || []).map((a) => (
          <li
            key={a.id}
            className={`rounded-lg border px-3 py-2 text-sm ${
              a.level === 'critical'
                ? 'border-red-500/50 bg-red-950/30'
                : a.level === 'danger'
                  ? 'border-orange-500/50 bg-orange-950/20'
                  : a.level === 'warning'
                    ? 'border-amber-500/50 bg-amber-950/25'
                    : 'border-blue-500/40 bg-blue-950/20'
            }`}
          >
            <div className="text-[0.65rem] text-[var(--pgm-text-muted)]">
              {a.created ? new Date(a.created).toLocaleString() : ''} — {a.level}
            </div>
            {a.message}
          </li>
        ))}
      </ul>
    );
  }

  return <p className="text-sm text-[var(--pgm-text-muted)]">—</p>;
}
