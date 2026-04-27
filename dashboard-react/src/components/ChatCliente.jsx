import { useCallback, useEffect, useRef, useState } from 'react';
import { fetchRealtimeToken, fetchTicketMessages, getBoot, postTicketMessage } from '../lib/api';

const POLL_MS = 5000;

/**
 * Chat persistido (ticket_messages) com socket.io opcional e polling de fallback.
 */
export default function ChatCliente({ ticketId, embedded = true }) {
  const me = typeof window !== 'undefined' ? (getBoot() || {}).userId : null;
  const [rows, setRows] = useState([]);
  const [text, setText] = useState('');
  const [sending, setSending] = useState(false);
  const [err, setErr] = useState(null);
  const [socketOn, setSocketOn] = useState(false);
  const pollRef = useRef(null);
  const socketRef = useRef(null);

  const load = useCallback(async () => {
    const r = await fetchTicketMessages(ticketId);
    if (r.ok) {
      setRows(r.messages || []);
    }
  }, [ticketId]);

  useEffect(() => {
    load();
  }, [load]);

  useEffect(() => {
    let cancelled = false;
    (async () => {
      const boot = getBoot();
      // Com false no boot não carrega o chunk socket.io (tickets-index.js) nem chama a API.
      if (boot?.serviceDeskRealtimeSocket === false) {
        return;
      }
      const tok = await fetchRealtimeToken(ticketId);
      if (!tok.ok || !tok.url || !tok.token || typeof window === 'undefined' || cancelled) {
        return;
      }
      try {
        const { io } = await import('socket.io-client');
        const s = io(String(tok.url), {
          path: '/socket.io',
          auth: { token: tok.token },
          reconnectionAttempts: 3,
          transports: ['websocket', 'polling'],
        });
        socketRef.current = s;
        s.on('connect', () => {
          setSocketOn(true);
          s.emit('join_ticket', { ticketId: Number(ticketId) });
        });
        s.on('ticket_message', (p) => {
          if (p && String(p.ticketId) === String(ticketId) && p.message) {
            setRows((prev) => {
              if (prev.some((x) => x.id === p.message.id)) {
                return prev;
              }
              return [...prev, p.message].sort(
                (a, b) => new Date(a.created) - new Date(b.created),
              );
            });
          }
        });
        s.on('disconnect', () => setSocketOn(false));
      } catch {
        setSocketOn(false);
      }
    })();
    return () => {
      cancelled = true;
      try {
        socketRef.current?.disconnect();
      } catch {
        /* ignore */
      }
    };
  }, [ticketId]);

  useEffect(() => {
    if (socketOn) {
      if (pollRef.current) {
        clearInterval(pollRef.current);
        pollRef.current = null;
      }
      return;
    }
    pollRef.current = setInterval(() => {
      if (document.visibilityState === 'visible') {
        load();
      }
    }, POLL_MS);
    return () => {
      if (pollRef.current) {
        clearInterval(pollRef.current);
      }
    };
  }, [load, socketOn]);

  async function onSubmit(e) {
    e?.preventDefault?.();
    const t = text.trim();
    if (!t) return;
    setSending(true);
    setErr(null);
    const r = await postTicketMessage(ticketId, t);
    setSending(false);
    if (!r.ok) {
      setErr(r.error || 'Falha ao enviar');
      return;
    }
    setText('');
    setRows((prev) => {
      if (prev.some((x) => x.id === r.message.id)) {
        return prev;
      }
      return [...prev, r.message].sort(
        (a, b) => new Date(a.created) - new Date(b.created),
      );
    });
    try {
      socketRef.current?.emit('ticket_message_relay', {
        ticketId: Number(ticketId),
        message: r.message,
      });
    } catch {
      /* ignore */
    }
  }

  return (
    <div
      className={`flex max-h-[min(22rem,50vh)] flex-col overflow-hidden rounded-xl border border-[var(--pgm-border-subtle)] bg-[var(--pgm-bg-surface,#1a1f28)] shadow-[var(--pgm-shadow-md)] ${
        embedded ? '' : 'border-2'
      }`}
    >
      <div className="border-b border-[var(--pgm-border-subtle)] bg-[var(--pgm-bg-elevated)] px-3 py-2">
        <h3 className="text-[0.8rem] font-semibold text-[var(--pgm-text)]">Chat (mensagens)</h3>
        <p className="text-[0.65rem] text-[var(--pgm-text-muted)]">
          {socketOn ? 'Tempo real (socket)' : `Atualiza a cada ${POLL_MS / 1000}s`}
        </p>
      </div>
      <ul className="min-h-0 flex-1 space-y-2 overflow-y-auto p-3 text-sm">
        {rows.length === 0 ? (
          <li className="text-center text-[0.8rem] text-[var(--pgm-text-muted)]">Sem mensagens.</li>
        ) : (
          rows.map((m) => {
            const mine = me != null && m.userId != null && Number(m.userId) === Number(me);
            return (
              <li
                key={m.id}
                className={`flex ${mine ? 'justify-end' : 'justify-start'}`}
              >
                <div
                  className={`max-w-[min(100%,24rem)] rounded-2xl border px-3 py-2 ${
                    mine
                      ? 'border-[#0056b3]/40 bg-[#0056b3]/35 text-white'
                      : 'border-[var(--pgm-border-subtle)] bg-[var(--pgm-bg-elevated)] text-[var(--pgm-text)]'
                  }`}
                >
                  <div className="mb-1 flex flex-wrap justify-between gap-2 text-[0.65rem] text-[var(--pgm-text-muted)]">
                    <span className={mine ? 'font-semibold text-white/95' : 'font-semibold text-[var(--pgm-text)]'}>
                      {m.userName || '—'}
                    </span>
                    <time className="shrink-0 opacity-80">{m.created ? new Date(m.created).toLocaleString() : '—'}</time>
                  </div>
                  <p className={`whitespace-pre-wrap text-sm ${mine ? 'text-white' : 'text-[var(--pgm-text)]'}`}>{m.message}</p>
                </div>
              </li>
            );
          })
        )}
      </ul>
      {err && <p className="px-3 text-xs text-red-300">{err}</p>}
      <form onSubmit={onSubmit} className="flex gap-2 border-t border-[var(--pgm-border-subtle)] p-2">
        <input
          value={text}
          onChange={(e) => setText(e.target.value)}
          placeholder="Escrever…"
          className="flex-1 rounded-md border border-[var(--pgm-border)] bg-[var(--pgm-bg-raised)] px-2 py-1.5 text-sm text-[var(--pgm-text)]"
        />
        <button
          type="submit"
          disabled={sending}
          className="rounded-lg bg-[#0056b3] px-3 py-1.5 text-sm font-semibold text-white disabled:opacity-50"
        >
          {sending ? '…' : 'Enviar'}
        </button>
      </form>
    </div>
  );
}
