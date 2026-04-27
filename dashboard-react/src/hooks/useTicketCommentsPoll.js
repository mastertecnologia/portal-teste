import { useEffect, useRef } from 'react';
import { fetchTicketComments, USE_MOCK } from '../lib/api';

/** Intervalo padrão de sincronização da conversa (polling). */
export const TICKET_COMMENTS_POLL_MS = 2000;

/**
 * Atualiza comentários e status do ticket em intervalo (aba visível).
 * Não é WebSocket; reduz carga vs. api-view completo.
 */
export function useTicketCommentsPoll(
  ticketId,
  setComentarios,
  setTicket,
  intervalMs = TICKET_COMMENTS_POLL_MS,
  pausePolling = false,
) {
  const setC = useRef(setComentarios);
  const setT = useRef(setTicket);
  setC.current = setComentarios;
  setT.current = setTicket;

  useEffect(() => {
    if (USE_MOCK || !ticketId || pausePolling) {
      // #region agent log
      if (!USE_MOCK && ticketId) {
        fetch('http://127.0.0.1:7753/ingest/17010d6d-b722-4a03-aba9-a1bdf34f817d', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-Debug-Session-Id': 'efc03d' },
          body: JSON.stringify({
            sessionId: 'efc03d',
            hypothesisId: 'H4',
            location: 'useTicketCommentsPoll.js:effect',
            message: 'poll effect inactive',
            data: { ticketId, pausePolling, USE_MOCK },
            timestamp: Date.now(),
          }),
        }).catch(() => {});
      }
      // #endregion
      return;
    }
    const boot = typeof window !== 'undefined' ? window.__TICKETS_BOOT__ : null;
    if (!boot?.paths?.apiComments) return;

    const run = async () => {
      if (typeof document !== 'undefined' && document.visibilityState !== 'visible') return;
      const res = await fetchTicketComments(ticketId);
      if (!res.ok) return;
      // #region agent log
      const pendingN = (res.comentarios || []).filter((x) => x && x.pending === true).length;
      fetch('http://127.0.0.1:7753/ingest/17010d6d-b722-4a03-aba9-a1bdf34f817d', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Debug-Session-Id': 'efc03d' },
        body: JSON.stringify({
          sessionId: 'efc03d',
          hypothesisId: 'H5',
          location: 'useTicketCommentsPoll.js:run',
          message: 'poll applied comentarios',
          data: {
            ticketId,
            pausePolling,
            n: (res.comentarios || []).length,
            pendingN,
          },
          timestamp: Date.now(),
        }),
      }).catch(() => {});
      // #endregion
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
        if (res.descricao !== undefined) {
          next.descricao = res.descricao;
        }
        if (res.descricaoAtendimento !== undefined) {
          next.descricaoAtendimento = res.descricaoAtendimento;
        }
        // horasTecnicas: não mesclar aqui — GET /api-comments pode concluir após POST do timer
        // e sobrescrever estado fresco com snapshot antigo (pausa “zera”, Parar/Iniciar inconsistentes).
        if (res.responsavel !== undefined) {
          next.responsavel = res.responsavel;
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
  }, [ticketId, intervalMs, pausePolling]);
}
