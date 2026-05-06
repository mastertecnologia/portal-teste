import React from 'react';

/**
 * Indicador visual de status de salvamento.
 *
 * Props:
 *   status: 'idle' | 'pending' | 'saving' | 'saved' | 'error'
 *   savedAt: Date | null
 */
export default function SaveIndicator({ status, savedAt }) {
  const config = {
    idle:    { color: '#6b7280', bg: '#e5e7eb', dot: '#6b7280', text: 'Aguardando' },
    pending: { color: '#a16207', bg: '#fef3c7', dot: '#f59e0b', text: 'Alterações pendentes', pulse: true },
    saving:  { color: '#a16207', bg: '#fef3c7', dot: '#f59e0b', text: 'Salvando...', pulse: true },
    saved:   { color: '#166534', bg: '#dcfce7', dot: '#16a34a', text: 'Tudo salvo' },
    error:   { color: '#991b1b', bg: '#fee2e2', dot: '#dc2626', text: 'Erro ao salvar' },
  };

  const c = config[status] || config.idle;

  const time = savedAt
    ? new Date(savedAt).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
    : null;

  return (
    <div style={{
      display: 'inline-flex', alignItems: 'center', gap: 8,
      padding: '4px 12px', borderRadius: 999,
      background: c.bg, color: c.color,
      fontSize: 13, fontWeight: 500,
    }}>
      <span style={{
        width: 8, height: 8, borderRadius: '50%',
        background: c.dot,
        animation: c.pulse ? 'savePulse 1.2s ease-in-out infinite' : 'none',
      }} />
      <span>{c.text}</span>
      {status === 'saved' && time && (
        <span style={{ opacity: 0.7, fontSize: 11 }}>às {time}</span>
      )}
      <style>{`
        @keyframes savePulse {
          0%, 100% { opacity: 1; transform: scale(1); }
          50% { opacity: 0.4; transform: scale(1.3); }
        }
      `}</style>
    </div>
  );
}
