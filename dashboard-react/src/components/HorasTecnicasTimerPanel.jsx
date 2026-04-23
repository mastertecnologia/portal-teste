import { useCallback, useEffect, useRef, useState } from 'react';
import { postTimerAction } from '../lib/api';
import { createPrecisionStopwatch, formatElapsedHms } from '../lib/precisionStopwatch';
import TimerWidget from './TimerWidget.jsx';
import './HorasTecnicasTimerPanel.css';

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
 * Layout “Cronômetro Real-Time PGM” (HTML/CSS de referência).
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

  function handlePrimaryClick() {
    if (sessaoEstaPausada(sessao)) {
      runAction('retomar');
    } else {
      runAction('iniciar');
    }
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
  const running = Boolean(sessao) && !paused;
  const statusText = idle ? 'Aguardando...' : paused ? 'Pausado' : 'Em execução';

  const timerCardClass = `timer-card${running ? ' running' : ''}`;

  return (
    <div>
      <div className="pgm-crono-realtime">
        <div className={timerCardClass} id="timerCard">
          <div className="header-section">
            <div>
              <span className="title-label">Controle de Horas</span>
              <div id="statusLabel" style={{ fontSize: '12px', color: '#64748b', marginTop: '2px' }}>
                <span className="status-dot" />
                <span id="statusText">{statusText}</span>
              </div>
            </div>
          </div>

          <div className="display-container">
            <div className="timer-display" id="display">
              {displayHms}
            </div>
          </div>

          <div className="controls">
            <button
              id="startBtn"
              type="button"
              className="btn-start"
              disabled={disabled || busy || running}
              onClick={handlePrimaryClick}
            >
              {busy && (idle || paused) ? '…' : 'Iniciar'}
            </button>
            <button
              id="pauseBtn"
              type="button"
              className="btn-pause"
              disabled={disabled || busy || idle}
              onClick={() => runAction('pausar')}
            >
              Pausar
            </button>
            <button
              id="stopBtn"
              type="button"
              className="btn-stop"
              disabled={disabled || busy || idle}
              onClick={() => runAction('finalizar')}
            >
              Parar
            </button>
          </div>
        </div>
      </div>

      <p className="pgm-crono-realtime-footer">
        Tempo já lançado neste ticket: <strong>{minutosLabel(registrados)}</strong>. Ao finalizar, o sistema grava em Horas
        cadastradas e desconta do contrato do cliente.
      </p>

      <TimerWidget ticketId={ticketId} displayHms={displayHms} />
    </div>
  );
}
