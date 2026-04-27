import { stripHtml } from '../lib/text';
import { badgeClass, priorityType, statusType } from '../lib/ticketUi';

/** Rótulo legível para categoria/assunto (evita linha vazia, «0» ou traço só). */
function categoriaAssuntoExibicao(raw) {
  const t = stripHtml(raw).trim();
  if (t === '' || t === '—' || t === '0') return 'Não informado';
  return t;
}

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

function rowAlways(label, value) {
  const v = value === null || value === undefined || String(value).trim() === '' ? '—' : value;
  return (
    <div className="flex flex-col gap-0.5 border-b border-[var(--pgm-border-subtle)] py-2 last:border-0">
      <span className="text-[0.65rem] font-semibold uppercase tracking-wide text-[var(--pgm-text-muted)]">{label}</span>
      <span className="text-[0.8rem] text-[var(--pgm-text)]">{v}</span>
    </div>
  );
}

/** Estado com as mesmas cores do badge do título do ticket (`statusType` + `badgeClass`). */
function rowEstado(label, status, { embedded, servicedesk }) {
  const raw = status === null || status === undefined || String(status).trim() === '' ? '' : stripHtml(String(status)).trim();
  if (!raw) {
    return rowAlways(label, '—');
  }
  return (
    <div className="flex flex-col gap-0.5 border-b border-[var(--pgm-border-subtle)] py-2 last:border-0">
      <span className="text-[0.65rem] font-semibold uppercase tracking-wide text-[var(--pgm-text-muted)]">{label}</span>
      <span>
        <span
          className={`inline-flex rounded-full font-semibold ${embedded ? 'px-2 py-0.5 text-[10px]' : 'px-2.5 py-0.5 text-[11px]'} ${badgeClass(
            statusType(raw),
            embedded,
            servicedesk,
          )}`}
        >
          {raw}
        </span>
      </span>
    </div>
  );
}

/** Prioridade com as mesmas cores de badge do Service Desk (`ticketUi`). */
function rowPrioridade(label, prioridade) {
  const p = prioridade === null || prioridade === undefined || String(prioridade).trim() === '' ? '—' : String(prioridade).trim();
  if (p === '—') {
    return rowAlways(label, '—');
  }
  return (
    <div className="flex flex-col gap-0.5 border-b border-[var(--pgm-border-subtle)] py-2 last:border-0">
      <span className="text-[0.65rem] font-semibold uppercase tracking-wide text-[var(--pgm-text-muted)]">{label}</span>
      <span>
        <span
          className={`inline-flex rounded-full border px-2.5 py-0.5 text-[0.75rem] font-semibold ${badgeClass(
            priorityType(p),
            false,
            true,
          )}`}
        >
          {p}
        </span>
      </span>
    </div>
  );
}

function fieldBlock(label, value, { valueClassName = 'text-[0.8rem] text-[var(--pgm-text)]' } = {}) {
  const v = value === null || value === undefined || String(value).trim() === '' ? '—' : value;
  return (
    <div className="flex flex-col gap-0.5 border-b border-[var(--pgm-border-subtle)] py-2 last:border-0">
      <span className="text-[0.65rem] font-semibold uppercase tracking-wide text-[var(--pgm-text-muted)]">{label}</span>
      <span className={valueClassName}>{v}</span>
    </div>
  );
}

/** Anel de progresso circular (percentual utilizado do contrato). */
function ContractProgressRing({ percent }) {
  const p = Math.max(0, Math.min(100, Number(percent) || 0));
  const r = 44;
  const c = 2 * Math.PI * r;
  const off = c * (1 - p / 100);
  return (
    <div className="relative mx-auto flex h-[7.5rem] w-[7.5rem] flex-shrink-0 items-center justify-center">
      <svg className="absolute inset-0 h-full w-full -rotate-90" viewBox="0 0 120 120" aria-hidden>
        <circle cx="60" cy="60" r={r} fill="none" className="stroke-[var(--pgm-border-subtle)]" strokeWidth="10" />
        <circle
          cx="60"
          cy="60"
          r={r}
          fill="none"
          className="stroke-emerald-500"
          strokeWidth="10"
          strokeLinecap="round"
          strokeDasharray={c}
          strokeDashoffset={off}
          style={{ transition: 'stroke-dashoffset 0.35s ease' }}
        />
      </svg>
      <span className="relative z-10 max-w-[5rem] text-center text-[0.75rem] font-semibold leading-tight text-[var(--pgm-text)]">
        {Math.round(p)}% Utilizado
      </span>
    </div>
  );
}

