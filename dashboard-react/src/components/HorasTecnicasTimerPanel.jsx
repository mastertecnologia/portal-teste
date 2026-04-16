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
      <div className="rounded-xl border border-[var(--pgm-badge-amber-ring,rgba(210,153,34,0.30))] bg-[var(--pgm-badge-amber-bg,rgba(210,153,34,0.14))] p-4 text-sm text-[var(--pgm-badge-amber-text,#f0c060)]">
        <h2 className="text-sm font-bold">Horas técnicas</h2>
        <p className="mt-1 text-xs">
          Timer indisponível (tabela ou colunas). Use o formulário clássico ou execute o script de verificação do
          atendimento_timer.
        </p>
      </div>
    );
  }

  const registrados = snap.minutosRegistrados ?? 0;

  return (
    <div className="overflow-hidden rounded-xl border border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-gradient-to-b from-[var(--pgm-bg-surface,#1a1f28)] to-[color-mix(in_srgb,var(--pgm-bg-surface,#1a1f28)_97%,rgba(255,255,255,0.03))] shadow-[var(--pgm-shadow-md)]">
      <div className="border-b border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-[var(--pgm-bg-elevated,#222834)] px-4 py-3">
        <h2 className="text-[0.85rem] font-semibold text-[var(--pgm-text,#e8eaed)]">Horas técnicas e contrato</h2>
      </div>
      <div className="p-4">
        <p className="text-xs text-[var(--pgm-text-muted,#9aa0a8)]">
          Tempo já lançado neste ticket: <span className="font-semibold text-[var(--pgm-text,#e8eaed)]">{minutosLabel(registrados)}</span>. Ao
          finalizar, o sistema grava em Horas cadastradas e desconta do contrato do cliente.
        </p>

        {!sessao ? (
          <div className="mt-3 text-center">
            <div className="mb-3 rounded-lg border border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-[var(--pgm-bg-base,#0c0f14)] px-4 py-3 font-mono text-[2rem] font-bold tracking-[0.1em] text-[var(--pgm-text-muted,#9aa0a8)] transition-all duration-300">
              00:00:00
            </div>
            <button
              type="button"
              disabled={disabled || busy}
              onClick={() => runAction('iniciar')}
              className="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-b from-[var(--pgm-primary,#1d9e75)] to-[#168a64] px-4 py-1.5 text-sm font-semibold text-white shadow-[var(--pgm-shadow-sm),inset_0_1px_0_rgba(255,255,255,0.12)] transition hover:-translate-y-px hover:shadow-[var(--pgm-shadow-md),0_0_16px_rgba(29,158,117,0.25)] disabled:opacity-50"
            >
              <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z" /></svg>
              {busy ? '…' : 'Iniciar'}
            </button>
          </div>
        ) : (
          <div className="mt-3 text-center">
            <div
              className={`relative mb-3 rounded-lg border px-4 py-3 font-mono text-[2rem] font-bold tracking-[0.1em] transition-all duration-300 ${
                sessao.pausado
                  ? 'border-[var(--pgm-badge-amber-ring)] bg-[var(--pgm-badge-amber-bg)] text-[var(--pgm-badge-amber-text)]'
                  : 'border-[rgba(29,158,117,0.25)] bg-[var(--pgm-bg-base,#0c0f14)] text-[var(--pgm-badge-teal-text,#5cdbc0)] shadow-[0_0_20px_rgba(29,158,117,0.08)]'
              }`}
            >
              {!sessao.pausado && (
                <span className="absolute right-3 top-3 flex h-2.5 w-2.5">
                  <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-[var(--pgm-primary,#1d9e75)] opacity-50" />
                  <span className="relative inline-flex h-2.5 w-2.5 rounded-full bg-[var(--pgm-primary,#1d9e75)]" />
                </span>
              )}
              {formatHms(elapsedSeconds)}
              {sessao.pausado ? <span className="ml-2 font-sans text-sm font-normal opacity-80">(pausado)</span> : null}
            </div>
            <div className="flex flex-wrap justify-center gap-2">
              {!sessao.pausado ? (
                <button
                  type="button"
                  disabled={disabled || busy}
                  onClick={() => runAction('pausar')}
                  className="inline-flex items-center gap-1.5 rounded-full border border-[var(--pgm-border,#3d4554)] bg-transparent px-4 py-1.5 text-sm font-medium text-[var(--pgm-text,#e8eaed)] transition hover:bg-[var(--pgm-bg-overlay,#2a3140)] hover:border-[var(--pgm-border-strong,#4f5869)] disabled:opacity-50"
                >
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="4" width="4" height="16" rx="1" /><rect x="14" y="4" width="4" height="16" rx="1" /></svg>
                  Pausar
                </button>
              ) : (
                <button
                  type="button"
                  disabled={disabled || busy}
                  onClick={() => runAction('retomar')}
                  className="inline-flex items-center gap-1.5 rounded-full border border-[var(--pgm-border,#3d4554)] bg-transparent px-4 py-1.5 text-sm font-medium text-[var(--pgm-text,#e8eaed)] transition hover:bg-[var(--pgm-bg-overlay,#2a3140)] hover:border-[var(--pgm-border-strong,#4f5869)] disabled:opacity-50"
                >
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z" /></svg>
                  Retomar
                </button>
              )}
              <button
                type="button"
                disabled={disabled || busy}
                onClick={() => runAction('finalizar')}
                className="inline-flex items-center gap-1.5 rounded-full border border-[var(--pgm-badge-red-ring,rgba(248,81,73,0.25))] bg-transparent px-4 py-1.5 text-sm font-medium text-[var(--pgm-badge-red-text,#ff9492)] transition hover:bg-[var(--pgm-badge-red-bg)] hover:border-[rgba(248,81,73,0.45)] hover:shadow-[0_0_12px_rgba(248,81,73,0.12)] disabled:opacity-50"
              >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="6" width="12" height="12" rx="2" /></svg>
                Parar
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
