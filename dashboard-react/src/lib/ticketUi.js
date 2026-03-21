/** Classes Tailwind para badges — reutilizado em técnico e cliente. */

export function badgeClass(type) {
  const map = {
    success: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    warning: 'bg-amber-50 text-amber-700 border-amber-200',
    critical: 'bg-rose-50 text-rose-700 border-rose-200',
    high: 'bg-orange-50 text-orange-700 border-orange-200',
    medium: 'bg-sky-50 text-sky-700 border-sky-200',
    low: 'bg-slate-50 text-slate-700 border-slate-200',
    progress: 'bg-sky-50 text-sky-800 border-sky-300',
    waiting: 'bg-violet-50 text-violet-800 border-violet-200',
    pendingTech: 'bg-amber-100 text-amber-900 border-amber-300',
    resolved: 'bg-emerald-100 text-emerald-900 border-emerald-300',
    escalated: 'bg-rose-50 text-rose-800 border-rose-300',
    closed: 'bg-slate-200 text-slate-700 border-slate-300',
    cancelled: 'bg-red-50 text-red-800 border-red-300',
  };
  return map[type] || 'bg-slate-50 text-slate-700 border-slate-200';
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

export function acaoLinkClassName(key) {
  return `inline-flex shrink-0 items-center rounded-full border px-1.5 py-0.5 text-[10px] font-semibold leading-tight transition hover:opacity-90 ${badgeClass(acaoKeyToBadgeType(key))}`;
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
