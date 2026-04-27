import { useMemo, useState } from 'react';
import TicketTimeline from './TicketTimeline.jsx';

function parseEventDate(ev) {
  const raw = ev?.created;
  if (!raw || typeof raw !== 'string') return null;
  const normalized = raw.includes('T') ? raw : raw.replace(' ', 'T');
  const d = new Date(normalized);
  return Number.isNaN(d.getTime()) ? null : d;
}

/**
 * Chave YYYY-MM-DD alinhada ao <input type="date">.
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

export default function TicketHorasTabPanel({ ticket, timelineEvents }) {
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
  const [filterTec, setFilterTec] = useState('');

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

  return (
    <div className="min-h-0 space-y-3 px-1">
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
          <input
            type="date"
            value={filterDay}
            onChange={(e) => setFilterDay(e.target.value)}
            className="rounded-md border border-[var(--pgm-border)] bg-[var(--pgm-bg-raised)] px-2 py-1.5 text-sm text-[var(--pgm-text)]"
          />
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
    </div>
  );
}
