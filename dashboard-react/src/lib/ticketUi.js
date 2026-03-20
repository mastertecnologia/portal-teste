/** Classes Tailwind para badges — reutilizado em técnico e cliente. */

export function badgeClass(type) {
  const map = {
    success: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    warning: 'bg-amber-50 text-amber-700 border-amber-200',
    critical: 'bg-rose-50 text-rose-700 border-rose-200',
    high: 'bg-orange-50 text-orange-700 border-orange-200',
    medium: 'bg-sky-50 text-sky-700 border-sky-200',
    low: 'bg-slate-50 text-slate-700 border-slate-200',
    progress: 'bg-cyan-50 text-cyan-700 border-cyan-200',
    waiting: 'bg-violet-50 text-violet-700 border-violet-200',
    pendingTech: 'bg-amber-50 text-amber-800 border-amber-200',
    resolved: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    escalated: 'bg-rose-50 text-rose-700 border-rose-200',
    closed: 'bg-slate-100 text-slate-600 border-slate-200',
  };
  return map[type] || 'bg-slate-50 text-slate-700 border-slate-200';
}

export function priorityType(value) {
  if (value === 'Crítica') return 'critical';
  if (value === 'Alta') return 'high';
  if (value === 'Média') return 'medium';
  return 'low';
}

/** Status alinhados ao portal (situacao) */
export function statusType(value) {
  if (value === 'Em execução' || value === 'Em andamento') return 'progress';
  if (value === 'Aguardando cliente' || value === 'Respondido') return 'waiting';
  if (value === 'Aguardando técnico') return 'pendingTech';
  if (value === 'Resolvido') return 'resolved';
  if (value === 'Escalado') return 'escalated';
  if (value === 'Cancelado' || value === 'Fechado') return 'closed';
  return 'low';
}
