import { useEffect, useMemo, useState } from 'react';
import {
  fetchTicketDetail,
  fetchTicketTimeline,
  postComentario,
  saveTicketSolicitacao,
  saveTicketDescricaoAtendimento,
  getBoot,
} from '../lib/api';
import { useTicketCommentsPoll, TICKET_COMMENTS_POLL_MS } from '../hooks/useTicketCommentsPoll';
import { useTicketTimelinePoll } from '../hooks/useTicketTimelinePoll';
import { useConversationScrollToBottom } from '../hooks/useConversationScrollToBottom';
import { stripHtml } from '../lib/text';
import { badgeClass, statusType } from '../lib/ticketUi';
import TicketAnexosPanel from '../components/TicketAnexosPanel.jsx';
import HorasTecnicasTimerPanel from '../components/HorasTecnicasTimerPanel.jsx';
import TicketTimeline from '../components/TicketTimeline.jsx';
import CommentMessage from '../components/CommentMessage.jsx';

/** `false` = não exibir links para o formulário legado (timer / anexos clássicos). O `boot` PHP continua enviando as URLs. */
const SHOW_LEGACY_TICKET_UI = false;

function resolveTechIndexUrl(boot) {
  if (boot?.paths?.indexTecnico) return boot.paths.indexTecnico;
  const w = boot?.webroot;
  if (w) return `${String(w).replace(/\/$/, '')}/tickets`;
  return null;
}

