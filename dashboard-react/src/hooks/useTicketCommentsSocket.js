import { useEffect, useRef, useState } from 'react';
import { fetchRealtimeToken, getBoot } from '../lib/api';
import { commentSortKey } from '../lib/text';

/**
 * Socket.io para comentários da conversa (ticketcomentarios), espelhando o relay de ChatCliente.
 * Quando `serviceDeskRealtimeSocket` está ativo no boot, recebe `ticket_comment` na sala do ticket.
 */
export function useTicketCommentsSocket(ticketId, setComentarios) {
  const [socketOn, setSocketOn] = useState(false);
  const socketRef = useRef(null);
  const setC = useRef(setComentarios);
  setC.current = setComentarios;

  useEffect(() => {
    let cancelled = false;
    const boot = typeof window !== 'undefined' ? getBoot() : null;
    if (!ticketId || boot?.serviceDeskRealtimeSocket === false) {
      // #region agent log
      fetch('http://127.0.0.1:7753/ingest/17010d6d-b722-4a03-aba9-a1bdf34f817d', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Debug-Session-Id': 'efc03d' },
        body: JSON.stringify({
          sessionId: 'efc03d',
          hypothesisId: 'H2',
          location: 'useTicketCommentsSocket.js:skip',
          message: 'socket hook skipped or realtime false',
          data: {
            ticketId: ticketId ?? null,
            rtFlag: boot?.serviceDeskRealtimeSocket,
          },
          timestamp: Date.now(),
        }),
      }).catch(() => {});
      // #endregion
      return () => {};
    }
    (async () => {
      const tok = await fetchRealtimeToken(ticketId);
      if (!tok.ok || !tok.url || !tok.token || typeof window === 'undefined' || cancelled) {
        // #region agent log
        fetch('http://127.0.0.1:7753/ingest/17010d6d-b722-4a03-aba9-a1bdf34f817d', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-Debug-Session-Id': 'efc03d' },
          body: JSON.stringify({
            sessionId: 'efc03d',
            hypothesisId: 'H3',
            location: 'useTicketCommentsSocket.js:token',
            message: 'realtime token missing or failed',
            data: {
              ticketId,
              tokOk: tok.ok,
              hasUrl: Boolean(tok.url),
              hasToken: Boolean(tok.token),
              err: tok.error || null,
              cancelled,
            },
            timestamp: Date.now(),
          }),
        }).catch(() => {});
        // #endregion
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
          // #region agent log
          fetch('http://127.0.0.1:7753/ingest/17010d6d-b722-4a03-aba9-a1bdf34f817d', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Debug-Session-Id': 'efc03d' },
            body: JSON.stringify({
              sessionId: 'efc03d',
              hypothesisId: 'H3',
              location: 'useTicketCommentsSocket.js:connect',
              message: 'socket connected',
              data: { ticketId },
              timestamp: Date.now(),
            }),
          }).catch(() => {});
          // #endregion
        });
        s.on('ticket_comment', (p) => {
          if (!p || String(p.ticketId) !== String(ticketId) || !p.comment) return;
          const msg = p.comment;
          if (msg.id == null) return;
          // #region agent log
          fetch('http://127.0.0.1:7753/ingest/17010d6d-b722-4a03-aba9-a1bdf34f817d', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Debug-Session-Id': 'efc03d' },
            body: JSON.stringify({
              sessionId: 'efc03d',
              hypothesisId: 'H2',
              location: 'useTicketCommentsSocket.js:ticket_comment',
              message: 'received ticket_comment',
              data: { ticketId, commentId: msg.id },
              timestamp: Date.now(),
            }),
          }).catch(() => {});
          // #endregion
          setC.current((prev) => {
            const mid = Number(msg.id);
            if (prev.some((x) => Number(x.id) === mid)) return prev;
            const next = [...prev, msg];
            next.sort((a, b) => commentSortKey(a.id) - commentSortKey(b.id));
            return next;
          });
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
      socketRef.current = null;
      setSocketOn(false);
    };
  }, [ticketId]);

  return { socketRef, socketOn };
}
