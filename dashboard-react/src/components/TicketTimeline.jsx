import { getBoot } from '../lib/api';

function formatSeconds(sec) {
  const s = Math.max(0, parseInt(String(sec), 10) || 0);
  const h = Math.floor(s / 3600);
  const m = Math.floor((s % 3600) / 60);
  const r = s % 60;
  return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(r).padStart(2, '0')}`;
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
    return (
      <div
        key={String(ev.id)}
        className="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-950"
      >
        <div className="text-xs font-semibold text-emerald-800">Horas</div>
        <div>
          {formatSeconds(ev.secondsSpent || 0)}
          {ev.billingType ? ` · ${ev.billingType}` : ''}
        </div>
        {ev.description ? <p className="mt-1 text-xs text-emerald-900">{ev.description}</p> : null}
        <div className="text-[10px] text-emerald-700">{ev.createdLabel || ''}</div>
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

const sectionTitleClass =
  'mb-2 border-b border-[var(--pgm-border-subtle,#e2e8f0)] pb-1.5 text-[0.7rem] font-semibold uppercase tracking-wide text-[var(--pgm-text-muted,#64748b)]';

/**
 * Separado: movimentações/auditoria/… vs horas (worklog) para não misturar no histórico.
 * @param {{ events: object[], className?: string }} p
 */
export default function TicketTimeline({ events, className = '' }) {
  const w = (getBoot() || {}).webroot || '';
  const list = events || [];
  const horas = list.filter((ev) => (ev.type || '').toLowerCase() === 'worklog');
  const outras = list.filter((ev) => (ev.type || '').toLowerCase() !== 'worklog');

  if (list.length === 0) {
    return null;
  }

  return (
    <div className={`space-y-5 ${className}`}>
      {outras.length > 0 ? (
        <section>
          <h3 className={sectionTitleClass}>Movimentações, auditoria e mais</h3>
          <div className="space-y-2">{outras.map((ev) => renderEvent(ev, w))}</div>
        </section>
      ) : null}
      {horas.length > 0 ? (
        <section>
          <h3 className={sectionTitleClass}>Horas</h3>
          <div className="space-y-2">{horas.map((ev) => renderEvent(ev, w))}</div>
        </section>
      ) : null}
    </div>
  );
}
