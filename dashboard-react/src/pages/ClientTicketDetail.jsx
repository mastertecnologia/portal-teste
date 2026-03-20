import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { fetchTicketDetail, postComentario, USE_MOCK } from '../lib/api';
import { MOCK_SESSION_CLIENTE } from '../data/mockData';
import { badgeClass, priorityType, statusType } from '../lib/ticketUi';

export default function ClientTicketDetail({ boot }) {
  const { id: idParam } = useParams();
  const id = boot?.ticketId ?? idParam;
  const [ticket, setTicket] = useState(null);
  const [comentarios, setComentarios] = useState([]);
  const [texto, setTexto] = useState('');
  const [enviando, setEnviando] = useState(false);
  const [erro, setErro] = useState(null);

  useEffect(() => {
    let c = false;
    (async () => {
      const res = await fetchTicketDetail(id);
      if (c) return;
      if (!res.ok) {
        setErro(res.error || 'Ticket não encontrado');
        return;
      }
      setTicket(res.data);
      setComentarios(res.data.comentarios || []);
    })();
    return () => {
      c = true;
    };
  }, [id]);

  async function handleComentario(e) {
    e.preventDefault();
    const t = texto.trim();
    if (!t || !ticket) return;
    setEnviando(true);
    setErro(null);
    const res = await postComentario(ticket.id, t);
    setEnviando(false);
    if (res.ok) {
      setComentarios((prev) => [...prev, res.data]);
      setTexto('');
    } else {
      setErro('Não foi possível enviar o comentário.');
    }
  }

  const backHref = boot?.paths?.indexCliente;
  const backLabel = '← Meus tickets';

  if (erro && !ticket) {
    return (
      <div className="min-h-screen bg-slate-100 px-4 py-12 text-center">
        <p className="text-rose-700">{erro}</p>
        {backHref ? (
          <a href={backHref} className="mt-4 inline-block text-cyan-700 underline">
            {backLabel}
          </a>
        ) : (
          <Link to="/cliente" className="mt-4 inline-block text-cyan-700 underline">
            {backLabel}
          </Link>
        )}
      </div>
    );
  }

  if (!ticket) {
    return (
      <div className="min-h-screen bg-slate-100 px-4 py-12 text-center text-slate-500">
        Carregando chamado…
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-slate-100 text-slate-800">
      <header className="border-b border-slate-200 bg-white shadow-sm">
        <div className="mx-auto max-w-3xl px-4 py-4 sm:px-6">
          {backHref ? (
            <a href={backHref} className="text-sm font-medium text-cyan-700 hover:underline">
              {backLabel}
            </a>
          ) : (
            <Link to="/cliente" className="text-sm font-medium text-cyan-700 hover:underline">
              {backLabel}
            </Link>
          )}
          <div className="mt-3 flex flex-wrap items-center gap-2">
            <h1 className="text-2xl font-bold text-slate-900">#{ticket.id}</h1>
            <span
              className={`inline-flex rounded-full border px-3 py-1 text-xs font-semibold ${badgeClass(
                statusType(ticket.status)
              )}`}
            >
              {ticket.status}
            </span>
            {ticket.prioridade && ticket.prioridade !== '—' && (
              <span
                className={`inline-flex rounded-full border px-3 py-1 text-xs font-semibold ${badgeClass(
                  priorityType(ticket.prioridade)
                )}`}
              >
                {ticket.prioridade}
              </span>
            )}
          </div>
          <p className="mt-1 text-sm text-slate-500">
            {ticket.atualizado}
            {ticket.responsavel && ticket.responsavel !== '—' ? ` · ${ticket.responsavel}` : ''}
          </p>
        </div>
      </header>

      <main className="mx-auto max-w-3xl space-y-6 px-4 py-8 sm:px-6">
        {erro && <p className="rounded-xl bg-rose-50 px-4 py-2 text-sm text-rose-800">{erro}</p>}

        <section className="rounded-[24px] border border-slate-200 bg-white p-6 shadow-sm">
          <h2 className="text-sm font-semibold uppercase tracking-wide text-slate-500">Assunto</h2>
          <p className="mt-1 text-lg font-semibold text-slate-900">{ticket.assunto}</p>
          <h2 className="mt-6 text-sm font-semibold uppercase tracking-wide text-slate-500">Descrição</h2>
          <div className="prose prose-sm mt-2 max-w-none whitespace-pre-wrap text-slate-700">{ticket.descricao}</div>
        </section>

        <section className="rounded-[24px] border border-slate-200 bg-white p-6 shadow-sm">
          <h2 className="text-lg font-bold text-slate-900">Anexos</h2>
          {ticket.anexos && ticket.anexos.length > 0 ? (
            <ul className="mt-4 space-y-2">
              {ticket.anexos.map((a) => (
                <li key={a.id} className="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3 text-sm">
                  <a href={a.url} className="font-medium text-teal-700 hover:underline" target="_blank" rel="noreferrer">
                    {a.nome}
                  </a>
                </li>
              ))}
            </ul>
          ) : (
            <p className="mt-3 text-sm text-slate-500">Nenhum anexo neste chamado.</p>
          )}
        </section>

        <section className="rounded-[24px] border border-slate-200 bg-white p-6 shadow-sm">
          <h2 className="text-lg font-bold text-slate-900">Conversa</h2>
          <p className="mt-1 text-sm text-slate-500">
            {USE_MOCK ? `Olá, ${MOCK_SESSION_CLIENTE.name}` : 'Comentários entre você e o suporte.'}
          </p>

          <ul className="mt-6 space-y-4">
            {comentarios.map((c) => (
              <li
                key={c.id}
                className={`rounded-2xl border px-4 py-3 ${
                  c.papel === 'tecnico' ? 'border-cyan-200 bg-cyan-50/50' : 'border-slate-200 bg-slate-50/80'
                }`}
              >
                <div className="flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
                  <span className="font-semibold text-slate-800">{c.autor}</span>
                  <span>{c.quando}</span>
                </div>
                <div
                  className="prose prose-sm mt-2 max-w-none text-slate-700"
                  dangerouslySetInnerHTML={{ __html: c.texto }}
                />
              </li>
            ))}
          </ul>

          <form onSubmit={handleComentario} className="mt-6 space-y-3">
            <textarea
              value={texto}
              onChange={(e) => setTexto(e.target.value)}
              rows={4}
              className="w-full rounded-xl border border-slate-200 p-3 text-sm"
              placeholder="Escreva um comentário…"
            />
            <button
              type="submit"
              disabled={enviando}
              className="rounded-xl bg-gradient-to-r from-cyan-600 to-teal-600 px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-50"
            >
              {enviando ? 'Enviando…' : 'Enviar comentário'}
            </button>
          </form>
        </section>
      </main>
    </div>
  );
}
