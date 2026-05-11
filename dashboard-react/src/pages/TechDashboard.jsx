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
  patchTicketStatus,
  postTransferirTicket,
  postStartTicket,
  postAlterarSituacao,
  postServicedeskAwaitCliente,
  postServicedeskEscalateLevel,
  fetchDashboardOperacional,
  getBoot,
  USE_MOCK,
} from '../lib/api';
import {
  badgeClass,
  servicedeskStatusTypeFromTicket,
  sortTicketAcoes,
  ticketHasValidQueueForStart,
  ticketHasValidTecnico,
} from '../lib/ticketUi';
import { compareTicketsBySlaUrgency, slaUrgencyBadgeClass, slaUrgencyLabel } from '../lib/slaUi.js';
import { MOCK_SESSION_TECNICO } from '../data/mockData';
import TicketsServicedeskInlineRow from '../components/TicketsServicedeskInlineRow.jsx';

const API_ERR_TRANSFER = {
  escalacao_invalida: 'Não foi possível concluir a transferência para a fila indicada.',
  mesma_fila: 'Selecione uma fila diferente da atual.',
  sem_permissao_transferir_fila:
    'Você precisa estar vinculado à fila deste ticket. Cadastre-se na fila em Filas → Técnicos.',
  tecnico_sem_empresa: 'O técnico informado não pertence à empresa atual.',
  tecnico_fila_obrigatorios:
    'Informe técnico e fila válidos (atribuição na grade, transferência ou escalonamento).',
  destino_sem_vinculo_fila:
    'O técnico selecionado não está vinculado à fila escolhida. Cadastre o técnico na fila em Filas → Técnicos e tente novamente.',
  destino_nivel_incompativel: 'O nível do técnico de destino não cobre essa fila.',
  motivo_obrigatorio: 'Informe o motivo (mín. 3 caracteres).',
  destino_ou_fila_obrigatorio: 'Indique fila de destino e/ou técnico, conforme a opção escolhida.',
  save_failed:
    'Não foi possível gravar a transferência no servidor. Se persistir, veja o log (apiTransferirTicket: update_ticket, mov_errors ou SQL).',
};

const API_ERR_START = {
  sem_permissao_fila:
    'Não é possível assumir: seu usuário precisa estar vinculado à fila deste ticket (Filas → Técnicos).',
  tecnico_fila_obrigatorios: 'Selecione um técnico e uma fila válidos antes de iniciar o atendimento.',
};

/** Polling do Service Desk embutido — aba em segundo plano não dispara fetch. */
const SERVICEDESK_POLL_MS = 10_000;
const SLA_DASHBOARD_POLL_MS = 30_000;

const GROUP_KEYS = {
  todos: 'todos',
  pendente: 'pendentes',
  execucao: 'emandamento',
  resolvido: 'resolvidos',
  fechados: 'fechados',
};

function runWhenBrowserIdle(task, timeoutMs = 900) {
  if (typeof window !== 'undefined' && typeof window.requestIdleCallback === 'function') {
    const id = window.requestIdleCallback(() => task(), { timeout: timeoutMs });
    return () => window.cancelIdleCallback(id);
  }
  const timerId = window.setTimeout(task, 150);
  return () => window.clearTimeout(timerId);
}

/** Legado pode mandar HTML em situacaoLabel até o servidor atualizar — remove tags. */
function stripHtml(raw) {
  if (raw == null) return '—';
  const s = String(raw);
  const t = s.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
  return t || '—';
}

function statusLabel(row) {
  const raw = stripHtml(row.situacaoLabel || row.status);
  const k = raw
    .normalize('NFD')
    .replace(/\p{M}/gu, '')
    .toLowerCase()
    .trim();
  if (k.includes('aguardando tecnico')) return 'Pendente';
  return raw;
}

function normalizeWorkflowCode(value) {
  return String(value || '')
    .normalize('NFD')
    .replace(/\p{M}/gu, '')
    .replace(/[\s-]+/g, '_')
    .toLowerCase()
    .trim();
}

function workflowCodeToLabel(code) {
  const c = normalizeWorkflowCode(code);
  if (c === 'resolvido') return 'Resolvido';
  if (c === 'fechado') return 'Fechado';
  if (c === 'pendente' || c === 'aberto' || c === 'aguardando_tecnico') return 'Pendente';
  if (c === 'emandamento' || c === 'em_execucao' || c === 'execucao' || c === 'em_andamento') return 'Em execução';
  return '';
}

function effectiveStatus(ticket) {
  const wf = ticket?.workflow;
  const hasCurrent = wf?.enabled === true && wf?.current && (wf?.current?.id != null || wf?.current?.codigo != null || wf?.current?.code != null || wf?.current?.slug != null);
  if (hasCurrent) {
    const wfCodeRaw = wf?.current?.codigo ?? wf?.current?.code ?? wf?.current?.slug ?? '';
    const wfCode = normalizeWorkflowCode(wfCodeRaw);
    const wfLabelRaw = stripHtml(wf?.current?.label || '');
    const wfLabel = wfLabelRaw !== '—' ? wfLabelRaw : workflowCodeToLabel(wfCodeRaw);
    return {
      label: wfLabel || statusLabel(ticket),
      code: wfCode,
      source: 'workflow',
    };
  }
  return {
    label: statusLabel(ticket),
    code: '',
    source: 'legacy',
  };
}

function formatSecondsCompact(totalSeconds) {
  const n = Math.max(0, Number(totalSeconds) || 0);
  const h = Math.floor(n / 3600);
  const m = Math.floor((n % 3600) / 60);
  if (h <= 0) return `${m}m`;
  if (m <= 0) return `${h}h`;
  return `${h}h ${m}m`;
}

const ACTION_MENU_WIDTH = 260;

/** Dots pulsantes alinhados ao mockup (execução, aguard. técnico, P1, crítica). */
const PULSE_STATUS = new Set(['progress', 'pendingTech', 'pendingTicket', 'critical', 'high']);
/** Status sem indicador visual (texto puro). */
const NO_DOT_STATUS = new Set(['low', 'open']);

/** “Nome — N2” quando existe nivel; cai para só o nome caso contrário. */
function tecnicoLabel(t) {
  const nm = t?.name || `Usuário #${t?.id}`;
  const level = t?.nivel_label || t?.supportLevelLabel || t?.supportLevel || '';
  return level ? `${nm} — ${level}` : nm;
}

function StatusDot({ type }) {
  if (NO_DOT_STATUS.has(type)) return null;
  const pulse = PULSE_STATUS.has(type) ? ' animate-pgm-pulse' : '';
  return (
    <span
      aria-hidden
      className={`inline-block h-[6px] w-[6px] shrink-0 rounded-full bg-current${pulse}`}
    />
  );
}

function SlaUrgencyCell({ ticket }) {
  const band = ticket?.sla_urgency_band || 'unknown';
  const tip = String(ticket?.sla_tooltip || '').trim() || 'SLA';
  return (
    <button
      type="button"
      className={`${slaUrgencyBadgeClass(band)} max-w-full`}
      title={tip}
      aria-label={tip}
      onClick={(e) => e.stopPropagation()}
    >
      {slaUrgencyLabel(band)}
    </button>
  );
}

/**
 * Garante que tickets abertos exponham Iniciar atendimento (quando pendente) e Transferir
 * (sempre). Backend pode esconder conforme flags — UI sempre mostra para alinhar ao mockup.
 */
function ensureCoreActions(ticket, opts = {}) {
  const hideTransfer = Boolean(opts.hideTransfer);
  const queuesRel = Boolean(opts.queuesRelacional);
  const list = Array.isArray(ticket.acoes) ? [...ticket.acoes] : [];
  const st = effectiveStatus(ticket);
  const label = String(st.label || '').toLowerCase();
  const wfCode = String(st.code || '');
  const wfEnabled = st.source === 'workflow' && ticket?.workflow?.enabled === true;
  const closed =
    label.includes('resolvido') || label.includes('fechado') || label.includes('cancelado');
  if (closed) return list;
  const has = (k) => list.some((a) => String(a.key || '').toLowerCase() === k);
  const inProgress =
    label.includes('em execucao') ||
    label.includes('em execução') ||
    label.includes('em andamento');
  const boot = getBoot();
  const pendenteCode = boot?.ticketStatus?.pendente;
  const isPendenteByCode =
    pendenteCode != null &&
    ticket.situacao !== undefined &&
    ticket.situacao !== null &&
    Number(ticket.situacao) === Number(pendenteCode);
  // Iniciar só aparece em tickets pendentes (aguardando técnico / sem responsável);
  // se já está em execução, o atendimento foi assumido e a ação não faz sentido.
  const isPendenteByWorkflow = wfEnabled && (wfCode === 'pendente' || wfCode === 'aberto');
  const isPendenteLegacy =
    isPendenteByCode ||
    (!inProgress &&
      (label.includes('aguardando tecnico') ||
        label.includes('aguardando técnico') ||
        !ticket.idtecnico_responsavel));
  const isPendente = wfEnabled ? isPendenteByWorkflow : isPendenteLegacy;
  const transitions = wfEnabled && Array.isArray(ticket?.workflow?.allowedTransitions)
    ? ticket.workflow.allowedTransitions
    : [];
  const findTransition = (matcher) => transitions.find((t) => matcher(normalizeWorkflowCode(t?.codigo)));
  const execTransition = findTransition((c) => c === 'emandamento' || c === 'em_execucao' || c === 'execucao' || c === 'em_andamento');
  const pendTransition = findTransition((c) => c === 'pendente' || c === 'aberto');
  const canManualStart = ticketHasValidTecnico(ticket) && ticketHasValidQueueForStart(ticket, queuesRel);
  if (isPendente && canManualStart && !has('iniciar') && (!wfEnabled || !!execTransition)) {
    list.push({
      key: 'iniciar',
      label: 'Iniciar atendimento',
      behavior: 'reactStart',
      workflowStateId: execTransition ? Number(execTransition.id) : null,
      statusLabel: execTransition?.label || 'Em execução',
      url: ticket.urls?.edit || '#',
    });
  }
  if (wfEnabled && pendTransition && !has('pendente')) {
    list.push({
      key: 'pendente',
      label: 'Pendente',
      behavior: 'reactStatus',
      situacaoDestino: boot?.ticketStatus?.pendente ?? null,
      workflowStateId: Number(pendTransition.id),
      statusLabel: pendTransition?.label || 'Pendente',
      url: ticket.urls?.edit || '#',
    });
  }
  if (!hideTransfer && ticketHasValidTecnico(ticket) && ticketHasValidQueueForStart(ticket, queuesRel) && !has('transferir')) {
    list.push({
      key: 'transferir',
      label: 'Transferir',
      behavior: 'reactTransfer',
      url: ticket.urls?.edit || '#',
    });
  }
  const sdAct = ticket.servicedeskActions && typeof ticket.servicedeskActions === 'object' ? ticket.servicedeskActions : {};
  if (sdAct.canAwaitCliente && !has('aguardcliente')) {
    list.push({
      key: 'aguardcliente',
      label: sdAct.awaitClienteUsesPendenteFallback
        ? 'Pausar SLA (pendente / aguarda)'
        : 'Pausar para Aguardando Cliente',
      behavior: 'reactAwaitCliente',
      workflowStateId: sdAct.awaitClienteWorkflowStateId != null ? Number(sdAct.awaitClienteWorkflowStateId) : null,
    });
  }
  if (sdAct.canEscalateLevel && !has('subirnivel')) {
    list.push({
      key: 'subirnivel',
      label: 'Subir nível',
      behavior: 'reactEscalateLevel',
    });
  }
  return list;
}

