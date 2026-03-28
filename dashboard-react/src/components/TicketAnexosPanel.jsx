import { useRef, useState } from 'react';
import { deleteTicketAnexo, getBoot, uploadTicketAnexo, USE_MOCK } from '../lib/api';

/**
 * Lista anexos com envio, download, abrir no navegador e exclusão (API CakePHP).
 */
export default function TicketAnexosPanel({ ticketId, anexos, onAnexosChange, disabled, embed = false }) {
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

  const shell = embed
    ? 'rounded-xl border border-[#484f58] bg-[#0d1117] p-4 shadow-[inset_0_0_0_1px_rgba(72,79,88,0.55)]'
    : 'rounded-lg border border-slate-200 bg-white p-4 shadow-sm';
  const titleCls = embed ? 'text-sm font-bold text-[#e6edf3]' : 'text-sm font-bold text-slate-900';
  const linkUpload = embed
    ? 'text-xs font-semibold text-[#5cdbc0] hover:underline disabled:opacity-50'
    : 'text-xs font-semibold text-teal-700 hover:underline disabled:opacity-50';
  const errCls = embed ? 'mt-2 text-xs text-red-400' : 'mt-2 text-xs text-rose-600';
  const rowCls = embed
    ? 'flex flex-wrap items-center justify-between gap-2 rounded-md border border-[#21262d] bg-[#161b22] px-2 py-1.5'
    : 'flex flex-wrap items-center justify-between gap-2 rounded-md border border-slate-100 bg-slate-50/80 px-2 py-1.5';
  const nameCls = embed ? 'min-w-0 flex-1 truncate font-medium text-[#e6edf3]' : 'min-w-0 flex-1 truncate font-medium text-slate-800';
  const visCls = embed ? 'font-semibold text-[#c9d1d9] hover:underline' : 'font-semibold text-slate-700 hover:underline';
  const dlCls = embed ? 'font-semibold text-[#5cdbc0] hover:underline' : 'font-semibold text-teal-700 hover:underline';
  const emptyCls = embed ? 'mt-2 text-xs text-[#6e7681]' : 'mt-2 text-xs text-slate-500';

  return (
    <div className={shell}>
      <div className="flex flex-wrap items-center justify-between gap-2">
        <h3 className={titleCls}>Anexos</h3>
        {canApi && (
          <>
            <input ref={inputRef} type="file" className="hidden" onChange={onFileChange} disabled={disabled || busy} />
            <button type="button" disabled={disabled || busy} onClick={() => inputRef.current?.click()} className={linkUpload}>
              {busy ? 'Aguarde…' : '+ Enviar arquivo'}
            </button>
          </>
        )}
      </div>
      {err && <p className={errCls}>{err}</p>}
      {list.length > 0 ? (
        <ul className="mt-2 space-y-2 text-sm">
          {list.map((a) => (
            <li key={a.id} className={rowCls}>
              <span className={nameCls} title={a.nome}>
                {a.nome}
              </span>
              <span className="flex flex-shrink-0 flex-wrap items-center gap-2 text-xs">
                {a.urlView && (
                  <a href={a.urlView} className={visCls} target="_blank" rel="noreferrer" title="Abrir no navegador (PDF, imagens)">
                    Visualizar
                  </a>
                )}
                {a.url && (
                  <a href={a.url} className={dlCls} target="_blank" rel="noreferrer" title="Baixar arquivo">
                    Baixar
                  </a>
                )}
                {canApi && (
                  <button
                    type="button"
                    disabled={busy}
                    onClick={() => remove(a.id)}
                    className={embed ? 'font-semibold text-red-400 hover:underline disabled:opacity-50' : 'font-semibold text-rose-600 hover:underline disabled:opacity-50'}
                  >
                    Remover
                  </button>
                )}
              </span>
            </li>
          ))}
        </ul>
      ) : (
        <p className={emptyCls}>Nenhum anexo ainda.</p>
      )}
    </div>
  );
}
