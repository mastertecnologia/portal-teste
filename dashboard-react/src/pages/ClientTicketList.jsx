import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { fetchTicketsCliente, USE_MOCK } from '../lib/api';
import { MOCK_SESSION_CLIENTE } from '../data/mockData';
import { badgeClass, priorityType, statusType } from '../lib/ticketUi';

export default function ClientTicketList({ boot }) {
  const [tickets, setTickets] = useState([]);
  const [loading, setLoading] = useState(true);
  const [assunto, setAssunto] = useState(
    boot?.queryAssunto !== undefined && boot?.queryAssunto !== null ? String(boot.queryAssunto) : ''
  );
  const [situacao, setSituacao] = useState(
    boot?.querySituacao !== undefined && boot?.querySituacao !== null ? String(boot.querySituacao) : ''
  );

  useEffect(() => {
    let c = false;
    (async () => {
      const res = await fetchTicketsCliente({
        assunto: assunto || undefined,
        situacao: situacao !== '' ? situacao : undefined,
      });
      if (!c) {
        if (res.ok) setTickets(res.data);
        setLoading(false);
      }
    })();
    return () => {
      c = true;
    };
  }, [assunto, situacao]);

  const indexHref = boot?.paths?.indexCliente || '/cliente';
  const addHref = boot?.paths?.addTicket;

  return (
    <div className="min-h-screen bg-slate-100 text-slate-800">
      <header className="border-b border-slate-200 bg-white shadow-sm">
        <div className="mx-auto flex max-w-5xl flex-col gap-4 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
          <div>
            {!boot && (
              <Link to="/" className="text-sm font-medium text-cyan-700 hover:underline">
                ← Início
              </Link>
            )}
            <h1 className="mt-2 text-2xl font-bold text-slate-900">Meus tickets</h1>
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

      <div className="mx-auto max-w-5xl px-4 py-4 sm:px-6">
        {boot && (
          <div className="mb-4 flex flex-wrap gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <label className="flex flex-col text-xs font-medium text-slate-600">
              Assunto (código)
              <input
                value={assunto}
                onChange={(e) => setAssunto(e.target.value)}
                className="mt-1 h-9 rounded-lg border border-slate-200 px-2 text-sm"
                placeholder="Filtrar"
              />
            </label>
            <label className="flex flex-col text-xs font-medium text-slate-600">
              Situação (-1 = todos)
              <input
                value={situacao}
                onChange={(e) => setSituacao(e.target.value)}
                className="mt-1 h-9 w-28 rounded-lg border border-slate-200 px-2 text-sm"
                placeholder="-1"
              />
            </label>
            <a
              href={indexHref}
              className="self-end rounded-xl border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            >
              Limpar (recarregar)
            </a>
          </div>
        )}
      </div>

      <main className="mx-auto max-w-5xl px-4 py-4 sm:px-6">
        {loading ? (
          <p className="text-center text-slate-500">Carregando…</p>
        ) : tickets.length === 0 ? (
          <div className="rounded-[28px] border border-slate-200 bg-white p-10 text-center shadow-sm">
            <p className="text-slate-600">Nenhum ticket encontrado.</p>
          </div>
        ) : (
          <ul className="space-y-4">
            {tickets.map((t) => {
              const dest = t.urls?.view || `/cliente/ticket/${t.id}`;
              const inner = (
                <>
                  <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                      <span className="text-lg font-bold text-slate-900">#{t.id}</span>
                      <span className="ml-2 text-sm text-slate-500">{t.created || t.atualizado}</span>
                      <h2 className="mt-2 font-semibold text-slate-800">{t.assunto}</h2>
                      <p className="mt-1 line-clamp-2 text-sm text-slate-600">{t.descricao}</p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                      {t.prioridade && (
                        <span
                          className={`inline-flex rounded-full border px-3 py-1 text-xs font-semibold ${badgeClass(
                            priorityType(t.prioridade)
                          )}`}
                        >
                          {t.prioridade}
                        </span>
                      )}
                      <span
                        className={`inline-flex rounded-full border px-3 py-1 text-xs font-semibold ${badgeClass(
                          statusType(t.status)
                        )}`}
                      >
                        {t.status}
                      </span>
                    </div>
                  </div>
                </>
              );
              return (
                <li key={t.id}>
                  {t.urls?.view ? (
                    <a
                      href={dest}
                      className="block rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm transition hover:border-cyan-300 hover:shadow-md"
                    >
                      {inner}
                    </a>
                  ) : (
                    <Link
                      to={dest}
                      className="block rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm transition hover:border-cyan-300 hover:shadow-md"
                    >
                      {inner}
                    </Link>
                  )}
                </li>
              );
            })}
          </ul>
        )}
      </main>
    </div>
  );
}
