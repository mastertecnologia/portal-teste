import { useMemo, useState } from 'react';
import { getBoot } from '../lib/api';
import { stripHtml } from '../lib/text';

function formatSeconds(sec) {
  const s = Math.max(0, parseInt(String(sec), 10) || 0);
  const h = Math.floor(s / 3600);
  const m = Math.floor((s % 3600) / 60);
  const r = s % 60;
  return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(r).padStart(2, '0')}`;
}

const FILTER_LABELS = {
  all: 'Todos',
  mov: 'Alteração de situação',
  comment: 'Comentário',
  audit: 'Auditoria',
  alert: 'Alerta',
  technical_report: 'Evidência / registo',
  product_usage: 'Peças / estoque',
  worklog: 'Horas',
  csat: 'Satisfação',
  other: 'Outros',
};

function cleanMovDescription(desc) {
  const t = String(desc || '')
    .replace(/\s*\[movimento de situação\]\s*$/i, '')
    .trim();
  return t || '—';
}

function parseEventDate(ev) {
  const raw = ev?.created;
  if (!raw || typeof raw !== 'string') {
    return null;
  }
  const normalized = raw.includes('T') ? raw : raw.replace(' ', 'T');
  const d = new Date(normalized);
  return Number.isNaN(d.getTime()) ? null : d;
}

function startOfLocalDay(d) {
  return new Date(d.getFullYear(), d.getMonth(), d.getDate());
}

function dateGroupLabel(d) {
  const dayStr = d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' });
  const today = startOfLocalDay(new Date());
  const y = new Date(today);
  y.setDate(y.getDate() - 1);
  const d0 = startOfLocalDay(d);
  if (d0.getTime() === today.getTime()) {
    return `${dayStr} - Hoje`;
  }
  if (d0.getTime() === y.getTime()) {
    return `${dayStr} - Ontem`;
  }
  return dayStr;
}

function dateKeyForGroup(d) {
  return d.toLocaleDateString('en-CA', { year: 'numeric', month: '2-digit', day: '2-digit' });
}

function eventFilterKey(ev) {
  const t = (ev.type || '').toLowerCase();
  return t || 'other';
}

function iconToneForType(t) {
  const x = (t || '').toLowerCase();
  if (x === 'mov') {
    return 'bg-[#e87722]';
  }
  if (x === 'audit') {
    return 'bg-slate-500';
  }
  if (x === 'alert') {
    return 'bg-amber-500';
  }
  if (x === 'technical_report') {
    return 'bg-sky-500';
  }
  if (x === 'product_usage') {
    return 'bg-amber-700';
  }
  if (x === 'csat') {
    return 'bg-amber-400';
  }
  if (x === 'comment') {
    return 'bg-blue-500';
  }
  if (x === 'worklog') {
    return 'bg-emerald-500';
  }
  return 'bg-[var(--pgm-text-muted,#6b7280)]';
}

function titleSubtitleForEvent(ev) {
  const t = (ev.type || '').toLowerCase();
  if (t === 'mov') {
    const c = cleanMovDescription(ev.description);
    const subtitle = c === '—' ? (ev.autor || '—') : ev.autor ? `${c} · ${ev.autor}` : c;
    return {
      title: 'Alteração de situação',
      subtitle,
    };
  }
  if (t === 'audit') {
    return { title: 'Auditoria', subtitle: ev.autor ? `${ev.autor} · ${ev.description || '—'}` : (ev.description || '—') };
  }
  if (t === 'worklog') {
    const secH =
      ev.secondsSpent != null && ev.secondsSpent !== ''
        ? ev.secondsSpent
        : ev.seconds_spent != null && ev.seconds_spent !== ''
          ? ev.seconds_spent
          : 0;
    const dataHoraLine = [ev.workDateLabel, ev.workTimeRangeLabel].filter(Boolean).join(' · ');
    const when = [dataHoraLine, formatSeconds(secH || 0), ev.billingType].filter(Boolean).join(' · ');
    const sub = [when, ev.autor && `Técnico: ${ev.autor}`, ev.description && ev.description !== 'Registro de horas (legado)' ? ev.description : null]
      .filter(Boolean)
      .join(' · ');
    return { title: 'Lançamento de horas', subtitle: sub || '—' };
  }
  if (t === 'alert') {
    return { title: 'Alerta', subtitle: ev.description || '—' };
  }
  if (t === 'signature') {
    return { title: 'Assinatura', subtitle: ev.description || '—' };
  }
  if (t === 'csat' && ev.rating != null) {
    return {
      title: 'Satisfação',
      subtitle: `${'★'.repeat(Math.min(5, Math.max(0, Number(ev.rating) || 0)))} ${ev.description || ''}`.trim(),
    };
  }
  if (t === 'product_usage') {
    return { title: 'Peças / estoque', subtitle: ev.description || '—' };
  }
  if (t === 'technical_report') {
    return { title: 'Evidência / registo técnico', subtitle: ev.description || '—' };
  }
  if (t === 'comment') {
    const body = stripHtml(String(ev.description || '')).trim() || '—';
    return {
      title: 'Comentário',
      subtitle: `${ev.autor || '—'} adicionou um comentário.\n${body}`,
    };
  }
  return { title: `[${t || 'evento'}]`, subtitle: ev.description || '—' };
}

/**
 * @param {object} ev
 * @param {string} w
 */
function renderEvent(ev, w) {
  const t = (ev.type || 'event').toLowerCase();
  if (t === 'audit' || t === 'mov') {
    return (
      <div
        key={String(ev.id)}
        className="border-l-2 border-slate-300 pl-3 text-xs text-slate-500"
      >
        <div className="font-semibold text-slate-600">
          {t === 'mov' ? 'Movimentação' : 'Auditoria'} — {ev.autor || '—'}
        </div>
        <div className="whitespace-pre-wrap text-slate-600">{ev.description || '—'}</div>
        <div className="text-[10px] text-slate-400">{ev.createdLabel || ev.created || ''}</div>
      </div>
    );
  }
  if (t === 'worklog') {
    const secH =
      ev.secondsSpent != null && ev.secondsSpent !== ''
        ? ev.secondsSpent
        : ev.seconds_spent != null && ev.seconds_spent !== ''
          ? ev.seconds_spent
          : 0;
    const dataHoraLine = [ev.workDateLabel, ev.workTimeRangeLabel].filter(Boolean).join(' · ');
    const footerWhen = dataHoraLine ? '' : ev.createdLabel || '';
    return (
      <div
        key={String(ev.id)}
        className="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-950"
      >
        <div className="text-xs font-semibold text-emerald-800">Horas</div>
        {dataHoraLine ? <div className="text-xs text-emerald-900/95">{dataHoraLine}</div> : null}
        <div>
          {formatSeconds(secH || 0)}
          {ev.billingType ? ` · ${ev.billingType}` : ''}
        </div>
        {ev.autor ? <div className="text-[10px] text-emerald-800/90">Técnico: {ev.autor}</div> : null}
        {ev.description ? (
          <p className="mt-1 text-xs text-emerald-900">
            <span className="font-medium text-emerald-800">Motivo:</span> {ev.description}
          </p>
        ) : null}
        {footerWhen ? <div className="text-[10px] text-emerald-700">{footerWhen}</div> : null}
      </div>
    );
  }
  if (t === 'signature') {
    const src = ev.attachment ? `${w}${String(ev.attachment).replace(/^\//, '')}` : null;
    return (
      <div
        key={String(ev.id)}
        className="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
      >
        <div className="text-xs font-semibold">Assinatura</div>
        {src ? <img src={src} alt="Assinatura" className="mt-1 max-h-32 max-w-full" /> : null}
        <div className="text-[10px] text-slate-500">{ev.createdLabel || ''}</div>
      </div>
    );
  }
  if (t === 'alert') {
    const meta = ev.metadata && typeof ev.metadata === 'object' ? ev.metadata : {};
    const lvl = String(meta.level || meta.severity || 'warning').toLowerCase();
    const ring =
      lvl === 'danger' || lvl === 'critical'
        ? 'border-red-500/50 bg-red-950/30 text-red-100'
        : lvl === 'warning'
          ? 'border-amber-500/50 bg-amber-950/25 text-amber-100'
          : 'border-sky-500/40 bg-sky-950/20 text-sky-100';
    return (
      <div key={String(ev.id)} className={`rounded-lg border px-3 py-2 text-sm ${ring}`}>
        <div className="text-xs font-semibold">Alerta</div>
        <p className="mt-1 whitespace-pre-wrap">{ev.description || '—'}</p>
        <div className="text-[10px] opacity-80">{ev.createdLabel || ev.created || ''}</div>
      </div>
    );
  }
  if (t === 'csat' && ev.rating != null) {
    return (
      <div key={String(ev.id)} className="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm">
        <div className="text-xs font-semibold">Satisfação</div>
        <div>{'★'.repeat(Math.min(5, Math.max(0, Number(ev.rating) || 0)))}</div>
        <p className="mt-1 text-xs">{ev.description}</p>
      </div>
    );
  }
  if (t === 'product_usage') {
    return (
      <div key={String(ev.id)} className="rounded border border-slate-200 bg-slate-50 px-3 py-2 text-xs">
        <div className="font-semibold text-slate-700">Peça / estoque</div>
        <p className="whitespace-pre-wrap text-slate-800">{ev.description || '—'}</p>
      </div>
    );
  }
  if (t === 'comment' || t === 'technical_report') {
    return (
      <div key={String(ev.id)} className="rounded-2xl border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm">
        <div className="text-[10px] text-slate-500">
          {ev.autor || '—'} · {ev.createdLabel || ev.created || ''}
        </div>
        <p className="whitespace-pre-wrap text-slate-800">{ev.description || '—'}</p>
        {t === 'technical_report' && ev.attachment ? (
          <img
            className="mt-1 max-h-32 rounded border border-slate-100"
            src={`${w}${String(ev.attachment).replace(/^\//, '')}`}
            alt=""
          />
        ) : null}
      </div>
    );
  }
  return (
    <div key={String(ev.id)} className="rounded border border-slate-200 px-2 py-1 text-xs text-slate-700">
      <span className="font-mono">[{t}]</span> {ev.description || '—'}
    </div>
  );
}

