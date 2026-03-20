import { useRef, useState } from 'react';
import { deleteTicketAnexo, getBoot, uploadTicketAnexo, USE_MOCK } from '../lib/api';

/**
 * Lista anexos com envio, download, abrir no navegador e exclusão (API CakePHP).
 */
export default function TicketAnexosPanel({ ticketId, anexos, onAnexosChange, disabled }) {
  const inputRef = useRef(null);
  const [busy, setBusy] = useState(false);
  const [err, setErr] = useState(null);
  const boot = getBoot();
  const canApi = USE_MOCK || (boot?.paths?.apiAnexoUpload && boot?.paths?.apiAnexoDelete);

  async function onFileChange(e) {
    const f = e.target.files?.[0];
    e.target.value = '';
    if (!f || !ticketId) return;
    setBusy(true);
    setErr(null);
    const res = await uploadTicketAnexo(ticketId, f);
    setBusy(false);
    if (res.ok && res.anexo) {
      onAnexosChange([...(anexos || []), res.anexo]);
    } else {
      setErr(res.error || 'Falha no envio do arquivo.');
    }
  }

  async function remove(id) {
    if (!window.confirm('Remover este anexo?')) return;
    setBusy(true);
    setErr(null);
    const res = await deleteTicketAnexo(id);
    setBusy(false);
    if (res.ok) {
      if (Array.isArray(res.anexos)) {
        onAnexosChange(res.anexos);
      } else {
        onAnexosChange((anexos || []).filter((a) => String(a.id) !== String(id)));
      }
    } else {
      setErr(res.error || 'Falha ao remover.');
    }
  }

  const list = anexos || [];

  return (
    <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
      <div className="flex flex-wrap items-center justify-between gap-2">
        <h3 className="text-sm font-bold text-slate-900">Anexos</h3>
        {canApi && (
          <>
            <input ref={inputRef} type="file" className="hidden" onChange={onFileChange} disabled={disabled || busy} />
            <button
              type="button"
              disabled={disabled || busy}
              onClick={() => inputRef.current?.click()}
              className="text-xs font-semibold text-teal-700 hover:underline disabled:opacity-50"
            >
              {busy ? 'Aguarde…' : '+ Enviar arquivo'}
            </button>
          </>
        )}
      </div>
      {err && <p className="mt-2 text-xs text-rose-600">{err}</p>}
      {list.length > 0 ? (
        <ul className="mt-2 space-y-2 text-sm">
          {list.map((a) => (
            <li key={a.id} className="flex flex-wrap items-center justify-between gap-2 rounded-md border border-slate-100 bg-slate-50/80 px-2 py-1.5">
              <span className="min-w-0 flex-1 truncate font-medium text-slate-800" title={a.nome}>
                {a.nome}
              </span>
              <span className="flex flex-shrink-0 flex-wrap items-center gap-2 text-xs">
                {a.url && (
                  <a href={a.url} className="font-semibold text-teal-700 hover:underline" target="_blank" rel="noreferrer">
                    Baixar
                  </a>
                )}
                {a.urlView && (
                  <a href={a.urlView} className="font-semibold text-slate-600 hover:underline" target="_blank" rel="noreferrer">
                    Abrir
                  </a>
                )}
                {canApi && (
                  <button
                    type="button"
                    disabled={busy}
                    onClick={() => remove(a.id)}
                    className="font-semibold text-rose-600 hover:underline disabled:opacity-50"
                  >
                    Remover
                  </button>
                )}
              </span>
            </li>
          ))}
        </ul>
      ) : (
        <p className="mt-2 text-xs text-slate-500">Nenhum anexo ainda.</p>
      )}
    </div>
  );
}
