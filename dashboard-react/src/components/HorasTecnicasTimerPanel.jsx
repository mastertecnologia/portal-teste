import { useEffect, useRef, useState } from 'react';
import { postTimerAction } from '../lib/api';
import AuditModal from './AuditModal.jsx';
import './HorasTecnicasTimerPanel.css';

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

function parseHmsToSeconds(v) {
  const t = String(v || '').trim();
  const m = /^(\d{1,4}):([0-5]\d):([0-5]\d)$/.exec(t);
  if (!m) return null;
  return (Number(m[1]) * 3600) + (Number(m[2]) * 60) + Number(m[3]);
}

function formatSecondsHms(total) {
  const s = Math.max(0, Math.floor(Number(total) || 0));
  const h = Math.floor(s / 3600);
  const m = Math.floor((s % 3600) / 60);
  const r = s % 60;
  return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(r).padStart(2, '0')}`;
}

/** Pausa efetiva: flag do servidor ou marca de hora de pausa (evita JSON inconsistente). */
function sessaoEstaPausada(sessao) {
  if (!sessao) return false;
  if (String(sessao.status || '').toLowerCase() === 'paused') return true;
  if (sessao.pausado === true) return true;
  const hp = sessao.horaPausa || sessao.pausedAt;
  return hp != null && String(hp).trim() !== '';
}

function normalizeSessao(raw) {
  if (!raw || typeof raw !== 'object') return null;
  const horaInicio = raw.startedAt || raw.horaInicio || null;
  if (!horaInicio) return null;
  const horaPausa = raw.pausedAt || raw.horaPausa || null;
  const status = String(raw.status || '').toLowerCase();
  const pausado = status === 'paused' || Boolean(raw.pausado) || Boolean(horaPausa);
  return {
    ...raw,
    horaInicio,
    horaPausa,
    startedAt: horaInicio,
    pausedAt: horaPausa,
    status: status || (pausado ? 'paused' : 'running'),
    pausado,
  };
}

export default function HorasTecnicasTimerPanel({
  ticketId,
  horasTecnicas,
  disabled,
  onSnapshot,
  onFeedback,
  entryActionsContent = null,
}) {
  const [optimistic, setOptimistic] = useState(null);
  const [busy, setBusy] = useState(false);
  const [auditOpen, setAuditOpen] = useState(false);
  const [editOpen, setEditOpen] = useState(false);
  const [editDuration, setEditDuration] = useState('00:00:00');
  const [editErr, setEditErr] = useState('');
  const [, setRender] = useState(0);
  const rollbackRef = useRef(null);
  const offsetRef = useRef(0);
  const stickySessaoRef = useRef(null);
  const finalizandoRef = useRef(false);

  const snap = horasTecnicas || {};
  const canUse = Boolean(snap.canUseTimer);
  const disponivel = snap.timerDisponivel !== false;
  const serverSessao = normalizeSessao(snap.sessao);
  const serverUnix = typeof snap.serverUnix === 'number' ? snap.serverUnix : null;

  if (serverSessao?.horaInicio) {
    stickySessaoRef.current = serverSessao;
  } else if (!finalizandoRef.current && !optimistic && stickySessaoRef.current?.horaInicio) {
    // Mantém sessão ativa localmente quando um snapshot tardio chega sem `sessao`.
  } else if (!optimistic) {
    stickySessaoRef.current = null;
  }

  const sessao = normalizeSessao(optimistic) ?? serverSessao ?? stickySessaoRef.current;

  useEffect(() => {
    if (!optimistic?.horaInicio || !serverSessao?.horaInicio) return;
    const localStart = parseSqlLocalDateTime(optimistic.horaInicio);
    const srvStart = parseSqlLocalDateTime(serverSessao.horaInicio);
    if (!localStart || !srvStart) {
      setOptimistic(null);
      return;
    }
    const diffMs = Math.abs(srvStart.getTime() - localStart.getTime());
    if (diffMs < 3000) {
      setOptimistic(null);
    }
  }, [optimistic, serverSessao]);

  useEffect(() => {
    if (serverUnix != null) {
      offsetRef.current = serverUnix * 1000 - Date.now();
    }
  }, [serverUnix, serverSessao?.id, serverSessao?.horaInicio, serverSessao?.horaPausa, serverSessao?.pausado]);
  const nowMs = Date.now() + offsetRef.current;
  const accumulatedSeconds = Math.max(
    0,
    Number(snap.accumulatedSeconds ?? ((Number(snap.minutosRegistrados) || 0) * 60)) || 0
  );
  const serverStatus = String(snap.status || '').toLowerCase();
  const paused = serverStatus === 'paused' || sessaoEstaPausada(sessao);
  const running = serverStatus === 'running' || (Boolean(sessao) && !sessaoEstaPausada(sessao));
  const finished = serverStatus === 'finished';
  const idle = !running && !paused && !finished;
  const startDt = sessao?.horaInicio ? parseSqlLocalDateTime(sessao.horaInicio) : null;
  const runningSessionSeconds = running && startDt
    ? Math.max(0, Math.floor((nowMs - startDt.getTime()) / 1000))
    : 0;
  const displaySeconds = running ? (accumulatedSeconds + runningSessionSeconds) : accumulatedSeconds;
  const displayHms = formatSecondsHms(displaySeconds);

  useEffect(() => {
    if (!running) return undefined;
    const t = window.setInterval(() => setRender((n) => (n + 1) % 1_000_000), 500);
    return () => window.clearInterval(t);
  }, [running]);

  async function runAction(action, options = {}) {
    const { skipBusy = false, payload = null } = options;
    if (!ticketId) return;
    rollbackRef.current = optimistic;
    let optimisticIniciarSnapshot = null;
    if (action === 'iniciar') {
      finalizandoRef.current = false;
      const localStartedAtMs = Date.now() + offsetRef.current;
      optimisticIniciarSnapshot = normalizeSessao({
        id: 'local',
        status: 'running',
        startedAt: localSqlDateTimeFromMs(localStartedAtMs),
        pausedAt: null,
      });
      setOptimistic(optimisticIniciarSnapshot);
      stickySessaoRef.current = optimisticIniciarSnapshot;
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
      finalizandoRef.current = true;
      rollbackRef.current = optimistic ?? serverSessao;
      setOptimistic(null);
    }

    if (!skipBusy) setBusy(true);
    let extra = payload && typeof payload === 'object' ? { ...payload } : {};
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
      let normalizedSessao = normalizeSessao(res.horasTecnicas?.sessao);
      if (res.horasTecnicas && onSnapshot) {
        if (action === 'iniciar' && optimisticIniciarSnapshot?.horaInicio) {
          if (!normalizedSessao?.horaInicio) {
            normalizedSessao = optimisticIniciarSnapshot;
          } else {
            const localStart = parseSqlLocalDateTime(optimisticIniciarSnapshot.horaInicio);
            const serverStart = parseSqlLocalDateTime(normalizedSessao.horaInicio);
            if (localStart && serverStart) {
              const diffMs = Math.abs(serverStart.getTime() - localStart.getTime());
              if (diffMs >= 3000) {
                normalizedSessao = optimisticIniciarSnapshot;
              }
            }
          }
        }
        onSnapshot({
          ...res.horasTecnicas,
          sessao: normalizedSessao,
        });
        if (normalizedSessao?.horaInicio) {
          stickySessaoRef.current = normalizedSessao;
        } else if (action === 'finalizar') {
          stickySessaoRef.current = null;
          finalizandoRef.current = false;
        }
      }
      if (action === 'iniciar' && optimisticIniciarSnapshot?.horaInicio) {
        setOptimistic(normalizedSessao || optimisticIniciarSnapshot);
      } else {
        setOptimistic(null);
      }
      if (onFeedback) onFeedback(res.message || null, null);
    } else {
      if (action === 'finalizar') {
        finalizandoRef.current = false;
      }
      if (action === 'finalizar') {
        setOptimistic(rollbackRef.current);
      } else if (action === 'iniciar') {
        stickySessaoRef.current = null;
        setOptimistic(rollbackRef.current ?? null);
      } else {
        setOptimistic(null);
      }
      if (onFeedback) {
        onFeedback(null, res.message || res.error || 'Não foi possível atualizar o timer.');
      }
    }
    return res;
  }

  function handlePlayClick() {
    if (running) return;
    if (paused) {
      runAction('retomar');
      return;
    }
    runAction('iniciar');
  }

  function handlePauseClick() {
    if (!running) return;
    runAction('pausar');
  }

  function openEditModal() {
    if (paused) {
      setAuditOpen(true);
      return;
    }
    const baseSeconds = running ? runningSessionSeconds : Math.max(0, Number(snap?.ultimaSessao?.durationSeconds || 0));
    setEditDuration(formatSecondsHms(baseSeconds));
    setEditErr('');
    setEditOpen(true);
  }

  async function saveEditDuration() {
    const durationSeconds = parseHmsToSeconds(editDuration);
    if (durationSeconds == null) {
      setEditErr('Use o formato hh:mm:ss.');
      return;
    }
    if (!running) return;
    setEditErr('');
    const r = await runAction('editar_duracao_sessao', { payload: { durationSeconds } });
    if (r?.ok) {
      setEditOpen(false);
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
  const timerCardClass = `timer-card compact${running ? ' running' : ''}`;
  const auditHms = displayHms && displayHms.length === 8 ? displayHms : '00:00:00';

  return (
    <div>
      <div className="pgm-crono-realtime">
        <div className={timerCardClass} id="timerCard">
          <div className="display-container compact-row">
            <div className="timer-display" id="display">
              {displayHms}
            </div>
            <div className="controls controls-inline">
              <button
                type="button"
                className="btn-neutral-icon"
                disabled={disabled || busy || running}
                onClick={handlePlayClick}
                title={paused ? 'Retomar' : 'Iniciar'}
                aria-label={paused ? 'Retomar timer' : 'Iniciar timer'}
              >
                <i className="fa fa-play" aria-hidden="true" />
              </button>
              <button
                type="button"
                className="btn-neutral-icon"
                disabled={disabled || busy || !running}
                onClick={handlePauseClick}
                title="Pausar"
                aria-label="Pausar timer"
              >
                <i className="fa fa-pause" aria-hidden="true" />
              </button>
              <button
                type="button"
                className="btn-neutral-icon"
                disabled={disabled || busy}
                onClick={openEditModal}
                title="Editar duração da sessão atual"
                aria-label="Editar duração da sessão atual"
              >
                <i className="fa fa-pencil" aria-hidden="true" />
              </button>
            </div>
          </div>

          {entryActionsContent ? (
            <div className="timer-entry-actions-inline">
              {entryActionsContent}
            </div>
          ) : (
            <button
              type="button"
              className="btn-audit-adjust"
              disabled={disabled || busy}
              onClick={() => setAuditOpen(true)}
            >
              Ajuste de Auditoria
            </button>
          )}

        </div>
      </div>

      <p className="pgm-crono-realtime-footer">
        Tempo já lançado neste ticket: <strong>{minutosLabel(registrados)}</strong>.
      </p>

      {auditOpen && (
        <AuditModal
          ticketId={ticketId}
          currentTimeHms={auditHms}
          ultimaFinalizacao={snap.ultimaFinalizacao}
          sessaoAtiva={Boolean(sessao)}
          onClose={() => setAuditOpen(false)}
        />
      )}

      {editOpen && (
        <div className="htp-modal-backdrop" onClick={() => !busy && setEditOpen(false)}>
          <div className="htp-modal htp-modal-sm" onClick={(e) => e.stopPropagation()}>
            <div className="htp-modal-head">
              Editar entrada de tempo
              <button type="button" className="htp-close" onClick={() => !busy && setEditOpen(false)}>
                ×
              </button>
            </div>
            <div className="htp-form htp-form-inline">
              <label>
                Duração
                <input
                  type="text"
                  value={editDuration}
                  onChange={(e) => setEditDuration(e.target.value)}
                  placeholder="00:00:00"
                  disabled={busy}
                />
              </label>
              {editErr ? <p className="htp-error">{editErr}</p> : null}
              <div className="htp-actions">
                <button type="button" disabled={busy} onClick={() => setEditOpen(false)}>
                  Cancelar
                </button>
                <button type="button" disabled={busy} onClick={saveEditDuration}>
                  Salvar
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
