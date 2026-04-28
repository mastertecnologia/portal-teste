import { useCallback, useEffect, useRef, useState } from 'react';
import {
  postTimerAction,
  postTicketSignature,
  saveTicketDescricaoAtendimento,
} from '../lib/api';
import { createPrecisionStopwatch, formatElapsedHms } from '../lib/precisionStopwatch';
import TimerWidget from './TimerWidget.jsx';
import AuditModal from './AuditModal.jsx';
import FinalizarTimerModal from './FinalizarTimerModal.jsx';
import './HorasTecnicasTimerPanel.css';

const TIMER_WIDGET_STORAGE_KEY = 'pgm_tickets_timer_widget_state_v1';

/** Interpreta Y-m-d H:i:s, Y-m-d H:i (e frações após s) como horário local (alinhado a localSqlDateTimeFromMs). */
function parseSqlLocalDateTime(s) {
  if (!s || typeof s !== 'string') return null;
  const t = s.trim();
  const m = /^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})(?::(\d{2}))?/.exec(t);
  if (!m) return null;
  const sec = m[6] != null && m[6] !== '' ? +m[6] : 0;
  const d = new Date(+m[1], +m[2] - 1, +m[3], +m[4], +m[5], sec, 0);
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
 * Layout alinhado ao mock: cabeçalho TICKET #, Iniciar / Finalizar (+ Pausar / Retomar quando aplicável), Ajuste de Auditoria.
 */
function TicketHeading({ ticketId }) {
  const s = String(ticketId ?? '');
  if (!s) return null;
  const head = s.length > 1 ? s.slice(0, -1) : '';
  const last = s.length > 1 ? s.slice(-1) : s;
  return (
    <div className="ticket-heading">
      <span className="ticket-highlight">
        TICKET #{head}
      </span>
      <span className="ticket-id-tail">{last}</span>
    </div>
  );
}

