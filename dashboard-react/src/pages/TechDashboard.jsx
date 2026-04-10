import {
  useCallback,
  useDeferredValue,
  useEffect,
  useLayoutEffect,
  useMemo,
  useRef,
  useState,
} from 'react';
import { createPortal } from 'react-dom';
import { Link } from 'react-router-dom';
import {
  fetchTicketsTecnico,
  fetchTecnicosParaTransferencia,
  fetchQueuesForTicket,
  postTransferirTicket,
  postStartTicket,
  USE_MOCK,
} from '../lib/api';
import { badgeClass, sortTicketAcoes, statusType } from '../lib/ticketUi';
import { MOCK_SESSION_TECNICO } from '../data/mockData';

const API_ERR_TRANSFER = {
  escalacao_invalida: 'Só é possível transferir para uma fila de nível superior (escalonamento).',
  mesma_fila: 'Selecione uma fila diferente da atual.',
  sem_permissao_transferir_fila: 'Você não está vinculado a esta fila ou seu nível não permite esta ação.',
  destino_sem_vinculo_fila: 'O técnico de destino não está vinculado à fila indicada.',
  destino_nivel_incompativel: 'O nível do técnico de destino não cobre essa fila.',
  motivo_obrigatorio: 'Informe o motivo (mín. 3 caracteres).',
  destino_ou_fila_obrigatorio: 'Indique fila de destino e/ou técnico, conforme a opção escolhida.',
  save_failed:
    'Não foi possível gravar a transferência no servidor. Se persistir, veja o log (apiTransferirTicket: update_ticket, mov_errors ou SQL).',
};

const API_ERR_START = {
  sem_permissao_fila:
    'Não é possível assumir: seu usuário precisa estar vinculado à fila deste ticket e ter nível de suporte compatível (ex.: fila N2 → nível N2). Admin: Portal → Filas / técnicos → Editar no seu usuário (filas + nível).',
};

/** Polling do Service Desk embutido — aba em segundo plano não dispara fetch. */
const SERVICEDESK_POLL_MS = 10_000;

const GROUP_KEYS = {
  todos: 'todos',
  pendente: 'pendentes',
  execucao: 'emandamento',
  resolvido: 'resolvidos',
  fechados: 'fechados',
};

/** Legado pode mandar HTML em situacaoLabel até o servidor atualizar — remove tags. */
function stripHtml(raw) {
  if (raw == null) return '—';
  const s = String(raw);
  const t = s.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
  return t || '—';
}

function statusLabel(row) {
  return stripHtml(row.situacaoLabel || row.status);
}

const ACTION_MENU_WIDTH = 268;

function ActionMenuIcon({ actionKey }) {
  const k = String(actionKey || '').toLowerCase();
  const c = 'h-4 w-4 shrink-0';
  const stroke = { fill: 'none', stroke: 'currentColor', strokeWidth: 1.5, strokeLinecap: 'round', strokeLinejoin: 'round' };
  switch (k) {
    case 'iniciar':
      return (
        <svg className={c} viewBox="0 0 24 24" aria-hidden {...stroke}>
          <path d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z" />
        </svg>
      );
    case 'pendente':
      return (
        <svg className={c} viewBox="0 0 24 24" aria-hidden {...stroke}>
          <path d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      );
    case 'emandamento':
    case 'execucao':
      return (
        <svg className={c} viewBox="0 0 24 24" aria-hidden {...stroke}>
          <path d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75v-6z" />
        </svg>
      );
    case 'resolvido':
      return (
        <svg className={c} viewBox="0 0 24 24" aria-hidden {...stroke}>
          <path d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      );
    case 'transferir':
      return (
        <svg className={c} viewBox="0 0 24 24" aria-hidden {...stroke}>
          <path d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
        </svg>
      );
    case 'cancelar':
      return (
        <svg className={c} viewBox="0 0 24 24" aria-hidden {...stroke}>
          <path d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
        </svg>
      );
    case 'imprimir':
      return (
        <svg className={c} viewBox="0 0 24 24" aria-hidden {...stroke}>
          <path d="M6.72 13.829v-.63A1.125 1.125 0 017.848 12h11.304a1.125 1.125 0 011.128 1.198v.63m-3.75 0v3.375c0 .621-.504 1.125-1.125 1.125H9.375c-.621 0-1.125-.504-1.125-1.125v-3.375m0 0h-.375A1.125 1.125 0 018.25 15.75h7.5c.621 0 1.125.504 1.125 1.125v.375m-12 0h.008v.008H6v-.008zm11.25 0h.008v.008H17.25v-.008z" />
        </svg>
      );
    default:
      return (
        <svg className={c} viewBox="0 0 24 24" aria-hidden {...stroke}>
          <path d="M8.25 4.5l7.5 7.5-7.5 7.5" />
        </svg>
      );
  }
}

function actionItemTone(key) {
  const k = String(key || '').toLowerCase();
  if (k === 'cancelar') return 'danger';
  if (k === 'imprimir') return 'muted';
  return 'default';
}

