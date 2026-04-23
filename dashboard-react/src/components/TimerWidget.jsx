import { useState } from 'react';
import AuditModal from './AuditModal.jsx';

/**
 * Widget flutuante: espelha o relógio do painel (sem setInterval próprio) e abre a auditoria.
 */
export default function TimerWidget({ ticketId, displayHms }) {
  const [open, setOpen] = useState(false);
  if (!ticketId) {
    return null;
  }
  return (
    <>
      <div
        className="pointer-events-auto fixed bottom-5 right-5 z-[10000] w-[min(200px,92vw)] select-none rounded-xl border border-l-4 border-l-[#10b981] border-[var(--pgm-border-subtle)] bg-[#1e293b] p-3.5 text-white shadow-lg"
        style={{ boxShadow: '0 12px 30px rgba(0,0,0,0.4)' }}
      >
        <div className="text-[10px] font-bold uppercase tracking-wider text-slate-400">Ticket #{ticketId}</div>
        <div className="mt-1 font-mono text-[1.6rem] leading-tight text-[#10b981]">{displayHms || '00:00:00'}</div>
        <div className="mt-2 flex items-center justify-center gap-2">
          <button
            type="button"
            onClick={() => setOpen(true)}
            className="rounded-lg bg-[#475569] px-3 py-1.5 text-sm text-white hover:bg-slate-600"
            title="Registar auditoria de tempo"
            aria-label="Abrir auditoria de tempo"
          >
            Audit
          </button>
        </div>
      </div>
      {open && (
        <AuditModal
          ticketId={ticketId}
          currentTimeHms={displayHms && displayHms.length === 8 ? displayHms : '00:00:00'}
          onClose={() => setOpen(false)}
        />
      )}
    </>
  );
}
