import { useCallback, useEffect, useRef, useState } from 'react';
import { getBoot, postAuditValidate } from '../lib/api.js';

/**
 * @param {object} p
 * @param {() => void} p.onClose
 * @param {string} p.currentTimeHms display atual HH:MM:SS
 * @param {number} p.ticketId
 * @param {() => void} [p.onSuccess]
 */
export default function AuditModal({ onClose, currentTimeHms, ticketId, onSuccess }) {
  const [authKey, setAuthKey] = useState('');
  const [newTime, setNewTime] = useState(currentTimeHms);
  const [reason, setReason] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState(null);
  const [saveSuccess, setSaveSuccess] = useState(false);
  const onSuccessRef = useRef(onSuccess);
  const onCloseRef = useRef(onClose);
  onSuccessRef.current = onSuccess;
  onCloseRef.current = onClose;

  useEffect(() => {
    setNewTime(currentTimeHms);
  }, [currentTimeHms]);

  const boot = typeof window !== 'undefined' ? getBoot() : null;
  const userId = Number(boot?.userId) || 0;

  const finishSuccess = useCallback(() => {
    onSuccessRef.current?.();
    onCloseRef.current();
  }, []);

  useEffect(() => {
    if (!saveSuccess) {
      return undefined;
    }
    const t = setTimeout(finishSuccess, 2200);
    return () => clearTimeout(t);
  }, [saveSuccess, finishSuccess]);

  useEffect(() => {
    const onKey = (e) => {
      if (e.key !== 'Escape' || busy) {
        return;
      }
      if (saveSuccess) {
        finishSuccess();
        return;
      }
      onClose();
    };
    document.addEventListener('keydown', onKey);
    return () => document.removeEventListener('keydown', onKey);
  }, [busy, saveSuccess, finishSuccess, onClose]);

  async function handleSubmit(e) {
    e.preventDefault();
    setError(null);
    if (userId < 1) {
      setError('Sessão inválida.');
      return;
    }
    if (!/^\d{2}:\d{2}:\d{2}$/.test((currentTimeHms || '').trim())) {
      setError('Tempo atual inválido.');
      return;
    }
    if (!/^\d{2}:\d{2}:\d{2}$/.test((newTime || '').trim())) {
      setError('Novo tempo deve ser HH:MM:SS (ex.: 01:05:00).');
      return;
    }
    if (!(reason || '').trim()) {
      setError('Informe o motivo.');
      return;
    }
    if (!(authKey || '').trim()) {
      setError('Informe a senha de auditoria.');
      return;
    }
    setBusy(true);
    const res = await postAuditValidate({
      ticketId,
      userId,
      oldTime: currentTimeHms.trim(),
      newTime: newTime.trim(),
      reason: reason.trim(),
      authKey: authKey.trim(),
    });
    setBusy(false);
    if (res.ok) {
      setSaveSuccess(true);
    } else {
      setError(res.message || res.error || 'Falha.');
    }
  }

  const dismissOverlay = () => {
    if (busy) {
      return;
    }
    if (saveSuccess) {
      finishSuccess();
    } else {
      onClose();
    }
  };

  if (saveSuccess) {
    return (
      <div
        className="fixed inset-0 z-[11000] flex items-center justify-center bg-[rgba(15,23,42,0.88)] p-4"
        role="alertdialog"
        aria-live="polite"
        onClick={dismissOverlay}
      >
        <div
          className="w-full max-w-sm rounded-2xl border border-emerald-500/40 bg-[#1a2332] p-6 text-center text-[var(--pgm-text,#e8eaed)] shadow-xl"
          onClick={(e) => e.stopPropagation()}
        >
          <p className="text-sm font-medium text-emerald-400">Registo de auditoria gravado com sucesso.</p>
          <p className="mt-1 text-xs text-slate-400">O tempo no ecrã segue o servidor; este registo fica no histórico de auditoria.</p>
          <button
            type="button"
            onClick={finishSuccess}
            className="mt-4 rounded-lg bg-emerald-600 px-4 py-2 text-sm text-white hover:bg-emerald-500"
          >
            Fechar
          </button>
        </div>
      </div>
    );
  }

  return (
    <div
      className="fixed inset-0 z-[11000] flex items-center justify-center bg-[rgba(15,23,42,0.88)] p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby="audit-modal-title"
      onClick={dismissOverlay}
    >
      <div
        className="w-full max-w-md rounded-2xl border border-[var(--pgm-border-subtle,rgba(255,255,255,0.08))] bg-[var(--pgm-bg-elevated,#222834)] p-6 text-[var(--pgm-text,#e8eaed)] shadow-xl"
        onClick={(e) => e.stopPropagation()}
      >
        <h2 id="audit-modal-title" className="text-sm font-semibold text-[var(--pgm-text,#e8eaed)]">
          Auditoria de tempo
        </h2>
        <p className="mt-1 text-xs text-[var(--pgm-text-muted,#9aa0a8)]">Ticket #{ticketId} — ajuste manual registado com senha (servidor).</p>
        <form onSubmit={handleSubmit} className="mt-4 space-y-3">
          <div>
            <label className="text-[0.7rem] font-semibold uppercase tracking-[0.06em] text-[var(--pgm-text-muted)]">
              Senha de auditoria
            </label>
            <input
              type="password"
              autoComplete="off"
              value={authKey}
              onChange={(e) => setAuthKey(e.target.value)}
              className="mt-1 w-full rounded-md border border-[var(--pgm-border,#3d4554)] bg-[var(--pgm-bg-raised,#141820)] px-3 py-2 text-sm outline-none focus:border-[var(--pgm-primary,#1d9e75)]"
            />
          </div>
          <div>
            <label className="text-[0.7rem] font-semibold uppercase tracking-[0.06em] text-[var(--pgm-text-muted)]">Tempo exibido (registado)</label>
            <input
              type="text"
              readOnly
              value={currentTimeHms}
              className="mt-1 w-full cursor-not-allowed rounded-md border border-[var(--pgm-border)] bg-[var(--pgm-bg-raised)] px-3 py-2 font-mono text-sm opacity-80"
            />
          </div>
          <div>
            <label className="text-[0.7rem] font-semibold uppercase tracking-[0.06em] text-[var(--pgm-text-muted)]">Novo tempo (HH:MM:SS)</label>
            <input
              type="text"
              value={newTime}
              onChange={(e) => setNewTime(e.target.value)}
              placeholder="00:00:00"
              className="mt-1 w-full rounded-md border border-[var(--pgm-border)] bg-[var(--pgm-bg-raised)] px-3 py-2 font-mono text-sm outline-none focus:border-[var(--pgm-primary)]"
            />
          </div>
          <div>
            <label className="text-[0.7rem] font-semibold uppercase tracking-[0.06em] text-[var(--pgm-text-muted)]">Motivo</label>
            <textarea
              value={reason}
              onChange={(e) => setReason(e.target.value)}
              rows={3}
              className="mt-1 w-full rounded-md border border-[var(--pgm-border)] bg-[var(--pgm-bg-raised)] px-3 py-2 text-sm outline-none focus:border-[var(--pgm-primary)]"
            />
          </div>
          {error && <p className="text-xs text-[var(--pgm-badge-red-text,#ff9492)]">{error}</p>}
          <div className="flex justify-end gap-2 pt-2">
            <button
              type="button"
              onClick={onClose}
              disabled={busy}
              className="rounded-lg border border-[var(--pgm-border)] px-3 py-2 text-sm text-[var(--pgm-text-muted)] hover:bg-[var(--pgm-bg-raised)] disabled:opacity-50"
            >
              Cancelar
            </button>
            <button
              type="submit"
              disabled={busy}
              className="rounded-lg bg-[#10b981] px-3 py-2 text-sm font-medium text-white disabled:opacity-50"
            >
              {busy ? '…' : 'Registar'}
            </button>
          </div>
        </form>
        <p className="mt-2 text-[10px] text-slate-500">Tecla Esc fecha o painel (exceto enquanto grava).</p>
      </div>
    </div>
  );
}