/** Menu por linha: portal + posição fixa (não é cortado pelo overflow da tabela), visual alinhado ao Service Desk. */
function TicketActionsMenu({
  ticket,
  acoes,
  openTransfer,
  handleStartAtendimento,
  startBusyId,
}) {
  const acoesOrd = sortTicketAcoes(acoes || []);
  const [open, setOpen] = useState(false);
  const [pos, setPos] = useState({ top: 0, left: 0 });
  const btnRef = useRef(null);
  const menuRef = useRef(null);

  const updatePosition = useCallback(() => {
    const el = btnRef.current;
    if (!el) return;
    const r = el.getBoundingClientRect();
    const pad = 8;
    let left = r.right - ACTION_MENU_WIDTH;
    left = Math.max(pad, Math.min(left, window.innerWidth - ACTION_MENU_WIDTH - pad));
    const estH = Math.min(360, 56 + acoesOrd.length * 48);
    let top = r.bottom + 6;
    if (top + estH > window.innerHeight - pad) {
      top = Math.max(pad, r.top - estH - 6);
    }
    setPos({ top, left });
  }, [acoesOrd.length]);

  useLayoutEffect(() => {
    if (!open) return;
    updatePosition();
  }, [open, updatePosition]);

  useEffect(() => {
    if (!open) return undefined;
    const onDoc = (e) => {
      const t = e.target;
      if (btnRef.current?.contains(t) || menuRef.current?.contains(t)) return;
      setOpen(false);
    };
    const onKey = (e) => {
      if (e.key === 'Escape') setOpen(false);
    };
    const onResize = () => updatePosition();
    const onScroll = () => setOpen(false);
    document.addEventListener('mousedown', onDoc);
    document.addEventListener('keydown', onKey);
    window.addEventListener('resize', onResize);
    window.addEventListener('scroll', onScroll, true);
    return () => {
      document.removeEventListener('mousedown', onDoc);
      document.removeEventListener('keydown', onKey);
      window.removeEventListener('resize', onResize);
      window.removeEventListener('scroll', onScroll, true);
    };
  }, [open, updatePosition]);

  if (acoesOrd.length === 0) return <span className="text-slate-400">—</span>;

  const toneCls = {
    default:
      'text-slate-700 hover:bg-emerald-50 hover:text-emerald-900 focus-visible:bg-emerald-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[var(--pgm-primary)]/45 dark:text-[var(--pgm-text)] dark:hover:bg-[var(--pgm-primary-muted)] dark:hover:text-[var(--pgm-text)] dark:focus-visible:bg-[var(--pgm-primary-muted)] dark:focus-visible:ring-[var(--pgm-primary)]/40',
    muted:
      'text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-slate-300 dark:text-[var(--pgm-text-muted)] dark:hover:bg-[var(--pgm-bg-elevated)] dark:hover:text-[var(--pgm-text)] dark:focus-visible:ring-[var(--pgm-border)]',
    danger:
      'text-slate-700 hover:bg-red-50 hover:text-red-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-red-300/70 dark:text-red-200 dark:hover:bg-red-950/40 dark:hover:text-red-100',
  };

  const rowBase =
    'flex w-full items-center gap-3 border-0 bg-transparent px-3 py-2.5 text-left text-sm font-medium transition disabled:cursor-not-allowed disabled:opacity-45';

  const menuPanel = open ? (
    <div
      ref={menuRef}
      role="menu"
      aria-label={`Ações do ticket ${ticket.id}`}
      className="fixed z-[60] w-[268px] overflow-hidden rounded-xl border border-slate-200/90 bg-white py-1 shadow-[0_16px_48px_-12px_rgba(15,23,42,0.28)] ring-1 ring-slate-900/5 dark:border-[var(--pgm-border)] dark:bg-[var(--pgm-bg-surface)] dark:shadow-[0_20px_50px_-12px_rgba(0,0,0,0.65)] dark:ring-white/10"
      style={{ top: pos.top, left: pos.left }}
    >
      <div className="border-b border-slate-100 bg-gradient-to-r from-emerald-50/90 to-teal-50/70 px-3 py-2 dark:border-[var(--pgm-border)] dark:bg-[linear-gradient(135deg,rgba(29,158,117,0.28)_0%,rgba(19,113,90,0.72)_100%)]">
        <p className="text-[10px] font-semibold uppercase tracking-wider text-teal-800/90 dark:text-white/90">
          Chamado
        </p>
        <p className="text-sm font-bold tabular-nums text-slate-900 dark:text-[var(--pgm-text)]">#{ticket.id}</p>
      </div>
      <ul className="max-h-[min(320px,70vh)] list-none overflow-y-auto py-1">
        {acoesOrd.map((a) => {
          const tone = actionItemTone(a.key);
          const iconWrap =
            tone === 'danger'
              ? 'text-red-500/90'
              : tone === 'muted'
                ? 'text-slate-400'
                : 'text-[var(--pgm-primary)]';
          if (a.behavior === 'reactTransfer') {
            return (
              <li key={`${a.key}-${a.label}`} role="none">
                <button
                  type="button"
                  role="menuitem"
                  className={`${rowBase} ${toneCls[tone]}`}
                  onClick={() => {
                    setOpen(false);
                    openTransfer(ticket);
                  }}
                >
                  <span className={iconWrap}>
                    <ActionMenuIcon actionKey={a.key} />
                  </span>
                  <span className="min-w-0 flex-1 leading-snug">{a.label}</span>
                </button>
              </li>
            );
          }
          if (a.behavior === 'reactStart') {
            const busy = startBusyId === Number(ticket.id);
            return (
              <li key={`${a.key}-${a.label}`} role="none">
                <button
                  type="button"
                  role="menuitem"
                  className={`${rowBase} ${toneCls.default}`}
                  disabled={busy}
                  onClick={() => {
                    setOpen(false);
                    handleStartAtendimento(ticket);
                  }}
                >
                  <span className="text-[var(--pgm-primary)]">
                    <ActionMenuIcon actionKey={a.key} />
                  </span>
                  <span className="min-w-0 flex-1 leading-snug">{busy ? 'Iniciando…' : a.label}</span>
                </button>
              </li>
            );
          }
          return (
            <li key={`${a.key}-${a.label}`} role="none">
              <a
                role="menuitem"
                href={a.url}
                target={a.target || '_self'}
                rel={a.target === '_blank' ? 'noreferrer' : undefined}
                className={`${rowBase} ${toneCls[tone]} no-underline`}
                onClick={() => setOpen(false)}
              >
                <span className={iconWrap}>
                  <ActionMenuIcon actionKey={a.key} />
                </span>
                <span className="min-w-0 flex-1 leading-snug">{a.label}</span>
              </a>
            </li>
          );
        })}
      </ul>
    </div>
  ) : null;

  return (
    <>
      <div className="flex justify-end">
        <button
          ref={btnRef}
          type="button"
          aria-expanded={open}
          aria-haspopup="menu"
          aria-label={`Abrir menu de ações do ticket ${ticket.id}`}
          className={`inline-flex h-9 shrink-0 items-center gap-2 rounded-lg px-3 text-xs font-semibold shadow-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--pgm-primary)] focus-visible:ring-offset-2 focus-visible:ring-offset-[var(--pgm-bg-page)] ${
            open
              ? 'bg-[var(--pgm-erp-teal-active)] text-white ring-2 ring-[var(--pgm-primary)]/45'
              : 'bg-[var(--pgm-primary)] text-white hover:brightness-110'
          }`}
          onClick={(e) => {
            e.stopPropagation();
            setOpen((v) => !v);
          }}
        >
          <svg className="h-4 w-4 opacity-95" viewBox="0 0 24 24" fill="currentColor" aria-hidden>
            <circle cx="12" cy="5" r="1.75" />
            <circle cx="12" cy="12" r="1.75" />
            <circle cx="12" cy="19" r="1.75" />
          </svg>
          <span>Ações</span>
          <svg
            className={`h-3.5 w-3.5 opacity-90 transition-transform ${open ? 'rotate-180' : ''}`}
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2.2"
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden
          >
            <path d="M6 9l6 6 6-6" />
          </svg>
        </button>
      </div>
      {typeof document !== 'undefined' && menuPanel ? createPortal(menuPanel, document.body) : null}
    </>
  );
}