const FILTER_ORDER = ['mov', 'comment', 'audit', 'alert', 'technical_report', 'product_usage', 'worklog', 'csat', 'signature', 'other'];

function TimelineHistoryLayout({ events, className }) {
  const w = (getBoot() || {}).webroot || '';
  const [typeFilter, setTypeFilter] = useState('all');

  const filterOptions = useMemo(() => {
    const keys = new Set();
    for (const ev of events || []) {
      keys.add(eventFilterKey(ev));
    }
    const rest = [...keys].filter((k) => !FILTER_ORDER.includes(k)).sort((a, b) => a.localeCompare(b));
    const ordered = FILTER_ORDER.filter((k) => keys.has(k));
    const allKeys = [...ordered, ...rest];
    return [{ value: 'all', label: FILTER_LABELS.all }, ...allKeys.map((k) => ({ value: k, label: FILTER_LABELS[k] || k }))];
  }, [events]);

  const filtered = useMemo(() => {
    if (typeFilter === 'all') {
      return events || [];
    }
    return (events || []).filter((ev) => eventFilterKey(ev) === typeFilter);
  }, [events, typeFilter]);

  const groups = useMemo(() => {
    const map = new Map();
    for (const ev of filtered) {
      const d = parseEventDate(ev);
      if (!d) {
        const k = '_nodate';
        if (!map.has(k)) {
          map.set(k, { label: 'Sem data', sortKey: '9999-99-99', items: [] });
        }
        map.get(k).items.push(ev);
        continue;
      }
      const key = dateKeyForGroup(d);
      if (!map.has(key)) {
        map.set(key, { label: dateGroupLabel(d), sortKey: key, date: d, items: [] });
      }
      map.get(key).items.push(ev);
    }
    const rows = [...map.values()];
    rows.sort((a, b) => a.sortKey.localeCompare(b.sortKey));
    for (const g of rows) {
      g.items.sort((a, b) => {
        const ta = parseEventDate(a)?.getTime() || 0;
        const tb = parseEventDate(b)?.getTime() || 0;
        return ta - tb;
      });
    }
    return rows;
  }, [filtered]);

  if (!events || events.length === 0) {
    return null;
  }

  return (
    <div className={`space-y-4 ${className}`}>
      <div className="flex flex-wrap items-center gap-2">
        <span className="text-[0.8125rem] text-[var(--pgm-text-muted,#9aa0a8)]">Filtrar por tipo:</span>
        <select
          value={typeFilter}
          onChange={(e) => setTypeFilter(e.target.value)}
          aria-label="Filtrar eventos por tipo"
          className="min-w-[10rem] rounded-lg border border-[var(--pgm-border,#3d4554)] bg-[var(--pgm-bg-raised,#141820)] px-2.5 py-1.5 text-[0.8125rem] text-[var(--pgm-text,#e8eaed)] outline-none focus:border-[var(--pgm-primary)]"
        >
          {filterOptions.map((o) => (
            <option key={o.value} value={o.value}>
              {o.label}
            </option>
          ))}
        </select>
      </div>

      {filtered.length === 0 ? (
        <p className="text-center text-[0.8125rem] text-[var(--pgm-text-muted,#9aa0a8)]">Nenhum evento com este filtro.</p>
      ) : (
        <div className="space-y-5">
          {groups.map((g) => (
            <div key={g.sortKey}>
              <h3 className="mb-3 border-b border-[var(--pgm-border-subtle,rgba(255,255,255,0.08))] pb-1.5 text-[0.8rem] font-semibold text-[var(--pgm-text,#e8eaed)]">
                {g.label}
              </h3>
              <ul className="space-y-3">
                {g.items.map((ev) => {
                  const d = parseEventDate(ev);
                  const timeStr = d
                    ? d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })
                    : '—';
                  const t = (ev.type || '').toLowerCase();
                  const { title, subtitle } = titleSubtitleForEvent(ev);
                  const tone = iconToneForType(t);
                  return (
                    <li
                      key={String(ev.id)}
                      className="grid grid-cols-[auto_auto_1fr] gap-2.5 border-b border-[var(--pgm-border-subtle,rgba(255,255,255,0.08))] pb-3 text-[0.8125rem] last:border-b-0 last:pb-0 sm:grid-cols-[2.5rem_1.5rem_1fr] sm:gap-3"
                    >
                      <time className="w-10 pt-0.5 text-right text-[0.75rem] tabular-nums text-[var(--pgm-text-muted,#9aa0a8)] sm:w-12">
                        {timeStr}
                      </time>
                      <div className="flex w-3 justify-center pt-1 sm:w-3.5" aria-hidden>
                        <span className={`h-2.5 w-2.5 flex-shrink-0 rounded-full sm:h-3 sm:w-3 ${tone}`} />
                      </div>
                      <div className="min-w-0">
                        <div className="font-semibold text-[var(--pgm-text,#e8eaed)]">{title}</div>
                        {t === 'technical_report' && ev.attachment ? (
                          <div className="mt-0.5 space-y-1">
                            <p className="whitespace-pre-wrap text-[0.75rem] leading-snug text-[var(--pgm-text-muted,#9aa0a8)]">
                              {subtitle}
                            </p>
                            <img
                              className="mt-0.5 max-h-32 rounded-md border border-[var(--pgm-border-subtle)]"
                              src={`${w}${String(ev.attachment).replace(/^\//, '')}`}
                              alt=""
                            />
                          </div>
                        ) : (
                          <p className="mt-0.5 whitespace-pre-wrap text-[0.75rem] leading-snug text-[var(--pgm-text-muted,#9aa0a8)]">
                            {subtitle}
                          </p>
                        )}
                      </div>
                    </li>
                  );
                })}
              </ul>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}

/**
 * Lista de eventos de timeline; o filtro (ex.: só horas) é feito no componente-pai.
 * @param {{ events: object[], className?: string, layout?: 'cards' | 'timeline' }} p
 */
export default function TicketTimeline({ events, className = '', layout = 'cards' }) {
  const w = (getBoot() || {}).webroot || '';
  const list = events || [];
  if (list.length === 0) {
    return null;
  }
  if (layout === 'timeline') {
    return <TimelineHistoryLayout events={list} className={className} />;
  }
  return <div className={`space-y-2 ${className}`}>{list.map((ev) => renderEvent(ev, w))}</div>;
}
