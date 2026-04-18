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

const SD_BASE = 'tracking-[0.04em] backdrop-blur-[4px] transition-all duration-[120ms] hover:brightness-110';

/**
 * Service Desk — Frosted Glass + Dots por cor de status.
 * Bg translúcido tintado do status + texto/dot na cor cheia do status
 * → cada status leva um dot de cor distinta (bg-current no <StatusDot />).
 */
const BADGE_SERVICEDESK = {
  success:     `${SD_BASE} bg-[rgba(39,174,96,0.16)] text-[#3ddc7f] shadow-[inset_0_0_0_1px_rgba(39,174,96,0.40)]`,
  warning:     `${SD_BASE} bg-[rgba(243,156,18,0.16)] text-[#f5b752] shadow-[inset_0_0_0_1px_rgba(243,156,18,0.40)]`,
  critical:    `${SD_BASE} bg-[rgba(220,51,15,0.18)] text-[#ff6b4a] shadow-[inset_0_0_0_1px_rgba(220,51,15,0.45)]`,
  high:        `${SD_BASE} bg-[rgba(243,156,18,0.16)] text-[#f5b752] shadow-[inset_0_0_0_1px_rgba(243,156,18,0.40)]`,
  medium:      `${SD_BASE} bg-[rgba(243,156,18,0.16)] text-[#f5b752] shadow-[inset_0_0_0_1px_rgba(243,156,18,0.40)]`,
  low:         `${SD_BASE} bg-[var(--pgm-badge-muted-bg)] text-[var(--pgm-badge-muted-text)] shadow-[inset_0_0_0_1px_var(--pgm-badge-muted-ring)]`,
  progress:    `${SD_BASE} bg-[rgba(45,170,225,0.16)] text-[#5ec4ea] shadow-[inset_0_0_0_1px_rgba(45,170,225,0.40)]`,
  waiting:     `${SD_BASE} bg-[rgba(45,170,225,0.16)] text-[#5ec4ea] shadow-[inset_0_0_0_1px_rgba(45,170,225,0.40)]`,
  pendingTech: `${SD_BASE} bg-[rgba(243,156,18,0.16)] text-[#f5b752] shadow-[inset_0_0_0_1px_rgba(243,156,18,0.40)]`,
  resolved:    `${SD_BASE} bg-[rgba(39,174,96,0.16)] text-[#3ddc7f] shadow-[inset_0_0_0_1px_rgba(39,174,96,0.40)]`,
  escalated:   `${SD_BASE} bg-[rgba(220,51,15,0.18)] text-[#ff6b4a] shadow-[inset_0_0_0_1px_rgba(220,51,15,0.45)]`,
  closed:      `${SD_BASE} bg-[rgba(138,90,194,0.16)] text-[#b085e3] shadow-[inset_0_0_0_1px_rgba(138,90,194,0.40)]`,
  cancelled:   `${SD_BASE} bg-[rgba(220,51,15,0.18)] text-[#ff6b4a] shadow-[inset_0_0_0_1px_rgba(220,51,15,0.45)]`,
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
