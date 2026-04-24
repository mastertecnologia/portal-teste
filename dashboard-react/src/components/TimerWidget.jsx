import { useCallback, useEffect, useRef, useState } from 'react';

const TIMER_WIDGET_POSITION_KEY = 'pgm_tickets_timer_widget_pos_v1';

function readStoredPosition() {
  if (typeof window === 'undefined') return null;
  try {
    const raw = window.localStorage.getItem(TIMER_WIDGET_POSITION_KEY);
    if (!raw) return null;
    const p = JSON.parse(raw);
    if (typeof p?.left !== 'number' || typeof p?.top !== 'number') return null;
    if (!Number.isFinite(p.left) || !Number.isFinite(p.top)) return null;
    return { left: p.left, top: p.top };
  } catch {
    return null;
  }
}

function defaultCornerPosition() {
  const vw = window.innerWidth;
  const vh = window.innerHeight;
  const w = Math.min(240, vw * 0.92);
  const approxH = 200;
  return { left: Math.round(vw - w - 20), top: Math.round(vh - approxH - 20) };
}

function clampWidgetPosition(left, top, elW, elH) {
  const vw = window.innerWidth;
  const vh = window.innerHeight;
  const pad = 8;
  const minLeft = pad - elW + 48;
  const maxLeft = vw - pad;
  const minTop = pad;
  const maxTop = vh - pad;
  return {
    left: Math.min(maxLeft, Math.max(minLeft, left)),
    top: Math.min(maxTop, Math.max(minTop, top)),
  };
}

/**
 * Widget flutuante (mock escuro): TICKET #, tempo, play / stop / ajuste (engrenagem).
 * Ações delegadas ao painel; engrenagem abre o mesmo modal de auditoria.
 */
function IconPlay() {
  return (
    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden>
      <path d="M8 5v14l11-7z" />
    </svg>
  );
}

function IconStop() {
  return (
    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden>
      <path d="M6 6h12v12H6z" />
    </svg>
  );
}

function IconGear() {
  return (
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden>
      <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
      <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" />
    </svg>
  );
}