function PapelBadge({ papel }) {
  const isTech = papel === 'tecnico';
  return (
    <span
      className={`inline-flex rounded border px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide ${
        isTech
          ? 'border-[var(--pgm-border)] bg-[var(--pgm-bg-raised)] text-[var(--pgm-text-secondary)]'
          : 'border-[var(--pgm-border)] bg-[var(--pgm-bg-elevated)] text-[var(--pgm-text-secondary)]'
      }`}
    >
      {isTech ? 'Suporte' : 'Cliente'}
    </span>
  );
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
  const [timelineEvents, setTimelineEvents] = useState([]);
  /** 'chat' = conversa com bolhas (padrão); 'timeline' = eventos/horas/mov legado unificado */
  const [rightPanelTab, setRightPanelTab] = useState('chat');

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
      fetchTicketTimeline(id).then((tr) => {
        if (tr.ok) setTimelineEvents(tr.events || []);
      });
    })();
    return () => {
      c = true;
    };
  }, [id]);

  useTicketCommentsPoll(id, setComentarios, setTicket);
  useTicketTimelinePoll(id, setTimelineEvents);

  const { listRef, onListScroll, pinToBottom } = useConversationScrollToBottom(comentarios);

  /** Eventos técnicos (mov, horas, audit, assinatura…); comentários só na aba «Conversa». */
  const eventosOperacionais = useMemo(
    () => (timelineEvents || []).filter((ev) => (ev.type || '').toLowerCase() !== 'comment'),
    [timelineEvents]
  );

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
    const res = await postComentario(ticket.id, t);
    setEnviando(false);
    setComentarios((prev) => prev.filter((c) => c.id !== tmpId));
    if (res.ok) {
      setRightPanelTab('chat');
      setComentarios((prev) => [...prev, res.data]);
      const tr = await fetchTicketTimeline(ticket.id);
      if (tr.ok) setTimelineEvents(tr.events || []);
    } else {
      setErro(res.error || 'Não foi possível enviar o comentário. Tente novamente.');
      setTexto(t);
    }
  }

  async function handleSalvarDescricao(e) {
    e.preventDefault();
    if (!ticket?.flags?.canEditDescricao) return;
    setSalvando(true);
    setErro(null);
    const res = await saveTicketSolicitacao(ticket.id, desc);
    setSalvando(false);
    if (res.ok) {
      setErro(null);
    } else {
      setErro(res.error || 'Falha ao salvar.');
    }
  }

  async function handleSalvarRelatorioAtendimento(e) {
    e.preventDefault();
    if (!ticket?.flags?.canEditDescricaoAtendimento) return;
    setSalvandoRelatorio(true);
    setErro(null);
    const res = await saveTicketDescricaoAtendimento(ticket.id, relatorioAtendimento);
    setSalvandoRelatorio(false);
    if (res.ok) {
      setTicket((prev) => (prev ? { ...prev, descricaoAtendimento: relatorioAtendimento } : prev));
      setErro(null);
    } else {
      setErro(res.error || 'Falha ao salvar o relatório. Confira se o banco tem a coluna descricao_atendimento (SQL em config/schema).');
    }
  }

  if (erro && !ticket) {
    return (
      <div className={embedded ? 'py-8 text-center' : 'min-h-screen bg-[var(--pgm-bg-base,#0c0f14)] px-4 py-12 text-center text-[var(--pgm-text,#e8eaed)]'}>
        <p className="text-[var(--pgm-badge-red-text,#ff9492)]">{erro}</p>
        {boot?.paths?.indexTecnico && (
          <a href={boot.paths.indexTecnico} className="mt-4 inline-block text-[var(--pgm-accent,#5cdbc0)] underline">
            Voltar à listagem
          </a>
        )}
      </div>
    );
  }

  if (!ticket) {
    return (
      <div className={embedded ? 'py-12 text-center text-[var(--pgm-text-muted,#9aa0a8)]' : 'min-h-screen bg-[var(--pgm-bg-base,#0c0f14)] px-4 py-12 text-center text-[var(--pgm-text-muted,#9aa0a8)]'}>
        Carregando ticket…
      </div>
    );
  }

  const statusLine = stripHtml(ticket.status);
  const techListUrl = resolveTechIndexUrl(boot);

  const headerActions = (
    <div className="flex flex-shrink-0 flex-wrap gap-2">
      {techListUrl && (
        <a
          href={techListUrl || ticket.urls?.indexTecnico}
          className="inline-flex items-center gap-1.5 rounded-lg border border-[var(--pgm-border,#3d4554)] bg-transparent px-3 py-1.5 text-xs font-medium text-[var(--pgm-text,#e8eaed)] transition hover:bg-[var(--pgm-bg-overlay,#2a3140)] hover:border-[var(--pgm-border-strong,#4f5869)] sm:text-[0.8125rem]"
        >
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
          Voltar a Fila
        </a>
      )}
      {SHOW_LEGACY_TICKET_UI && boot?.classicEditUrl && (
        <a
          href={boot.classicEditUrl}
          className="inline-flex items-center gap-1.5 rounded-lg border border-[var(--pgm-border,#3d4554)] bg-transparent px-3 py-1.5 text-xs font-medium text-[var(--pgm-text,#e8eaed)] transition hover:bg-[var(--pgm-bg-overlay,#2a3140)] sm:text-[0.8125rem]"
        >
          Clássico (timer, anexos)
        </a>
      )}
      {ticket.urls?.imprimir && (
        <a
          href={ticket.urls.imprimir}
          target="_blank"
          rel="noreferrer"
          className="inline-flex items-center gap-1.5 rounded-lg border border-[var(--pgm-border,#3d4554)] bg-transparent px-3 py-1.5 text-xs font-medium text-[var(--pgm-text,#e8eaed)] transition hover:bg-[var(--pgm-bg-overlay,#2a3140)] hover:border-[var(--pgm-border-strong,#4f5869)] sm:text-[0.8125rem]"
        >
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"><path d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m0 0a48.159 48.159 0 0110.5 0m-10.5 0V5.25A2.25 2.25 0 019.5 3h5a2.25 2.25 0 012.25 2.25v3.231" /></svg>
          Imprimir
        </a>
      )}
      {ticket.flags?.canCancel && ticket.urls?.cancelar && (
        <a
          href={ticket.urls.cancelar}
          className="inline-flex items-center gap-1.5 rounded-lg border border-[var(--pgm-badge-red-ring,rgba(248,81,73,0.25))] bg-transparent px-3 py-1.5 text-xs font-medium text-[var(--pgm-badge-red-text,#ff9492)] transition hover:bg-[var(--pgm-badge-red-bg)] hover:border-[rgba(248,81,73,0.45)] sm:text-[0.8125rem]"
        >
          Cancelar Ticket
        </a>
      )}
    </div>
  );

  const header = embedded ? (
    <div className="relative z-20 mb-4 flex shrink-0 flex-wrap items-start justify-between gap-3 border-b border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-[var(--pgm-bg-elevated,#222834)] px-4 pb-3 pt-3">
      <div className="min-w-0">
        {techListUrl ? (
          <a href={techListUrl} className="inline-flex items-center gap-1 text-[0.8125rem] font-medium text-[var(--pgm-accent,#5cdbc0)] transition hover:text-white hover:underline">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
            Tickets
          </a>
        ) : null}
        <h1 className="mt-1 font-mono text-xl font-bold text-[var(--pgm-text,#e8eaed)]">Ticket #{ticket.id}</h1>
        <p className="flex items-center gap-2 text-[0.8125rem] text-[var(--pgm-text-muted,#9aa0a8)]">
          {stripHtml(ticket.cliente)}
          <span
            className={`inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold ${badgeClass(
              statusType(statusLine),
              embedded,
              Boolean(boot?.servicedesk)
            )}`}
          >
            {statusLine}
          </span>
        </p>
      </div>
      {headerActions}
    </div>
  ) : (
    <div className="border-b border-[var(--pgm-glass-border,rgba(255,255,255,0.08))] bg-[var(--pgm-glass-bg,rgba(26,31,40,0.72))] backdrop-blur-[var(--pgm-glass-blur,12px)]">
      <div className="mx-auto flex max-w-6xl flex-wrap items-start justify-between gap-3 px-4 py-4 sm:px-6">
        <div>
          <h1 className="font-mono text-[1.35rem] font-bold text-[var(--pgm-text,#e8eaed)]">Ticket #{ticket.id}</h1>
          <p className="flex flex-wrap items-center gap-2 text-[0.8125rem] text-[var(--pgm-text-muted,#9aa0a8)]">
            <span>{stripHtml(ticket.cliente)}</span>
            <span
              className={`inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-semibold ${badgeClass(
                statusType(statusLine),
                embedded,
                Boolean(boot?.servicedesk)
              )}`}
            >
              {statusLine}
            </span>
          </p>
        </div>
        {headerActions}
      </div>
    </div>
  );

  const alerts = (
    <>
      {erro && <p className="mb-2 rounded-lg border border-[rgba(248,113,113,0.35)] bg-[var(--pgm-badge-red-bg)] px-3 py-2 text-xs text-[var(--pgm-badge-red-text,#ff9492)] sm:text-sm">{erro}</p>}
      {embedded && (
        <p className="mb-3 text-xs text-[var(--pgm-text-muted,#9aa0a8)]">
          Comentários e status do ticket atualizam (~{Math.round(TICKET_COMMENTS_POLL_MS / 1000)}s) com a aba visível.
          Envio de e-mail não bloqueia a resposta (PHP-FPM).
        </p>
      )}
    </>
  );

  const horasTecnicasBlock = (
    <HorasTecnicasTimerPanel
      ticketId={ticket.id}
      horasTecnicas={ticket.horasTecnicas}
      canEditDescricaoAtendimento={Boolean(ticket.flags?.canEditDescricaoAtendimento)}
      onRelatorioSaved={(texto) => setRelatorioAtendimento(texto)}
      onSnapshot={(ht) => setTicket((p) => (p ? { ...p, horasTecnicas: ht } : p))}
      onFeedback={(_okMsg, errMsg) => {
        if (errMsg) {
          setErro(errMsg);
        } else {
          setErro(null);
        }
      }}
    />
  );

  const descricaoBlock = (
    <div className="overflow-hidden rounded-xl border border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-gradient-to-b from-[var(--pgm-bg-surface,#1a1f28)] to-[color-mix(in_srgb,var(--pgm-bg-surface,#1a1f28)_97%,rgba(255,255,255,0.03))] shadow-[var(--pgm-shadow-md)]">
      <div className="border-b border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-[var(--pgm-bg-elevated,#222834)] px-4 py-3">
        <h2 className="text-[0.85rem] font-semibold text-[var(--pgm-text,#e8eaed)]">{stripHtml(ticket.assunto)}</h2>
      </div>
      <div className="p-4">
        {ticket.flags?.canEditDescricao ? (
          <form onSubmit={handleSalvarDescricao} className="space-y-2">
            <label className="text-[0.7rem] font-semibold uppercase tracking-[0.06em] text-[var(--pgm-text-muted,#9aa0a8)]">Descrição (admin)</label>
            <textarea
              value={desc}
              onChange={(e) => setDesc(e.target.value)}
              rows={embedded ? 6 : 8}
              className="w-full rounded-md border border-[var(--pgm-border,#3d4554)] bg-[var(--pgm-bg-raised,#141820)] p-2.5 text-[0.875rem] text-[var(--pgm-text,#e8eaed)] outline-none transition placeholder:text-[var(--pgm-text-muted)] focus:border-[var(--pgm-primary)] focus:shadow-[0_0_0_3px_rgba(29,158,117,0.20),var(--pgm-shadow-glow)]"
            />
            <button
              type="submit"
              disabled={salvando}
              className="inline-flex items-center rounded-lg bg-gradient-to-b from-[var(--pgm-primary,#1d9e75)] to-[#168a64] px-3 py-1.5 text-sm font-semibold text-white shadow-[var(--pgm-shadow-sm),inset_0_1px_0_rgba(255,255,255,0.12)] transition hover:-translate-y-px hover:shadow-[var(--pgm-shadow-md)] disabled:opacity-50"
            >
              {salvando ? 'Salvando…' : 'Salvar descrição'}
            </button>
          </form>
        ) : (
          <div className="max-w-none whitespace-pre-wrap text-[0.875rem] leading-relaxed text-[var(--pgm-text-secondary,#c4c9d1)]">{ticket.descricao}</div>
        )}
      </div>
    </div>
  );

  const relatorioAtendimentoBlock =
    ticket.flags?.canEditDescricaoAtendimento ? (
      <div className="overflow-hidden rounded-xl border border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-gradient-to-b from-[var(--pgm-bg-surface,#1a1f28)] to-[color-mix(in_srgb,var(--pgm-bg-surface,#1a1f28)_97%,rgba(255,255,255,0.03))] shadow-[var(--pgm-shadow-md)]">
        <div className="border-b border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-[var(--pgm-bg-elevated,#222834)] px-4 py-3">
          <h2 className="text-[0.85rem] font-semibold text-[var(--pgm-text,#e8eaed)]">Relatório do atendimento</h2>
        </div>
        <div className="p-4">
          <p className="mb-3 text-xs text-[var(--pgm-text-muted,#9aa0a8)]">
            Descreva o que foi feito antes de finalizar o chamado (ex.: reparado pacote Office em modo online, ajuste de
            perfil de e-mail, etc.). O cliente verá este texto na visualização do ticket e na impressão.
          </p>
          <form onSubmit={handleSalvarRelatorioAtendimento} className="space-y-2">
            <textarea
              value={relatorioAtendimento}
              onChange={(e) => setRelatorioAtendimento(e.target.value)}
              rows={embedded ? 4 : 5}
              placeholder="Ex.: Reparado Outlook via reparo online do Office 365; testado envio e recebimento."
              className="w-full rounded-md border border-[var(--pgm-border,#3d4554)] bg-[var(--pgm-bg-raised,#141820)] p-2.5 text-[0.875rem] text-[var(--pgm-text,#e8eaed)] outline-none transition placeholder:text-[var(--pgm-text-muted)] focus:border-[var(--pgm-primary)] focus:shadow-[0_0_0_3px_rgba(29,158,117,0.20),var(--pgm-shadow-glow)]"
            />
            <button
              type="submit"
              disabled={salvandoRelatorio}
              className="inline-flex items-center rounded-lg bg-gradient-to-b from-[var(--pgm-primary,#1d9e75)] to-[#168a64] px-3 py-1.5 text-sm font-semibold text-white shadow-[var(--pgm-shadow-sm),inset_0_1px_0_rgba(255,255,255,0.12)] transition hover:-translate-y-px hover:shadow-[var(--pgm-shadow-md)] disabled:opacity-50"
            >
              {salvandoRelatorio ? 'Salvando…' : 'Salvar relatório do atendimento'}
            </button>
          </form>
        </div>
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
    <div className="flex h-[min(32rem,calc(100dvh-14rem))] min-h-[12rem] flex-col overflow-hidden rounded-xl border border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-gradient-to-b from-[var(--pgm-bg-surface,#1a1f28)] to-[color-mix(in_srgb,var(--pgm-bg-surface,#1a1f28)_97%,rgba(255,255,255,0.03))] shadow-[var(--pgm-shadow-md)] [contain:layout] sm:h-[min(34rem,calc(100dvh-15rem))]">
      <div className="shrink-0 space-y-2 border-b border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-[var(--pgm-bg-elevated,#222834)] px-3 py-2.5 sm:px-4">
        <div className="flex flex-wrap gap-1.5">
          <button
            type="button"
            onClick={() => setRightPanelTab('chat')}
            className={`rounded-lg px-3 py-1.5 text-xs font-semibold transition ${
              rightPanelTab === 'chat'
                ? 'bg-[var(--pgm-primary,#1d9e75)] text-white shadow-sm'
                : 'border border-[var(--pgm-border,#3d4554)] bg-transparent text-[var(--pgm-text-muted,#9aa0a8)] hover:border-[var(--pgm-border-strong)] hover:text-[var(--pgm-text,#e8eaed)]'
            }`}
          >
            Conversa
            {comentarios.length > 0 ? (
              <span className="ml-1 opacity-90">({comentarios.length})</span>
            ) : null}
          </button>
          <button
            type="button"
            onClick={() => setRightPanelTab('timeline')}
            className={`rounded-lg px-3 py-1.5 text-xs font-semibold transition ${
              rightPanelTab === 'timeline'
                ? 'bg-[var(--pgm-primary,#1d9e75)] text-white shadow-sm'
                : 'border border-[var(--pgm-border,#3d4554)] bg-transparent text-[var(--pgm-text-muted,#9aa0a8)] hover:border-[var(--pgm-border-strong)] hover:text-[var(--pgm-text,#e8eaed)]'
            }`}
          >
            Eventos (histórico)
            {eventosOperacionais.length > 0 ? (
              <span className="ml-1 opacity-90">({eventosOperacionais.length})</span>
            ) : null}
          </button>
        </div>
        <p className="text-[0.65rem] leading-snug text-[var(--pgm-text-muted,#9aa0a8)]">
          {rightPanelTab === 'chat'
            ? 'Mensagens com o cliente e a equipa. Movimentações, horas e logs ficam no separador «Eventos (histórico)» — sem misturar com a conversa.'
            : 'Apenas operação: movimentações, horas, auditoria, assinaturas, peças, etc. Comentários de chat estão em «Conversa».'}
        </p>
      </div>
      {rightPanelTab === 'chat' ? (
        <ul
          ref={listRef}
          onScroll={onListScroll}
          className="min-h-0 flex-1 basis-0 space-y-2 overflow-y-auto overflow-x-hidden overscroll-contain p-3"
        >
          {comentarios.length === 0 ? (
            <li className="rounded-lg border border-dashed border-[var(--pgm-border,#3d4554)] px-3 py-6 text-center text-[0.8125rem] text-[var(--pgm-text-muted,#9aa0a8)]">
              Nenhuma mensagem ainda. Escreva abaixo para contactar o cliente ou a equipa.
            </li>
          ) : (
            comentarios.map((c) => {
              return (
                <li
                  key={c.id}
                  className={`rounded-lg border px-3 py-2 text-sm ${
                    c.pending
                      ? 'border-amber-700/50 bg-amber-950/35 text-amber-100'
                      : c.papel === 'tecnico'
                        ? 'border-[var(--pgm-border)] bg-[var(--pgm-bg-raised)] text-[var(--pgm-text)]'
                        : 'border-[var(--pgm-border)] bg-[var(--pgm-bg-elevated)] text-[var(--pgm-text)]'
                  }`}
                >
                  <div className="mb-1 flex flex-wrap items-center justify-between gap-2 text-xs text-[var(--pgm-text-muted)]">
                    <div className="flex min-w-0 flex-wrap items-center gap-2">
                      <span className="font-semibold text-[var(--pgm-text)]">{c.autor || '—'}</span>
                      <PapelBadge papel={c.papel} />
                    </div>
                    <time className="flex-shrink-0">{c.quando}</time>
                  </div>
                  <CommentMessage texto={c.texto} />
                </li>
              );
            })
          )}
        </ul>
      ) : (
        <div className="min-h-0 flex-1 basis-0 overflow-y-auto overflow-x-hidden overscroll-contain p-3">
          {eventosOperacionais.length === 0 ? (
            <div className="rounded-lg border border-dashed border-[var(--pgm-border,#3d4554)] px-3 py-6 text-center text-[0.8125rem] text-[var(--pgm-text-muted,#9aa0a8)]">
              {Array.isArray(timelineEvents) && timelineEvents.length > 0
                ? 'Não há eventos operacionais (só comentários de conversa, na aba «Conversa»).'
                : 'Nenhum evento ainda. Registre horas ou aguarde movimentações no ticket.'}
            </div>
          ) : (
            <TicketTimeline events={eventosOperacionais} />
          )}
        </div>
      )}
      <div className="flex shrink-0 gap-2 border-t border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-[var(--pgm-bg-surface,#1a1f28)] p-3">
        <form onSubmit={handleComentario} className="flex flex-1 gap-2">
          <textarea
            value={texto}
            onChange={(e) => setTexto(e.target.value)}
            onKeyDown={(e) => {
              if ((e.ctrlKey || e.metaKey) && e.key === 'Enter' && texto.trim()) {
                e.preventDefault();
                handleComentario(e);
              }
            }}
            rows={2}
            placeholder="Novo comentário… (Ctrl+Enter para enviar)"
            className="min-h-[42px] flex-1 resize-vertical rounded-[var(--pgm-radius-lg,12px)] border border-[var(--pgm-border,#3d4554)] bg-[var(--pgm-bg-raised,#141820)] p-2.5 text-[0.8125rem] leading-relaxed text-[var(--pgm-text,#e8eaed)] outline-none transition placeholder:text-[var(--pgm-text-muted)] focus:border-[var(--pgm-primary)] focus:shadow-[0_0_0_3px_rgba(29,158,117,0.20),var(--pgm-shadow-glow)]"
          />
          <button
            type="submit"
            disabled={enviando}
            title="Enviar comentário"
            className="self-end rounded-lg bg-gradient-to-b from-[var(--pgm-primary,#1d9e75)] to-[#168a64] p-2.5 text-white shadow-[var(--pgm-shadow-sm),inset_0_1px_0_rgba(255,255,255,0.12)] transition hover:-translate-y-px hover:shadow-[var(--pgm-shadow-md),0_0_16px_rgba(29,158,117,0.25)] active:translate-y-0 disabled:opacity-50"
          >
            {enviando ? (
              <svg className="h-5 w-5 animate-pulse" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="12" cy="12" r="1" /><circle cx="19" cy="12" r="1" /><circle cx="5" cy="12" r="1" /></svg>
            ) : (
              <svg className="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" /></svg>
            )}
          </button>
        </form>
      </div>
    </div>
  );

  if (embedded) {
    return (
      <div className="tickets-react-edit flex min-h-0 w-full max-w-full flex-1 flex-col overflow-x-hidden text-[var(--pgm-text,#e8eaed)]">
        {header}
        <div className="min-h-0 flex-1 px-0">
          {alerts}
          <div className="grid min-h-0 gap-4 lg:grid-cols-12 lg:items-start">
            <div className="min-h-0 min-w-0 space-y-4 lg:col-span-7">
              {descricaoBlock}
              {horasTecnicasBlock}
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
    <div className="min-h-screen bg-[var(--pgm-bg-base,#0c0f14)] text-[var(--pgm-text,#e8eaed)]">
      {header}
      <main className="mx-auto max-w-6xl space-y-4 px-4 py-6 sm:px-6">
        {alerts}
        <div className="grid min-h-0 gap-4 lg:grid-cols-12 lg:items-start">
          <div className="min-h-0 min-w-0 space-y-4 lg:col-span-7">
            {descricaoBlock}
            {horasTecnicasBlock}
            {relatorioAtendimentoBlock}
            {anexosBlock}
          </div>
          <div className="min-h-0 min-w-0 self-start lg:col-span-5">{comentariosBlock}</div>
        </div>
      </main>
    </div>
  );
}
