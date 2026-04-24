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
  const w = Math.min(200, vw * 0.92);
  const approxH = 132;
  return { left: Math.round(vw - w - 16), top: Math.round(vh - approxH - 16) };
}

function clampWidgetPosition(left, top, elW, elH) {
  const vw = window.innerWidth;
  const vh = window.innerHeight;
  const pad = 8;
  const w = Number.isFinite(elW) && elW > 0 ? elW : Math.min(200, vw * 0.92);
  const h = Number.isFinite(elH) && elH > 0 ? elH : 132;
  const minLeft = pad - w + 48;
  const maxLeft = Math.max(minLeft, vw - w - pad);
  const minTop = pad;
  const maxTop = Math.max(minTop, vh - h - pad);
  return {
    left: Math.round(Math.min(maxLeft, Math.max(minLeft, left))),
    top: Math.round(Math.min(maxTop, Math.max(minTop, top))),
  };
}

/** Widget flutuante compacto: TICKET #, tempo, play e finalizar. */
function IconPlay() {
  return (
    <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" aria-hidden>
      <path d="M8 5v14l11-7z" />
    </svg>
  );
}

function IconStop() {
  return (
    <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor" aria-hidden>
      <path d="M6 6h12v12H6z" />
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
    function reclampToViewport() {
      const el = rootRef.current;
      if (!el) return;
      const { width, height } = el.getBoundingClientRect();
      setPos((p) => clampWidgetPosition(p.left, p.top, width, height));
    }
    window.addEventListener('resize', reclampToViewport);
    const vv = window.visualViewport;
    if (vv) {
      vv.addEventListener('resize', reclampToViewport);
      vv.addEventListener('scroll', reclampToViewport);
    }
    const id = requestAnimationFrame(() => reclampToViewport());
    return () => {
      cancelAnimationFrame(id);
      window.removeEventListener('resize', reclampToViewport);
      if (vv) {
        vv.removeEventListener('resize', reclampToViewport);
        vv.removeEventListener('scroll', reclampToViewport);
      }
    };
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
      className="pointer-events-auto fixed z-[10000] w-[min(200px,92vw)] select-none overflow-hidden rounded-lg border border-[#334155] shadow-lg"
      style={{
        left: pos.left,
        top: pos.top,
        boxShadow: '0 10px 24px rgba(0,0,0,0.42)',
      }}
    >
      <div
        title="Arrastar para mover o widget"
        onPointerDown={handleHeaderPointerDown}
        className={`bg-[#0f172a] px-2 py-1.5 text-[10px] font-bold uppercase tracking-wide text-slate-400 ${
          dragging ? 'cursor-grabbing touch-none' : 'cursor-grab touch-manipulation'
        }`}
      >
        TICKET #{ticketId}
      </div>
      <div className="bg-[#1e293b] px-2 pb-2 pt-1.5">
        <div className="text-center font-mono text-[1.15rem] font-medium leading-tight tracking-tight text-[#34d399]">
          {displayHms || '00:00:00'}
        </div>
        <div className="mt-2 grid grid-cols-2 gap-1.5">
          <button
            type="button"
            title={paused ? 'Retomar' : 'Iniciar'}
            aria-label={paused ? 'Retomar cronómetro' : 'Iniciar cronómetro'}
            disabled={playDisabled}
            onClick={() => onPlay?.()}
            className="flex h-9 items-center justify-center rounded-md bg-[#10b981] text-white shadow-sm enabled:hover:bg-[#059669] disabled:cursor-not-allowed disabled:opacity-40"
          >
            <IconPlay />
          </button>
          <button
            type="button"
            title="Finalizar"
            aria-label="Finalizar cronómetro"
            disabled={stopDisabled}
            onClick={() => onStop?.()}
            className="flex h-9 items-center justify-center rounded-md bg-[#ef4444] text-white shadow-sm enabled:hover:bg-[#dc2626] disabled:cursor-not-allowed disabled:opacity-40"
          >
            <IconStop />
          </button>
        </div>
      </div>
    </div>
  );
}
