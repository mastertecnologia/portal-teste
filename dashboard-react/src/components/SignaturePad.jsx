import { useCallback, useEffect, useRef, useState } from 'react';

/**
 * @param {object} p
 * @param {(dataUrl: string | null) => void} p.onChange
 * @param {string} p.className
 */
export default function SignaturePad({ onChange, className = '' }) {
  const cvs = useRef(null);
  const [drawing, setDrawing] = useState(false);
  const getCtx = useCallback(() => cvs.current?.getContext('2d'), []);

  useEffect(() => {
    const el = cvs.current;
    if (!el) return;
    const dpr = window.devicePixelRatio || 1;
    const w = 400;
    const h = 140;
    el.width = w * dpr;
    el.height = h * dpr;
    el.style.width = `${w}px`;
    el.style.height = `${h}px`;
    const ctx = el.getContext('2d');
    ctx.setTransform(1, 0, 0, 1, 0, 0);
    ctx.scale(dpr, dpr);
    ctx.fillStyle = '#fff';
    ctx.fillRect(0, 0, w, h);
    ctx.strokeStyle = '#0f172a';
    ctx.lineWidth = 2;
    onChange?.(null);
  }, [onChange]);

  const pos = (e) => {
    const el = cvs.current;
    if (!el) return { x: 0, y: 0 };
    const r = el.getBoundingClientRect();
    const t = e.touches?.[0];
    const clientX = t ? t.clientX : e.clientX;
    const clientY = t ? t.clientY : e.clientY;
    return { x: clientX - r.left, y: clientY - r.top };
  };

  const emit = useCallback(() => {
    const el = cvs.current;
    if (!el) return;
    try {
      onChange?.(el.toDataURL('image/png'));
    } catch {
      onChange?.(null);
    }
  }, [onChange]);

  const onDown = (e) => {
    e.preventDefault();
    setDrawing(true);
    const p = pos(e);
    const ctx = getCtx();
    if (!ctx) return;
    ctx.beginPath();
    ctx.moveTo(p.x, p.y);
  };
  const onUp = (e) => {
    e.preventDefault();
    setDrawing(false);
    emit();
  };
  const onMove = (e) => {
    if (!drawing) return;
    e.preventDefault();
    const p = pos(e);
    const ctx = getCtx();
    if (!ctx) return;
    ctx.lineTo(p.x, p.y);
    ctx.stroke();
  };

  function clear() {
    const el = cvs.current;
    if (!el) return;
    const ctx = el.getContext('2d');
    ctx.fillStyle = '#fff';
    ctx.fillRect(0, 0, 400, 140);
    onChange?.(null);
  }

  return (
    <div className={className}>
      <div className="text-xs font-medium text-slate-700">Assinatura (opcional)</div>
      <canvas
        ref={cvs}
        className="mt-1 touch-none cursor-crosshair rounded border border-slate-300 bg-white"
        onMouseDown={onDown}
        onMouseUp={onUp}
        onMouseLeave={onUp}
        onMouseMove={onMove}
        onTouchStart={onDown}
        onTouchEnd={onUp}
        onTouchMove={onMove}
      />
      <button
        type="button"
        onClick={clear}
        className="mt-1 text-xs text-slate-600 underline"
      >
        Limpar
      </button>
    </div>
  );
}
