import { useCallback, useEffect, useMemo } from 'react';
import './WorkflowTimeline.css';

import {
  normalizeWorkflowCodigo,
  workflowStepColumnIndex,
} from '../lib/ticketUi';

const STEPS = [
  { id: 'aberto', label: 'Aberto', tone: 'open', finalHint: null },
  { id: 'exec', label: 'Em execução', tone: 'exec', finalHint: null },
  { id: 'pend', label: 'Pendente', tone: 'pend', finalHint: null },
  { id: 'resolv', label: 'Resolvido', tone: 'resolved', finalHint: 'Estado final' },
  { id: 'fech', label: 'Fechado', tone: 'closed', finalHint: 'Finalizado' },
];

const FINAL_IDX = new Set([3, 4]);

function buildAllowedByStep(allowedTransitions) {
  /** @type {Map<number, object>} */
  const map = new Map();
  for (const t of allowedTransitions || []) {
    const idx = workflowStepColumnIndex(t?.codigo);
    if (idx >= 0 && !map.has(idx)) map.set(idx, t);
  }
  return map;
}

function primaryNextTransition(allowedTransitions, curIdx) {
  const list = allowedTransitions || [];
  if (!list.length) return null;
  if (curIdx < 0) return list[0];
  let best = null;
  let bestDelta = Infinity;
  for (const t of list) {
    const idx = workflowStepColumnIndex(t?.codigo);
    if (idx < 0) continue;
    const d = idx - curIdx;
    if (d > 0 && d < bestDelta) {
      bestDelta = d;
      best = t;
    }
  }
  return best || list[0];
}

/**
 * @typedef {object} WorkflowTimelineProps
 * @property {object} ticket
 * @property {boolean} [patchBusy]
 * @property {number|null} [patchingWorkflowStateId]
 * @property {boolean} [interactive]
 * @property {(transition: object) => void | Promise<void>} [onTransitionClick]
 */

/**
 * Timeline do workflow (PGM Service Desk).
 * Clique apenas quando `interactive` + `onTransitionClick` e transição permitida pela API.
 */
