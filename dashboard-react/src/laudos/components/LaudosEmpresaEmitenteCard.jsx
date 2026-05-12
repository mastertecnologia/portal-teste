import React, { useMemo, useRef, useState } from 'react';
import { EmpresasAPI } from '../api';
import useDebounceSave from '../hooks/useDebounceSave';
import SaveIndicator from './SaveIndicator';
import { maskCNPJ, maskCEP, maskPhone } from '../utils/masks';
import { friendlyLaudosError } from '../utils/friendlyError';

function pickEmpresa(empresa) {
  if (!empresa) return null;
  return {
    razao_social: empresa.razao_social ?? '',
    cnpj: empresa.cnpj ?? '',
    email: empresa.email ?? '',
    telefone: empresa.telefone ?? '',
    telefone2: empresa.telefone2 ?? '',
    cep: empresa.cep ?? '',
    endereco: empresa.endereco ?? '',
    site: empresa.site ?? '',
    public_validation_url: empresa.public_validation_url ?? '',
  };
}

/**
 * Dados da empresa emissora (laudos_empresas) — estilo dos cards do módulo Laudos.
 * Logo e carimbo: upload multipart + pré-visualização.
 */
export default function LaudosEmpresaEmitenteCard({ empresa, empresaId, disabled, onEmpresaUpdated }) {
  const saveData = useMemo(() => pickEmpresa(empresa), [empresa]);
  const logoRef = useRef(null);
  const carRef = useRef(null);
  const [busy, setBusy] = useState({ logo: false, carimbo: false });

  const { saveStatus, savedAt } = useDebounceSave({
    data: saveData,
    onSave: async (data) => {
      const resp = await EmpresasAPI.update(empresaId, data);
      if (resp.data) {
        onEmpresaUpdated?.(resp.data);
      }
    },
    delay: 700,
    enabled: !disabled && !!empresaId && !!saveData,
  });

  const e = empresa || {};
  const cacheKey = e.modified || e.updated || '';

  const previewUrl = (path) => {
    if (!path) return '';
    const clean = String(path).replace(/^\/+/, '');
    return `/${clean}${cacheKey ? `?v=${encodeURIComponent(String(cacheKey))}` : ''}`;
  };

  const patchField = (field, value) => {
    onEmpresaUpdated?.({ [field]: value });
  };

  const uploadAsset = async (kind, file) => {
    if (!file || disabled) return;
    setBusy((b) => ({ ...b, [kind]: true }));
    try {
      const resp = kind === 'logo'
        ? await EmpresasAPI.uploadLogo(empresaId, file)
        : await EmpresasAPI.uploadCarimbo(empresaId, file);
      if (resp.data) onEmpresaUpdated?.(resp.data);
    } catch (err) {
      alert(friendlyLaudosError(err));
    } finally {
      setBusy((b) => ({ ...b, [kind]: false }));
      if (kind === 'logo' && logoRef.current) logoRef.current.value = '';
      if (kind === 'carimbo' && carRef.current) carRef.current.value = '';
    }
  };

  const removeAsset = async (kind) => {
    if (disabled) return;
    const msg = kind === 'logo' ? 'Remover o logotipo do servidor?' : 'Remover o carimbo do servidor?';
    if (!confirm(msg)) return;
    setBusy((b) => ({ ...b, [kind]: true }));
    try {
      const resp = kind === 'logo'
        ? await EmpresasAPI.deleteLogo(empresaId)
        : await EmpresasAPI.deleteCarimbo(empresaId);
      if (resp.data) onEmpresaUpdated?.(resp.data);
    } catch (err) {
      alert(friendlyLaudosError(err));
    } finally {
      setBusy((b) => ({ ...b, [kind]: false }));
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
          Empresa emissora
        </h3>
        <SaveIndicator status={saveStatus} savedAt={savedAt} />
      </div>

      {/* Logo e carimbo */}
      <div style={{
        display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16, marginBottom: 16,
        padding: 12, background: '#f9fafb', borderRadius: 8, border: '1px solid #e5e7eb',
      }}>
        <AssetBlock
          title="Logotipo (PDF)"
          path={e.logo_path}
          previewUrl={previewUrl(e.logo_path)}
          disabled={disabled}
          busy={busy.logo}
          inputRef={logoRef}
          onPick={(files) => uploadAsset('logo', files?.[0])}
          onRemove={() => removeAsset('logo')}
        />
        <AssetBlock
          title="Carimbo (PDF)"
          path={e.carimbo_path}
          previewUrl={previewUrl(e.carimbo_path)}
          disabled={disabled}
          busy={busy.carimbo}
          inputRef={carRef}
          onPick={(files) => uploadAsset('carimbo', files?.[0])}
          onRemove={() => removeAsset('carimbo')}
        />
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12 }}>
        <Field label="Razão social">
          <input
            type="text"
            value={e.razao_social || ''}
            disabled={disabled}
            onChange={(ev) => patchField('razao_social', ev.target.value)}
            style={inputStyle}
          />
        </Field>
        <Field label="CNPJ">
          <input
            type="text"
            value={e.cnpj || ''}
            disabled={disabled}
            onChange={(ev) => patchField('cnpj', maskCNPJ(ev.target.value))}
            style={inputStyle}
          />
        </Field>
        <Field label="E-mail">
          <input
            type="email"
            value={e.email || ''}
            disabled={disabled}
            onChange={(ev) => patchField('email', ev.target.value)}
            style={inputStyle}
          />
        </Field>
        <Field label="Telefone">
          <input
            type="text"
            value={e.telefone || ''}
            disabled={disabled}
            onChange={(ev) => patchField('telefone', maskPhone(ev.target.value))}
            style={inputStyle}
          />
        </Field>
        <Field label="Telefone 2">
          <input
            type="text"
            value={e.telefone2 || ''}
            disabled={disabled}
            onChange={(ev) => patchField('telefone2', maskPhone(ev.target.value))}
            style={inputStyle}
          />
        </Field>
        <Field label="CEP">
          <input
            type="text"
            value={e.cep || ''}
            disabled={disabled}
            onChange={(ev) => patchField('cep', maskCEP(ev.target.value))}
            style={inputStyle}
          />
        </Field>
        <Field label="Site" style={{ gridColumn: '1 / -1' }}>
          <input
            type="text"
            value={e.site || ''}
            disabled={disabled}
            onChange={(ev) => patchField('site', ev.target.value)}
            style={inputStyle}
          />
        </Field>
        <Field label="URL pública de validação (QR)" style={{ gridColumn: '1 / -1' }}>
          <input
            type="text"
            value={e.public_validation_url || ''}
            disabled={disabled}
            onChange={(ev) => patchField('public_validation_url', ev.target.value)}
            style={inputStyle}
            placeholder="https://..."
          />
        </Field>
        <Field label="Endereço" style={{ gridColumn: '1 / -1' }}>
          <textarea
            value={e.endereco || ''}
            disabled={disabled}
            onChange={(ev) => patchField('endereco', ev.target.value)}
            rows={2}
            style={{ ...inputStyle, resize: 'vertical' }}
          />
        </Field>
      </div>
      <p style={{ margin: '12px 0 0', fontSize: 11, color: '#6b7280' }}>
        Apenas JPEG, PNG ou WebP até 3 MB. São usados no cabeçalho (logo) e na zona de assinatura (carimbo) do PDF.
      </p>
    </div>
  );
}

