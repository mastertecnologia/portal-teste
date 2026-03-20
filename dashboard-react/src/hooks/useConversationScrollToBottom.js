import { useCallback, useLayoutEffect, useRef } from 'react';

const NEAR_BOTTOM_PX = 100;

/**
 * Mantém a lista de mensagens “grudada” no fim quando o usuário já está no fim;
 * se ele rolou para cima para ler o histórico, não puxa de volta.
 * Chame pinToBottom() ao enviar mensagem.
 */
export function useConversationScrollToBottom(comentarios) {
  const listRef = useRef(null);
  const stickBottomRef = useRef(true);

  const onListScroll = useCallback(() => {
    const el = listRef.current;
    if (!el) return;
    const gap = el.scrollHeight - el.scrollTop - el.clientHeight;
    stickBottomRef.current = gap < NEAR_BOTTOM_PX;
  }, []);

  const pinToBottom = useCallback(() => {
    stickBottomRef.current = true;
  }, []);

  useLayoutEffect(() => {
    const el = listRef.current;
    if (!el) return;
    if (comentarios.length === 0) return;
    if (!stickBottomRef.current) return;
    el.scrollTop = el.scrollHeight;
  }, [comentarios]);

  return { listRef, onListScroll, pinToBottom };
}