export default function WorkflowTimeline({
  ticket,
  patchBusy = false,
  patchingWorkflowStateId = null,
  interactive = false,
  onTransitionClick,
}) {
  const wf = ticket?.workflow;
  const enabled = wf?.enabled === true;

  const derived = useMemo(() => {
    const w = ticket?.workflow;
    const wfEnabled = w?.enabled === true;
    if (!wfEnabled) {
      return {
        enabled: false,
        curNormalized: '',
        curIdx: -1,
        curLabel: '—',
        allowedTransitions: [],
        allowedByStep: new Map(),
        primaryNext: null,
      };
    }

    const current = w?.current || {};
    const rawCodigo = current.codigo;
    const curNormalized = normalizeWorkflowCodigo(rawCodigo);
    const curIdx = workflowStepColumnIndex(rawCodigo);
    const curLabel = String(current.label || '').trim() || '—';

    const allowedTransitions = Array.isArray(w?.allowedTransitions) ? w.allowedTransitions : [];

    const allowedByStep = buildAllowedByStep(allowedTransitions);
    const primaryNext = primaryNextTransition(allowedTransitions, curIdx);

    return {
      enabled: true,
      current,
      rawCodigo,
      curNormalized,
      curIdx,
      curLabel,
      allowedTransitions,
      allowedByStep,
      primaryNext,
    };
  }, [ticket?.id, ticket?.workflow]);

  /** Hooks-first: sempre executar antes de qualquer early return sobre `enabled`. */

  useEffect(() => {
    if (typeof import.meta === 'undefined' || !import.meta.env?.DEV) return;
    const twf = ticket?.workflow;
    if (twf?.enabled !== true || ticket?.id == null) return;
    const len = Array.isArray(twf.allowedTransitions) ? twf.allowedTransitions.length : 0;
    if (len === 0) {
      // eslint-disable-next-line no-console
      console.warn(`Workflow sem transições para ticket ${ticket.id}`);
    }
  }, [ticket?.id, ticket?.workflow?.enabled, ticket?.workflow?.allowedTransitions?.length]);

  const activateTransition = useCallback(
    (tr) => {
      if (patchBusy) return;
      onTransitionClick?.(tr);
    },
    [patchBusy, onTransitionClick],
  );

  if (!enabled) return null;

  const {
    current,
    curNormalized,
    curIdx,
    curLabel,
    allowedTransitions,
    allowedByStep,
    primaryNext,
  } = derived;

  const allowInteractiveTimeline =
    interactive === true &&
    typeof onTransitionClick === 'function' &&
    allowedTransitions.length > 0;

  const onlyCurrent = allowedTransitions.length === 0;

  const nextLabelShort = primaryNext
    ? String(primaryNext.label || primaryNext.codigo || `Estado #${primaryNext.id}`).trim()
    : '';

  const rootAriaLabel = onlyCurrent
    ? `Workflow do ticket ${ticket?.id}: estado atual ${curLabel}. Nenhuma transição listada.`
    : `Workflow do ticket ${ticket?.id}. Estado atual ${curLabel}. ${allowedTransitions.length} transição(ões) permitida(s). Linha fixa Aberto até Fechado.`;

  /** @param {number} stepIndex */
  function renderPillContent(stepIndex, transition, isCurrent, pillTone, isAllowedTarget) {
    const step = STEPS[stepIndex];
    const hint = step.finalHint;
    const nwId = Number(transition?.id);
    const patchingThis =
      patchBusy &&
      patchingWorkflowStateId != null &&
      Number(patchingWorkflowStateId) === nwId &&
      nwId > 0;
    const showButton =
      allowInteractiveTimeline &&
      transition &&
      !isCurrent &&
      isAllowedTarget &&
      nwId > 0;

    const toneClassSuffix =
      pillTone === 'muted' ? 'muted' : pillTone === 'blocked' ? 'blocked' : pillTone;

    const cls = [
      'pgm-workflow-timeline__pill',
      `pgm-workflow-timeline__pill--${toneClassSuffix}`,
      isCurrent ? 'pgm-workflow-timeline__pill--current' : '',
      isAllowedTarget ? 'pgm-workflow-timeline__pill--allowed' : '',
      showButton ? 'pgm-workflow-timeline__pill--clickable' : '',
      patchingThis ? 'pgm-workflow-timeline__pill--patching' : '',
    ]
      .filter(Boolean)
      .join(' ');

    const descriptiveTitle =
      `${step.label}. ` +
      (isCurrent ? 'Estado atual.' : '') +
      (showButton ? ` Alterar para ${String(transition?.label || step.label)}.` : '') +
      (!isCurrent && !isAllowedTarget && FINAL_IDX.has(stepIndex)
        ? ' Estado final indisponível neste momento.'
        : '') +
      (!isCurrent && !isAllowedTarget && !FINAL_IDX.has(stepIndex)
        ? ' Estado não disponível neste momento.'
        : '');

    const inner = (
      <>
        <span className="pgm-workflow-timeline__pill-label">{step.label}</span>
        {hint ? <span className="pgm-workflow-timeline__pill-hint">{hint}</span> : null}
        {patchingThis ? (
          <span className="pgm-workflow-timeline__spinner" aria-hidden />
        ) : null}
      </>
    );

    if (showButton) {
      const destName = String(transition?.label || step.label).trim() || step.label;
      return (
        <button
          type="button"
          className={`pgm-workflow-timeline__pill-btn ${cls}`}
          title={`Alterar para ${destName}`}
          aria-busy={patchingThis || undefined}
          disabled={patchBusy}
          aria-disabled={patchBusy ? true : undefined}
          onClick={() => activateTransition(transition)}
        >
          {inner}
        </button>
      );
    }

    const extraProps = {};
    if (isCurrent) {
      extraProps['aria-current'] = 'step';
    }

    return (
      <span
        className={cls}
        title={descriptiveTitle}
        {...extraProps}
      >
        {inner}
      </span>
    );
  }

  if (onlyCurrent) {
    const tone = curIdx >= 0 ? STEPS[curIdx].tone : 'fallback';
    const pillClass = [
      'pgm-workflow-timeline__pill',
      tone === 'fallback' ? 'pgm-workflow-timeline__pill--fallback' : `pgm-workflow-timeline__pill--${tone}`,
      'pgm-workflow-timeline__pill--current',
    ].join(' ');
    const step = curIdx >= 0 ? STEPS[curIdx] : null;
    return (
      <div className="pgm-workflow-timeline" aria-label={rootAriaLabel}>
        <ul className="pgm-workflow-timeline__track" role="list" aria-label={`Estado atual: ${curLabel}`}>
          <li className="pgm-workflow-timeline__li" role="listitem">
            <span className={pillClass} aria-current="step" title={`Estado atual: ${curLabel}. Código: ${curNormalized || '—'}.`}>
              <span className="pgm-workflow-timeline__pill-label">{curLabel}</span>
              {step?.finalHint ? (
                <span className="pgm-workflow-timeline__pill-hint">{step.finalHint}</span>
              ) : null}
            </span>
          </li>
        </ul>
      </div>
    );
  }

  return (
    <div className="pgm-workflow-timeline" aria-label={rootAriaLabel} aria-busy={patchBusy || undefined}>
      {nextLabelShort ? (
        <div className="pgm-workflow-timeline__next-hint">
          Próximo:{' '}
          <span className="pgm-workflow-timeline__next-hint-strong">{nextLabelShort}</span>
        </div>
      ) : null}
      <ul className="pgm-workflow-timeline__track" role="list">
        {STEPS.map((step, i) => {
          const isCurrent = curIdx === i;
          const transition = allowedByStep.get(i);
          const isAllowedTarget = !!transition;
          const isFinal = FINAL_IDX.has(i);
          const isBlockedFinal = isFinal && !isCurrent && !transition;

          let pillTone = step.tone;
          if (!isCurrent && !isAllowedTarget) {
            pillTone = isBlockedFinal ? 'blocked' : 'muted';
          }

          const labelNode = renderPillContent(i, transition, isCurrent, pillTone, isAllowedTarget);

          return (
            <li key={step.id} className="pgm-workflow-timeline__li pgm-workflow-timeline__li--step" role="listitem">
              <span className="pgm-workflow-timeline__step-wrap">
                {i > 0 ? (
                  <span className="pgm-workflow-timeline__connector" aria-hidden />
                ) : null}
                {labelNode}
              </span>
            </li>
          );
        })}
      </ul>
      {curIdx < 0 && current?.codigo != null ? (
        <div
          className="pgm-workflow-timeline__unknown"
          title={String(current.codigo || '')}
        >
          Atual:{' '}
          <span className="pgm-workflow-timeline__unknown-strong">{curLabel}</span>
          <span className="pgm-workflow-timeline__unknown-note"> (fora do mapa fixo da linha; código {curNormalized || '—'})</span>
        </div>
      ) : null}
    </div>
  );
}
