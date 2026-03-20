import { useEffect, useState } from 'react';
import { fetchTicketDetail, postComentario, saveTicketSolicitacao } from '../lib/api';

export default function TechTicketEdit({ boot }) {
  const id = boot?.ticketId;
  const [ticket, setTicket] = useState(null);
  const [comentarios, setComentarios] = useState([]);
  const [texto, setTexto] = useState('');
  const [desc, setDesc] = useState('');
  const [enviando, setEnviando] = useState(false);
  const [salvando, setSalvando] = useState(false);
  const [erro, setErro] = useState(null);
  const [msg, setMsg] = useState(null);

  useEffect(() => {
    let c = false;
    (async () => {
      const res = await fetchTicketDetail(id);
      if (c) return;
      if (!res.ok) {
        setErro(res.error || 'Ticket não encontrado');
        return;
      }
      setTicket(res.data);
      setComentarios(res.data.comentarios || []);
      setDesc(res.data.descricao || '');
    })();
    return () => {
      c = true;
    };
  }, [id]);

  async function handleComentario(e) {
    e.preventDefault();
    const t = texto.trim();
    if (!t || !ticket) return;
    setEnviando(true);
    setErro(null);
    const res = await postComentario(ticket.id, t);
    setEnviando(false);
    if (res.ok) {
      setComentarios((prev) => [...prev, res.data]);
      setTexto('');
      setMsg('Comentário enviado.');
    } else {
      setErro('Não foi possível enviar o comentário.');
    }
  }

  async function handleSalvarDescricao(e) {
    e.preventDefault();
    if (!ticket?.flags?.canEditDescricao) return;
    setSalvando(true);
    setMsg(null);
    const res = await saveTicketSolicitacao(ticket.id, desc);
    setSalvando(false);
    if (res.ok) setMsg('Descrição salva.');
    else setErro(res.error || 'Falha ao salvar.');
  }

  if (erro && !ticket) {
    return (
      <div className="min-h-screen bg-slate-100 px-4 py-12 text-center">
        <p className="text-rose-700">{erro}</p>
        {boot?.paths?.indexTecnico && (
          <a href={boot.paths.indexTecnico} className="mt-4 inline-block text-teal-700 underline">
            Voltar à listagem
          </a>
        )}
      </div>
    );
  }

  if (!ticket) {
    return (
      <div className="min-h-screen bg-slate-100 px-4 py-12 text-center text-slate-500">
        Carregando ticket…
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-slate-100 text-slate-800">
      <header className="border-b border-slate-200 bg-white shadow-sm">
        <div className="mx-auto flex max-w-4xl flex-wrap items-center justify-between gap-3 px-4 py-4 sm:px-6">
          <div>
            {boot?.paths?.indexTecnico && (
              <a href={boot.paths.indexTecnico} className="text-sm font-medium text-teal-700 hover:underline">
                ← Tickets
              </a>
            )}
            <h1 className="mt-2 text-2xl font-bold text-slate-900">Ticket #{ticket.id}</h1>
            <p className="text-sm text-slate-500">
              {ticket.cliente} · {ticket.status}
            </p>
          </div>
          <div className="flex flex-wrap gap-2">
            {boot?.classicEditUrl && (
              <a
                href={boot.classicEditUrl}
                className="rounded-2xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50"
              >
                Formulário clássico (timer, anexos HTMX)
              </a>
            )}
            {ticket.urls?.imprimir && (
              <a
                href={ticket.urls.imprimir}
                target="_blank"
                rel="noreferrer"
                className="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
              >
                Imprimir
              </a>
            )}
          </div>
        </div>
      </header>

      <main className="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6">
        {msg && <p className="rounded-xl bg-emerald-50 px-4 py-2 text-sm text-emerald-800">{msg}</p>}
        {erro && <p className="rounded-xl bg-rose-50 px-4 py-2 text-sm text-rose-800">{erro}</p>}

        <section className="rounded-[24px] border border-slate-200 bg-white p-6 shadow-sm">
          <h2 className="text-lg font-bold text-slate-900">{ticket.assunto}</h2>
          {ticket.flags?.canEditDescricao ? (
            <form onSubmit={handleSalvarDescricao} className="mt-4 space-y-3">
              <label className="block text-sm font-medium text-slate-600">Descrição (admin)</label>
              <textarea
                value={desc}
                onChange={(e) => setDesc(e.target.value)}
                rows={8}
                className="w-full rounded-xl border border-slate-200 p-3 text-sm"
              />
              <button
                type="submit"
                disabled={salvando}
                className="rounded-xl bg-teal-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"
              >
                {salvando ? 'Salvando…' : 'Salvar descrição'}
              </button>
            </form>
          ) : (
            <div className="prose prose-sm mt-4 max-w-none whitespace-pre-wrap text-slate-700">{ticket.descricao}</div>
          )}
        </section>

        <section className="rounded-[24px] border border-slate-200 bg-white p-6 shadow-sm">
          <h2 className="text-lg font-bold text-slate-900">Anexos</h2>
          {ticket.anexos?.length ? (
            <ul className="mt-3 space-y-2">
              {ticket.anexos.map((a) => (
                <li key={a.id}>
                  <a href={a.url} className="text-teal-700 hover:underline" target="_blank" rel="noreferrer">
                    {a.nome}
                  </a>
                </li>
              ))}
            </ul>
          ) : (
            <p className="mt-2 text-sm text-slate-500">Nenhum anexo.</p>
          )}
        </section>

        <section className="rounded-[24px] border border-slate-200 bg-white p-6 shadow-sm">
          <h2 className="text-lg font-bold text-slate-900">Comentários</h2>
          <ul className="mt-4 space-y-3">
            {comentarios.map((c) => (
              <li
                key={c.id}
                className={`rounded-2xl border px-4 py-3 ${
                  c.papel === 'tecnico' ? 'border-cyan-200 bg-cyan-50/50' : 'border-slate-200 bg-slate-50/80'
                }`}
              >
                <div className="flex justify-between text-xs text-slate-500">
                  <span className="font-semibold text-slate-800">{c.autor}</span>
                  <span>{c.quando}</span>
                </div>
                <p className="mt-2 text-sm text-slate-700">{c.texto}</p>
              </li>
            ))}
          </ul>
          <form onSubmit={handleComentario} className="mt-6 space-y-3">
            <textarea
              value={texto}
              onChange={(e) => setTexto(e.target.value)}
              rows={4}
              placeholder="Novo comentário…"
              className="w-full rounded-xl border border-slate-200 p-3 text-sm"
            />
            <button
              type="submit"
              disabled={enviando}
              className="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"
            >
              {enviando ? 'Enviando…' : 'Enviar'}
            </button>
          </form>
        </section>
      </main>
    </div>
  );
}
