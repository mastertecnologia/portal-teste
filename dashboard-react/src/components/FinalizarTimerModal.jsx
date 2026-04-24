import { useEffect, useState } from 'react';
import SignaturePad from './SignaturePad.jsx';

/**
 * @param {object} p
 * @param {boolean} p.open
 * @param {string} p.displayHms
 * @param {boolean} p.busy
 * @param {boolean} p.canEditDescricaoAtendimento
 * @param {() => void} p.onClose
 * @param {(atividade: string, signatureDataUrl?: string | null) => Promise<{ ok: boolean, error?: string }>} p.onSubmit
 */
export default function FinalizarTimerModal({
  open,
  displayHms,
  busy,
  canEditDescricaoAtendimento,
  onClose,
  onSubmit,
}) {
  const [texto, setTexto] = useState('');
  const [error, setError] = useState(null);
  const [sig, setSig] = useState(null);

  useEffect(() => {
    if (open) {
      setTexto('');
      setError(null);
      setSig(null);
    }
  }, [open]);

  useEffect(() => {
    if (!open) return undefined;
    const onKey = (e) => {
      if (e.key === 'Escape' && !busy) {
        onClose();
      }
    };
    document.addEventListener('keydown', onKey);
    return () => document.removeEventListener('keydown', onKey);
  }, [open, busy, onClose]);

  if (!open) {
    return null;
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setError(null);
    const t = (texto || '').trim();
    if (canEditDescricaoAtendimento && t.length < 3) {
      setError('Descreva o que foi feito nestes minutos (mínimo 3 caracteres).');
      return;
    }
    const r = await onSubmit(texto, sig);
    if (r && !r.ok && r.error) {
      setError(r.error);
    }
  }

  const hms = displayHms && displayHms.length === 8 ? displayHms : '00:00:00';

  return (
    <div
      className="fixed inset-0 z-[10500] flex items-center justify-center bg-black/45 p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby="finalize-timer-title"
      onClick={() => !busy && onClose()}
    >
      <div
        className="w-full max-w-lg rounded-xl border border-slate-200 bg-white p-6 text-slate-900 shadow-2xl"
        onClick={(e) => e.stopPropagation()}
      >
        <h2 id="finalize-timer-title" className="text-lg font-bold text-slate-900">
          Finalizar Atendimento
        </h2>
        <p className="mt-3 text-sm text-slate-600">
          Tempo total registrado:{' '}
          <span className="inline-block rounded-md bg-emerald-100 px-2 py-0.5 font-mono text-sm font-semibold text-emerald-800">
            {hms}
          </span>
        </p>

        <form onSubmit={handleSubmit} className="mt-4 space-y-3">
          {canEditDescricaoAtendimento ? (
            <div>
              <label className="sr-only" htmlFor="finalize-atividade">
                O que foi feito nestes minutos
              </label>
              <textarea
                id="finalize-atividade"
                value={texto}
                onChange={(e) => setTexto(e.target.value)}
                rows={5}
                disabled={busy}
                placeholder="O que foi feito nestes minutos? Descreva as atividades…"
                className="w-full resize-y rounded-lg border-2 border-slate-900 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-emerald-600"
              />
            </div>
          ) : (
            <p className="text-sm text-slate-500">
              As horas serão lançadas em Horas cadastradas. Confirme abaixo para finalizar o timer.
            </p>
          )}

          <SignaturePad onChange={setSig} className="mt-3" />

          {error && <p className="text-sm text-red-600">{error}</p>}

          <div className="flex justify-end gap-2 pt-2">
            <button
              type="button"
              disabled={busy}
              onClick={onClose}
              className="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50"
            >
              Voltar
            </button>
            <button
              type="submit"
              disabled={busy}
              className="rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-700 disabled:opacity-50"
            >
              {busy ? '…' : 'Salvar e Lançar Horas'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