export default function HorasTecnicasTimerPanel({
  ticketId,
  horasTecnicas,
  disabled,
  onSnapshot,
  onFeedback,
  canEditDescricaoAtendimento = false,
  onRelatorioSaved,
}) {
  const safeStorage = typeof window !== 'undefined' ? window.localStorage : null;
  const [optimistic, setOptimistic] = useState(null);
  const [busy, setBusy] = useState(false);
  const [auditOpen, setAuditOpen] = useState(false);
  const [finalizeOpen, setFinalizeOpen] = useState(false);
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
    if (!safeStorage || !ticketId) return;
    try {
      if (sessao && sessao.horaInicio) {
        safeStorage.setItem(
          TIMER_WIDGET_STORAGE_KEY,
          JSON.stringify({
            ticketId: Number(ticketId),
            sessao: {
              id: sessao.id || null,
              horaInicio: sessao.horaInicio,
              horaPausa: sessao.horaPausa || null,
              pausado: Boolean(sessaoEstaPausada(sessao)),
            },
            updatedAt: Date.now(),
          })
        );
      } else {
        const raw = safeStorage.getItem(TIMER_WIDGET_STORAGE_KEY);
        if (raw) {
          const parsed = JSON.parse(raw);
          if (!parsed || Number(parsed.ticketId) === Number(ticketId)) {
            safeStorage.removeItem(TIMER_WIDGET_STORAGE_KEY);
          }
        }
      }
    } catch (_e) {
      // noop: storage indisponível ou quota excedida
    }
  }, [safeStorage, ticketId, sessao]);

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

  async function runAction(action, options = {}) {
    const { skipBusy = false } = options;
    if (!ticketId) return;
    rollbackRef.current = optimistic;
    let optimisticIniciarSnapshot = null;

    if (action === 'iniciar') {
      const t0 = Date.now() + offsetRef.current;
      optimisticIniciarSnapshot = {
        id: 'local',
        horaInicio: localSqlDateTimeFromMs(t0),
        horaPausa: null,
        pausado: false,
      };
      setOptimistic(optimisticIniciarSnapshot);
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

    if (!skipBusy) setBusy(true);
    let extra = {};
    if (action === 'iniciar' && typeof navigator !== 'undefined' && navigator.geolocation) {
      try {
        const pos = await new Promise((resolve, reject) => {
          navigator.geolocation.getCurrentPosition(resolve, reject, {
            timeout: 20000,
            maximumAge: 120000,
            enableHighAccuracy: true,
          });
        });
        extra = { lat: pos.coords.latitude, lng: pos.coords.longitude };
      } catch (geoErr) {
        if (!skipBusy) setBusy(false);
        setOptimistic(null);
        if (onFeedback) {
          onFeedback(
            null,
            'Não foi possível obter a localização. Permita o acesso à geolocalização para iniciar o timer no cliente.'
          );
        }
        return { ok: false, error: 'geo_denied', message: geoErr?.message };
      }
    }
    const res = await postTimerAction(ticketId, action, extra);
    if (!skipBusy) setBusy(false);

    if (res.ok) {
      if (res.horasTecnicas && onSnapshot) {
        let ht = res.horasTecnicas;
        if (action === 'iniciar' && optimisticIniciarSnapshot?.horaInicio && !ht.sessao?.horaInicio) {
          ht = { ...ht, sessao: optimisticIniciarSnapshot };
        }
        onSnapshot(ht);
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

  function openFinalizeModal() {
    if (!sessao || busy || disabled) return;
    setFinalizeOpen(true);
  }

  async function handleFinalizeSubmit(atividade, signatureDataUrl) {
    const t = (atividade || '').trim();
    if (canEditDescricaoAtendimento && t.length < 3) {
      return { ok: false, error: 'Descreva o que foi feito nestes minutos (mínimo 3 caracteres).' };
    }
    setBusy(true);
    try {
      if (canEditDescricaoAtendimento && t) {
        const sr = await saveTicketDescricaoAtendimento(ticketId, t);
        if (!sr.ok) {
          return { ok: false, error: sr.error || 'Não foi possível gravar o relatório do atendimento.' };
        }
        onRelatorioSaved?.(t);
      }
      const res = await runAction('finalizar', { skipBusy: true });
      if (res && res.ok) {
        if (signatureDataUrl && String(signatureDataUrl).length > 80) {
          const sigRes = await postTicketSignature(ticketId, signatureDataUrl);
          if (!sigRes.ok) {
            return { ok: false, error: sigRes.error || 'Timer finalizado, mas falha ao gravar assinatura.' };
          }
        }
        setFinalizeOpen(false);
        return { ok: true };
      }
      return {
        ok: false,
        error: (res && (res.message || res.error)) || 'Não foi possível finalizar o timer.',
      };
    } finally {
      setBusy(false);
    }
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

  const timerCardClass = `timer-card${running ? ' running' : ''}`;
  const auditHms = displayHms && displayHms.length === 8 ? displayHms : '00:00:00';

  return (
    <div>
      <div className="pgm-crono-realtime">
        <div className={timerCardClass} id="timerCard">
          <div className="header-section">
            <TicketHeading ticketId={ticketId} />
          </div>

          <div className="display-container">
            <div className="timer-display" id="display">
              {displayHms}
            </div>
          </div>

          <div className="controls controls-primary">
            {running ? (
              <>
                <button
                  id="pauseBtn"
                  type="button"
                  className="btn-pause"
                  disabled={disabled || busy}
                  onClick={() => runAction('pausar')}
                >
                  Pausar
                </button>
                <button
                  id="stopBtn"
                  type="button"
                  className="btn-stop"
                  disabled={disabled || busy}
                  onClick={openFinalizeModal}
                >
                  Finalizar
                </button>
              </>
            ) : paused ? (
              <>
                <button
                  id="startBtn"
                  type="button"
                  className="btn-start"
                  disabled={disabled || busy}
                  onClick={handlePrimaryClick}
                >
                  {busy ? '…' : 'Retomar'}
                </button>
                <button
                  id="stopBtn"
                  type="button"
                  className="btn-stop"
                  disabled={disabled || busy}
                  onClick={openFinalizeModal}
                >
                  Finalizar
                </button>
              </>
            ) : (
              <>
                <button
                  id="startBtn"
                  type="button"
                  className="btn-start"
                  disabled={disabled || busy}
                  onClick={handlePrimaryClick}
                >
                  {busy ? '…' : 'Iniciar'}
                </button>
                <button
                  id="stopBtn"
                  type="button"
                  className="btn-stop"
                  disabled={disabled || busy || idle}
                  onClick={openFinalizeModal}
                >
                  Finalizar
                </button>
              </>
            )}
          </div>

          <button
            type="button"
            className="btn-audit-adjust"
            disabled={disabled || busy}
            onClick={() => setAuditOpen(true)}
          >
            Ajuste de Auditoria
          </button>

        </div>
      </div>

      <p className="pgm-crono-realtime-footer">
        Tempo já lançado neste ticket: <strong>{minutosLabel(registrados)}</strong>. Ao finalizar, o sistema grava em Horas
        cadastradas e desconta do contrato do cliente.
      </p>

      <TimerWidget
        ticketId={ticketId}
        displayHms={displayHms}
        busy={busy}
        disabled={disabled}
        idle={idle}
        running={running}
        paused={paused}
        onPlay={handlePrimaryClick}
        onStop={openFinalizeModal}
      />

      {auditOpen && (
        <AuditModal
          ticketId={ticketId}
          currentTimeHms={auditHms}
          ultimaFinalizacao={snap.ultimaFinalizacao}
          sessaoAtiva={Boolean(sessao)}
          onClose={() => setAuditOpen(false)}
        />
      )}

      <FinalizarTimerModal
        open={finalizeOpen}
        displayHms={displayHms}
        busy={busy}
        canEditDescricaoAtendimento={canEditDescricaoAtendimento}
        onClose={() => !busy && setFinalizeOpen(false)}
        onSubmit={handleFinalizeSubmit}
      />
    </div>
  );
}
