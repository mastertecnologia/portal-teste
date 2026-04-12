import { useDeferredValue, useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { fetchTicketsCliente, USE_MOCK } from '../lib/api';
import { MOCK_SESSION_CLIENTE } from '../data/mockData';
import { acaoLinkClassName, badgeClass, sortTicketAcoes, statusType } from '../lib/ticketUi';
import { stripHtml } from '../lib/text';

const FILA_TO_API = {
  todos: 'todos',
  ativos: 'ativos',
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
  const [fila, setFila] = useState('ativos');
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

  useEffect(() => {
    if (!embedded || !boot?.servicedesk) return undefined;
    const id = window.setInterval(async () => {
      if (document.visibilityState !== 'visible') return;
      const res = await fetchTicketsCliente({
        fila: FILA_TO_API[fila] || 'todos',
        assunto:
          boot?.queryAssunto !== undefined && boot?.queryAssunto !== null && String(boot.queryAssunto) !== ''
            ? String(boot.queryAssunto)
            : undefined,
      });
      if (res.ok) setTickets(res.data);
    }, 10_000);
    return () => clearInterval(id);
  }, [embedded, boot?.servicedesk, boot?.queryAssunto, fila]);

  const fromApiRows = useMemo(() => {
    if (!USE_MOCK) return tickets;
    let list = tickets;
    const st = (x) => x.status;
    if (fila === 'ativos') {
      list = list.filter(
        (t) =>
          st(t) === 'Aguardando técnico' || st(t) === 'Em execução' || st(t) === 'Em andamento'
      );
    } else if (fila === 'pendente') {
      list = list.filter((t) => st(t) === 'Aguardando técnico');
    } else if (fila === 'execucao') {
      list = list.filter((t) => st(t) === 'Em execução' || st(t) === 'Em andamento');
    } else if (fila === 'resolvido') {
      list = list.filter((t) => st(t) === 'Resolvido');
    } else if (fila === 'fechados') {
      list = list.filter((t) => st(t) === 'Cancelado' || st(t) === 'Fechado');
    }
    return list.map((t) => ({
      id: t.id,
      autor: MOCK_SESSION_CLIENTE.name,
      created: t.atualizado || '—',
      assunto: t.assunto,
      solicitacaoPreview: t.descricao,
      situacaoLabel: t.status,
      status: t.status,
      cliente: t.cliente,
      tecnicos: t.tecnicos ?? t.responsavel ?? '—',
      urls: { view: `/cliente/ticket/${t.id}` },
      acoes: [],
    }));
  }, [tickets, fila]);

  const deferredQ = useDeferredValue(q);

  const rows = useMemo(() => {
    const qq = deferredQ.trim().toLowerCase();
    if (!qq) return fromApiRows;
    return fromApiRows.filter((t) => {
      const id = String(t.id);
      const cliente = String(t.cliente || '').toLowerCase();
      const assunto = String(t.assunto || '').toLowerCase();
      const autor = String(t.autor || '').toLowerCase();
      const tec = String(t.tecnicos || '').toLowerCase();
      return id.includes(qq) || cliente.includes(qq) || assunto.includes(qq) || autor.includes(qq) || tec.includes(qq);
    });
  }, [fromApiRows, deferredQ]);

  const totalFila = fromApiRows.length;

  const tableSection = (
    <section
      className={
        embedded
          ? 'rounded-xl border border-[var(--pgm-border)] bg-[var(--pgm-bg-surface)] shadow-[0_4px_20px_rgba(0,0,0,0.35)]'
          : 'rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm'
      }
    >
      <div
        className={
          embedded
            ? 'border-b border-[var(--pgm-border)] p-3'
            : 'flex flex-col gap-4 border-b border-slate-100 pb-4 lg:flex-row lg:items-center lg:justify-between'
        }
      >
        {embedded ? (
          <div className="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between lg:gap-4">
            <div className="min-w-0 flex-1">
              <h2 className="text-lg font-bold leading-tight text-[var(--pgm-text)]">
                {boot?.servicedesk ? 'Service Desk — meus chamados' : 'Tickets — cliente'}
              </h2>
              <p className="mt-0.5 text-xs text-[var(--pgm-text-muted)]">
                {boot?.servicedesk
                  ? `Atualização a cada 10 s · ${totalFila} ticket(s) neste filtro`
                  : `Fila · ${totalFila} ticket(s) neste filtro`}
              </p>
            </div>
            <div className="flex w-full flex-col gap-2 sm:flex-row sm:items-center lg:w-auto lg:max-w-xl">
              <input
                value={q}
                onChange={(e) => setQ(e.target.value)}
                placeholder="Buscar nº, cliente ou assunto"
                className="h-9 w-full min-w-0 flex-1 rounded-lg border border-[var(--pgm-border)] bg-[var(--pgm-bg-elevated)] px-2.5 text-sm text-[var(--pgm-text)] outline-none placeholder:text-[var(--pgm-text-muted)] focus:border-[var(--pgm-primary)] sm:min-w-[180px]"
              />
              <select
                value={fila}
                onChange={(e) => setFila(e.target.value)}
                className="h-9 w-full shrink-0 rounded-lg border border-[var(--pgm-border)] bg-[var(--pgm-bg-elevated)] px-2.5 text-sm text-[var(--pgm-text)] outline-none focus:border-[var(--pgm-primary)] sm:w-44"
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
        ) : (
          <>
            <div>
              <h3 className="text-lg font-bold text-slate-900">Fila</h3>
              <p className="text-sm text-slate-500">{totalFila} ticket(s) · seus chamados na empresa</p>
            </div>
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
                <option value="ativos">Aguardando + Em execução</option>
                <option value="pendente">Aguardando técnico</option>
                <option value="execucao">Em execução</option>
                <option value="resolvido">Resolvidos</option>
                <option value="fechados">Cancelados / fechados</option>
              </select>
            </div>
          </>
        )}
      </div>

      <div
        className={
          embedded ? 'overflow-hidden' : 'mt-5 overflow-hidden rounded-2xl border border-slate-200'
        }
      >
        <div className="overflow-x-auto">
          <table
            className={
              embedded
                ? 'min-w-full divide-y divide-[var(--pgm-border)] text-xs text-[var(--pgm-text-muted)] sm:text-sm'
                : 'min-w-full divide-y divide-slate-200 text-xs sm:text-sm'
            }
          >
            <thead
              className={
                embedded
                  ? 'bg-[var(--pgm-bg-elevated)] text-left text-xs text-[var(--pgm-text-muted)]'
                  : 'bg-slate-50 text-left text-xs text-slate-500'
              }
            >
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
            <tbody
              className={
                embedded
                  ? 'divide-y divide-[var(--pgm-border)] bg-[var(--pgm-bg-surface)]'
                  : 'divide-y divide-slate-100 bg-white'
              }
            >
              {loading ? (
                <tr>
                  <td
                    colSpan={8}
                    className={`px-4 py-8 text-center ${embedded ? 'text-[var(--pgm-text-muted)]' : 'text-slate-500'}`}
                  >
                    Carregando…
                  </td>
                </tr>
              ) : rows.length === 0 ? (
                <tr>
                  <td
                    colSpan={8}
                    className={`px-4 py-8 text-center ${embedded ? 'text-[var(--pgm-text-muted)]' : 'text-slate-500'}`}
                  >
                    Nenhum ticket neste filtro.
                  </td>
                </tr>
              ) : (
                rows.map((ticket) => {
                  const st = statusLabel(ticket);
                  const assuntoLinha = stripHtml(ticket.assunto);
                  const dest = ticket.urls?.view || `/cliente/ticket/${ticket.id}`;
                  const acoesOrd = sortTicketAcoes(ticket.acoes || []);
                  const idLinkCls = embedded
                    ? 'text-[var(--pgm-primary-hover)] hover:text-[var(--pgm-primary)] hover:underline'
                    : 'text-cyan-700 hover:underline';
                  const rowHover = embedded ? 'hover:bg-[var(--pgm-bg-elevated)]' : 'hover:bg-slate-50/80';
                  return (
                    <tr key={ticket.id} className={`align-middle transition ${rowHover}`}>
                      <td className="px-2 py-1.5 font-semibold sm:px-3">
                        {ticket.urls?.view ? (
                          <a className={idLinkCls} href={dest}>
                            #{ticket.id}
                          </a>
                        ) : (
                          <Link className={idLinkCls} to={dest}>
                            #{ticket.id}
                          </Link>
                        )}
                      </td>
                      <td className="max-w-[7rem] truncate px-2 py-1.5 sm:px-3" title={ticket.autor || ''}>
                        {ticket.autor || '—'}
                      </td>
                      <td
                        className={`whitespace-nowrap px-2 py-1.5 sm:px-3 ${embedded ? 'text-[var(--pgm-text-muted)]' : 'text-slate-600'}`}
                      >
                        {ticket.created || '—'}
                      </td>
                      <td className="max-w-[14rem] px-2 py-1.5 sm:max-w-xs sm:px-3">
                        <div
                          className={`truncate font-medium ${embedded ? 'text-[var(--pgm-text)]' : 'text-slate-800'}`}
                          title={assuntoLinha}
                        >
                          {assuntoLinha}
                        </div>
                        {ticket.solicitacaoPreview ? (
                          <div
                            className={`line-clamp-1 text-[11px] leading-tight ${embedded ? 'text-[var(--pgm-text-muted)]' : 'text-slate-500'}`}
                            title={ticket.solicitacaoPreview}
                          >
                            {ticket.solicitacaoPreview}
                          </div>
                        ) : null}
                      </td>
                      <td className="whitespace-nowrap px-2 py-1.5 sm:px-3">
                        <span
                          className={`inline-flex max-w-[10rem] truncate rounded-full border px-2 py-0.5 text-[10px] font-semibold leading-tight sm:max-w-[12rem] sm:text-xs ${badgeClass(
                            statusType(st),
                            embedded,
                            Boolean(boot?.servicedesk)
                          )}`}
                          title={st}
                        >
                          {st}
                        </span>
                      </td>
                      <td
                        className={`max-w-[7rem] truncate px-2 py-1.5 sm:px-3 ${embedded ? 'text-[var(--pgm-text-secondary)]' : 'text-slate-700'}`}
                        title={ticket.tecnicos || ''}
                      >
                        {ticket.tecnicos && ticket.tecnicos !== '—' ? ticket.tecnicos : '—'}
                      </td>
                      <td className="max-w-[8rem] truncate px-2 py-1.5 sm:px-3" title={ticket.cliente || ''}>
                        {ticket.cliente || '—'}
                      </td>
                      <td className="px-2 py-1 sm:px-3">
                        {acoesOrd.length === 0 ? (
                          <span className={embedded ? 'text-[var(--pgm-text-muted)]' : 'text-slate-400'}>—</span>
                        ) : (
                          <div className="flex max-w-[42vw] flex-nowrap items-center gap-0.5 overflow-x-auto py-0.5 sm:max-w-none sm:overflow-visible [scrollbar-width:thin]">
                            {acoesOrd.map((a) => (
                              <a
                                key={a.key + a.label}
                                href={a.url}
                                target={a.target || '_self'}
                                rel={a.target === '_blank' ? 'noreferrer' : undefined}
                                className={acaoLinkClassName(a.key, embedded, Boolean(boot?.servicedesk))}
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
    return <div className="tickets-react-client w-full text-[var(--pgm-text)]">{tableSection}</div>;
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
          {USE_MOCK ? (
            <div className="flex flex-wrap gap-2">
              <Link
                to="/tecnico"
                className="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-100"
              >
                Painel técnico (demo)
              </Link>
            </div>
          ) : null}
        </div>
      </header>

      <main className="mx-auto max-w-6xl space-y-4 px-4 py-6 sm:px-6">{tableSection}</main>
    </div>
  );
}
