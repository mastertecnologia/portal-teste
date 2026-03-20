import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { fetchTicketsTecnico, USE_MOCK } from '../lib/api';
import { badgeClass, statusType } from '../lib/ticketUi';
import { MOCK_SESSION_TECNICO } from '../data/mockData';

const GROUP_KEYS = {
  todos: 'todos',
  pendente: 'pendentes',
  execucao: 'emandamento',
  resolvido: 'resolvidos',
  fechados: 'fechados',
};

function statusLabel(row) {
  return row.situacaoLabel || row.status || '—';
}

export default function TechDashboard({ boot }) {
  const [groups, setGroups] = useState(null);
  const [q, setQ] = useState('');
  const [filtroStatus, setFiltroStatus] = useState('pendente');

  useEffect(() => {
    let cancel = false;
    (async () => {
      const res = await fetchTicketsTecnico();
      if (cancel) return;
      if (res.ok && res.groups) setGroups(res.groups);
    })();
    return () => {
      cancel = true;
    };
  }, []);

  const rows = useMemo(() => {
    if (!groups) return [];
    const key = GROUP_KEYS[filtroStatus] || 'todos';
    const base = groups[key] || [];
    const qq = q.trim().toLowerCase();
    if (!qq) return base;
    return base.filter((t) => {
      const id = String(t.id);
      const cliente = String(t.cliente || '').toLowerCase();
      const assunto = String(t.assunto || '').toLowerCase();
      return id.includes(qq) || cliente.includes(qq) || assunto.includes(qq);
    });
  }, [groups, filtroStatus, q]);

  const totalTodos = groups?.todos?.length ?? 0;
  const hoje = new Date().toLocaleDateString('pt-BR');

  const dash = boot?.paths?.dashboard;
  const addTicket = boot?.paths?.addTicket;

  return (
    <div className="min-h-screen bg-slate-100 text-slate-800">
      <div className="flex min-h-screen">
        <aside className="hidden w-72 shrink-0 bg-gradient-to-b from-teal-950 via-teal-900 to-teal-800 text-white lg:flex lg:flex-col">
          <div className="border-b border-white/10 px-6 py-6">
            <div className="flex items-center gap-4">
              <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/95 text-teal-800 shadow-lg">
                <div className="text-center leading-tight">
                  <div className="text-lg font-extrabold">PGM</div>
                  <div className="text-[9px] font-medium uppercase tracking-wide">Tickets</div>
                </div>
              </div>
              <div>
                <h1 className="text-lg font-semibold">Portal</h1>
                <p className="text-sm text-teal-100/80">Painel técnico</p>
              </div>
            </div>
          </div>
          <nav className="flex-1 space-y-2 px-4 py-6 text-sm">
            {dash ? (
              <a
                href={dash}
                className="flex w-full items-center justify-between rounded-2xl px-4 py-3 text-left text-teal-50/90 transition hover:bg-white/10"
              >
                <span className="font-medium">← Dashboard</span>
              </a>
            ) : (
              <Link
                to="/"
                className="flex w-full items-center justify-between rounded-2xl px-4 py-3 text-left text-teal-50/90 transition hover:bg-white/10"
              >
                <span className="font-medium">← Início</span>
              </Link>
            )}
            {USE_MOCK && (
              <Link
                to="/cliente"
                className="flex w-full items-center justify-between rounded-2xl px-4 py-3 text-left text-teal-50/90 transition hover:bg-white/10"
              >
                <span className="font-medium">Demo cliente</span>
              </Link>
            )}
          </nav>
        </aside>

        <main className="flex-1">
          <header className="sticky top-0 z-20 border-b border-slate-200 bg-white/90 backdrop-blur">
            <div className="flex flex-col gap-4 px-4 py-4 sm:px-6 xl:flex-row xl:items-center xl:justify-between">
              <div>
                <p className="text-sm font-medium text-teal-700">
                  {boot ? 'Empresa atual (sessão)' : MOCK_SESSION_TECNICO.empresa}
                </p>
                <h2 className="text-2xl font-bold tracking-tight text-slate-900">Tickets — técnico</h2>
                <p className="text-sm text-slate-500">
                  Mesmas rotas do portal: alterar situação e cancelar usam URLs do CakePHP (navegação completa).
                </p>
              </div>
              <div className="flex flex-wrap items-center gap-3">
                <div className="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm shadow-sm">
                  <span className="block text-slate-500">Data</span>
                  <span className="font-semibold text-slate-800">{hoje}</span>
                </div>
                {addTicket ? (
                  <a
                    href={addTicket}
                    className="rounded-2xl bg-gradient-to-r from-teal-700 to-cyan-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-teal-700/20"
                  >
                    Abrir ticket
                  </a>
                ) : (
                  <span className="rounded-2xl border border-slate-200 px-5 py-3 text-sm text-slate-500">Abrir ticket (portal)</span>
                )}
              </div>
            </div>
          </header>

          <div className="space-y-6 p-4 sm:p-6">
            <section className="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm">
              <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                  <h3 className="text-lg font-bold text-slate-900">Fila</h3>
                  <p className="text-sm text-slate-500">
                    {totalTodos} ticket(s) na empresa · integração JSON ativa quando embutido no CakePHP
                  </p>
                </div>
                <div className="flex flex-col gap-3 sm:flex-row">
                  <input
                    value={q}
                    onChange={(e) => setQ(e.target.value)}
                    placeholder="Buscar nº, cliente ou assunto"
                    className="h-11 rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none placeholder:text-slate-400 focus:border-teal-500"
                  />
                  <select
                    value={filtroStatus}
                    onChange={(e) => setFiltroStatus(e.target.value)}
                    className="h-11 rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-teal-500"
                  >
                    <option value="todos">Todos</option>
                    <option value="pendente">Aguardando técnico</option>
                    <option value="execucao">Em execução</option>
                    <option value="resolvido">Resolvidos</option>
                    <option value="fechados">Cancelados / fechados</option>
                  </select>
                </div>
              </div>

              <div className="mt-5 overflow-hidden rounded-2xl border border-slate-200">
                <div className="overflow-x-auto">
                  <table className="min-w-full divide-y divide-slate-200 text-sm">
                    <thead className="bg-slate-50 text-left text-slate-500">
                      <tr>
                        <th className="px-4 py-3 font-semibold">Ticket</th>
                        <th className="px-4 py-3 font-semibold">Autor</th>
                        <th className="px-4 py-3 font-semibold">Data</th>
                        <th className="px-4 py-3 font-semibold">Assunto</th>
                        <th className="px-4 py-3 font-semibold">Status</th>
                        <th className="px-4 py-3 font-semibold">Cliente</th>
                        <th className="px-4 py-3 font-semibold">Ações</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100 bg-white">
                      {rows.length === 0 ? (
                        <tr>
                          <td colSpan={7} className="px-4 py-8 text-center text-slate-500">
                            Nenhum ticket neste filtro.
                          </td>
                        </tr>
                      ) : (
                        rows.map((ticket) => {
                          const st = statusLabel(ticket);
                          return (
                            <tr key={ticket.id} className="transition hover:bg-slate-50/80">
                              <td className="px-4 py-4 font-semibold">
                                {ticket.urls?.edit ? (
                                  <a className="text-teal-700 hover:underline" href={ticket.urls.edit}>
                                    #{ticket.id}
                                  </a>
                                ) : (
                                  <Link className="text-teal-700 hover:underline" to={`/cliente/ticket/${ticket.id}`}>
                                    #{ticket.id}
                                  </Link>
                                )}
                              </td>
                              <td className="px-4 py-4">{ticket.autor || '—'}</td>
                              <td className="px-4 py-4 text-slate-600">{ticket.created || ticket.atualizado || '—'}</td>
                              <td className="px-4 py-4">
                                <div className="max-w-xs font-medium text-slate-800">{ticket.assunto}</div>
                                {ticket.solicitacaoPreview && (
                                  <div className="text-xs text-slate-500 line-clamp-2">{ticket.solicitacaoPreview}</div>
                                )}
                              </td>
                              <td className="px-4 py-4">
                                <span
                                  className={`inline-flex rounded-full border px-3 py-1 text-xs font-semibold ${badgeClass(
                                    statusType(st)
                                  )}`}
                                >
                                  {st}
                                </span>
                              </td>
                              <td className="px-4 py-4">{ticket.cliente || '—'}</td>
                              <td className="px-4 py-4">
                                {(ticket.acoes || []).length === 0 ? (
                                  <span className="text-slate-400">—</span>
                                ) : (
                                  <div className="flex flex-wrap gap-1">
                                    {ticket.acoes.map((a) => (
                                      <a
                                        key={a.key + a.label}
                                        href={a.url}
                                        target={a.target || '_self'}
                                        rel={a.target === '_blank' ? 'noreferrer' : undefined}
                                        className="rounded-lg border border-slate-200 bg-slate-50 px-2 py-1 text-xs font-medium text-slate-700 hover:bg-teal-50"
                                        title={a.label}
                                      >
                                        {a.label}
                                      </a>
                                    ))}
                                  </div>
                                )}
                              </td>
                            </tr>
                          );
                        })
                      )}
                    </tbody>
                  </table>
                </div>
              </div>
            </section>

            {boot?.paths?.indexCliente && (
              <p className="text-center text-xs text-slate-400">
                <a href={boot.paths.indexCliente} className="text-teal-700 hover:underline">
                  Abrir visão cliente (mesma sessão)
                </a>
              </p>
            )}
          </div>
        </main>
      </div>
    </div>
  );
}
