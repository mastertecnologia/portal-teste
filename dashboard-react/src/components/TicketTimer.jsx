import { useEffect, useLayoutEffect, useMemo, useRef, useState } from 'react';

/** Segundos: diff pequeno entre projeção local e novo payload — suaviza pulo no poll. */
const ANCHOR_SYNC_EPS_SEC = 2.5;

/**
 * Timer persistido no ticket (não confundir com AtendimentoTimer / apiTimer).
 * Em execução: âncora em `elapsed_seconds` do servidor no momento do payload + relógio local
 * (sem somar de novo `Date.now() - started_at` sobre `elapsed_seconds`).
 *
 * @param {{ situacao: number, attendimentoTimer?: { started_at?: string|null, total_seconds?: number, elapsed_seconds?: number, finished_at?: string|null } }} props.ticket
 * @param {number} props.situacaoExecCode — tickets.situacao numérico "em execução"
 * @param {{ source?: string, code?: string }} [props.effectiveStatus]
 */
export default function TicketTimer({ ticket, situacaoExecCode, effectiveStatus = null }) {
  const timer = ticket?.attendimentoTimer;
  const [tick, setTick] = useState(0);
  const exec = Number(situacaoExecCode);
  const wfCode = String(effectiveStatus?.code || '');
  const runningByWorkflow = effectiveStatus?.source === 'workflow'
    && (wfCode === 'emandamento' || wfCode === 'em_execucao' || wfCode === 'execucao' || wfCode === 'em_andamento');
  const running = runningByWorkflow || Number(ticket?.situacao) === exec;

  const anchorTicketIdRef = useRef(null);
  const anchorElapsedRef = useRef(0);
  const anchorAtRef = useRef(Date.now());

  useEffect(() => {
    if (!running) {
      anchorTicketIdRef.current = null;
      return undefined;
    }
    const id = window.setInterval(() => setTick((x) => x + 1), 1000);
    return () => window.clearInterval(id);
  }, [running, ticket?.id]);

  useLayoutEffect(() => {
    if (!running) return;

    const tid = ticket?.id;
    const incoming = Math.max(
      0,
      Number(timer?.elapsed_seconds ?? 0) || 0,
      Number(timer?.total_seconds ?? 0) || 0,
    );
    const now = Date.now();

    if (anchorTicketIdRef.current !== tid) {
      anchorTicketIdRef.current = tid;
      anchorElapsedRef.current = incoming;
      anchorAtRef.current = now;
      setTick((x) => x + 1);
      return;
    }

    const projected = anchorElapsedRef.current + (now - anchorAtRef.current) / 1000;

    if (incoming + 0.5 < projected) {
      return;
    }

    const diff = Math.abs(incoming - projected);
    if (diff <= ANCHOR_SYNC_EPS_SEC || incoming > projected + ANCHOR_SYNC_EPS_SEC) {
      anchorElapsedRef.current = incoming;
      anchorAtRef.current = now;
      setTick((x) => x + 1);
    }
  }, [running, ticket?.id, timer?.elapsed_seconds, timer?.total_seconds]);

  const displaySeconds = useMemo(() => {
    const staticDisplay = Math.max(
      0,
      Number(timer?.elapsed_seconds ?? 0) || 0,
      Number(timer?.total_seconds ?? 0) || 0,
    );
    if (!running) {
      return staticDisplay;
    }
    return Math.max(
      0,
      anchorElapsedRef.current + Math.floor((Date.now() - anchorAtRef.current) / 1000),
    );
  }, [running, timer?.elapsed_seconds, timer?.total_seconds, tick, ticket?.id]);

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
