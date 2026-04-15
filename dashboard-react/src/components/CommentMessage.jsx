/**
 * Exibe texto do comentário: escapa HTML quando for texto puro (como no legado);
 * se já houver marcação salva no banco, renderiza como HTML (cuidado com XSS — origem confiável).
 */
export default function CommentMessage({ texto }) {
  if (texto == null || texto === '') return null;
  const s = String(texto);
  const looksLikeHtml = /<[a-z][\s\S]*>/i.test(s);
  if (looksLikeHtml) {
    return (
      <div className="prose prose-sm max-w-none text-inherit" dangerouslySetInnerHTML={{ __html: s }} />
    );
  }
  return <div className="whitespace-pre-wrap break-words text-inherit leading-relaxed">{s}</div>;
}