export default function TimerWidget({
  ticketId,
  displayHms,
  busy,
  disabled,
  idle,
  running,
  paused,
  onPlay,
  onStop,
  onOpenAudit,
}) {
  const rootRef = useRef(null);
  const dragRef = useRef(null);
  const [pos, setPos] = useState(() => {
    if (typeof window === 'undefined') return { left: 0, top: 0 };
    return readStoredPosition() ?? defaultCornerPosition();
  });
  const [dragging, setDragging] = useState(false);

  const persistPos = useCallback((next) => {
    try {
      window.localStorage.setItem(TIMER_WIDGET_POSITION_KEY, JSON.stringify(next));
    } catch {
      /* quota / private mode */
    }
  }, []);

  useEffect(() => {
    function onResize() {
      const el = rootRef.current;
      if (!el) return;
      const { width, height } = el.getBoundingClientRect();
      setPos((p) => clampWidgetPosition(p.left, p.top, width, height));
    }
    window.addEventListener('resize', onResize);
    return () => window.removeEventListener('resize', onResize);
  }, []);

  useEffect(() => {
    const el = rootRef.current;
    if (!el) return;
    const { width, height } = el.getBoundingClientRect();
    setPos((p) => clampWidgetPosition(p.left, p.top, width, height));
  }, [ticketId]);

  if (!ticketId) {
    return null;
  }

  const playDisabled = disabled || busy || running;
  const stopDisabled = disabled || busy || idle;

  function handleHeaderPointerDown(e) {
    if (dragRef.current) return;
    if (e.pointerType === 'mouse' && e.button !== 0) return;
    const el = rootRef.current;
    const captureTarget = e.currentTarget;
    if (!el) return;
    const r = el.getBoundingClientRect();
    dragRef.current = {
      pointerId: e.pointerId,
      startX: e.clientX,
      startY: e.clientY,
      origLeft: r.left,
      origTop: r.top,
    };
    setDragging(true);
    try {
      captureTarget.setPointerCapture(e.pointerId);
    } catch {
      /* capture opcional */
    }

    function onMove(ev) {
      const d = dragRef.current;
      if (!d || ev.pointerId !== d.pointerId) return;
      const dx = ev.clientX - d.startX;
      const dy = ev.clientY - d.startY;
      const { width, height } = el.getBoundingClientRect();
      setPos(clampWidgetPosition(d.origLeft + dx, d.origTop + dy, width, height));
    }

    function onUp(ev) {
      const d = dragRef.current;
      if (!d || ev.pointerId !== d.pointerId) return;
      dragRef.current = null;
      setDragging(false);
      try {
        captureTarget.releasePointerCapture(ev.pointerId);
      } catch {
        /* */
      }
      window.removeEventListener('pointermove', onMove);
      window.removeEventListener('pointerup', onUp);
      window.removeEventListener('pointercancel', onUp);
      const { width, height } = el.getBoundingClientRect();
      setPos((p) => {
        const c = clampWidgetPosition(p.left, p.top, width, height);
        persistPos(c);
        return c;
      });
    }

    window.addEventListener('pointermove', onMove);
    window.addEventListener('pointerup', onUp);
    window.addEventListener('pointercancel', onUp);
  }

  return (
    <div
      ref={rootRef}
      className="pointer-events-auto fixed z-[10000] w-[min(240px,92vw)] select-none overflow-hidden rounded-xl border border-[#334155] shadow-lg"
      style={{
        left: pos.left,
        top: pos.top,
        boxShadow: '0 12px 30px rgba(0,0,0,0.45)',
      }}
    >
      <div
        title="Arrastar para mover o widget"
        onPointerDown={handleHeaderPointerDown}
        className={`bg-[#0f172a] px-3 py-2 text-[11px] font-bold uppercase tracking-wide text-slate-400 ${
          dragging ? 'cursor-grabbing touch-none' : 'cursor-grab touch-manipulation'
        }`}
      >
        TICKET #{ticketId}
      </div>
      <div className="bg-[#1e293b] px-3 pb-3 pt-2">
        <div className="text-center font-mono text-[1.55rem] font-medium leading-tight tracking-tight text-[#34d399]">
          {displayHms || '00:00:00'}
        </div>
        <div className="mt-3 grid grid-cols-3 gap-2">
          <button
            type="button"
            title={paused ? 'Retomar' : 'Iniciar'}
            aria-label={paused ? 'Retomar cronómetro' : 'Iniciar cronómetro'}
            disabled={playDisabled}
            onClick={() => onPlay?.()}
            className="flex h-11 items-center justify-center rounded-lg bg-[#10b981] text-white shadow-sm enabled:hover:bg-[#059669] disabled:cursor-not-allowed disabled:opacity-40"
          >
            <IconPlay />
          </button>
          <button
            type="button"
            title="Finalizar"
            aria-label="Finalizar cronómetro"
            disabled={stopDisabled}
            onClick={() => onStop?.()}
            className="flex h-11 items-center justify-center rounded-lg bg-[#ef4444] text-white shadow-sm enabled:hover:bg-[#dc2626] disabled:cursor-not-allowed disabled:opacity-40"
          >
            <IconStop />
          </button>
          <button
            type="button"
            title="Ajuste de auditoria"
            aria-label="Abrir ajuste de auditoria"
            disabled={disabled || busy}
            onClick={() => onOpenAudit?.()}
            className="flex h-11 items-center justify-center rounded-lg bg-[#475569] text-slate-200 shadow-sm enabled:hover:bg-[#64748b] disabled:cursor-not-allowed disabled:opacity-40"
          >
            <IconGear />
          </button>
        </div>
      </div>
    </div>
  );
}
