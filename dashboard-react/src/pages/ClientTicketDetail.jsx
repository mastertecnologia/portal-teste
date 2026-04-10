import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { fetchTicketDetail, postComentario, getBoot, USE_MOCK } from '../lib/api';
import { useTicketCommentsPoll, TICKET_COMMENTS_POLL_MS } from '../hooks/useTicketCommentsPoll';
import { useConversationScrollToBottom } from '../hooks/useConversationScrollToBottom';
import { MOCK_SESSION_CLIENTE } from '../data/mockData';
import { badgeClass, priorityType, statusType } from '../lib/ticketUi';
import { stripHtml } from '../lib/text';
import TicketAnexosPanel from '../components/TicketAnexosPanel.jsx';
import CommentMessage from '../components/CommentMessage.jsx';

function PapelBadge({ papel, embed }) {
  const isTech = papel === 'tecnico';
  if (embed) {
    return (
      <span
        className={`inline-flex rounded border px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide ${
          isTech
            ? 'border-cyan-700/50 bg-cyan-950/40 text-cyan-200'
            : 'border-[var(--pgm-border)] bg-[var(--pgm-bg-elevated)] text-[var(--pgm-text-secondary)]'
        }`}
      >
        {isTech ? 'Suporte' : 'Cliente'}
      </span>
    );
  }
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

/** Lista de tickets do cliente no embed (boot.paths ou resposta da API). */
function resolveClienteIndexUrl(boot, ticket) {
  const fromBoot = boot?.paths?.indexCliente;
  if (fromBoot) return fromBoot;
  const fromTicket = ticket?.urls?.indexCliente;
  if (fromTicket) return fromTicket;
  const w = boot?.webroot;
  if (w) return `${String(w).replace(/\/$/, '')}/tickets/indexcliente`;
  return null;
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

  const { listRef, onListScroll, pinToBottom } = useConversationScrollToBottom(comentarios);

  async function handleComentario(e) {
    e.preventDefault();
    const t = texto.trim();
    if (!t || !ticket) return;
    pinToBottom();
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
      setErro(res.error || 'Não foi possível enviar o comentário. Tente novamente.');
      setTexto(t);
    }
  }

  const backLabel = '← Meus tickets';

  if (erro && !ticket) {
    const backErr = embedded ? resolveClienteIndexUrl(boot, null) : null;
    return (
      <div
        className={
          embedded ? 'py-8 text-center text-[var(--pgm-text)]' : 'min-h-screen bg-slate-100 px-4 py-12 text-center'
        }
      >
        <p className={embedded ? 'text-red-300' : 'text-rose-700'}>{erro}</p>
        {embedded && backErr ? (
          <a href={backErr} className="mt-4 inline-block text-[var(--pgm-primary-hover)] underline hover:text-[var(--pgm-primary)]">
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
      <div
        className={
          embedded ? 'py-12 text-center text-[var(--pgm-text-muted)]' : 'min-h-screen bg-slate-100 px-4 py-12 text-center text-slate-500'
        }
      >
        Carregando chamado…
      </div>
    );
  }

  const statusPlain = stripHtml(ticket.status);
  const backHref = embedded ? resolveClienteIndexUrl(boot, ticket) : null;

  const clienteNome = stripHtml(ticket.cliente || '').trim() || '—';

  const header = embedded ? (
    <div className="tickets-react-ticket-strip relative z-20 mb-4 shrink-0 rounded-xl border border-[var(--pgm-border)] bg-[var(--pgm-bg-surface)] p-4 shadow-[0_4px_20px_rgba(0,0,0,0.35)]">
      {backHref ? (
        <a href={backHref} className="text-sm font-medium text-[var(--pgm-primary-hover)] hover:text-[var(--pgm-primary)] hover:underline">
          {backLabel}
        </a>
      ) : (
        <Link to="/cliente" className="text-sm font-medium text-[var(--pgm-primary-hover)] hover:text-[var(--pgm-primary)] hover:underline">
          {backLabel}
        </Link>
      )}
      <h1 className="tickets-react-ticket-title mt-2 text-xl font-bold leading-snug tracking-tight text-[var(--pgm-text)]">
        Ticket #{ticket.id}
      </h1>
      <p className="mt-1 flex flex-wrap items-center gap-2 text-sm text-[var(--pgm-text-muted)]">
        <span className="text-[var(--pgm-text-secondary)]">{clienteNome}</span>
        <span className="text-[var(--pgm-border)]">·</span>
        <span
          className={`inline-flex rounded-full border px-2.5 py-0.5 text-xs font-semibold ${badgeClass(
            statusType(statusPlain),
            true
          )}`}
        >
          {statusPlain}
        </span>
      </p>
      {ticket.prioridade && ticket.prioridade !== '—' ? (
        <p className="mt-1.5">
          <span
            className={`inline-flex rounded-full border px-2 py-0.5 text-[11px] font-semibold ${badgeClass(
              priorityType(ticket.prioridade),
              true
            )}`}
          >
            Severidade: {ticket.prioridade}
          </span>
        </p>
      ) : null}
      <p className="tickets-react-ticket-strip-muted mt-2 text-xs text-[var(--pgm-text-muted)]">
        {ticket.atualizado}
        {ticket.responsavel && ticket.responsavel !== '—' ? ` · ${ticket.responsavel}` : ''}
      </p>
      <p className="tickets-react-ticket-strip-hint mt-2 border-t border-[var(--pgm-border)] pt-2 text-xs text-[var(--pgm-text-muted)]">
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
          <h1 className="text-2xl font-bold text-slate-900">Ticket #{ticket.id}</h1>
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

  const descShell = embedded
    ? 'rounded-xl border border-[var(--pgm-border)] bg-[var(--pgm-bg-surface)] p-4 shadow-[0_4px_20px_rgba(0,0,0,0.35)]'
    : 'rounded-lg border border-slate-200 bg-white p-4 shadow-sm';
  const descLabel = embedded ? 'text-xs font-semibold uppercase tracking-wide text-[var(--pgm-text-muted)]' : 'text-xs font-semibold uppercase tracking-wide text-slate-500';
  const descTitle = embedded ? 'mt-1 text-base font-semibold text-[var(--pgm-text)]' : 'mt-1 text-base font-semibold text-slate-900';
  const descBody = embedded
    ? 'prose prose-sm mt-2 max-w-none whitespace-pre-wrap text-[var(--pgm-text-secondary)]'
    : 'prose prose-sm mt-2 max-w-none whitespace-pre-wrap text-slate-700';
  const techBox = embedded
    ? 'mt-2 rounded-lg border border-[var(--pgm-primary)]/45 bg-[var(--pgm-primary-muted)] p-3 text-sm whitespace-pre-wrap text-[var(--pgm-text)]'
    : 'mt-2 rounded-md border border-teal-100 bg-teal-50/40 p-3 text-sm whitespace-pre-wrap text-slate-800';

  const descCard = (
    <div className={descShell}>
      <h2 className={descLabel}>Assunto</h2>
      <p className={descTitle}>{stripHtml(ticket.assunto)}</p>
      <h2 className={`mt-4 ${descLabel}`}>Descrição</h2>
      <div className={descBody}>{ticket.descricao}</div>
      {relatoTecnico ? (
        <>
          <h2 className={`mt-4 ${descLabel}`}>Atendimento técnico</h2>
          <p className={embedded ? 'mt-1 text-xs text-[var(--pgm-text-muted)]' : 'mt-1 text-xs text-slate-500'}>
            O que foi feito pelo suporte neste chamado.
          </p>
          <div className={techBox}>{relatoTecnico}</div>
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
      embed={embedded}
    />
  );

  const bootNow = getBoot();
  const meuNome = USE_MOCK ? MOCK_SESSION_CLIENTE.name : (bootNow?.userName || '').trim();

  const chatShell = embedded
    ? 'flex h-[min(32rem,calc(100dvh-14rem))] min-h-[12rem] flex-col overflow-hidden rounded-xl border border-[var(--pgm-border)] bg-[var(--pgm-bg-surface)] shadow-[0_4px_20px_rgba(0,0,0,0.35)] [contain:layout] sm:h-[min(34rem,calc(100dvh-15rem))]'
    : 'flex h-[min(32rem,calc(100dvh-14rem))] min-h-[12rem] flex-col overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm [contain:layout] sm:h-[min(34rem,calc(100dvh-15rem))]';

  const chatCard = (
    <div className={chatShell}>
      <div className={embedded ? 'shrink-0 border-b border-[var(--pgm-border)] px-4 py-2' : 'shrink-0 border-b border-slate-100 px-4 py-2'}>
        <h2 className={embedded ? 'text-sm font-bold text-[var(--pgm-text)]' : 'text-sm font-bold text-slate-900'}>Conversa</h2>
        <p className={embedded ? 'text-xs text-[var(--pgm-text-muted)]' : 'text-xs text-slate-500'}>
          {meuNome
            ? `Você está como ${meuNome}. Cada mensagem fica gravada no chamado e no histórico (movimentações).`
            : 'Mensagens com o suporte — gravadas no chamado e no histórico.'}
        </p>
      </div>
      <ul
        ref={listRef}
        onScroll={onListScroll}
        className="min-h-0 flex-1 basis-0 space-y-2 overflow-y-auto overflow-x-hidden overscroll-contain p-3"
      >
        {comentarios.length === 0 ? (
          <li
            className={
              embedded
                ? 'rounded-lg border border-dashed border-[var(--pgm-border)] bg-[var(--pgm-bg-elevated)] px-3 py-6 text-center text-sm text-[var(--pgm-text-muted)]'
                : 'rounded-lg border border-dashed border-slate-200 bg-slate-50/50 px-3 py-6 text-center text-sm text-slate-500'
            }
          >
            Nenhuma mensagem ainda. Escreva abaixo para falar com o suporte.
          </li>
        ) : (
          comentarios.map((c) => (
            <li
              key={c.id}
              className={
                c.pending
                  ? embedded
                    ? 'rounded-lg border border-amber-700/50 bg-amber-950/35 px-3 py-2 text-sm text-amber-100'
                    : 'rounded-lg border border-amber-200 bg-amber-50/80 px-3 py-2 text-sm'
                  : c.papel === 'tecnico'
                    ? embedded
                      ? 'rounded-lg border border-cyan-700/50 bg-cyan-950/40 px-3 py-2 text-sm text-cyan-100'
                      : 'rounded-lg border border-cyan-200 bg-cyan-50/50 px-3 py-2 text-sm'
                    : embedded
                      ? 'rounded-lg border border-[var(--pgm-border)] bg-[var(--pgm-bg-elevated)] px-3 py-2 text-sm'
                      : 'rounded-lg border border-slate-200 bg-slate-50/80 px-3 py-2 text-sm'
              }
            >
              <div
                className={
                  embedded
                    ? 'flex flex-wrap items-center justify-between gap-2 text-xs text-[var(--pgm-text-muted)]'
                    : 'flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500'
                }
              >
                <div className="flex min-w-0 flex-wrap items-center gap-2">
                  <span className={embedded ? 'font-semibold text-[var(--pgm-text)]' : 'font-semibold text-slate-900'}>
                    {c.autor || '—'}
                  </span>
                  <PapelBadge papel={c.papel} embed={embedded} />
                </div>
                <time className={embedded ? 'flex-shrink-0 text-[var(--pgm-text-muted)]' : 'flex-shrink-0 text-slate-500'}>{c.quando}</time>
              </div>
              <CommentMessage texto={c.texto} embed={embedded} />
            </li>
          ))
        )}
      </ul>
      <form
        onSubmit={handleComentario}
        className={embedded ? 'shrink-0 border-t border-[var(--pgm-border)] p-3' : 'shrink-0 border-t border-slate-100 p-3'}
      >
        <textarea
          value={texto}
          onChange={(e) => setTexto(e.target.value)}
          rows={3}
          className={
            embedded
              ? 'w-full rounded-lg border border-[var(--pgm-border)] bg-[var(--pgm-bg-elevated)] p-2 text-sm text-[var(--pgm-text)] placeholder:text-[var(--pgm-text-muted)] focus:border-[var(--pgm-primary)] focus:outline-none'
              : 'w-full rounded-lg border border-slate-200 p-2 text-sm'
          }
          placeholder="Escreva um comentário…"
        />
        <button
          type="submit"
          disabled={enviando}
          className="mt-2 rounded-lg bg-[var(--pgm-primary)] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[var(--pgm-erp-teal-active)] disabled:opacity-50"
        >
          {enviando ? 'Enviando…' : 'Enviar comentário'}
        </button>
      </form>
    </div>
  );

  const inner = (
    <>
      {erro && (
        <p
          className={
            embedded
              ? 'mb-2 rounded-lg border border-red-800/50 bg-red-950/40 px-3 py-2 text-sm text-red-200'
              : 'mb-2 rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-800'
          }
        >
          {erro}
        </p>
      )}
      <div className="grid min-h-0 gap-4 lg:grid-cols-12 lg:items-start">
        <div className="min-h-0 min-w-0 space-y-4 lg:col-span-7">
          {descCard}
          {anexosCard}
        </div>
        <div className="min-h-0 min-w-0 self-start lg:col-span-5">{chatCard}</div>
      </div>
    </>
  );

  if (embedded) {
    return (
      <div className="tickets-react-client-detail flex min-h-0 w-full max-w-full flex-col overflow-x-hidden bg-transparent px-3 pb-4 pt-5 text-[var(--pgm-text)] sm:px-4 sm:pt-6">
        {header}
        <div className="min-h-0 flex-1">{inner}</div>
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
