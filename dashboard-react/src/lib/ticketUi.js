/** Classes Tailwind para badges — reutilizado em técnico e cliente. */

const BADGE_LIGHT = {
  success: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/45 dark:text-emerald-200 dark:border-emerald-800',
  warning: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/35 dark:text-amber-200 dark:border-amber-800',
  critical: 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/35 dark:text-rose-200 dark:border-rose-800',
  high: 'bg-orange-50 text-orange-700 border-orange-200 dark:bg-orange-950/30 dark:text-orange-200 dark:border-orange-800',
  medium: 'bg-emerald-50/80 text-emerald-800 border-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-200 dark:border-emerald-800',
  low: 'bg-slate-50 text-slate-700 border-slate-200 dark:bg-[var(--pgm-bg-elevated)] dark:text-[var(--pgm-text-muted)] dark:border-[var(--pgm-border)]',
  progress: 'bg-teal-50 text-teal-800 border-teal-200 dark:bg-teal-950/35 dark:text-teal-100 dark:border-teal-800',
  waiting: 'bg-violet-50 text-violet-800 border-violet-200 dark:bg-violet-950/35 dark:text-violet-200 dark:border-violet-800',
  pendingTech: 'bg-emerald-100 text-emerald-900 border-emerald-300 dark:bg-emerald-950/45 dark:text-emerald-100 dark:border-emerald-700',
  resolved: 'bg-emerald-100 text-emerald-900 border-emerald-300 dark:bg-emerald-950/50 dark:text-emerald-100 dark:border-emerald-700',
  escalated: 'bg-rose-50 text-rose-800 border-rose-300',
  closed: 'bg-slate-200 text-slate-700 border-slate-300',
  cancelled: 'bg-red-50 text-red-800 border-red-300',
};

/** Badges no embed portal cliente — paleta pgm_orcamentos_premium (fundo claro). */
const BADGE_EMBED = {
  success: 'border border-emerald-200 bg-emerald-50 text-emerald-800',
  warning: 'border border-amber-200 bg-amber-50 text-amber-900',
  critical: 'border border-rose-200 bg-rose-50 text-rose-800',
  high: 'border border-orange-200 bg-orange-50 text-orange-900',
  medium: 'border border-sky-200 bg-sky-50 text-sky-900',
  low: 'border border-[#e5e4e0] bg-[#f9f9f8] text-[#6b6a65]',
  progress: 'border border-sky-200 bg-sky-50 text-sky-900',
  waiting: 'border border-violet-200 bg-violet-50 text-violet-900',
  pendingTech: 'border border-amber-200 bg-amber-50 text-amber-950',
  resolved: 'border border-emerald-200 bg-emerald-50 text-emerald-900',
  escalated: 'border border-rose-200 bg-rose-50 text-rose-900',
  closed: 'border border-[#e5e4e0] bg-[#f2f1ee] text-[#5f5e5a]',
  cancelled: 'border border-red-200 bg-red-50 text-red-800',
};

/**
 * @param {string} type
 * @param {boolean} [embed] lista/detalhe embutidos no shell cliente (tema premium claro)
 */
export function badgeClass(type, embed = false) {
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
 */
export function acaoLinkClassName(key, embed = false) {
  const k = String(key || '').toLowerCase();
  const base =
    'inline-flex shrink-0 items-center rounded-full border px-1.5 py-0.5 text-[10px] font-semibold leading-tight transition hover:opacity-90';
  if (embed && k === 'imprimir') {
    return `${base} border-[rgba(0,192,139,0.45)] bg-[#e6faf4] text-[#008f68] hover:bg-[rgba(0,192,139,0.18)]`;
  }
  if (embed && k === 'cancelar') {
    return `${base} ${badgeClass('cancelled', true)}`;
  }
  return `${base} ${badgeClass(acaoKeyToBadgeType(key), embed)}`;
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