/** Coluna esquerda: resumo do contrato (dados de `contratos_horas` via API). */
export function TicketResumoPanel({ ticket }) {
  if (!ticket) return null;
  const ch = ticket.contractHours;
  const pct = ch && ch.percentUsed != null ? Number(ch.percentUsed) : null;
  const has = ch && ch.hasContract;
  const urlContrato = ticket.urls?.contratoHoras;

  return (
    <div className="overflow-hidden rounded-xl border border-[var(--pgm-border-subtle)] bg-[var(--pgm-bg-surface)] shadow-[var(--pgm-shadow-md)]">
      <div className="border-b border-[var(--pgm-border-subtle)] bg-[var(--pgm-bg-elevated)] px-3 py-2.5">
        <h2 className="text-[0.8rem] font-semibold text-[var(--pgm-text)]">Resumo do Contrato</h2>
      </div>
      <div className="px-3 pb-3 pt-2">
        <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:gap-4">
          <div className="min-w-0 flex-1 space-y-0">
            {fieldBlock('Contrato', has ? ch.contractCode : '—')}
            {fieldBlock('Plano', has ? ch.plano : '—')}
            {fieldBlock('Vigência', has ? ch.vigenciaTexto : '—')}
            {fieldBlock('Horas contratadas', has ? ch.horasContratadasHms : '—')}
            {fieldBlock('Horas utilizadas', has ? ch.horasUtilizadasHms : '—', {
              valueClassName: 'text-[0.8rem] font-medium text-emerald-600',
            })}
            {fieldBlock('Saldo de horas', has ? ch.saldoHorasHms : '—', {
              valueClassName: 'text-[0.8rem] font-medium text-emerald-600',
            })}
          </div>
          <div className="mx-auto flex flex-shrink-0 items-center justify-center lg:mx-0">
            {has ? (
              <ContractProgressRing percent={pct != null && !Number.isNaN(pct) ? pct : 0} />
            ) : (
              <div className="flex h-[7.5rem] w-[7.5rem] items-center justify-center rounded-full border border-dashed border-[var(--pgm-border-subtle)] text-[0.85rem] text-[var(--pgm-text-muted)]">
                —
              </div>
            )}
          </div>
        </div>

        {ch?.alertaAviso ? (
          <div className="mt-3 flex gap-2 rounded-lg border border-amber-200/40 bg-amber-500/10 px-3 py-2.5 text-[0.78rem] text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/15 dark:text-amber-100">
            <span className="text-lg leading-none" aria-hidden>
              !
            </span>
            <p className="leading-snug">{ch.alertaAviso}</p>
          </div>
        ) : null}
        {ch?.previsaoEsgotamento ? (
          <div className="mt-2 flex gap-2 rounded-lg border border-red-200/50 bg-red-500/10 px-3 py-2.5 text-[0.78rem] text-red-900 dark:border-red-500/35 dark:bg-red-500/12 dark:text-red-100">
            <span className="text-lg leading-none" aria-hidden>
              !
            </span>
            <p className="leading-snug">{ch.previsaoEsgotamento}</p>
          </div>
        ) : null}

        {urlContrato ? (
          <a
            href={urlContrato}
            className="mt-4 flex w-full items-center justify-center rounded-lg border border-[var(--pgm-border-subtle)] bg-[var(--pgm-bg-elevated)] px-3 py-2.5 text-[0.8rem] font-medium text-[var(--pgm-text)] transition hover:bg-[var(--pgm-bg-surface)]"
          >
            Ver contrato completo
          </a>
        ) : (
          <p className="mt-3 text-center text-[0.72rem] text-[var(--pgm-text-muted)]">Sem contrato de horas vinculado ao cliente.</p>
        )}
      </div>
    </div>
  );
}

/** Bloco “Informações do ticket” (coluna direita). */
export default function TicketInfoPanel({ ticket, embedded = false, servicedesk = false }) {
  if (!ticket) return null;
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
        {rowAlways('Responsável solicitante', ticket.responsavel)}
        {rowAlways('Categoria / assunto', categoriaAssuntoExibicao(ticket.assunto))}
        {rowPrioridade('Prioridade', ticket.prioridade)}
        {rowEstado('Estado', ticket.status, { embedded, servicedesk })}
        {row('Aberto em', aberto)}
        {row('Última atualização', atual)}
      </div>
    </div>
  );
}
