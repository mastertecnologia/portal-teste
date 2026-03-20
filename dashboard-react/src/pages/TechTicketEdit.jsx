import { useEffect, useState } from 'react';
import {
  fetchTicketDetail,
  postComentario,
  saveTicketSolicitacao,
  saveTicketDescricaoAtendimento,
  getBoot,
} from '../lib/api';
import { useTicketCommentsPoll, TICKET_COMMENTS_POLL_MS } from '../hooks/useTicketCommentsPoll';
import { useConversationScrollToBottom } from '../hooks/useConversationScrollToBottom';
import { stripHtml } from '../lib/text';
import TicketAnexosPanel from '../components/TicketAnexosPanel.jsx';
import CommentMessage from '../components/CommentMessage.jsx';

/** `false` = não exibir links para o formulário legado (timer / anexos clássicos). O `boot` PHP continua enviando as URLs. */
const SHOW_LEGACY_TICKET_UI = false;

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

function resolveTechIndexUrl(boot) {
  if (boot?.paths?.indexTecnico) return boot.paths.indexTecnico;
  const w = boot?.webroot;
  if (w) return `${String(w).replace(/\/$/, '')}/tickets`;
  return null;
}

export default function TechTicketEdit({ boot }) {
  const id = boot?.ticketId;
  const embedded = Boolean(boot);
  const [ticket, setTicket] = useState(null);
  const [comentarios, setComentarios] = useState([]);
  const [texto, setTexto] = useState('');
  const [desc, setDesc] = useState('');
  const [relatorioAtendimento, setRelatorioAtendimento] = useState('');
  const [enviando, setEnviando] = useState(false);
  const [salvando, setSalvando] = useState(false);
  const [salvandoRelatorio, setSalvandoRelatorio] = useState(false);
  const [erro, setErro] = useState(null);
  const [msg, setMsg] = useState(null);

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
      setDesc(res.data.descricao || '');
      setRelatorioAtendimento(res.data.descricaoAtendimento || '');
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
    const papel = (b?.role ?? 1) === 0 ? 'tecnico' : 'cliente';
    const nome = (b?.userName || 'Eu').trim();
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
    setMsg(null);
    const res = await postComentario(ticket.id, t);
    setEnviando(false);
    setComentarios((prev) => prev.filter((c) => c.id !== tmpId));
    if (res.ok) {
      setComentarios((prev) => [...prev, res.data]);
      setMsg('Comentário enviado.');
    } else {
      setErro('Não foi possível enviar o comentário.');
      setTexto(t);
    }
  }

  async function handleSalvarDescricao(e) {
    e.preventDefault();
    if (!ticket?.flags?.canEditDescricao) return;
    setSalvando(true);
    setMsg(null);
    const res = await saveTicketSolicitacao(ticket.id, desc);
    setSalvando(false);
    if (res.ok) setMsg('Descrição salva.');
    else setErro(res.error || 'Falha ao salvar.');
  }

  async function handleSalvarRelatorioAtendimento(e) {
    e.preventDefault();
    if (!ticket?.flags?.canEditDescricaoAtendimento) return;
    setSalvandoRelatorio(true);
    setMsg(null);
    setErro(null);
    const res = await saveTicketDescricaoAtendimento(ticket.id, relatorioAtendimento);
    setSalvandoRelatorio(false);
    if (res.ok) {
      setTicket((prev) => (prev ? { ...prev, descricaoAtendimento: relatorioAtendimento } : prev));
      setMsg('Relatório do atendimento salvo.');
    } else {
      setErro(res.error || 'Falha ao salvar o relatório. Confira se o banco tem a coluna descricao_atendimento (SQL em config/schema).');
    }
  }

  if (erro && !ticket) {
    return (
      <div className={embedded ? 'py-8 text-center' : 'min-h-screen bg-slate-100 px-4 py-12 text-center'}>
        <p className="text-rose-700">{erro}</p>
        {boot?.paths?.indexTecnico && (
          <a href={boot.paths.indexTecnico} className="mt-4 inline-block text-teal-700 underline">
            Voltar à listagem
          </a>
        )}
      </div>
    );
  }

  if (!ticket) {
    return (
      <div className={embedded ? 'py-12 text-center text-slate-500' : 'min-h-screen bg-slate-100 px-4 py-12 text-center text-slate-500'}>
        Carregando ticket…
      </div>
    );
  }

  const statusLine = stripHtml(ticket.status);

  const headerActions = (
    <div className="flex flex-shrink-0 flex-wrap gap-2">
      {SHOW_LEGACY_TICKET_UI && boot?.classicEditUrl && (
        <a
          href={boot.classicEditUrl}
          className="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-800 hover:bg-slate-50 sm:text-sm"
        >
          Clássico (timer, anexos)
        </a>
      )}
      {ticket.urls?.imprimir && (
        <a
          href={ticket.urls.imprimir}
          target="_blank"
          rel="noreferrer"
          className="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 sm:text-sm"
        >
          Imprimir
        </a>
      )}
    </div>
  );

  const techListUrl = resolveTechIndexUrl(boot);

  const header = embedded ? (
    <div className="relative z-20 mb-4 flex shrink-0 flex-wrap items-start justify-between gap-3 border-b border-slate-200 bg-slate-50 pb-3 pt-1 shadow-sm">
      <div className="min-w-0">
        {techListUrl ? (
          <a href={techListUrl} className="text-sm font-medium text-teal-700 hover:underline">
            ← Tickets
          </a>
        ) : null}
        <h1 className="mt-1 text-xl font-bold text-slate-900">Ticket #{ticket.id}</h1>
        <p className="text-sm text-slate-600">
          {stripHtml(ticket.cliente)} · <span className="font-medium text-slate-800">{statusLine}</span>
        </p>
      </div>
      {headerActions}
    </div>
  ) : (
    <div className="border-b border-slate-200 bg-white shadow-sm">
      <div className="mx-auto flex max-w-6xl flex-wrap items-start justify-between gap-3 px-4 py-4 sm:px-6">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">Ticket #{ticket.id}</h1>
          <p className="text-sm text-slate-500">
            {stripHtml(ticket.cliente)} · {statusLine}
          </p>
        </div>
        {headerActions}
      </div>
    </div>
  );

  const alerts = (
    <>
      {msg && (
        <p className="mb-2 rounded-lg bg-emerald-50 px-3 py-2 text-xs text-emerald-800 sm:text-sm">{msg}</p>
      )}
      {erro && <p className="mb-2 rounded-lg bg-rose-50 px-3 py-2 text-xs text-rose-800 sm:text-sm">{erro}</p>}
      {embedded && (
        <p className="mb-3 text-xs text-slate-400">
          Comentários e status do ticket atualizam (~{Math.round(TICKET_COMMENTS_POLL_MS / 1000)}s) com a aba visível.
          Envio de e-mail não bloqueia a resposta (PHP-FPM).
        </p>
      )}
    </>
  );

  const descricaoBlock = (
    <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
      <h2 className="text-base font-bold text-slate-900">{stripHtml(ticket.assunto)}</h2>
      {ticket.flags?.canEditDescricao ? (
        <form onSubmit={handleSalvarDescricao} className="mt-3 space-y-2">
          <label className="text-xs font-medium text-slate-600">Descrição (admin)</label>
          <textarea
            value={desc}
            onChange={(e) => setDesc(e.target.value)}
            rows={embedded ? 6 : 8}
            className="w-full rounded-lg border border-slate-200 p-2 text-sm"
          />
          <button
            type="submit"
            disabled={salvando}
            className="rounded-lg bg-teal-700 px-3 py-1.5 text-sm font-semibold text-white disabled:opacity-50"
          >
            {salvando ? 'Salvando…' : 'Salvar descrição'}
          </button>
        </form>
      ) : (
        <div className="prose prose-sm mt-3 max-w-none whitespace-pre-wrap text-slate-700">{ticket.descricao}</div>
      )}
    </div>
  );

  const relatorioAtendimentoBlock =
    ticket.flags?.canEditDescricaoAtendimento ? (
      <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <h2 className="text-sm font-bold text-slate-900">Relatório do atendimento</h2>
        <p className="mt-1 text-xs text-slate-500">
          Descreva o que foi feito antes de finalizar o chamado (ex.: reparado pacote Office em modo online, ajuste de
          perfil de e-mail, etc.). O cliente verá este texto na visualização do ticket e na impressão.
        </p>
        <form onSubmit={handleSalvarRelatorioAtendimento} className="mt-3 space-y-2">
          <textarea
            value={relatorioAtendimento}
            onChange={(e) => setRelatorioAtendimento(e.target.value)}
            rows={embedded ? 4 : 5}
            placeholder="Ex.: Reparado Outlook via reparo online do Office 365; testado envio e recebimento."
            className="w-full rounded-lg border border-slate-200 p-2 text-sm"
          />
          <button
            type="submit"
            disabled={salvandoRelatorio}
            className="rounded-lg bg-cyan-700 px-3 py-1.5 text-sm font-semibold text-white disabled:opacity-50"
          >
            {salvandoRelatorio ? 'Salvando…' : 'Salvar relatório do atendimento'}
          </button>
        </form>
      </div>
    ) : null;

  const anexosBlock = (
    <TicketAnexosPanel
      ticketId={ticket.id}
      anexos={ticket.anexos}
      onAnexosChange={(next) => setTicket((prev) => (prev ? { ...prev, anexos: next } : prev))}
      disabled={false}
    />
  );

  const comentariosBlock = (
    <div className="flex h-[min(32rem,calc(100dvh-14rem))] min-h-[12rem] flex-col overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm [contain:layout] sm:h-[min(34rem,calc(100dvh-15rem))]">
      <div className="shrink-0 border-b border-slate-100 px-4 py-2">
        <h3 className="text-sm font-bold text-slate-900">Conversa</h3>
        <p className="text-xs text-slate-500">
          Nome do autor vem do cadastro de usuário. Comentários ficam em ticket + movimentações (trecho no histórico).
        </p>
      </div>
      <ul
        ref={listRef}
        onScroll={onListScroll}
        className="min-h-0 flex-1 basis-0 space-y-2 overflow-y-auto overflow-x-hidden overscroll-contain p-3"
      >
        {comentarios.length === 0 ? (
          <li className="rounded-lg border border-dashed border-slate-200 bg-slate-50/50 px-3 py-6 text-center text-sm text-slate-500">
            Nenhum comentário ainda.
          </li>
        ) : (
          comentarios.map((c) => (
            <li
              key={c.id}
              className={`rounded-lg border px-3 py-2 text-sm ${
                c.pending
                  ? 'border-amber-200 bg-amber-50/80'
                  : c.papel === 'tecnico'
                    ? 'border-cyan-200 bg-cyan-50/40'
                    : 'border-slate-200 bg-slate-50/90'
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
          placeholder="Novo comentário…"
          className="w-full rounded-lg border border-slate-200 p-2 text-sm"
        />
        <button
          type="submit"
          disabled={enviando}
          className="mt-2 rounded-lg bg-slate-900 px-3 py-1.5 text-sm font-semibold text-white disabled:opacity-50"
        >
          {enviando ? 'Enviando…' : 'Enviar'}
        </button>
      </form>
    </div>
  );

  if (embedded) {
    return (
      <div className="tickets-react-edit flex min-h-0 w-full max-w-full flex-col overflow-x-hidden text-slate-800">
        {header}
        <div className="min-h-0 flex-1 px-0">
          {alerts}
          <div className="grid min-h-0 gap-4 lg:grid-cols-12 lg:items-start">
            <div className="min-h-0 min-w-0 space-y-4 lg:col-span-7">
              {descricaoBlock}
              {relatorioAtendimentoBlock}
              {anexosBlock}
            </div>
            <div className="min-h-0 min-w-0 self-start lg:col-span-5">{comentariosBlock}</div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-slate-100 text-slate-800">
      {header}
      <main className="mx-auto max-w-6xl space-y-4 px-4 py-6 sm:px-6">
        {alerts}
        <div className="grid min-h-0 gap-4 lg:grid-cols-12 lg:items-start">
          <div className="min-h-0 min-w-0 space-y-4 lg:col-span-7">
            {descricaoBlock}
            {relatorioAtendimentoBlock}
            {anexosBlock}
          </div>
          <div className="min-h-0 min-w-0 self-start lg:col-span-5">{comentariosBlock}</div>
        </div>
      </main>
    </div>
  );
}
