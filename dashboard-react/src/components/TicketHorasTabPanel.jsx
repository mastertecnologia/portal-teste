import { useMemo, useState } from 'react';
import { createPortal } from 'react-dom';
import { deleteTimeEntry, fetchTimeEntries, getBoot, upsertTimeEntry } from '../lib/api';
import TicketTimeline from './TicketTimeline.jsx';

function parseEventDate(ev) {
  const raw = ev?.created;
  if (!raw || typeof raw !== 'string') return null;
  const normalized = raw.includes('T') ? raw : raw.replace(' ', 'T');
  const d = new Date(normalized);
  return Number.isNaN(d.getTime()) ? null : d;
}

/**
 * Chave YYYY-MM-DD (mesmo formato interno do filtro «Dia / data»).
 * Usa a data do lançamento (workDateLabel = dia em ticketshoras), não TicketEvents.created
 * (que pode ser outro dia após backfill ou fuso).
 */
function worklogDayKeyForFilter(ev) {
  const label = String(ev?.workDateLabel || '').trim();
  const br = /^(\d{1,2})\/(\d{1,2})\/(\d{4})$/.exec(label);
  if (br) {
    const day = String(br[1]).padStart(2, '0');
    const month = String(br[2]).padStart(2, '0');
    const year = br[3];
    return `${year}-${month}-${day}`;
  }
  const d = parseEventDate(ev);
  if (!d) return null;
  return d.toLocaleDateString('en-CA', { year: 'numeric', month: '2-digit', day: '2-digit' });
}

function worklogSeconds(ev) {
  if ((ev.type || '').toLowerCase() !== 'worklog') return 0;
  const sec =
    ev.secondsSpent != null && ev.secondsSpent !== ''
      ? ev.secondsSpent
      : ev.seconds_spent != null && ev.seconds_spent !== ''
        ? ev.seconds_spent
        : 0;
  return Math.max(0, Number(sec) || 0);
}

function formatMinutosHumanos(totalMin) {
  const m = Math.max(0, Math.floor(Number(totalMin) || 0));
  if (m <= 0) return '0 min';
  const h = Math.floor(m / 60);
  const r = m % 60;
  if (h <= 0) return `${r} min`;
  if (r === 0) return `${h} h`;
  return `${h} h ${r} min`;
}

function formatMinutosFromSeconds(sec) {
  return formatMinutosHumanos(Math.ceil(Math.max(0, Number(sec) || 0) / 60));
}

function formatDurationHms(totalSeconds) {
  const s = Math.max(0, Number(totalSeconds) || 0);
  const h = Math.floor(s / 3600);
  const m = Math.floor((s % 3600) / 60);
  const r = s % 60;
  return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(r).padStart(2, '0')}`;
}

function parseDurationHms(value) {
  const t = String(value || '').trim();
  const m = /^(\d{1,3}):([0-5]\d):([0-5]\d)$/.exec(t);
  if (!m) return null;
  const h = Number(m[1]);
  const min = Number(m[2]);
  const sec = Number(m[3]);
  return h * 3600 + min * 60 + sec;
}

function formatDateTimePtBr(value) {
  if (!value) return '—';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return '—';
  return d.toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: false,
  });
}

function toDateTimeLocalValue(value) {
  if (!value) return '';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return '';
  const p = (n) => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}T${p(d.getHours())}:${p(d.getMinutes())}:${p(d.getSeconds())}`;
}

function fromDateTimeLocalToIso(value) {
  if (!value) return null;
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return null;
  return d.toISOString();
}

function splitDateTimeLocal(value) {
  if (!value || !value.includes('T')) return { date: '', time: '' };
  const [date, timeRaw] = value.split('T');
  return { date: date || '', time: (timeRaw || '').slice(0, 8) };
}

function joinDateTimeLocal(date, time) {
  if (!date || !time) return '';
  return `${date}T${time}`;
}

function nowDateTimeLocalParts() {
  const d = new Date();
  const p = (n) => String(n).padStart(2, '0');
  return {
    date: `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}`,
    time: `${p(d.getHours())}:${p(d.getMinutes())}:${p(d.getSeconds())}`,
  };
}

/** Exibe ISO (YYYY-MM-DD) como dd/mm/aaaa (PT-BR). */
function isoDateToBr(iso) {
  if (!iso || typeof iso !== 'string' || !/^\d{4}-\d{2}-\d{2}$/.test(iso)) return '';
  const [y, m, d] = iso.split('-');
  return `${d}/${m}/${y}`;
}