function ActionMenuIcon({ actionKey }) {
  const k = String(actionKey || '').toLowerCase();
  const c = 'h-[18px] w-[18px] shrink-0';
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
          <path d="M9 12.75l2.25 2.25L15 9.75" />
          <path d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
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
    case 'aguardcliente':
      return (
        <svg className={c} viewBox="0 0 24 24" aria-hidden {...stroke}>
          <path d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
        </svg>
      );
    case 'subirnivel':
      return (
        <svg className={c} viewBox="0 0 24 24" aria-hidden {...stroke}>
          <path d="M3 4.5h14.25M3 9h9.75M3 13.5h9.75m4.5-4.5v12m0 0l-3.75-3.75M17.25 21l3.75-3.75" />
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
  if (k === 'emandamento' || k === 'execucao') return 'execucao';
  if (k === 'pendente') return 'pendente';
  if (k === 'resolvido') return 'resolvido';
  if (k === 'cancelar') return 'danger';
  if (k === 'imprimir') return 'imprimir';
  if (k === 'aguardcliente') return 'pendente';
  if (k === 'subirnivel') return 'default';
  return 'default';
}

/** Ações finais do menu (mock: linha divisória antes deste grupo). */
function isSecondaryMenuActionKey(key) {
  const k = String(key || '').toLowerCase();
  return k === 'cancelar' || k === 'imprimir';
}

/** Menu por linha: portal + posição fixa (não é cortado pelo overflow da tabela), visual alinhado ao Service Desk. */
function TicketActionsMenu({
  ticket,
  acoes,
  openTransfer,
  handleStartAtendimento,
  startBusyId,
  handleAlterarSituacao,
  statusBusyKey,
  handleServicedeskAwaitCliente,
  handleServicedeskEscalateLevel,
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

  if (acoesOrd.length === 0) return <span className="text-[var(--pgm-text-muted)]">—</span>;

  const tc = {
    default:
      'text-[var(--pgm-text-secondary)] hover:bg-[var(--pgm-bg-overlay)] hover:text-[var(--pgm-text-secondary)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--pgm-primary)] focus-visible:ring-offset-2 focus-visible:ring-offset-[var(--pgm-bg-surface)]',
    execucao:
      'text-[#2DAAE1] hover:bg-[rgba(45,170,225,0.1)] hover:text-[#2DAAE1] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2DAAE1] focus-visible:ring-offset-2 focus-visible:ring-offset-[var(--pgm-bg-surface)]',
    pendente:
      'text-[#F39C12] hover:bg-[rgba(243,156,18,0.1)] hover:text-[#F39C12] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#F39C12] focus-visible:ring-offset-2 focus-visible:ring-offset-[var(--pgm-bg-surface)]',
    resolvido:
      'text-[#27AE60] hover:bg-[rgba(39,174,96,0.1)] hover:text-[#27AE60] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#27AE60] focus-visible:ring-offset-2 focus-visible:ring-offset-[var(--pgm-bg-surface)]',
    danger:
      'text-[#ea580c] hover:bg-[rgba(234,88,12,0.1)] hover:text-[#ea580c] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#ea580c] focus-visible:ring-offset-2 focus-visible:ring-offset-[var(--pgm-bg-surface)]',
    imprimir:
      'text-[#8a5ac2] hover:bg-[rgba(138,90,194,0.1)] hover:text-[#8a5ac2] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#8a5ac2] focus-visible:ring-offset-2 focus-visible:ring-offset-[var(--pgm-bg-surface)]',
  };

  const rowBase =
    'flex w-full items-center gap-3 bg-transparent px-4 py-[11px] text-left text-[0.875rem] font-medium transition-colors duration-[120ms] cursor-pointer disabled:cursor-not-allowed disabled:opacity-45';

  const menuPanel = open ? (
    <div
      ref={menuRef}
      role="menu"
      aria-label={`Ações do ticket ${ticket.id}`}
      className="tickets-sd-action-menu fixed z-[60] overflow-hidden bg-[var(--pgm-bg-surface)] text-[var(--pgm-text)]"
      style={{ top: pos.top, left: pos.left, animation: 'pgm-modal-in 0.2s cubic-bezier(0.16,1,0.3,1)' }}
    >
      <div className="px-4 pb-3 pt-3.5 text-[0.6875rem] font-semibold uppercase leading-none tracking-[0.1em] text-[var(--pgm-text-muted)]">
        Ticket #{ticket.id}
      </div>
      <div className="h-px bg-[var(--pgm-border-subtle)]" />
      <ul className="tickets-sd-action-menu-list max-h-[min(320px,70vh)] list-none overflow-y-auto">
        {acoesOrd.flatMap((a, i) => {
          const prevKey = i > 0 ? acoesOrd[i - 1].key : null;
          const showDividerBefore =
            i > 0 &&
            isSecondaryMenuActionKey(a.key) &&
            !isSecondaryMenuActionKey(prevKey);
          const divider = showDividerBefore ? (
            <li
              key={`sd-menu-divider-${ticket.id}-${i}`}
              className="tickets-sd-action-menu-divider-wrap pointer-events-none"
              role="separator"
              aria-hidden="true"
            >
              <span className="tickets-sd-action-menu-divider" />
            </li>
          ) : null;

          const tone = actionItemTone(a.key);
          const iconColors = {
            execucao: 'text-[#2DAAE1]',
            pendente: 'text-[#F39C12]',
            resolvido: 'text-[#27AE60]',
            danger: 'text-[#ea580c]',
            imprimir: 'text-[#8a5ac2]',
          };
          const iconWrap = `${iconColors[tone] || 'text-[var(--pgm-text-secondary)]'}`;
          if (a.behavior === 'reactAwaitCliente' && typeof handleServicedeskAwaitCliente === 'function') {
            const busy = statusBusyKey === `await-${ticket.id}`;
            const row = (
              <li key={`${a.key}-${a.label}`} role="none">
                <button
                  type="button"
                  role="menuitem"
                  className={`${rowBase} ${tc.pendente}`}
                  disabled={busy}
                  onClick={() => {
                    setOpen(false);
                    handleServicedeskAwaitCliente(ticket, a);
                  }}
                >
                  <span className="text-[#F39C12]">
                    <ActionMenuIcon actionKey={a.key} />
                  </span>
                  <span className="min-w-0 flex-1 leading-snug">{busy ? 'Aplicando…' : a.label}</span>
                </button>
              </li>
            );
            return divider ? [divider, row] : [row];
          }
          if (a.behavior === 'reactEscalateLevel' && typeof handleServicedeskEscalateLevel === 'function') {
            const busy = statusBusyKey === `escalate-${ticket.id}`;
            const row = (
              <li key={`${a.key}-${a.label}`} role="none">
                <button
                  type="button"
                  role="menuitem"
                  className={`${rowBase} ${tc.default}`}
                  disabled={busy}
                  onClick={() => {
                    setOpen(false);
                    handleServicedeskEscalateLevel(ticket);
                  }}
                >
                  <span className="text-[var(--pgm-text-secondary)]">
                    <ActionMenuIcon actionKey={a.key} />
                  </span>
                  <span className="min-w-0 flex-1 leading-snug">{busy ? 'Escalonando…' : a.label}</span>
                </button>
              </li>
            );
            return divider ? [divider, row] : [row];
          }
          if (a.behavior === 'reactTransfer') {
            const row = (
              <li key={`${a.key}-${a.label}`} role="none">
                <button
                  type="button"
                  role="menuitem"
                  className={`${rowBase} ${tc[tone]}`}
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
            return divider ? [divider, row] : [row];
          }
          if (a.behavior === 'reactStart') {
            const busy = startBusyId === Number(ticket.id);
            const row = (
              <li key={`${a.key}-${a.label}`} role="none">
                <button
                  type="button"
                  role="menuitem"
                  className={`${rowBase} ${tc.default}`}
                  disabled={busy}
                  onClick={() => {
                    setOpen(false);
                    handleStartAtendimento(ticket);
                  }}
                >
                  <span className="text-[var(--pgm-text-secondary)]">
                    <ActionMenuIcon actionKey={a.key} />
                  </span>
                  <span className="min-w-0 flex-1 leading-snug">{busy ? 'Iniciando…' : a.label}</span>
                </button>
              </li>
            );
            return divider ? [divider, row] : [row];
          }
          if (a.behavior === 'reactStatus' && typeof handleAlterarSituacao === 'function' && (a.situacaoDestino != null || a.workflowStateId != null)) {
            const sk = `${ticket.id}-${a.workflowStateId || a.situacaoDestino}`;
            const busy = statusBusyKey === sk;
            const row = (
              <li key={`${a.key}-${a.label}`} role="none">
                <button
                  type="button"
                  role="menuitem"
                  className={`${rowBase} ${tc[tone]}`}
                  disabled={busy}
                  onClick={() => {
                    setOpen(false);
                    handleAlterarSituacao(ticket, {
                      situacaoDestino: a.situacaoDestino,
                      workflowStateId: a.workflowStateId,
                      statusLabel: a.statusLabel,
                    });
                  }}
                >
                  <span className={iconWrap}>
                    <ActionMenuIcon actionKey={a.key} />
                  </span>
                  <span className="min-w-0 flex-1 leading-snug">{busy ? 'Atualizando…' : a.label}</span>
                </button>
              </li>
            );
            return divider ? [divider, row] : [row];
          }
          const row = (
            <li key={`${a.key}-${a.label}`} role="none">
              <a
                role="menuitem"
                href={a.url}
                target={a.target || '_self'}
                rel={a.target === '_blank' ? 'noreferrer' : undefined}
                className={`${rowBase} ${tc[tone]} no-underline`}
                onClick={() => setOpen(false)}
              >
                <span className={iconWrap}>
                  <ActionMenuIcon actionKey={a.key} />
                </span>
                <span className="min-w-0 flex-1 leading-snug">{a.label}</span>
              </a>
            </li>
          );
          return divider ? [divider, row] : [row];
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
          className={`inline-flex h-7 shrink-0 items-center gap-1.5 whitespace-nowrap rounded-[var(--pgm-radius-sm,6px)] border px-2.5 text-[0.75rem] font-medium transition-all duration-[120ms] focus:outline-none ${
            open
              ? 'border-[var(--pgm-border)] bg-[var(--pgm-bg-overlay)] text-[var(--pgm-text)]'
              : 'border-[var(--pgm-border-subtle)] bg-transparent text-[var(--pgm-text-muted)] hover:bg-[var(--pgm-bg-overlay)] hover:text-[var(--pgm-text)] hover:border-[var(--pgm-border)]'
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
function techRowHighlightClass(ticket, servicedesk = false) {
  const label = String(effectiveStatus(ticket).label || '').toLowerCase();
  const closed =
    label.includes('resolvido') || label.includes('fechado') || label.includes('cancelado');
  const semResp =
    !closed &&
    !ticket.idtecnico_responsavel &&
    (String(ticket.tecnicos || '').includes('não atribuído') ||
      ticket.tecnicos === '—' ||
      ticket.tecnicos === '');
  const parts = ['align-middle', 'transition-all', 'duration-[120ms]'];
  if (servicedesk) {
    parts.push('border-b', 'border-[var(--pgm-border-subtle)]');
    const isWaiting = label.includes('aguardando cliente') || label.includes('respondido');
    if (isWaiting) {
      parts.push('shadow-[inset_3px_0_0_var(--pgm-badge-blue-ring)]', 'hover:shadow-[inset_3px_0_0_var(--pgm-badge-blue-text)]');
    } else if (semResp) {
      parts.push('shadow-[inset_3px_0_0_var(--pgm-badge-amber-ring)]', 'hover:shadow-[inset_3px_0_0_var(--pgm-badge-amber-text)]');
    } else {
      parts.push('hover:shadow-[inset_3px_0_0_var(--pgm-primary)]');
    }
  } else {
    if (label.includes('aguardando')) {
      parts.push('bg-[var(--pgm-primary-muted)]');
    } else if (label.includes('execução') || label.includes('andamento')) {
      parts.push('bg-[color-mix(in_srgb,var(--pgm-primary)_10%,var(--pgm-bg-surface)_90%)]');
    }
    if (semResp) {
      parts.push('ring-1', 'ring-inset', 'ring-[var(--pgm-border)]');
    }
  }
  return parts.join(' ');
}

export default function TechDashboard({ boot }) {
  const embedded = Boolean(boot);
  const [groups, setGroups] = useState(null);
  const [workflow, setWorkflow] = useState(null);
  const [slaDashboard, setSlaDashboard] = useState(null);
  const [slaDashboardFilter, setSlaDashboardFilter] = useState('all');
  const [q, setQ] = useState('');
  const [filtroStatus, setFiltroStatus] = useState('ativos');
  const [sortBySlaUrgency, setSortBySlaUrgency] = useState(false);
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
  const [transferErrLink, setTransferErrLink] = useState(null);
  const [transferQueuesErr, setTransferQueuesErr] = useState('');
  const [startBusyId, setStartBusyId] = useState(null);
  const [statusBusyKey, setStatusBusyKey] = useState(null);
  const [patchBusyId, setPatchBusyId] = useState(null);
  const [statusInteractionLock, setStatusInteractionLock] = useState({});
  /** Erro por PATCH inline (rollback já aplicado na linha). */
  const [inlinePatchError, setInlinePatchError] = useState({
    id: null,
    msg: '',
    linkUrl: null,
    linkLabel: null,
  });
  const inlinePatchErrorTimerRef = useRef(null);
  const [transferOkHint, setTransferOkHint] = useState('');
  const [loadError, setLoadError] = useState(null);
  const sdTableScrollRef = useRef(null);
  /** Impede dois polls `api-index` em paralelo (tick ignorado se o anterior ainda não terminou). */
  const pollReloadInFlightRef = useRef(false);
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

  const reload = useCallback(async (opts = {}) => {
    const silent = opts.silent === true;

    const applyHttpError = (res) => {
      const hint = res.httpStatus ? ` (HTTP ${res.httpStatus})` : '';
      setLoadError(
        res.error === 'session_empresa_invalida'
          ? 'Sessão sem empresa válida. Troque de empresa ou faça login novamente.'
          : res.error === 'api_index_failed'
            ? `Falha ao montar a lista de tickets no servidor.${hint} Veja o log da aplicação (apiIndex).`
            : `Não foi possível carregar os tickets.${hint} ${res.error || ''}`.trim(),
      );
    };

    let res;
    try {
      res = await fetchTicketsTecnico(loadFilters);
    } catch (err) {
      if (silent) {
        if (typeof import.meta !== 'undefined' && import.meta.env?.DEV) {
          console.debug('[tech-dashboard] reload polling: falha de rede ou fetch', err);
        }
      } else {
        setLoadError(
          'Não foi possível carregar os tickets. Verifique a conexão e tente novamente.',
        );
      }
      return;
    }

    if (res.ok && res.groups) {
      setLoadError(null);
      setGroups(res.groups);
      setWorkflow(res.workflow ?? { enabled: false, filas: [] });
      return;
    }

    if (!silent) {
      applyHttpError(res);
    } else if (typeof import.meta !== 'undefined' && import.meta.env?.DEV) {
      console.debug('[tech-dashboard] reload polling: resposta não ok', res.error, res.httpStatus);
    }
  }, [loadFilters]);

  const reloadSlaDashboard = useCallback(async () => {
    const res = await fetchDashboardOperacional();
    if (!res.ok || !res.dashboard) return;
    setSlaDashboard(res.dashboard);
  }, []);

  useEffect(() => {
    void reload({ silent: false });
  }, [loadFilters, reload]);

  useEffect(() => {
    let cancel = false;
    const stop = runWhenBrowserIdle(() => {
      void (async () => {
        const r = await fetchTecnicosParaTransferencia();
        if (cancel || !r.ok) return;
        setTecnicosOpcoes(r.tecnicos || []);
      })();
    }, 1200);
    return () => {
      cancel = true;
      stop();
    };
  }, []);

  useEffect(() => {
    let cancel = false;
    const stop = runWhenBrowserIdle(() => {
      void (async () => {
        const r = await fetchDashboardOperacional();
        if (cancel || !r.ok || !r.dashboard) return;
        setSlaDashboard(r.dashboard);
      })();
    }, 1500);
    return () => {
      cancel = true;
      stop();
    };
  }, []);

  useEffect(() => {
    if (!embedded || !boot?.servicedesk) return undefined;
    const id = window.setInterval(() => {
      if (document.visibilityState !== 'visible') return;
      if (pollReloadInFlightRef.current) return;
      pollReloadInFlightRef.current = true;
      void (async () => {
        try {
          await reload({ silent: true });
        } finally {
          pollReloadInFlightRef.current = false;
        }
      })();
    }, SERVICEDESK_POLL_MS);
    return () => window.clearInterval(id);
  }, [embedded, boot?.servicedesk, reload]);

  useEffect(() => {
    const id = window.setInterval(() => {
      if (document.visibilityState !== 'visible') return;
      reloadSlaDashboard();
    }, SLA_DASHBOARD_POLL_MS);
    return () => window.clearInterval(id);
  }, [reloadSlaDashboard]);

  const wfEnabled = Boolean(workflow?.enabled);
  const filasMeta = workflow?.filas || [];
  const queuesRelacional = Boolean(workflow?.queuesRelacional);
  const effectiveBoot = boot || getBoot();
  /**
   * Só controla UI da grid técnica (inline + ocultar Transferir). Endpoints PATCH existem no boot como URLs
   * e não ativam este layout por si. Ausência da flag ou false = legado (/tickets). Timer do ticket ≠ apiTimer.
   */
  const inlineAssignment = effectiveBoot?.inlineAssignment === true;
  const situacaoExecCode = effectiveBoot?.ticketStatus?.emandamento;

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
      if (sortBySlaUrgency) {
        base = [...base].sort(compareTicketsBySlaUrgency);
      } else {
        base.sort((x, y) => Number(y.id) - Number(x.id));
      }
    } else {
      const key = GROUP_KEYS[filtroStatus] || 'todos';
      base = groups[key] || [];
    }
    const qq = deferredQ.trim().toLowerCase();
    if (!qq) return base;
    return base.filter((t) => {
      const id = String(t.id);
      const cliente = String(t.cliente || '').toLowerCase();
      const assunto = String(t.assunto_nome || '').toLowerCase();
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
  }, [groups, filtroStatus, deferredQ, sortBySlaUrgency]);

  const mergeTicketInGroups = useCallback((ticketId, newRow) => {
    setGroups((prev) => {
      if (!prev || !newRow) return prev;
      const repl = (arr) =>
        Array.isArray(arr)
          ? arr.map((t) => (Number(t?.id) === Number(ticketId) ? { ...t, ...newRow } : t))
          : arr;
      return {
        ...prev,
        todos: repl(prev.todos),
        pendentes: repl(prev.pendentes),
        emandamento: repl(prev.emandamento),
        resolvidos: repl(prev.resolvidos),
        fechados: repl(prev.fechados),
      };
    });
  }, []);

  const lockTicketStatusInteraction = useCallback((ticketId, ms = 1200) => {
    const id = Number(ticketId);
    if (!Number.isFinite(id) || id <= 0) return;
    setStatusInteractionLock((prev) => ({ ...prev, [id]: true }));
    window.setTimeout(() => {
      setStatusInteractionLock((prev) => {
        if (!prev[id]) return prev;
        const next = { ...prev };
        delete next[id];
        return next;
      });
    }, ms);
  }, []);

  const isTicketStatusInteractionLocked = useCallback(
    (ticketId) => {
      const id = Number(ticketId);
      return Number(startBusyId) === id || Number(patchBusyId) === id || Boolean(statusInteractionLock[id]);
    },
    [startBusyId, patchBusyId, statusInteractionLock],
  );

  const onInlinePatchError = useCallback((ticketId, detail) => {
    if (inlinePatchErrorTimerRef.current) {
      window.clearTimeout(inlinePatchErrorTimerRef.current);
    }
    if (typeof detail === 'string') {
      setInlinePatchError({
        id: ticketId,
        msg: String(detail || 'Erro'),
        linkUrl: null,
        linkLabel: null,
      });
    } else {
      setInlinePatchError({
        id: ticketId,
        msg: String(detail?.msg || 'Erro'),
        linkUrl: detail?.linkUrl || null,
        linkLabel: detail?.linkLabel || null,
      });
    }
    inlinePatchErrorTimerRef.current = window.setTimeout(() => {
      inlinePatchErrorTimerRef.current = null;
      setInlinePatchError({ id: null, msg: '', linkUrl: null, linkLabel: null });
    }, 6000);
  }, []);

  const totalTodos = groups?.todos?.length ?? 0;
  const hoje = new Date().toLocaleDateString('pt-BR');
  const slaStateSummary = slaDashboard?.sla_por_etapa || {};
  const slaAlertsByType = slaDashboard?.alertas_sla_state || {};
  const slaAlertRows = useMemo(() => {
    const overdue = Array.isArray(slaAlertsByType.overdue) ? slaAlertsByType.overdue.map((x) => ({ ...x, _type: 'overdue' })) : [];
    const near = Array.isArray(slaAlertsByType.near_due) ? slaAlertsByType.near_due.map((x) => ({ ...x, _type: 'near_due' })) : [];
    const paused = Array.isArray(slaAlertsByType.paused) ? slaAlertsByType.paused.map((x) => ({ ...x, _type: 'paused' })) : [];
    const all = [...overdue, ...near, ...paused];
    if (slaDashboardFilter === 'overdue') return all.filter((x) => x._type === 'overdue');
    if (slaDashboardFilter === 'near_due') return all.filter((x) => x._type === 'near_due');
    if (slaDashboardFilter === 'paused') return all.filter((x) => x._type === 'paused');
    return all;
  }, [slaAlertsByType, slaDashboardFilter]);

  const addTicket = boot?.paths?.addTicket;

  const openTransfer = async (ticket) => {
    if (!ticketHasValidTecnico(ticket) || !ticketHasValidQueueForStart(ticket, queuesRelacional)) {
      window.alert('Para escalonar/transferir, selecione um técnico e uma fila válidos.');
      return;
    }
    setTransferTicket(ticket);
    setTransferDest('');
    setTransferMotivo('');
    setTransferFila(wfEnabled ? ticket.filaSuporte || 'n1' : '');
    setTransferErr('');
    setTransferErrLink(null);
    setTransferQueuesErr('');
    setTransferQueues([]);
    setTransferQueueId('');
    setTransferAssignMode('com');
    if (queuesRelacional) {
      // Lista completa de filas do ticket (ABAC); sem escalation_only — alinhado a mover para qualquer nível.
      const rq = await fetchQueuesForTicket(ticket.id, { escalationOnly: false });
      if (!rq.ok) {
        setTransferQueuesErr(rq.error || 'Não foi possível carregar as filas.');
        setTransferQueues([]);
        setTransferQueueId('');
      } else {
        const list = rq.queues || [];
        setTransferQueues(list);
        setTransferQueuesErr('');
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
      setTransferErrLink(null);
      return;
    }
    setTransferSaving(true);
    setTransferErr('');
    setTransferErrLink(null);
    const qid = Number(transferQueueId) || 0;
    if (transferAssignMode !== 'com') {
      setTransferSaving(false);
      setTransferErr('Escalonamento manual exige técnico e fila de destino válidos.');
      setTransferErrLink(null);
      return;
    }
    let payload;
    if (queuesRelacional) {
      if (!qid) {
        setTransferSaving(false);
        setTransferErr('Selecione a fila de destino.');
        setTransferErrLink(null);
        return;
      }
      if (!dest) {
        setTransferSaving(false);
        setTransferErr('Selecione o técnico de destino.');
        setTransferErrLink(null);
        return;
      }
      payload = { iduser_destino: dest, queue_id: qid, motivo: transferMotivo.trim() };
    } else {
      if (wfEnabled) {
        const fc = transferFila || 'n1';
        if (!dest) {
          setTransferSaving(false);
          setTransferErr('Selecione o técnico de destino.');
          setTransferErrLink(null);
          return;
        }
        if (!fc || fc === '-') {
          setTransferSaving(false);
          setTransferErr('Selecione uma fila válida.');
          setTransferErrLink(null);
          return;
        }
        payload = {
          iduser_destino: dest,
          motivo: transferMotivo.trim(),
        };
        if (transferFila) {
          payload.fila_suporte = transferFila;
        }
      } else {
        if (!dest) {
          setTransferSaving(false);
          setTransferErr('Selecione o técnico de destino.');
          setTransferErrLink(null);
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
      let msg = API_ERR_TRANSFER[code] || r.message || code || 'Falha ao transferir.';
      if (code === 'destino_sem_vinculo_fila') {
        if (r.message && String(r.message).trim() !== '') {
          msg = String(r.message).trim();
        }
        if (r.queue_name && !msg.includes(String(r.queue_name))) {
          msg += ` Fila: ${r.queue_name}.`;
        }
        setTransferErr(msg);
        const href = effectiveBoot?.paths?.queuesAdmin;
        setTransferErrLink(href ? { href, label: 'Abrir Filas / técnicos' } : null);
      } else {
        setTransferErr(msg);
        setTransferErrLink(null);
      }
      return;
    }
    setTransferErrLink(null);
    setTransferOpen(false);
    setTransferOkHint('Transferência registrada.');
    window.setTimeout(() => setTransferOkHint(''), 4000);
    await reload();
  };

  const handleStartAtendimento = async (ticket) => {
    const id = Number(ticket.id);
    if (!ticketHasValidTecnico(ticket) || !ticketHasValidQueueForStart(ticket, queuesRelacional)) {
      window.alert(
        queuesRelacional
          ? 'Escolha e salve a fila no dropdown “Fila…” (além do técnico). Sem fila válida gravada no ticket o servidor não inicia o atendimento.'
          : 'Salve o técnico e a fila antes de iniciar o atendimento.',
      );
      return;
    }
    setStartBusyId(id);
    lockTicketStatusInteraction(id, 1500);
    try {
      const execTransition = Array.isArray(ticket?.workflow?.allowedTransitions)
        ? ticket.workflow.allowedTransitions.find((tr) => {
            const c = normalizeWorkflowCode(tr?.codigo);
            return c === 'emandamento' || c === 'em_execucao' || c === 'execucao' || c === 'em_andamento';
          })
        : null;
      const hasWorkflow = ticket?.workflow?.enabled === true && ticket?.workflow?.current?.id != null;
      const r = hasWorkflow && execTransition
        ? await patchTicketStatus(
            id,
            {
              workflow_state_id: Number(execTransition.id),
              status: String(execTransition.label || 'Em execução'),
            },
            { source: 'start' },
          )
        : await postStartTicket(id);
      if (!r.ok) {
        const code = r.error;
        const friendly = API_ERR_START[code];
        const rawMsg = r.message ? String(r.message).trim() : '';
        const detail =
          rawMsg && rawMsg !== friendly ? ` (${rawMsg})` : '';
        window.alert((friendly || code || 'Não foi possível iniciar o atendimento.') + detail);
        await reload();
        return;
      }
      if (r.ticket) {
        mergeTicketInGroups(id, r.ticket);
      }
      setTransferOkHint('Atendimento iniciado.');
      window.setTimeout(() => setTransferOkHint(''), 4000);
      if (!r.ticket) await reload();
    } finally {
      setStartBusyId(null);
    }
  };

  const handleServicedeskAwaitCliente = async (ticket, action) => {
    const id = Number(ticket.id);
    if (isTicketStatusInteractionLocked(id)) return;
    setStatusBusyKey(`await-${id}`);
    try {
      const body =
        action && action.workflowStateId != null && Number(action.workflowStateId) > 0
          ? { workflow_state_id: Number(action.workflowStateId) }
          : {};
      const r = await postServicedeskAwaitCliente(id, body);
      if (!r.ok) {
        window.alert(r.message || r.error || 'Não foi possível pausar para aguardando cliente.');
        return;
      }
      if (r.ticket) mergeTicketInGroups(id, r.ticket);
      lockTicketStatusInteraction(id, 900);
      setTransferOkHint('Ticket em espera; SLA pausado quando aplicável.');
      window.setTimeout(() => setTransferOkHint(''), 4000);
      if (!r.ticket) await reload();
    } finally {
      setStatusBusyKey(null);
    }
  };

  const handleServicedeskEscalateLevel = async (ticket) => {
    const id = Number(ticket.id);
    if (isTicketStatusInteractionLocked(id)) return;
    const ok = window.confirm(
      'Subir nível (N1→N2 ou N2→N3)? O ticket volta para pendente sem técnico responsável na fila de destino.',
    );
    if (!ok) return;
    setStatusBusyKey(`escalate-${id}`);
    try {
      const r = await postServicedeskEscalateLevel(id, { motivo: 'Escalonamento de nível (Service Desk)' });
      if (!r.ok) {
        window.alert(r.message || r.error || 'Não foi possível escalar o nível.');
        return;
      }
      if (r.ticket) mergeTicketInGroups(id, r.ticket);
      lockTicketStatusInteraction(id, 900);
      setTransferOkHint('Nível escalonado; ticket na fila de destino.');
      window.setTimeout(() => setTransferOkHint(''), 4000);
      if (!r.ticket) await reload();
    } finally {
      setStatusBusyKey(null);
    }
  };

  const handleAlterarSituacao = async (ticket, req) => {
    const id = Number(ticket.id);
    if (isTicketStatusInteractionLocked(id)) {
      return;
    }
    const situacaoDestino = req && typeof req === 'object' ? req.situacaoDestino : req;
    const workflowStateId = req && typeof req === 'object' ? req.workflowStateId : null;
    const statusLabelText = req && typeof req === 'object' ? req.statusLabel : null;
    const sk = `${id}-${workflowStateId || situacaoDestino}`;
    setStatusBusyKey(sk);
    try {
      const r = workflowStateId != null && Number(workflowStateId) > 0
        ? await patchTicketStatus(
            id,
            {
              workflow_state_id: Number(workflowStateId),
              status: String(statusLabelText || '—'),
            },
            { source: 'menu' },
          )
        : await postAlterarSituacao(id, situacaoDestino);
      if (!r.ok) {
        window.alert(r.message || r.error || 'Não foi possível alterar o status do ticket.');
        return;
      }
      if (r.ticket) {
        mergeTicketInGroups(id, r.ticket);
      }
      lockTicketStatusInteraction(id, 700);
      setTransferOkHint('Situação atualizada.');
      window.setTimeout(() => setTransferOkHint(''), 4000);
      if (!r.ticket) await reload();
    } finally {
      setStatusBusyKey(null);
    }
  };

  const colCount = useMemo(() => {
    let n = wfEnabled ? 11 : 9;
    if (inlineAssignment) {
      n += 2;
    }
    return n;
  }, [wfEnabled, inlineAssignment]);

  const isSD = Boolean(boot?.servicedesk);

  /** Sticky no scroll interno (div overflow-auto). Fundo opaco + sombra; z-[2] fica abaixo do menu de ações (z-60). */
  const techFilaThSticky =
    'sticky top-0 z-[2] bg-[var(--pgm-bg-elevated)] shadow-[0_1px_0_0_var(--pgm-border-subtle)]';

  /** Barra de filtros: sem w-full para permitir flex-wrap na largura do painel (evita scroll horizontal na barra). */
  const sdFieldToolbar =
    'h-8 min-w-0 rounded-lg border border-[var(--pgm-border)] bg-[var(--pgm-bg-elevated)] px-2.5 text-sm text-[var(--pgm-text)] outline-none transition placeholder:text-[var(--pgm-text-muted)] focus:border-[var(--pgm-primary)] focus:shadow-[0_0_0_3px_rgba(29,158,117,0.20),var(--pgm-shadow-glow)]';

  /** min-h-0: permite flex-1 encolher e ativar overflow-auto interno (sticky no thead). */
  const filaCardClass = embedded && isSD
    ? 'pgm-card flex w-full min-h-0 min-w-0 max-w-full flex-1 flex-col overflow-x-clip overflow-y-hidden'
    : embedded
      ? 'flex w-full min-h-0 min-w-0 max-w-full flex-1 flex-col overflow-x-clip overflow-y-hidden rounded-xl border border-[var(--pgm-border)] bg-[var(--pgm-bg-surface)] shadow-[0_4px_20px_rgba(0,0,0,0.08)]'
      : 'w-full min-w-0 max-w-full rounded-[28px] border border-[var(--pgm-border)] bg-[var(--pgm-bg-surface)] p-5 shadow-[var(--pgm-shadow-md)]';

  const filaToolbar = (className) =>
    [className, embedded && isSD ? 'filter-toolbar' : ''].filter(Boolean).join(' ');

  const tableSection = (
    <section
      className={filaCardClass}
    >
      {loadError ? (
        <div className="border-b border-[var(--pgm-badge-amber-ring,rgba(210,153,34,0.30))] bg-[var(--pgm-badge-amber-bg,rgba(210,153,34,0.14))] px-3 py-2.5 text-sm text-[var(--pgm-badge-amber-text,#f0c060)]">
          <span className="font-semibold">Lista não carregou: </span>
          {loadError}
        </div>
      ) : null}
      {slaDashboard ? (
        <div className="border-b border-[var(--pgm-border-subtle)] bg-[var(--pgm-bg-elevated)] px-3 py-3">
          <div className="flex flex-wrap items-center gap-2">
            <span className="text-[0.68rem] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted)]">
              SLA por etapa
            </span>
            <span className="text-[10px] text-[var(--pgm-text-muted)]">
              atualização {new Date(slaDashboard.gerado_em || Date.now()).toLocaleTimeString('pt-BR')}
            </span>
          </div>
          <div className="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-3">
            <div className="rounded-md border border-[rgba(220,51,15,0.3)] bg-[rgba(220,51,15,0.12)] px-2.5 py-2 text-[0.75rem] text-[var(--pgm-badge-red-text,#ff9492)]">
              <div className="text-[10px] uppercase tracking-[0.06em]">SLA estourado</div>
              <div className="text-[1rem] font-bold">{Number(slaStateSummary.overdue || 0)}</div>
            </div>
            <div className="rounded-md border border-[rgba(243,156,18,0.3)] bg-[rgba(243,156,18,0.12)] px-2.5 py-2 text-[0.75rem] text-[var(--pgm-badge-amber-text,#f0c060)]">
              <div className="text-[10px] uppercase tracking-[0.06em]">Próximos do limite</div>
              <div className="text-[1rem] font-bold">{Number(slaStateSummary.near_due || 0)}</div>
            </div>
            <div className="rounded-md border border-[rgba(39,174,96,0.3)] bg-[rgba(39,174,96,0.12)] px-2.5 py-2 text-[0.75rem] text-[var(--pgm-badge-green-text,#7ee787)]">
              <div className="text-[10px] uppercase tracking-[0.06em]">Pausados</div>
              <div className="text-[1rem] font-bold">{Number(slaStateSummary.paused || 0)}</div>
            </div>
          </div>
          {slaDashboard?.sla_operational_kpis ? (
            <div className="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-4">
              {[
                ['Escalonados hoje', slaDashboard.sla_operational_kpis.escalados_hoje],
                ['Tickets críticos', slaDashboard.sla_operational_kpis.criticos_abertos],
                ['Sem técnico', slaDashboard.sla_operational_kpis.sem_tecnico],
                ['Aguard. cliente', slaDashboard.sla_operational_kpis.aguardando_cliente],
              ].map(([label, val]) => (
                <div
                  key={label}
                  className="rounded-md border border-[var(--pgm-border-subtle)] bg-[var(--pgm-bg-surface)] px-2.5 py-2 text-[0.72rem] text-[var(--pgm-text-secondary)]"
                >
                  <div className="text-[9px] font-semibold uppercase tracking-[0.06em] text-[var(--pgm-text-muted)]">{label}</div>
                  <div className="text-[0.95rem] font-bold tabular-nums text-[var(--pgm-text)]">{Number(val || 0)}</div>
                </div>
              ))}
            </div>
          ) : null}
          {effectiveBoot?.paths?.workflowSlaAdmin && Number(effectiveBoot?.role) === 0 ? (
            <div className="mt-2">
              <a
                href={effectiveBoot.paths.workflowSlaAdmin}
                className="inline-flex items-center gap-1 text-[11px] font-semibold text-[var(--pgm-primary-hover)] no-underline hover:underline"
              >
                Configurar SLA
                <span aria-hidden>→</span>
              </a>
            </div>
          ) : null}
          <div className="mt-2 flex flex-wrap gap-1.5">
            {[
              { key: 'all', label: 'Todos' },
              { key: 'overdue', label: 'Estourado' },
              { key: 'near_due', label: 'Próx. limite' },
              { key: 'paused', label: 'Pausado' },
            ].map((f) => (
              <button
                key={f.key}
                type="button"
                onClick={() => setSlaDashboardFilter(f.key)}
                className={`rounded-full border px-2 py-0.5 text-[10px] font-semibold ${
                  slaDashboardFilter === f.key
                    ? 'border-[var(--pgm-primary)] bg-[rgba(29,158,117,0.18)] text-[var(--pgm-primary-hover)]'
                    : 'border-[var(--pgm-border)] bg-transparent text-[var(--pgm-text-muted)]'
                }`}
              >
                {f.label}
              </button>
            ))}
          </div>
          <div className="mt-2 grid grid-cols-1 gap-2 lg:grid-cols-2">
            <div className="rounded-md border border-[var(--pgm-border-subtle)] bg-[var(--pgm-bg-surface)] p-2">
              <div className="mb-1 text-[10px] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted)]">
                Alertas ativos
              </div>
              {slaAlertRows.length === 0 ? (
                <div className="text-[11px] text-[var(--pgm-text-muted)]">Sem alertas no filtro.</div>
              ) : (
                <ul className="space-y-1.5">
                  {slaAlertRows.slice(0, 6).map((row) => {
                    const tone =
                      row._type === 'overdue'
                        ? 'text-[var(--pgm-badge-red-text,#ff9492)]'
                        : row._type === 'near_due'
                          ? 'text-[var(--pgm-badge-amber-text,#f0c060)]'
                          : 'text-[var(--pgm-badge-green-text,#7ee787)]';
                    const label = row._type === 'overdue' ? 'SLA estourado' : row._type === 'near_due' ? 'Próx. limite' : 'Pausado';
                    return (
                      <li key={`${row._type}-${row.id}`} className="flex items-center justify-between gap-2 text-[11px]">
                        <span className="font-mono text-[var(--pgm-text-muted)]">#{row.id}</span>
                        <span className={`font-semibold ${tone}`}>{label}</span>
                        <span className="truncate text-[var(--pgm-text-muted)]">
                          {row.data_limite_resolucao
                            ? new Date(row.data_limite_resolucao).toLocaleString('pt-BR')
                            : 'sem prazo'}
                        </span>
                      </li>
                    );
                  })}
                </ul>
              )}
            </div>
            <div className="rounded-md border border-[var(--pgm-border-subtle)] bg-[var(--pgm-bg-surface)] p-2">
              <div className="mb-1 text-[10px] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted)]">
                Tempo médio por etapa
              </div>
              {Array.isArray(slaStateSummary.avg_seconds_by_state) && slaStateSummary.avg_seconds_by_state.length > 0 ? (
                <ul className="space-y-1">
                  {slaStateSummary.avg_seconds_by_state.slice(0, 6).map((item) => (
                    <li key={`avg-${item.workflow_state_id}`} className="flex items-center justify-between gap-2 text-[11px]">
                      <span className="truncate text-[var(--pgm-text-secondary)]">{item.label || `Estado #${item.workflow_state_id}`}</span>
                      <span className="font-semibold text-[var(--pgm-primary-hover)]">{formatSecondsCompact(item.avg_seconds)}</span>
                    </li>
                  ))}
                </ul>
              ) : (
                <div className="text-[11px] text-[var(--pgm-text-muted)]">Sem dados de média por etapa.</div>
              )}
            </div>
          </div>
        </div>
      ) : null}
      {inlineAssignment && inlinePatchError.msg ? (
        <div
          className="border-b border-[var(--pgm-border)] bg-[var(--pgm-bg-elevated)] px-3 py-2 text-sm text-[var(--pgm-badge-amber-text,#f0c060)]"
          role="alert"
        >
          <span className="font-semibold">Ticket #{inlinePatchError.id}: </span>
          <span>{inlinePatchError.msg}</span>
          {inlinePatchError.linkUrl ? (
            <>
              {' '}
              <a
                href={inlinePatchError.linkUrl}
                className="font-semibold text-[var(--pgm-primary-hover)] underline"
              >
                {inlinePatchError.linkLabel || 'Abrir gestão de filas'}
              </a>
            </>
          ) : null}
        </div>
      ) : null}
      {!embedded ? (
        <div className="flex flex-col gap-3 border-b border-[var(--pgm-border-subtle)] bg-[var(--pgm-bg-elevated)] p-3 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <h3 className="text-[0.95rem] font-bold text-[var(--pgm-text)]">Fila</h3>
            <p className="text-[0.8rem] text-[var(--pgm-text-muted)]">
              {totalTodos} ticket(s) na empresa · integração JSON ativa quando embutido no CakePHP
            </p>
          </div>
          <div className="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
            <input
              value={q}
              onChange={(e) => setQ(e.target.value)}
              placeholder="Buscar nº, cliente ou assunto"
              className="h-8 w-full min-w-[180px] rounded-md border border-[var(--pgm-border)] bg-[var(--pgm-bg-raised)] px-2.5 text-[0.8rem] text-[var(--pgm-text)] outline-none transition placeholder:text-[var(--pgm-text-muted)] focus:border-[var(--pgm-primary)] focus:shadow-[0_0_0_3px_rgba(29,158,117,0.20),var(--pgm-shadow-glow)] sm:max-w-xs"
            />
            <select
              value={filtroStatus}
              onChange={(e) => setFiltroStatus(e.target.value)}
              className="h-8 rounded-md border border-[var(--pgm-border)] bg-[var(--pgm-bg-raised)] px-2.5 text-[0.8rem] text-[var(--pgm-text)] outline-none transition focus:border-[var(--pgm-primary)] focus:shadow-[0_0_0_3px_rgba(29,158,117,0.20),var(--pgm-shadow-glow)]"
            >
              <option value="todos">Todos</option>
              <option value="ativos">Aguardando + Em execução</option>
              <option value="pendente">Pendente</option>
              <option value="execucao">Em execução</option>
              <option value="resolvido">Resolvidos</option>
              <option value="fechados">Cancelados / fechados</option>
            </select>
            {filtroStatus === 'ativos' ? (
              <label className="flex cursor-pointer items-center gap-2 whitespace-nowrap text-[0.78rem] text-[var(--pgm-text-secondary)]">
                <input
                  type="checkbox"
                  className="rounded border-[var(--pgm-border)]"
                  checked={sortBySlaUrgency}
                  onChange={(e) => setSortBySlaUrgency(e.target.checked)}
                />
                Ordenar por urgência SLA
              </label>
            ) : null}
          </div>
        </div>
      ) : wfEnabled ? (
        <div
          className={filaToolbar(
            'flex min-w-0 max-w-full flex-wrap items-center gap-x-2 gap-y-2 border-b border-[var(--pgm-border-subtle)] bg-[var(--pgm-bg-elevated)] px-3 py-2.5',
          )}
        >
          <span
            className="shrink-0 whitespace-nowrap text-xs tabular-nums text-[var(--pgm-text-muted)]"
            title="Tickets na empresa (todos os status)"
          >
            <span className="font-semibold text-[var(--pgm-text)]">{totalTodos}</span> na empresa
          </span>
          {isSD && embedded ? <span className="filter-separator" aria-hidden="true" /> : null}
          <input
            id="sd-tech-q"
            value={q}
            onChange={(e) => setQ(e.target.value)}
            placeholder="Buscar nº, cliente ou assunto"
            aria-label="Buscar na lista"
            className={`${sdFieldToolbar} min-w-[8rem] flex-[1_1_12rem] max-w-full`}
          />
          <select
            id="sd-tech-status"
            value={filtroStatus}
            onChange={(e) => setFiltroStatus(e.target.value)}
            aria-label="Situação"
            className={`${sdFieldToolbar} min-w-[6.5rem] flex-[1_1_9rem] max-w-[min(100%,13.5rem)] cursor-pointer`}
          >
            <option value="todos">Todos</option>
            <option value="ativos">Aguardando + Em execução</option>
            <option value="pendente">Pendente</option>
            <option value="execucao">Em execução</option>
            <option value="resolvido">Resolvidos</option>
            <option value="fechados">Cancelados / fechados</option>
          </select>
          {filtroStatus === 'ativos' ? (
            <label className="flex cursor-pointer items-center gap-2 whitespace-nowrap text-xs text-[var(--pgm-text-secondary)]">
              <input
                type="checkbox"
                className="rounded border-[var(--pgm-border)]"
                checked={sortBySlaUrgency}
                onChange={(e) => setSortBySlaUrgency(e.target.checked)}
              />
              Urgência SLA
            </label>
          ) : null}
          <select
            id="sd-wf-fila"
            value={filaSuporte}
            onChange={(e) => setFilaSuporte(e.target.value)}
            aria-label="Fila de suporte"
            className={`${sdFieldToolbar} min-w-[6.5rem] flex-[1_1_9rem] max-w-[min(100%,12rem)] cursor-pointer`}
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
            className={`${sdFieldToolbar} min-w-[5.5rem] flex-[1_1_7rem] max-w-[min(100%,9rem)] cursor-pointer`}
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
            className={`${sdFieldToolbar} min-w-[6.5rem] flex-[1_1_9rem] max-w-[min(100%,12rem)] cursor-pointer`}
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
        <div
          className={filaToolbar(
            'flex min-w-0 max-w-full flex-wrap items-center gap-x-2 gap-y-2 border-b border-[var(--pgm-border-subtle)] bg-[var(--pgm-bg-elevated)] px-3 py-2.5',
          )}
        >
          <span
            className="shrink-0 whitespace-nowrap text-xs tabular-nums text-[var(--pgm-text-muted)]"
            title="Tickets na empresa (todos os status)"
          >
            <span className="font-semibold text-[var(--pgm-text)]">{totalTodos}</span> na empresa
          </span>
          {isSD && embedded ? <span className="filter-separator" aria-hidden="true" /> : null}
          <input
            id="sd-tech-q-legacy"
            value={q}
            onChange={(e) => setQ(e.target.value)}
            placeholder="Buscar nº, cliente ou assunto"
            aria-label="Buscar na lista"
            className={`${sdFieldToolbar} min-w-[8rem] flex-[1_1_12rem] max-w-full`}
          />
          <select
            id="sd-tech-status-legacy"
            value={filtroStatus}
            onChange={(e) => setFiltroStatus(e.target.value)}
            aria-label="Situação"
            className={`${sdFieldToolbar} min-w-[6.5rem] flex-[1_1_9rem] max-w-[min(100%,13.5rem)] cursor-pointer`}
          >
            <option value="todos">Todos</option>
            <option value="ativos">Aguardando + Em execução</option>
            <option value="pendente">Pendente</option>
            <option value="execucao">Em execução</option>
            <option value="resolvido">Resolvidos</option>
            <option value="fechados">Cancelados / fechados</option>
          </select>
          {filtroStatus === 'ativos' ? (
            <label className="flex cursor-pointer items-center gap-2 whitespace-nowrap text-xs text-[var(--pgm-text-secondary)]">
              <input
                type="checkbox"
                className="rounded border-[var(--pgm-border)]"
                checked={sortBySlaUrgency}
                onChange={(e) => setSortBySlaUrgency(e.target.checked)}
              />
              Urgência SLA
            </label>
          ) : null}
        </div>
      )}

      {transferOkHint ? (
        <div
          className="border-b border-[var(--pgm-badge-green-ring,rgba(63,185,80,0.25))] bg-[var(--pgm-badge-green-bg,rgba(63,185,80,0.14))] px-3 py-2 text-sm text-[var(--pgm-badge-green-text,#7ee787)]"
          role="status"
          aria-live="polite"
        >
          {transferOkHint}
        </div>
      ) : null}

      {wfEnabled ? (
        !embedded ? (
          <div className="flex flex-col gap-2 border-b border-[var(--pgm-border-subtle)] bg-[var(--pgm-bg-elevated)] p-3 sm:flex-row sm:flex-wrap sm:items-center">
            <select
              value={filaSuporte}
              onChange={(e) => setFilaSuporte(e.target.value)}
              className="h-8 rounded-md border border-[var(--pgm-border)] bg-[var(--pgm-bg-raised)] px-2 text-[0.8rem] text-[var(--pgm-text)] outline-none transition focus:border-[var(--pgm-primary)] focus:shadow-[0_0_0_3px_rgba(29,158,117,0.20),var(--pgm-shadow-glow)]"
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
              className="h-8 rounded-md border border-[var(--pgm-border)] bg-[var(--pgm-bg-raised)] px-2 text-[0.8rem] text-[var(--pgm-text)] outline-none transition focus:border-[var(--pgm-primary)] focus:shadow-[0_0_0_3px_rgba(29,158,117,0.20),var(--pgm-shadow-glow)]"
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
              className="h-8 min-w-[10rem] rounded-md border border-[var(--pgm-border)] bg-[var(--pgm-bg-raised)] px-2 text-[0.8rem] text-[var(--pgm-text)] outline-none transition focus:border-[var(--pgm-primary)] focus:shadow-[0_0_0_3px_rgba(29,158,117,0.20),var(--pgm-shadow-glow)]"
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
            ? 'flex min-h-0 min-w-0 max-w-full flex-1 flex-col overflow-hidden'
            : 'mt-5 min-w-0 max-w-full overflow-x-clip rounded-2xl border border-[var(--pgm-border-subtle)]'
        }
      >
        <div
          ref={sdTableScrollRef}
          className="min-h-0 min-w-0 max-w-full flex-1 overflow-x-auto overflow-y-auto"
        >
          <table
            className={`min-w-full text-[0.8125rem] ${embedded && isSD ? 'pgm-table' : ''} ${embedded && !isSD ? 'divide-y divide-[var(--pgm-border)]' : ''}`}
          >
            <thead className="bg-[var(--pgm-bg-elevated)] text-left text-[var(--pgm-text-muted)]">
              <tr>
                <th
                  className={`${techFilaThSticky} px-3 py-2 text-left text-[0.65rem] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted)]`}
                >
                  Ticket
                </th>
                <th
                  className={`${techFilaThSticky} max-w-[7rem] px-3 py-2 text-left text-[0.65rem] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted)]`}
                >
                  Autor
                </th>
                <th
                  className={`${techFilaThSticky} whitespace-nowrap px-3 py-2 text-left text-[0.65rem] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted)]`}
                >
                  Data
                </th>
                <th
                  className={`${techFilaThSticky} min-w-[8rem] px-3 py-2 text-left text-[0.65rem] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted)] sm:min-w-[10rem]`}
                >
                  Assunto
                </th>
                <th
                  className={`${techFilaThSticky} w-[4.75rem] min-w-[4.75rem] px-2 py-2 text-center text-[0.65rem] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted)]`}
                >
                  SLA
                </th>
                {inlineAssignment ? (
                  <th
                    className={`${techFilaThSticky} max-w-[6rem] px-2 py-2 text-left text-[0.65rem] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted)]`}
                  >
                    Prioridade
                  </th>
                ) : null}
                <th
                  className={`${techFilaThSticky} whitespace-nowrap px-3 py-2 text-left text-[0.65rem] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted)]`}
                >
                  Status
                </th>
                {wfEnabled ? (
                  <>
                    <th
                      className={`${techFilaThSticky} max-w-[8rem] px-3 py-2 text-left text-[0.65rem] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted)]`}
                    >
                      Técnico
                    </th>
                    <th
                      className={`${techFilaThSticky} max-w-[9rem] px-3 py-2 text-left text-[0.65rem] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted)]`}
                    >
                      Fila
                    </th>
                    <th
                      className={`${techFilaThSticky} whitespace-nowrap px-3 py-2 text-left text-[0.65rem] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted)]`}
                    >
                      Nível
                    </th>
                  </>
                ) : null}
                {!wfEnabled ? (
                  <th
                    className={`${techFilaThSticky} max-w-[7rem] px-3 py-2 text-left text-[0.65rem] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted)]`}
                  >
                    Técnico
                  </th>
                ) : null}
                {inlineAssignment ? (
                  <th
                    className={`${techFilaThSticky} whitespace-nowrap px-2 py-2 text-right text-[0.65rem] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted)]`}
                  >
                    Tempo
                  </th>
                ) : null}
                <th
                  className={`${techFilaThSticky} max-w-[8rem] px-3 py-2 text-left text-[0.65rem] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted)]`}
                >
                  Cliente
                </th>
                <th
                  className={`${techFilaThSticky} w-[7.25rem] min-w-[7.25rem] px-3 py-2 text-right text-[0.65rem] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted)]`}
                >
                  Ações
                </th>
              </tr>
            </thead>
            <tbody
              className={
                embedded && isSD
                  ? 'bg-[var(--pgm-bg-surface)]'
                  : embedded
                    ? 'divide-y divide-[var(--pgm-border)] bg-[var(--pgm-bg-surface)]'
                    : ''
              }
            >
              {rows.length === 0 ? (
                <tr>
                  <td
                    colSpan={colCount}
                    className="px-4 py-8 text-center text-[var(--pgm-text-muted)]"
                  >
                    {loadError
                      ? 'Ajuste o problema indicado acima e atualize a página.'
                      : 'Nenhum ticket neste filtro.'}
                  </td>
                </tr>
              ) : (
                rows.map((ticket) => {
                  const st = effectiveStatus(ticket).label;
                  const assuntoLinha = stripHtml(ticket.assunto_nome ?? 'Não informado');
                  return (
                    <tr
                      key={ticket.id}
                      className={`${techRowHighlightClass(ticket, isSD)} cursor-pointer hover:bg-[var(--pgm-bg-elevated)]`}
                    >
                      <td className="px-3 py-2 font-semibold">
                        {ticket.urls?.edit ? (
                          <a
                            className="tickets-sd-ticket-link font-mono text-[11.5px] font-semibold text-[var(--pgm-primary-hover)] no-underline transition hover:text-[var(--pgm-primary)] hover:underline"
                            href={ticket.urls.edit}
                          >
                            #{ticket.id}
                          </a>
                        ) : (
                          <Link
                            className="tickets-sd-ticket-link font-mono text-[11.5px] font-semibold text-[var(--pgm-primary-hover)] no-underline transition hover:text-[var(--pgm-primary)] hover:underline"
                            to={`/cliente/ticket/${ticket.id}`}
                          >
                            #{ticket.id}
                          </Link>
                        )}
                      </td>
                      <td
                        className="max-w-[7rem] truncate px-3 py-2 text-[0.75rem] text-[var(--pgm-text-muted)]"
                        title={ticket.autor || ''}
                      >
                        {ticket.autor || '—'}
                      </td>
                      <td className="whitespace-nowrap px-3 py-2 font-mono text-[0.75rem] text-[var(--pgm-text-muted)]">
                        {ticket.created || ticket.atualizado || '—'}
                      </td>
                      <td className="max-w-[14rem] px-3 py-2 sm:max-w-xs">
                        <div
                          className="truncate font-medium text-[var(--pgm-text)]"
                          title={assuntoLinha}
                        >
                          {assuntoLinha}
                        </div>
                        {ticket.solicitacaoPreview ? (
                          <div
                            className="line-clamp-1 text-[11px] leading-tight text-[var(--pgm-text-muted)]"
                            title={ticket.solicitacaoPreview}
                          >
                            {ticket.solicitacaoPreview}
                          </div>
                        ) : null}
                      </td>
                      <td className="w-[4.75rem] min-w-[4.75rem] px-2 py-2 text-center align-middle">
                        <SlaUrgencyCell ticket={ticket} />
                      </td>
                      {inlineAssignment ? (
                        <TicketsServicedeskInlineRow
                          ticket={ticket}
                          wfEnabled={wfEnabled}
                          queuesRelacional={queuesRelacional}
                          workflowFilas={filasMeta}
                          tecnicos={tecnicosOpcoes}
                          ticketStatus={effectiveBoot?.ticketStatus}
                          situacaoExecCode={situacaoExecCode}
                          effectiveStatus={effectiveStatus(ticket)}
                          onMergeTicket={mergeTicketInGroups}
                          patchBusyId={patchBusyId}
                          setPatchBusyId={setPatchBusyId}
                          statusInteractionLocked={isTicketStatusInteractionLocked(ticket.id)}
                          onPatchError={onInlinePatchError}
                          queuesAdminUrl={effectiveBoot?.paths?.queuesAdmin || ''}
                        />
                      ) : (
                        <>
                          <td className="whitespace-nowrap px-3 py-2">
                            <span
                              className={`inline-flex max-w-[10rem] items-center gap-1.5 truncate rounded-full px-2 py-0.5 text-[10px] font-semibold leading-tight sm:max-w-[12rem] sm:text-[11px] ${badgeClass(
                                servicedeskStatusTypeFromTicket(ticket, st),
                                embedded,
                                isSD
                              )}`}
                              title={st}
                            >
                              <StatusDot type={servicedeskStatusTypeFromTicket(ticket, st)} />
                              <span className="truncate">{st}</span>
                            </span>
                          </td>
                          {wfEnabled ? (
                            <>
                              <td className="max-w-[8rem] truncate px-3 py-2 text-[var(--pgm-text-secondary)]" title={ticket.tecnicos || ''}>
                                {ticket.tecnicos && ticket.tecnicos !== '—' ? ticket.tecnicos : '—'}
                              </td>
                              <td
                                className="max-w-[9rem] truncate px-3 py-2 text-[var(--pgm-text-muted)]"
                                title={ticket.filaLabel || ''}
                              >
                                <span className="line-clamp-2">{ticket.filaLabel || '—'}</span>
                                {ticket.transferido ? (
                                  <span className="mt-0.5 block text-[10px] font-semibold text-[var(--pgm-badge-amber-text,#f0c060)]">Transferido</span>
                                ) : null}
                              </td>
                              <td className="whitespace-nowrap px-3 py-2 text-[var(--pgm-text-muted)]">
                                {ticket.supportLevelLabel
                                  ? ticket.supportLevelLabel
                                  : ticket.nivelAtendimento != null
                                    ? `N${ticket.nivelAtendimento}`
                                    : '—'}
                              </td>
                            </>
                          ) : null}
                          {!wfEnabled ? (
                            <td className="max-w-[7rem] truncate px-3 py-2 text-[var(--pgm-text-secondary)]" title={ticket.tecnicos || ''}>
                              {ticket.tecnicos && ticket.tecnicos !== '—' ? ticket.tecnicos : '—'}
                            </td>
                          ) : null}
                        </>
                      )}
                      <td
                        className="max-w-[8rem] truncate px-3 py-2 text-[var(--pgm-text-muted)]"
                        title={ticket.cliente || ''}
                      >
                        {ticket.cliente || '—'}
                      </td>
                      <td className="px-3 py-2 text-right">
                        <TicketActionsMenu
                          ticket={ticket}
                          acoes={ensureCoreActions(ticket, { hideTransfer: inlineAssignment, queuesRelacional })}
                          openTransfer={openTransfer}
                          handleStartAtendimento={handleStartAtendimento}
                          startBusyId={startBusyId}
                          handleAlterarSituacao={handleAlterarSituacao}
                          statusBusyKey={statusBusyKey}
                          handleServicedeskAwaitCliente={handleServicedeskAwaitCliente}
                          handleServicedeskEscalateLevel={handleServicedeskEscalateLevel}
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
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/55 backdrop-blur-[4px] p-4" role="dialog" aria-modal="true">
          <div className="max-h-[90vh] w-full max-w-[520px] overflow-hidden overflow-y-auto rounded-[var(--pgm-radius-2xl,20px)] border border-[var(--pgm-border-subtle)] bg-[var(--pgm-bg-surface)] shadow-[var(--pgm-shadow-xl)]" style={{animation:'pgm-modal-in 0.3s cubic-bezier(0.16,1,0.3,1)', borderTop:'2px solid transparent', borderImage:'linear-gradient(90deg,var(--pgm-primary,#1d9e75),#2ec4a0,var(--pgm-primary,#1d9e75)) 1'}}>
            <div className="flex items-start justify-between border-b border-[var(--pgm-border-subtle)] bg-[var(--pgm-bg-elevated)] px-5 py-4">
              <div>
                <h4 className="text-[0.95rem] font-bold text-[var(--pgm-text)]">
                  Transferir ticket #{transferTicket?.id}
                </h4>
                <p className="mt-1 text-[0.8rem] text-[var(--pgm-text-muted)]">
              {queuesRelacional
                ? 'Escolha a fila da mesma empresa. Depois defina se o ticket fica só na fila (sem responsável) ou se já vai para um técnico.'
                : wfEnabled
                  ? 'Escolha se deseja só mudar a fila de suporte (sem trocar o responsável) ou encaminhar a um técnico. O histórico registra motivo e data/hora.'
                  : 'O histórico registrará técnico anterior, novo técnico, data/hora e motivo.'}
              </p>
              </div>
              <button
                type="button"
                className="flex h-7 w-7 shrink-0 items-center justify-center rounded-[var(--pgm-radius-sm,6px)] bg-transparent text-[var(--pgm-text-muted)] transition hover:bg-[var(--pgm-bg-overlay)] hover:text-[var(--pgm-text)]"
                onClick={() => setTransferOpen(false)}
                aria-label="Fechar"
              >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round"><path d="M18 6L6 18M6 6l12 12" /></svg>
              </button>
            </div>
            <div className="space-y-3 p-5">
              {queuesRelacional ? (
                <>
                  {transferQueuesErr ? <p className="text-sm text-[var(--pgm-badge-amber-text,#f0c060)]">{transferQueuesErr}</p> : null}
                  <label className="block text-[0.7rem] font-semibold uppercase tracking-[0.06em] text-[var(--pgm-text-muted)]">
                    Fila de destino
                    <select
                      value={transferQueueId}
                      onChange={(e) => setTransferQueueId(e.target.value)}
                      className="mt-1 w-full rounded-md border border-[var(--pgm-border)] bg-[var(--pgm-bg-raised)] px-3 py-2 text-[0.875rem] text-[var(--pgm-text)] outline-none transition focus:border-[var(--pgm-primary)] focus:shadow-[0_0_0_3px_rgba(29,158,117,0.20),var(--pgm-shadow-glow)]"
                    >
                      <option value="">Selecione…</option>
                      {transferQueues.map((fq) => (
                        <option key={fq.id} value={String(fq.id)}>
                          {fq.name || fq.codigo || `Fila #${fq.id}`}
                        </option>
                      ))}
                    </select>
                  </label>
                  <fieldset className="rounded-lg border border-[var(--pgm-border-subtle)] bg-[var(--pgm-bg-elevated)] p-3">
                    <legend className="px-1 text-[0.7rem] font-semibold uppercase tracking-[0.06em] text-[var(--pgm-text-muted)]">
                      Responsável
                    </legend>
                    <div className="mt-2 space-y-2">
                      <label className="flex cursor-pointer items-start gap-2 text-[0.8125rem] text-[var(--pgm-text-secondary)]">
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
                      <label className="flex cursor-pointer items-start gap-2 text-[0.8125rem] text-[var(--pgm-text-secondary)]">
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
                    <label className="block text-[0.7rem] font-semibold uppercase tracking-[0.06em] text-[var(--pgm-text-muted)]">
                      Técnico
                      <select
                        value={transferDest}
                        onChange={(e) => setTransferDest(e.target.value)}
                        className="mt-1 w-full rounded-md border border-[var(--pgm-border)] bg-[var(--pgm-bg-raised)] px-3 py-2 text-[0.875rem] text-[var(--pgm-text)] outline-none transition focus:border-[var(--pgm-primary)] focus:shadow-[0_0_0_3px_rgba(29,158,117,0.20),var(--pgm-shadow-glow)]"
                      >
                        <option value="">Selecione o técnico…</option>
                        {tecnicosModal.map((tm) => (
                          <option key={tm.id} value={String(tm.id)}>
                            {tecnicoLabel(tm)}
                          </option>
                        ))}
                      </select>
                      {transferQueueId && tecnicosModal.length === 0 ? (
                        <p className="mt-1 text-xs text-[var(--pgm-badge-amber-text,#f0c060)]">
                          Nenhum técnico listado: cadastre o vínculo à fila em Usuários ou ajuste o nível de suporte para cobrir esta fila.
                        </p>
                      ) : null}
                    </label>
                  ) : null}
                </>
              ) : (
                <>
                  {wfEnabled ? (
                    <label className="block text-[0.7rem] font-semibold uppercase tracking-[0.06em] text-[var(--pgm-text-muted)]">
                      Fila de destino
                      <select
                        value={transferFila}
                        onChange={(e) => setTransferFila(e.target.value)}
                        className="mt-1 w-full rounded-md border border-[var(--pgm-border)] bg-[var(--pgm-bg-raised)] px-3 py-2 text-[0.875rem] text-[var(--pgm-text)] outline-none transition focus:border-[var(--pgm-primary)] focus:shadow-[0_0_0_3px_rgba(29,158,117,0.20),var(--pgm-shadow-glow)]"
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
                    <fieldset className="rounded-lg border border-[var(--pgm-border-subtle)] bg-[var(--pgm-bg-elevated)] p-3">
                      <legend className="px-1 text-[0.7rem] font-semibold uppercase tracking-[0.06em] text-[var(--pgm-text-muted)]">
                        Responsável
                      </legend>
                      <div className="mt-2 space-y-2">
                        <label className="flex cursor-pointer items-start gap-2 text-[0.8125rem] text-[var(--pgm-text-secondary)]">
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
                        <label className="flex cursor-pointer items-start gap-2 text-[0.8125rem] text-[var(--pgm-text-secondary)]">
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
                    <label className="block text-[0.7rem] font-semibold uppercase tracking-[0.06em] text-[var(--pgm-text-muted)]">
                      {wfEnabled ? 'Técnico de destino' : 'Novo técnico responsável'}
                      <select
                        value={transferDest}
                        onChange={(e) => setTransferDest(e.target.value)}
                        className="mt-1 w-full rounded-md border border-[var(--pgm-border)] bg-[var(--pgm-bg-raised)] px-3 py-2 text-[0.875rem] text-[var(--pgm-text)] outline-none transition focus:border-[var(--pgm-primary)] focus:shadow-[0_0_0_3px_rgba(29,158,117,0.20),var(--pgm-shadow-glow)]"
                      >
                        <option value="">Selecione…</option>
                        {tecnicosOpcoes.map((t) => (
                          <option key={t.id} value={String(t.id)}>
                            {tecnicoLabel(t)}
                          </option>
                        ))}
                      </select>
                    </label>
                  )}
                </>
              )}
                    <label className="block text-[0.7rem] font-semibold uppercase tracking-[0.06em] text-[var(--pgm-text-muted)]">
                Motivo
                <textarea
                  value={transferMotivo}
                  onChange={(e) => setTransferMotivo(e.target.value)}
                  rows={3}
                  className="mt-1 w-full rounded-md border border-[var(--pgm-border)] bg-[var(--pgm-bg-raised)] px-3 py-2 text-[0.875rem] text-[var(--pgm-text)] outline-none transition focus:border-[var(--pgm-primary)] focus:shadow-[0_0_0_3px_rgba(29,158,117,0.20),var(--pgm-shadow-glow)]"
                  placeholder="Ex.: Escalação para N2 — necessidade de visita presencial."
                />
              </label>
              {transferErr ? (
                <div className="space-y-1 text-sm text-[var(--pgm-badge-red-text,#ff9492)]">
                  <p>{transferErr}</p>
                  {transferErrLink?.href ? (
                    <p>
                      <a
                        href={transferErrLink.href}
                        className="font-semibold text-[var(--pgm-primary-hover)] underline"
                      >
                        {transferErrLink.label || 'Abrir Filas / técnicos'}
                      </a>
                    </p>
                  ) : null}
                </div>
              ) : null}
            </div>
            <div className="flex flex-wrap justify-end gap-2 border-t border-[var(--pgm-border-subtle)] px-5 py-4">
              <button
                type="button"
                className="inline-flex items-center gap-1.5 rounded-lg border border-[var(--pgm-border)] bg-transparent px-4 py-2 text-[0.8125rem] font-medium text-[var(--pgm-text)] transition hover:bg-[var(--pgm-bg-overlay)] hover:border-[var(--pgm-border-strong)]"
                onClick={() => setTransferOpen(false)}
                disabled={transferSaving}
              >
                Cancelar
              </button>
              <button
                type="button"
                className="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-b from-[var(--pgm-primary,#1d9e75)] to-[#168a64] px-4 py-2 text-[0.8125rem] font-semibold text-white shadow-[var(--pgm-shadow-sm),inset_0_1px_0_rgba(255,255,255,0.12)] transition hover:-translate-y-px hover:shadow-[var(--pgm-shadow-md),0_0_16px_rgba(29,158,117,0.25)] active:translate-y-0 disabled:opacity-50"
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
      <div className="tickets-react-tech flex min-h-0 w-full min-w-0 max-w-full flex-1 flex-col overflow-visible px-4 pb-6 pt-4 text-[var(--pgm-text)] sm:px-5">
        {boot?.servicedesk ? (
          <header className="mb-2 border-b border-[var(--pgm-border-subtle)] pb-3">
            <p className="m-0 text-[0.7rem] font-semibold uppercase tracking-[0.12em] text-[var(--pgm-primary,#1d9e75)]">
              Service Desk
            </p>
            <h2 className="m-0 text-[1.1rem] font-bold leading-snug text-[var(--pgm-text)]">Fila técnica</h2>
          </header>
        ) : (
          <header className="mb-3 flex min-h-[2.75rem] flex-wrap items-center justify-between gap-3 overflow-visible py-1">
            <div>
              <p className="text-[0.7rem] font-semibold uppercase tracking-[0.12em] text-[var(--pgm-primary,#1d9e75)]">Painel Técnico</p>
              <h2 className="m-0 min-w-0 self-center text-[1.15rem] font-bold leading-snug text-[var(--pgm-text)]">Tickets — técnico</h2>
            </div>
            <div className="flex shrink-0 flex-wrap items-center justify-end gap-2">
              {boot?.paths?.ticketsOperacional && Number(boot?.role) === 0 ? (
                <a
                  href={boot.paths.ticketsOperacional}
                  className="inline-flex items-center gap-1.5 rounded-lg border border-[var(--pgm-border)] bg-transparent px-3 py-2 text-[0.8125rem] font-medium text-[var(--pgm-text)] transition hover:bg-[var(--pgm-bg-overlay)] hover:border-[var(--pgm-border-strong)]"
                >
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <path d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                  </svg>
                  Painel operacional
                </a>
              ) : null}
              {addTicket ? (
                <a
                  href={addTicket}
                  className="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-[#006D5B] px-3 py-2 text-[0.8125rem] font-semibold text-white shadow-[0_1px_3px_rgba(0,0,0,0.25),inset_0_1px_0_rgba(255,255,255,0.18)] transition hover:-translate-y-px hover:brightness-110 hover:shadow-[0_4px_12px_rgba(0,109,91,0.30)] active:translate-y-0"
                >
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <path d="M12 5v14m-7-7h14" />
                  </svg>
                  Abrir ticket
                </a>
              ) : null}
            </div>
          </header>
        )}
        {tableSection}
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-[var(--pgm-bg-base)] text-[var(--pgm-text)]">
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
          <header className="sticky top-0 z-20 border-b border-[var(--pgm-glass-border)] bg-[var(--pgm-glass-bg)] backdrop-blur-[var(--pgm-glass-blur,12px)]">
            <div className="flex flex-col gap-4 px-4 py-4 sm:px-6 xl:flex-row xl:items-center xl:justify-between">
              <div>
                <p className="text-[0.7rem] font-semibold uppercase tracking-[0.12em] text-[var(--pgm-primary,#1d9e75)]">
                  {MOCK_SESSION_TECNICO.empresa}
                </p>
                <h2 className="text-[1.35rem] font-bold text-[var(--pgm-text)]">
                  Tickets — técnico
                </h2>
                <p className="text-[0.8rem] text-[var(--pgm-text-muted)]">
                  Responsável, filas N1–N3/NOC/serviço e transferência com registro no histórico.
                </p>
              </div>
              <div className="flex flex-wrap items-center gap-3">
                <div className="rounded-xl border border-[var(--pgm-border-subtle)] bg-[var(--pgm-bg-elevated)] px-4 py-3 text-sm shadow-[var(--pgm-shadow-sm)]">
                  <span className="block text-[0.7rem] font-semibold uppercase tracking-[0.06em] text-[var(--pgm-text-muted)]">Data</span>
                  <span className="font-semibold text-[var(--pgm-text)]">{hoje}</span>
                </div>
                <span className="rounded-xl border border-[var(--pgm-border)] px-5 py-3 text-[0.8125rem] text-[var(--pgm-text-muted)]">
                  Abrir ticket (portal)
                </span>
              </div>
            </div>
          </header>

          <div className="space-y-6 p-4 sm:p-6">
            {tableSection}
            <p className="text-center text-xs text-[var(--pgm-text-muted)]">Modo demonstração — use Vite em localhost.</p>
          </div>
        </main>
      </div>
    </div>
  );
}
