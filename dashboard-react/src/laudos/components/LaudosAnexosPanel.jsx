import React, { useRef, useState } from 'react';
import { AnexosAPI } from '../api';
import { friendlyLaudosError } from '../utils/friendlyError';

/**
 * Anexos do parecer — upload, download e exclusão.
 */
export default function LaudosAnexosPanel({ parecerId, anexos = [], disabled, onChanged }) {
  const fileRef = useRef(null);
  const [busy, setBusy] = useState(false);

  const refresh = () => onChanged?.();

  const handleFiles = async (files) => {
    if (!files?.length || disabled) return;
    setBusy(true);
    try {
      for (const f of Array.from(files)) {
        await AnexosAPI.upload(parecerId, f, '');
      }
      refresh();
    } catch (err) {
      alert('Erro ao enviar anexo: ' + friendlyLaudosError(err));
    } finally {
      setBusy(false);
      if (fileRef.current) fileRef.current.value = '';
    }
  };

  const handleRemove = async (anexo) => {
    if (disabled || !confirm(`Remover o anexo "${anexo.nome_original}"?`)) return;
    try {
      await AnexosAPI.remove(anexo.id);
      refresh();
    } catch (err) {
      alert('Erro ao remover: ' + friendlyLaudosError(err));
    }
  };

  return (
    <div style={{
      background: 'white', border: '1px solid #e5e7eb', borderRadius: 8,
      marginBottom: 16, padding: 16,
    }}>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 12 }}>
        <h3 style={{
          margin: 0, fontSize: 14, fontWeight: 600, color: '#111827',
          textTransform: 'uppercase', letterSpacing: 0.4,
        }}
        >
          Anexos
        </h3>
        <div>
          <input
            ref={fileRef}
            type="file"
            multiple
            style={{ display: 'none' }}
            onChange={(e) => handleFiles(e.target.files)}
          />
          <button
            type="button"
            disabled={disabled || busy}
            onClick={() => fileRef.current?.click()}
            style={{
              padding: '8px 16px', fontSize: 13, fontWeight: 500,
              background: '#3b82f6', color: 'white', border: 'none',
              borderRadius: 6, cursor: disabled ? 'not-allowed' : 'pointer',
            }}
          >
            {busy ? 'Enviando...' : '+ Adicionar anexos'}
          </button>
        </div>
      </div>

      {(!anexos || anexos.length === 0) ? (
        <div style={{ padding: 16, textAlign: 'center', color: '#6b7280', border: '2px dashed #d1d5db', borderRadius: 6 }}>
          Nenhum anexo. PDFs, documentos Word/Excel ou imagens até 5 MB.
        </div>
      ) : (
        <ul style={{ listStyle: 'none', margin: 0, padding: 0 }}>
          {anexos.map((a) => (
            <li
              key={a.id}
              style={{
                display: 'flex', justifyContent: 'space-between', alignItems: 'center',
                padding: '10px 0', borderBottom: '1px solid #f3f4f6', fontSize: 13,
              }}
            >
              <span style={{ fontWeight: 500 }}>{a.nome_original}</span>
              <span style={{ display: 'flex', gap: 8 }}>
                <a
                  href={AnexosAPI.downloadUrl(a.id)}
                  target="_blank"
                  rel="noreferrer"
                  style={{ color: '#3b82f6', fontSize: 12 }}
                >
                  Baixar
                </a>
                <button
                  type="button"
                  disabled={disabled}
                  onClick={() => handleRemove(a)}
                  style={{
                    border: 'none', background: 'transparent', color: '#dc2626',
                    cursor: disabled ? 'not-allowed' : 'pointer', fontSize: 12,
                  }}
                >
                  Remover
                </button>
              </span>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
