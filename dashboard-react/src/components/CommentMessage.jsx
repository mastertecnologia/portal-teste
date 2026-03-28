/**
 * Exibe texto do comentário: escapa HTML quando for texto puro (como no legado);
 * se já houver marcação salva no banco, renderiza como HTML (cuidado com XSS — origem confiável).
 */
export default function CommentMessage({ texto, embed = false }) {
  if (texto == null || texto === '') return null;
  const s = String(texto);
  const textCls = embed ? 'text-[#5f5e5a]' : 'text-slate-700';
  const looksLikeHtml = /<[a-z][\s\S]*>/i.test(s);
  if (looksLikeHtml) {
    return (
      <div className={`prose prose-sm mt-1 max-w-none ${textCls}`} dangerouslySetInnerHTML={{ __html: s }} />
    );
  }
  return <div className={`mt-1 whitespace-pre-wrap break-words text-sm leading-relaxed ${textCls}`}>{s}</div>;
}
