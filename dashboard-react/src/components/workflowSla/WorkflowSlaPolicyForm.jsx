import { useEffect, useMemo, useState } from 'react';

function trimStr(v) {
  return v != null && String(v).trim() !== '' ? String(v).trim() : '';
}

/** Rótulo para select/preview: API envia label; fallback razaosocial / nomefantasia. */
function empresaOptionLabel(e) {
  if (!e || typeof e !== 'object') return '';
  return (
    trimStr(e.label) ||
    trimStr(e.nome) ||
    trimStr(e.razaosocial) ||
    trimStr(e.nomefantasia) ||
    (e.id != null ? `Empresa #${e.id}` : '')
  );
}

function buildPreview(form, empresas, states) {
  const row = empresas.find((x) => Number(x.id) === Number(form.empresa_id));
  const emp = form.is_global ? 'qualquer empresa (regra global)' : empresaOptionLabel(row) || 'empresa selecionada';
  const st = states.find((s) => Number(s.id) === Number(form.workflow_state_id))?.nome || 'estado';
  const resMin = Number(form.resolucao_minutos) || 0;
  const dest = states.find((s) => Number(s.id) === Number(form.escalate_to_state_id))?.nome || '—';
  const tol = Number(form.escalate_after_minutos) || 0;
  const auto = !!form.auto_escalar;
  let text = `Quando um ticket da empresa ${emp} entrar em ${st}, terá ${resMin} minutos úteis para resolução.`;
  if (auto && Number(form.escalate_to_state_id) > 0) {
    text += ` Se vencer o prazo${tol > 0 ? ` após mais ${tol} minuto(s) úteis de tolerância` : ''}, será movido automaticamente para ${dest}.`;
  } else {
    text += ' Sem auto-escalonamento configurado para este estado.';
  }
  return text;
}

