import { useEffect, useMemo, useRef, useState } from 'react';
import {
  attachAssetToTicket,
  detachAssetFromTicket,
  fetchServicedeskData,
} from '../lib/api';
import TicketTimeline from './TicketTimeline.jsx';
import TicketHorasTabPanel from './TicketHorasTabPanel.jsx';

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

function fmtHms(sec) {
  const s = Math.max(0, Number(sec) || 0);
  const h = Math.floor(s / 3600);
  const m = Math.floor((s % 3600) / 60);
  const r = s % 60;
  return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(r).padStart(2, '0')}`;
}

function statusOpLabel(s) {
  const map = {
    em_uso: 'Em uso',
    estoque: 'Em estoque',
    manutencao: 'Manutenção',
    reservado: 'Reservado',
    descartado: 'Descartado',
    perdido: 'Perdido',
  };
  return map[String(s || '').toLowerCase()] || (s ? String(s) : '—');
}

function tipoLabel(t) {
  const v = String(t || '').toLowerCase();
  const map = {
    notebook: 'Notebook',
    desktop: 'Desktop',
    servidor: 'Servidor',
    impressora: 'Impressora',
    switch: 'Switch',
    roteador: 'Roteador',
    firewall: 'Firewall',
    monitor: 'Monitor',
    storage: 'Storage',
    nobreak: 'Nobreak',
    telefone: 'Telefone',
    celular: 'Celular',
    tablet: 'Tablet',
  };
  return map[v] || (t ? String(t) : '—');
}

/** Caminhos absolutos na raiz do host quebram com APP em subpasta (ex. /portal/). Alinha a tickets API (`boot.webroot`). */
function pathWithWebroot(boot, path) {
  const p = path.startsWith('/') ? path : `/${path}`;
  const b = boot?.webroot != null ? String(boot.webroot).replace(/\/$/, '') : '';
  return b ? `${b}${p}` : p;
}

function debugLog18a583(runId, hypothesisId, location, message, data = {}) {
  // #region agent log
  fetch('http://127.0.0.1:7753/ingest/17010d6d-b722-4a03-aba9-a1bdf34f817d', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-Debug-Session-Id': '18a583' },
    body: JSON.stringify({
      sessionId: '18a583',
      runId,
      hypothesisId,
      location,
      message,
      data,
      timestamp: Date.now(),
    }),
  }).catch(() => {});
  // #endregion
}

export default function ServiceDeskTabPanels({ ticket, tab, boot = null, timelineEvents = null }) {
  const id = ticket?.id;
  const [data, setData] = useState(null);
  const [ativosQ, setAtivosQ] = useState('');
  const [ativosBusy, setAtivosBusy] = useState(false);
  const [ativosError, setAtivosError] = useState(null);
  const [showAddAsset, setShowAddAsset] = useState(false);
  const [pickerQ, setPickerQ] = useState('');
  const pickerRef = useRef(null);
  const [err, setErr] = useState(null);

  const historicoList = useMemo(() => {
    if (!Array.isArray(timelineEvents)) {
      return [];
    }
    return timelineEvents.filter((e) => String(e.type || '').toLowerCase() !== 'signature');
  }, [timelineEvents]);

  useEffect(() => {
    let c = false;
    (async () => {
      setErr(null);
      setData(null);
      if (tab === 'historico' || tab === 'horas') {
        return;
      }
      const r = await fetchServicedeskData(id, tab);
      // #region agent log
      debugLog18a583('pre-fix', 'H5', 'ServiceDeskTabPanels:fetchServicedeskData', 'api response received', {
        ticketId: Number(id || 0),
        tab,
        ok: Boolean(r?.ok),
        error: r?.error || null,
        responseTab: r?.tab || null,
        availableCount: Array.isArray(r?.available) ? r.available.length : null,
        linkedCount: Array.isArray(r?.linked) ? r.linked.length : Array.isArray(r?.rows) ? r.rows.length : null,
      });
      // #endregion
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

  useEffect(() => {
    if (tab !== 'ativos' || !data?.ok) return;
    const linked = Array.isArray(data.linked) ? data.linked : Array.isArray(data.rows) ? data.rows : [];
    const available = Array.isArray(data.available) ? data.available : [];
    // #region agent log
    debugLog18a583('pre-fix', 'H6', 'ServiceDeskTabPanels:ativosRender', 'ativos payload rendered', {
      ticketId: Number(id || 0),
      clienteIdPayload: Number(data?.cliente_id || 0),
      linkedCount: linked.length,
      availableCount: available.length,
      availableIdsSample: available.slice(0, 10).map((a) => Number(a?.id || 0)),
    });
    // #endregion
  }, [tab, data, id]);

  if (tab === 'historico') {
    return (
      <div className="min-h-0 space-y-3 px-1">
        <p className="text-[0.7rem] leading-snug text-[var(--pgm-text-muted)]">
          Histórico do chamado: comentários, situação, peças, horas e restantes eventos. Use o filtro por tipo. Assinaturas
          na OS/impressão.
        </p>
        {historicoList.length === 0 ? (
          <p className="text-sm text-[var(--pgm-text-muted)]">Nenhum evento ainda.</p>
        ) : (
          <TicketTimeline events={historicoList} layout="timeline" />
        )}
      </div>
    );
  }

  if (tab === 'horas') {
    return <TicketHorasTabPanel ticket={ticket} timelineEvents={timelineEvents} />;
  }

  if (err) {
    return <p className="text-sm text-red-300">{err}</p>;
  }
  if (!data?.ok) {
    return <p className="text-sm text-[var(--pgm-text-muted)]">A carregar…</p>;
  }

  if (data.tab === 'ativos') {
    const linked = Array.isArray(data.linked) ? data.linked : Array.isArray(data.rows) ? data.rows : [];
    const available = Array.isArray(data.available) ? data.available : [];
    const clienteId = data.cliente_id || ticket?.idcliente || null;
    const q = ativosQ.trim().toLowerCase();
    const filteredLinked = q
      ? linked.filter((a) => {
          const s = `${a.descricao || ''} ${a.identificador || ''} ${a.codigo_qr || ''} ${a.tipo || ''} ${a.numero_serie || ''} ${a.hostname || ''} ${a.localizacao || ''}`.toLowerCase();
          return s.includes(q);
        })
      : linked;
    const pq = pickerQ.trim().toLowerCase();
    const filteredAvailable = pq
      ? available.filter((a) => {
          const s = `${a.descricao || ''} ${a.identificador || ''} ${a.codigo_qr || ''} ${a.tipo || ''} ${a.numero_serie || ''} ${a.hostname || ''}`.toLowerCase();
          return s.includes(pq);
        })
      : available;
    const ativosCadastrarUrl = clienteId
      ? pathWithWebroot(boot, `/ativos/add?idcliente=${encodeURIComponent(clienteId)}`)
      : pathWithWebroot(boot, '/ativos/add');

    const reload = async () => {
      const r = await fetchServicedeskData(id, 'ativos');
      if (r.ok) {
        setData(r);
      }
    };
    const handleAttach = async (assetId) => {
      if (!assetId || ativosBusy) return;
      setAtivosBusy(true);
      setAtivosError(null);
      const r = await attachAssetToTicket(id, assetId);
      setAtivosBusy(false);
      if (!r.ok) {
        setAtivosError(r.error || 'erro_ao_vincular');

        return;
      }
      setPickerQ('');
      setShowAddAsset(false);
      reload();
    };
    const handleDetach = async (asset) => {
      if (!asset || ativosBusy) return;
      const ok = typeof window !== 'undefined' && typeof window.confirm === 'function'
        ? window.confirm(`Desvincular "${asset.descricao || asset.id}" deste chamado?`)
        : true;
      if (!ok) return;
      setAtivosBusy(true);
      setAtivosError(null);
      const r = await detachAssetFromTicket(id, {
        ticketAssetId: asset.ticket_asset_id,
        assetId: asset.id,
      });
      setAtivosBusy(false);
      if (!r.ok) {
        setAtivosError(r.error || 'erro_ao_desvincular');

        return;
      }
      reload();
    };

    return (
      <div className="space-y-3">
        <div className="flex flex-wrap items-center gap-2">
          <input
            type="search"
            value={ativosQ}
            onChange={(e) => setAtivosQ(e.target.value)}
            placeholder="Filtrar CIs vinculados…"
            className="min-w-[12rem] flex-1 rounded-md border border-[var(--pgm-border)] bg-[var(--pgm-bg-raised)] px-2 py-1.5 text-sm text-[var(--pgm-text)]"
          />
          <button
            type="button"
            onClick={() => {
              setShowAddAsset((v) => !v);
              setTimeout(() => pickerRef.current?.focus(), 50);
            }}
            disabled={ativosBusy}
            className="inline-flex items-center gap-1 rounded-md bg-emerald-700 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-emerald-600 disabled:opacity-50"
          >
            + Vincular CI
          </button>
          <a
            href={ativosCadastrarUrl}
            target="_blank"
            rel="noreferrer"
            className="inline-flex items-center gap-1 rounded-md border border-[var(--pgm-border)] bg-[var(--pgm-bg-raised)] px-2.5 py-1.5 text-xs font-semibold text-[var(--pgm-text)] hover:bg-[var(--pgm-bg-elevated)]"
            title="Cadastrar novo ativo (CMDB)"
          >
            Cadastrar ativo
          </a>
        </div>

        {showAddAsset ? (
          <div className="rounded-lg border border-[var(--pgm-border)] bg-[var(--pgm-bg-elevated)] p-2">
            <div className="mb-2 flex items-center gap-2">
              <input
                ref={pickerRef}
                type="search"
                value={pickerQ}
                onChange={(e) => setPickerQ(e.target.value)}
                placeholder="Pesquisar CI deste cliente…"
                className="min-w-[12rem] flex-1 rounded-md border border-[var(--pgm-border)] bg-[var(--pgm-bg-raised)] px-2 py-1.5 text-sm text-[var(--pgm-text)]"
              />
              <button
                type="button"
                onClick={() => setShowAddAsset(false)}
                className="rounded-md border border-[var(--pgm-border)] px-2 py-1 text-xs text-[var(--pgm-text-muted)] hover:bg-[var(--pgm-bg-raised)]"
              >
                Fechar
              </button>
            </div>
            {filteredAvailable.length === 0 ? (
              <p className="px-1 py-2 text-xs text-[var(--pgm-text-muted)]">
                {available.length === 0
                  ? 'Nenhum ativo cadastrado para este cliente. Use "Cadastrar ativo" para criar um novo.'
                  : 'Nenhum CI corresponde à pesquisa.'}
              </p>
            ) : (
              <ul className="max-h-56 divide-y divide-[var(--pgm-border-subtle)] overflow-y-auto">
                {filteredAvailable.slice(0, 50).map((a) => (
                  <li key={a.id} className="flex items-center justify-between gap-2 px-1 py-1.5">
                    <div className="min-w-0 flex-1">
                      <div className="truncate text-sm text-[var(--pgm-text)]">{a.descricao || `CI #${a.id}`}</div>
                      <div className="truncate text-[0.7rem] text-[var(--pgm-text-muted)]">
                        {tipoLabel(a.tipo)} · {a.identificador || a.numero_serie || `#${a.id}`}
                        {a.localizacao ? ` · ${a.localizacao}` : ''}
                      </div>
                    </div>
                    <button
                      type="button"
                      onClick={() => handleAttach(a.id)}
                      disabled={ativosBusy}
                      className="shrink-0 rounded-md bg-emerald-700 px-2 py-1 text-xs font-semibold text-white hover:bg-emerald-600 disabled:opacity-50"
                    >
                      Vincular
                    </button>
                  </li>
                ))}
              </ul>
            )}
          </div>
        ) : null}

        {ativosError ? (
          <p className="text-xs text-red-300">Erro: {String(ativosError)}</p>
        ) : null}

        <div className="overflow-x-auto">
          <table className="w-full min-w-[44rem] text-left text-sm">
            <thead>
              <tr className="border-b border-[var(--pgm-border)] text-xs uppercase text-[var(--pgm-text-muted)]">
                <th className="py-2 pr-2">CI</th>
                <th className="py-2 pr-2">Tipo</th>
                <th className="py-2 pr-2">Série / ID</th>
                <th className="py-2 pr-2">Estado</th>
                <th className="py-2 pr-2">Local</th>
                <th className="py-2 pr-2 text-right">Ações</th>
              </tr>
            </thead>
            <tbody>
              {filteredLinked.map((a) => (
                <tr key={a.ticket_asset_id || a.id} className="border-b border-[var(--pgm-border-subtle)]">
                  <td className="py-2 pr-2">
                    <div className="text-[var(--pgm-text)]">{a.descricao || `CI #${a.id}`}</div>
                    {a.hostname || a.marca || a.modelo ? (
                      <div className="text-[0.7rem] text-[var(--pgm-text-muted)]">
                        {[a.marca, a.modelo].filter(Boolean).join(' ')}
                        {a.hostname ? ` · ${a.hostname}` : ''}
                      </div>
                    ) : null}
                  </td>
                  <td className="py-2 pr-2 text-[var(--pgm-text-muted)]">{tipoLabel(a.tipo)}</td>
                  <td className="py-2 pr-2 font-mono text-xs">
                    {a.numero_serie || a.identificador || a.id}
                  </td>
                  <td className="py-2 pr-2">{statusOpLabel(a.status_operacional)}</td>
                  <td className="py-2 pr-2 text-[var(--pgm-text-muted)]">{a.localizacao || '—'}</td>
                  <td className="py-2 pr-2 text-right">
                    <a
                      href={pathWithWebroot(boot, `/ativos/view/${encodeURIComponent(a.id)}`)}
                      target="_blank"
                      rel="noreferrer"
                      className="mr-2 inline-flex items-center gap-1 rounded-md border border-[var(--pgm-border)] px-2 py-1 text-[0.7rem] text-[var(--pgm-text)] hover:bg-[var(--pgm-bg-raised)]"
                    >
                      Ficha
                    </a>
                    <button
                      type="button"
                      onClick={() => handleDetach(a)}
                      disabled={ativosBusy}
                      className="inline-flex items-center gap-1 rounded-md border border-red-500/40 bg-red-950/30 px-2 py-1 text-[0.7rem] font-semibold text-red-200 hover:bg-red-900/40 disabled:opacity-50"
                    >
                      Desvincular
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        {filteredLinked.length === 0 && (
          <p className="text-sm text-[var(--pgm-text-muted)]">
            Nenhum CI vinculado a este chamado. Use "+ Vincular CI" para associar um ativo.
          </p>
        )}
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
