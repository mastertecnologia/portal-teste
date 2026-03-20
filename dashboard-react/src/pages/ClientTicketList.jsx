import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { fetchTicketsCliente, USE_MOCK } from '../lib/api';
import { MOCK_SESSION_CLIENTE } from '../data/mockData';
import { badgeClass, statusType } from '../lib/ticketUi';
import { stripHtml } from '../lib/text';

const FILA_TO_API = {
  todos: 'todos',
  pendente: 'pendente',
  execucao: 'execucao',
  resolvido: 'resolvido',
  fechados: 'fechados',
};

function statusLabel(row) {
  return stripHtml(row.situacaoLabel || row.status || '—');
}

export default function ClientTicketList({ boot }) {
  const embedded = Boolean(boot);
  const [tickets, setTickets] = useState([]);
  const [loading, setLoading] = useState(true);
  const [fila, setFila] = useState('pendente');
  const [q, setQ] = useState('');

  useEffect(() => {
    let c = false;
    (async () => {
      setLoading(true);
      const res = await fetchTicketsCliente({
        fila: FILA_TO_API[fila] || 'todos',
        assunto:
          boot?.queryAssunto !== undefined && boot?.queryAssunto !== null && String(boot.queryAssunto) !== ''
            ? String(boot.queryAssunto)
            : undefined,
      });
      if (!c) {
        if (res.ok) setTickets(res.data);
        setLoading(false);
      }
    })();
    return () => {
      c = true;
    };
  }, [fila, boot?.queryAssunto]);

  const fromApiRows = useMemo(() => {
    if (!USE_MOCK) return tickets;
    return tickets.map((t) => ({
      id: t.id,
      autor: MOCK_SESSION_CLIENTE.name,
      created: t.atualizado || '—',
      assunto: t.assunto,
      solicitacaoPreview: t.descricao,
      situacaoLabel: t.status,
      status: t.status,
      cliente: t.cliente,
      urls: { view: `/cliente/ticket/${t.id}` },
      acoes: [],
    }));
  }, [tickets]);

  const rows = useMemo(() => {
    const qq = q.trim().toLowerCase();
    if (!qq) return fromApiRows;
    return fromApiRows.filter((t) => {
      const id = String(t.id);
      const cliente = String(t.cliente || '').toLowerCase();
      const assunto = String(t.assunto || '').toLowerCase();
      return id.includes(qq) || cliente.includes(qq) || assunto.includes(qq);
    });
  }, [fromApiRows, q]);

  const totalFila = fromApiRows.length;
  const addHref = boot?.paths?.addTicket;

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
            ? 'flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between'
            : 'flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between'
        }
      >
        {!embedded && (
          <div>
            <h3 className="text-lg font-bold text-slate-900">Fila</h3>
            <p className="text-sm text-slate-500">{totalFila} ticket(s) · seus chamados na empresa</p>
          </div>
        )}
        {embedded && (
          <div className="min-w-0 flex-1">
            <h3 className="text-base font-bold text-slate-900">Fila</h3>
            <p className="text-xs text-slate-500">{totalFila} ticket(s) neste filtro</p>
          </div>
        )}
        <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
          <input
            value={q}
            onChange={(e) => setQ(e.target.value)}
            placeholder="Buscar nº, cliente ou assunto"
            className="h-10 w-full min-w-[200px] rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm outline-none placeholder:text-slate-400 focus:border-cyan-500 sm:max-w-xs"
          />
          <select
            value={fila}
            onChange={(e) => setFila(e.target.value)}
            className="h-10 rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm outline-none focus:border-cyan-500"
          >
            <option value="todos">Todos</option>
            <option value="pendente">Aguardando técnico</option>
            <option value="execucao">Em execução</option>
            <option value="resolvido">Resolvidos</option>
            <option value="fechados">Cancelados / fechados</option>
          </select>
        </div>
      </div>

      <div className={embedded ? 'overflow-hidden' : 'mt-5 overflow-hidden rounded-2xl border border-slate-200'}>
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-slate-200 text-sm">
            <thead className="bg-slate-50 text-left text-slate-500">
              <tr>
                <th className="px-3 py-2.5 font-semibold sm:px-4">Ticket</th>
                <th className="px-3 py-2.5 font-semibold sm:px-4">Autor</th>
                <th className="px-3 py-2.5 font-semibold sm:px-4">Data</th>
                <th className="px-3 py-2.5 font-semibold sm:px-4">Assunto</th>
                <th className="px-3 py-2.5 font-semibold sm:px-4">Status</th>
                <th className="px-3 py-2.5 font-semibold sm:px-4">Cliente</th>
                <th className="px-3 py-2.5 font-semibold sm:px-4">Ações</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 bg-white">
              {loading ? (
                <tr>
                  <td colSpan={7} className="px-4 py-8 text-center text-slate-500">
                    Carregando…
                  </td>
                </tr>
              ) : rows.length === 0 ? (
                <tr>
                  <td colSpan={7} className="px-4 py-8 text-center text-slate-500">
                    Nenhum ticket neste filtro.
                  </td>
                </tr>
              ) : (
                rows.map((ticket) => {
                  const st = statusLabel(ticket);
                  const assuntoLinha = stripHtml(ticket.assunto);
                  const dest = ticket.urls?.view || `/cliente/ticket/${ticket.id}`;
                  return (
                    <tr key={ticket.id} className="transition hover:bg-slate-50/80">
                      <td className="px-3 py-3 font-semibold sm:px-4">
                        {ticket.urls?.view ? (
                          <a className="text-cyan-700 hover:underline" href={dest}>
                            #{ticket.id}
                          </a>
                        ) : (
                          <Link className="text-cyan-700 hover:underline" to={dest}>
                            #{ticket.id}
                          </Link>
                        )}
                      </td>
                      <td className="max-w-[140px] truncate px-3 py-3 sm:px-4" title={ticket.autor || ''}>
                        {ticket.autor || '—'}
                      </td>
                      <td className="whitespace-nowrap px-3 py-3 text-slate-600 sm:px-4">
                        {ticket.created || '—'}
                      </td>
                      <td className="px-3 py-3 sm:px-4">
                        <div className="max-w-xs font-medium text-slate-800">{assuntoLinha}</div>
                        {ticket.solicitacaoPreview && (
                          <div className="line-clamp-2 text-xs text-slate-500">{ticket.solicitacaoPreview}</div>
                        )}
                      </td>
                      <td className="px-3 py-3 sm:px-4">
                        <span
                          className={`inline-flex rounded-full border px-2.5 py-0.5 text-xs font-semibold ${badgeClass(
                            statusType(st)
                          )}`}
                        >
                          {st}
                        </span>
                      </td>
                      <td className="max-w-[160px] truncate px-3 py-3 sm:px-4" title={ticket.cliente || ''}>
                        {ticket.cliente || '—'}
                      </td>
                      <td className="px-3 py-3 sm:px-4">
                        {(ticket.acoes || []).length === 0 ? (
                          <span className="text-slate-400">—</span>
                        ) : (
                          <div className="flex max-w-[200px] flex-wrap gap-1">
                            {ticket.acoes.map((a) => (
                              <a
                                key={a.key + a.label}
                                href={a.url}
                                target={a.target || '_self'}
                                rel={a.target === '_blank' ? 'noreferrer' : undefined}
                                className="rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[11px] font-medium text-slate-700 hover:bg-cyan-50"
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
      <div className="tickets-react-client w-full text-slate-800">
        <div className="mb-3 flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 pb-3">
          <div>
            <h2 className="text-lg font-bold text-slate-900">Tickets — cliente</h2>
          </div>
          <div className="flex flex-wrap items-center gap-2">
            {addHref && (
              <a
                href={addHref}
                className="rounded-lg bg-gradient-to-r from-cyan-600 to-teal-600 px-3 py-1.5 text-sm font-semibold text-white hover:opacity-95"
              >
                Abrir chamado
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
      <header className="border-b border-slate-200 bg-white shadow-sm">
        <div className="mx-auto flex max-w-6xl flex-col gap-4 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
          <div>
            <Link to="/" className="text-sm font-medium text-cyan-700 hover:underline">
              ← Início
            </Link>
            <h1 className="mt-2 text-2xl font-bold text-slate-900">Tickets — cliente</h1>
            <p className="text-sm text-slate-500">
              {USE_MOCK ? (
                <>
                  Olá, <span className="font-semibold text-slate-700">{MOCK_SESSION_CLIENTE.name}</span>
                </>
              ) : (
                'Chamados da sua conta na empresa atual.'
              )}
            </p>
          </div>
          <div className="flex flex-wrap gap-2">
            {USE_MOCK && (
              <Link
                to="/tecnico"
                className="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-100"
              >
                Painel técnico (demo)
              </Link>
            )}
            {addHref ? (
              <a
                href={addHref}
                className="rounded-2xl bg-gradient-to-r from-cyan-600 to-teal-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md hover:opacity-95"
              >
                Abrir novo chamado
              </a>
            ) : (
              <Link
                to="/cliente"
                className="rounded-2xl bg-gradient-to-r from-cyan-600 to-teal-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md hover:opacity-95"
              >
                Abrir novo chamado
              </Link>
            )}
          </div>
        </div>
      </header>

      <main className="mx-auto max-w-6xl space-y-4 px-4 py-6 sm:px-6">{tableSection}</main>
    </div>
  );
}
