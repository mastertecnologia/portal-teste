import { useCallback, useMemo } from 'react';
import TicketTimer from './TicketTimer.jsx';
import { patchTicketAssignment, patchTicketPriority, patchTicketStatus } from '../lib/api.js';

const PRIORIDADE_OPTS = [
  { code: 'baixa', label: 'Baixo' },
  { code: 'media', label: 'Médio' },
  { code: 'alta', label: 'Alto' },
  { code: 'critica', label: 'Crítico' },
];

function selectClass(disabled) {
  return [
    'max-w-full rounded-md border px-1.5 py-1 text-[11px] outline-none transition',
    'border-[var(--pgm-border)] bg-[var(--pgm-bg-raised)] text-[var(--pgm-text)]',
    'focus:border-[var(--pgm-primary)]',
    disabled ? 'cursor-not-allowed opacity-60' : 'cursor-pointer',
  ].join(' ');
}

function rowSnapshot(ticket) {
  return { ...ticket };
}

/**
 * Células editáveis (técnico, fila, status, prioridade) + timer do ticket.
 * PATCH assignment: apenas { tecnico_id, fila_id } — fila só com técnico (regra da API).
 */
export default function TicketsServicedeskInlineRow({
  ticket,
  wfEnabled,
  queuesRelacional,
  queues,
  workflowFilas,
  tecnicos,
  ticketStatus,
  situacaoExecCode,
  onMergeTicket,
  patchBusyId,
  setPatchBusyId,
  onPatchError,
}) {
  const busy = Number(patchBusyId) === Number(ticket.id);
  const tid = Number(ticket.idtecnico_responsavel) || 0;
  const canChangeQueue = tid > 0;

  const setBusy = useCallback(
    (v) => {
      setPatchBusyId(v ? Number(ticket.id) : null);
    },
    [setPatchBusyId, ticket.id],
  );

  const fail = useCallback(
    (msg) => {
      onPatchError?.(Number(ticket.id), msg || 'Falha ao salvar');
    },
    [onPatchError, ticket.id],
  );

  const statusOptions = useMemo(() => {
    const p = ticketStatus?.pendente;
    const e = ticketStatus?.emandamento;
    const r = ticketStatus?.resolvido;
    const f = ticketStatus?.fechado;
    const opts = [];
    if (p != null) opts.push({ value: 'Pendente', code: p });
    if (e != null) opts.push({ value: 'Em execução', code: e });
    if (r != null) opts.push({ value: 'Resolvido', code: r });
    if (f != null) opts.push({ value: 'Fechado', code: f });
    return opts;
  }, [ticketStatus]);

  const currentStatusCode = Number(ticket.situacao);
  const statusValue = useMemo(() => {
    const hit = statusOptions.find((o) => Number(o.code) === currentStatusCode);
    return hit ? hit.value : String(ticket.situacaoLabel || '');
  }, [statusOptions, currentStatusCode, ticket.situacaoLabel]);

  const onStatus = async (e) => {
    const label = e.target.value;
    const hit = statusOptions.find((o) => o.value === label);
    if (!hit) return;
    const snap = rowSnapshot(ticket);
    setBusy(true);
    const r = await patchTicketStatus(ticket.id, { status: label });
    setBusy(false);
    if (!r.ok) {
      onMergeTicket(ticket.id, snap);
      fail(r.error || r.message);
      return;
    }
    if (r.ticket) onMergeTicket(ticket.id, r.ticket);
  };

  const onPrioridade = async (e) => {
    const code = e.target.value;
    const hit = PRIORIDADE_OPTS.find((o) => o.code === code);
    if (!hit) return;
    const snap = rowSnapshot(ticket);
    setBusy(true);
    const r = await patchTicketPriority(ticket.id, { prioridade: hit.label });
    setBusy(false);
    if (!r.ok) {
      onMergeTicket(ticket.id, snap);
      fail(r.error || r.message);
      return;
    }
    if (r.ticket) onMergeTicket(ticket.id, r.ticket);
  };

  const onTecnico = async (e) => {
    const v = e.target.value;
    const newTid = v === '' ? 0 : Number(v);
    if (newTid <= 0) return;
    const snap = rowSnapshot(ticket);
    setBusy(true);
    const body = {
      tecnico_id: newTid,
      fila_id: ticket.filaQueueId > 0 ? Number(ticket.filaQueueId) : null,
    };
    const r = await patchTicketAssignment(ticket.id, body);
    setBusy(false);
    if (!r.ok) {
      onMergeTicket(ticket.id, snap);
      fail(r.error || r.message);
      return;
    }
    if (r.ticket) onMergeTicket(ticket.id, r.ticket);
  };

  const onFila = async (e) => {
    const qid = Number(e.target.value);
    if (!qid || !canChangeQueue) return;
    const snap = rowSnapshot(ticket);
    setBusy(true);
    const r = await patchTicketAssignment(ticket.id, {
      tecnico_id: tid,
      fila_id: qid,
    });
    setBusy(false);
    if (!r.ok) {
      onMergeTicket(ticket.id, snap);
      fail(r.error || r.message);
      return;
    }
    if (r.ticket) onMergeTicket(ticket.id, r.ticket);
  };

  const priorCode = (() => {
    if (ticket.prioridadeCode) {
      const p = String(ticket.prioridadeCode).toLowerCase();
      if (p === 'critico') return 'critica';

      return p;
    }
    const s = String(ticket.severidadeCode || 'media').toLowerCase();
    if (s === 'urgente' || s === 'critico') return 'critica';
    if (['baixa', 'media', 'alta', 'critica'].includes(s)) return s;

    return 'media';
  })();

  const queueOptions = useMemo(() => {
    if (queuesRelacional && Array.isArray(queues)) {
      return queues.map((q) => ({ id: q.id, label: q.name || q.codigo || `#${q.id}` }));
    }
    if (Array.isArray(workflowFilas)) {
      return workflowFilas.map((f) => ({
        id: f.code,
        label: f.label || f.code,
        legacyCode: f.code,
      }));
    }
    return [];
  }, [queuesRelacional, queues, workflowFilas]);

  return (
    <>
      <td className="max-w-[7.5rem] px-2 py-2">
        <select
          className={selectClass(busy)}
          value={priorCode}
          onChange={onPrioridade}
          disabled={busy}
          aria-label={`Prioridade ticket ${ticket.id}`}
        >
          {PRIORIDADE_OPTS.map((o) => (
            <option key={o.code} value={o.code}>
              {o.label}
            </option>
          ))}
        </select>
      </td>
      <td className="whitespace-nowrap px-2 py-2">
        <select
          className={selectClass(busy)}
          value={statusValue}
          onChange={onStatus}
          disabled={busy}
          aria-label={`Status ticket ${ticket.id}`}
        >
          {statusOptions.map((o) => (
            <option key={String(o.code)} value={o.value}>
              {o.value}
            </option>
          ))}
        </select>
      </td>
      {wfEnabled ? (
        <>
          <td className="max-w-[9rem] px-2 py-2">
            {queuesRelacional ? (
              <select
                className={selectClass(busy || !canChangeQueue)}
                value={ticket.filaQueueId ? String(ticket.filaQueueId) : ''}
                onChange={onFila}
                disabled={busy || !canChangeQueue}
                title={!canChangeQueue ? 'Atribua um técnico para alterar a fila (PATCH assignment).' : undefined}
                aria-label={`Fila ticket ${ticket.id}`}
              >
                <option value="">{canChangeQueue ? 'Fila…' : 'Sem técnico'}</option>
                {queueOptions.map((q) => (
                  <option key={String(q.id)} value={String(q.id)}>
                    {q.label}
                  </option>
                ))}
              </select>
            ) : (
              <span className="line-clamp-2 text-[0.75rem] text-[var(--pgm-text-muted)]">{ticket.filaLabel || '—'}</span>
            )}
          </td>
          <td className="whitespace-nowrap px-2 py-2 text-[0.75rem] text-[var(--pgm-text-muted)]">
            {ticket.supportLevelLabel ? ticket.supportLevelLabel : ticket.nivelAtendimento != null ? `N${ticket.nivelAtendimento}` : '—'}
          </td>
        </>
      ) : null}
      <td className="max-w-[8rem] px-2 py-2">
        <select
          className={selectClass(busy)}
          value={ticket.idtecnico_responsavel ? String(ticket.idtecnico_responsavel) : ''}
          onChange={onTecnico}
          disabled={busy}
          aria-label={`Técnico ticket ${ticket.id}`}
        >
          <option value="">—</option>
          {tecnicos.map((t) => (
            <option key={t.id} value={String(t.id)}>
              {t.nivel_label ? `${t.name} — ${t.nivel_label}` : t.name}
            </option>
          ))}
        </select>
      </td>
      <td className="whitespace-nowrap px-2 py-2 text-right">
        <TicketTimer ticket={ticket} situacaoExecCode={situacaoExecCode} />
      </td>
    </>
  );
}