function AssetBlock({
  title, path, previewUrl, disabled, busy, inputRef, onPick, onRemove,
}) {
  return (
    <div>
      <div style={{ fontSize: 12, color: '#6b7280', marginBottom: 6, fontWeight: 600 }}>{title}</div>
      <div style={{
        border: '1px solid #d1d5db', borderRadius: 8, background: '#fff',
        minHeight: 100, display: 'flex', alignItems: 'center', justifyContent: 'center',
        marginBottom: 8, overflow: 'hidden',
      }}
      >
        {path && previewUrl ? (
          <img src={previewUrl} alt="" style={{ maxHeight: 96, maxWidth: '100%', objectFit: 'contain' }} />
        ) : (
          <span style={{ fontSize: 12, color: '#9ca3af' }}>Sem imagem</span>
        )}
      </div>
      <input
        ref={inputRef}
        type="file"
        accept="image/jpeg,image/png,image/webp"
        style={{ display: 'none' }}
        onChange={(ev) => onPick(ev.target.files)}
      />
      <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
        <button
          type="button"
          disabled={disabled || busy}
          onClick={() => inputRef.current?.click()}
          style={btnPrimary}
        >
          {busy ? 'A enviar…' : 'Subir'}
        </button>
        <button
          type="button"
          disabled={disabled || busy || !path}
          onClick={onRemove}
          style={btnGhost}
        >
          Remover
        </button>
      </div>
    </div>
  );
}

function Field({ label, children, style }) {
  return (
    <div style={style}>
      <label style={{ display: 'block', fontSize: 12, color: '#6b7280', marginBottom: 4 }}>{label}</label>
      {children}
    </div>
  );
}

const inputStyle = {
  width: '100%', padding: '7px 10px',
  border: '1px solid #d1d5db', borderRadius: 4,
  fontSize: 13, fontFamily: 'inherit',
};
const btnPrimary = {
  padding: '6px 12px', fontSize: 12, fontWeight: 500,
  background: '#3b82f6', color: 'white', border: 'none', borderRadius: 6, cursor: 'pointer',
};
const btnGhost = {
  padding: '6px 12px', fontSize: 12, fontWeight: 500,
  background: 'white', color: '#374151', border: '1px solid #d1d5db', borderRadius: 6, cursor: 'pointer',
};
