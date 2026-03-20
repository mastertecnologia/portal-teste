import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { fetchTicketDetail, postComentario, getBoot, USE_MOCK } from '../lib/api';
import { useTicketCommentsPoll, TICKET_COMMENTS_POLL_MS } from '../hooks/useTicketCommentsPoll';
import { MOCK_SESSION_CLIENTE } from '../data/mockData';
import { badgeClass, priorityType, statusType } from '../lib/ticketUi';
import { stripHtml } from '../lib/text';
import TicketAnexosPanel from '../components/TicketAnexosPanel.jsx';
import CommentMessage from '../components/CommentMessage.jsx';

function PapelBadge({ papel }) {
  const isTech = papel === 'tecnico';
  return (
    <span
      className={`inline-flex rounded px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide ${
        isTech ? 'bg-cyan-100 text-cyan-800' : 'bg-slate-200 text-slate-700'
      }`}
    >
      {isTech ? 'Suporte' : 'Cliente'}
    </span>
  );
}

export default function ClientTicketDetail({ boot }) {
  const { id: idParam } = useParams();
  const id = boot?.ticketId ?? idParam;
  const embedded = Boolean(boot);
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

  useTicketCommentsPoll(id, setComentarios, setTicket);

  async function handleComentario(e) {
    e.preventDefault();
    const t = texto.trim();
    if (!t || !ticket) return;
    const b = getBoot();
    const nome = USE_MOCK
      ? MOCK_SESSION_CLIENTE.name
      : (b?.userName || 'Eu').trim();
    const papel = (b?.role ?? 1) === 0 ? 'tecnico' : 'cliente';
    const tmpId = `pending-${Date.now()}`;
    const optimistic = {
      id: tmpId,
      autor: nome,
      papel,
      texto: t,
      quando: 'Enviando…',
      pending: true,
    };
    setComentarios((prev) => [...prev, optimistic]);
    setTexto('');
    setEnviando(true);
    setErro(null);
    const res = await postComentario(ticket.id, t);
    setEnviando(false);
    setComentarios((prev) => prev.filter((c) => c.id !== tmpId));
    if (res.ok) {
      setComentarios((prev) => [...prev, res.data]);
    } else {
      setErro('Não foi possível enviar o comentário.');
      setTexto(t);
    }
  }

  const backHref = boot?.paths?.indexCliente;
  const backLabel = '← Meus tickets';

  if (erro && !ticket) {
    return (
      <div className={embedded ? 'py-8 text-center' : 'min-h-screen bg-slate-100 px-4 py-12 text-center'}>
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
      <div className={embedded ? 'py-12 text-center text-slate-500' : 'min-h-screen bg-slate-100 px-4 py-12 text-center text-slate-500'}>
        Carregando chamado…
      </div>
    );
  }

  const statusPlain = stripHtml(ticket.status);

  const header = embedded ? (
    <div className="mb-4 border-b border-slate-200 pb-3 pt-4 sm:pt-5">
      {backHref ? (
        <a href={backHref} className="text-sm font-medium text-cyan-700 hover:underline">
          {backLabel}
        </a>
      ) : (
        <Link to="/cliente" className="text-sm font-medium text-cyan-700 hover:underline">
          {backLabel}
        </Link>
      )}
      <div className="mt-2 flex flex-wrap items-center gap-2">
        <h1 className="text-xl font-bold text-slate-900">#{ticket.id}</h1>
        <span
          className={`inline-flex rounded-full border px-2.5 py-0.5 text-xs font-semibold ${badgeClass(
            statusType(statusPlain)
          )}`}
        >
          {statusPlain}
        </span>
        {ticket.prioridade && ticket.prioridade !== '—' && (
          <span
            className={`inline-flex rounded-full border px-2.5 py-0.5 text-xs font-semibold ${badgeClass(
              priorityType(ticket.prioridade)
            )}`}
          >
            {ticket.prioridade}
          </span>
        )}
      </div>
      <p className="mt-1 text-xs text-slate-500">
        {ticket.atualizado}
        {ticket.responsavel && ticket.responsavel !== '—' ? ` · ${ticket.responsavel}` : ''}
      </p>
      <p className="mt-2 text-xs text-slate-400">
        Conversa e status atualizam automaticamente (~{Math.round(TICKET_COMMENTS_POLL_MS / 1000)}s) com a aba visível.
      </p>
    </div>
  ) : (
    <header className="border-b border-slate-200 bg-white shadow-sm">
      <div className="mx-auto max-w-5xl px-4 py-4 sm:px-6">
        <Link to="/cliente" className="text-sm font-medium text-cyan-700 hover:underline">
          {backLabel}
        </Link>
        <div className="mt-3 flex flex-wrap items-center gap-2">
          <h1 className="text-2xl font-bold text-slate-900">#{ticket.id}</h1>
          <span
            className={`inline-flex rounded-full border px-3 py-1 text-xs font-semibold ${badgeClass(
              statusType(statusPlain)
            )}`}
          >
            {statusPlain}
          </span>
        </div>
        <p className="mt-1 text-sm text-slate-500">
          {ticket.atualizado}
          {ticket.responsavel && ticket.responsavel !== '—' ? ` · ${ticket.responsavel}` : ''}
        </p>
      </div>
    </header>
  );

  const relatoTecnico = (ticket.descricaoAtendimento || '').trim();

  const descCard = (
    <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
      <h2 className="text-xs font-semibold uppercase tracking-wide text-slate-500">Assunto</h2>
      <p className="mt-1 text-base font-semibold text-slate-900">{stripHtml(ticket.assunto)}</p>
      <h2 className="mt-4 text-xs font-semibold uppercase tracking-wide text-slate-500">Descrição</h2>
      <div className="prose prose-sm mt-2 max-w-none whitespace-pre-wrap text-slate-700">{ticket.descricao}</div>
      {relatoTecnico ? (
        <>
          <h2 className="mt-4 text-xs font-semibold uppercase tracking-wide text-slate-500">Atendimento técnico</h2>
          <p className="mt-1 text-xs text-slate-500">O que foi feito pelo suporte neste chamado.</p>
          <div className="mt-2 rounded-md border border-teal-100 bg-teal-50/40 p-3 text-sm whitespace-pre-wrap text-slate-800">
            {relatoTecnico}
          </div>
        </>
      ) : null}
    </div>
  );

  const anexosCard = (
    <TicketAnexosPanel
      ticketId={ticket.id}
      anexos={ticket.anexos}
      onAnexosChange={(next) => setTicket((prev) => (prev ? { ...prev, anexos: next } : prev))}
      disabled={false}
    />
  );

  const bootNow = getBoot();
  const meuNome = USE_MOCK ? MOCK_SESSION_CLIENTE.name : (bootNow?.userName || '').trim();

  const chatCard = (
    <div className="flex min-h-[260px] max-h-[min(36rem,calc(100dvh-11rem))] flex-col overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm sm:max-h-[min(40rem,calc(100dvh-12rem))]">
      <div className="shrink-0 border-b border-slate-100 px-4 py-2">
        <h2 className="text-sm font-bold text-slate-900">Conversa</h2>
        <p className="text-xs text-slate-500">
          {meuNome
            ? `Você está como ${meuNome}. Cada mensagem fica gravada no chamado e no histórico (movimentações).`
            : 'Mensagens com o suporte — gravadas no chamado e no histórico.'}
        </p>
      </div>
      <ul className="min-h-0 flex-1 space-y-2 overflow-y-auto overscroll-contain p-3">
        {comentarios.length === 0 ? (
          <li className="rounded-lg border border-dashed border-slate-200 bg-slate-50/50 px-3 py-6 text-center text-sm text-slate-500">
            Nenhuma mensagem ainda. Escreva abaixo para falar com o suporte.
          </li>
        ) : (
          comentarios.map((c) => (
            <li
              key={c.id}
              className={`rounded-lg border px-3 py-2 text-sm ${
                c.pending
                  ? 'border-amber-200 bg-amber-50/80'
                  : c.papel === 'tecnico'
                    ? 'border-cyan-200 bg-cyan-50/50'
                    : 'border-slate-200 bg-slate-50/80'
              }`}
            >
              <div className="flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
                <div className="flex min-w-0 flex-wrap items-center gap-2">
                  <span className="font-semibold text-slate-900">{c.autor || '—'}</span>
                  <PapelBadge papel={c.papel} />
                </div>
                <time className="flex-shrink-0 text-slate-500">{c.quando}</time>
              </div>
              <CommentMessage texto={c.texto} />
            </li>
          ))
        )}
      </ul>
      <form onSubmit={handleComentario} className="shrink-0 border-t border-slate-100 p-3">
        <textarea
          value={texto}
          onChange={(e) => setTexto(e.target.value)}
          rows={3}
          className="w-full rounded-lg border border-slate-200 p-2 text-sm"
          placeholder="Escreva um comentário…"
        />
        <button
          type="submit"
          disabled={enviando}
          className="mt-2 rounded-lg bg-gradient-to-r from-cyan-600 to-teal-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"
        >
          {enviando ? 'Enviando…' : 'Enviar comentário'}
        </button>
      </form>
    </div>
  );

  const inner = (
    <>
      {erro && <p className="mb-2 rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-800">{erro}</p>}
      <div className="grid gap-4 lg:grid-cols-12 lg:items-start">
        <div className="min-w-0 space-y-4 lg:col-span-7">
          {descCard}
          {anexosCard}
        </div>
        <div className="min-w-0 lg:col-span-5">{chatCard}</div>
      </div>
    </>
  );

  if (embedded) {
    return (
      <div className="tickets-react-client-detail w-full text-slate-800">
        {header}
        {inner}
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-slate-100 text-slate-800">
      {header}
      <main className="mx-auto max-w-5xl px-4 py-6 sm:px-6">{inner}</main>
    </div>
  );
}
