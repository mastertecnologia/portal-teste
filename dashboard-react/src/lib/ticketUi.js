/** Classes Tailwind para badges — reutilizado em técnico e cliente. */

/** Preenchimento sólido com cores de status padrão + texto branco. */
const BADGE_LIGHT = {
  success:     'bg-[#27AE60] text-white border-transparent shadow-[0_1px_3px_rgba(0,0,0,0.15)]',
  warning:     'bg-[#F39C12] text-white border-transparent shadow-[0_1px_3px_rgba(0,0,0,0.15)]',
  critical:    'bg-[#dc330f] text-white border-transparent shadow-[0_1px_3px_rgba(0,0,0,0.15)]',
  high:        'bg-[#F39C12] text-white border-transparent shadow-[0_1px_3px_rgba(0,0,0,0.15)]',
  medium:      'bg-[#F39C12] text-white border-transparent shadow-[0_1px_3px_rgba(0,0,0,0.15)]',
  low:         'bg-slate-200 text-slate-700 border-slate-300',
  progress:    'bg-[#2DAAE1] text-white border-transparent shadow-[0_1px_3px_rgba(0,0,0,0.15)]',
  waiting:     'bg-[#2DAAE1] text-white border-transparent shadow-[0_1px_3px_rgba(0,0,0,0.15)]',
  pendingTech: 'bg-[#F39C12] text-white border-transparent shadow-[0_1px_3px_rgba(0,0,0,0.15)]',
  resolved:    'bg-[#27AE60] text-white border-transparent shadow-[0_1px_3px_rgba(0,0,0,0.15)]',
  escalated:   'bg-[#dc330f] text-white border-transparent shadow-[0_1px_3px_rgba(0,0,0,0.15)]',
  closed:      'bg-[#8a5ac2] text-white border-transparent shadow-[0_1px_3px_rgba(0,0,0,0.15)]',
  cancelled:   'bg-[#dc330f] text-white border-transparent shadow-[0_1px_3px_rgba(0,0,0,0.15)]',
};

/* Service Desk grid: badges sólidos PGM v2 (legível + contraste). */
const SD_SOLID =
  'tracking-[0.04em] border border-transparent text-white shadow-[0_1px_3px_rgba(15,23,42,0.12)] transition-all duration-[120ms] hover:brightness-[1.06]';

const BADGE_SERVICEDESK = {
  open:        `${SD_SOLID} bg-[#64748b]`,
  pendingTicket: `${SD_SOLID} bg-[#F39C12]`,
  success:     `${SD_SOLID} bg-[#27AE60]`,
  warning:     `${SD_SOLID} bg-[#F39C12]`,
  critical:    `${SD_SOLID} bg-[#dc330f]`,
  high:        `${SD_SOLID} bg-[#F39C12]`,
  medium:      `${SD_SOLID} bg-[#F39C12]`,
  low:         `${SD_SOLID} bg-[#1d9e75]`,
  progress:    `${SD_SOLID} bg-[#2DAAE1]`,
  waiting:     `${SD_SOLID} bg-[#2DAAE1]`,
  pendingTech: `${SD_SOLID} bg-[#F39C12]`,
  resolved:    `${SD_SOLID} bg-[#27AE60]`,
  escalated:   `${SD_SOLID} bg-[#dc330f]`,
  closed:      `${SD_SOLID} bg-[#334155]`,
  cancelled:   `${SD_SOLID} bg-[#dc330f]`,
};

/** Badges no embed portal cliente — preenchimento sólido + texto branco. */
const BADGE_EMBED = {
  success:     'border border-transparent bg-[#27AE60] text-white shadow-[0_1px_3px_rgba(0,0,0,0.25)]',
  warning:     'border border-transparent bg-[#F39C12] text-white shadow-[0_1px_3px_rgba(0,0,0,0.25)]',
  critical:    'border border-transparent bg-[#dc330f] text-white shadow-[0_1px_3px_rgba(0,0,0,0.25)]',
  high:        'border border-transparent bg-[#F39C12] text-white shadow-[0_1px_3px_rgba(0,0,0,0.25)]',
  medium:      'border border-transparent bg-[#F39C12] text-white shadow-[0_1px_3px_rgba(0,0,0,0.25)]',
  low:         'border border-[var(--pgm-border)] bg-[var(--pgm-bg-elevated)] text-[var(--pgm-text-secondary)]',
  progress:    'border border-transparent bg-[#2DAAE1] text-white shadow-[0_1px_3px_rgba(0,0,0,0.25)]',
  waiting:     'border border-transparent bg-[#2DAAE1] text-white shadow-[0_1px_3px_rgba(0,0,0,0.25)]',
  pendingTech: 'border border-transparent bg-[#F39C12] text-white shadow-[0_1px_3px_rgba(0,0,0,0.25)]',
  resolved:    'border border-transparent bg-[#27AE60] text-white shadow-[0_1px_3px_rgba(0,0,0,0.25)]',
  escalated:   'border border-transparent bg-[#dc330f] text-white shadow-[0_1px_3px_rgba(0,0,0,0.25)]',
  closed:      'border border-transparent bg-[#8a5ac2] text-white shadow-[0_1px_3px_rgba(0,0,0,0.25)]',
  cancelled:   'border border-transparent bg-[#dc330f] text-white shadow-[0_1px_3px_rgba(0,0,0,0.25)]',
};