export default function WorkflowSlaPolicyForm({ empresas, states, initial, onSubmit, onCancel, submitting }) {
  const empty = {
    is_global: false,
    empresa_id: empresas[0]?.id || '',
    workflow_state_id: '',
    resposta_minutos: '',
    resolucao_minutos: '',
    pausa_sla: false,
    is_final: false,
    auto_escalar: false,
    escalate_to_state_id: '',
    escalate_after_minutos: 0,
    nota_local: '',
  };
  const [form, setForm] = useState(empty);

  useEffect(() => {
    if (initial) {
      setForm({
        is_global: initial.scope === 'global',
        empresa_id: initial.empresa_id ?? empresas[0]?.id ?? '',
        workflow_state_id: String(initial.workflow_state_id || ''),
        resposta_minutos: initial.resposta_minutos ?? '',
        resolucao_minutos: initial.resolucao_minutos ?? '',
        pausa_sla: !!initial.pausa_sla,
        is_final: !!initial.is_final,
        auto_escalar: !!initial.auto_escalar,
        escalate_to_state_id: initial.escalate_to_state_id ? String(initial.escalate_to_state_id) : '',
        escalate_after_minutos: initial.escalate_after_minutos ?? 0,
        nota_local: '',
      });
    } else {
      setForm({
        ...empty,
        empresa_id: empresas[0]?.id || '',
      });
    }
  }, [initial]);

  useEffect(() => {
    if (initial) return;
    const first = empresas[0]?.id;
    if (first === undefined || first === '') return;
    setForm((f) => (f.empresa_id !== '' && f.empresa_id !== undefined ? f : { ...f, empresa_id: first }));
  }, [empresas, initial]);

  const preview = useMemo(() => buildPreview(form, empresas, states), [form, empresas, states]);

  const handleSubmit = (e) => {
    e.preventDefault();
    if (!form.is_global && empresas.length === 0) {
      return;
    }
    if (!form.is_global && (form.empresa_id === '' || form.empresa_id === undefined)) {
      return;
    }
    onSubmit({
      is_global: form.is_global,
      empresa_id: form.is_global ? null : Number(form.empresa_id),
      workflow_state_id: Number(form.workflow_state_id),
      resposta_minutos: form.resposta_minutos === '' ? null : Number(form.resposta_minutos),
      resolucao_minutos: form.resolucao_minutos === '' ? null : Number(form.resolucao_minutos),
      pausa_sla: form.pausa_sla,
      is_final: form.is_final,
      auto_escalar: form.auto_escalar,
      escalate_to_state_id: form.auto_escalar ? Number(form.escalate_to_state_id) || null : null,
      escalate_after_minutos: Number(form.escalate_after_minutos) || 0,
      nota_local: form.nota_local,
    });
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <div className="rounded-lg border border-[var(--pgm-border-subtle)] bg-[var(--pgm-bg-elevated)] p-3">
        <p className="text-[0.65rem] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted)]">Preview</p>
        <p className="mt-1 text-sm leading-relaxed text-[var(--pgm-text-secondary)]">{preview}</p>
      </div>

      <label className="flex items-center gap-2 text-sm text-[var(--pgm-text)]">
        <input
          type="checkbox"
          checked={form.is_global}
          onChange={(e) => setForm((f) => ({ ...f, is_global: e.target.checked }))}
        />
        Regra global
        <span className="rounded-full bg-[rgba(45,170,225,0.15)] px-2 py-0.5 text-[10px] font-semibold text-[#2DAAE1]">
          sobrescreve por empresa
        </span>
      </label>

      <label className="block text-[0.7rem] font-semibold uppercase tracking-[0.06em] text-[var(--pgm-text-muted)]">
        Empresa
        {!form.is_global && empresas.length === 0 ? (
          <p className="mt-2 rounded-md border border-[var(--pgm-border-subtle)] bg-[var(--pgm-bg-elevated)] px-3 py-2 text-sm text-[var(--pgm-text-muted)]">
            Nenhuma empresa disponível para configuração. Verifique o endpoint workflow-sla-empresas, WORKFLOW_EMPRESAS e
            cadastro em empresas (inativa).
          </p>
        ) : (
          <select
            disabled={form.is_global}
            className="mt-1 w-full rounded-md border border-[var(--pgm-border)] bg-[var(--pgm-bg-raised)] px-3 py-2 text-sm text-[var(--pgm-text)] disabled:opacity-50"
            value={empresas.length ? String(form.empresa_id) : ''}
            onChange={(e) => setForm((f) => ({ ...f, empresa_id: e.target.value }))}
          >
            {empresas.map((e) => (
              <option key={e.id} value={String(e.id)}>
                {empresaOptionLabel(e)}
              </option>
            ))}
          </select>
        )}
      </label>

      <label className="block text-[0.7rem] font-semibold uppercase tracking-[0.06em] text-[var(--pgm-text-muted)]">
        Estado do workflow
        <select
          required
          className="mt-1 w-full rounded-md border border-[var(--pgm-border)] bg-[var(--pgm-bg-raised)] px-3 py-2 text-sm"
          value={form.workflow_state_id}
          onChange={(e) => setForm((f) => ({ ...f, workflow_state_id: e.target.value }))}
        >
          <option value="">Selecione…</option>
          {states.map((s) => (
            <option key={s.id} value={String(s.id)}>
              {s.nome} ({s.codigo})
            </option>
          ))}
        </select>
      </label>

      <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <label className="block text-[0.7rem] font-semibold uppercase tracking-[0.06em] text-[var(--pgm-text-muted)]">
          Resposta (min)
          <input
            type="number"
            min={0}
            className="mt-1 w-full rounded-md border border-[var(--pgm-border)] bg-[var(--pgm-bg-raised)] px-3 py-2 text-sm"
            value={form.resposta_minutos}
            onChange={(e) => setForm((f) => ({ ...f, resposta_minutos: e.target.value }))}
          />
        </label>
        <label className="block text-[0.7rem] font-semibold uppercase tracking-[0.06em] text-[var(--pgm-text-muted)]">
          Resolução (min)
          <input
            type="number"
            min={0}
            className="mt-1 w-full rounded-md border border-[var(--pgm-border)] bg-[var(--pgm-bg-raised)] px-3 py-2 text-sm"
            value={form.resolucao_minutos}
            onChange={(e) => setForm((f) => ({ ...f, resolucao_minutos: e.target.value }))}
          />
        </label>
      </div>

      <label className="flex items-center gap-2 text-sm">
        <input
          type="checkbox"
          checked={form.pausa_sla}
          onChange={(e) => setForm((f) => ({ ...f, pausa_sla: e.target.checked }))}
        />
        Pausar SLA neste estado
      </label>
      <label className="flex items-center gap-2 text-sm">
        <input
          type="checkbox"
          checked={form.is_final}
          onChange={(e) => setForm((f) => ({ ...f, is_final: e.target.checked }))}
        />
        Estado final
      </label>
      <label className="flex items-center gap-2 text-sm">
        <input
          type="checkbox"
          checked={form.auto_escalar}
          onChange={(e) => setForm((f) => ({ ...f, auto_escalar: e.target.checked }))}
        />
        Auto-escalar
      </label>

      {form.auto_escalar ? (
        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <label className="block text-[0.7rem] font-semibold uppercase tracking-[0.06em] text-[var(--pgm-text-muted)]">
            Escalar para estado
            <select
              className="mt-1 w-full rounded-md border border-[var(--pgm-border)] bg-[var(--pgm-bg-raised)] px-3 py-2 text-sm"
              value={form.escalate_to_state_id}
              onChange={(e) => setForm((f) => ({ ...f, escalate_to_state_id: e.target.value }))}
            >
              <option value="">Selecione…</option>
              {states
                .filter((s) => Number(s.id) !== Number(form.workflow_state_id))
                .map((s) => (
                  <option key={s.id} value={String(s.id)}>
                    {s.nome}
                  </option>
                ))}
            </select>
          </label>
          <label className="block text-[0.7rem] font-semibold uppercase tracking-[0.06em] text-[var(--pgm-text-muted)]">
            Tolerância após vencimento (min úteis)
            <input
              type="number"
              min={0}
              className="mt-1 w-full rounded-md border border-[var(--pgm-border)] bg-[var(--pgm-bg-raised)] px-3 py-2 text-sm"
              value={form.escalate_after_minutos}
              onChange={(e) => setForm((f) => ({ ...f, escalate_after_minutos: e.target.value }))}
            />
          </label>
        </div>
      ) : null}

      <label className="block text-[0.7rem] font-semibold uppercase tracking-[0.06em] text-[var(--pgm-text-muted)]">
        Observação (local, não gravada no servidor)
        <textarea
          rows={2}
          className="mt-1 w-full rounded-md border border-[var(--pgm-border)] bg-[var(--pgm-bg-raised)] px-3 py-2 text-sm"
          value={form.nota_local}
          onChange={(e) => setForm((f) => ({ ...f, nota_local: e.target.value }))}
        />
      </label>

      <div className="flex flex-wrap justify-end gap-2 border-t border-[var(--pgm-border-subtle)] pt-3">
        <button
          type="button"
          className="rounded-lg border border-[var(--pgm-border)] px-4 py-2 text-sm font-medium text-[var(--pgm-text)] hover:bg-[var(--pgm-bg-overlay)]"
          onClick={onCancel}
        >
          Cancelar
        </button>
        <button
          type="submit"
          disabled={submitting || (!form.is_global && empresas.length === 0)}
          className="rounded-lg bg-gradient-to-b from-[var(--pgm-primary)] to-[#168a64] px-4 py-2 text-sm font-semibold text-white shadow-[var(--pgm-shadow-sm)] disabled:opacity-50"
        >
          {submitting ? 'Salvando…' : 'Salvar'}
        </button>
      </div>
    </form>
  );
}
