import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { getBoot, postAuditValidate } from '../lib/api.js';

function normalizeHms(s) {
  const t = (s || '').trim();
  if (/^\d{2}:\d{2}:\d{2}$/.test(t)) return t;
  return '00:00:00';
}

/**
 * @param {object} p
 * @param {() => void} p.onClose
 * @param {string} p.currentTimeHms display atual HH:MM:SS (cronómetro)
 * @param {number} p.ticketId
 * @param {{ duracaoHms: string, periodoInicio: string, periodoFim: string } | null | undefined} [p.ultimaFinalizacao]
 * @param {boolean} [p.sessaoAtiva] true se há timer iniciado (em curso ou pausado)
 * @param {() => void} [p.onSuccess]
 */
export default function AuditModal({
  onClose,
  currentTimeHms,
  ticketId,
  ultimaFinalizacao,
  sessaoAtiva = false,
  onSuccess,
}) {
  const [authKey, setAuthKey] = useState('');
  const [newTime, setNewTime] = useState(() => normalizeHms(currentTimeHms));
  const [reason, setReason] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState(null);
  const [saveSuccess, setSaveSuccess] = useState(false);
  const onSuccessRef = useRef(onSuccess);
  const onCloseRef = useRef(onClose);
  onSuccessRef.current = onSuccess;
  onCloseRef.current = onClose;

  const displayHms = useMemo(() => normalizeHms(currentTimeHms), [currentTimeHms]);

  /** Sem sessão ativa: o log de auditoria refere-se ao último bloco gravado em Horas cadastradas. */
  const oldTimeForAudit = useMemo(() => {
    if (!sessaoAtiva && ultimaFinalizacao?.duracaoHms && /^\d{2}:\d{2}:\d{2}$/.test(ultimaFinalizacao.duracaoHms)) {
      return ultimaFinalizacao.duracaoHms;
    }
    return displayHms;
  }, [sessaoAtiva, ultimaFinalizacao?.duracaoHms, displayHms]);

  useEffect(() => {
    if (!sessaoAtiva && ultimaFinalizacao?.duracaoHms && /^\d{2}:\d{2}:\d{2}$/.test(ultimaFinalizacao.duracaoHms)) {
      setNewTime(ultimaFinalizacao.duracaoHms);
    } else {
      setNewTime(displayHms);
    }
  }, [displayHms, sessaoAtiva, ultimaFinalizacao?.duracaoHms]);

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
    if (!/^\d{2}:\d{2}:\d{2}$/.test(oldTimeForAudit)) {
      setError('Tempo anterior (origem do registo) inválido.');
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
      oldTime: oldTimeForAudit,
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
      className="fixed inset-0 z-[11000] flex items-center justify-center bg-[rgba(15,23,42,0.55)] p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby="audit-modal-title"
      onClick={dismissOverlay}
    >
      <div
        className="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 text-slate-900 shadow-xl"
        onClick={(e) => e.stopPropagation()}
      >
        <h2 id="audit-modal-title" className="text-lg font-bold text-slate-900">
          Ajuste de Auditoria
        </h2>
        <div
          className="mt-3 rounded-md border-l-4 border-orange-500 bg-orange-50 px-3 py-2.5 text-sm text-slate-800"
          role="status"
        >
          <span className="font-semibold text-orange-900">Segurança:</span>{' '}
          Esta alteração será validada pelo CakePHP e gravada permanentemente no log.
        </div>
        <form onSubmit={handleSubmit} className="mt-5 space-y-4">
          <div className="rounded-lg border border-slate-200 bg-slate-50 px-3 py-3 text-sm text-slate-800">
            <p className="font-bold text-slate-900">Último tempo gravado (Horas cadastradas)</p>
            {ultimaFinalizacao?.duracaoHms ? (
              <>
                <p className="mt-2 font-mono text-base font-semibold text-slate-900">{ultimaFinalizacao.duracaoHms}</p>
                <p className="mt-1 text-xs text-slate-600">
                  Início: {ultimaFinalizacao.periodoInicio} · Fim: {ultimaFinalizacao.periodoFim}
                </p>
                {!sessaoAtiva ? (
                  <p className="mt-2 text-xs text-slate-600">
                    Sem cronómetro em curso: o registo de auditoria usa esta duração como <strong>tempo anterior</strong>.
                    Ajuste o campo abaixo para o valor correto.
                  </p>
                ) : (
                  <p className="mt-2 text-xs text-slate-500">
                    Com cronómetro ativo, o tempo anterior no log é o do display ({displayHms}); acima fica a última
                    gravação no ticket como referência.
                  </p>
                )}
              </>
            ) : (
              <p className="mt-2 text-xs text-slate-600">
                Não foi encontrado lançamento em Horas cadastradas para este ticket (ou datas inválidas na base).
              </p>
            )}
          </div>
          <div>
            <label className="text-sm font-bold text-slate-900" htmlFor="audit-old-time">
              Tempo anterior (enviado ao log)
            </label>
            <input
              id="audit-old-time"
              type="text"
              readOnly
              value={oldTimeForAudit}
              className="mt-1.5 w-full cursor-not-allowed rounded-lg border border-slate-200 bg-slate-100 px-3 py-2 font-mono text-sm text-slate-700"
              aria-readonly="true"
            />
          </div>
          <div>
            <label className="text-sm font-bold text-slate-900" htmlFor="audit-new-time">
              Novo Tempo (HH:MM:SS)
            </label>
            <input
              id="audit-new-time"
              type="text"
              value={newTime}
              onChange={(e) => setNewTime(e.target.value)}
              placeholder="00:00:00"
              className="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 font-mono text-sm text-slate-900 outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
            />
          </div>
          <div>
            <label className="text-sm font-bold text-slate-900" htmlFor="audit-password">
              Senha de Auditoria (Admin)
            </label>
            <input
              id="audit-password"
              type="password"
              autoComplete="off"
              value={authKey}
              onChange={(e) => setAuthKey(e.target.value)}
              placeholder="Senha definida pelo Admin"
              className="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
            />
          </div>
          <div>
            <label className="text-sm font-bold text-slate-900" htmlFor="audit-reason">
              Motivo da Alteração
            </label>
            <textarea
              id="audit-reason"
              value={reason}
              onChange={(e) => setReason(e.target.value)}
              rows={3}
              placeholder="Justifique este ajuste..."
              className="mt-1.5 w-full resize-y rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
            />
          </div>
          {error && <p className="text-sm text-red-600">{error}</p>}
          <div className="flex justify-end gap-2 pt-1">
            <button
              type="button"
              onClick={onClose}
              disabled={busy}
              className="rounded-lg bg-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-800 hover:bg-slate-300 disabled:opacity-50"
            >
              Cancelar
            </button>
            <button
              type="submit"
              disabled={busy}
              className="rounded-lg bg-orange-500 px-4 py-2.5 text-sm font-bold text-white hover:bg-orange-600 disabled:opacity-50"
            >
              {busy ? '…' : 'Validar e Salvar'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
