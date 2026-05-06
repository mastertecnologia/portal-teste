import { useState } from 'react';

/** Transições com edição básica (origem/destino/global). */
export default function WorkflowTransitionList({
  transitions,
  states,
  loading,
  onSave,
  onDelete,
  busyId,
}) {
  const [editing, setEditing] = useState(null);
  const [form, setForm] = useState({ from_state_id: '', to_state_id: '', is_global: false });

  const startEdit = (t) => {
    setEditing(t.id);
    setForm({
      from_state_id: String(t.from_state_id),
      to_state_id: String(t.to_state_id),
      is_global: t.scope === 'global',
    });
  };

  const submitEdit = async () => {
    if (!editing) return;
    const r = await onSave(editing, {
      from_state_id: Number(form.from_state_id),
      to_state_id: Number(form.to_state_id),
      is_global: form.is_global,
    });
    if (r && r.ok) {
      setEditing(null);
    }
  };

  if (loading) {
    return <div className="text-sm text-[var(--pgm-text-muted)]">Carregando transições…</div>;
  }
  if (!transitions?.length) {
    return <div className="text-sm text-[var(--pgm-text-muted)]">Nenhuma transição visível para esta empresa.</div>;
  }

  const stateLabel = (id) => states?.find((s) => Number(s.id) === Number(id))?.nome || `#${id}`;

  return (
    <div className="overflow-x-auto rounded-lg border border-[var(--pgm-border-subtle)] bg-[var(--pgm-bg-surface)]">
      <table className="pgm-table min-w-full text-[0.8125rem]">
        <thead className="bg-[var(--pgm-bg-elevated)] text-left text-[var(--pgm-text-muted)]">
          <tr>
            <th className="px-3 py-2 text-[0.65rem] font-semibold uppercase tracking-[0.08em]">Origem</th>
            <th className="px-3 py-2 text-[0.65rem] font-semibold uppercase tracking-[0.08em]">Destino</th>
            <th className="px-3 py-2 text-[0.65rem] font-semibold uppercase tracking-[0.08em]">Âmbito</th>
            <th className="px-3 py-2 text-right text-[0.65rem] font-semibold uppercase tracking-[0.08em]">Ações</th>
          </tr>
        </thead>
        <tbody>
          {transitions.map((t) => (
            <tr key={t.id} className="border-b border-[var(--pgm-border-subtle)]">
              {editing === t.id ? (
                <>
                  <td className="px-3 py-2">
                    <select
                      className="w-full rounded border border-[var(--pgm-border)] bg-[var(--pgm-bg-elevated)] px-2 py-1 text-sm"
                      value={form.from_state_id}
                      onChange={(e) => setForm((f) => ({ ...f, from_state_id: e.target.value }))}
                    >
                      {states?.map((s) => (
                        <option key={s.id} value={String(s.id)}>
                          {s.nome}
                        </option>
                      ))}
                    </select>
                  </td>
                  <td className="px-3 py-2">
                    <select
                      className="w-full rounded border border-[var(--pgm-border)] bg-[var(--pgm-bg-elevated)] px-2 py-1 text-sm"
                      value={form.to_state_id}
                      onChange={(e) => setForm((f) => ({ ...f, to_state_id: e.target.value }))}
                    >
                      {states?.map((s) => (
                        <option key={s.id} value={String(s.id)}>
                          {s.nome}
                        </option>
                      ))}
                    </select>
                  </td>
                  <td className="px-3 py-2">
                    <label className="flex items-center gap-2 text-xs text-[var(--pgm-text-secondary)]">
                      <input
                        type="checkbox"
                        checked={form.is_global}
                        onChange={(e) => setForm((f) => ({ ...f, is_global: e.target.checked }))}
                      />
                      Global
                    </label>
                  </td>
                  <td className="px-3 py-2 text-right">
                    <button
                      type="button"
                      className="mr-2 text-xs font-medium text-[var(--pgm-primary-hover)]"
                      onClick={() => setEditing(null)}
                    >
                      Cancelar
                    </button>
                    <button
                      type="button"
                      disabled={busyId === t.id}
                      className="rounded-lg bg-gradient-to-b from-[var(--pgm-primary)] to-[#168a64] px-3 py-1 text-xs font-semibold text-white disabled:opacity-50"
                      onClick={() => submitEdit()}
                    >
                      Salvar
                    </button>
                  </td>
                </>
              ) : (
                <>
                  <td className="px-3 py-2 text-[var(--pgm-text)]">{t.from_nome || stateLabel(t.from_state_id)}</td>
                  <td className="px-3 py-2 text-[var(--pgm-text)]">{t.to_nome || stateLabel(t.to_state_id)}</td>
                  <td className="px-3 py-2">
                    <span
                      className={`rounded-full px-2 py-0.5 text-[10px] font-semibold ${
                        t.scope === 'global'
                          ? 'bg-[rgba(45,170,225,0.15)] text-[#2DAAE1]'
                          : 'bg-[rgba(29,158,117,0.18)] text-[var(--pgm-primary-hover)]'
                      }`}
                    >
                      {t.scope === 'global' ? 'Global' : 'Empresa'}
                    </span>
                  </td>
                  <td className="px-3 py-2 text-right">
                    <button
                      type="button"
                      className="mr-2 text-xs font-medium text-[var(--pgm-text-secondary)] hover:text-[var(--pgm-text)]"
                      onClick={() => startEdit(t)}
                    >
                      Editar
                    </button>
                    <button
                      type="button"
                      disabled={busyId === t.id}
                      className="text-xs font-medium text-[var(--pgm-badge-red-text,#ff9492)] disabled:opacity-50"
                      onClick={() => onDelete(t.id)}
                    >
                      Excluir
                    </button>
                  </td>
                </>
              )}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
