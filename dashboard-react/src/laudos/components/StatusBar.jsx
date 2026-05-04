import React from 'react';

const STATUSES = [
  { key: 'rascunho',   label: 'Rascunho',   color: '#6b7280', bg: '#f3f4f6' },
  { key: 'em_analise', label: 'Em análise', color: '#a16207', bg: '#fef3c7' },
  { key: 'aprovado',   label: 'Aprovado',   color: '#166534', bg: '#dcfce7' },
  { key: 'concluido',  label: 'Concluído',  color: '#1e40af', bg: '#dbeafe' },
  { key: 'enviado',    label: 'Enviado',    color: '#5b21b6', bg: '#ede9fe' },
];

/**
 * Pílulas de status com clique para mudar.
 *
 * Props:
 *   value: string atual
 *   onChange: (newStatus) => void
 *   disabled: bool
 */
export default function StatusBar({ value, onChange, disabled }) {
  return (
    <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap', alignItems: 'center' }}>
      <span style={{ fontSize: 13, color: '#6b7280', marginRight: 4 }}>Status:</span>
      {STATUSES.map((s) => {
        const isActive = s.key === value;
        return (
          <button
            key={s.key}
            type="button"
            disabled={disabled}
            onClick={() => onChange?.(s.key)}
            style={{
              padding: '6px 14px', borderRadius: 999,
              border: isActive ? `2px solid ${s.color}` : '1px solid #e5e7eb',
              background: isActive ? s.bg : 'white',
              color: isActive ? s.color : '#6b7280',
              fontSize: 12, fontWeight: isActive ? 600 : 500,
              cursor: disabled ? 'not-allowed' : 'pointer',
              opacity: disabled ? 0.5 : 1,
              transition: 'all 0.15s ease',
            }}
          >
            {s.label}
          </button>
        );
      })}
    </div>
  );
}

/**
 * Pequena pílula só para listagem (somente leitura).
 */
export function StatusBadge({ status }) {
  const s = STATUSES.find((x) => x.key === status) || STATUSES[0];
  return (
    <span style={{
      padding: '2px 10px', borderRadius: 999,
      background: s.bg, color: s.color,
      fontSize: 11, fontWeight: 600,
      whiteSpace: 'nowrap',
    }}>
      {s.label}
    </span>
  );
}
