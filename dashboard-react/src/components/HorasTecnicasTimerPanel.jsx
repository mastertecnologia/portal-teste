import { useCallback, useEffect, useRef, useState } from 'react';
import { postTimerAction } from '../lib/api';
import { createPrecisionStopwatch, formatElapsedHms } from '../lib/precisionStopwatch';

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
 * Painel: API iniciar / pausar / retomar / finalizar; display via precisionStopwatch (Date.now, sem drift).
 * Layout alinhado ao Service Desk (cartão claro, três ações com ícones).
 */
export default function HorasTecnicasTimerPanel({ ticketId, horasTecnicas, disabled, onSnapshot, onFeedback }) {
  const [optimistic, setOptimistic] = useState(null);
  const [busy, setBusy] = useState(false);
  const [, setRender] = useState(0);
  const rollbackRef = useRef(null);
  const offsetRef = useRef(0);
  const swRef = useRef(null);

  if (swRef.current == null) {
    swRef.current = createPrecisionStopwatch({
      nowMs: () => Date.now() + offsetRef.current,
    });
  }

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

  const syncStopwatchToSessao = useCallback(() => {
    const sw = swRef.current;
    if (!sw) return;

    if (!sessao?.horaInicio) {
      sw.syncIdle();
      return;
    }
    const start = parseSqlLocalDateTime(sessao.horaInicio);
    if (!start) {
      sw.syncIdle();
      return;
    }
    if (sessaoEstaPausada(sessao)) {
      const hp = sessao.horaPausa ? parseSqlLocalDateTime(sessao.horaPausa) : null;
      if (hp) {
        sw.syncPaused(Math.max(0, hp.getTime() - start.getTime()));
      } else {
        sw.syncPaused(0);
      }
      return;
    }
    sw.syncRunningFromAnchor(start.getTime());
  }, [sessao]);

  useEffect(() => {
    const sw = swRef.current;
    sw.setOnRender(() => setRender((n) => (n + 1) % 1_000_000));
    return () => {
      sw.setOnRender(null);
      sw.dispose();
    };
  }, []);

  useEffect(() => {
    syncStopwatchToSessao();
    setRender((n) => (n + 1) % 1_000_000);
  }, [syncStopwatchToSessao]);

  const displayHms = formatElapsedHms(swRef.current.getElapsedMs());

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
            {displayHms}
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
