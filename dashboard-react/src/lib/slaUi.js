/**
 * Indicadores visuais de urgência SLA (campos vindos do backend — não recalcular regra crítica aqui).
 */

const BAND_ORDER = {
  violated: 0,
  critical: 1,
  attention: 2,
  ok: 3,
  paused: 4,
  unknown: 5,
};

export function slaBandOrder(band) {
  const k = String(band || 'unknown').toLowerCase();
  return BAND_ORDER[k] ?? BAND_ORDER.unknown;
}

/** Classes da célula “SLA” (badge separada do status operacional). */
export function slaUrgencyBadgeClass(band) {
  const b = String(band || 'unknown').toLowerCase();
  const base =
    'inline-flex max-w-[5.5rem] items-center justify-center rounded-md border px-1 py-0.5 text-[9px] font-bold uppercase tracking-[0.06em]';
  switch (b) {
    case 'violated':
      return `${base} border-[#b91c1c] bg-[#dc2626] text-white shadow-[0_0_0_1px_rgba(220,38,38,0.35)] animate-pulse`;
    case 'critical':
      return `${base} border-[#b45309] bg-[#ea580c] text-white animate-pulse`;
    case 'attention':
      return `${base} border-[#b45309] bg-[#f59e0b] text-white`;
    case 'paused':
      return `${base} border-[#64748b] bg-[#94a3b8] text-white`;
    case 'ok':
      return `${base} border-[#15803d] bg-[#22c55e] text-white`;
    default:
      return `${base} border-[var(--pgm-border)] bg-[var(--pgm-bg-elevated)] text-[var(--pgm-text-muted)]`;
  }
}

export function slaUrgencyLabel(band) {
  const b = String(band || 'unknown').toLowerCase();
  const m = {
    ok: 'OK',
    attention: 'Atenção',
    critical: 'Crítico',
    violated: 'Estourado',
    paused: 'Pausado',
    unknown: '—',
  };
  return m[b] || '—';
}

function priorityRank(ticket) {
  const c = String(ticket?.prioridadeCode || '').toLowerCase();
  if (c === 'critica' || c === 'p1') return 0;
  if (c === 'alta' || c === 'p2') return 1;
  if (c === 'media' || c === 'p3') return 2;
  if (c === 'baixa' || c === 'p4') return 3;
  const px = String(ticket?.prioridadeLabel || '').toLowerCase();
  if (px.includes('crít') || px.includes('crit')) return 0;
  if (px.includes('alta')) return 1;
  if (px.includes('méd') || px.includes('med')) return 2;
  return 9;
}

function deadlineTs(ticket) {
  const iso = ticket?.data_limite_resolucao_iso;
  if (!iso) return null;
  const t = Date.parse(String(iso));
  return Number.isFinite(t) ? t : null;
}

/**
 * Ordenação opcional: violado → crítico → atenção → prazo mais cedo → prioridade → id.
 */
export function compareTicketsBySlaUrgency(a, b) {
  const ba = slaBandOrder(a?.sla_urgency_band);
  const bb = slaBandOrder(b?.sla_urgency_band);
  if (ba !== bb) return ba - bb;
  const da = deadlineTs(a);
  const db = deadlineTs(b);
  if (da !== null && db !== null && da !== db) return da - db;
  if (da !== null && db === null) return -1;
  if (da === null && db !== null) return 1;
  const pa = priorityRank(a);
  const pb = priorityRank(b);
  if (pa !== pb) return pa - pb;
  return Number(b?.id || 0) - Number(a?.id || 0);
}
