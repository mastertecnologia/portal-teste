import { Link } from 'react-router-dom';

export default function Home() {
  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-100 via-teal-50/40 to-slate-100 px-4 py-12 text-slate-800">
      <div className="mx-auto max-w-3xl text-center">
        <div className="mb-2 inline-flex rounded-full bg-teal-100 px-4 py-1 text-xs font-semibold uppercase tracking-wider text-teal-800">
          PGM — Tickets
        </div>
        <h1 className="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Escolha o painel</h1>
        <p className="mt-3 text-slate-600">
          Operação (fila + painel SLA) e portal do cliente, com comentários e histórico do chamado.
        </p>
      </div>

      <div className="mx-auto mt-12 grid max-w-6xl gap-6 sm:grid-cols-2 xl:grid-cols-3">
        <Link
          to="/tecnico"
          className="group rounded-[28px] border-2 border-transparent bg-white p-8 shadow-lg shadow-slate-200/60 transition hover:border-teal-400 hover:shadow-xl"
        >
          <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-900 text-lg font-bold text-white">T</div>
          <h2 className="mt-6 text-xl font-bold text-slate-900">Painel do técnico</h2>
          <p className="mt-2 text-sm leading-relaxed text-slate-600">
            Fila completa, prioridades, status e visão executiva. Pronto para integrar com <code className="rounded bg-slate-100 px-1">TicketsController</code>.
          </p>
          <span className="mt-6 inline-block text-sm font-semibold text-teal-700 group-hover:underline">Entrar →</span>
        </Link>

        <Link
          to="/tecnico/operacional"
          className="group rounded-[28px] border-2 border-transparent bg-white p-8 shadow-lg shadow-slate-200/60 transition hover:border-emerald-400 hover:shadow-xl"
        >
          <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-800 text-lg font-bold text-white">O</div>
          <h2 className="mt-6 text-xl font-bold text-slate-900">Painel operacional</h2>
          <p className="mt-2 text-sm leading-relaxed text-slate-600">
            Backlog, SLA, P1 e alertas (mock aqui; no portal: <code className="rounded bg-slate-100 px-1">/tickets/operacional</code> ou{' '}
            <code className="rounded bg-slate-100 px-1">/servicedesk/operacional</code>).
          </p>
          <span className="mt-6 inline-block text-sm font-semibold text-emerald-700 group-hover:underline">Entrar →</span>
        </Link>

        <Link
          to="/cliente"
          className="group rounded-[28px] border-2 border-transparent bg-white p-8 shadow-lg shadow-slate-200/60 transition hover:border-cyan-400 hover:shadow-xl"
        >
          <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-600 to-teal-600 text-lg font-bold text-white">C</div>
          <h2 className="mt-6 text-xl font-bold text-slate-900">Portal do cliente</h2>
          <p className="mt-2 text-sm leading-relaxed text-slate-600">
            Meus chamados, detalhe do ticket, linha do tempo e campo para novo comentário (mock; ligue à API depois).
          </p>
          <span className="mt-6 inline-block text-sm font-semibold text-cyan-700 group-hover:underline">Entrar →</span>
        </Link>
      </div>

      <p className="mx-auto mt-12 max-w-xl text-center text-xs text-slate-500">
        Desenvolvimento: <code className="rounded bg-slate-200/60 px-1">npm run dev</code> em <code className="rounded bg-slate-200/60 px-1">dashboard-react</code>
      </p>
    </div>
  );
}
