import { useEffect, useRef } from 'react';
import { fetchTicketTimeline, USE_MOCK } from '../lib/api';

const DEFAULT_MS = 5000;

export function useTicketTimelinePoll(ticketId, setEvents, intervalMs = DEFAULT_MS) {
  const setE = useRef(setEvents);
  setE.current = setEvents;

  useEffect(() => {
    if (USE_MOCK || !ticketId) return;
    const boot = typeof window !== 'undefined' ? window.__TICKETS_BOOT__ : null;
    if (!boot?.paths?.apiTimeline) return;

    const run = async () => {
      if (typeof document !== 'undefined' && document.visibilityState !== 'visible') return;
      const res = await fetchTicketTimeline(ticketId);
      if (res.ok) setE.current(res.events || []);
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
