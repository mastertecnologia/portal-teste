import { useEffect, useMemo, useState } from 'react';

function parseIso(s) {
  if (s == null || s === '') return null;
  const d = new Date(s);
  return Number.isNaN(d.getTime()) ? null : d;
}

/**
 * Timer persistido no ticket (não confundir com AtendimentoTimer / apiTimer).
 * Exibição: total_seconds + (agora − started_at) quando status = em execução.
 *
 * @param {{ situacao: number, attendimentoTimer?: { started_at?: string|null, total_seconds?: number, finished_at?: string|null } }} props.ticket
 * @param {number} props.situacaoExecCode — tickets.situacao numérico "em execução"
 */
export default function TicketTimer({ ticket, situacaoExecCode }) {
  const timer = ticket?.attendimentoTimer;
  const [tick, setTick] = useState(0);
  const exec = Number(situacaoExecCode);
  const running = Number(ticket?.situacao) === exec;

  useEffect(() => {
    if (!running) return undefined;
    const id = window.setInterval(() => setTick((x) => x + 1), 1000);
    return () => window.clearInterval(id);
  }, [running, ticket?.id, timer?.started_at, timer?.total_seconds]);

  const displaySeconds = useMemo(() => {
    const base = Number(timer?.total_seconds) || 0;
    if (!running || !timer?.started_at) {
      return Math.max(0, base);
    }
    const st = parseIso(timer.started_at);
    if (!st) {
      return Math.max(0, base);
    }
    return Math.max(0, base + Math.floor((Date.now() - st.getTime()) / 1000));
  }, [timer?.total_seconds, timer?.started_at, running, tick]);

  const hh = String(Math.floor(displaySeconds / 3600)).padStart(2, '0');
  const mm = String(Math.floor((displaySeconds % 3600) / 60)).padStart(2, '0');
  const ss = String(displaySeconds % 60).padStart(2, '0');

  return (
    <span
      className="font-mono text-[11px] tabular-nums text-[var(--pgm-text-secondary)]"
      title={timer?.finished_at ? `Encerrado: ${timer.finished_at}` : running ? 'Em atendimento' : 'Pausado / aguardando'}
    >
      {hh}:{mm}:{ss}
    </span>
  );
}
