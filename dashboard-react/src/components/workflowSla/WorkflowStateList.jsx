/** Lista e ações de workflow_states (admin SLA). */
export default function WorkflowStateList({
  states,
  loading,
  onCreate,
  onEdit,
  onDelete,
  busyId,
}) {
  if (loading) {
    return <div className="text-sm text-[var(--pgm-text-muted)]">Carregando estados…</div>;
  }

  return (
    <div className="space-y-3">
      <div className="flex flex-wrap items-center justify-between gap-2">
        <p className="m-0 text-xs text-[var(--pgm-text-muted)]">
          Estados usados em transições, políticas SLA e tickets (workflow_state_id). Códigos conhecidos (aberto,
          emandamento, pendente, resolvido, fechado) sincronizam com a situação legada do ticket.
        </p>
        <button
          type="button"
          className="rounded-lg bg-gradient-to-b from-[var(--pgm-primary)] to-[#168a64] px-4 py-2 text-xs font-semibold text-white"
          onClick={onCreate}
        >
          Novo estado
        </button>
      </div>

      {!states?.length ? (
        <div className="rounded-xl border border-dashed border-[var(--pgm-border)] bg-[var(--pgm-bg-elevated)] p-8 text-center">
          <p className="text-sm text-[var(--pgm-text-muted)]">Nenhum estado cadastrado.</p>
          <button
            type="button"
            className="mt-3 rounded-lg bg-[var(--pgm-primary)] px-4 py-2 text-sm font-semibold text-white"
            onClick={onCreate}
          >
            Criar primeiro estado
          </button>
        </div>
      ) : (
        <div className="overflow-x-auto rounded-lg border border-[var(--pgm-border-subtle)] bg-[var(--pgm-bg-surface)]">
          <table className="pgm-table min-w-full text-[0.8125rem]">
            <thead className="bg-[var(--pgm-bg-elevated)] text-left text-[var(--pgm-text-muted)]">
              <tr>
                <th className="px-3 py-2 text-[0.65rem] font-semibold uppercase tracking-[0.08em]">ID</th>
                <th className="px-3 py-2 text-[0.65rem] font-semibold uppercase tracking-[0.08em]">Nome</th>
                <th className="px-3 py-2 text-[0.65rem] font-semibold uppercase tracking-[0.08em]">Código</th>
                <th className="px-3 py-2 text-[0.65rem] font-semibold uppercase tracking-[0.08em]">Inicial</th>
                <th className="px-3 py-2 text-[0.65rem] font-semibold uppercase tracking-[0.08em]">Final</th>
                <th className="px-3 py-2 text-[0.65rem] font-semibold uppercase tracking-[0.08em]">Legado</th>
                <th className="px-3 py-2 text-right text-[0.65rem] font-semibold uppercase tracking-[0.08em]">Ações</th>
              </tr>
            </thead>
            <tbody>
              {states.map((s) => (
                <tr key={s.id} className="border-b border-[var(--pgm-border-subtle)]">
                  <td className="px-3 py-2 font-mono text-[var(--pgm-text-muted)]">{s.id}</td>
                  <td className="px-3 py-2 font-medium text-[var(--pgm-text)]">{s.nome}</td>
                  <td className="px-3 py-2 font-mono text-[var(--pgm-text-secondary)]">{s.codigo}</td>
                  <td className="px-3 py-2">
                    {s.is_inicial ? (
                      <span className="rounded-full bg-[rgba(29,158,117,0.18)] px-2 py-0.5 text-[10px] font-semibold text-[var(--pgm-primary-hover)]">
                        Sim
                      </span>
                    ) : (
                      <span className="text-[var(--pgm-text-muted)]">—</span>
                    )}
                  </td>
                  <td className="px-3 py-2">
                    {s.is_final ? (
                      <span className="rounded-full bg-[rgba(138,90,194,0.18)] px-2 py-0.5 text-[10px] font-semibold text-[#8a5ac2]">
                        Sim
                      </span>
                    ) : (
                      <span className="text-[var(--pgm-text-muted)]">—</span>
                    )}
                  </td>
                  <td className="px-3 py-2 text-xs">
                    {s.legacy_situacao_mapped ? (
                      <span className="text-[var(--pgm-badge-green-text,#15803d)]">Situação ERP</span>
                    ) : (
                      <span className="text-[var(--pgm-text-muted)]">Só workflow</span>
                    )}
                  </td>
                  <td className="px-3 py-2 text-right text-xs whitespace-nowrap">
                    <button
                      type="button"
                      className="text-[var(--pgm-primary-hover)] disabled:opacity-40"
                      disabled={busyId === s.id}
                      onClick={() => onEdit(s)}
                    >
                      Editar
                    </button>
                    <span className="mx-1 text-[var(--pgm-text-muted)]">|</span>
                    <button
                      type="button"
                      className="text-[var(--pgm-badge-red-text)] disabled:opacity-40"
                      disabled={busyId === s.id}
                      onClick={() => onDelete(s)}
                    >
                      Excluir
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
