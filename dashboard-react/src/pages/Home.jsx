import { Link } from 'react-router-dom';

export default function Home() {
  return (
    <div className="min-h-screen bg-[var(--pgm-bg-base,#0c0f14)] px-4 py-12 text-[var(--pgm-text,#e8eaed)]">
      <div className="mx-auto max-w-3xl text-center">
        <div className="mb-2 inline-flex rounded-full bg-[var(--pgm-badge-teal-bg)] px-4 py-1 text-xs font-semibold uppercase tracking-wider text-[var(--pgm-badge-teal-text)]">
          PGM — Tickets
        </div>
        <h1 className="text-3xl font-bold tracking-tight text-[var(--pgm-text,#e8eaed)] sm:text-4xl">Escolha o painel</h1>
        <p className="mt-3 text-[var(--pgm-text-secondary,#c4c9d1)]">
          Operação (fila + painel SLA) e portal do cliente, com comentários e histórico do chamado.
        </p>
      </div>

      <div className="mx-auto mt-12 grid max-w-6xl gap-6 sm:grid-cols-2 xl:grid-cols-3">
        <Link
          to="/tecnico"
          className="group rounded-[var(--pgm-radius-2xl,20px)] border border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-gradient-to-b from-[var(--pgm-bg-surface,#1a1f28)] to-[color-mix(in_srgb,var(--pgm-bg-surface,#1a1f28)_97%,rgba(255,255,255,0.03))] p-8 shadow-[var(--pgm-shadow-md)] transition-all hover:border-[var(--pgm-primary)] hover:shadow-[var(--pgm-shadow-lg),var(--pgm-shadow-glow)] hover:-translate-y-px"
        >
          <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-[var(--pgm-primary)] to-[var(--pgm-secondary)] text-lg font-bold text-white">T</div>
          <h2 className="mt-6 text-xl font-bold text-[var(--pgm-text,#e8eaed)]">Painel do técnico</h2>
          <p className="mt-2 text-sm leading-relaxed text-[var(--pgm-text-secondary,#c4c9d1)]">
            Fila completa, prioridades, status e visão executiva. Pronto para integrar com <code className="rounded bg-[var(--pgm-bg-elevated,#222834)] px-1 font-mono text-[var(--pgm-badge-teal-text)]">TicketsController</code>.
          </p>
          <span className="mt-6 inline-block text-sm font-semibold text-[var(--pgm-accent,#5cdbc0)] group-hover:underline">Entrar →</span>
        </Link>

        <Link
          to="/tecnico/operacional"
          className="group rounded-[var(--pgm-radius-2xl,20px)] border border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-gradient-to-b from-[var(--pgm-bg-surface,#1a1f28)] to-[color-mix(in_srgb,var(--pgm-bg-surface,#1a1f28)_97%,rgba(255,255,255,0.03))] p-8 shadow-[var(--pgm-shadow-md)] transition-all hover:border-[var(--pgm-badge-green-text)] hover:shadow-[var(--pgm-shadow-lg)] hover:-translate-y-px"
        >
          <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-[var(--pgm-badge-green-text)] to-[var(--pgm-primary)] text-lg font-bold text-white">O</div>
          <h2 className="mt-6 text-xl font-bold text-[var(--pgm-text,#e8eaed)]">Painel operacional</h2>
          <p className="mt-2 text-sm leading-relaxed text-[var(--pgm-text-secondary,#c4c9d1)]">
            Backlog, SLA, P1 e alertas (mock aqui; no portal: <code className="rounded bg-[var(--pgm-bg-elevated,#222834)] px-1 font-mono text-[var(--pgm-badge-teal-text)]">/tickets/operacional</code> ou{' '}
            <code className="rounded bg-[var(--pgm-bg-elevated,#222834)] px-1 font-mono text-[var(--pgm-badge-teal-text)]">/servicedesk/operacional</code>).
          </p>
          <span className="mt-6 inline-block text-sm font-semibold text-[var(--pgm-badge-green-text,#7ee787)] group-hover:underline">Entrar →</span>
        </Link>

        <Link
          to="/cliente"
          className="group rounded-[var(--pgm-radius-2xl,20px)] border border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-gradient-to-b from-[var(--pgm-bg-surface,#1a1f28)] to-[color-mix(in_srgb,var(--pgm-bg-surface,#1a1f28)_97%,rgba(255,255,255,0.03))] p-8 shadow-[var(--pgm-shadow-md)] transition-all hover:border-[var(--pgm-badge-blue-text)] hover:shadow-[var(--pgm-shadow-lg)] hover:-translate-y-px"
        >
          <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-[var(--pgm-badge-blue-text)] to-[var(--pgm-primary)] text-lg font-bold text-white">C</div>
          <h2 className="mt-6 text-xl font-bold text-[var(--pgm-text,#e8eaed)]">Portal do cliente</h2>
          <p className="mt-2 text-sm leading-relaxed text-[var(--pgm-text-secondary,#c4c9d1)]">
            Meus chamados, detalhe do ticket, linha do tempo e campo para novo comentário (mock; ligue à API depois).
          </p>
          <span className="mt-6 inline-block text-sm font-semibold text-[var(--pgm-badge-blue-text,#79b8ff)] group-hover:underline">Entrar →</span>
        </Link>
      </div>

      <p className="mx-auto mt-12 max-w-xl text-center text-xs text-[var(--pgm-text-muted,#9aa0a8)]">
        Desenvolvimento: <code className="rounded bg-[var(--pgm-bg-elevated,#222834)] px-1 font-mono">npm run dev</code> em <code className="rounded bg-[var(--pgm-bg-elevated,#222834)] px-1 font-mono">dashboard-react</code>
      </p>
    </div>
  );
}
