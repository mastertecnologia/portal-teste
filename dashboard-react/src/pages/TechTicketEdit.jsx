import { useEffect, useMemo, useRef, useState } from 'react';
import {
  fetchTicketDetail,
  fetchTicketTimeline,
  postComentario,
  saveTicketSolicitacao,
  saveTicketDescricaoAtendimento,
  getBoot,
} from '../lib/api';
import { useTicketCommentsPoll, TICKET_COMMENTS_POLL_MS } from '../hooks/useTicketCommentsPoll';
import { useTicketCommentsSocket } from '../hooks/useTicketCommentsSocket';
import { useTicketTimelinePoll } from '../hooks/useTicketTimelinePoll';
import { useConversationScrollToBottom } from '../hooks/useConversationScrollToBottom';
import { finalizeOptimisticComment, formatCommentPostTimestamp, stripHtml } from '../lib/text';
import { badgeClass, statusType } from '../lib/ticketUi';
import TicketAnexosPanel from '../components/TicketAnexosPanel.jsx';
import HorasTecnicasTimerPanel from '../components/HorasTecnicasTimerPanel.jsx';
import CommentMessage from '../components/CommentMessage.jsx';
import ServiceDeskTabPanels from '../components/ServiceDeskTabPanels.jsx';
import TicketInfoPanel, { TicketResumoPanel } from '../components/TicketInfoPanel.jsx';

const SD_TAB_IDS = new Set([
  'atendimento',
  'chat',
  'historico',
  'horas',
  'ativos',
  'pecas',
  'laudos',
  'financeiro',
  'contrato',
  'alertas',
]);

