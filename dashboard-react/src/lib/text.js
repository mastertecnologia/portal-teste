/** Data/hora em comentário novo (pt-BR, alinhado ao formato do PHP d/m/Y H:i). */
export function formatCommentPostTimestamp(d = new Date()) {
  return d.toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

/** Ordenação por id numérico (ids temporários de otimista ao fim). */
export function commentSortKey(id) {
  if (typeof id === 'number' && Number.isFinite(id)) return id;
  return Number.MAX_SAFE_INTEGER;
}

/** Troca o item otimista pelo comentário gravado sem duplicar se o socket já inseriu o mesmo id. */
export function finalizeOptimisticComment(prev, tmpId, saved) {
  const sid = Number(saved.id);
  const rest = prev.filter(
    (c) => c.id !== tmpId && !(Number.isFinite(sid) && Number(c.id) === sid),
  );
  const next = [...rest, saved];
  next.sort((a, b) => commentSortKey(a.id) - commentSortKey(b.id));
  return next;
}

/** Remove tags HTML (ex.: labels vindos do PHP legado). */
export function stripHtml(s) {
  if (s == null) return '—';
  const t = String(s)
    .replace(/<[^>]*>/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
  return t || '—';
}
