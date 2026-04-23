import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { postTimerAction } from '../lib/api';

/** Interpreta Y-m-d H:i:s como horário local (mesma convenção que localSqlDateTimeFromMs). */
function parseSqlLocalDateTime(s) {
  if (!s || typeof s !== 'string') return null;
  const m = /^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2}):(\d{2})/.exec(s.trim());
  if (!m) return null;
  const d = new Date(+m[1], +m[2] - 1, +m[3], +m[4], +m[5], +m[6], 0);
  return Number.isNaN(d.getTime()) ? null : d;
}

function localSqlDateTimeFromMs(ms) {
  const d = new Date(ms);
  const p = (n) => (n < 10 ? `0${n}` : String(n));
  return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())} ${p(d.getHours())}:${p(d.getMinutes())}:${p(d.getSeconds())}`;
}

/** HH:MM:SS.cc (centésimos), alinhado ao cronómetro de referência. */
function formatTimeMs(ms) {
  const safe = Math.max(0, Math.floor(ms));
  const totalSeconds = Math.floor(safe / 1000);
  const centiseconds = Math.floor((safe % 1000) / 10);
  const seconds = totalSeconds % 60;
  const minutes = Math.floor(totalSeconds / 60) % 60;
  const hours = Math.floor(totalSeconds / 3600);
  const pad = (n) => String(n).padStart(2, '0');
  return `${pad(hours)}:${pad(minutes)}:${pad(seconds)}.${pad(centiseconds)}`;
}

function minutosLabel(totalMin) {
  const m = Math.max(0, Math.floor(Number(totalMin) || 0));
  const h = Math.floor(m / 60);
  const r = m % 60;
  if (h <= 0) return `${r} min`;
  if (r === 0) return `${h} h`;
  return `${h} h ${r} min`;
}

/** Pausa efetiva: flag do servidor ou marca de hora de pausa (evita JSON inconsistente). */
function sessaoEstaPausada(sessao) {
  if (!sessao) return false;
  if (sessao.pausado === true) return true;
  const hp = sessao.horaPausa;
  return hp != null && String(hp).trim() !== '';
}

/**
 * Cronômetro de horas técnicas: display HH:MM:SS.cc (rAF + timestamps), API iniciar/pausar/retomar/finalizar.
 */
export default function HorasTecnicasTimerPanel({ ticketId, horasTecnicas, disabled, onSnapshot, onFeedback }) {
  const [optimistic, setOptimistic] = useState(null);
  const [busy, setBusy] = useState(false);
  const [rafTick, setRafTick] = useState(0);
  const offsetRef = useRef(0);
  const rollbackRef = useRef(null);
  /** Último elapsed “bom” em ms; evita contar com relógio quando pausado sem horaPausa. */
  const freezeElapsedRef = useRef(0);

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

  const elapsedMs = useMemo(() => {
    if (!sessao?.horaInicio) {
      freezeElapsedRef.current = 0;
      return 0;
    }
    const start = parseSqlLocalDateTime(sessao.horaInicio);
    if (!start) {
      return 0;
    }
    if (sessaoEstaPausada(sessao)) {
      if (sessao.horaPausa) {
        const pause = parseSqlLocalDateTime(sessao.horaPausa);
        if (pause) {
          const frozen = Math.max(0, pause.getTime() - start.getTime());
          freezeElapsedRef.current = frozen;
          return frozen;
        }
      }
      return freezeElapsedRef.current;
    }
    const running = Math.max(0, nowMs() - start.getTime());
    freezeElapsedRef.current = running;
    return running;
  }, [sessao, rafTick, nowMs]);

  useEffect(() => {
    if (!sessao || sessaoEstaPausada(sessao)) return undefined;
    let id = 0;
    const loop = () => {
      setRafTick((t) => (t + 1) % 1_000_000);
      id = requestAnimationFrame(loop);
    };
    id = requestAnimationFrame(loop);
    return () => cancelAnimationFrame(id);
  }, [sessao?.id, sessao?.pausado, sessao?.horaPausa]);

  async function runAction(action) {
    if (!ticketId) return;
    if (action === 'finalizar') {
      const ok = window.confirm('Finalizar o timer e registrar as horas no ticket e no contrato do cliente?');
      if (!ok) return;
    }

    rollbackRef.current = optimistic;

    if (action === 'iniciar') {
      const t0 = Date.now() + offsetRef.current;
      setOptimistic({
        id: 'local',
        horaInicio: localSqlDateTimeFromMs(t0),
        horaPausa: null,
        pausado: false,
      });
    } else if (action === 'pausar' && sessao?.horaInicio) {
      const tPause = Date.now() + offsetRef.current;
      setOptimistic({
        ...sessao,
        pausado: true,
        horaPausa: localSqlDateTimeFromMs(tPause),
      });
    } else if (action === 'retomar' && sessao?.horaInicio && sessao.horaPausa) {
      const hi = parseSqlLocalDateTime(sessao.horaInicio);
      const hp = parseSqlLocalDateTime(sessao.horaPausa);
      if (hi && hp) {
        const elapsed = hp.getTime() - hi.getTime();
        const tResume = Date.now() + offsetRef.current;
        setOptimistic({
          ...sessao,
          horaInicio: localSqlDateTimeFromMs(tResume - elapsed),
          pausado: false,
          horaPausa: null,
        });
      } else {
        setOptimistic({
          ...sessao,
          pausado: false,
          horaPausa: null,
        });
      }
    } else if (action === 'finalizar') {
      rollbackRef.current = optimistic ?? serverSessao;
      setOptimistic(null);
    }

    setBusy(true);
    const res = await postTimerAction(ticketId, action);
    setBusy(false);

    if (res.ok) {
      if (res.horasTecnicas && onSnapshot) {
        onSnapshot(res.horasTecnicas);
      }
      setOptimistic(null);
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
  const paused = sessaoEstaPausada(sessao);
  const idle = !sessao;

  const btnBase =
    'rounded-md px-5 py-2.5 text-base font-medium transition-opacity disabled:cursor-not-allowed disabled:opacity-45';

  return (
    <div className="overflow-hidden rounded-xl border border-neutral-700 bg-[#111] text-white shadow-lg">
      <div className="border-b border-neutral-800 px-4 py-3">
        <h2 className="text-[0.9rem] font-bold tracking-tight text-white">Horas técnicas</h2>
      </div>
      <div className="p-4">
        <p className="text-xs text-slate-300">
          Tempo já lançado neste ticket:{' '}
          <span className="font-semibold text-white">{minutosLabel(registrados)}</span>. Ao finalizar, o sistema grava em Horas
          cadastradas e desconta do contrato do cliente.
        </p>

        <div className="mt-5 flex flex-col items-center">
          <div
            className={`mb-5 font-mono text-5xl font-bold tracking-[0.08em] tabular-nums ${
              paused ? 'text-amber-300' : 'text-white'
            }`}
          >
            {formatTimeMs(elapsedMs)}
            {paused ? <span className="ml-3 font-sans text-sm font-medium text-amber-300/90">(pausado)</span> : null}
          </div>

          <div className="flex flex-wrap justify-center gap-2">
            <button
              type="button"
              disabled={disabled || busy || !idle}
              onClick={() => runAction('iniciar')}
              className={`${btnBase} bg-[#28a745] text-white hover:opacity-95`}
            >
              {busy && idle ? '…' : 'Iniciar'}
            </button>

            {paused ? (
              <button
                type="button"
                disabled={disabled || busy}
                onClick={() => runAction('retomar')}
                className={`${btnBase} bg-[#ffc107] text-black hover:opacity-95`}
              >
                Retomar
              </button>
            ) : (
              <button
                type="button"
                disabled={disabled || busy || idle}
                onClick={() => runAction('pausar')}
                className={`${btnBase} bg-[#ffc107] text-black hover:opacity-95`}
              >
                Pausar
              </button>
            )}

            <button
              type="button"
              disabled={disabled || busy || idle}
              onClick={() => runAction('finalizar')}
              className={`${btnBase} bg-[#dc3545] text-white hover:opacity-95`}
            >
              Parar
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