/**
 * @param {string} type
 * @param {boolean} [embed] lista/detalhe embutidos no shell cliente (tema escuro)
 * @param {boolean} [servicedesk] fila Service Desk — badge por status (cores distintas); colunas sem tinta
 */
export function badgeClass(type, embed = false, servicedesk = false) {
  if (servicedesk) {
    return BADGE_SERVICEDESK[type] || BADGE_SERVICEDESK.low;
  }
  const map = embed ? BADGE_EMBED : BADGE_LIGHT;
  return map[type] || (embed ? BADGE_EMBED.low : BADGE_LIGHT.low);
}

/** Normaliza rótulo de status vindo do legado (HTML, acentos, espaços). */
export function normalizeStatusKey(value) {
  if (value == null) return '';
  const t = String(value)
    .replace(/<[^>]*>/g, ' ')
    .normalize('NFD')
    .replace(/\p{M}/gu, '')
    .toLowerCase()
    .replace(/\s+/g, ' ')
    .trim();
  return t;
}

export function priorityType(value) {
  if (value === 'Crítica' || value === 'Urgente') return 'critical';
  if (value === 'Alta') return 'high';
  if (value === 'Média') return 'medium';
  return 'low';
}

/** Status alinhados ao portal (situacao) — aceita texto cru ou já sem HTML. */
export function statusType(value) {
  const v = normalizeStatusKey(value);
  if (!v || v === '-') return 'low';
  if (v.includes('critica') || v.includes('critico')) return 'critical';
  if (v.includes('urgente')) return 'high';
  if (v.includes('aberto') || v.includes('reaberto')) return 'open';
  if (v.includes('em execucao') || v.includes('em andamento')) return 'progress';
  if (v.includes('aguardando cliente') || v.includes('respondido')) return 'waiting';
  if (v.includes('aguardando tecnico') || v.includes('aguardando técnico')) return 'pendingTech';
  if (v.includes('pendente')) return 'pendingTicket';
  if (v.includes('resolvido')) return 'resolved';
  if (v.includes('escalado')) return 'escalated';
  if (v.includes('cancelado')) return 'cancelled';
  if (v.includes('fechado') || v.includes('encerrado')) return 'closed';
  return 'low';
}

/** Normaliza código de estado workflow (hífen/espaço → _, minúsculas, sem acento). */
export function normalizeWorkflowCodigo(v) {
  return String(v || '')
    .normalize('NFD')
    .replace(/\p{M}/gu, '')
    .replace(/[\s-]+/g, '_')
    .toLowerCase();
}

const _WF_EXEC = new Set(['emandamento', 'em_andamento', 'em_execucao', 'execucao', 'andamento']);

function isWorkflowExecCodigo(c) {
  if (!c) return false;
  if (_WF_EXEC.has(c)) return true;
  return c.startsWith('em_exec');
}

/** Indica transição PATCH para estado “Em execução” (critério alinhado a `workflowTransitionPatchStatusLabel`). */
export function workflowTransitionTargetsExecucao(transitionLike) {
  return isWorkflowExecCodigo(normalizeWorkflowCodigo(transitionLike?.codigo));
}

/**
 * Backend `tecnico_fila_obrigatorios` usa `queue_id` + vínculos em fila (modo filas relacionais).
 * Com `queuesRelacional`, só conta ID numérico — não aceitar apenas `filaSuporte` legado.
 */
export function ticketHasValidTecnico(ticket) {
  const id = Number(
    ticket?.idtecnico_responsavel
      ?? ticket?.tecnico_id
      ?? ticket?.iduser
      ?? ticket?.tecnico?.id
      ?? ticket?.owner_id
      ?? 0,
  );
  return Number.isFinite(id) && id > 0;
}

export function ticketHasValidQueueForStart(ticket, queuesRelacional) {
  const qid = Number(ticket?.filaQueueId ?? ticket?.queue_id ?? 0);
  if (Number.isFinite(qid) && qid > 0) return true;
  if (queuesRelacional) return false;
  const fila = String(ticket?.filaSuporte ?? '').trim().toLowerCase();
  return fila !== '' && fila !== '-' && fila !== '0' && fila !== 'null' && fila !== 'undefined';
}

