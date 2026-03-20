import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { fetchTicketsTecnico, USE_MOCK } from '../lib/api';
import { acaoLinkClassName, badgeClass, sortTicketAcoes, statusType } from '../lib/ticketUi';
import { MOCK_SESSION_TECNICO } from '../data/mockData';

const GROUP_KEYS = {
  todos: 'todos',
  pendente: 'pendentes',
  execucao: 'emandamento',
  resolvido: 'resolvidos',
  fechados: 'fechados',
};

/** Legado pode mandar HTML em situacaoLabel até o servidor atualizar — remove tags. */
function stripHtml(raw) {
  if (raw == null) return '—';
  const s = String(raw);
  const t = s.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
  return t || '—';
}

function statusLabel(row) {
  return stripHtml(row.situacaoLabel || row.status);
}

export default function TechDashboard({ boot }) {
  const embedded = Boolean(boot);
  const [groups, setGroups] = useState(null);
  const [q, setQ] = useState('');
  const [filtroStatus, setFiltroStatus] = useState('ativos');

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
    let base;
    if (filtroStatus === 'ativos') {
      const a = groups.pendentes || [];
      const b = groups.emandamento || [];
      const seen = new Set();
      base = [...a, ...b].filter((t) => {
        if (seen.has(t.id)) return false;
        seen.add(t.id);
        return true;
      });
      base.sort((x, y) => Number(y.id) - Number(x.id));
    } else {
      const key = GROUP_KEYS[filtroStatus] || 'todos';
      base = groups[key] || [];
    }
    const qq = q.trim().toLowerCase();
    if (!qq) return base;
    return base.filter((t) => {
      const id = String(t.id);
      const cliente = String(t.cliente || '').toLowerCase();
      const assunto = String(t.assunto || '').toLowerCase();
      const tec = String(t.tecnicos || '').toLowerCase();
      return id.includes(qq) || cliente.includes(qq) || assunto.includes(qq) || tec.includes(qq);
    });
  }, [groups, filtroStatus, q]);

  const totalTodos = groups?.todos?.length ?? 0;
  const hoje = new Date().toLocaleDateString('pt-BR');

  const dash = boot?.paths?.dashboard;
  const addTicket = boot?.paths?.addTicket;

  const tableSection = (
    <section
      className={
        embedded
          ? 'rounded-lg border border-slate-200 bg-white shadow-sm'
          : 'rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm'
      }
    >
      <div
        className={
          embedded
            ? 'flex flex-col gap-2 border-b border-slate-100 p-3 sm:flex-row sm:items-center sm:justify-between'
            : 'flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between'
        }
      >
        {!embedded && (
          <div>
            <h3 className="text-lg font-bold text-slate-900">Fila</h3>
            <p className="text-sm text-slate-500">
              {totalTodos} ticket(s) na empresa · integração JSON ativa quando embutido no CakePHP
            </p>
          </div>
        )}
        {embedded && (
          <div className="min-w-0 flex-1">
            <h3 className="text-base font-bold text-slate-900">Fila</h3>
            <p className="text-xs text-slate-500">{totalTodos} ticket(s) na empresa</p>
          </div>
        )}
        <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
          <input
            value={q}
            onChange={(e) => setQ(e.target.value)}
            placeholder="Buscar nº, cliente ou assunto"
            className="h-9 w-full min-w-[180px] rounded-lg border border-slate-200 bg-slate-50 px-2.5 text-sm outline-none placeholder:text-slate-400 focus:border-teal-500 sm:max-w-xs"
          />
          <select
            value={filtroStatus}
            onChange={(e) => setFiltroStatus(e.target.value)}
            className="h-9 rounded-lg border border-slate-200 bg-slate-50 px-2.5 text-sm outline-none focus:border-teal-500"
          >
            <option value="todos">Todos</option>
            <option value="ativos">Aguardando + Em execução</option>
            <option value="pendente">Aguardando técnico</option>
            <option value="execucao">Em execução</option>
            <option value="resolvido">Resolvidos</option>
            <option value="fechados">Cancelados / fechados</option>
          </select>
        </div>
      </div>

      <div className={embedded ? 'overflow-hidden' : 'mt-5 overflow-hidden rounded-2xl border border-slate-200'}>
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-slate-200 text-xs sm:text-sm">
            <thead className="bg-slate-50 text-left text-xs text-slate-500">
              <tr>
                <th className="px-2 py-1.5 font-semibold sm:px-3">Ticket</th>
                <th className="max-w-[7rem] px-2 py-1.5 font-semibold sm:px-3">Autor</th>
                <th className="whitespace-nowrap px-2 py-1.5 font-semibold sm:px-3">Data</th>
                <th className="min-w-[8rem] px-2 py-1.5 font-semibold sm:min-w-[10rem] sm:px-3">Assunto</th>
                <th className="whitespace-nowrap px-2 py-1.5 font-semibold sm:px-3">Status</th>
                <th className="max-w-[7rem] px-2 py-1.5 font-semibold sm:px-3">Técnico</th>
                <th className="max-w-[8rem] px-2 py-1.5 font-semibold sm:px-3">Cliente</th>
                <th className="min-w-[14rem] px-2 py-1.5 font-semibold sm:min-w-[17rem] sm:px-3">Ações</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 bg-white">
              {rows.length === 0 ? (
                <tr>
                  <td colSpan={8} className="px-4 py-8 text-center text-slate-500">
                    Nenhum ticket neste filtro.
                  </td>
                </tr>
              ) : (
                rows.map((ticket) => {
                  const st = statusLabel(ticket);
                  const assuntoLinha = stripHtml(ticket.assunto);
                  const acoesOrd = sortTicketAcoes(ticket.acoes || []);
                  return (
                    <tr key={ticket.id} className="align-middle transition hover:bg-slate-50/80">
                      <td className="px-2 py-1.5 font-semibold sm:px-3">
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
                      <td className="max-w-[7rem] truncate px-2 py-1.5 sm:px-3" title={ticket.autor || ''}>
                        {ticket.autor || '—'}
                      </td>
                      <td className="whitespace-nowrap px-2 py-1.5 text-slate-600 sm:px-3">
                        {ticket.created || ticket.atualizado || '—'}
                      </td>
                      <td className="max-w-[14rem] px-2 py-1.5 sm:max-w-xs sm:px-3">
                        <div className="truncate font-medium text-slate-800" title={assuntoLinha}>
                          {assuntoLinha}
                        </div>
                        {ticket.solicitacaoPreview ? (
                          <div className="line-clamp-1 text-[11px] leading-tight text-slate-500" title={ticket.solicitacaoPreview}>
                            {ticket.solicitacaoPreview}
                          </div>
                        ) : null}
                      </td>
                      <td className="whitespace-nowrap px-2 py-1.5 sm:px-3">
                        <span
                          className={`inline-flex max-w-[10rem] truncate rounded-full border px-2 py-0.5 text-[10px] font-semibold leading-tight sm:max-w-[12rem] sm:text-xs ${badgeClass(
                            statusType(st)
                          )}`}
                          title={st}
                        >
                          {st}
                        </span>
                      </td>
                      <td className="max-w-[7rem] truncate px-2 py-1.5 text-slate-700 sm:px-3" title={ticket.tecnicos || ''}>
                        {ticket.tecnicos && ticket.tecnicos !== '—' ? ticket.tecnicos : '—'}
                      </td>
                      <td className="max-w-[8rem] truncate px-2 py-1.5 sm:px-3" title={ticket.cliente || ''}>
                        {ticket.cliente || '—'}
                      </td>
                      <td className="px-2 py-1 sm:px-3">
                        {acoesOrd.length === 0 ? (
                          <span className="text-slate-400">—</span>
                        ) : (
                          <div className="flex max-w-[42vw] flex-nowrap items-center gap-0.5 overflow-x-auto py-0.5 sm:max-w-none sm:overflow-visible [scrollbar-width:thin]">
                            {acoesOrd.map((a) => (
                              <a
                                key={a.key + a.label}
                                href={a.url}
                                target={a.target || '_self'}
                                rel={a.target === '_blank' ? 'noreferrer' : undefined}
                                className={acaoLinkClassName(a.key)}
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
  );

  if (embedded) {
    return (
      <div className="tickets-react-tech w-full text-slate-800">
        <div className="mb-2 flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 pb-2">
          <div>
            <h2 className="text-lg font-bold text-slate-900">Tickets — técnico</h2>
            <p className="text-xs text-slate-500">Listagem e ações usam as mesmas URLs do portal.</p>
          </div>
          <div className="flex flex-wrap items-center gap-2">
            {dash && (
              <a href={dash} className="text-sm font-medium text-slate-600 hover:text-teal-700">
                Dashboard
              </a>
            )}
            {addTicket && (
              <a
                href={addTicket}
                className="rounded-lg bg-teal-700 px-3 py-1.5 text-sm font-semibold text-white hover:bg-teal-800"
              >
                Abrir ticket
              </a>
            )}
            {boot?.paths?.indexCliente && (
              <a href={boot.paths.indexCliente} className="text-sm text-slate-600 hover:text-teal-700">
                Visão cliente
              </a>
            )}
          </div>
        </div>
        {tableSection}
      </div>
    );
  }

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
            <Link
              to="/"
              className="flex w-full items-center justify-between rounded-2xl px-4 py-3 text-left text-teal-50/90 transition hover:bg-white/10"
            >
              <span className="font-medium">← Início</span>
            </Link>
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
                <p className="text-sm font-medium text-teal-700">{MOCK_SESSION_TECNICO.empresa}</p>
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
                <span className="rounded-2xl border border-slate-200 px-5 py-3 text-sm text-slate-500">Abrir ticket (portal)</span>
              </div>
            </div>
          </header>

          <div className="space-y-6 p-4 sm:p-6">
            {tableSection}
            <p className="text-center text-xs text-slate-400">Modo demonstração — use Vite em localhost.</p>
          </div>
        </main>
      </div>
    </div>
  );
}
