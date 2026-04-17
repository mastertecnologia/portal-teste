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

const SD_BASE = 'tracking-[0.04em] backdrop-blur-[2px] transition-all duration-[120ms] hover:brightness-110 shadow-[0_1px_3px_rgba(0,0,0,0.25)]';

const BADGE_SERVICEDESK = {
  success:     `${SD_BASE} bg-[#27AE60] text-white`,
  warning:     `${SD_BASE} bg-[#F39C12] text-white`,
  critical:    `${SD_BASE} bg-[#dc330f] text-white`,
  high:        `${SD_BASE} bg-[#F39C12] text-white`,
  medium:      `${SD_BASE} bg-[#F39C12] text-white`,
  low:         `${SD_BASE} bg-[var(--pgm-badge-muted-bg)] text-[var(--pgm-badge-muted-text)] shadow-[inset_0_0_0_1px_var(--pgm-badge-muted-ring)]`,
  progress:    `${SD_BASE} bg-[#2DAAE1] text-white`,
  waiting:     `${SD_BASE} bg-[#2DAAE1] text-white`,
  pendingTech: `${SD_BASE} bg-[#F39C12] text-white`,
  resolved:    `${SD_BASE} bg-[#27AE60] text-white`,
  escalated:   `${SD_BASE} bg-[#dc330f] text-white`,
  closed:      `${SD_BASE} bg-[#8a5ac2] text-white`,
  cancelled:   `${SD_BASE} bg-[#dc330f] text-white`,
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
  if (v.includes('em execucao') || v.includes('em andamento')) return 'progress';
  if (v.includes('aguardando cliente') || v.includes('respondido')) return 'waiting';
  if (v.includes('aguardando tecnico') || v.includes('aguardando técnico')) return 'pendingTech';
  if (v.includes('resolvido')) return 'resolved';
  if (v.includes('escalado')) return 'escalated';
  if (v.includes('cancelado')) return 'cancelled';
  if (v.includes('fechado')) return 'closed';
  return 'low';
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
