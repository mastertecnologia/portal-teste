import { useCallback, useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import {
  fetchTicketsTecnico,
  fetchTecnicosParaTransferencia,
  fetchQueuesForTicket,
  postTransferirTicket,
  postStartTicket,
  USE_MOCK,
} from '../lib/api';
import { acaoLinkClassName, badgeClass, sortTicketAcoes, statusType } from '../lib/ticketUi';
import { MOCK_SESSION_TECNICO } from '../data/mockData';

const API_ERR_TRANSFER = {
  escalacao_invalida: 'Só é possível transferir para uma fila de nível superior (escalonamento).',
  mesma_fila: 'Selecione uma fila diferente da atual.',
  sem_permissao_transferir_fila: 'Você não está vinculado a esta fila ou seu nível não permite esta ação.',
  destino_sem_vinculo_fila: 'O técnico de destino não está vinculado à fila indicada.',
  destino_nivel_incompativel: 'O nível do técnico de destino não cobre essa fila.',
  motivo_obrigatorio: 'Informe o motivo (mín. 3 caracteres).',
};

const API_ERR_START = {
  sem_permissao_fila: 'Você não pode assumir este ticket: verifique vínculo com a fila e nível de suporte.',
};

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
  const [workflow, setWorkflow] = useState(null);
  const [q, setQ] = useState('');
  const [filtroStatus, setFiltroStatus] = useState('ativos');
  const [filaSuporte, setFilaSuporte] = useState('');
  const [nivelAtendimento, setNivelAtendimento] = useState('');
  const [semResponsavel, setSemResponsavel] = useState(false);
  const [somenteTransferidos, setSomenteTransferidos] = useState(false);
  const [idResponsavel, setIdResponsavel] = useState('');
  const [queueDbFilter, setQueueDbFilter] = useState('');
  const [tecnicosOpcoes, setTecnicosOpcoes] = useState([]);

  const [transferOpen, setTransferOpen] = useState(false);
  const [transferTicket, setTransferTicket] = useState(null);
  const [transferDest, setTransferDest] = useState('');
  const [transferMotivo, setTransferMotivo] = useState('');
  const [transferFila, setTransferFila] = useState('');
  const [transferQueues, setTransferQueues] = useState([]);
  const [transferQueueId, setTransferQueueId] = useState('');
  const [tecnicosModal, setTecnicosModal] = useState([]);
  const [transferSaving, setTransferSaving] = useState(false);
  const [transferErr, setTransferErr] = useState('');
  const [transferQueuesErr, setTransferQueuesErr] = useState('');
  const [startBusyId, setStartBusyId] = useState(null);
  const [transferOkHint, setTransferOkHint] = useState('');

  const loadFilters = useMemo(
    () => ({
      filaSuporte: filaSuporte || undefined,
      nivelAtendimento: nivelAtendimento || undefined,
      semResponsavel,
      somenteTransferidos,
      idResponsavel: idResponsavel || undefined,
      queueId: queueDbFilter || undefined,
    }),
    [filaSuporte, nivelAtendimento, semResponsavel, somenteTransferidos, idResponsavel, queueDbFilter]
  );

  const reload = useCallback(async () => {
    const res = await fetchTicketsTecnico(loadFilters);
    if (res.ok && res.groups) {
      setGroups(res.groups);
      setWorkflow(res.workflow ?? { enabled: false, filas: [] });
    }
  }, [loadFilters]);

  useEffect(() => {
    let cancel = false;
    (async () => {
      const res = await fetchTicketsTecnico(loadFilters);
      if (cancel) return;
      if (res.ok && res.groups) {
        setGroups(res.groups);
        setWorkflow(res.workflow ?? { enabled: false, filas: [] });
      }
    })();
    return () => {
      cancel = true;
    };
  }, [loadFilters]);

  useEffect(() => {
    let cancel = false;
    (async () => {
      const r = await fetchTecnicosParaTransferencia();
      if (cancel || !r.ok) return;
      setTecnicosOpcoes(r.tecnicos || []);
    })();
    return () => {
      cancel = true;
    };
  }, []);

  const wfEnabled = Boolean(workflow?.enabled);
  const filasMeta = workflow?.filas || [];
  const queuesRelacional = Boolean(workflow?.queuesRelacional);
  const dbQueuesList = workflow?.queues || [];

  useEffect(() => {
    if (!transferOpen || !queuesRelacional) return undefined;
    let cancel = false;
    (async () => {
      const q = Number(transferQueueId) || 0;
      const r = await fetchTecnicosParaTransferencia(q || undefined);
      if (cancel || !r.ok) return;
      setTecnicosModal(r.tecnicos || []);
    })();
    return () => {
      cancel = true;
    };
  }, [transferOpen, transferQueueId, queuesRelacional]);

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
      const fila = String(t.filaLabel || '').toLowerCase();
      return (
        id.includes(qq) ||
        cliente.includes(qq) ||
        assunto.includes(qq) ||
        tec.includes(qq) ||
        fila.includes(qq)
      );
    });
  }, [groups, filtroStatus, q]);

  const totalTodos = groups?.todos?.length ?? 0;
  const hoje = new Date().toLocaleDateString('pt-BR');

  const dash = boot?.paths?.dashboard;
  const addTicket = boot?.paths?.addTicket;

  const openTransfer = async (ticket) => {
    setTransferTicket(ticket);
    setTransferDest('');
    setTransferMotivo('');
    setTransferFila(wfEnabled ? ticket.filaSuporte || 'n1' : '');
    setTransferErr('');
    setTransferQueuesErr('');
    setTransferQueues([]);
    setTransferQueueId('');
    if (queuesRelacional) {
      const useEscalation = Boolean(workflow?.supportLevelsEnabled);
      const rq = await fetchQueuesForTicket(ticket.id, { escalationOnly: useEscalation });
      if (!rq.ok) {
        setTransferQueuesErr(rq.error || 'Não foi possível carregar as filas.');
        setTransferQueues([]);
        setTransferQueueId('');
      } else {
        const list = rq.queues || [];
        setTransferQueues(list);
        setTransferQueuesErr('');
        if (useEscalation && list.length === 0) {
          setTransferQueuesErr('Não há filas de escalonamento disponíveis acima do nível atual.');
        }
        const pref =
          ticket.filaQueueId && list.some((x) => Number(x.id) === Number(ticket.filaQueueId))
            ? String(ticket.filaQueueId)
            : list[0]
              ? String(list[0].id)
              : '';
        setTransferQueueId(pref);
      }
    }
    setTransferOpen(true);
  };

  const submitTransfer = async () => {
    if (!transferTicket) return;
    const id = Number(transferTicket.id);
    const dest = Number(transferDest);
    if (transferMotivo.trim().length < 3) {
      setTransferErr('Informe o motivo da transferência (mín. 3 caracteres).');
      return;
    }
    setTransferSaving(true);
    setTransferErr('');
    const qid = Number(transferQueueId) || 0;
    let payload;
    if (queuesRelacional) {
      if (!qid) {
        setTransferSaving(false);
        setTransferErr('Selecione a fila de destino.');
        return;
      }
      if (!dest) {
        payload = { queue_id: qid, motivo: transferMotivo.trim() };
      } else {
        payload = { iduser_destino: dest, queue_id: qid, motivo: transferMotivo.trim() };
      }
    } else {
      if (!dest) {
        setTransferSaving(false);
        setTransferErr('Selecione o técnico de destino.');
        return;
      }
      payload = {
        iduser_destino: dest,
        motivo: transferMotivo.trim(),
      };
      if (wfEnabled && transferFila) {
        payload.fila_suporte = transferFila;
      }
    }
    const r = await postTransferirTicket(id, payload);
    setTransferSaving(false);
    if (!r.ok) {
      const code = r.error;
      setTransferErr(API_ERR_TRANSFER[code] || code || 'Falha ao transferir.');
      return;
    }
    setTransferOpen(false);
    setTransferOkHint('Transferência registrada.');
    window.setTimeout(() => setTransferOkHint(''), 4000);
    await reload();
  };

  const handleStartAtendimento = async (ticket) => {
    const id = Number(ticket.id);
    setStartBusyId(id);
    try {
      const r = await postStartTicket(id);
      if (!r.ok) {
        const code = r.error;
        window.alert(API_ERR_START[code] || code || 'Não foi possível iniciar o atendimento.');
        return;
      }
      setTransferOkHint('Atendimento iniciado.');
      window.setTimeout(() => setTransferOkHint(''), 4000);
      await reload();
    } finally {
      setStartBusyId(null);
    }
  };

  const colCount = wfEnabled ? 10 : 8;

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
        <div className="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
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

      {transferOkHint ? (
        <div
          className="border-b border-emerald-100 bg-emerald-50 px-3 py-2 text-sm text-emerald-900"
          role="status"
          aria-live="polite"
        >
          {transferOkHint}
        </div>
      ) : null}

      {wfEnabled ? (
        <div className="flex flex-col gap-2 border-b border-slate-100 p-3 sm:flex-row sm:flex-wrap sm:items-center">
          <select
            value={filaSuporte}
            onChange={(e) => setFilaSuporte(e.target.value)}
            className="h-9 rounded-lg border border-slate-200 bg-white px-2 text-sm outline-none focus:border-teal-500"
          >
            <option value="">Todas as filas</option>
            {filasMeta.map((f) => (
              <option key={f.code} value={f.code}>
                {f.label}
              </option>
            ))}
          </select>
          <select
            value={nivelAtendimento}
            onChange={(e) => setNivelAtendimento(e.target.value)}
            className="h-9 rounded-lg border border-slate-200 bg-white px-2 text-sm outline-none focus:border-teal-500"
          >
            <option value="">Todos os níveis</option>
            {[1, 2, 3, 4, 5].map((n) => (
              <option key={n} value={String(n)}>
                Nível {n}
              </option>
            ))}
          </select>
          <select
            value={idResponsavel}
            onChange={(e) => setIdResponsavel(e.target.value)}
            className="h-9 min-w-[10rem] rounded-lg border border-slate-200 bg-white px-2 text-sm outline-none focus:border-teal-500"
          >
            <option value="">Qualquer técnico</option>
            {tecnicosOpcoes.map((t) => (
              <option key={t.id} value={String(t.id)}>
                {t.name}
              </option>
            ))}
          </select>
          <label className="flex cursor-pointer items-center gap-2 text-sm text-slate-600">
            <input
              type="checkbox"
              checked={semResponsavel}
              onChange={(e) => setSemResponsavel(e.target.checked)}
            />
            Sem técnico
          </label>
          <label className="flex cursor-pointer items-center gap-2 text-sm text-slate-600">
            <input
              type="checkbox"
              checked={somenteTransferidos}
              onChange={(e) => setSomenteTransferidos(e.target.checked)}
            />
            Transferidos
          </label>
          {dbQueuesList.length > 0 ? (
            <select
              value={queueDbFilter}
              onChange={(e) => setQueueDbFilter(e.target.value)}
              className="h-9 min-w-[10rem] rounded-lg border border-slate-200 bg-white px-2 text-sm outline-none focus:border-teal-500"
            >
              <option value="">Todas as filas (cadastro)</option>
              {dbQueuesList.map((fq) => (
                <option key={fq.id} value={String(fq.id)}>
                  {fq.name || fq.codigo || `Fila #${fq.id}`}
                </option>
              ))}
            </select>
          ) : null}
        </div>
      ) : null}

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
                {wfEnabled ? (
                  <>
                    <th className="max-w-[9rem] px-2 py-1.5 font-semibold sm:px-3">Fila</th>
                    <th className="whitespace-nowrap px-2 py-1.5 font-semibold sm:px-3">Nível</th>
                  </>
                ) : null}
                <th className="max-w-[7rem] px-2 py-1.5 font-semibold sm:px-3">Técnico</th>
                <th className="max-w-[8rem] px-2 py-1.5 font-semibold sm:px-3">Cliente</th>
                <th className="min-w-[14rem] px-2 py-1.5 font-semibold sm:min-w-[17rem] sm:px-3">Ações</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 bg-white">
              {rows.length === 0 ? (
                <tr>
                  <td colSpan={colCount} className="px-4 py-8 text-center text-slate-500">
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
                          <div
                            className="line-clamp-1 text-[11px] leading-tight text-slate-500"
                            title={ticket.solicitacaoPreview}
                          >
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
                      {wfEnabled ? (
                        <>
                          <td
                            className="max-w-[9rem] truncate px-2 py-1.5 text-slate-700 sm:px-3"
                            title={ticket.filaLabel || ''}
                          >
                            <span className="line-clamp-2">{ticket.filaLabel || '—'}</span>
                            {ticket.transferido ? (
                              <span className="mt-0.5 block text-[10px] font-semibold text-amber-700">Transferido</span>
                            ) : null}
                          </td>
                          <td className="whitespace-nowrap px-2 py-1.5 text-slate-600 sm:px-3">
                            {ticket.supportLevelLabel
                              ? ticket.supportLevelLabel
                              : ticket.nivelAtendimento != null
                                ? `N${ticket.nivelAtendimento}`
                                : '—'}
                          </td>
                        </>
                      ) : null}
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
                            {acoesOrd.map((a) => {
                              if (a.behavior === 'reactTransfer') {
                                return (
                                  <button
                                    key={a.key + a.label}
                                    type="button"
                                    className={acaoLinkClassName(a.key)}
                                    title={a.label}
                                    onClick={() => openTransfer(ticket)}
                                  >
                                    {a.label}
                                  </button>
                                );
                              }
                              if (a.behavior === 'reactStart') {
                                const busy = startBusyId === Number(ticket.id);
                                return (
                                  <button
                                    key={a.key + a.label}
                                    type="button"
                                    className={acaoLinkClassName(a.key)}
                                    title={a.label}
                                    disabled={busy}
                                    onClick={() => handleStartAtendimento(ticket)}
                                  >
                                    {busy ? 'Iniciando…' : a.label}
                                  </button>
                                );
                              }
                              return (
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
                              );
                            })}
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

      {transferOpen ? (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" role="dialog" aria-modal="true">
          <div className="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-2xl border border-slate-200 bg-white p-5 shadow-xl">
            <h4 className="text-lg font-bold text-slate-900">
              Transferir ticket #{transferTicket?.id}
            </h4>
            <p className="mt-1 text-sm text-slate-500">
              {queuesRelacional
                ? 'Escolha a fila da mesma empresa. Você pode só mover o chamado para a fila ou indicar um técnico da fila selecionada.'
                : 'O histórico registrará técnico anterior, novo técnico, data/hora e motivo.'}
            </p>
            <div className="mt-4 space-y-3">
              {queuesRelacional ? (
                <>
                  {transferQueuesErr ? <p className="text-sm text-amber-800">{transferQueuesErr}</p> : null}
                  <label className="block text-sm font-medium text-slate-700">
                    Fila de destino
                    <select
                      value={transferQueueId}
                      onChange={(e) => setTransferQueueId(e.target.value)}
                      className="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-teal-500"
                    >
                      <option value="">Selecione…</option>
                      {transferQueues.map((fq) => (
                        <option key={fq.id} value={String(fq.id)}>
                          {fq.name || fq.codigo || `Fila #${fq.id}`}
                        </option>
                      ))}
                    </select>
                  </label>
                  <label className="block text-sm font-medium text-slate-700">
                    Encaminhar para técnico (opcional)
                    <select
                      value={transferDest}
                      onChange={(e) => setTransferDest(e.target.value)}
                      className="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-teal-500"
                    >
                      <option value="">Apenas mover para a fila (sem responsável)</option>
                      {tecnicosModal.map((tm) => (
                        <option key={tm.id} value={String(tm.id)}>
                          {tm.name}
                        </option>
                      ))}
                    </select>
                  </label>
                </>
              ) : (
                <>
                  <label className="block text-sm font-medium text-slate-700">
                    Novo técnico responsável
                    <select
                      value={transferDest}
                      onChange={(e) => setTransferDest(e.target.value)}
                      className="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-teal-500"
                    >
                      <option value="">Selecione…</option>
                      {tecnicosOpcoes.map((t) => (
                        <option key={t.id} value={String(t.id)}>
                          {t.name}
                        </option>
                      ))}
                    </select>
                  </label>
                  {wfEnabled ? (
                    <label className="block text-sm font-medium text-slate-700">
                      Fila de destino (opcional — escala N1→N2→N3 etc.)
                      <select
                        value={transferFila}
                        onChange={(e) => setTransferFila(e.target.value)}
                        className="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-teal-500"
                      >
                        {filasMeta.map((f) => (
                          <option key={f.code} value={f.code}>
                            {f.label}
                          </option>
                        ))}
                      </select>
                    </label>
                  ) : null}
                </>
              )}
              <label className="block text-sm font-medium text-slate-700">
                Motivo
                <textarea
                  value={transferMotivo}
                  onChange={(e) => setTransferMotivo(e.target.value)}
                  rows={3}
                  className="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-teal-500"
                  placeholder="Ex.: Escalação para N2 — necessidade de visita presencial."
                />
              </label>
              {transferErr ? <p className="text-sm text-red-600">{transferErr}</p> : null}
            </div>
            <div className="mt-5 flex flex-wrap justify-end gap-2">
              <button
                type="button"
                className="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                onClick={() => setTransferOpen(false)}
                disabled={transferSaving}
              >
                Cancelar
              </button>
              <button
                type="button"
                className="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800 disabled:opacity-50"
                onClick={() => submitTransfer()}
                disabled={transferSaving}
              >
                {transferSaving ? 'Salvando…' : 'Confirmar transferência'}
              </button>
            </div>
          </div>
        </div>
      ) : null}
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
                  Responsável, filas N1–N3/NOC/serviço e transferência com registro no histórico.
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
