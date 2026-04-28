import { useCallback, useEffect, useRef, useState } from 'react';
import {
  deleteTimeEntry,
  fetchTimeEntries,
  postTimerAction,
  postTicketSignature,
  saveTicketDescricaoAtendimento,
  upsertTimeEntry,
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

function splitDateTimeLocal(isoLike) {
  if (!isoLike) return { date: '', time: '' };
  const d = new Date(isoLike);
  if (Number.isNaN(d.getTime())) return { date: '', time: '' };
  const local = new Date(d.getTime() - d.getTimezoneOffset() * 60000);
  const s = local.toISOString();
  return { date: s.slice(0, 10), time: s.slice(11, 19) };
}

function joinDateTimeLocal(date, time) {
  if (!date || !time) return '';
  const d = new Date(`${date}T${time}`);
  if (Number.isNaN(d.getTime())) return '';
  return d.toISOString();
}

function parseDurationToSeconds(value) {
  const raw = String(value || '').trim();
  const m = /^(\d{1,3}):([0-5]\d):([0-5]\d)$/.exec(raw);
  if (!m) return null;
  const hh = Number(m[1] || 0);
  const mm = Number(m[2] || 0);
  const ss = Number(m[3] || 0);
  return hh * 3600 + mm * 60 + ss;
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
  const [entriesOpen, setEntriesOpen] = useState(false);
  const [manualOpen, setManualOpen] = useState(false);
  const [entriesBusy, setEntriesBusy] = useState(false);
  const [entriesErr, setEntriesErr] = useState('');
  const [entries, setEntries] = useState([]);
  const [editingEntry, setEditingEntry] = useState(null);
  const [form, setForm] = useState({
    durationHms: '',
    startDate: '',
    startTime: '',
    endDate: '',
    endTime: '',
    technicianContactId: '',
    billable: true,
    descricao: '',
    taxa: '',
    auditReason: '',
    auditAuthKey: '',
  });
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

  const loadEntries = useCallback(async () => {
    if (!ticketId) return;
    setEntriesBusy(true);
    setEntriesErr('');
    const r = await fetchTimeEntries(ticketId);
    if (!r.ok) {
      setEntriesErr(r.error || 'Falha ao carregar entradas.');
      setEntries([]);
    } else {
      setEntries(Array.isArray(r.entries) ? r.entries : []);
    }
    setEntriesBusy(false);
  }, [ticketId]);

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

  function openEntriesModal() {
    setEntriesOpen(true);
    loadEntries();
  }

  function openManualModal(entry = null) {
    const start = splitDateTimeLocal(entry?.startWorkHour || new Date().toISOString());
    const end = splitDateTimeLocal(entry?.endWorkHour || new Date(Date.now() + 60000).toISOString());
    setEditingEntry(entry || null);
    setEntriesErr('');
    const startIso = joinDateTimeLocal(start.date, start.time || '00:00:00');
    const endIso = joinDateTimeLocal(end.date, end.time || '00:01:00');
    const seconds = Math.max(0, Math.floor((new Date(endIso).getTime() - new Date(startIso).getTime()) / 1000) || 0);
    setForm({
      durationHms: formatElapsedHms(seconds * 1000),
      startDate: start.date,
      startTime: start.time || '00:00:00',
      endDate: end.date,
      endTime: end.time || '00:01:00',
      technicianContactId: entry?.technicianContactId ? String(entry.technicianContactId) : '',
      billable: entry?.billable !== false,
      descricao: String(entry?.note || ''),
      taxa: String(entry?.rate || ''),
      auditReason: '',
      auditAuthKey: '',
    });
    setManualOpen(true);
  }

  async function submitManualForm(ev) {
    ev.preventDefault();
    if (!ticketId) return;
    const startIso = joinDateTimeLocal(form.startDate, form.startTime);
    const endIso = joinDateTimeLocal(form.endDate, form.endTime);
    if (!startIso || !endIso) {
      setEntriesErr('Preencha data e hora de início e término.');
      return;
    }
    if (editingEntry && (!form.auditReason.trim() || !form.auditAuthKey.trim())) {
      setEntriesErr('Editar horas exige motivo e senha de auditoria.');
      return;
    }
    setEntriesBusy(true);
    setEntriesErr('');
    const payload = {
      id: editingEntry?.id ? Number(editingEntry.id) : 0,
      TicketID: Number(ticketId),
      StartWorkHour: startIso,
      EndWorkHour: endIso,
      TechnicianContactID: Number(form.technicianContactId || 0),
      Billable: !!form.billable,
      Rate: form.taxa || '',
      Description: form.descricao || '',
      auditReason: form.auditReason || '',
      auditAuthKey: form.auditAuthKey || '',
    };
    const r = await upsertTimeEntry(ticketId, payload);
    if (!r.ok) {
      setEntriesErr(r.error || 'Falha ao salvar entrada.');
      setEntriesBusy(false);
      return;
    }
    await loadEntries();
    setEntriesBusy(false);
    setManualOpen(false);
    setEditingEntry(null);
  }

  async function handleDeleteEntry(entryId) {
    if (!ticketId || !entryId) return;
    if (!window.confirm('Excluir esta entrada de tempo?')) return;
    const reason = window.prompt('Motivo da alteração (auditoria):', '') || '';
    if (!reason.trim()) {
      setEntriesErr('Motivo obrigatório para excluir.');
      return;
    }
    const authKey = window.prompt('Senha de auditoria:', '') || '';
    if (!authKey.trim()) {
      setEntriesErr('Senha de auditoria obrigatória para excluir.');
      return;
    }
    setEntriesBusy(true);
    setEntriesErr('');
    const r = await deleteTimeEntry(ticketId, entryId, { reason: reason.trim(), authKey: authKey.trim() });
    if (!r.ok) {
      setEntriesErr(r.error || 'Falha ao excluir entrada.');
      setEntriesBusy(false);
      return;
    }
    await loadEntries();
    setEntriesBusy(false);
  }

  function recalcDurationFromRange(nextForm) {
    const startIso = joinDateTimeLocal(nextForm.startDate, nextForm.startTime);
    const endIso = joinDateTimeLocal(nextForm.endDate, nextForm.endTime);
    const sec = Math.max(0, Math.floor((new Date(endIso).getTime() - new Date(startIso).getTime()) / 1000) || 0);
    return { ...nextForm, durationHms: formatElapsedHms(sec * 1000) };
  }

  function handleDurationChange(rawValue) {
    setForm((prev) => {
      const next = { ...prev, durationHms: rawValue };
      const parsedSeconds = parseDurationToSeconds(rawValue);
      const startIso = joinDateTimeLocal(prev.startDate, prev.startTime);
      if (parsedSeconds === null || !startIso) {
        return next;
      }
      const endMs = new Date(startIso).getTime() + parsedSeconds * 1000;
      const endParts = splitDateTimeLocal(new Date(endMs).toISOString());
      next.endDate = endParts.date || prev.endDate;
      next.endTime = endParts.time || prev.endTime;
      return next;
    });
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

          <div className="timer-entry-links">
            <button type="button" className="timer-link-btn" disabled={disabled || busy} onClick={openManualModal}>
              Entrada Manual de Tempo
            </button>
            <button type="button" className="timer-link-btn" disabled={disabled || busy} onClick={openEntriesModal}>
              Ver todas as entradas
            </button>
          </div>
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

      {entriesOpen && (
        <div className="htp-modal-backdrop" onClick={() => !entriesBusy && setEntriesOpen(false)}>
          <div className="htp-modal" onClick={(e) => e.stopPropagation()}>
            <div className="htp-modal-head">
              <strong>Entradas de Tempo</strong>
              <button type="button" className="htp-close" onClick={() => setEntriesOpen(false)}>x</button>
            </div>
            {entriesErr ? <div className="htp-error">{entriesErr}</div> : null}
            <div className="htp-table-wrap">
              <table className="htp-table">
                <thead>
                  <tr>
                    <th>ID</th><th>Técnico</th><th>Duração</th><th>Faturável</th><th>Taxa</th><th>Observação</th><th>Ações</th>
                  </tr>
                </thead>
                <tbody>
                  {entriesBusy ? (
                    <tr><td colSpan={7}>Carregando...</td></tr>
                  ) : entries.length ? (
                    entries.map((row) => (
                      <tr key={row.id}>
                        <td>{row.id}</td>
                        <td>{row.technicianName || `ID ${row.technicianContactId || '-'}`}</td>
                        <td>{formatElapsedHms((Number(row.durationSeconds || 0)) * 1000)}</td>
                        <td>{row.billable ? 'Sim' : 'Não'}</td>
                        <td>{row.rate || '-'}</td>
                        <td>{row.note || '-'}</td>
                        <td>
                          <button type="button" className="htp-mini" onClick={() => openManualModal(row)}>Editar</button>
                          <button type="button" className="htp-mini htp-mini-danger" onClick={() => handleDeleteEntry(row.id)}>Excluir</button>
                        </td>
                      </tr>
                    ))
                  ) : (
                    <tr><td colSpan={7}>Nenhuma entrada encontrada.</td></tr>
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {manualOpen && (
        <div className="htp-modal-backdrop" onClick={() => !entriesBusy && setManualOpen(false)}>
          <div className="htp-modal htp-modal-sm" onClick={(e) => e.stopPropagation()}>
            <div className="htp-modal-head">
              <strong>{editingEntry ? 'Editar entrada de tempo' : 'Adicionar entrada de tempo'}</strong>
              <button type="button" className="htp-close" onClick={() => setManualOpen(false)}>x</button>
            </div>
            {entriesErr ? <div className="htp-error">{entriesErr}</div> : null}
            <form className="htp-form" onSubmit={submitManualForm}>
              <label>Duração
                <input
                  type="text"
                  value={form.durationHms}
                  onChange={(e) => handleDurationChange(e.target.value)}
                  placeholder="HH:MM:SS"
                />
              </label>
              <label>Descrição
                <textarea value={form.descricao} onChange={(e) => setForm((p) => ({ ...p, descricao: e.target.value }))} rows={3} />
              </label>
              <label className="htp-checkbox">
                <input type="checkbox" checked={form.billable} onChange={(e) => setForm((p) => ({ ...p, billable: e.target.checked }))} />
                Faturável
              </label>
              <label>Taxa
                <select value={form.taxa} onChange={(e) => setForm((p) => ({ ...p, taxa: e.target.value }))}>
                  <option value="">Nada selecionado</option>
                  <option value="padrao">Padrão</option>
                </select>
              </label>
              <div className="htp-grid2">
                <label>Data de início<input type="date" value={form.startDate} onChange={(e) => setForm((p) => recalcDurationFromRange({ ...p, startDate: e.target.value }))} required /></label>
                <label>Hora de início<input type="time" step="1" value={form.startTime} onChange={(e) => setForm((p) => recalcDurationFromRange({ ...p, startTime: e.target.value }))} required /></label>
              </div>
              <div className="htp-grid2">
                <label>Data de término<input type="date" value={form.endDate} onChange={(e) => setForm((p) => recalcDurationFromRange({ ...p, endDate: e.target.value }))} required /></label>
                <label>Hora de término<input type="time" step="1" value={form.endTime} onChange={(e) => setForm((p) => recalcDurationFromRange({ ...p, endTime: e.target.value }))} required /></label>
              </div>
              <label>Técnico (ID)
                <input type="number" min="1" value={form.technicianContactId} onChange={(e) => setForm((p) => ({ ...p, technicianContactId: e.target.value }))} />
              </label>
              {editingEntry ? (
                <div className="htp-grid2">
                  <label>Motivo da alteração
                    <input type="text" value={form.auditReason} onChange={(e) => setForm((p) => ({ ...p, auditReason: e.target.value }))} required />
                  </label>
                  <label>Senha de auditoria
                    <input type="password" value={form.auditAuthKey} onChange={(e) => setForm((p) => ({ ...p, auditAuthKey: e.target.value }))} required />
                  </label>
                </div>
              ) : null}
              <div className="htp-actions">
                <button type="button" onClick={() => setManualOpen(false)} disabled={entriesBusy}>Cancelar</button>
                <button type="submit" disabled={entriesBusy}>{entriesBusy ? 'Salvando...' : 'Salvar'}</button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
