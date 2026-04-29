import { useEffect, useMemo, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import {
  addTicketProduct,
  attachAssetToTicket,
  detachAssetFromTicket,
  fetchServicedeskData,
  searchTicketProductsServices,
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

function ativosErrorMessage(raw) {
  const code = String(raw || '').trim().toLowerCase();
  const map = {
    bad_request: 'Requisição inválida ao vincular ativo.',
    forbidden: 'Você não tem permissão para vincular/desvincular CIs neste chamado.',
    asset_not_found: 'O ativo não pertence ao cliente/empresa deste chamado.',
    invalid_params: 'Dados inválidos para executar a ação.',
    save_failed: 'Não foi possível salvar o vínculo do ativo.',
    delete_failed: 'Não foi possível remover o vínculo do ativo.',
    not_found: 'Vínculo não encontrado para este chamado.',
    exception: 'Erro interno ao processar o vínculo do ativo.',
    no_api: 'API de vínculo de ativos não configurada.',
    erro_ao_vincular: 'Não foi possível vincular o ativo ao chamado.',
    erro_ao_desvincular: 'Não foi possível desvincular o ativo do chamado.',
  };
  return map[code] || `Falha ao processar a ação (${String(raw || 'erro')}).`;
}

function AssetFieldIcon({ field }) {
  const common = {
    width: 14,
    height: 14,
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    strokeWidth: 1.8,
    className: 'text-[var(--pgm-text-muted)]',
    'aria-hidden': true,
  };
  switch (field) {
    case 'tipo':
      return <svg {...common}><path d="M3 7l9-4 9 4-9 4-9-4zm0 5l9 4 9-4M3 17l9 4 9-4" strokeLinecap="round" strokeLinejoin="round" /></svg>;
    case 'estado':
      return <svg {...common}><path d="M9 12l2 2 4-4M21 12a9 9 0 11-18 0 9 9 0 0118 0z" strokeLinecap="round" strokeLinejoin="round" /></svg>;
    case 'serie':
      return <svg {...common}><path d="M8 7h8M8 12h8M8 17h5M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z" strokeLinecap="round" strokeLinejoin="round" /></svg>;
    case 'qr':
      return <svg {...common}><path d="M5 5h5v5H5V5zm9 0h5v5h-5V5zM5 14h5v5H5v-5zm9 0h2m3 0h-1m-4 3h5m-3-3v5" strokeLinecap="round" strokeLinejoin="round" /></svg>;
    case 'hostname':
      return <svg {...common}><path d="M4 6h16M4 12h16M4 18h16M8 4v16M16 4v16" strokeLinecap="round" strokeLinejoin="round" /></svg>;
    case 'marcaModelo':
      return <svg {...common}><path d="M8 6h8M6 10h12M8 14h8M10 18h4" strokeLinecap="round" strokeLinejoin="round" /></svg>;
    case 'localizacao':
      return <svg {...common}><path d="M12 21s7-4.5 7-11a7 7 0 10-14 0c0 6.5 7 11 7 11zm0-8.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5z" strokeLinecap="round" strokeLinejoin="round" /></svg>;
    default:
      return <svg {...common}><path d="M12 8h.01M12 12h.01M12 16h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" strokeLinecap="round" strokeLinejoin="round" /></svg>;
  }
}

function fmtDateBr(value) {
  if (!value) return '—';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return String(value);
  return d.toLocaleDateString('pt-BR');
}

function fmtCurrencyBr(value) {
  const n = Number(value);
  if (!Number.isFinite(n)) return '—';
  return BR.format(n);
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

function AssetDetailsModal({ assetModal, setAssetModal, sections, boot, assetModalRef }) {
  if (!assetModal || typeof document === 'undefined') return null;
  return createPortal(
    <div
      className="fixed inset-0 z-[9999] flex items-center justify-center bg-black/55 px-3 py-6 backdrop-blur-[2px] transition-opacity duration-200"
      role="dialog"
      aria-modal="true"
      aria-label="Ficha do ativo"
      onClick={() => setAssetModal(null)}
    >
      <div
        className="w-full max-w-2xl overflow-hidden rounded-2xl border border-[var(--pgm-border)] bg-[var(--pgm-bg-surface)] shadow-[0_20px_60px_rgba(0,0,0,0.45)] transition-all duration-200 ease-out"
        style={{ transform: 'translateY(0) scale(1)', opacity: 1 }}
        ref={assetModalRef}
        onClick={(ev) => ev.stopPropagation()}
      >
        <div className="flex items-start justify-between gap-3 border-b border-[var(--pgm-border-subtle)] bg-gradient-to-r from-[var(--pgm-bg-elevated)] to-[var(--pgm-bg-surface)] px-4 py-3">
          <div className="min-w-0">
            <p className="truncate text-sm font-semibold text-[var(--pgm-text)]">
              {assetModal.descricao || `CI #${assetModal.id}`}
            </p>
            <p className="truncate text-[0.72rem] text-[var(--pgm-text-muted)]">
              {tipoLabel(assetModal.tipo)} · {assetModal.identificador || `#${assetModal.id}`}
            </p>
          </div>
          <button
            type="button"
            onClick={() => setAssetModal(null)}
            className="rounded-md border border-[var(--pgm-border)] px-2 py-1 text-xs text-[var(--pgm-text-muted)] hover:bg-[var(--pgm-bg-raised)] hover:text-[var(--pgm-text)]"
          >
            Fechar
          </button>
        </div>
        <div className="max-h-[68vh] space-y-3 overflow-y-auto px-4 py-4">
          {sections
            .filter((s) => s.rows.length > 0)
            .map((section) => (
              <div key={section.title} className="rounded-xl border border-[var(--pgm-border-subtle)] bg-[var(--pgm-bg-elevated)] px-3 py-3">
                <p className="mb-2 text-[0.68rem] font-semibold uppercase tracking-[0.06em] text-[var(--pgm-text-muted)]">
                  {section.title}
                </p>
                <div className="grid gap-2 sm:grid-cols-2">
                  {section.rows.map((row) => (
                    <div key={`${section.title}-${row.label}`} className="rounded-lg border border-[var(--pgm-border-subtle)] bg-[var(--pgm-bg-surface)] px-3 py-2">
                      <p className="flex items-center gap-1.5 text-[0.65rem] uppercase tracking-[0.05em] text-[var(--pgm-text-muted)]">
                        <AssetFieldIcon field={row.key} />
                        {row.label}
                      </p>
                      <p className="mt-0.5 break-words text-sm text-[var(--pgm-text)]">{row.value}</p>
                    </div>
                  ))}
                </div>
              </div>
            ))}
        </div>
        <div className="flex justify-end border-t border-[var(--pgm-border-subtle)] px-4 py-3">
          <a
            href={pathWithWebroot(boot, `/ativos/view/${encodeURIComponent(assetModal.id)}`)}
            target="_blank"
            rel="noreferrer"
            className="inline-flex items-center gap-1 rounded-md border border-[var(--pgm-border)] px-2.5 py-1.5 text-xs font-semibold text-[var(--pgm-text)] hover:bg-[var(--pgm-bg-raised)]"
          >
            Abrir ficha completa
          </a>
        </div>
      </div>
    </div>,
    document.body
  );
}

export default function ServiceDeskTabPanels({ ticket, tab, boot = null, timelineEvents = null }) {
  const id = ticket?.id;
  const [data, setData] = useState(null);
  const [ativosQ, setAtivosQ] = useState('');
  const [assetModal, setAssetModal] = useState(null);
  const [ativosBusy, setAtivosBusy] = useState(false);
  const [ativosError, setAtivosError] = useState(null);
  const [showAddAsset, setShowAddAsset] = useState(false);
  const [pickerQ, setPickerQ] = useState('');
  const pickerRef = useRef(null);
  const assetModalRef = useRef(null);
  const assetTriggerRef = useRef(null);
  const [err, setErr] = useState(null);
  const [pecasModalOpen, setPecasModalOpen] = useState(false);
  const [pecasCatalogQ, setPecasCatalogQ] = useState('');
  const [pecasCatalogTipo, setPecasCatalogTipo] = useState('');
  const [pecasCatalogItems, setPecasCatalogItems] = useState([]);
  const [pecasQtyById, setPecasQtyById] = useState({});
  const [pecasCatalogBusy, setPecasCatalogBusy] = useState(false);
  const [pecasActionBusyId, setPecasActionBusyId] = useState(0);
  const [pecasError, setPecasError] = useState(null);
  const [pecasSuccess, setPecasSuccess] = useState('');
  const [pecaLinhaDestaqueId, setPecaLinhaDestaqueId] = useState(0);
  const [pecasAddAndKeepOpen, setPecasAddAndKeepOpen] = useState(false);

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

  useEffect(() => {
    if (!assetModal) {
      if (assetTriggerRef.current && typeof assetTriggerRef.current.focus === 'function') {
        assetTriggerRef.current.focus();
      }
      return undefined;
    }
    const node = assetModalRef.current;
    const selectors = [
      'a[href]',
      'button:not([disabled])',
      'textarea:not([disabled])',
      'input:not([disabled])',
      'select:not([disabled])',
      '[tabindex]:not([tabindex="-1"])',
    ].join(',');
    const focusables = node ? Array.from(node.querySelectorAll(selectors)) : [];
    const firstFocusable = focusables[0] || null;
    const lastFocusable = focusables[focusables.length - 1] || null;
    firstFocusable?.focus();

    const onKeydown = (ev) => {
      if (ev.key === 'Escape') {
        setAssetModal(null);
        return;
      }
      if (ev.key === 'Tab' && focusables.length > 0) {
        if (ev.shiftKey && document.activeElement === firstFocusable) {
          ev.preventDefault();
          lastFocusable?.focus();
          return;
        }
        if (!ev.shiftKey && document.activeElement === lastFocusable) {
          ev.preventDefault();
          firstFocusable?.focus();
        }
      }
    };
    window.addEventListener('keydown', onKeydown);
    return () => window.removeEventListener('keydown', onKeydown);
  }, [assetModal]);

  const reloadTabData = async (targetTab) => {
    if (!id) return;
    const r = await fetchServicedeskData(id, targetTab);
    if (r.ok) {
      setData(r);
    }
  };

  const loadPecasCatalog = async (opts = {}) => {
    if (!id) return;
    setPecasCatalogBusy(true);
    setPecasError(null);
    const r = await searchTicketProductsServices(id, {
      q: opts.q ?? pecasCatalogQ,
      tipo: opts.tipo ?? pecasCatalogTipo,
    });
    setPecasCatalogBusy(false);
    if (!r.ok) {
      setPecasError(r.error || 'Erro ao buscar produtos/serviços.');
      return;
    }
    const nextItems = r.items || [];
    setPecasCatalogItems(nextItems);
    setPecasQtyById((prev) => {
      const next = { ...prev };
      nextItems.forEach((it) => {
        const idKey = Number(it?.id || 0);
        if (idKey > 0 && (!Number.isFinite(Number(next[idKey])) || Number(next[idKey]) <= 0)) {
          next[idKey] = 1;
        }
      });
      return next;
    });
  };

  const openPecasModal = async () => {
    setPecasCatalogQ('');
    setPecasCatalogTipo('');
    setPecasError(null);
    setPecasModalOpen(true);
    await loadPecasCatalog({ q: '', tipo: '' });
  };

  const handleAddPecaServico = async (item) => {
    const idProduto = Number(item?.id || 0);
    const qty = Number(pecasQtyById[idProduto] || 0);
    if (idProduto <= 0) return;
    if (!Number.isFinite(qty) || qty <= 0) {
      setPecasError('A quantidade deve ser maior que zero.');
      return;
    }
    setPecasActionBusyId(idProduto);
    setPecasError(null);
    const r = await addTicketProduct(id, {
      produto_id: idProduto,
      quantidade: qty,
      valor_unitario: Number(item?.valor || 0),
    });
    setPecasActionBusyId(0);
    if (!r.ok) {
      if (r.error === 'estoque_insuficiente') {
        setPecasError('Estoque insuficiente para o produto selecionado.');
      } else {
        setPecasError('Não foi possível adicionar o item ao ticket.');
      }
      return;
    }
    setPecasSuccess('Item adicionado com sucesso.');
    if (!pecasAddAndKeepOpen) {
      setPecasModalOpen(false);
      setPecasCatalogQ('');
      setPecasCatalogTipo('');
      setPecasError(null);
    }
    setPecaLinhaDestaqueId(Number(r.id || 0));
    await reloadTabData('pecas');
  };

  useEffect(() => {
    if (!pecasModalOpen) return undefined;
    const t = window.setTimeout(() => {
      loadPecasCatalog();
    }, 250);
    return () => window.clearTimeout(t);
  }, [pecasModalOpen, pecasCatalogQ, pecasCatalogTipo]);

  useEffect(() => {
    if (!pecasSuccess) return undefined;
    const t = window.setTimeout(() => setPecasSuccess(''), 2600);
    return () => window.clearTimeout(t);
  }, [pecasSuccess]);

  useEffect(() => {
    if (!pecaLinhaDestaqueId) return undefined;
    const t = window.setTimeout(() => setPecaLinhaDestaqueId(0), 2200);
    return () => window.clearTimeout(t);
  }, [pecaLinhaDestaqueId]);

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
    const sections = [
      {
        title: 'Identificação',
        rows: [
          { key: 'tipo', label: 'Tipo', value: tipoLabel(assetModal?.tipo) },
          { key: 'serie', label: 'Categoria', value: assetModal?.categoria || '—' },
          { key: 'serie', label: 'Cliente', value: assetModal?.cliente_nome || '—' },
          { key: 'serie', label: 'Patrimônio', value: assetModal?.patrimonio || '—' },
          { key: 'serie', label: 'Identificador', value: assetModal?.identificador || `#${assetModal?.id || '—'}` },
          { key: 'qr', label: 'Código QR', value: assetModal?.codigo_qr || '—' },
          { key: 'estado', label: 'Estado', value: statusOpLabel(assetModal?.status_operacional) },
        ],
      },
      {
        title: 'Hardware / Rede',
        rows: [
          { key: 'marcaModelo', label: 'Marca', value: assetModal?.marca || '—' },
          { key: 'marcaModelo', label: 'Modelo', value: assetModal?.modelo || '—' },
          { key: 'serie', label: 'Nº de série', value: assetModal?.numero_serie || '—' },
          { key: 'hostname', label: 'Hostname', value: assetModal?.hostname || '—' },
          { key: 'hostname', label: 'IP', value: assetModal?.ip || '—' },
          { key: 'hostname', label: 'MAC', value: assetModal?.mac || '—' },
          { key: 'hostname', label: 'Sistema', value: assetModal?.sistema_operacional || '—' },
          { key: 'localizacao', label: 'Localização', value: assetModal?.localizacao || '—' },
        ],
      },
      {
        title: 'Garantia / Financeiro',
        rows: [
          { key: 'serie', label: 'Aquisição', value: fmtDateBr(assetModal?.dt_aquisicao) },
          { key: 'serie', label: 'Instalação', value: fmtDateBr(assetModal?.dt_instalacao) },
          { key: 'serie', label: 'Fim da garantia', value: fmtDateBr(assetModal?.dt_garantia_fim) },
          { key: 'serie', label: 'Fornecedor', value: assetModal?.fornecedor || '—' },
          { key: 'serie', label: 'Custo', value: fmtCurrencyBr(assetModal?.custo_aquisicao) },
          { key: 'serie', label: 'Propriedade', value: assetModal?.propriedade || '—' },
        ],
      },
      {
        title: 'Cadastro',
        rows: [
          { key: 'estado', label: 'Ativo no cadastro', value: assetModal?.ativo ? 'Sim' : 'Não' },
          { key: 'serie', label: 'Responsável', value: assetModal?.responsavel_nome || '—' },
          { key: 'serie', label: 'Criado em', value: fmtDateBr(assetModal?.created) },
          { key: 'serie', label: 'Atualizado em', value: fmtDateBr(assetModal?.modified) },
          { key: 'obs', label: 'Observações', value: assetModal?.observacoes || '—' },
        ],
      },
    ];

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
          <p className="text-xs text-red-300">Erro: {ativosErrorMessage(ativosError)}</p>
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
                    <button
                      type="button"
                      onClick={(ev) => {
                        assetTriggerRef.current = ev.currentTarget;
                        setAssetModal(a);
                      }}
                      className="mr-2 inline-flex items-center gap-1 rounded-md border border-[var(--pgm-border)] px-2 py-1 text-[0.7rem] text-[var(--pgm-text)] hover:bg-[var(--pgm-bg-raised)]"
                    >
                      Ficha
                    </button>
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
            Nenhum CI vinculado a este chamado. Use &quot;+ Vincular CI&quot; para associar um ativo.
          </p>
        )}
        <AssetDetailsModal
          assetModal={assetModal}
          setAssetModal={setAssetModal}
          sections={sections}
          boot={boot}
          assetModalRef={assetModalRef}
        />
      </div>
    );
  }

  if (data.tab === 'pecas') {
    const rows = data.rows || [];
    return (
      <div>
        <div className="mb-2 flex flex-wrap items-center justify-between gap-2">
          <span className="text-[0.7rem] text-[var(--pgm-text-muted)]">
            Produtos e serviços lançados no ticket.
          </span>
          <button
            type="button"
            onClick={openPecasModal}
            className="inline-flex items-center gap-1 rounded-md bg-emerald-700 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-emerald-600"
          >
            + Peça / serviço
          </button>
        </div>
        {pecasSuccess ? <p className="mb-2 text-xs text-emerald-300">{pecasSuccess}</p> : null}
        {pecasError ? <p className="mb-2 text-xs text-red-300">{pecasError}</p> : null}
        <table className="w-full min-w-[38rem] text-left text-sm">
          <thead>
            <tr className="border-b border-[var(--pgm-border)] text-xs text-[var(--pgm-text-muted)]">
              <th className="py-2">Data</th>
              <th className="py-2">Tipo</th>
              <th className="py-2">Descrição</th>
              <th className="py-2">Qtd</th>
              <th className="py-2">Vl. Unit.</th>
              <th className="py-2">Total</th>
            </tr>
          </thead>
          <tbody>
            {rows.map((r) => (
              <tr
                key={r.id}
                className={`border-b border-[var(--pgm-border-subtle)] ${
                  Number(r.id || 0) === Number(pecaLinhaDestaqueId || 0) ? 'bg-emerald-900/20' : ''
                }`}
              >
                <td className="py-2">{r.data ? new Date(r.data).toLocaleString() : '—'}</td>
                <td className="py-2">{r.tipo || 'Produto'}</td>
                <td className="py-2">{r.descricao}</td>
                <td className="py-2">{r.quantidade}</td>
                <td className="py-2">{BR.format(r.valorUnit || 0)}</td>
                <td className="py-2">{BR.format(r.valorTotal || 0)}</td>
              </tr>
            ))}
            {rows.length === 0 ? (
              <tr>
                <td className="py-3 text-[var(--pgm-text-muted)]" colSpan={6}>Nenhum item adicionado.</td>
              </tr>
            ) : null}
          </tbody>
        </table>
        <p className="mt-2 font-semibold">Total: {BR.format(data.total || 0)}</p>
        {pecasModalOpen ? (
          <div
            className="fixed inset-0 z-[9999] flex items-center justify-center bg-black/55 px-3 py-6"
            role="dialog"
            aria-modal="true"
            onClick={() => setPecasModalOpen(false)}
          >
            <div
              className="w-full max-w-5xl rounded-xl border border-[var(--pgm-border)] bg-[var(--pgm-bg-surface)] p-4 shadow-xl"
              onClick={(ev) => ev.stopPropagation()}
            >
              <div className="mb-3 flex flex-wrap items-center justify-between gap-2 border-b border-[var(--pgm-border-subtle)] pb-2">
                <h3 className="text-sm font-semibold text-[var(--pgm-text)]">Selecionar peça / serviço</h3>
                <button
                  type="button"
                  className="rounded-md border border-[var(--pgm-border)] px-2 py-1 text-xs text-[var(--pgm-text-muted)]"
                  onClick={() => setPecasModalOpen(false)}
                >
                  Fechar
                </button>
              </div>
              <div className="mb-3 grid gap-2 sm:grid-cols-[1fr_180px]">
                <input
                  type="search"
                  value={pecasCatalogQ}
                  onChange={(e) => setPecasCatalogQ(e.target.value)}
                  placeholder="Buscar por nome, código ou descrição..."
                  className="rounded-md border border-[var(--pgm-border)] bg-[var(--pgm-bg-raised)] px-2 py-1.5 text-sm text-[var(--pgm-text)]"
                />
                <select
                  value={pecasCatalogTipo}
                  onChange={(e) => setPecasCatalogTipo(e.target.value)}
                  className="rounded-md border border-[var(--pgm-border)] bg-[var(--pgm-bg-raised)] px-2 py-1.5 text-sm text-[var(--pgm-text)]"
                >
                  <option value="">Todos os tipos</option>
                  <option value="produto">Produto</option>
                  <option value="servico">Serviço</option>
                </select>
              </div>
              <div className="mb-3">
                <label className="inline-flex items-center gap-2 text-xs text-[var(--pgm-text-muted)]">
                  <input
                    type="checkbox"
                    checked={pecasAddAndKeepOpen}
                    onChange={(e) => setPecasAddAndKeepOpen(Boolean(e.target.checked))}
                  />
                  Adicionar e continuar (não fechar modal)
                </label>
              </div>
              <div className="max-h-[60vh] overflow-auto">
                <table className="w-full min-w-[52rem] text-left text-sm">
                  <thead>
                    <tr className="border-b border-[var(--pgm-border)] text-xs text-[var(--pgm-text-muted)]">
                      <th className="py-2">Descrição</th>
                      <th className="py-2">Tipo</th>
                      <th className="py-2">Valor padrão</th>
                      <th className="py-2">Estoque</th>
                      <th className="py-2 w-[110px]">Qtd</th>
                      <th className="py-2">Total</th>
                      <th className="py-2 text-right">Ação</th>
                    </tr>
                  </thead>
                  <tbody>
                    {pecasCatalogItems.map((it) => {
                      const idItem = Number(it.id || 0);
                      const qty = Number(pecasQtyById[idItem] || 1);
                      const valor = Number(it.valor || 0);
                      const totalLinha = qty > 0 ? qty * valor : 0;
                      return (
                        <tr key={idItem} className="border-b border-[var(--pgm-border-subtle)]">
                          <td className="py-2">
                            <div className="text-[var(--pgm-text)]">{it.descricao || '—'}</div>
                            <div className="text-[0.65rem] text-[var(--pgm-text-muted)]">{it.codigo || 'sem código'}</div>
                          </td>
                          <td className="py-2">{it.tipo === 'servico' ? 'Serviço' : 'Produto'}</td>
                          <td className="py-2">{BR.format(valor)}</td>
                          <td className="py-2">{it.tipo === 'produto' ? (it.estoque ?? '—') : '—'}</td>
                          <td className="py-2">
                            <input
                              type="number"
                              min="1"
                              step="0.01"
                              value={qty}
                              onChange={(e) =>
                                setPecasQtyById((prev) => ({ ...prev, [idItem]: e.target.value }))
                              }
                              className="w-full rounded-md border border-[var(--pgm-border)] bg-[var(--pgm-bg-raised)] px-2 py-1 text-sm text-[var(--pgm-text)]"
                            />
                          </td>
                          <td className="py-2">{BR.format(totalLinha)}</td>
                          <td className="py-2 text-right">
                            <button
                              type="button"
                              onClick={() => handleAddPecaServico(it)}
                              disabled={pecasActionBusyId === idItem || !Number.isFinite(qty) || qty <= 0}
                              className="rounded-md bg-emerald-700 px-2 py-1 text-xs font-semibold text-white hover:bg-emerald-600 disabled:opacity-50"
                            >
                              {pecasActionBusyId === idItem ? 'Adicionando...' : 'Adicionar'}
                            </button>
                          </td>
                        </tr>
                      );
                    })}
                    {!pecasCatalogBusy && pecasCatalogItems.length === 0 ? (
                      <tr>
                        <td className="py-3 text-[var(--pgm-text-muted)]" colSpan={7}>
                          Nenhum produto/serviço encontrado para os filtros informados.
                        </td>
                      </tr>
                    ) : null}
                  </tbody>
                </table>
                {pecasCatalogBusy ? <p className="py-3 text-sm text-[var(--pgm-text-muted)]">Carregando...</p> : null}
              </div>
            </div>
          </div>
        ) : null}
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
