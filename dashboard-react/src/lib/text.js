/** Remove tags HTML (ex.: labels vindos do PHP legado). */
export function stripHtml(s) {
  if (s == null) return '—';
  const t = String(s)
    .replace(/<[^>]*>/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
  return t || '—';
}
