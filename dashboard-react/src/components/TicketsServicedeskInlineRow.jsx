import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import TicketTimer from './TicketTimer.jsx';
import WorkflowTimeline from './WorkflowTimeline.jsx';
import { fetchQueuesForUser, patchTicketAssignment, patchTicketPriority, patchTicketStatus } from '../lib/api.js';
import {
  badgeClass,
  servicedeskStatusTypeFromTicket,
  ticketHasValidQueueForStart,
  ticketHasValidTecnico,
  workflowTransitionPatchStatusLabel,
  workflowTransitionTargetsExecucao,
} from '../lib/ticketUi';

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
    'focus:border-[var(--pgm-primary)] focus:shadow-[0_0_0_2px_var(--pgm-bg-surface),0_0_0_4px_rgba(29,158,117,0.22)]',
    disabled ? 'cursor-not-allowed opacity-60' : 'cursor-pointer',
  ].join(' ');
}

/** Tarja à esquerda do bloco de status (PGM Service Desk v2). */
function statusAccentBorderClass(type) {
  const m = {
    open: 'border-l-[#64748b]',
    pendingTicket: 'border-l-[#F39C12]',
    pendingTech: 'border-l-[#F39C12]',
    progress: 'border-l-[#2DAAE1]',
    waiting: 'border-l-[#2DAAE1]',
    resolved: 'border-l-[#27AE60]',
    closed: 'border-l-[#334155]',
    cancelled: 'border-l-[#dc330f]',
    escalated: 'border-l-[#dc330f]',
    critical: 'border-l-[#dc330f]',
    high: 'border-l-[#F39C12]',
    medium: 'border-l-[#F39C12]',
    warning: 'border-l-[#F39C12]',
    success: 'border-l-[#27AE60]',
    low: 'border-l-[#1d9e75]',
  };
  return m[type] || 'border-l-[#1d9e75]';
}

function rowSnapshot(ticket) {
  return { ...ticket };
}

function workflowStateForUi(state) {
  if (!state) return null;
  const id = Number(state.id);
  if (!Number.isFinite(id) || id <= 0) return null;
  const code = state.codigo ?? state.code ?? state.slug ?? '';
  const label = String(state.label || '').trim();
  const normalized = { ...state, codigo: code };
  return {
    value: String(id),
    label: label || workflowTransitionPatchStatusLabel(normalized),
    workflowStateId: id,
    statusLabel: workflowTransitionPatchStatusLabel(normalized),
  };
}

/**
 * Células editáveis (técnico → fila, status, prioridade) + timer.
 * Modo filas relacionais: lista de filas vem da API por técnico (queues_users); fila só após escolher técnico.
 */
