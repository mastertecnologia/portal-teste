import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { postTimerAction } from '../lib/api';

function parseServerDateTime(s) {
  if (!s || typeof s !== 'string') return null;
  const d = new Date(s.replace(' ', 'T'));
  return Number.isNaN(d.getTime()) ? null : d;
}

function localSqlDateTime() {
  const d = new Date();
  const p = (n) => (n < 10 ? `0${n}` : String(n));
  return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())} ${p(d.getHours())}:${p(d.getMinutes())}:${p(d.getSeconds())}`;
}

function formatHms(totalSeconds) {
  const sec = Math.max(0, Math.floor(totalSeconds));
  const h = Math.floor(sec / 3600);
  const m = Math.floor((sec % 3600) / 60);
  const s = sec % 60;
  const pad = (n) => (n < 10 ? `0${n}` : String(n));
  return `${pad(h)}:${pad(m)}:${pad(s)}`;
}

function minutosLabel(totalMin) {
  const m = Math.max(0, Math.floor(Number(totalMin) || 0));
  const h = Math.floor(m / 60);
  const r = m % 60;
  if (h <= 0) return `${r} min`;
  if (r === 0) return `${h} h`;
  return `${h} h ${r} min`;
}

/**
 * Cronômetro de horas técnicas: contagem fluida (rAF), atualização otimista nos cliques,
 * layout claro alinhado ao Service Desk (três ações sempre visíveis).
 */
export default function HorasTecnicasTimerPanel({ ticketId, horasTecnicas, disabled, onSnapshot, onFeedback }) {
  const [optimistic, setOptimistic] = useState(null);
  const [busy, setBusy] = useState(false);
  const [rafTick, setRafTick] = useState(0);
  const offsetRef = useRef(0);
  const rollbackRef = useRef(null);

  const snap = horasTecnicas || {};
  const canUse = Boolean(snap.canUseTimer);
  const disponivel = snap.timerDisponivel !== false;
  const serverSessao = snap.sessao || null;
  const serverUnix = typeof snap.serverUnix === 'number' ? snap.serverUnix : null;

  const sessao = optimistic ?? serverSessao;

  useEffect(() => {
    if (serverUnix != null) {
      offsetRef.current = serverUnix * 1000 - Date.now();
    }
  }, [serverUnix, serverSessao?.id, serverSessao?.horaInicio, serverSessao?.horaPausa, serverSessao?.pausado]);

  const nowMs = useCallback(() => Date.now() + offsetRef.current, []);

  const elapsedSeconds = useMemo(() => {
    if (!sessao?.horaInicio) return 0;
    const start = parseServerDateTime(sessao.horaInicio);
    if (!start) return 0;
    if (sessao.pausado && sessao.horaPausa) {
      const pause = parseServerDateTime(sessao.horaPausa);
      if (!pause) return 0;
      return Math.max(0, Math.floor((pause.getTime() - start.getTime()) / 1000));
    }
    return Math.max(0, Math.floor((nowMs() - start.getTime()) / 1000));
  }, [sessao, rafTick, nowMs]);

  useEffect(() => {
    if (!sessao || sessao.pausado) return undefined;
    let id = 0;
    const loop = () => {
      setRafTick((t) => (t + 1) % 1_000_000);
      id = requestAnimationFrame(loop);
    };
    id = requestAnimationFrame(loop);
    return () => cancelAnimationFrame(id);
  }, [sessao?.id, sessao?.pausado]);

  async function runAction(action) {
    if (!ticketId) return;
    if (action === 'finalizar') {
      const ok = window.confirm('Finalizar o timer e registrar as horas no ticket e no contrato do cliente?');
      if (!ok) return;
    }

    rollbackRef.current = optimistic;

    if (action === 'iniciar') {
      setOptimistic({
        id: 'local',
        horaInicio: localSqlDateTime(),
        horaPausa: null,
        pausado: false,
      });
    } else if (action === 'pausar' && sessao?.horaInicio) {
      setOptimistic({
        ...sessao,
        pausado: true,
        horaPausa: localSqlDateTime(),
      });
    } else if (action === 'retomar' && sessao?.horaInicio) {
      setOptimistic({
        ...sessao,
        pausado: false,
        horaPausa: null,
      });
    } else if (action === 'finalizar') {
      rollbackRef.current = optimistic ?? serverSessao;
      setOptimistic(null);
    }

    setBusy(true);
    const res = await postTimerAction(ticketId, action);
    setBusy(false);

    if (res.ok) {
      setOptimistic(null);
      if (res.horasTecnicas && onSnapshot) {
        onSnapshot(res.horasTecnicas);
      }
      if (onFeedback) onFeedback(res.message || null, null);
    } else {
      if (action === 'finalizar') {
        setOptimistic(rollbackRef.current);
      } else {
        setOptimistic(null);
      }
      if (onFeedback) {
        onFeedback(null, res.message || res.error || 'Não foi possível atualizar o timer.');
      }
    }
    return res;
  }

  if (!canUse) {
    return null;
  }

  if (!disponivel) {
    return (
      <div className="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
        <h2 className="text-sm font-bold">Horas técnicas</h2>
        <p className="mt-1 text-xs">
          Timer indisponível (tabela ou colunas). Use o formulário clássico ou execute o script de verificação do atendimento_timer.
        </p>
      </div>
    );
  }

  const registrados = snap.minutosRegistrados ?? 0;
  const paused = Boolean(sessao && sessao.pausado);
  const idle = !sessao;

  return (
    <div className="overflow-hidden rounded-xl border border-slate-200/90 bg-white shadow-sm">
      <div className="border-b border-slate-200 bg-slate-100/90 px-4 py-3">
        <h2 className="text-[0.9rem] font-bold tracking-tight text-slate-900">Horas técnicas</h2>
      </div>
      <div className="p-4">
        <p className="text-xs text-slate-600">
          Tempo já lançado neste ticket:{' '}
          <span className="font-semibold text-slate-800">{minutosLabel(registrados)}</span>. Ao finalizar, o sistema grava em
          Horas cadastradas e desconta do contrato do cliente.
        </p>

        <div className="mt-3 text-center">
          <div
            className={`mb-3 rounded-lg border px-4 py-3 font-mono text-[2rem] font-bold tracking-[0.12em] transition-colors ${
              paused
                ? 'border-amber-200 bg-amber-50/80 text-amber-800'
                : 'border-slate-200 bg-slate-50 text-[#155E4A]'
            }`}
          >
            {formatHms(elapsedSeconds)}
            {paused ? <span className="ml-2 font-sans text-sm font-medium text-amber-800/90">(pausado)</span> : null}
          </div>

          <div className="flex flex-wrap justify-center gap-2">
            <button
              type="button"
              disabled={disabled || busy || !idle}
              onClick={() => runAction('iniciar')}
              className="inline-flex items-center gap-1.5 rounded-full bg-[#2daa6a] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:brightness-105 disabled:cursor-not-allowed disabled:opacity-45"
            >
              <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden>
                <path d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z" />
              </svg>
              {busy && idle ? '…' : 'Iniciar'}
            </button>

            {paused ? (
              <button
                type="button"
                disabled={disabled || busy}
                onClick={() => runAction('retomar')}
                className="inline-flex items-center gap-1.5 rounded-full border border-sky-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 shadow-sm transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-45"
              >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden>
                  <path d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z" />
                </svg>
                Retomar
              </button>
            ) : (
              <button
                type="button"
                disabled={disabled || busy || idle}
                onClick={() => runAction('pausar')}
                className="inline-flex items-center gap-1.5 rounded-full border border-sky-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 shadow-sm transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-45"
              >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden>
                  <rect x="6" y="4" width="4" height="16" rx="1" />
                  <rect x="14" y="4" width="4" height="16" rx="1" />
                </svg>
                Pausar
              </button>
            )}

            <button
              type="button"
              disabled={disabled || busy || idle}
              onClick={() => runAction('finalizar')}
              className="inline-flex items-center gap-1.5 rounded-full border border-rose-300 bg-white px-4 py-2 text-sm font-semibold text-rose-600 shadow-sm transition hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-45"
            >
              <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden>
                <rect x="6" y="6" width="12" height="12" rx="2" />
              </svg>
              Parar
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
