import { useRef, useState } from 'react';
import { deleteTicketAnexo, getBoot, uploadTicketAnexo, USE_MOCK } from '../lib/api';

/** Traduz códigos do controller para algo legível ao usuário. */
function describeAnexoError(code, detail) {
  const map = {
    no_file: 'Nenhum arquivo recebido. Tente novamente — verifique também o tamanho (post_max_size/upload_max_filesize do PHP).',
    upload_failed: 'Não foi possível salvar o arquivo no servidor (permissão da pasta webroot/arquivos/tickets ou disco cheio).',
    save_failed: 'O arquivo foi enviado, mas o registro no banco falhou.',
    not_found: 'Ticket ou anexo não encontrado.',
    forbidden: 'Sem permissão para anexar neste ticket.',
    unlink_failed: 'Não foi possível apagar o arquivo do disco.',
    delete_failed: 'Falha ao excluir o registro do anexo.',
    api_anexo_disabled: 'API de anexos indisponível neste contexto.',
    post_save_exception: 'Arquivo salvo, mas o registro pós-save falhou.',
    post_delete_exception: 'Anexo removido, mas a finalização falhou.',
  };
  if (!code) return null;
  const base = map[code] || code;
  return detail ? `${base} — ${detail}` : base;
}

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
      setErr(describeAnexoError(res.error, res.detail) || 'Falha no envio do arquivo.');
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
      setErr(describeAnexoError(res.error, res.detail) || 'Falha ao remover.');
    }
  }

  const list = anexos || [];

  return (
    <div className="overflow-hidden rounded-xl border border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-gradient-to-b from-[var(--pgm-bg-surface,#1a1f28)] to-[color-mix(in_srgb,var(--pgm-bg-surface,#1a1f28)_97%,rgba(255,255,255,0.03))] shadow-[var(--pgm-shadow-md)]">
      <div className="flex flex-wrap items-center justify-between gap-2 border-b border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-[var(--pgm-bg-elevated,#222834)] px-4 py-3">
        <h3 className="text-[0.85rem] font-semibold text-[var(--pgm-text,#e8eaed)]">Anexos</h3>
        {canApi && (
          <>
            <input ref={inputRef} type="file" className="hidden" onChange={onFileChange} disabled={disabled || busy} />
            <button
              type="button"
              disabled={disabled || busy}
              onClick={() => inputRef.current?.click()}
              className="text-xs font-semibold text-[var(--pgm-accent,#5cdbc0)] transition hover:text-white hover:underline disabled:opacity-50"
            >
              {busy ? 'Aguarde…' : '+ Enviar arquivo'}
            </button>
          </>
        )}
      </div>
      <div className="p-4">
        {err && <p className="mb-2 text-xs text-[var(--pgm-badge-red-text,#ff9492)]">{err}</p>}
        {list.length > 0 ? (
          <ul className="space-y-2 text-[0.8125rem]">
            {list.map((a) => (
              <li
                key={a.id}
                className="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-[var(--pgm-bg-elevated,#222834)] px-3 py-2"
              >
                <span className="min-w-0 flex-1 truncate font-medium text-[var(--pgm-text,#e8eaed)]" title={a.nome}>
                  {a.nome}
                </span>
                <span className="flex flex-shrink-0 flex-wrap items-center gap-2 text-xs">
                  {a.urlView && (
                    <a
                      href={a.urlView}
                      className="font-semibold text-[var(--pgm-text-secondary,#c4c9d1)] transition hover:text-white hover:underline"
                      target="_blank"
                      rel="noreferrer"
                      title="Abrir no navegador (PDF, imagens)"
                    >
                      Visualizar
                    </a>
                  )}
                  {a.url && (
                    <a
                      href={a.url}
                      className="font-semibold text-[var(--pgm-accent,#5cdbc0)] transition hover:text-white hover:underline"
                      target="_blank"
                      rel="noreferrer"
                      title="Baixar arquivo"
                    >
                      Baixar
                    </a>
                  )}
                  {canApi && (
                    <button
                      type="button"
                      disabled={busy}
                      onClick={() => remove(a.id)}
                      className="font-semibold text-[var(--pgm-badge-red-text,#ff9492)] transition hover:text-white hover:underline disabled:opacity-50"
                    >
                      Remover
                    </button>
                  )}
                </span>
              </li>
            ))}
          </ul>
        ) : (
          <p className="text-xs text-[var(--pgm-text-muted,#9aa0a8)]">Nenhum anexo ainda.</p>
        )}
      </div>
    </div>
  );
}