/**
 * Interpreta dd/mm/aaaa (PT-BR). Retorno: { iso } válido, { empty: true } se vazio, ou { invalid: true }.
 */
function parseBrDateFilter(s) {
  const t = String(s ?? '').trim();
  if (!t) return { empty: true };
  const m = /^(\d{1,2})\/(\d{1,2})\/(\d{4})$/.exec(t);
  if (!m) return { invalid: true };
  const day = Number(m[1]);
  const month = Number(m[2]);
  const year = Number(m[3]);
  if (month < 1 || month > 12 || day < 1 || day > 31) return { invalid: true };
  const dt = new Date(year, month - 1, day);
  if (dt.getFullYear() !== year || dt.getMonth() !== month - 1 || dt.getDate() !== day) {
    return { invalid: true };
  }
  const iso = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
  return { iso };
}

/** Rótulo do técnico vindo do JSON (camelCase ou snake_case legado). */
function technicianDisplayLabel(en) {
  const raw =
    en?.technicianName ??
    en?.technician_name ??
    en?.technician ??
    en?.tecnico ??
    en?.userName ??
    en?.user_name ??
    '';
  return String(raw || '').trim();
}

export default function TicketHorasTabPanel({ ticket, timelineEvents, onlyEntryActions = false }) {
  const eventosHoras = useMemo(
    () => (timelineEvents || []).filter((ev) => (ev.type || '').toLowerCase() === 'worklog'),
    [timelineEvents]
  );

  const minutosOficiais = ticket?.horasTecnicas?.minutosRegistrados;
  const minutosFallback = useMemo(
    () => eventosHoras.reduce((s, ev) => s + Math.ceil(worklogSeconds(ev) / 60), 0),
    [eventosHoras]
  );

  const totalMinutosTicket =
    minutosOficiais != null && Number(minutosOficiais) > 0
      ? Number(minutosOficiais)
      : minutosFallback;

  const [filterDay, setFilterDay] = useState('');
  /** Texto do filtro de dia em PT-BR (dd/mm/aaaa); valor interno do filtro continua em `filterDay` (ISO). */
  const [dateFilterInput, setDateFilterInput] = useState('');
  const [filterTec, setFilterTec] = useState('');
  const [entriesOpen, setEntriesOpen] = useState(false);
  const [manualOpen, setManualOpen] = useState(false);
  const [entries, setEntries] = useState([]);
  const [entryTechnicians, setEntryTechnicians] = useState([]);
  const [entriesBusy, setEntriesBusy] = useState(false);
  const [entriesErr, setEntriesErr] = useState('');
  const [editingEntry, setEditingEntry] = useState(null);
  const [form, setForm] = useState({
    startDate: '',
    startTime: '',
    endDate: '',
    endTime: '',
    duration: '00:00:00',
    technicianContactId: '',
    billable: true,
    descricao: '',
    taxa: '',
    auditReason: '',
    auditAuthKey: '',
    showMore: true,
  });

  const technicianOptions = useMemo(() => {
    const names = new Set();
    for (const ev of eventosHoras) {
      const a = (ev.autor || '').trim();
      if (a) names.add(a);
    }
    return [...names].sort((a, b) => a.localeCompare(b, 'pt-BR'));
  }, [eventosHoras]);

  const filtered = useMemo(() => {
    return eventosHoras.filter((ev) => {
      if (filterTec && (ev.autor || '').trim() !== filterTec) return false;
      if (filterDay) {
        const key = worklogDayKeyForFilter(ev);
        if (key == null || key !== filterDay) return false;
      }
      return true;
    });
  }, [eventosHoras, filterDay, filterTec]);

  const segundosFiltrados = useMemo(() => filtered.reduce((s, ev) => s + worklogSeconds(ev), 0), [filtered]);

  const filtersActive = Boolean(filterDay || filterTec);

  async function reloadEntries() {
    if (!ticket?.id) return;
    setEntriesBusy(true);
    setEntriesErr('');
    const r = await fetchTimeEntries(ticket.id);
    setEntriesBusy(false);
    if (!r.ok) {
      setEntriesErr(r.error || 'Falha ao carregar entradas.');
      return r;
    }
    setEntries(r.entries || []);
    setEntryTechnicians(r.technicians || []);
    return r;
  }

  async function openEntriesModal() {
    setEntriesOpen(true);
    await reloadEntries();
  }

  async function openManualModal(entry = null) {
    let technicians = entryTechnicians;
    if (technicians.length === 0) {
      const loaded = await reloadEntries();
      if (loaded?.ok) {
        technicians = loaded.technicians || [];
      }
    }
    const startValue = entry ? toDateTimeLocalValue(entry.startWorkHour) : '';
    const endValue = entry ? toDateTimeLocalValue(entry.endWorkHour) : '';
    const s = splitDateTimeLocal(startValue);
    const e = splitDateTimeLocal(endValue);
    const nowParts = nowDateTimeLocalParts();
    const boot = getBoot();
    const fallbackTechId = Number(boot?.userId || 0);
    const entryTechId = Number(
      entry?.technicianId ??
      entry?.technicianContactId ??
      entry?.technician_contact_id ??
      0
    );
    const defaultTechId = entryTechId || fallbackTechId || Number(technicians?.[0]?.id || 0);
    setEditingEntry(entry);
    setForm({
      startDate: s.date || nowParts.date,
      startTime: s.time || nowParts.time,
      endDate: e.date || nowParts.date,
      endTime: e.time || nowParts.time,
      duration: '00:00:00',
      technicianContactId: String(defaultTechId || ''),
      billable: entry?.billable !== false,
      descricao: String(entry?.note || ''),
      taxa: String(entry?.rate || ''),
      auditReason: '',
      auditAuthKey: '',
      showMore: true,
    });
    setManualOpen(true);
  }

  async function submitManualForm(e) {
    e.preventDefault();
    const startIso = fromDateTimeLocalToIso(joinDateTimeLocal(form.startDate, form.startTime || '00:00:00'));
    const durationSeconds = parseDurationHms(form.duration);
    if (!startIso) {
      setEntriesErr('Preencha data/hora de início.');
      return;
    }
    if (durationSeconds == null) {
      setEntriesErr('Duração inválida. Use o formato hh:mm:ss.');
      return;
    }
    const endIso = new Date(new Date(startIso).getTime() + durationSeconds * 1000).toISOString();
    const payload = {
      id: editingEntry?.id || undefined,
      StartWorkHour: startIso,
      EndWorkHour: endIso,
      Billable: Boolean(form.billable),
      TechnicianContactID: Number(form.technicianContactId || 0),
      Rate: form.taxa || '',
      Description: form.descricao || '',
      auditReason: form.auditReason || '',
      auditAuthKey: form.auditAuthKey || '',
      TicketID: Number(ticket?.id || 0),
    };
    setEntriesBusy(true);
    setEntriesErr('');
    const r = await upsertTimeEntry(ticket.id, payload);
    setEntriesBusy(false);
    if (!r.ok) {
      setEntriesErr(r.error || 'Falha ao salvar entrada.');
      return;
    }
    setManualOpen(false);
    await reloadEntries();
  }

  async function handleDeleteEntry(entryId) {
    if (!entryId) return;
    const ok = typeof window !== 'undefined' && typeof window.confirm === 'function'
      ? window.confirm('Excluir esta entrada de tempo?')
      : true;
    if (!ok) return;
    const reason = typeof window !== 'undefined' ? window.prompt('Motivo da alteração (auditoria):', '') : '';
    if (!reason || !String(reason).trim()) {
      setEntriesErr('Motivo obrigatório para excluir horas.');
      return;
    }
    const authKey = typeof window !== 'undefined' ? window.prompt('Senha de auditoria:', '') : '';
    if (!authKey || !String(authKey).trim()) {
      setEntriesErr('Senha de auditoria obrigatória para excluir horas.');
      return;
    }
    setEntriesBusy(true);
    setEntriesErr('');
    const r = await deleteTimeEntry(ticket.id, entryId, { reason: String(reason).trim(), authKey: String(authKey).trim() });
    setEntriesBusy(false);
    if (!r.ok) {
      setEntriesErr(r.error || 'Falha ao excluir entrada.');
      return;
    }
    await reloadEntries();
  }

  const entriesModal = entriesOpen && typeof document !== 'undefined' ? createPortal(
    <div className="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40 px-3 py-6" onClick={() => setEntriesOpen(false)}>
      <div className="w-full max-w-6xl rounded-md border border-[#e5e7eb] bg-white p-5 text-[#1f2937] shadow-xl" onClick={(ev) => ev.stopPropagation()}>
        <div className="mb-3 flex items-center justify-between border-b border-[#eef2f6] pb-3">
          <h3 className="text-[1.02rem] font-semibold text-[#1f2937]">Entradas de Tempo</h3>
          <button type="button" className="rounded-md border border-[#e5e7eb] px-2 py-1 text-xs text-[#6b7280] hover:text-[#111827]" onClick={() => setEntriesOpen(false)}>Fechar</button>
        </div>
        {entriesErr ? <p className="mb-2 text-xs text-red-600">{entriesErr}</p> : null}
        <div className="max-h-[55vh] overflow-auto">
          <table className="w-full min-w-[56rem] text-left text-sm">
            <thead>
              <tr className="border-b border-[#edf1f5] text-[11px] uppercase tracking-[0.03em] text-[#6b7280]">
                <th className="w-[64px] py-2 text-center">ID</th>
                <th className="py-2">Técnico</th>
                <th className="py-2">Duração</th>
                <th className="w-[110px] py-2 text-center">Faturável</th>
                <th className="w-[120px] py-2 text-center">Taxa</th>
                <th className="py-2">Notas</th>
                <th className="w-[96px] py-2 text-right">Ações</th>
              </tr>
            </thead>
            <tbody>
              {entries.map((en) => (
                <tr key={en.id} className="border-b border-[#f1f5f9]">
                  <td className="py-2.5 text-center">{en.id}</td>
                  <td className="py-2.5">
                    <div className="flex items-center gap-2">
                      <span className="inline-flex h-6 w-6 items-center justify-center rounded-full border border-[#e5e7eb] bg-[#f8fafc] text-[10px] font-semibold text-[#6b7280]">
                        {String(technicianDisplayLabel(en) || 'T')
                          .split(' ')
                          .filter(Boolean)
                          .slice(0, 2)
                          .map((p) => p[0]?.toUpperCase() || '')
                          .join('') || 'T'}
                      </span>
                      <span>{technicianDisplayLabel(en) || `#${en.technicianId}`}</span>
                    </div>
                  </td>
                  <td className="py-2.5">
                    <div className="font-medium leading-tight">Total: {formatDurationHms(en.durationSeconds)}</div>
                    <div className="text-[10px] leading-tight text-[#6b7280]">Início: {formatDateTimePtBr(en.startWorkHour)}</div>
                  </td>
                  <td className="py-2.5 text-center">{en.billable === false ? 'Não' : 'Sim'}</td>
                  <td className="py-2.5 text-center text-[#6b7280]">{en.rate || '—'}</td>
                  <td className="max-w-[240px] truncate py-2.5 text-[#6b7280]" title={en.note || ''}>{en.note || '—'}</td>
                  <td className="py-2.5 text-right">
                    <button
                      type="button"
                      className="mr-2 rounded border border-[#e5e7eb] px-1.5 py-0.5 text-[11px] text-[#374151] hover:bg-[#f8fafc]"
                      title="Editar"
                      onClick={() => openManualModal(en)}
                    >
                      <i className="fa fa-edit" aria-hidden="true" />
                    </button>
                    <button
                      type="button"
                      className="rounded border border-[#e5e7eb] px-1.5 py-0.5 text-[11px] text-[#374151] hover:bg-[#f8fafc]"
                      title="Excluir"
                      onClick={() => handleDeleteEntry(en.id)}
                    >
                      <i className="fa fa-trash" aria-hidden="true" />
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <div className="mt-4 flex items-center justify-between">
          <button type="button" className="rounded-full border border-[#d1d5db] bg-white px-5 py-1.5 text-sm font-semibold text-[#374151]" onClick={() => setEntriesOpen(false)}>Fechar</button>
          <button type="button" className="rounded-full bg-[#f50074] px-5 py-1.5 text-sm font-semibold text-white hover:brightness-110" onClick={() => openManualModal(null)}>Adicionar entrada</button>
        </div>
      </div>
    </div>,
    document.body
  ) : null;

  const manualModal = manualOpen && typeof document !== 'undefined' ? createPortal(
    <div className="fixed inset-0 z-[10000] flex items-center justify-center bg-black/40 px-3 py-6" onClick={() => setManualOpen(false)}>
      <form className="w-full max-w-[860px] rounded-md border border-[#e5e7eb] bg-white p-5 text-[#1f2937] shadow-xl" onClick={(ev) => ev.stopPropagation()} onSubmit={submitManualForm}>
        <div className="mb-3 flex items-center justify-between border-b border-[#eef2f6] pb-3">
          <h3 className="text-[1.02rem] font-semibold text-[#1f2937]">{editingEntry ? 'Editar entrada de tempo' : 'Adicionar entrada de tempo'}</h3>
          <button type="button" className="rounded-md border border-[#e5e7eb] px-2 py-1 text-xs text-[#6b7280] hover:text-[#111827]" onClick={() => setManualOpen(false)}>Fechar</button>
        </div>
        <div className="grid gap-3">
          <label className="text-xs text-[#6b7280]">
            Duração
            <input type="text" value={form.duration} onChange={(e) => setForm((p) => ({ ...p, duration: e.target.value }))} className="mt-1 h-[38px] w-full rounded border border-[#d1d5db] bg-white px-3 py-1.5 text-sm text-[#111827]" />
            <span className="mt-1 block text-[10px]">hh:mm:ss</span>
          </label>
          <label className="text-xs text-[#6b7280]">
            Descrição
            <textarea value={form.descricao} onChange={(e) => setForm((p) => ({ ...p, descricao: e.target.value }))} placeholder="Digite sua descrição" rows={3} className="mt-1 w-full rounded border border-[#d1d5db] bg-white px-3 py-2 text-sm text-[#111827]" />
            <span className="mt-1 block text-right text-[10px] text-[#6b7280]">{form.descricao.length}/4000</span>
          </label>
          <label className="inline-flex items-center gap-2 text-xs text-[#6b7280]">
            <input type="checkbox" checked={form.billable} onChange={(e) => setForm((p) => ({ ...p, billable: e.target.checked }))} />
            Faturável
          </label>
          <label className="text-xs text-[#6b7280]">
            Taxa
            <select value={form.taxa} onChange={(e) => setForm((p) => ({ ...p, taxa: e.target.value }))} className="mt-1 h-[38px] w-full rounded border border-[#d1d5db] bg-white px-3 py-1.5 text-sm text-[#111827]">
              <option value="">Nada selecionado</option>
              <option value="padrao">Padrão</option>
            </select>
          </label>
          <div className="mt-1 flex items-center gap-2">
            <span className="h-px flex-1 bg-[#e5e7eb]" />
            <button type="button" className="w-fit text-xs text-[#374151]" onClick={() => setForm((p) => ({ ...p, showMore: !p.showMore }))}>
            {form.showMore ? 'Mostrar menos' : 'Mostrar mais'}
            </button>
            <span className="h-px flex-1 bg-[#e5e7eb]" />
          </div>
        </div>
        {form.showMore ? (
          <div className="mt-1 grid gap-3 sm:grid-cols-2">
            <label className="text-xs text-[#6b7280]">
            Data de Início
            <div className="mt-1 grid grid-cols-[1fr_180px] gap-2">
              <input type="date" value={form.startDate} onChange={(e) => setForm((p) => ({ ...p, startDate: e.target.value }))} className="h-[38px] w-full rounded border border-[#d1d5db] bg-white px-3 py-1.5 text-sm" required />
              <input type="time" step="1" value={form.startTime} onChange={(e) => setForm((p) => ({ ...p, startTime: e.target.value }))} className="h-[38px] w-full rounded border border-[#d1d5db] bg-white px-3 py-1.5 text-sm" required />
            </div>
          </label>
            <label className="text-xs text-[#6b7280]">
            Data de Término
            <div className="mt-1 grid grid-cols-[1fr_180px] gap-2">
              <input type="date" value={form.endDate} onChange={(e) => setForm((p) => ({ ...p, endDate: e.target.value }))} className="h-[38px] w-full rounded border border-[#d1d5db] bg-white px-3 py-1.5 text-sm" required />
              <input type="time" step="1" value={form.endTime} onChange={(e) => setForm((p) => ({ ...p, endTime: e.target.value }))} className="h-[38px] w-full rounded border border-[#d1d5db] bg-white px-3 py-1.5 text-sm" required />
            </div>
          </label>
          <label className="text-xs text-[#6b7280]">
            Técnico
            {entryTechnicians.length > 0 ? (
              <select
                value={form.technicianContactId}
                onChange={(e) => {
                  const nextId = e.target.value;
                  setForm((p) => ({ ...p, technicianContactId: nextId }));
                }}
                className="mt-1 h-[38px] w-full rounded border border-[#d1d5db] bg-white px-3 py-1.5 text-sm"
              >
                <option value="">Selecione</option>
                {entryTechnicians.map((tech) => (
                  <option key={tech.id} value={String(tech.id)}>
                    {(tech.name || '').trim() || `#${tech.id}`}
                  </option>
                ))}
              </select>
            ) : (
              <input
                type="number"
                min="1"
                value={form.technicianContactId}
                onChange={(e) => setForm((p) => ({ ...p, technicianContactId: e.target.value }))}
                className="mt-1 h-[38px] w-full rounded border border-[#d1d5db] bg-white px-3 py-1.5 text-sm"
              />
            )}
          </label>
            <div className="text-right text-xs text-[#6b7280]">12h clock</div>
          </div>
        ) : null}
        {editingEntry ? (
          <div className="mt-2 grid gap-3 sm:grid-cols-2">
            <label className="text-xs text-[#6b7280]">
              Motivo da alteração (auditoria)
              <input
                type="text"
                value={form.auditReason}
                onChange={(e) => setForm((p) => ({ ...p, auditReason: e.target.value }))}
                className="mt-1 h-[38px] w-full rounded border border-[#d1d5db] bg-white px-3 py-1.5 text-sm"
                required={Boolean(editingEntry)}
              />
            </label>
            <label className="text-xs text-[#6b7280]">
              Senha de auditoria
              <input
                type="password"
                value={form.auditAuthKey}
                onChange={(e) => setForm((p) => ({ ...p, auditAuthKey: e.target.value }))}
                className="mt-1 h-[38px] w-full rounded border border-[#d1d5db] bg-white px-3 py-1.5 text-sm"
                required={Boolean(editingEntry)}
              />
            </label>
          </div>
        ) : null}
        <div className="mt-5 flex justify-end gap-2 border-t border-[#eef2f6] pt-4">
          <button type="button" className="rounded-full border border-[#d1d5db] px-4 py-2 text-xs text-[#374151]" onClick={() => setManualOpen(false)}>Cancelar</button>
          <button type="submit" disabled={entriesBusy} className="rounded-full bg-[#1d9e75] px-4 py-2 text-xs font-semibold text-white hover:brightness-110">{entriesBusy ? 'Salvando...' : (editingEntry ? 'Salvar' : 'Adicionar entrada de tempo')}</button>
        </div>
      </form>
    </div>,
    document.body
  ) : null;

  return (
    <div className="min-h-0 space-y-3 px-1">
      <div className="flex flex-wrap gap-2">
        <button type="button" onClick={openEntriesModal} className="rounded-md border border-[var(--pgm-border)] bg-[var(--pgm-bg-elevated)] px-2.5 py-1.5 text-xs font-semibold text-[var(--pgm-text)] hover:bg-[var(--pgm-bg-raised)]">
          Ver todas as entradas
        </button>
        <button type="button" onClick={() => openManualModal(null)} className="rounded-md bg-[var(--pgm-primary)] px-2.5 py-1.5 text-xs font-semibold text-white hover:brightness-110">
          Entrada manual de tempo
        </button>
      </div>
      {onlyEntryActions ? (
        <>
          <div className="flex flex-wrap items-baseline gap-x-4 gap-y-1 text-sm">
            <div>
              <span className="text-[var(--pgm-text-muted)]">Total contabilizado neste ticket: </span>
              <strong className="text-[var(--pgm-text)]">{formatMinutosHumanos(totalMinutosTicket)}</strong>
              {minutosOficiais != null && Number(minutosOficiais) > 0 ? (
                <span className="ml-1 text-[0.65rem] text-[var(--pgm-text-muted)]">(Horas cadastradas)</span>
              ) : null}
            </div>
          </div>
          {entriesModal}
          {manualModal}
        </>
      ) : (
        <>
      <div className="flex flex-wrap items-baseline gap-x-4 gap-y-1 text-sm">
        <div>
          <span className="text-[var(--pgm-text-muted)]">Total contabilizado neste ticket: </span>
          <strong className="text-[var(--pgm-text)]">{formatMinutosHumanos(totalMinutosTicket)}</strong>
          {minutosOficiais != null && Number(minutosOficiais) > 0 ? (
            <span className="ml-1 text-[0.65rem] text-[var(--pgm-text-muted)]">(Horas cadastradas)</span>
          ) : null}
        </div>
        {filtersActive ? (
          <div>
            <span className="text-[var(--pgm-text-muted)]">Total filtrado: </span>
            <strong className="text-[var(--pgm-text)]">{formatMinutosFromSeconds(segundosFiltrados)}</strong>
          </div>
        ) : null}
      </div>

      <div className="flex flex-wrap items-end gap-3">
        <label className="flex flex-col gap-1 text-[0.7rem] text-[var(--pgm-text-muted)]">
          Dia / data
          <div className="flex items-center gap-1">
            <input
              type="text"
              inputMode="numeric"
              autoComplete="off"
              placeholder="dd/mm/aaaa"
              value={dateFilterInput}
              onChange={(e) => {
                const v = e.target.value;
                setDateFilterInput(v);
                const r = parseBrDateFilter(v);
                if (r.empty) setFilterDay('');
                else if (r.iso) setFilterDay(r.iso);
              }}
              onBlur={() => {
                const r = parseBrDateFilter(dateFilterInput.trim());
                if (r.empty) {
                  setFilterDay('');
                  setDateFilterInput('');
                } else if (r.iso) {
                  setFilterDay(r.iso);
                  setDateFilterInput(isoDateToBr(r.iso));
                } else {
                  setDateFilterInput(filterDay ? isoDateToBr(filterDay) : '');
                }
              }}
              className="w-[9.5rem] rounded-md border border-[var(--pgm-border)] bg-[var(--pgm-bg-raised)] px-2 py-1.5 text-sm text-[var(--pgm-text)]"
            />
            <div
              className="relative flex h-[2.125rem] w-[2.125rem] shrink-0 items-center justify-center overflow-hidden rounded-md border border-[var(--pgm-border)] bg-[var(--pgm-bg-raised)] text-[var(--pgm-text-muted)] hover:text-[var(--pgm-text)]"
              title="Calendário"
            >
              <svg
                className="pointer-events-none h-4 w-4"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                strokeWidth={2}
                aria-hidden="true"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                />
              </svg>
              <input
                type="date"
                lang="pt-BR"
                value={filterDay}
                onChange={(e) => {
                  const v = e.target.value;
                  setFilterDay(v);
                  setDateFilterInput(v ? isoDateToBr(v) : '');
                }}
                className="absolute inset-0 cursor-pointer opacity-0"
                aria-label="Abrir calendário para escolher a data"
              />
            </div>
          </div>
        </label>
        <label className="flex flex-col gap-1 text-[0.7rem] text-[var(--pgm-text-muted)]">
          Técnico
          <select
            value={filterTec}
            onChange={(e) => setFilterTec(e.target.value)}
            className="min-w-[12rem] rounded-md border border-[var(--pgm-border)] bg-[var(--pgm-bg-raised)] px-2 py-1.5 text-sm text-[var(--pgm-text)]"
          >
            <option value="">Todos</option>
            {technicianOptions.map((name) => (
              <option key={name} value={name}>
                {name}
              </option>
            ))}
          </select>
        </label>
        {(filterDay || filterTec) && (
          <button
            type="button"
            onClick={() => {
              setFilterDay('');
              setDateFilterInput('');
              setFilterTec('');
            }}
            className="rounded-md border border-[var(--pgm-border)] px-2.5 py-1.5 text-xs font-semibold text-[var(--pgm-text-muted)] hover:text-[var(--pgm-text)]"
          >
            Limpar filtros
          </button>
        )}
      </div>

      <p className="text-[0.7rem] leading-snug text-[var(--pgm-text-muted)]">
        Lançamentos de horas (legado e registos). O histórico completo de eventos está no separador «Histórico».
      </p>

      {filtered.length === 0 ? (
        <p className="text-sm text-[var(--pgm-text-muted)]">
          {eventosHoras.length === 0
            ? 'Nenhum lançamento de horas ainda. Use o timer do ticket para gravar tempo.'
            : 'Nenhum lançamento com os filtros seleccionados.'}
        </p>
      ) : (
        <TicketTimeline events={filtered} layout="cards" />
      )}
      {entriesModal}
      {manualModal}
        </>
      )}
    </div>
  );
}