/** Destaques operacionais na fila (Service Desk / técnico). */
function techRowHighlightClass(ticket) {
  const label = String(ticket.situacaoLabel || ticket.status || '').toLowerCase();
  const closed =
    label.includes('resolvido') || label.includes('fechado') || label.includes('cancelado');
  const semResp =
    !closed &&
    !ticket.idtecnico_responsavel &&
    (String(ticket.tecnicos || '').includes('não atribuído') ||
      ticket.tecnicos === '—' ||
      ticket.tecnicos === '');
  const parts = ['align-middle', 'transition'];
  if (label.includes('aguardando')) {
    parts.push('bg-emerald-50/90', 'dark:bg-[rgba(29,158,117,0.12)]');
  } else if (label.includes('execução') || label.includes('andamento')) {
    parts.push('bg-teal-50/80', 'dark:bg-[rgba(29,158,117,0.09)]');
  }
  if (semResp) {
    parts.push('ring-1', 'ring-inset', 'ring-slate-300/80', 'dark:ring-slate-600/50');
  }
  return parts.join(' ');
}

export default function TechDashboard({ boot }) {
  const embedded = Boolean(boot);
  const [groups, setGroups] = useState(null);
  const [workflow, setWorkflow] = useState(null);
  const [q, setQ] = useState('');
  const [filtroStatus, setFiltroStatus] = useState('ativos');
  const [filaSuporte, setFilaSuporte] = useState('');
  const [nivelAtendimento, setNivelAtendimento] = useState('');
  const [idResponsavel, setIdResponsavel] = useState('');
  const [tecnicosOpcoes, setTecnicosOpcoes] = useState([]);

  const [transferOpen, setTransferOpen] = useState(false);
  const [transferTicket, setTransferTicket] = useState(null);
  const [transferDest, setTransferDest] = useState('');
  const [transferMotivo, setTransferMotivo] = useState('');
  const [transferFila, setTransferFila] = useState('');
  const [transferQueues, setTransferQueues] = useState([]);
  const [transferQueueId, setTransferQueueId] = useState('');
  const [tecnicosModal, setTecnicosModal] = useState([]);
  const [transferSaving, setTransferSaving] = useState(false);
  const [transferErr, setTransferErr] = useState('');
  const [transferQueuesErr, setTransferQueuesErr] = useState('');
  const [startBusyId, setStartBusyId] = useState(null);
  const [transferOkHint, setTransferOkHint] = useState('');
  const [loadError, setLoadError] = useState(null);
  /** sem = só fila / sem responsável; com = atribuir técnico */
  const [transferAssignMode, setTransferAssignMode] = useState('sem');

  const loadFilters = useMemo(
    () => ({
      filaSuporte: filaSuporte || undefined,
      nivelAtendimento: nivelAtendimento || undefined,
      idResponsavel: idResponsavel || undefined,
    }),
    [filaSuporte, nivelAtendimento, idResponsavel]
  );

  const reload = useCallback(async () => {
    const res = await fetchTicketsTecnico(loadFilters);
    if (res.ok && res.groups) {
      setLoadError(null);
      setGroups(res.groups);
      setWorkflow(res.workflow ?? { enabled: false, filas: [] });
      return;
    }
    const hint = res.httpStatus ? ` (HTTP ${res.httpStatus})` : '';
    setLoadError(
      res.error === 'session_empresa_invalida'
        ? 'Sessão sem empresa válida. Troque de empresa ou faça login novamente.'
        : res.error === 'api_index_failed'
          ? `Falha ao montar a lista de tickets no servidor.${hint} Veja o log da aplicação (apiIndex).`
          : `Não foi possível carregar os tickets.${hint} ${res.error || ''}`.trim(),
    );
  }, [loadFilters]);

  useEffect(() => {
    let cancel = false;
    (async () => {
      const res = await fetchTicketsTecnico(loadFilters);
      if (cancel) return;
      if (res.ok && res.groups) {
        setLoadError(null);
        setGroups(res.groups);
        setWorkflow(res.workflow ?? { enabled: false, filas: [] });
      } else {
        const hint = res.httpStatus ? ` (HTTP ${res.httpStatus})` : '';
        setLoadError(
          res.error === 'session_empresa_invalida'
            ? 'Sessão sem empresa válida. Troque de empresa ou faça login novamente.'
            : res.error === 'api_index_failed'
              ? `Falha ao montar a lista de tickets no servidor.${hint}`
              : `Não foi possível carregar os tickets.${hint} ${res.error || ''}`.trim(),
        );
      }
    })();
    return () => {
      cancel = true;
    };
  }, [loadFilters]);

  useEffect(() => {
    let cancel = false;
    (async () => {
      const r = await fetchTecnicosParaTransferencia();
      if (cancel || !r.ok) return;
      setTecnicosOpcoes(r.tecnicos || []);
    })();
    return () => {
      cancel = true;
    };
  }, []);

  useEffect(() => {
    if (!embedded || !boot?.servicedesk) return undefined;
    const id = window.setInterval(() => {
      if (document.visibilityState !== 'visible') return;
      reload();
    }, SERVICEDESK_POLL_MS);
    return () => window.clearInterval(id);
  }, [embedded, boot?.servicedesk, reload]);

  const wfEnabled = Boolean(workflow?.enabled);
  const filasMeta = workflow?.filas || [];
  const queuesRelacional = Boolean(workflow?.queuesRelacional);

  useEffect(() => {
    if (!transferOpen || !queuesRelacional) return undefined;
    let cancel = false;
    (async () => {
      const q = Number(transferQueueId) || 0;
      const r = await fetchTecnicosParaTransferencia(q || undefined);
      if (cancel || !r.ok) return;
      setTecnicosModal(r.tecnicos || []);
    })();
    return () => {
      cancel = true;
    };
  }, [transferOpen, transferQueueId, queuesRelacional]);

  const deferredQ = useDeferredValue(q);

  const rows = useMemo(() => {
    if (!groups) return [];
    let base;
    if (filtroStatus === 'ativos') {
      const a = groups.pendentes || [];
      const b = groups.emandamento || [];
      const seen = new Set();
      base = [...a, ...b].filter((t) => {
        if (seen.has(t.id)) return false;
        seen.add(t.id);
        return true;
      });
      base.sort((x, y) => Number(y.id) - Number(x.id));
    } else {
      const key = GROUP_KEYS[filtroStatus] || 'todos';
      base = groups[key] || [];
    }
    const qq = deferredQ.trim().toLowerCase();
    if (!qq) return base;
    return base.filter((t) => {
      const id = String(t.id);
      const cliente = String(t.cliente || '').toLowerCase();
      const assunto = String(t.assunto || '').toLowerCase();
      const tec = String(t.tecnicos || '').toLowerCase();
      const fila = String(t.filaLabel || '').toLowerCase();
      return (
        id.includes(qq) ||
        cliente.includes(qq) ||
        assunto.includes(qq) ||
        tec.includes(qq) ||
        fila.includes(qq)
      );
    });
  }, [groups, filtroStatus, deferredQ]);

  const totalTodos = groups?.todos?.length ?? 0;
  const hoje = new Date().toLocaleDateString('pt-BR');

  const addTicket = boot?.paths?.addTicket;

  const openTransfer = async (ticket) => {
    setTransferTicket(ticket);
    setTransferDest('');
    setTransferMotivo('');
    setTransferFila(wfEnabled ? ticket.filaSuporte || 'n1' : '');
    setTransferErr('');
    setTransferQueuesErr('');
    setTransferQueues([]);
    setTransferQueueId('');
    setTransferAssignMode('sem');
    if (queuesRelacional) {
      const useEscalation = Boolean(workflow?.supportLevelsEnabled);
      const rq = await fetchQueuesForTicket(ticket.id, { escalationOnly: useEscalation });
      if (!rq.ok) {
        setTransferQueuesErr(rq.error || 'Não foi possível carregar as filas.');
        setTransferQueues([]);
        setTransferQueueId('');
      } else {
        const list = rq.queues || [];
        setTransferQueues(list);
        setTransferQueuesErr('');
        if (useEscalation && list.length === 0) {
          setTransferQueuesErr('Não há filas de escalonamento disponíveis acima do nível atual.');
        }
        const pref =
          ticket.filaQueueId && list.some((x) => Number(x.id) === Number(ticket.filaQueueId))
            ? String(ticket.filaQueueId)
            : list[0]
              ? String(list[0].id)
              : '';
        setTransferQueueId(pref);
      }
    }
    setTransferOpen(true);
  };

  const submitTransfer = async () => {
    if (!transferTicket) return;
    const id = Number(transferTicket.id);
    const dest = Number(transferDest);
    if (transferMotivo.trim().length < 3) {
      setTransferErr('Informe o motivo da transferência (mín. 3 caracteres).');
      return;
    }
    setTransferSaving(true);
    setTransferErr('');
    const qid = Number(transferQueueId) || 0;
    let payload;
    if (queuesRelacional) {
      if (!qid) {
        setTransferSaving(false);
        setTransferErr('Selecione a fila de destino.');
        return;
      }
      if (transferAssignMode === 'com') {
        if (!dest) {
          setTransferSaving(false);
          setTransferErr('Selecione o técnico ou marque “somente fila (sem responsável)”.');
          return;
        }
        payload = { iduser_destino: dest, queue_id: qid, motivo: transferMotivo.trim() };
      } else {
        setTransferDest('');
        payload = { queue_id: qid, motivo: transferMotivo.trim() };
      }
    } else {
      if (wfEnabled) {
        const fc = transferFila || 'n1';
        const cur = transferTicket.filaSuporte || 'n1';
        if (transferAssignMode === 'sem') {
          if (fc === cur) {
            setTransferSaving(false);
            setTransferErr('Escolha uma fila de destino diferente da atual ou atribua um técnico.');
            return;
          }
          payload = { fila_suporte: fc, motivo: transferMotivo.trim() };
        } else {
          if (!dest) {
            setTransferSaving(false);
            setTransferErr('Selecione o técnico de destino.');
            return;
          }
          payload = {
            iduser_destino: dest,
            motivo: transferMotivo.trim(),
          };
          if (transferFila) {
            payload.fila_suporte = transferFila;
          }
        }
      } else {
        if (!dest) {
          setTransferSaving(false);
          setTransferErr('Selecione o técnico de destino.');
          return;
        }
        payload = {
          iduser_destino: dest,
          motivo: transferMotivo.trim(),
        };
      }
    }
    const r = await postTransferirTicket(id, payload);
    setTransferSaving(false);
    if (!r.ok) {
      const code = r.error;
      setTransferErr(API_ERR_TRANSFER[code] || code || 'Falha ao transferir.');
      return;
    }
    setTransferOpen(false);
    setTransferOkHint('Transferência registrada.');
    window.setTimeout(() => setTransferOkHint(''), 4000);
    await reload();
  };

  const handleStartAtendimento = async (ticket) => {
    const id = Number(ticket.id);
    setStartBusyId(id);
    try {
      const r = await postStartTicket(id);
      if (!r.ok) {
        const code = r.error;
        window.alert(API_ERR_START[code] || code || 'Não foi possível iniciar o atendimento.');
        return;
      }
      setTransferOkHint('Atendimento iniciado.');
      window.setTimeout(() => setTransferOkHint(''), 4000);
      await reload();
    } finally {
      setStartBusyId(null);
    }
  };

  const colCount = wfEnabled ? 10 : 8;

  /** Campos compactos (Service Desk embutido). */
  const sdField =
    'h-9 w-full rounded-lg border border-slate-200 bg-white px-2.5 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-[var(--pgm-primary)] focus:ring-1 focus:ring-[var(--pgm-primary)]/30 dark:border-[var(--pgm-border)] dark:bg-[var(--pgm-bg-elevated)] dark:text-[var(--pgm-text)] dark:placeholder:text-[var(--pgm-text-muted)] dark:focus:border-[var(--pgm-primary)] dark:focus:ring-[var(--pgm-primary)]/35';

  const tableSection = (
    <section
      className={
        embedded
          ? 'overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-[var(--pgm-border)] dark:bg-[var(--pgm-bg-surface)] dark:shadow-[0_4px_24px_rgba(0,0,0,0.35)]'
          : 'rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm dark:border-[var(--pgm-border)] dark:bg-[var(--pgm-bg-surface)]'
      }
    >
      {loadError ? (
        <div className="border-b border-amber-300 bg-amber-50 px-3 py-2.5 text-sm text-amber-950 dark:border-amber-800/60 dark:bg-amber-950/35 dark:text-amber-100">
          <span className="font-semibold">Lista não carregou: </span>
          {loadError}
        </div>
      ) : null}
      {!embedded ? (
        <div className="flex flex-col gap-3 border-b border-slate-100 p-3 dark:border-[var(--pgm-border)] lg:flex-row lg:items-center lg:justify-between">
          <div>
            <h3 className="text-lg font-bold text-slate-900 dark:text-[var(--pgm-text)]">Fila</h3>
            <p className="text-sm text-slate-500 dark:text-[var(--pgm-text-muted)]">
              {totalTodos} ticket(s) na empresa · integração JSON ativa quando embutido no CakePHP
            </p>
          </div>
          <div className="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
            <input
              value={q}
              onChange={(e) => setQ(e.target.value)}
              placeholder="Buscar nº, cliente ou assunto"
              className="h-9 w-full min-w-[180px] rounded-lg border border-slate-200 bg-slate-50 px-2.5 text-sm outline-none placeholder:text-slate-400 focus:border-[var(--pgm-primary)] dark:border-[var(--pgm-border)] dark:bg-[var(--pgm-bg-elevated)] dark:text-[var(--pgm-text)] dark:placeholder:text-[var(--pgm-text-muted)] dark:focus:border-[var(--pgm-primary)] sm:max-w-xs"
            />
            <select
              value={filtroStatus}
              onChange={(e) => setFiltroStatus(e.target.value)}
              className="h-9 rounded-lg border border-slate-200 bg-slate-50 px-2.5 text-sm outline-none focus:border-[var(--pgm-primary)] dark:border-[var(--pgm-border)] dark:bg-[var(--pgm-bg-elevated)] dark:text-[var(--pgm-text)] dark:focus:border-[var(--pgm-primary)]"
            >
              <option value="todos">Todos</option>
              <option value="ativos">Aguardando + Em execução</option>
              <option value="pendente">Aguardando técnico</option>
              <option value="execucao">Em execução</option>
              <option value="resolvido">Resolvidos</option>
              <option value="fechados">Cancelados / fechados</option>
            </select>
          </div>
        </div>
      ) : wfEnabled ? (
        <div className="flex flex-nowrap items-center gap-2 border-b border-slate-100 bg-slate-50/40 px-3 py-2.5 [scrollbar-width:thin] overflow-x-auto dark:border-[var(--pgm-border)] dark:bg-[var(--pgm-bg-elevated)]/50">
          <span
            className="shrink-0 whitespace-nowrap text-xs tabular-nums text-slate-500 dark:text-[var(--pgm-text-muted)]"
            title="Tickets na empresa (todos os status)"
          >
            <span className="font-semibold text-slate-800 dark:text-[var(--pgm-text)]">{totalTodos}</span> na empresa
          </span>
          <input
            id="sd-tech-q"
            value={q}
            onChange={(e) => setQ(e.target.value)}
            placeholder="Buscar nº, cliente ou assunto"
            aria-label="Buscar na lista"
            className={`${sdField} min-w-[9rem] flex-1 basis-[10rem]`}
          />
          <select
            id="sd-tech-status"
            value={filtroStatus}
            onChange={(e) => setFiltroStatus(e.target.value)}
            aria-label="Situação"
            className={`${sdField} w-[12rem] shrink-0 cursor-pointer sm:w-[13rem]`}
          >
            <option value="todos">Todos</option>
            <option value="ativos">Aguardando + Em execução</option>
            <option value="pendente">Aguardando técnico</option>
            <option value="execucao">Em execução</option>
            <option value="resolvido">Resolvidos</option>
            <option value="fechados">Cancelados / fechados</option>
          </select>
          <select
            id="sd-wf-fila"
            value={filaSuporte}
            onChange={(e) => setFilaSuporte(e.target.value)}
            aria-label="Fila de suporte"
            className={`${sdField} w-[10.5rem] shrink-0 cursor-pointer sm:w-[11.5rem]`}
          >
            <option value="">Todas as filas</option>
            {filasMeta.map((f) => (
              <option key={f.code} value={f.code}>
                {f.label}
              </option>
            ))}
          </select>
          <select
            id="sd-wf-nivel"
            value={nivelAtendimento}
            onChange={(e) => setNivelAtendimento(e.target.value)}
            aria-label="Nível de atendimento"
            className={`${sdField} w-[7.25rem] shrink-0 cursor-pointer`}
          >
            <option value="">Todos os níveis</option>
            {[1, 2, 3, 4, 5].map((n) => (
              <option key={n} value={String(n)}>
                Nível {n}
              </option>
            ))}
          </select>
          <select
            id="sd-wf-tec"
            value={idResponsavel}
            onChange={(e) => setIdResponsavel(e.target.value)}
            aria-label="Técnico responsável"
            className={`${sdField} w-[10rem] shrink-0 cursor-pointer sm:w-[11rem]`}
          >
            <option value="">Qualquer técnico</option>
            {tecnicosOpcoes.map((t) => (
              <option key={t.id} value={String(t.id)}>
                {t.name}
              </option>
            ))}
          </select>
        </div>
      ) : (
        <div className="flex flex-nowrap items-center gap-2 border-b border-slate-100 bg-slate-50/40 px-3 py-2.5 [scrollbar-width:thin] overflow-x-auto dark:border-[var(--pgm-border)] dark:bg-[var(--pgm-bg-elevated)]/50">
          <span
            className="shrink-0 whitespace-nowrap text-xs tabular-nums text-slate-500 dark:text-[var(--pgm-text-muted)]"
            title="Tickets na empresa (todos os status)"
          >
            <span className="font-semibold text-slate-800 dark:text-[var(--pgm-text)]">{totalTodos}</span> na empresa
          </span>
          <input
            id="sd-tech-q"
            value={q}
            onChange={(e) => setQ(e.target.value)}
            placeholder="Buscar nº, cliente ou assunto"
            aria-label="Buscar na lista"
            className={`${sdField} min-w-[9rem] flex-1 basis-[10rem]`}
          />
          <select
            id="sd-tech-status"
            value={filtroStatus}
            onChange={(e) => setFiltroStatus(e.target.value)}
            aria-label="Situação"
            className={`${sdField} w-[12rem] shrink-0 cursor-pointer sm:w-[13rem]`}
          >
            <option value="todos">Todos</option>
            <option value="ativos">Aguardando + Em execução</option>
            <option value="pendente">Aguardando técnico</option>
            <option value="execucao">Em execução</option>
            <option value="resolvido">Resolvidos</option>
            <option value="fechados">Cancelados / fechados</option>
          </select>
        </div>
      )}

      {transferOkHint ? (
        <div
          className="border-b border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-900 dark:border-[color:rgba(29,158,117,0.45)] dark:bg-[linear-gradient(135deg,rgba(29,158,117,0.2)_0%,rgba(19,113,90,0.55)_100%)] dark:text-white/95"
          role="status"
          aria-live="polite"
        >
          {transferOkHint}
        </div>
      ) : null}

      {wfEnabled ? (
        !embedded ? (
          <div className="flex flex-col gap-2 border-b border-slate-100 bg-slate-50/40 p-3 dark:border-[var(--pgm-border)] dark:bg-[var(--pgm-bg-elevated)]/40 sm:flex-row sm:flex-wrap sm:items-center">
            <select
              value={filaSuporte}
              onChange={(e) => setFilaSuporte(e.target.value)}
              className="h-9 rounded-lg border border-slate-200 bg-white px-2 text-sm outline-none focus:border-[var(--pgm-primary)] dark:border-[var(--pgm-border)] dark:bg-[var(--pgm-bg-elevated)] dark:text-[var(--pgm-text)] dark:focus:border-[var(--pgm-primary)]"
            >
              <option value="">Todas as filas</option>
              {filasMeta.map((f) => (
                <option key={f.code} value={f.code}>
                  {f.label}
                </option>
              ))}
            </select>
            <select
              value={nivelAtendimento}
              onChange={(e) => setNivelAtendimento(e.target.value)}
              className="h-9 rounded-lg border border-slate-200 bg-white px-2 text-sm outline-none focus:border-[var(--pgm-primary)] dark:border-[var(--pgm-border)] dark:bg-[var(--pgm-bg-elevated)] dark:text-[var(--pgm-text)] dark:focus:border-[var(--pgm-primary)]"
            >
              <option value="">Todos os níveis</option>
              {[1, 2, 3, 4, 5].map((n) => (
                <option key={n} value={String(n)}>
                  Nível {n}
                </option>
              ))}
            </select>
            <select
              value={idResponsavel}
              onChange={(e) => setIdResponsavel(e.target.value)}
              className="h-9 min-w-[10rem] rounded-lg border border-slate-200 bg-white px-2 text-sm outline-none focus:border-[var(--pgm-primary)] dark:border-[var(--pgm-border)] dark:bg-[var(--pgm-bg-elevated)] dark:text-[var(--pgm-text)] dark:focus:border-[var(--pgm-primary)]"
            >
              <option value="">Qualquer técnico</option>
              {tecnicosOpcoes.map((t) => (
                <option key={t.id} value={String(t.id)}>
                  {t.name}
                </option>
              ))}
            </select>
          </div>
        ) : null
      ) : null}

      <div
        className={
          embedded
            ? 'overflow-hidden'
            : 'mt-5 overflow-hidden rounded-2xl border border-slate-200 dark:border-[var(--pgm-border)]'
        }
      >
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-slate-200 text-xs dark:divide-[var(--pgm-border)] sm:text-sm">
            <thead className="bg-slate-50 text-left text-xs text-slate-500 dark:bg-[var(--pgm-bg-elevated)] dark:text-[var(--pgm-text-muted)]">
              <tr>
                <th className="px-2 py-1.5 font-semibold sm:px-3">Ticket</th>
                <th className="max-w-[7rem] px-2 py-1.5 font-semibold sm:px-3">Autor</th>
                <th className="whitespace-nowrap px-2 py-1.5 font-semibold sm:px-3">Data</th>
                <th className="min-w-[8rem] px-2 py-1.5 font-semibold sm:min-w-[10rem] sm:px-3">Assunto</th>
                <th className="whitespace-nowrap px-2 py-1.5 font-semibold sm:px-3">Status</th>
                {wfEnabled ? (
                  <>
                    <th className="max-w-[9rem] px-2 py-1.5 font-semibold sm:px-3">Fila</th>
                    <th className="whitespace-nowrap px-2 py-1.5 font-semibold sm:px-3">Nível</th>
                  </>
                ) : null}
                <th className="max-w-[7rem] px-2 py-1.5 font-semibold sm:px-3">Técnico</th>
                <th className="max-w-[8rem] px-2 py-1.5 font-semibold sm:px-3">Cliente</th>
                <th className="w-[7.25rem] min-w-[7.25rem] px-2 py-1.5 text-right font-semibold sm:px-3">Ações</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 bg-white dark:divide-[var(--pgm-border)] dark:bg-[var(--pgm-bg-surface)]">
              {rows.length === 0 ? (
                <tr>
                  <td
                    colSpan={colCount}
                    className="px-4 py-8 text-center text-slate-500 dark:text-[var(--pgm-text-muted)]"
                  >
                    {loadError
                      ? 'Ajuste o problema indicado acima e atualize a página.'
                      : 'Nenhum ticket neste filtro.'}
                  </td>
                </tr>
              ) : (
                rows.map((ticket) => {
                  const st = statusLabel(ticket);
                  const assuntoLinha = stripHtml(ticket.assunto);
                  return (
                    <tr
                      key={ticket.id}
                      className={`${techRowHighlightClass(ticket)} hover:bg-slate-50/80 dark:hover:bg-[var(--pgm-bg-elevated)]/90`}
                    >
                      <td className="px-2 py-1.5 font-semibold sm:px-3">
                        {ticket.urls?.edit ? (
                          <a
                            className="text-[var(--pgm-primary)] hover:underline dark:text-[var(--pgm-primary)]"
                            href={ticket.urls.edit}
                          >
                            #{ticket.id}
                          </a>
                        ) : (
                          <Link
                            className="text-[var(--pgm-primary)] hover:underline dark:text-[var(--pgm-primary)]"
                            to={`/cliente/ticket/${ticket.id}`}
                          >
                            #{ticket.id}
                          </Link>
                        )}
                      </td>
                      <td className="max-w-[7rem] truncate px-2 py-1.5 sm:px-3" title={ticket.autor || ''}>
                        {ticket.autor || '—'}
                      </td>
                      <td className="whitespace-nowrap px-2 py-1.5 text-slate-600 sm:px-3">
                        {ticket.created || ticket.atualizado || '—'}
                      </td>
                      <td className="max-w-[14rem] px-2 py-1.5 sm:max-w-xs sm:px-3">
                        <div className="truncate font-medium text-slate-800" title={assuntoLinha}>
                          {assuntoLinha}
                        </div>
                        {ticket.solicitacaoPreview ? (
                          <div
                            className="line-clamp-1 text-[11px] leading-tight text-slate-500"
                            title={ticket.solicitacaoPreview}
                          >
                            {ticket.solicitacaoPreview}
                          </div>
                        ) : null}
                      </td>
                      <td className="whitespace-nowrap px-2 py-1.5 sm:px-3">
                        <span
                          className={`inline-flex max-w-[10rem] truncate rounded-full border px-2 py-0.5 text-[10px] font-semibold leading-tight sm:max-w-[12rem] sm:text-xs ${badgeClass(
                            statusType(st)
                          )}`}
                          title={st}
                        >
                          {st}
                        </span>
                      </td>
                      {wfEnabled ? (
                        <>
                          <td
                            className="max-w-[9rem] truncate px-2 py-1.5 text-slate-700 sm:px-3"
                            title={ticket.filaLabel || ''}
                          >
                            <span className="line-clamp-2">{ticket.filaLabel || '—'}</span>
                            {ticket.transferido ? (
                              <span className="mt-0.5 block text-[10px] font-semibold text-amber-700">Transferido</span>
                            ) : null}
                          </td>
                          <td className="whitespace-nowrap px-2 py-1.5 text-slate-600 sm:px-3">
                            {ticket.supportLevelLabel
                              ? ticket.supportLevelLabel
                              : ticket.nivelAtendimento != null
                                ? `N${ticket.nivelAtendimento}`
                                : '—'}
                          </td>
                        </>
                      ) : null}
                      <td className="max-w-[7rem] truncate px-2 py-1.5 text-slate-700 sm:px-3" title={ticket.tecnicos || ''}>
                        {ticket.tecnicos && ticket.tecnicos !== '—' ? ticket.tecnicos : '—'}
                      </td>
                      <td className="max-w-[8rem] truncate px-2 py-1.5 sm:px-3" title={ticket.cliente || ''}>
                        {ticket.cliente || '—'}
                      </td>
                      <td className="px-2 py-1 text-right sm:px-3">
                        <TicketActionsMenu
                          ticket={ticket}
                          acoes={ticket.acoes}
                          openTransfer={openTransfer}
                          handleStartAtendimento={handleStartAtendimento}
                          startBusyId={startBusyId}
                        />
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>
      </div>

      {transferOpen ? (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" role="dialog" aria-modal="true">
          <div className="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-2xl border border-slate-200 bg-white p-5 shadow-xl dark:border-[var(--pgm-border)] dark:bg-[var(--pgm-bg-surface)] dark:shadow-[0_24px_64px_rgba(0,0,0,0.55)]">
            <h4 className="text-lg font-bold text-slate-900 dark:text-[var(--pgm-text)]">
              Transferir ticket #{transferTicket?.id}
            </h4>
            <p className="mt-1 text-sm text-slate-500">
              {queuesRelacional
                ? 'Escolha a fila da mesma empresa. Depois defina se o ticket fica só na fila (sem responsável) ou se já vai para um técnico.'
                : wfEnabled
                  ? 'Escolha se deseja só mudar a fila de suporte (sem trocar o responsável) ou encaminhar a um técnico. O histórico registra motivo e data/hora.'
                  : 'O histórico registrará técnico anterior, novo técnico, data/hora e motivo.'}
            </p>
            <div className="mt-4 space-y-3">
              {queuesRelacional ? (
                <>
                  {transferQueuesErr ? <p className="text-sm text-amber-800">{transferQueuesErr}</p> : null}
                  <label className="block text-sm font-medium text-slate-700 dark:text-[var(--pgm-text)]">
                    Fila de destino
                    <select
                      value={transferQueueId}
                      onChange={(e) => setTransferQueueId(e.target.value)}
                      className="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-[var(--pgm-primary)] dark:border-[var(--pgm-border)] dark:bg-[var(--pgm-bg-elevated)] dark:text-[var(--pgm-text)] dark:focus:border-[var(--pgm-primary)]"
                    >
                      <option value="">Selecione…</option>
                      {transferQueues.map((fq) => (
                        <option key={fq.id} value={String(fq.id)}>
                          {fq.name || fq.codigo || `Fila #${fq.id}`}
                        </option>
                      ))}
                    </select>
                  </label>
                  <fieldset className="rounded-lg border border-slate-100 bg-slate-50/80 p-3 dark:border-[var(--pgm-border)] dark:bg-[var(--pgm-bg-elevated)]/60">
                    <legend className="px-1 text-sm font-medium text-slate-700 dark:text-[var(--pgm-text)]">
                      Responsável
                    </legend>
                    <div className="mt-2 space-y-2">
                      <label className="flex cursor-pointer items-start gap-2 text-sm text-slate-700 dark:text-[var(--pgm-text)]">
                        <input
                          type="radio"
                          name="transferAssign"
                          className="mt-0.5"
                          checked={transferAssignMode === 'sem'}
                          onChange={() => {
                            setTransferAssignMode('sem');
                            setTransferDest('');
                          }}
                        />
                        <span>Somente mover para a fila (sem definir técnico responsável)</span>
                      </label>
                      <label className="flex cursor-pointer items-start gap-2 text-sm text-slate-700 dark:text-[var(--pgm-text)]">
                        <input
                          type="radio"
                          name="transferAssign"
                          className="mt-0.5"
                          checked={transferAssignMode === 'com'}
                          onChange={() => setTransferAssignMode('com')}
                        />
                        <span>Atribuir a um técnico vinculado à fila selecionada</span>
                      </label>
                    </div>
                  </fieldset>
                  {transferAssignMode === 'com' ? (
                    <label className="block text-sm font-medium text-slate-700 dark:text-[var(--pgm-text)]">
                      Técnico
                      <select
                        value={transferDest}
                        onChange={(e) => setTransferDest(e.target.value)}
                        className="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-[var(--pgm-primary)] dark:border-[var(--pgm-border)] dark:bg-[var(--pgm-bg-elevated)] dark:text-[var(--pgm-text)] dark:focus:border-[var(--pgm-primary)]"
                      >
                        <option value="">Selecione o técnico…</option>
                        {tecnicosModal.map((tm) => (
                          <option key={tm.id} value={String(tm.id)}>
                            {tm.name}
                          </option>
                        ))}
                      </select>
                      {transferQueueId && tecnicosModal.length === 0 ? (
                        <p className="mt-1 text-xs text-amber-800">
                          Nenhum técnico listado: cadastre o vínculo à fila em Usuários ou ajuste o nível de suporte para cobrir esta fila.
                        </p>
                      ) : null}
                    </label>
                  ) : null}
                </>
              ) : (
                <>
                  {wfEnabled ? (
                    <label className="block text-sm font-medium text-slate-700 dark:text-[var(--pgm-text)]">
                      Fila de destino
                      <select
                        value={transferFila}
                        onChange={(e) => setTransferFila(e.target.value)}
                        className="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-[var(--pgm-primary)] dark:border-[var(--pgm-border)] dark:bg-[var(--pgm-bg-elevated)] dark:text-[var(--pgm-text)] dark:focus:border-[var(--pgm-primary)]"
                      >
                        {filasMeta.map((f) => (
                          <option key={f.code} value={f.code}>
                            {f.label}
                          </option>
                        ))}
                      </select>
                    </label>
                  ) : null}
                  {wfEnabled ? (
                    <fieldset className="rounded-lg border border-slate-100 bg-slate-50/80 p-3 dark:border-[var(--pgm-border)] dark:bg-[var(--pgm-bg-elevated)]/60">
                      <legend className="px-1 text-sm font-medium text-slate-700 dark:text-[var(--pgm-text)]">
                        Responsável
                      </legend>
                      <div className="mt-2 space-y-2">
                        <label className="flex cursor-pointer items-start gap-2 text-sm text-slate-700 dark:text-[var(--pgm-text)]">
                          <input
                            type="radio"
                            name="transferAssignLegacy"
                            className="mt-0.5"
                            checked={transferAssignMode === 'sem'}
                            onChange={() => {
                              setTransferAssignMode('sem');
                              setTransferDest('');
                            }}
                          />
                          <span>Só alterar a fila; deixar sem técnico responsável (aguardando na fila)</span>
                        </label>
                        <label className="flex cursor-pointer items-start gap-2 text-sm text-slate-700 dark:text-[var(--pgm-text)]">
                          <input
                            type="radio"
                            name="transferAssignLegacy"
                            className="mt-0.5"
                            checked={transferAssignMode === 'com'}
                            onChange={() => setTransferAssignMode('com')}
                          />
                          <span>Encaminhar para um técnico</span>
                        </label>
                      </div>
                    </fieldset>
                  ) : null}
                  {(!wfEnabled || transferAssignMode === 'com') && (
                    <label className="block text-sm font-medium text-slate-700 dark:text-[var(--pgm-text)]">
                      {wfEnabled ? 'Técnico de destino' : 'Novo técnico responsável'}
                      <select
                        value={transferDest}
                        onChange={(e) => setTransferDest(e.target.value)}
                        className="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-[var(--pgm-primary)] dark:border-[var(--pgm-border)] dark:bg-[var(--pgm-bg-elevated)] dark:text-[var(--pgm-text)] dark:focus:border-[var(--pgm-primary)]"
                      >
                        <option value="">Selecione…</option>
                        {tecnicosOpcoes.map((t) => (
                          <option key={t.id} value={String(t.id)}>
                            {t.name}
                          </option>
                        ))}
                      </select>
                    </label>
                  )}
                </>
              )}
                    <label className="block text-sm font-medium text-slate-700 dark:text-[var(--pgm-text)]">
                Motivo
                <textarea
                  value={transferMotivo}
                  onChange={(e) => setTransferMotivo(e.target.value)}
                  rows={3}
                  className="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-[var(--pgm-primary)] dark:border-[var(--pgm-border)] dark:bg-[var(--pgm-bg-elevated)] dark:text-[var(--pgm-text)] dark:focus:border-[var(--pgm-primary)]"
                  placeholder="Ex.: Escalação para N2 — necessidade de visita presencial."
                />
              </label>
              {transferErr ? <p className="text-sm text-red-600">{transferErr}</p> : null}
            </div>
            <div className="mt-5 flex flex-wrap justify-end gap-2">
              <button
                type="button"
                className="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-[var(--pgm-border)] dark:text-[var(--pgm-text)] dark:hover:bg-[var(--pgm-bg-elevated)]"
                onClick={() => setTransferOpen(false)}
                disabled={transferSaving}
              >
                Cancelar
              </button>
              <button
                type="button"
                className="rounded-lg bg-[var(--pgm-primary)] px-4 py-2 text-sm font-semibold text-white hover:bg-[var(--pgm-erp-teal-active)] disabled:opacity-50 dark:hover:brightness-110"
                onClick={() => submitTransfer()}
                disabled={transferSaving}
              >
                {transferSaving ? 'Salvando…' : 'Confirmar transferência'}
              </button>
            </div>
          </div>
        </div>
      ) : null}
    </section>
  );

  if (embedded) {
    return (
      <div className="tickets-react-tech w-full overflow-visible pt-1 text-slate-800 dark:text-[var(--pgm-text)]">
        <header className="mb-2 flex min-h-[2.75rem] flex-wrap items-center justify-between gap-3 overflow-visible py-1">
          <h2 className="m-0 min-w-0 self-center text-lg font-semibold leading-snug tracking-tight text-slate-900 dark:text-[var(--pgm-text)]">
            {boot?.servicedesk ? 'Fila técnica' : 'Tickets — técnico'}
          </h2>
          <div className="flex shrink-0 flex-wrap items-center justify-end gap-2">
            {!boot?.servicedesk && boot?.paths?.ticketsOperacional ? (
              <a
                href={boot.paths.ticketsOperacional}
                className="inline-flex items-center justify-center rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-semibold leading-none text-slate-700 shadow-sm hover:bg-slate-50 dark:border-[var(--pgm-border)] dark:bg-[var(--pgm-bg-elevated)] dark:text-[var(--pgm-text)] dark:hover:bg-[var(--pgm-bg-surface)]"
              >
                Painel operacional
              </a>
            ) : null}
            {addTicket ? (
              <a
                href={addTicket}
                className="inline-flex shrink-0 items-center justify-center self-center rounded-md bg-[var(--pgm-primary)] px-3 py-2 text-sm font-semibold leading-none text-white shadow-sm hover:bg-[var(--pgm-erp-teal-active)] dark:hover:brightness-110"
              >
                Abrir ticket
              </a>
            ) : null}
          </div>
        </header>
        {tableSection}
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-slate-100 text-slate-800 dark:bg-[var(--pgm-bg-page)] dark:text-[var(--pgm-text)]">
      <div className="flex min-h-screen">
        <aside className="hidden w-72 shrink-0 bg-gradient-to-b from-[var(--pgm-primary)] to-[var(--pgm-secondary)] text-white lg:flex lg:flex-col">
          <div className="border-b border-white/10 px-6 py-6">
            <div className="flex items-center gap-4">
              <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/95 text-[var(--pgm-secondary)] shadow-lg">
                <div className="text-center leading-tight">
                  <div className="text-lg font-extrabold">PGM</div>
                  <div className="text-[9px] font-medium uppercase tracking-wide">Tickets</div>
                </div>
              </div>
              <div>
                <h1 className="text-lg font-semibold">Portal</h1>
                <p className="text-sm text-white/75">Painel técnico</p>
              </div>
            </div>
          </div>
          <nav className="flex-1 space-y-2 px-4 py-6 text-sm">
            <Link
              to="/"
              className="flex w-full items-center justify-between rounded-2xl px-4 py-3 text-left text-white/90 transition hover:bg-white/10"
            >
              <span className="font-medium">← Início</span>
            </Link>
            {USE_MOCK && (
              <Link
                to="/cliente"
                className="flex w-full items-center justify-between rounded-2xl px-4 py-3 text-left text-white/90 transition hover:bg-white/10"
              >
                <span className="font-medium">Demo cliente</span>
              </Link>
            )}
          </nav>
        </aside>

        <main className="flex-1">
          <header className="sticky top-0 z-20 border-b border-slate-200 bg-white/90 backdrop-blur dark:border-[var(--pgm-border)] dark:bg-[var(--pgm-bg-surface)]/95">
            <div className="flex flex-col gap-4 px-4 py-4 sm:px-6 xl:flex-row xl:items-center xl:justify-between">
              <div>
                <p className="text-sm font-medium text-[var(--pgm-primary)] dark:text-[var(--pgm-text-secondary)]">
                  {MOCK_SESSION_TECNICO.empresa}
                </p>
                <h2 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-[var(--pgm-text)]">
                  Tickets — técnico
                </h2>
                <p className="text-sm text-slate-500 dark:text-[var(--pgm-text-muted)]">
                  Responsável, filas N1–N3/NOC/serviço e transferência com registro no histórico.
                </p>
              </div>
              <div className="flex flex-wrap items-center gap-3">
                <div className="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm shadow-sm dark:border-[var(--pgm-border)] dark:bg-[var(--pgm-bg-elevated)]">
                  <span className="block text-slate-500 dark:text-[var(--pgm-text-muted)]">Data</span>
                  <span className="font-semibold text-slate-800 dark:text-[var(--pgm-text)]">{hoje}</span>
                </div>
                <span className="rounded-2xl border border-slate-200 px-5 py-3 text-sm text-slate-500 dark:border-[var(--pgm-border)] dark:text-[var(--pgm-text-muted)]">
                  Abrir ticket (portal)
                </span>
              </div>
            </div>
          </header>

          <div className="space-y-6 p-4 sm:p-6">
            {tableSection}
            <p className="text-center text-xs text-slate-400">Modo demonstração — use Vite em localhost.</p>
          </div>
        </main>
      </div>
    </div>
  );
}