/**
 * Coluna visual da timeline PGM (0 Aberto … 4 Fechado). -1 = fora do mapa.
 */
export function workflowStepColumnIndex(codigo) {
  const c = normalizeWorkflowCodigo(codigo);
  if (!c) return -1;
  if (c === 'aberto' || c === 'open' || c === 'novo' || c === 'reaberto') return 0;
  if (isWorkflowExecCodigo(c)) return 1;
  if (c === 'pendente' || c === 'pending' || c === 'aguardando' || c === 'pausado') return 2;
  if (c === 'resolvido' || c === 'resolved' || c === 'solucionado') return 3;
  if (c === 'fechado' || c === 'encerrado' || c === 'closed' || c === 'cancelado') return 4;
  return -1;
}

/** Compatível com o PATCH da grid (campo status) — mesmo critério do select inline. */
export function workflowTransitionPatchStatusLabel(o) {
  const c = normalizeWorkflowCodigo(o?.codigo);
  const label = String(o?.label || '').trim();
  if (['pendente', 'pending', 'aberto', 'open', 'novo', 'reaberto'].includes(c)) return 'Pendente';
  if (isWorkflowExecCodigo(c)) return 'Em execução';
  if (c === 'resolvido' || c === 'resolved' || c === 'solucionado') return 'Resolvido';
  if (c === 'fechado' || c === 'encerrado' || c === 'closed') return 'Fechado';
  if (c === 'cancelado') return label || 'Cancelado';
  return label || '—';
}

function workflowCodigoToBadgeType(codigo) {
  const cRaw = normalizeWorkflowCodigo(codigo);
  if (cRaw === 'cancelado') return 'cancelled';
  const idx = workflowStepColumnIndex(codigo);
  if (idx === 0) return 'open';
  if (idx === 1) return 'progress';
  if (idx === 2) return 'pendingTicket';
  if (idx === 3) return 'resolved';
  if (idx === 4) return 'closed';
  return 'low';
}

/**
 * Tipo visual de badge na fila Service Desk: prioriza `workflow.current` quando habilitado.
 */
export function servicedeskStatusTypeFromTicket(ticket, situacaoLabelText) {
  const wf = ticket?.workflow;
  if (wf?.enabled === true && wf?.current?.codigo != null && String(wf.current.codigo).trim() !== '') {
    return workflowCodigoToBadgeType(wf.current.codigo);
  }
  return statusType(situacaoLabelText ?? ticket?.situacaoLabel ?? ticket?.status ?? '');
}

/**
 * Cor do link de ação na listagem (key vinda do PHP: pendente, emandamento, resolvido, cancelar, imprimir).
 */
export function acaoKeyToBadgeType(key) {
  const k = String(key || '').toLowerCase();
  if (k === 'pendente') return 'pendingTech';
  if (k === 'emandamento' || k === 'execucao') return 'progress';
  if (k === 'resolvido') return 'resolved';
  if (k === 'cancelar') return 'cancelled';
  if (k === 'imprimir') return 'low';
  if (k === 'transferir') return 'medium';
  if (k === 'iniciar') return 'progress';
  return 'low';
}

/**
 * @param {string} key
 * @param {boolean} [embed]
 * @param {boolean} [servicedesk]
 */
export function acaoLinkClassName(key, embed = false, servicedesk = false) {
  const k = String(key || '').toLowerCase();
  const base =
    'inline-flex shrink-0 items-center rounded-full border px-1.5 py-0.5 text-[10px] font-semibold leading-tight transition hover:opacity-90';
  if (embed && k === 'imprimir') {
    if (servicedesk) {
      return `${base} border-[var(--pgm-border)] bg-[var(--pgm-bg-elevated)] text-[var(--pgm-text-secondary)] hover:bg-[var(--pgm-bg-surface)]`;
    }
    return `${base} border-[var(--pgm-primary)] bg-[var(--pgm-primary)] text-white shadow-[inset_0_1px_0_rgba(255,255,255,0.12)] hover:brightness-110`;
  }
  if (embed && k === 'cancelar') {
    return `${base} ${badgeClass('cancelled', true, servicedesk)}`;
  }
  return `${base} ${badgeClass(acaoKeyToBadgeType(key), embed, servicedesk)}`;
}

/** Mesma ordem visual para qualquer status (como na fila “Em execução”). */
const ACAO_ORDER = ['iniciar', 'pendente', 'emandamento', 'resolvido', 'transferir', 'cancelar', 'imprimir'];

export function sortTicketAcoes(acoes) {
  if (!Array.isArray(acoes) || acoes.length === 0) return [];
  const rank = (k) => {
    const i = ACAO_ORDER.indexOf(String(k || '').toLowerCase());
    return i === -1 ? 100 : i;
  };
  return [...acoes].sort((a, b) => rank(a.key) - rank(b.key));
}
