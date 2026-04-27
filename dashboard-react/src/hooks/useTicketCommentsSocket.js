import { useEffect, useRef, useState } from 'react';
import { fetchRealtimeToken, getBoot } from '../lib/api';
import { commentSortKey } from '../lib/text';

/**
 * Socket.io para comentários do ticket (ticketcomentarios).
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
      return () => {};
    }
    (async () => {
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
        s.on('ticket_comment', (p) => {
          if (!p || String(p.ticketId) !== String(ticketId) || !p.comment) return;
          const msg = p.comment;
          if (msg.id == null) return;
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
