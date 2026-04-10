import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { postTimerAction } from '../lib/api';

function parseServerDateTime(s) {
  if (!s || typeof s !== 'string') return null;
  const d = new Date(s.replace(' ', 'T'));
  return Number.isNaN(d.getTime()) ? null : d;
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
 * Cronômetro de horas técnicas (espelha o painel clássico): contagem em tempo real enquanto roda;
 * ao finalizar, grava Ticketshoras e consome contrato no servidor.
 */
export default function HorasTecnicasTimerPanel({ ticketId, horasTecnicas, disabled, onSnapshot, onFeedback }) {
  const [tick, setTick] = useState(0);
  const [busy, setBusy] = useState(false);
  const offsetRef = useRef(0);

  const snap = horasTecnicas || {};
  const canUse = Boolean(snap.canUseTimer);
  const disponivel = snap.timerDisponivel !== false;
  const sessao = snap.sessao || null;
  const serverUnix = typeof snap.serverUnix === 'number' ? snap.serverUnix : null;

  useEffect(() => {
    if (serverUnix != null) {
      offsetRef.current = serverUnix * 1000 - Date.now();
    }
  }, [serverUnix, sessao?.id, sessao?.horaInicio, sessao?.horaPausa, sessao?.pausado]);

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
  }, [sessao, tick, nowMs]);

  useEffect(() => {
    if (!sessao || sessao.pausado) return undefined;
    const id = setInterval(() => setTick((t) => t + 1), 1000);
    return () => clearInterval(id);
  }, [sessao?.id, sessao?.pausado]);

  async function runAction(action) {
    if (!ticketId) return;
    if (action === 'finalizar') {
      const ok = window.confirm('Finalizar o timer e registrar as horas no ticket e no contrato do cliente?');
      if (!ok) return;
    }
    setBusy(true);
    const res = await postTimerAction(ticketId, action);
    setBusy(false);
    if (res.ok && res.horasTecnicas && onSnapshot) {
      onSnapshot(res.horasTecnicas);
    }
    if (onFeedback) {
      if (res.ok) onFeedback(res.message || null, null);
      else onFeedback(null, res.message || res.error || 'Não foi possível atualizar o timer.');
    }
    return res;
  }

  if (!canUse) {
    return null;
  }

  if (!disponivel) {
    return (
      <div className="rounded-lg border border-amber-200 bg-amber-50/80 p-4 text-sm text-amber-900">
        <h2 className="text-sm font-bold">Horas técnicas</h2>
        <p className="mt-1 text-xs text-amber-800">
          Timer indisponível (tabela ou colunas). Use o formulário clássico ou execute o script de verificação do
          atendimento_timer.
        </p>
      </div>
    );
  }

  const registrados = snap.minutosRegistrados ?? 0;

  return (
    <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
      <h2 className="text-sm font-bold text-slate-900">Horas técnicas e contrato</h2>
      <p className="mt-1 text-xs text-slate-500">
        Tempo já lançado neste ticket (após finalizar o cronômetro): <span className="font-semibold text-slate-700">{minutosLabel(registrados)}</span>. Ao
        finalizar, o sistema grava em Horas cadastradas e desconta do contrato do cliente, como no painel clássico.
      </p>

      {!sessao ? (
        <div className="mt-3 flex flex-wrap items-center gap-2">
          <button
            type="button"
            disabled={disabled || busy}
            onClick={() => runAction('iniciar')}
            className="rounded-lg bg-cyan-700 px-3 py-1.5 text-sm font-semibold text-white disabled:opacity-50"
          >
            {busy ? '…' : 'Iniciar cronômetro'}
          </button>
        </div>
      ) : (
        <div className="mt-3 space-y-3">
          <div
            className={`rounded-lg px-3 py-2 font-mono text-2xl font-semibold tracking-tight ${
              sessao.pausado ? 'bg-amber-100 text-amber-950' : 'bg-cyan-600 text-white'
            }`}
          >
            {formatHms(elapsedSeconds)}
            {sessao.pausado ? <span className="ml-2 text-sm font-sans font-normal">(pausado)</span> : null}
          </div>
          <div className="flex flex-wrap gap-2">
            {!sessao.pausado ? (
              <button
                type="button"
                disabled={disabled || busy}
                onClick={() => runAction('pausar')}
                className="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-semibold text-slate-800 disabled:opacity-50"
              >
                Pausar
              </button>
            ) : (
              <button
                type="button"
                disabled={disabled || busy}
                onClick={() => runAction('retomar')}
                className="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-semibold text-slate-800 disabled:opacity-50"
              >
                Retomar
              </button>
            )}
            <button
              type="button"
              disabled={disabled || busy}
              onClick={() => runAction('finalizar')}
              className="rounded-lg bg-[var(--pgm-primary)] px-3 py-1.5 text-sm font-semibold text-white hover:bg-[var(--pgm-erp-teal-active)] disabled:opacity-50"
            >
              Finalizar e registrar
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
