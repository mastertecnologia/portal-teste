import { useEffect, useRef } from 'react';
import { fetchTicketComments, USE_MOCK } from '../lib/api';

/** Intervalo padrão de sincronização da conversa (polling). */
export const TICKET_COMMENTS_POLL_MS = 2500;

/**
 * Atualiza comentários e status do ticket em intervalo (aba visível).
 * Não é WebSocket; reduz carga vs. api-view completo.
 */
export function useTicketCommentsPoll(ticketId, setComentarios, setTicket, intervalMs = TICKET_COMMENTS_POLL_MS) {
  const setC = useRef(setComentarios);
  const setT = useRef(setTicket);
  setC.current = setComentarios;
  setT.current = setTicket;

  useEffect(() => {
    if (USE_MOCK || !ticketId) return;
    const boot = typeof window !== 'undefined' ? window.__TICKETS_BOOT__ : null;
    if (!boot?.paths?.apiComments) return;

    const run = async () => {
      if (typeof document !== 'undefined' && document.visibilityState !== 'visible') return;
      const res = await fetchTicketComments(ticketId);
      if (!res.ok) return;
      setC.current(res.comentarios);
      setT.current((prev) => {
        if (!prev) return prev;
        const next = { ...prev };
        // Sempre aplicar situacao quando a API envia (inclui 0 = pendente).
        if (res.situacao !== undefined && res.situacao !== null) {
          next.situacao = res.situacao;
        }
        if (res.status !== undefined && res.status !== null && res.status !== '') {
          next.status = res.status;
        }
        return next;
      });
    };

    const iv = setInterval(run, intervalMs);
    const onVis = () => {
      if (document.visibilityState === 'visible') run();
    };
    document.addEventListener('visibilitychange', onVis);
    run();
    return () => {
      clearInterval(iv);
      document.removeEventListener('visibilitychange', onVis);
    };
  }, [ticketId, intervalMs]);
}