/** Com `apiServicedeskData` desligado só existem as guias Atendimento e Chat. */
function normalizeSdTab(h, canServicedeskTabs) {
  const raw = (h || '').replace(/^#/, '');
  if (!SD_TAB_IDS.has(raw)) return 'atendimento';
  if (!canServicedeskTabs && raw !== 'atendimento' && raw !== 'chat') return 'atendimento';
  return raw;
}

function minutosHumanosCurto(totalMin) {
  const m = Math.max(0, Math.floor(Number(totalMin) || 0));
  if (m <= 0) return '';
  const h = Math.floor(m / 60);
  const r = m % 60;
  if (h <= 0) return `${r} min`;
  if (r === 0) return `${h} h`;
  return `${h} h ${r} min`;
}

/** `false` = não exibir links para o formulário legado (timer / anexos clássicos). O `boot` PHP continua enviando as URLs. */
const SHOW_LEGACY_TICKET_UI = false;

function resolveTechIndexUrl(boot) {
  if (boot?.paths?.indexTecnico) return boot.paths.indexTecnico;
  const w = boot?.webroot;
  if (w) return `${String(w).replace(/\/$/, '')}/tickets`;
  return null;
}

function PapelBadge({ papel }) {
  const isTech = papel === 'tecnico';
  return (
    <span
      className={`inline-flex rounded border px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide ${
        isTech
          ? 'border-[var(--pgm-badge-teal-ring)] bg-[var(--pgm-badge-teal-bg)] text-[var(--pgm-badge-teal-text)]'
          : 'border-[var(--pgm-border-subtle)] bg-[var(--pgm-bg-surface)] text-[var(--pgm-text-secondary)]'
      }`}
    >
      {isTech ? 'Suporte' : 'Cliente'}
    </span>
  );
}

export default function TechTicketEdit({ boot }) {
  const id = boot?.ticketId;
  const embedded = Boolean(boot);
  const canServicedeskTabs = Boolean(boot?.paths?.apiServicedeskData);
  const mainTabKeys = useMemo(
    () =>
      canServicedeskTabs
        ? [
            'atendimento',
            'chat',
            'historico',
            'horas',
            'ativos',
            'pecas',
            'laudos',
            'financeiro',
            'contrato',
            'alertas',
          ]
        : ['atendimento', 'chat'],
    [canServicedeskTabs],
  );
  const [ticket, setTicket] = useState(null);
  const [comentarios, setComentarios] = useState([]);
  const [texto, setTexto] = useState('');
  const [desc, setDesc] = useState('');
  const [relatorioAtendimento, setRelatorioAtendimento] = useState('');
  const [salvando, setSalvando] = useState(false);
  const [salvandoRelatorio, setSalvandoRelatorio] = useState(false);
  const [erro, setErro] = useState(null);
  const [timelineEvents, setTimelineEvents] = useState([]);
  const [mainSdTab, setMainSdTab] = useState(() =>
    typeof window === 'undefined'
      ? 'atendimento'
      : normalizeSdTab((window.location.hash || '').replace(/^#/, ''), canServicedeskTabs),
  );
  /** Incrementado a cada `onSnapshot` do timer — evita que um GET /api-view tardio sobrescreva sessão ativa. */
  const horasTecnicasMutationsRef = useRef(0);
  const comentarioEmProgressoRef = useRef(false);

  useEffect(() => {
    const h = () =>
      setMainSdTab(normalizeSdTab((window.location.hash || '').replace(/^#/, ''), canServicedeskTabs));
    window.addEventListener('hashchange', h);
    return () => window.removeEventListener('hashchange', h);
  }, [canServicedeskTabs]);

  useEffect(() => {
    setMainSdTab((prev) => normalizeSdTab(prev, canServicedeskTabs));
  }, [canServicedeskTabs]);

  useEffect(() => {
    let c = false;
    horasTecnicasMutationsRef.current = 0;
    const ticketIdNum = id != null ? Number(id) : NaN;
    const mutationsAtFetchStart = horasTecnicasMutationsRef.current;
    (async () => {
      const res = await fetchTicketDetail(id);
      if (c) return;
      if (!res.ok) {
        setErro(res.error || 'Ticket não encontrado');
        return;
      }
      setTicket((prev) => {
        const data = res.data;
        const dataId = data?.id != null ? Number(data.id) : NaN;
        if (!Number.isFinite(ticketIdNum) || dataId !== ticketIdNum) {
          return prev ?? data;
        }
        const mergeStaleTimer =
          horasTecnicasMutationsRef.current > mutationsAtFetchStart &&
          prev &&
          Number(prev.id) === ticketIdNum &&
          prev.horasTecnicas?.sessao?.horaInicio &&
          !data.horasTecnicas?.sessao?.horaInicio;
        if (mergeStaleTimer) {
          return {
            ...data,
            horasTecnicas: {
              ...data.horasTecnicas,
              sessao: prev.horasTecnicas.sessao,
              serverUnix: data.horasTecnicas?.serverUnix ?? prev.horasTecnicas.serverUnix,
            },
          };
        }
        return data;
      });
      setComentarios(res.data.comentarios || []);
      setDesc(res.data.descricao || '');
      setRelatorioAtendimento(res.data.descricaoAtendimento || '');
      fetchTicketTimeline(id).then((tr) => {
        if (tr.ok) setTimelineEvents(tr.events || []);
      });
    })();
    return () => {
      c = true;
    };
  }, [id]);

  const { socketRef, socketOn } = useTicketCommentsSocket(id, setComentarios);
  useTicketCommentsPoll(id, setComentarios, setTicket, TICKET_COMMENTS_POLL_MS, socketOn);
  useTicketTimelinePoll(id, setTimelineEvents);

  const { listRef, onListScroll, pinToBottom } = useConversationScrollToBottom(comentarios);

  /** Total em minutos para o rótulo da guia «Horas»: API (Horas cadastradas) ou soma dos worklogs na timeline. */
  const horasTabBadgeMinutos = useMemo(() => {
    const worklogs = (timelineEvents || []).filter((ev) => (ev.type || '').toLowerCase() === 'worklog');
    const mr = ticket?.horasTecnicas?.minutosRegistrados;
    if (mr != null && Number(mr) > 0) return Number(mr);
    const secs = worklogs.reduce((s, ev) => {
      const sec =
        ev.secondsSpent != null && ev.secondsSpent !== ''
          ? ev.secondsSpent
          : ev.seconds_spent != null && ev.seconds_spent !== ''
            ? ev.seconds_spent
            : 0;
      return s + Math.max(0, Number(sec) || 0);
    }, 0);
    return Math.ceil(secs / 60);
  }, [ticket?.horasTecnicas?.minutosRegistrados, timelineEvents]);

  async function handleComentario(e) {
    e.preventDefault();
    const t = texto.trim();
    if (!t || !ticket) return;
    if (comentarioEmProgressoRef.current) return;
    pinToBottom();
    const b = getBoot();
    const papel = (b?.role ?? 1) === 0 ? 'tecnico' : 'cliente';
    const nome = (b?.userName || 'Eu').trim();
    const uid = b?.userId != null ? Number(b.userId) : undefined;
    const tmpId = `pending-${Date.now()}`;
    const optimistic = {
      id: tmpId,
      idautor: uid,
      autor: nome,
      papel,
      texto: t,
      quando: formatCommentPostTimestamp(),
    };
    setComentarios((prev) => [...prev, optimistic]);
    setTexto('');
    comentarioEmProgressoRef.current = true;
    setErro(null);
    try {
      const res = await postComentario(ticket.id, t);
      if (res.ok) {
        const saved = {
          ...res.data,
          idautor: res.data.idautor != null ? Number(res.data.idautor) : uid,
        };
        setComentarios((prev) => finalizeOptimisticComment(prev, tmpId, saved));
        try {
          socketRef.current?.emit('ticket_comment_relay', {
            ticketId: Number(ticket.id),
            comment: saved,
          });
        } catch {
          /* ignore */
        }
        const tr = await fetchTicketTimeline(ticket.id);
        if (tr.ok) setTimelineEvents(tr.events || []);
      } else {
        setComentarios((prev) => prev.filter((c) => c.id !== tmpId));
        setErro(res.error || 'Não foi possível enviar o comentário. Tente novamente.');
        setTexto(t);
      }
    } finally {
      comentarioEmProgressoRef.current = false;
    }
  }

  async function handleSalvarDescricao(e) {
    e.preventDefault();
    if (!ticket?.flags?.canEditDescricao) return;
    setSalvando(true);
    setErro(null);
    const res = await saveTicketSolicitacao(ticket.id, desc);
    setSalvando(false);
    if (res.ok) {
      setErro(null);
    } else {
      setErro(res.error || 'Falha ao salvar.');
    }
  }

  async function handleSalvarRelatorioAtendimento(e) {
    e.preventDefault();
    if (!ticket?.flags?.canEditDescricaoAtendimento) return;
    setSalvandoRelatorio(true);
    setErro(null);
    const res = await saveTicketDescricaoAtendimento(ticket.id, relatorioAtendimento);
    setSalvandoRelatorio(false);
    if (res.ok) {
      setTicket((prev) => (prev ? { ...prev, descricaoAtendimento: relatorioAtendimento } : prev));
      setErro(null);
    } else {
      setErro(res.error || 'Falha ao salvar o relatório. Confira se o banco tem a coluna descricao_atendimento (SQL em config/schema).');
    }
  }

  if (erro && !ticket) {
    return (
      <div className={embedded ? 'py-8 text-center' : 'min-h-screen bg-[var(--pgm-bg-base,#0c0f14)] px-4 py-12 text-center text-[var(--pgm-text,#e8eaed)]'}>
        <p className="text-[var(--pgm-badge-red-text,#ff9492)]">{erro}</p>
        {boot?.paths?.indexTecnico && (
          <a href={boot.paths.indexTecnico} className="mt-4 inline-block text-[var(--pgm-accent,#5cdbc0)] underline">
            Voltar à listagem
          </a>
        )}
      </div>
    );
  }

  if (!ticket) {
    return (
      <div className={embedded ? 'py-12 text-center text-[var(--pgm-text-muted,#9aa0a8)]' : 'min-h-screen bg-[var(--pgm-bg-base,#0c0f14)] px-4 py-12 text-center text-[var(--pgm-text-muted,#9aa0a8)]'}>
        Carregando ticket…
      </div>
    );
  }

  const statusLine = stripHtml(ticket.status);
  const techListUrl = resolveTechIndexUrl(boot);

  function goSdTab(t) {
    setMainSdTab(t);
    if (typeof window !== 'undefined') {
      window.location.hash = t;
    }
  }

  const tabLabels = {
    atendimento: 'Atendimento',
    chat: 'Chat com Cliente',
    historico: 'Histórico',
    horas: 'Horas',
    ativos: 'Ativos',
    pecas: 'Peças / Serviços',
    laudos: 'Laudos',
    financeiro: 'Financeiro',
    contrato: 'Contrato',
    alertas: 'Alertas',
  };

  function SdTabIcon({ tabId }) {
    const common = { width: 14, height: 14, viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', strokeWidth: 2, className: 'shrink-0 opacity-90' };
    switch (tabId) {
      case 'atendimento':
        return (
          <svg {...common}>
            <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" strokeLinecap="round" strokeLinejoin="round" />
          </svg>
        );
      case 'chat':
        return (
          <svg {...common}>
            <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" strokeLinecap="round" strokeLinejoin="round" />
          </svg>
        );
      case 'historico':
        return <svg {...common}><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" strokeLinecap="round" strokeLinejoin="round" /></svg>;
      case 'horas':
        return (
          <svg {...common}>
            <path d="M12 8v5l3 3M12 3a9 9 0 100 18 9 9 0 000-18z" strokeLinecap="round" strokeLinejoin="round" />
          </svg>
        );
      case 'ativos':
        return <svg {...common}><path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" strokeLinecap="round" strokeLinejoin="round" /></svg>;
      case 'pecas':
        return <svg {...common}><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" strokeLinecap="round" strokeLinejoin="round" /></svg>;
      case 'laudos':
        return <svg {...common}><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" strokeLinecap="round" strokeLinejoin="round" /></svg>;
      case 'financeiro':
        return <svg {...common}><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" strokeLinecap="round" strokeLinejoin="round" /></svg>;
      case 'contrato':
        return <svg {...common}><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" strokeLinecap="round" strokeLinejoin="round" /></svg>;
      case 'alertas':
        return <svg {...common}><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" strokeLinecap="round" strokeLinejoin="round" /></svg>;
      default:
        return null;
    }
  }

  const chatTabCount = comentarios.length;
  const sdTabBar = (
    <div className="mb-2 flex flex-wrap gap-1 border-b border-[var(--pgm-border-subtle)] px-1 pb-2">
      {mainTabKeys.map((t) => (
        <button
          key={t}
          type="button"
          onClick={() => goSdTab(t)}
          className={`inline-flex items-center gap-1.5 rounded-t-md px-2.5 py-1.5 text-[0.7rem] font-semibold sm:text-xs ${
            mainSdTab === t
              ? 'bg-[#0056b3] text-white'
              : 'bg-[var(--pgm-bg-elevated)] text-[var(--pgm-text-muted)] hover:text-[var(--pgm-text)]'
          }`}
        >
          <SdTabIcon tabId={t} />
          {tabLabels[t]}
          {t === 'chat' && chatTabCount > 0 ? (
            <span className="ml-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-[#1d9e75] px-1.5 text-[0.65rem] font-bold text-white tabular-nums">
              {chatTabCount > 99 ? '99+' : chatTabCount}
            </span>
          ) : null}
          {t === 'horas' && horasTabBadgeMinutos > 0 ? (
            <span className="ml-1 tabular-nums opacity-90">({minutosHumanosCurto(horasTabBadgeMinutos)})</span>
          ) : null}
        </button>
      ))}
    </div>
  );

  const headerActions = (
    <div className="flex flex-shrink-0 flex-wrap gap-2">
      {techListUrl && (
        <a
          href={techListUrl || ticket.urls?.indexTecnico}
          className="inline-flex items-center gap-1.5 rounded-lg border border-[var(--pgm-border,#3d4554)] bg-transparent px-3 py-1.5 text-xs font-medium text-[var(--pgm-text,#e8eaed)] transition hover:bg-[var(--pgm-bg-overlay,#2a3140)] hover:border-[var(--pgm-border-strong,#4f5869)] sm:text-[0.8125rem]"
        >
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
          Voltar a Fila
        </a>
      )}
      {SHOW_LEGACY_TICKET_UI && boot?.classicEditUrl && (
        <a
          href={boot.classicEditUrl}
          className="inline-flex items-center gap-1.5 rounded-lg border border-[var(--pgm-border,#3d4554)] bg-transparent px-3 py-1.5 text-xs font-medium text-[var(--pgm-text,#e8eaed)] transition hover:bg-[var(--pgm-bg-overlay,#2a3140)] sm:text-[0.8125rem]"
        >
          Clássico (timer, anexos)
        </a>
      )}
      {boot?.paths?.apiPdfTicketOs && id ? (
        <a
          href={`${String(boot.paths.apiPdfTicketOs).replace(/\/$/, '')}/${id}`}
          target="_blank"
          rel="noreferrer"
          className="inline-flex items-center gap-1.5 rounded-lg border border-[#0056b3]/50 bg-[#0056b3]/15 px-3 py-1.5 text-xs font-medium text-[#7eb8ff] transition hover:bg-[#0056b3]/25 sm:text-[0.8125rem]"
        >
          PDF OS
        </a>
      ) : null}
      {boot?.paths?.apiPdfLaudo && id ? (
        <a
          href={`${String(boot.paths.apiPdfLaudo).replace(/\/$/, '')}/${id}`}
          target="_blank"
          rel="noreferrer"
          className="inline-flex items-center gap-1.5 rounded-lg border border-[#0056b3]/50 bg-[#0056b3]/15 px-3 py-1.5 text-xs font-medium text-[#7eb8ff] transition hover:bg-[#0056b3]/25 sm:text-[0.8125rem]"
        >
          PDF Laudo
        </a>
      ) : null}
      {ticket.urls?.imprimir && (
        <a
          href={ticket.urls.imprimir}
          target="_blank"
          rel="noreferrer"
          className="inline-flex items-center gap-1.5 rounded-lg border border-[var(--pgm-border,#3d4554)] bg-transparent px-3 py-1.5 text-xs font-medium text-[var(--pgm-text,#e8eaed)] transition hover:bg-[var(--pgm-bg-overlay,#2a3140)] hover:border-[var(--pgm-border-strong,#4f5869)] sm:text-[0.8125rem]"
        >
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"><path d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m0 0a48.159 48.159 0 0110.5 0m-10.5 0V5.25A2.25 2.25 0 019.5 3h5a2.25 2.25 0 012.25 2.25v3.231" /></svg>
          Imprimir
        </a>
      )}
      {ticket.flags?.canCancel && ticket.urls?.cancelar && (
        <a
          href={ticket.urls.cancelar}
          className="inline-flex items-center gap-1.5 rounded-lg border border-[var(--pgm-badge-red-ring,rgba(248,81,73,0.25))] bg-transparent px-3 py-1.5 text-xs font-medium text-[var(--pgm-badge-red-text,#ff9492)] transition hover:bg-[var(--pgm-badge-red-bg)] hover:border-[rgba(248,81,73,0.45)] sm:text-[0.8125rem]"
        >
          Cancelar Ticket
        </a>
      )}
    </div>
  );

  const header = embedded ? (
    <div className="relative z-20 mb-4 flex shrink-0 flex-wrap items-start justify-between gap-3 border-b border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-[var(--pgm-bg-elevated,#222834)] px-4 pb-3 pt-3">
      <div className="min-w-0">
        {techListUrl ? (
          <a href={techListUrl} className="inline-flex items-center gap-1 text-[0.8125rem] font-medium text-[var(--pgm-accent,#5cdbc0)] transition hover:text-white hover:underline">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
            Tickets
          </a>
        ) : null}
        <h1 className="mt-1 font-mono text-xl font-bold text-[var(--pgm-text,#e8eaed)]">Ticket #{ticket.id}</h1>
        <p className="flex items-center gap-2 text-[0.8125rem] text-[var(--pgm-text-muted,#9aa0a8)]">
          {stripHtml(ticket.cliente)}
          <span
            className={`inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold ${badgeClass(
              statusType(statusLine),
              embedded,
              Boolean(boot?.servicedesk)
            )}`}
          >
            {statusLine}
          </span>
        </p>
      </div>
      {headerActions}
    </div>
  ) : (
    <div className="border-b border-[var(--pgm-glass-border,rgba(255,255,255,0.08))] bg-[var(--pgm-glass-bg,rgba(26,31,40,0.72))] backdrop-blur-[var(--pgm-glass-blur,12px)]">
      <div className="mx-auto flex max-w-6xl flex-wrap items-start justify-between gap-3 px-4 py-4 sm:px-6">
        <div>
          <h1 className="font-mono text-[1.35rem] font-bold text-[var(--pgm-text,#e8eaed)]">Ticket #{ticket.id}</h1>
          <p className="flex flex-wrap items-center gap-2 text-[0.8125rem] text-[var(--pgm-text-muted,#9aa0a8)]">
            <span>{stripHtml(ticket.cliente)}</span>
            <span
              className={`inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-semibold ${badgeClass(
                statusType(statusLine),
                embedded,
                Boolean(boot?.servicedesk)
              )}`}
            >
              {statusLine}
            </span>
          </p>
        </div>
        {headerActions}
      </div>
    </div>
  );

  const alerts = (
    <>
      {erro && <p className="mb-2 rounded-lg border border-[rgba(248,113,113,0.35)] bg-[var(--pgm-badge-red-bg)] px-3 py-2 text-xs text-[var(--pgm-badge-red-text,#ff9492)] sm:text-sm">{erro}</p>}
      {embedded && (
        <p className="mb-3 text-xs text-[var(--pgm-text-muted,#9aa0a8)]">
          {socketOn
            ? 'Chat em tempo real (socket). Comentários e status continuam sincronizados com o servidor.'
            : `Comentários e status do ticket atualizam (~${Math.round(TICKET_COMMENTS_POLL_MS / 1000)}s) com a aba visível.`}{' '}
          Envio de e-mail não bloqueia a resposta (PHP-FPM).
        </p>
      )}
    </>
  );

  const horasTecnicasBlock = (
    <HorasTecnicasTimerPanel
      ticketId={ticket.id}
      horasTecnicas={ticket.horasTecnicas}
      canEditDescricaoAtendimento={Boolean(ticket.flags?.canEditDescricaoAtendimento)}
      onRelatorioSaved={(texto) => setRelatorioAtendimento(texto)}
      onSnapshot={(ht) => {
        horasTecnicasMutationsRef.current += 1;
        setTicket((p) => (p ? { ...p, horasTecnicas: ht } : p));
      }}
      onFeedback={(_okMsg, errMsg) => {
        if (errMsg) {
          setErro(errMsg);
        } else {
          setErro(null);
        }
      }}
    />
  );

  const descricaoBlock = (
    <div className="overflow-hidden rounded-xl border border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-gradient-to-b from-[var(--pgm-bg-surface,#1a1f28)] to-[color-mix(in_srgb,var(--pgm-bg-surface,#1a1f28)_97%,rgba(255,255,255,0.03))] shadow-[var(--pgm-shadow-md)]">
      <div className="border-b border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-[var(--pgm-bg-elevated,#222834)] px-4 py-3">
        <h2 className="text-[0.85rem] font-semibold text-[var(--pgm-text,#e8eaed)]">{stripHtml(ticket.assunto)}</h2>
      </div>
      <div className="p-4">
        {ticket.flags?.canEditDescricao ? (
          <form onSubmit={handleSalvarDescricao} className="space-y-2">
            <label className="text-[0.7rem] font-semibold uppercase tracking-[0.06em] text-[var(--pgm-text-muted,#9aa0a8)]">Descrição (admin)</label>
            <textarea
              value={desc}
              onChange={(e) => setDesc(e.target.value)}
              rows={embedded ? 6 : 8}
              className="w-full rounded-md border border-[var(--pgm-border,#3d4554)] bg-[var(--pgm-bg-raised,#141820)] p-2.5 text-[0.875rem] text-[var(--pgm-text,#e8eaed)] outline-none transition placeholder:text-[var(--pgm-text-muted)] focus:border-[var(--pgm-primary)] focus:shadow-[0_0_0_3px_rgba(29,158,117,0.20),var(--pgm-shadow-glow)]"
            />
            <button
              type="submit"
              disabled={salvando}
              className="inline-flex items-center rounded-lg bg-gradient-to-b from-[var(--pgm-primary,#1d9e75)] to-[#168a64] px-3 py-1.5 text-sm font-semibold text-white shadow-[var(--pgm-shadow-sm),inset_0_1px_0_rgba(255,255,255,0.12)] transition hover:-translate-y-px hover:shadow-[var(--pgm-shadow-md)] disabled:opacity-50"
            >
              {salvando ? 'Salvando…' : 'Salvar descrição'}
            </button>
          </form>
        ) : (
          <div className="max-w-none whitespace-pre-wrap text-[0.875rem] leading-relaxed text-[var(--pgm-text-secondary,#c4c9d1)]">{ticket.descricao}</div>
        )}
      </div>
    </div>
  );

  const relatorioAtendimentoBlock =
    ticket.flags?.canEditDescricaoAtendimento ? (
      <div className="overflow-hidden rounded-xl border border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-gradient-to-b from-[var(--pgm-bg-surface,#1a1f28)] to-[color-mix(in_srgb,var(--pgm-bg-surface,#1a1f28)_97%,rgba(255,255,255,0.03))] shadow-[var(--pgm-shadow-md)]">
        <div className="border-b border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-[var(--pgm-bg-elevated,#222834)] px-4 py-3">
          <h2 className="text-[0.85rem] font-semibold text-[var(--pgm-text,#e8eaed)]">Relatório do atendimento</h2>
        </div>
        <div className="p-4">
          <p className="mb-3 text-xs text-[var(--pgm-text-muted,#9aa0a8)]">
            Descreva o que foi feito antes de finalizar o chamado (ex.: reparado pacote Office em modo online, ajuste de
            perfil de e-mail, etc.). O cliente verá este texto na visualização do ticket e na impressão.
          </p>
          <form onSubmit={handleSalvarRelatorioAtendimento} className="space-y-2">
            <textarea
              value={relatorioAtendimento}
              onChange={(e) => setRelatorioAtendimento(e.target.value)}
              rows={embedded ? 4 : 5}
              placeholder="Ex.: Reparado Outlook via reparo online do Office 365; testado envio e recebimento."
              className="w-full rounded-md border border-[var(--pgm-border,#3d4554)] bg-[var(--pgm-bg-raised,#141820)] p-2.5 text-[0.875rem] text-[var(--pgm-text,#e8eaed)] outline-none transition placeholder:text-[var(--pgm-text-muted)] focus:border-[var(--pgm-primary)] focus:shadow-[0_0_0_3px_rgba(29,158,117,0.20),var(--pgm-shadow-glow)]"
            />
            <button
              type="submit"
              disabled={salvandoRelatorio}
              className="inline-flex items-center rounded-lg bg-gradient-to-b from-[var(--pgm-primary,#1d9e75)] to-[#168a64] px-3 py-1.5 text-sm font-semibold text-white shadow-[var(--pgm-shadow-sm),inset_0_1px_0_rgba(255,255,255,0.12)] transition hover:-translate-y-px hover:shadow-[var(--pgm-shadow-md)] disabled:opacity-50"
            >
              {salvandoRelatorio ? 'Salvando…' : 'Salvar relatório do atendimento'}
            </button>
          </form>
        </div>
      </div>
    ) : null;

  const anexosBlock = (
    <TicketAnexosPanel
      ticketId={ticket.id}
      anexos={ticket.anexos}
      onAnexosChange={(next) => setTicket((prev) => (prev ? { ...prev, anexos: next } : prev))}
      disabled={false}
    />
  );

  const comentariosBlock = (
    <div className="flex h-[min(32rem,calc(100dvh-14rem))] min-h-[12rem] flex-col overflow-hidden rounded-xl border border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-gradient-to-b from-[var(--pgm-bg-surface,#1a1f28)] to-[color-mix(in_srgb,var(--pgm-bg-surface,#1a1f28)_97%,rgba(255,255,255,0.03))] shadow-[var(--pgm-shadow-md)] [contain:layout] sm:h-[min(34rem,calc(100dvh-15rem))]">
      <div className="shrink-0 border-b border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-[var(--pgm-bg-elevated,#222834)] px-3 py-2.5 sm:px-4">
        <p className="text-[0.65rem] leading-snug text-[var(--pgm-text-muted,#9aa0a8)]">
          Mensagens com o cliente e a equipa. Os lançamentos de tempo estão no separador «Horas»; o histórico completo, em
          «Histórico».
        </p>
      </div>
      <ul
        ref={listRef}
        onScroll={onListScroll}
        className="min-h-0 flex-1 basis-0 space-y-2 overflow-y-auto overflow-x-hidden overscroll-contain p-3"
      >
        {comentarios.length === 0 ? (
          <li className="rounded-lg border border-dashed border-[var(--pgm-border,#3d4554)] px-3 py-6 text-center text-[0.8125rem] text-[var(--pgm-text-muted,#9aa0a8)]">
            Nenhuma mensagem ainda. Escreva abaixo para contactar o cliente ou a equipa.
          </li>
        ) : (
          comentarios.map((c) => {
            return (
              <li
                key={c.id}
                className="rounded-lg border border-[var(--pgm-border-subtle)] bg-white px-3 py-2 text-sm text-[var(--pgm-text)] shadow-sm"
              >
                <div className="mb-1 flex flex-wrap items-center justify-between gap-2 text-xs text-[var(--pgm-text-muted)]">
                  <div className="flex min-w-0 flex-wrap items-center gap-2">
                    <span className="font-semibold text-[var(--pgm-text)]">{c.autor || '—'}</span>
                    <PapelBadge papel={c.papel} />
                  </div>
                  <time className="flex-shrink-0">{c.quando}</time>
                </div>
                <CommentMessage texto={c.texto} />
              </li>
            );
          })
        )}
      </ul>
      <div className="flex shrink-0 gap-2 border-t border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-[var(--pgm-bg-surface,#1a1f28)] p-3">
        <form onSubmit={handleComentario} className="flex flex-1 gap-2">
          <textarea
            value={texto}
            onChange={(e) => setTexto(e.target.value)}
            onKeyDown={(e) => {
              if ((e.ctrlKey || e.metaKey) && e.key === 'Enter' && texto.trim()) {
                e.preventDefault();
                handleComentario(e);
              }
            }}
            rows={2}
            placeholder="Novo comentário… (Ctrl+Enter para enviar)"
            className="min-h-[42px] flex-1 resize-vertical rounded-[var(--pgm-radius-lg,12px)] border border-[var(--pgm-border,#3d4554)] bg-[var(--pgm-bg-raised,#141820)] p-2.5 text-[0.8125rem] leading-relaxed text-[var(--pgm-text,#e8eaed)] outline-none transition placeholder:text-[var(--pgm-text-muted)] focus:border-[var(--pgm-primary)] focus:shadow-[0_0_0_3px_rgba(29,158,117,0.20),var(--pgm-shadow-glow)]"
          />
          <button
            type="submit"
            title="Enviar comentário"
            className="self-end rounded-lg bg-gradient-to-b from-[var(--pgm-primary,#1d9e75)] to-[#168a64] p-2.5 text-white shadow-[var(--pgm-shadow-sm),inset_0_1px_0_rgba(255,255,255,0.12)] transition hover:-translate-y-px hover:shadow-[var(--pgm-shadow-md),0_0_16px_rgba(29,158,117,0.25)] active:translate-y-0"
          >
            <svg className="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" /></svg>
          </button>
        </form>
      </div>
    </div>
  );

  const atendimentoGrid = (
    <div className="grid min-h-0 gap-4 lg:grid-cols-12 lg:items-start">
      <div className="min-h-0 min-w-0 space-y-4 lg:col-span-3">
        {horasTecnicasBlock}
        <TicketResumoPanel ticket={ticket} />
      </div>
      <div className="min-h-0 min-w-0 space-y-4 lg:col-span-5">
        {descricaoBlock}
        {relatorioAtendimentoBlock}
        {anexosBlock}
      </div>
      <div className="flex min-h-0 min-w-0 flex-col gap-3 self-start lg:col-span-4">
        <TicketInfoPanel ticket={ticket} />
      </div>
    </div>
  );

  if (embedded) {
    return (
      <div className="tickets-react-edit flex min-h-0 w-full max-w-full flex-1 flex-col overflow-x-hidden text-[var(--pgm-text,#e8eaed)]">
        {header}
        {sdTabBar}
        <div className="min-h-0 flex-1 px-0">
          {alerts}
          {mainSdTab === 'atendimento' ? (
            atendimentoGrid
          ) : mainSdTab === 'chat' ? (
            <div className="min-h-0 min-h-[12rem] flex-1 px-1 sm:px-2">{comentariosBlock}</div>
          ) : canServicedeskTabs ? (
            <div className="min-h-[12rem] px-2">
              <ServiceDeskTabPanels
                ticket={ticket}
                tab={mainSdTab}
                boot={boot}
                timelineEvents={timelineEvents}
              />
            </div>
          ) : (
            atendimentoGrid
          )}
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-[var(--pgm-bg-base,#0c0f14)] text-[var(--pgm-text,#e8eaed)]">
      {header}
      <main className="mx-auto max-w-7xl space-y-4 px-4 py-6 sm:px-6">
        {sdTabBar}
        {alerts}
        {mainSdTab === 'atendimento' ? (
          atendimentoGrid
        ) : mainSdTab === 'chat' ? (
          <div className="min-h-0 min-h-[12rem] px-1 sm:px-2">{comentariosBlock}</div>
        ) : canServicedeskTabs ? (
          <div className="min-h-[12rem]">
            <ServiceDeskTabPanels
              ticket={ticket}
              tab={mainSdTab}
              boot={boot}
              timelineEvents={timelineEvents}
            />
          </div>
        ) : (
          atendimentoGrid
        )}
      </main>
    </div>
  );
}
