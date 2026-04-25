import { stripHtml } from '../lib/text';

function row(label, value) {
  if (value === null || value === undefined || String(value).trim() === '') {
    return null;
  }
  return (
    <div className="flex flex-col gap-0.5 border-b border-[var(--pgm-border-subtle)] py-2 last:border-0">
      <span className="text-[0.65rem] font-semibold uppercase tracking-wide text-[var(--pgm-text-muted)]">{label}</span>
      <span className="text-[0.8rem] text-[var(--pgm-text)]">{value}</span>
    </div>
  );
}

/** Coluna esquerda: timer + resumo operacional (mockup Service Desk). */
export function TicketResumoPanel({ ticket }) {
  if (!ticket) return null;
  const ch = ticket.contractHours;
  const pct = ch && ch.percentUsed != null ? Number(ch.percentUsed) : null;
  const min = ticket.horasTecnicas?.minutosRegistrados;

  return (
    <div className="overflow-hidden rounded-xl border border-[var(--pgm-border-subtle)] bg-[var(--pgm-bg-surface)] shadow-[var(--pgm-shadow-md)]">
      <div className="border-b border-[var(--pgm-border-subtle)] bg-[var(--pgm-bg-elevated)] px-3 py-2.5">
        <h2 className="text-[0.8rem] font-semibold text-[var(--pgm-text)]">Resumo</h2>
      </div>
      <div className="space-y-0 px-3 pb-2 pt-1 text-sm">
        {row('Prioridade', ticket.prioridade)}
        {row('Responsável (solicitante)', ticket.responsavel)}
        {ch && ch.label ? (
          <div className="flex flex-col gap-0.5 border-b border-[var(--pgm-border-subtle)] py-2 last:border-0">
            <span className="text-[0.65rem] font-semibold uppercase tracking-wide text-[var(--pgm-text-muted)]">Contrato (horas)</span>
            <span className="text-[0.8rem] text-[var(--pgm-text)]">
              {ch.label}
              {pct != null && !Number.isNaN(pct) ? ` · ${pct.toFixed(0)}% usado` : ''}
            </span>
          </div>
        ) : null}
        {min != null && min > 0 ? (
          <div className="flex flex-col gap-0.5 py-2">
            <span className="text-[0.65rem] font-semibold uppercase tracking-wide text-[var(--pgm-text-muted)]">Tempo neste ticket</span>
            <span className="font-mono text-[0.85rem] text-[var(--pgm-text)]">{min} min registrados</span>
          </div>
        ) : null}
      </div>
    </div>
  );
}

/** Bloco “Informações do ticket” (coluna direita). */
export default function TicketInfoPanel({ ticket }) {
  if (!ticket) return null;
  const assunto = stripHtml(ticket.assunto);
  const aberto = ticket.abertoEm || ticket.atualizado;
  const atual = ticket.atualizadoEm || ticket.atualizado;

  return (
    <div className="overflow-hidden rounded-xl border border-[var(--pgm-border-subtle)] bg-[var(--pgm-bg-surface)] shadow-[var(--pgm-shadow-md)]">
      <div className="border-b border-[var(--pgm-border-subtle)] bg-[var(--pgm-bg-elevated)] px-3 py-2.5">
        <h2 className="text-[0.8rem] font-semibold text-[var(--pgm-text)]">Informações do ticket</h2>
      </div>
      <div className="px-3 pb-2 pt-0">
        {row('Cliente', stripHtml(ticket.cliente))}
        {row('Documento', ticket.cnpj)}
        {row('E-mail', ticket.email)}
        {row('Categoria / assunto', assunto)}
        {row('Prioridade', ticket.prioridade)}
        {row('Estado', ticket.status)}
        {row('Aberto em', aberto)}
        {row('Última atualização', atual)}
      </div>
    </div>
  );
}
