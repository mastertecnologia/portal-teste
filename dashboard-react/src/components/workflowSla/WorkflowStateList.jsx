/** Lista somente leitura de workflow_states (admin SLA). */
export default function WorkflowStateList({ states, loading }) {
  if (loading) {
    return <div className="text-sm text-[var(--pgm-text-muted)]">Carregando estados…</div>;
  }
  if (!states?.length) {
    return <div className="text-sm text-[var(--pgm-text-muted)]">Nenhum estado cadastrado.</div>;
  }
  return (
    <div className="overflow-x-auto rounded-lg border border-[var(--pgm-border-subtle)] bg-[var(--pgm-bg-surface)]">
      <table className="pgm-table min-w-full text-[0.8125rem]">
        <thead className="bg-[var(--pgm-bg-elevated)] text-left text-[var(--pgm-text-muted)]">
          <tr>
            <th className="px-3 py-2 text-[0.65rem] font-semibold uppercase tracking-[0.08em]">ID</th>
            <th className="px-3 py-2 text-[0.65rem] font-semibold uppercase tracking-[0.08em]">Nome</th>
            <th className="px-3 py-2 text-[0.65rem] font-semibold uppercase tracking-[0.08em]">Código</th>
            <th className="px-3 py-2 text-[0.65rem] font-semibold uppercase tracking-[0.08em]">Inicial</th>
            <th className="px-3 py-2 text-[0.65rem] font-semibold uppercase tracking-[0.08em]">Final</th>
          </tr>
        </thead>
        <tbody>
          {states.map((s) => (
            <tr key={s.id} className="border-b border-[var(--pgm-border-subtle)]">
              <td className="px-3 py-2 font-mono text-[var(--pgm-text-muted)]">{s.id}</td>
              <td className="px-3 py-2 font-medium text-[var(--pgm-text)]">{s.nome}</td>
              <td className="px-3 py-2 text-[var(--pgm-text-secondary)]">{s.codigo}</td>
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
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