export default function TicketsServicedeskInlineRow({
  ticket,
  wfEnabled,
  queuesRelacional,
  workflowFilas,
  tecnicos,
  ticketStatus,
  situacaoExecCode,
  effectiveStatus,
  onMergeTicket,
  patchBusyId,
  setPatchBusyId,
  statusInteractionLocked = false,
  onPatchError,
}) {
  const busy = Number(patchBusyId) === Number(ticket.id) || Boolean(statusInteractionLocked);
  const tid = (() => {
    const raw =
      ticket?.idtecnico_responsavel ??
      ticket?.tecnico_id ??
      ticket?.iduser ??
      ticket?.tecnico?.id ??
      ticket?.owner_id ??
      0;
    const id = Number(raw);
    return Number.isFinite(id) && id > 0 ? id : 0;
  })();

  /** Técnico escolhido na UI ainda não gravado (aguardando escolha de fila). */
  const [pendingTecnicoId, setPendingTecnicoId] = useState(null);
  const [permittedQueues, setPermittedQueues] = useState([]);
  const [queuesLoading, setQueuesLoading] = useState(false);
  const [queuesLoadError, setQueuesLoadError] = useState(null);
  const queuesFetchSeqRef = useRef(0);

  useEffect(() => {
    setPendingTecnicoId(null);
    setQueuesLoadError(null);
  }, [tid, ticket.filaQueueId, ticket.id]);

  useEffect(() => {
    if (!queuesRelacional) {
      setPermittedQueues([]);
      setQueuesLoading(false);
      setQueuesLoadError(null);
      return undefined;
    }
    const uid = pendingTecnicoId !== null ? pendingTecnicoId : tid;
    if (uid <= 0) {
      setPermittedQueues([]);
      setQueuesLoading(false);
      setQueuesLoadError(null);
      return undefined;
    }
    const ac = new AbortController();
    const seq = ++queuesFetchSeqRef.current;
    setQueuesLoading(true);
    setQueuesLoadError(null);
    setPermittedQueues([]);
    void fetchQueuesForUser(uid, { signal: ac.signal }).then((r) => {
      if (seq !== queuesFetchSeqRef.current) {
        return;
      }
      if (r.aborted) {
        if (seq === queuesFetchSeqRef.current) {
          setQueuesLoading(false);
        }
        return;
      }
      setQueuesLoading(false);
      if (r.ok && Array.isArray(r.queues)) {
        setPermittedQueues(r.queues);
        if (r.queues.length === 0) {
          setQueuesLoadError('Nenhuma fila permitida para este técnico. Cadastre o vínculo em Filas → técnicos.');
        } else {
          setQueuesLoadError(null);
        }
      } else {
        setPermittedQueues([]);
        setQueuesLoadError(r.message || r.error || 'Não foi possível carregar as filas do técnico.');
      }
    });
    return () => {
      ac.abort();
    };
  }, [queuesRelacional, ticket.id, tid, pendingTecnicoId]);

  const mandanteTecId = pendingTecnicoId !== null ? pendingTecnicoId : tid;

  const permittedIdSet = useMemo(() => {
    const s = new Set();
    for (const q of permittedQueues || []) {
      const id = Number(q.id);
      if (Number.isFinite(id) && id > 0) s.add(id);
    }
    return s;
  }, [permittedQueues]);

  const canOpenFilaSelectRelacional =
    queuesRelacional && mandanteTecId > 0 && !queuesLoading && permittedIdSet.size > 0;

  const filaSelectValueRelacional = (() => {
    if (pendingTecnicoId !== null) {
      return '';
    }
    const cur = Number(ticket.filaQueueId) || 0;
    if (cur > 0 && permittedIdSet.has(cur)) {
      return String(cur);
    }
    return '';
  })();

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

  const [patchingWorkflowStateId, setPatchingWorkflowStateId] = useState(null);

  const workflowOptions = useMemo(() => {
    const list = ticket?.workflow?.allowedTransitions;
    return Array.isArray(list) ? list : [];
  }, [ticket]);

  const allowedWorkflowStateIds = useMemo(() => {
    const s = new Set();
    for (const o of workflowOptions) {
      const n = Number(o.id);
      if (Number.isFinite(n) && n > 0) s.add(n);
    }
    return s;
  }, [workflowOptions]);

  const useWorkflowStatus =
    ticket?.workflow?.enabled === true &&
    ticket?.workflow?.current?.id != null;

  const statusOptions = useMemo(() => {
    if (useWorkflowStatus) {
      const current = workflowStateForUi(ticket?.workflow?.current);
      const currentId = current ? current.workflowStateId : 0;
      const options = current ? [current] : [];
      for (const o of workflowOptions) {
        const next = workflowStateForUi(o);
        if (!next || next.workflowStateId === currentId) continue;
        options.push(next);
      }
      return options;
    }
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
  }, [ticketStatus, useWorkflowStatus, workflowOptions, ticket]);

  const currentStatusCode = Number(ticket.situacao);
  const statusValue = useMemo(() => {
    if (useWorkflowStatus) {
      const currentId = Number(ticket?.workflow?.current?.id || 0);
      return currentId > 0 ? String(currentId) : '';
    }
    const hit = statusOptions.find((o) => Number(o.code) === currentStatusCode);
    return hit ? hit.value : String(ticket.situacaoLabel || '');
  }, [statusOptions, currentStatusCode, ticket.situacaoLabel, useWorkflowStatus, ticket]);

  const applyStatusPatch = useCallback(
    async (payload, patchSource = 'outro') => {
      const snap = rowSnapshot(ticket);
      const wfId =
        payload && payload.workflowStateId != null && !Number.isNaN(Number(payload.workflowStateId))
          ? Number(payload.workflowStateId)
          : null;
      setBusy(true);
      if (wfId != null && wfId > 0) setPatchingWorkflowStateId(wfId);
      try {
        if (useWorkflowStatus && wfId != null && wfId > 0) {
          const currentWfId = Number(ticket?.workflow?.current?.id || 0);
          if (currentWfId > 0 && currentWfId === wfId) {
            return;
          }
        }
        if (!useWorkflowStatus) {
          const nextStatus = String(payload?.status || '').trim().toLowerCase();
          const currentStatus = String(ticket.situacaoLabel || ticket.status || '').trim().toLowerCase();
          if (nextStatus !== '' && nextStatus === currentStatus) {
            return;
          }
        }
        let body;
        if (wfId != null && wfId > 0) {
          const fromPayload =
            payload?.statusLabel != null ? String(payload.statusLabel).trim() : '';
          const fallback = workflowOptions.find((x) => Number(x.id) === wfId);
          const statusStr =
            fromPayload ||
            (fallback ? workflowTransitionPatchStatusLabel(fallback) : '');
          body = { workflow_state_id: wfId, status: statusStr || '—' };
        } else {
          body = { status: payload.status };
        }
        const r = await patchTicketStatus(ticket.id, body, { source: patchSource });
        if (!r.ok) {
          onMergeTicket(ticket.id, snap);
          fail(r.message || r.error);
          return;
        }
        if (r.ticket) onMergeTicket(ticket.id, r.ticket);
      } finally {
        setBusy(false);
        setPatchingWorkflowStateId(null);
      }
    },
    [ticket, setBusy, onMergeTicket, fail, workflowOptions, useWorkflowStatus],
  );

  const onTimelineTransition = useCallback(
    async (transition) => {
      if (!useWorkflowStatus || busy || !transition) return;
      const sid = Number(transition.id);
      if (!Number.isFinite(sid) || sid <= 0 || !allowedWorkflowStateIds.has(sid)) return;
      if (workflowTransitionTargetsExecucao(transition) && queuesRelacional) {
        if (!ticketHasValidTecnico(ticket) || !ticketHasValidQueueForStart(ticket, true)) {
          fail(
            !ticketHasValidTecnico(ticket)
              ? 'Atribua um técnico ao ticket antes de colocá-lo em execução.'
              : 'Escolha a fila no dropdown “Fila…” antes de Em execução. Sem fila gravada no ticket o servidor recusa.',
          );
          return;
        }
      }
      await applyStatusPatch(
        {
          workflowStateId: sid,
          statusLabel: workflowTransitionPatchStatusLabel(transition),
        },
        'timeline',
      );
    },
    [
      useWorkflowStatus,
      busy,
      applyStatusPatch,
      allowedWorkflowStateIds,
      queuesRelacional,
      ticket,
      fail,
    ],
  );

  const onStatus = async (e) => {
    if (statusInteractionLocked) return;
    const selected = e.target.value;
    const hit = statusOptions.find((o) => o.value === selected);
    if (!hit) return;
    let movingToExec = false;
    if (useWorkflowStatus) {
      const tr = workflowOptions.find((x) => Number(x.id) === Number(hit.workflowStateId));
      movingToExec = tr ? workflowTransitionTargetsExecucao(tr) : false;
    } else {
      movingToExec =
        situacaoExecCode != null && Number(situacaoExecCode) === Number(hit.code);
    }
    if (movingToExec && queuesRelacional) {
      if (!ticketHasValidTecnico(ticket) || !ticketHasValidQueueForStart(ticket, true)) {
        fail(
          !ticketHasValidTecnico(ticket)
            ? 'Atribua um técnico ao ticket antes de colocá-lo em execução.'
            : 'Escolha a fila no dropdown “Fila…” antes de Em execução. Sem fila gravada no ticket o servidor recusa.',
        );
        return;
      }
    }
    if (useWorkflowStatus) {
      await applyStatusPatch(
        {
          workflowStateId: hit.workflowStateId,
          statusLabel: hit.statusLabel,
        },
        'dropdown',
      );
    } else {
      await applyStatusPatch({ status: selected }, 'dropdown');
    }
  };

  const onPrioridade = async (e) => {
    const code = e.target.value;
    const hit = PRIORIDADE_OPTS.find((o) => o.code === code);
    if (!hit) return;
    const snap = rowSnapshot(ticket);
    setBusy(true);
    try {
      const r = await patchTicketPriority(ticket.id, { prioridade: hit.label });
      if (!r.ok) {
        onMergeTicket(ticket.id, snap);
        fail(r.message || r.error);
        return;
      }
      if (r.ticket) onMergeTicket(ticket.id, r.ticket);
    } finally {
      setBusy(false);
    }
  };

  const onTecnico = async (e) => {
    const v = e.target.value;
    const newTid = v === '' ? 0 : Number(v);
    if (newTid <= 0) return;

    if (!queuesRelacional) {
      const snap = rowSnapshot(ticket);
      setBusy(true);
      try {
        const body = {
          tecnico_id: newTid,
          fila_id: ticket.filaQueueId > 0 ? Number(ticket.filaQueueId) : null,
        };
        const r = await patchTicketAssignment(ticket.id, body);
        if (!r.ok) {
          onMergeTicket(ticket.id, snap);
          fail(r.message || r.error);
          return;
        }
        if (r.ticket) onMergeTicket(ticket.id, r.ticket);
      } finally {
        setBusy(false);
      }
      return;
    }

    if (newTid === tid && pendingTecnicoId === null) return;

    if (newTid === tid) {
      setPendingTecnicoId(null);
      return;
    }

    setPendingTecnicoId(newTid);
  };

  const onFila = async (e) => {
    const qid = Number(e.target.value);
    if (!qid) return;
    if (queuesRelacional) {
      if (queuesLoading || permittedIdSet.size === 0) {
        fail('Aguarde o carregamento das filas ou selecione um técnico com filas permitidas.');
        return;
      }
    }
    const tecPatch = pendingTecnicoId !== null ? pendingTecnicoId : tid;
    if (tecPatch <= 0) return;
    if (queuesRelacional && !permittedIdSet.has(qid)) {
      fail('Fila não permitida para o técnico selecionado.');
      return;
    }
    const snap = rowSnapshot(ticket);
    setBusy(true);
    try {
      const r = await patchTicketAssignment(ticket.id, {
        tecnico_id: tecPatch,
        fila_id: qid,
      });
      if (!r.ok) {
        onMergeTicket(ticket.id, snap);
        fail(r.message || r.error);
        return;
      }
      setPendingTecnicoId(null);
      if (r.ticket) onMergeTicket(ticket.id, r.ticket);
    } finally {
      setBusy(false);
    }
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
    if (queuesRelacional) {
      return (permittedQueues || []).map((q) => ({
        id: q.id,
        label: q.name || q.codigo || `#${q.id}`,
      }));
    }
    if (Array.isArray(workflowFilas)) {
      return workflowFilas.map((f) => ({
        id: f.code,
        label: f.label || f.code,
        legacyCode: f.code,
      }));
    }
    return [];
  }, [queuesRelacional, permittedQueues, workflowFilas]);

  const filaSelectDisabledRelacional = busy || !canOpenFilaSelectRelacional;

  const filaSelectTitleRelacional = (() => {
    if (!queuesRelacional) return undefined;
    if (mandanteTecId <= 0) return 'Selecione o técnico primeiro; em seguida aparecem só as filas permitidas para ele.';
    if (queuesLoading) return 'Carregando filas…';
    if (queuesLoadError) return queuesLoadError;
    if (permittedIdSet.size === 0) return 'Nenhuma fila permitida para este técnico.';
    if (pendingTecnicoId !== null) return 'Escolha a fila para gravar técnico e fila no chamado.';
    return undefined;
  })();

  const tecnicoSelectValue = String((pendingTecnicoId !== null ? pendingTecnicoId : tid) || '');

  const statusBadgeText = useMemo(() => {
    if (effectiveStatus?.source === 'workflow' && effectiveStatus?.label) {
      return String(effectiveStatus.label).trim() || '—';
    }
    const raw =
      ticket?.workflow?.enabled === true && ticket?.workflow?.current?.label
        ? ticket.workflow.current.label
        : ticket.situacaoLabel || ticket.status || '—';
    return String(raw)
      .replace(/<[^>]*>/g, ' ')
      .replace(/\s+/g, ' ')
      .trim() || '—';
  }, [ticket, effectiveStatus]);

  const gridStatusType = useMemo(
    () => servicedeskStatusTypeFromTicket(ticket, effectiveStatus?.label || ticket.situacaoLabel),
    [ticket, effectiveStatus],
  );

  const statusSelectTitle = `Status do ticket ${ticket.id}: ${statusBadgeText}. Valor selecionado corresponde à próxima ação na fila.`;

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
      <td className="min-w-0 max-w-[11rem] whitespace-normal px-2 py-2 align-top">
        <div className="flex min-w-0 flex-col gap-1">
          <span
            className={`inline-flex max-w-full items-center gap-1 truncate rounded-full px-2 py-0.5 text-[9px] font-semibold leading-tight ${badgeClass(gridStatusType, false, true)}`}
            title={statusBadgeText}
          >
            {statusBadgeText}
          </span>
          <div
            className={`rounded-md border border-[var(--pgm-border-subtle)] border-l-[3px] bg-[var(--pgm-bg-raised)] pl-1 ${statusAccentBorderClass(gridStatusType)}`}
          >
            <select
              className={`${selectClass(busy)} w-full max-w-full border-0 bg-transparent pl-0.5`}
              value={statusValue}
              onChange={onStatus}
              disabled={busy}
              aria-label={`Alterar status do ticket ${ticket.id}`}
              title={statusSelectTitle}
            >
              {statusOptions.map((o) => (
                <option
                  key={String(o.workflowStateId || o.code || o.value)}
                  value={o.value}
                  title={String(o.label || o.value)}
                >
                  {o.label || o.value}
                </option>
              ))}
            </select>
          </div>
          <WorkflowTimeline
            ticket={ticket}
            patchBusy={busy}
            patchingWorkflowStateId={patchingWorkflowStateId}
            interactive={useWorkflowStatus}
            onTransitionClick={onTimelineTransition}
          />
        </div>
      </td>
      {wfEnabled ? (
        <>
          <td className="max-w-[8rem] px-2 py-2">
            <select
              className={selectClass(busy)}
              value={tecnicoSelectValue}
              onChange={onTecnico}
              disabled={busy}
              aria-label={`Técnico ticket ${ticket.id}`}
              title="Escolha o técnico primeiro; as filas listadas depois são só as que ele pode atender."
            >
              <option value="">—</option>
              {tecnicos.map((t) => (
                <option key={t.id} value={String(t.id)}>
                  {t.nivel_label ? `${t.name} — ${t.nivel_label}` : t.name}
                </option>
              ))}
            </select>
          </td>
          <td className="max-w-[9rem] px-2 py-2">
            {queuesRelacional ? (
              <select
                className={selectClass(filaSelectDisabledRelacional)}
                value={filaSelectValueRelacional}
                onChange={onFila}
                disabled={filaSelectDisabledRelacional}
                title={filaSelectTitleRelacional}
                aria-label={`Fila ticket ${ticket.id}`}
              >
                <option value="">
                  {mandanteTecId <= 0 ? 'Técnico…' : queuesLoading ? 'Carregando…' : 'Fila…'}
                </option>
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
      ) : (
        <td className="max-w-[8rem] px-2 py-2">
          <select
            className={selectClass(busy)}
            value={tecnicoSelectValue}
            onChange={onTecnico}
            disabled={busy}
            aria-label={`Técnico ticket ${ticket.id}`}
            title="Alterar técnico responsável."
          >
            <option value="">—</option>
            {tecnicos.map((t) => (
              <option key={t.id} value={String(t.id)}>
                {t.nivel_label ? `${t.name} — ${t.nivel_label}` : t.name}
              </option>
            ))}
          </select>
        </td>
      )}
      <td className="whitespace-nowrap px-2 py-2 text-right">
        <TicketTimer ticket={ticket} situacaoExecCode={situacaoExecCode} effectiveStatus={effectiveStatus} />
      </td>
    </>
  );
}
