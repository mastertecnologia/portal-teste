import React, { useRef, useEffect, useState } from 'react';

/**
 * Canvas para desenho de assinatura digital.
 *
 * Props:
 *   value: dataURL da imagem atual (base64) ou null
 *   onChange: (dataURL) => void
 */
export default function SignaturePad({ value, onChange }) {
  const canvasRef = useRef(null);
  const drawingRef = useRef(false);
  const lastPosRef = useRef({ x: 0, y: 0 });
  const [hasDrawn, setHasDrawn] = useState(!!value);

  // Setup canvas e carrega valor inicial se houver
  useEffect(() => {
    const canvas = canvasRef.current;
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    ctx.strokeStyle = '#1f2937';
    ctx.fillStyle = '#fff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    if (value) {
      const img = new Image();
      img.onload = () => {
        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
        setHasDrawn(true);
      };
      img.src = value;
    }
  }, []);

  const getPos = (e) => {
    const canvas = canvasRef.current;
    const rect = canvas.getBoundingClientRect();
    const scaleX = canvas.width / rect.width;
    const scaleY = canvas.height / rect.height;
    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
    const clientY = e.touches ? e.touches[0].clientY : e.clientY;
    return {
      x: (clientX - rect.left) * scaleX,
      y: (clientY - rect.top) * scaleY,
    };
  };

  const startDraw = (e) => {
    e.preventDefault();
    drawingRef.current = true;
    lastPosRef.current = getPos(e);
  };

  const draw = (e) => {
    if (!drawingRef.current) return;
    e.preventDefault();
    const ctx = canvasRef.current.getContext('2d');
    const pos = getPos(e);
    ctx.beginPath();
    ctx.moveTo(lastPosRef.current.x, lastPosRef.current.y);
    ctx.lineTo(pos.x, pos.y);
    ctx.stroke();
    lastPosRef.current = pos;
    setHasDrawn(true);
  };

  const endDraw = () => {
    if (!drawingRef.current) return;
    drawingRef.current = false;
    if (canvasRef.current) {
      const dataURL = canvasRef.current.toDataURL('image/png');
      onChange?.(dataURL);
    }
  };

  const handleClear = () => {
    const canvas = canvasRef.current;
    const ctx = canvas.getContext('2d');
    ctx.fillStyle = '#fff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    setHasDrawn(false);
    onChange?.(null);
  };

  return (
    <div>
      <div style={{
        border: '1px solid #d1d5db', borderRadius: 6,
        background: '#fff', display: 'inline-block',
      }}>
        <canvas
          ref={canvasRef}
          width={500}
          height={150}
          style={{ display: 'block', cursor: 'crosshair', touchAction: 'none', maxWidth: '100%' }}
          onMouseDown={startDraw}
          onMouseMove={draw}
          onMouseUp={endDraw}
          onMouseLeave={endDraw}
          onTouchStart={startDraw}
          onTouchMove={draw}
          onTouchEnd={endDraw}
        />
      </div>
      <div style={{ marginTop: 8, display: 'flex', gap: 8, alignItems: 'center' }}>
        <button
          type="button"
          onClick={handleClear}
          style={{
            padding: '6px 12px', fontSize: 12,
            border: '1px solid #d1d5db', borderRadius: 4,
            background: 'white', cursor: 'pointer',
          }}
        >
          Limpar
        </button>
        <span style={{ fontSize: 11, color: '#6b7280' }}>
          {hasDrawn ? '✓ Assinado' : 'Desenhe sua assinatura acima'}
        </span>
      </div>
    </div>
  );
}
