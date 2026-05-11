/**
 * API dos tickets no portal CakePHP (sessão + JSON).
 * Fora do portal (npm run dev), usa mocks se não houver window.__TICKETS_BOOT__.
 */

import {
  getTicketById,
  listTicketsForCliente,
  listTicketsForTecnico,
  MOCK_SESSION_CLIENTE,
} from '../data/mockData';

export function getBoot() {
  return typeof window !== 'undefined' ? window.__TICKETS_BOOT__ : null;
}

export const USE_MOCK = typeof window !== 'undefined' && !window.__TICKETS_BOOT__;

function qs(params) {
  const u = new URLSearchParams();
  Object.entries(params).forEach(([k, v]) => {
    if (v !== undefined && v !== null && v !== '') u.set(k, String(v));
  });
  const s = u.toString();
  return s ? `?${s}` : '';
}

/** Primeira mensagem de validação Cake (errors aninhados). */
function firstNestedErrorString(errors) {
  if (!errors || typeof errors !== 'object') return '';
  for (const v of Object.values(errors)) {
    if (typeof v === 'string' && v.trim()) return v.trim();
    if (Array.isArray(v)) {
      for (const x of v) {
        if (typeof x === 'string' && x.trim()) return x.trim();
      }
    } else if (v && typeof v === 'object') {
      const sub = firstNestedErrorString(v);
      if (sub) return sub;
    }
  }
  return '';
}

/** Texto amigável a partir do JSON de erro do Cake (message/detail/errors) ou do Response. */
function patchTicketsErrorMessage(r, json) {
  if (json && typeof json === 'object') {
    const m = json.message || json.detail || json.error_description;
    const base = typeof m === 'string' && m.trim() !== '' ? m.trim() : '';
    const fe = firstNestedErrorString(json.errors);
    if (fe) {
      if (base && !base.includes(fe)) return `${base}: ${fe}`;
      if (!base) return fe;
    }
    if (base) return base;
    if (typeof json.error === 'string' && json.error.trim() !== '') return json.error.trim();
  }
  if (r && typeof r.statusText === 'string' && r.statusText.trim() !== '') return r.statusText.trim();
  return 'Erro';
}

function mockTicketToTechRow(t) {
  const open =
    t.status === 'Aguardando técnico' ||
    t.status === 'Pendente' ||
    t.status === 'Em execução' ||
    t.status === 'Em andamento';
  const pendente = t.status === 'Aguardando técnico' || t.status === 'Pendente';
  const acoes = [];
  if (open) {
    if (pendente) {
      acoes.push({
        key: 'iniciar',
        label: 'Iniciar atendimento',
        behavior: 'reactStart',
        url: `#/mock-ticket/${t.id}`,
      });
    }
    acoes.push({
      key: 'transferir',
      label: 'Transferir',
      behavior: 'reactTransfer',
      url: `#/mock-ticket/${t.id}`,
    });
  }
  return {
    id: t.id,
    autor: '—',
    created: t.atualizado || '—',
    assunto_nome: t.assunto ?? 'Não informado',
    situacao: open ? 1 : 2,
    situacaoLabel: pendente ? 'Pendente' : t.status,
    cliente: t.cliente,
    tecnicos: t.tecnicos ?? t.responsavel ?? '—',
    filaSuporte: t.filaSuporte || 'n1',
    filaLabel: t.filaLabel || 'Fila N1 — Suporte inicial / triagem',
    filaQueueId: 1,
    nivelAtendimento: t.nivelAtendimento ?? 1,
    transferido: Boolean(t.transferido),
    solicitacaoPreview: (t.descricao || '').slice(0, 120),
    urls: { edit: `#/mock-ticket/${t.id}` },
    acoes,
    sla_status: 'dentro_sla',
    sla_percentual_consumido: 12,
    sla_resolucao_pausado: false,
    data_limite_resolucao_iso: new Date(Date.now() + 3600000).toISOString(),
    sla_remaining_minutes: 60,
    sla_urgency_band: 'ok',
    sla_tooltip: 'SLA dentro do esperado (mock).',
    servicedeskActions: {
      canAwaitCliente: true,
      awaitClienteWorkflowStateId: 99,
      awaitClienteUsesPendenteFallback: false,
      canEscalateLevel: true,
      escalateTargetFilaCode: 'n2',
    },
    mayAssumeTicketQueue: true,
  };
}

export async function fetchTicketsTecnico(filters = {}) {
  if (USE_MOCK) {
    const all = listTicketsForTecnico().map(mockTicketToTechRow);
    const groups = {
      todos: all,
      pendentes: all.filter((t) => t.situacaoLabel === 'Pendente'),
      emandamento: all.filter((t) => t.situacaoLabel === 'Em execução' || t.situacaoLabel === 'Em andamento'),
      resolvidos: all.filter((t) => t.situacaoLabel === 'Resolvido'),
      fechados: all.filter((t) => t.situacaoLabel === 'Cancelado' || t.situacaoLabel === 'Fechado'),
    };
    const filas = [
      { code: 'n1', label: 'Fila N1 — Suporte inicial / triagem', nivel: 1 },
      { code: 'n2', label: 'Fila N2 — Suporte avançado / field service', nivel: 2 },
      { code: 'n3', label: 'Fila N3 — Infraestrutura / especializado', nivel: 3 },
      { code: 'noc', label: 'Fila NOC — Monitoramento', nivel: 4 },
      { code: 'servico', label: 'Fila requisições de serviço', nivel: 5 },
    ];
    const queues = [
      { id: 1, name: 'N1 — Triagem', codigo: 'n1' },
      { id: 2, name: 'N2 — Avançado', codigo: 'n2' },
    ];
    return {
      ok: true,
      groups,
      workflow: { enabled: true, filas, queuesRelacional: true, queues },
    };
  }
  const boot = getBoot();
  const q = qs({
    fila_suporte: filters.filaSuporte,
    nivel_atendimento: filters.nivelAtendimento,
    sem_responsavel: filters.semResponsavel ? '1' : '',
    idtecnico_responsavel: filters.idResponsavel,
    transferidos: filters.somenteTransferidos ? '1' : '',
    queue_id: filters.queueId,
    sd: boot?.servicedesk ? '1' : '',
  });
  const r = await fetch(`${boot.paths.apiIndex}${q}`, { credentials: 'same-origin' });
  let json = {};
  try {
    json = await r.json();
  } catch (_) {
    json = {};
  }
  if (!r.ok) {
    return {
      ok: false,
      error: json.error || r.statusText || 'http_error',
      httpStatus: r.status,
      groups: null,
      workflow: null,
    };
  }
  if (!json.ok) {
    return {
      ok: false,
      error: json.error || 'erro',
      httpStatus: r.status,
      groups: null,
      workflow: null,
    };
  }
  return { ok: true, groups: json.groups, workflow: json.workflow || { enabled: false, filas: [] } };
}

export async function fetchQueuesForTicket(ticketId, opts = {}) {
  if (USE_MOCK) {
    return {
      ok: true,
      queues: [
        { id: 1, name: 'N1 — Triagem', codigo: 'n1' },
        { id: 2, name: 'N2 — Avançado', codigo: 'n2' },
      ],
    };
  }
  const boot = getBoot();
  const base = boot.paths?.apiGetAvailableQueues || boot.paths?.apiQueuesForTicket;
  if (!base) return { ok: false, error: 'no_api', queues: [] };
  const q = opts.escalationOnly ? '?escalation_only=1' : '';
  const r = await fetch(`${base}${encodeURIComponent(ticketId)}${q}`, {
    credentials: 'same-origin',
    headers: { Accept: 'application/json' },
  });
  if (!r.ok) return { ok: false, error: r.statusText, queues: [] };
  const json = await r.json();
  if (!json.ok) return { ok: false, error: json.error || 'erro', queues: [] };
  return { ok: true, queues: json.queues || [] };
}

/**
 * Filas com vínculo explícito em queues_users. GET queues/api-for-user/:id
 * @param {number|string} userId
 * @param {{ signal?: AbortSignal }} [opts]
 */
export async function fetchQueuesForUser(userId, opts = {}) {
  if (USE_MOCK) {
    return {
      ok: true,
      queues: [
        { id: 1, name: 'N1 — Triagem', codigo: 'n1' },
        { id: 2, name: 'N2 — Avançado', codigo: 'n2' },
      ],
    };
  }
  const boot = getBoot();
  const base = boot?.paths?.apiQueuesForUser;
  if (!base || !(Number(userId) > 0)) {
    return { ok: false, error: 'no_api', queues: [] };
  }
  const url = `${base}${encodeURIComponent(String(userId))}`;
  let r;
  try {
    r = await fetch(url, {
      credentials: 'same-origin',
      headers: { Accept: 'application/json' },
      signal: opts.signal,
    });
  } catch (e) {
    if (e && e.name === 'AbortError') {
      return { ok: false, error: 'aborted', aborted: true, queues: [] };
    }
    return { ok: false, error: 'network', message: e?.message || 'Erro de rede', queues: [] };
  }
  let json = {};
  try {
    json = await r.json();
  } catch (_) {
    /* ignore */
  }
  if (!r.ok || !json.ok) {
    return {
      ok: false,
      error: (json && json.error) || r.statusText,
      message: patchTicketsErrorMessage(r, json),
      queues: [],
    };
  }
  return { ok: true, queues: json.queues || [] };
}

export async function postStartTicket(ticketId) {
  if (USE_MOCK) {
    return { ok: true };
  }
  const boot = getBoot();
  const headers = { Accept: 'application/json', 'Content-Type': 'application/json' };
  const init = { credentials: 'same-origin', headers, body: '{}' };

  async function readBody(res) {
    let json = {};
    try {
      json = await res.json();
    } catch (_) {
      /* ignore */
    }
    return json;
  }

  const slug = boot.paths?.apiStartTicketSlug;
  const legacy = boot.paths?.apiStartTicket;
  if (slug) {
    const rPut = await fetch(`${slug}${encodeURIComponent(ticketId)}`, { method: 'PUT', ...init });
    const jPut = await readBody(rPut);
    if (rPut.ok && jPut.ok) return { ok: true };
    if (rPut.status !== 405 && rPut.status !== 404) {
      return { ok: false, error: jPut.error || rPut.statusText, message: jPut.message };
    }
  }
  if (!legacy) return { ok: false, error: 'no_api' };
  const r = await fetch(`${legacy}${encodeURIComponent(ticketId)}`, { method: 'POST', ...init });
  const json = await readBody(r);
  if (!r.ok) return { ok: false, error: json.error || r.statusText, message: json.message };
  if (!json.ok) return { ok: false, error: json.error || 'erro', message: json.message };
  return { ok: true };
}

export async function fetchTecnicosParaTransferencia(queueId) {
  if (USE_MOCK) {
    return {
      ok: true,
      tecnicos: [
        { id: 1, name: 'NOC 02', nivel_id: 4, nivel_label: 'NOC', nivel_sort: 4 },
        { id: 2, name: 'Service Desk', nivel_id: 1, nivel_label: 'N1', nivel_sort: 1 },
        { id: 3, name: 'Suporte N2', nivel_id: 2, nivel_label: 'N2', nivel_sort: 2 },
      ],
    };
  }
  const boot = getBoot();
  const q = qs({ queue_id: queueId });
  const r = await fetch(`${boot.paths.apiTecnicosLista}${q}`, { credentials: 'same-origin' });
  if (!r.ok) return { ok: false, error: r.statusText, tecnicos: [] };
  const json = await r.json();
  if (!json.ok) return { ok: false, error: json.error || 'erro', tecnicos: [] };
  return { ok: true, tecnicos: json.tecnicos || [] };
}

/**
 * patchTicketAssignment / patchTicketStatus / patchTicketPriority: URLs em `boot.paths`.
 * Continuam no boot independentemente de inlineAssignment; só a grid técnica (TechDashboard) usa a flag para UI.
 */
export async function patchTicketAssignment(ticketId, payload) {
  if (USE_MOCK) {
    await new Promise((r) => setTimeout(r, 80));
    return { ok: true, ticket: { id: Number(ticketId), ...payload } };
  }
  const boot = getBoot();
  const base = boot?.paths?.apiPatchTicketAssignment;
  if (!base) return { ok: false, error: 'no_api' };
  const url = `${base}${encodeURIComponent(String(ticketId))}/assignment`;
  const r = await fetch(url, {
    method: 'PATCH',
    credentials: 'same-origin',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    body: JSON.stringify(payload || {}),
  });
  let json = {};
  try {
    json = await r.json();
  } catch (_) {
    /* ignore */
  }
  if (!r.ok || !json.ok) {
    return {
      ok: false,
      error: (json && json.error) || r.statusText,
      message: patchTicketsErrorMessage(r, json),
      queue_name: json && typeof json.queue_name === 'string' ? json.queue_name : undefined,
      ticket: null,
    };
  }
  return { ok: true, ticket: json.ticket || null };
}

/**
 * PATCH status do ticket (workflow ou legado).
 * @param {number|string} ticketId
 * @param {object} body
 * @param {{ source?: string }} [meta] — opcional; para diagnóstico (`debugTicketStatusPatch` no localStorage).
 */
export async function patchTicketStatus(ticketId, body, meta = {}) {
  try {
    const debugOn =
      typeof localStorage !== 'undefined' &&
      localStorage.getItem('debugTicketStatusPatch') === '1';
    if (debugOn) {
      const payload = {
        ts: new Date().toISOString(),
        ticketId: Number(ticketId),
        workflow_state_id: body?.workflow_state_id ?? null,
        status: body?.status ?? null,
        source: meta?.source || 'outro',
      };
      if (typeof import.meta !== 'undefined' && import.meta.env?.DEV) {
        payload.stack = new Error('[ticket-status-patch]').stack;
      }
      console.debug('[ticket-status-patch]', payload);
    }
  } catch (_) {
    /* ignore debug failures */
  }

  if (USE_MOCK) {
    await new Promise((r) => setTimeout(r, 80));
    return { ok: true, situacao: 1, situacaoLabel: body?.status || 'mock', ticket: null };
  }
  const boot = getBoot();
  const base = boot?.paths?.apiPatchTicketStatus;
  if (!base) {
    return { ok: false, error: 'no_api', ticket: null };
  }
  const url = `${base}${encodeURIComponent(String(ticketId))}/status`;
  const r = await fetch(url, {
    method: 'PATCH',
    credentials: 'same-origin',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    body: JSON.stringify(body || {}),
  });
  let json = {};
  try {
    json = await r.json();
  } catch (_) {
    /* ignore */
  }
  if (!r.ok || !json.ok) {
    return {
      ok: false,
      error: (json && json.error) || r.statusText,
      message: patchTicketsErrorMessage(r, json),
      ticket: null,
    };
  }
  return {
    ok: true,
    situacao: json.situacao,
    situacaoLabel: json.situacaoLabel,
    ticket: json.ticket || null,
  };
}

/** POST: transição de workflow + pausa de SLA (aguardando cliente). */
export async function postServicedeskAwaitCliente(ticketId, body = {}) {
  if (USE_MOCK) {
    await new Promise((r) => setTimeout(r, 60));
    return {
      ok: true,
      ticket: {
        id: Number(ticketId),
        sla_urgency_band: 'paused',
        sla_status: 'dentro_sla',
        sla_tooltip: 'SLA pausado (mock).',
        servicedeskActions: { canAwaitCliente: false, canEscalateLevel: true, escalateTargetFilaCode: 'n2' },
      },
    };
  }
  const boot = getBoot();
  const base = boot?.paths?.apiServicedeskAwaitCliente;
  if (!base) return { ok: false, error: 'no_api', message: 'API indisponível', ticket: null };
  const url = `${base}${encodeURIComponent(String(ticketId))}`;
  const r = await fetch(url, {
    method: 'POST',
    credentials: 'same-origin',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    body: JSON.stringify(body && typeof body === 'object' ? body : {}),
  });
  let json = {};
  try {
    json = await r.json();
  } catch (_) {
    /* ignore */
  }
  if (!r.ok || !json.ok) {
    return {
      ok: false,
      error: (json && json.error) || r.statusText,
      message: patchTicketsErrorMessage(r, json),
      ticket: null,
    };
  }
  return { ok: true, ticket: json.ticket || null };
}

/** POST: escalonamento N1→N2 ou N2→N3 (fila + pendente). */
export async function postServicedeskEscalateLevel(ticketId, body = {}) {
  if (USE_MOCK) {
    await new Promise((r) => setTimeout(r, 60));
    return {
      ok: true,
      ticket: {
        id: Number(ticketId),
        filaSuporte: 'n2',
        nivelAtendimento: 2,
        servicedeskActions: { canAwaitCliente: false, canEscalateLevel: true, escalateTargetFilaCode: 'n3' },
      },
    };
  }
  const boot = getBoot();
  const base = boot?.paths?.apiServicedeskEscalateLevel;
  if (!base) return { ok: false, error: 'no_api', message: 'API indisponível', ticket: null };
  const url = `${base}${encodeURIComponent(String(ticketId))}`;
  const r = await fetch(url, {
    method: 'POST',
    credentials: 'same-origin',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    body: JSON.stringify(body && typeof body === 'object' ? body : {}),
  });
  let json = {};
  try {
    json = await r.json();
  } catch (_) {
    /* ignore */
  }
  if (!r.ok || !json.ok) {
    return {
      ok: false,
      error: (json && json.error) || r.statusText,
      message: patchTicketsErrorMessage(r, json),
      ticket: null,
    };
  }
  return { ok: true, ticket: json.ticket || null };
}

export async function patchTicketPriority(ticketId, body) {
  if (USE_MOCK) {
    await new Promise((r) => setTimeout(r, 80));
    return { ok: true, ticket: null };
  }
  const boot = getBoot();
  const base = boot?.paths?.apiPatchTicketPriority;
  if (!base) return { ok: false, error: 'no_api' };
  const url = `${base}${encodeURIComponent(String(ticketId))}/priority`;
  const r = await fetch(url, {
    method: 'PATCH',
    credentials: 'same-origin',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    body: JSON.stringify(body || {}),
  });
  let json = {};
  try {
    json = await r.json();
  } catch (_) {
    /* ignore */
  }
  if (!r.ok || !json.ok) {
    return {
      ok: false,
      error: (json && json.error) || r.statusText,
      message: patchTicketsErrorMessage(r, json),
      ticket: null,
    };
  }
  return { ok: true, ticket: json.ticket || null };
}

export async function patchTicketSubject(ticketId, body) {
  if (USE_MOCK) {
    await new Promise((r) => setTimeout(r, 80));
    return { ok: true, ticket: null };
  }
  const boot = getBoot();
  const base = boot?.paths?.apiPatchTicketSubject
    || boot?.paths?.apiPatchTicketAssignment
    || boot?.paths?.apiPatchTicketStatus
    || boot?.paths?.apiPatchTicketPriority;
  if (!base) {
    return { ok: false, error: 'no_api', message: 'Endpoint de edição de categoria não encontrado no boot.' };
  }
  const url = `${base}${encodeURIComponent(String(ticketId))}/subject`;
  const r = await fetch(url, {
    method: 'PATCH',
    credentials: 'same-origin',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    body: JSON.stringify(body || {}),
  });
  let json = {};
  try {
    json = await r.json();
  } catch (_) {
    /* ignore */
  }
  if (!r.ok || !json.ok) {
    return {
      ok: false,
      error: (json && json.error) || r.statusText,
      message: patchTicketsErrorMessage(r, json),
      ticket: null,
    };
  }
  return { ok: true, ticket: json.ticket || null };
}

export async function fetchTicketSubjectOptions() {
  if (USE_MOCK) {
    return {
      ok: true,
      options: [
        { id: 1, value: 1, nome: 'Dúvida', label: 'Dúvida' },
        { id: 2, value: 2, nome: 'Incidente', label: 'Incidente' },
      ],
    };
  }
  const boot = getBoot();
  const url = boot?.paths?.apiTicketSubjectOptions;
  if (!url) return { ok: false, error: 'no_api', options: [] };
  const r = await fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
  const json = await readBody(r);
  if (!r.ok || !json.ok) return { ok: false, error: json.error || r.statusText, options: [] };
  return { ok: true, options: Array.isArray(json.options) ? json.options : [] };
}

export async function postTransferirTicket(ticketId, payload) {
  if (USE_MOCK) {
    return { ok: true };
  }
  const boot = getBoot();
  const url = `${boot.paths.apiTransferirTicket}${encodeURIComponent(ticketId)}`;
  const r = await fetch(url, {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify(payload),
  });
  let json = {};
  try {
    json = await r.json();
  } catch (_) {
    /* ignore */
  }
  if (!r.ok) {
    return {
      ok: false,
      error: json.error || r.statusText,
      message: typeof json.message === 'string' ? json.message : undefined,
      queue_name: typeof json.queue_name === 'string' ? json.queue_name : undefined,
    };
  }
  if (!json.ok) {
    return {
      ok: false,
      error: json.error || 'erro',
      message: typeof json.message === 'string' ? json.message : undefined,
      queue_name: typeof json.queue_name === 'string' ? json.queue_name : undefined,
    };
  }
  return { ok: true };
}

export async function fetchTicketsCliente(filters = {}) {
  if (USE_MOCK) {
    return { ok: true, data: listTicketsForCliente(MOCK_SESSION_CLIENTE.clienteId) };
  }
  const boot = getBoot();
  const q = qs({
    assunto: filters.assunto,
    situacao: filters.situacao,
    fila: filters.fila,
    sd: boot?.servicedesk ? '1' : '',
  });
  const r = await fetch(`${boot.paths.apiIndexCliente}${q}`, { credentials: 'same-origin' });
  if (!r.ok) return { ok: false, error: r.statusText, data: [] };
  const json = await r.json();
  if (!json.ok) return { ok: false, error: json.error || 'erro', data: [] };
  return { ok: true, data: json.tickets || [] };
}

/** Estado mínimo do timer só para `npm run dev` sem portal. */
let mockTimerSessao = null;

export async function fetchTicketDetail(id) {
  if (USE_MOCK) {
    const t = getTicketById(id);
    if (!t) return { ok: false, error: 'Não encontrado' };
    const horasTecnicas = {
      canUseTimer: true,
      minutosRegistrados: 0,
      sessao: mockTimerSessao,
      serverUnix: Math.floor(Date.now() / 1000),
      timerDisponivel: true,
      ultimaFinalizacao: null,
    };
    return {
      ok: true,
      data: {
        ...t,
        assunto_nome: t.assunto ?? 'Não informado',
        status: t.status,
        descricao: t.descricao || '',
        descricaoAtendimento: t.descricaoAtendimento || '',
        cliente: t.cliente,
        comentarios: t.comentarios || [],
        anexos: t.anexos || [],
        urls: { indexTecnico: '#/', cancelar: `#/mock-ticket/${t.id}/cancelar`, imprimir: '#' },
        flags: { role: 0, canEditDescricao: true, canEditDescricaoAtendimento: true, canCancel: true },
        horasTecnicas,
      },
    };
  }
  const boot = getBoot();
  const sdQ = boot?.servicedesk ? '?sd=1' : '';
  const r = await fetch(`${boot.paths.apiView}${encodeURIComponent(id)}${sdQ}`, {
    credentials: 'same-origin',
    cache: 'no-store',
    headers: { Accept: 'application/json' },
  });
  if (!r.ok) return { ok: false, error: r.statusText };
  const json = await r.json();
  if (!json.ok) return { ok: false, error: json.error || 'erro' };
  return { ok: true, data: json.ticket };
}

/** Polling leve: só comentários + status (sem anexos/descrição). */
export async function fetchTicketComments(id) {
  if (USE_MOCK) {
    return { ok: true, comentarios: [], status: null, situacao: null };
  }
  const boot = getBoot();
  if (!boot?.paths?.apiComments) {
    return { ok: false, error: 'no_api_comments' };
  }
  const r = await fetch(`${boot.paths.apiComments}${encodeURIComponent(id)}`, {
    credentials: 'same-origin',
    cache: 'no-store',
    headers: { Accept: 'application/json' },
  });
  if (!r.ok) return { ok: false, error: r.statusText };
  const json = await r.json();
  if (!json.ok) return { ok: false, error: json.error || 'erro' };
  return {
    ok: true,
    comentarios: json.comentarios || [],
    status: json.status ?? null,
    situacao: json.situacao ?? null,
    descricao: json.descricao,
    descricaoAtendimento: json.descricaoAtendimento,
    horasTecnicas: json.horasTecnicas,
    responsavel: json.responsavel,
  };
}

export async function fetchTicketTimeline(ticketId) {
  if (USE_MOCK) {
    return { ok: true, events: [] };
  }
  const boot = getBoot();
  const p = boot?.paths?.apiTimeline;
  if (!p) return { ok: false, error: 'no_api_timeline', events: [] };
  const r = await fetch(`${p}${encodeURIComponent(ticketId)}`, {
    credentials: 'same-origin',
    cache: 'no-store',
    headers: { Accept: 'application/json' },
  });
  if (!r.ok) return { ok: false, error: r.statusText, events: [] };
  const json = await r.json();
  if (!json.ok) return { ok: false, error: json.error || 'erro', events: [] };
  return { ok: true, events: json.events || [] };
}

export async function fetchTicketMessages(ticketId) {
  if (USE_MOCK) {
    return { ok: true, messages: [] };
  }
  const boot = getBoot();
  const p = boot?.paths?.apiTicketMessages;
  if (!p) return { ok: false, error: 'no_api', messages: [] };
  const r = await fetch(`${p}${encodeURIComponent(ticketId)}`, {
    credentials: 'same-origin',
    cache: 'no-store',
    headers: { Accept: 'application/json' },
  });
  if (!r.ok) return { ok: false, error: r.statusText, messages: [] };
  const j = await r.json();
  if (!j.ok) return { ok: false, error: j.error, messages: [] };
  return { ok: true, messages: j.messages || [] };
}

export async function postTicketMessage(ticketId, text) {
  if (USE_MOCK) {
    return { ok: true, message: { id: `m${Date.now()}`, message: text, userName: 'Eu' } };
  }
  const boot = getBoot();
  const p = boot?.paths?.apiTicketMessages;
  if (!p) return { ok: false, error: 'no_api' };
  const r = await fetch(`${p}${encodeURIComponent(ticketId)}`, {
    method: 'POST',
    credentials: 'same-origin',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    body: JSON.stringify({ message: text }),
  });
  const j = await r.json().catch(() => ({}));
  if (!r.ok || !j.ok) return { ok: false, error: j.error || r.statusText };
  return { ok: true, message: j.message };
}

export async function fetchRealtimeToken(ticketId) {
  if (USE_MOCK) {
    return { ok: true, url: null, token: null, expires: 0 };
  }
  const boot = getBoot();
  const p = boot?.paths?.apiRealtimeToken;
  if (!p) return { ok: false, error: 'no_api' };
  const r = await fetch(`${p}${encodeURIComponent(ticketId)}`, {
    credentials: 'same-origin',
    cache: 'no-store',
    headers: { Accept: 'application/json' },
  });
  const j = await r.json().catch(() => ({}));
  if (!r.ok || !j.ok) return { ok: false, error: j.error || r.statusText };
  return { ok: true, url: j.url, token: j.token, expires: j.expires };
}

export async function fetchServicedeskData(ticketId, tab) {
  if (USE_MOCK) {
    return { ok: true, tab, rows: [] };
  }
  const boot = getBoot();
  const p = boot?.paths?.apiServicedeskData;
  if (!p) return { ok: false, error: 'no_api' };
  const r = await fetch(
    `${p}${encodeURIComponent(ticketId)}${qs({ tab: tab || 'ativos' })}`,
    { credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' } },
  );
  const j = await r.json().catch(() => ({}));
  if (!r.ok || !j.ok) return { ok: false, error: j.error || r.statusText };
  return { ok: true, ...j };
}

export async function searchTicketProductsServices(ticketId, params = {}) {
  if (USE_MOCK) {
    return { ok: true, items: [] };
  }
  const boot = getBoot();
  const p = boot?.paths?.apiTicketProductSearch;
  if (!p) return { ok: false, error: 'no_api', items: [] };
  const r = await fetch(
    `${p}${encodeURIComponent(ticketId)}${qs({
      q: params.q || '',
      tipo: params.tipo || '',
      sCodProduto: params.sCodProduto || '',
      sDescricao: params.sDescricao || '',
      apenasComSaldo: params.apenasComSaldo ? '1' : '0',
    })}`,
    { credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' } },
  );
  const j = await r.json().catch(() => ({}));
  if (!r.ok || !j.ok) return { ok: false, error: j.error || r.statusText, items: [] };
  return { ok: true, items: Array.isArray(j.items) ? j.items : [] };
}

export async function addTicketProduct(ticketId, payload) {
  if (USE_MOCK) {
    return { ok: true, id: Date.now() };
  }
  const boot = getBoot();
  const p = boot?.paths?.apiAddTicketProduct;
  if (!p) return { ok: false, error: 'no_api' };
  const r = await fetch(`${p}${encodeURIComponent(ticketId)}`, {
    method: 'POST',
    credentials: 'same-origin',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    body: JSON.stringify(payload || {}),
  });
  const j = await r.json().catch(() => ({}));
  if (!r.ok || !j.ok) return { ok: false, error: j.error || r.statusText, message: j.message || '' };
  return { ok: true, id: j.id };
}

export async function postTicketSignature(ticketId, imageDataUrl) {
  if (USE_MOCK) {
    return { ok: true, url: '#' };
  }
  const boot = getBoot();
  const base = boot?.paths?.apiTicketSignature;
  if (!base) return { ok: false, error: 'no_api' };
  const r = await fetch(`${base}${encodeURIComponent(ticketId)}`, {
    method: 'POST',
    credentials: 'same-origin',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    body: JSON.stringify({ image: imageDataUrl }),
  });
  let json = {};
  try {
    json = await r.json();
  } catch (_) {
    /* ignore */
  }
  if (!r.ok || !json.ok) {
    return { ok: false, error: json.error || r.statusText };
  }
  return { ok: true, url: json.url };
}

export async function postComentario(ticketId, texto) {
  if (USE_MOCK) {
    await new Promise((res) => setTimeout(res, 400));
    return {
      ok: true,
      data: {
        id: Date.now(),
        autor: MOCK_SESSION_CLIENTE.name,
        papel: 'cliente',
        texto,
        quando: 'agora',
      },
    };
  }
  const boot = getBoot();
  const r = await fetch(`${boot.paths.apiAddComentario}${encodeURIComponent(ticketId)}`, {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ comentario: texto }),
  });
  if (!r.ok) return { ok: false, error: r.statusText };
  const json = await r.json();
  if (!json.ok) return { ok: false, error: json.error || 'erro' };
  return { ok: true, data: json.data };
}

export async function editComentario(comentarioId, texto) {
  if (USE_MOCK) {
    await new Promise((res) => setTimeout(res, 300));
    return {
      ok: true,
      data: {
        id: comentarioId,
        idautor: 1,
        autor: MOCK_SESSION_CLIENTE.name,
        papel: 'cliente',
        texto,
        quando: 'agora',
      },
    };
  }
  const boot = getBoot();
  if (!boot?.paths?.apiEditComentario) {
    return { ok: false, error: 'api_edit_disabled' };
  }
  const r = await fetch(`${boot.paths.apiEditComentario}${encodeURIComponent(comentarioId)}`, {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ comentario: texto }),
  });
  if (!r.ok) return { ok: false, error: r.statusText };
  const json = await r.json();
  if (!json.ok) return { ok: false, error: json.error || 'erro' };
  return { ok: true, data: json.data };
}

export async function deleteComentario(comentarioId) {
  if (USE_MOCK) {
    await new Promise((res) => setTimeout(res, 200));
    return { ok: true };
  }
  const boot = getBoot();
  if (!boot?.paths?.apiDeleteComentario) {
    return { ok: false, error: 'api_delete_disabled' };
  }
  const r = await fetch(`${boot.paths.apiDeleteComentario}${encodeURIComponent(comentarioId)}`, {
    method: 'POST',
    credentials: 'same-origin',
    headers: { Accept: 'application/json' },
  });
  if (!r.ok) return { ok: false, error: r.statusText };
  const json = await r.json();
  if (!json.ok) return { ok: false, error: json.error || 'erro' };
  return { ok: true };
}

export async function uploadTicketAnexo(ticketId, file) {
  if (USE_MOCK) {
    await new Promise((r) => setTimeout(r, 250));
    return {
      ok: true,
      anexo: {
        id: Date.now(),
        nome: file.name,
        url: '#',
        urlView: '#',
      },
    };
  }
  const boot = getBoot();
  if (!boot?.paths?.apiAnexoUpload) {
    return { ok: false, error: 'api_anexo_disabled' };
  }
  const fd = new FormData();
  fd.append('file', file);
  const r = await fetch(`${boot.paths.apiAnexoUpload}${encodeURIComponent(ticketId)}`, {
    method: 'POST',
    credentials: 'same-origin',
    headers: { Accept: 'application/json' },
    body: fd,
  });
  // Lê o corpo JSON mesmo em status >=400; o controller responde JSON com `error` em
  // todos os caminhos de falha (no_file, upload_failed, save_failed, forbidden, …).
  let json = null;
  try { json = await r.json(); } catch { /* corpo não-JSON (ex. erro fatal do PHP) */ }
  if (!r.ok) {
    return { ok: false, error: (json && json.error) || r.statusText || 'erro', detail: json && json.detail };
  }
  if (!json || !json.ok) return { ok: false, error: (json && json.error) || 'erro', detail: json && json.detail };
  return { ok: true, anexo: json.anexo };
}

export async function deleteTicketAnexo(anexoId) {
  if (USE_MOCK) {
    await new Promise((r) => setTimeout(r, 150));
    return { ok: true, anexos: undefined };
  }
  const boot = getBoot();
  if (!boot?.paths?.apiAnexoDelete) {
    return { ok: false, error: 'api_anexo_disabled' };
  }
  const r = await fetch(`${boot.paths.apiAnexoDelete}${encodeURIComponent(anexoId)}`, {
    method: 'POST',
    credentials: 'same-origin',
    headers: { Accept: 'application/json' },
  });
  let json = null;
  try { json = await r.json(); } catch { /* corpo não-JSON */ }
  if (!r.ok) {
    return { ok: false, error: (json && json.error) || r.statusText || 'erro', detail: json && json.detail };
  }
  if (!json || !json.ok) return { ok: false, error: (json && json.error) || 'erro', detail: json && json.detail };
  return { ok: true, anexos: json.anexos };
}

export async function saveTicketSolicitacao(ticketId, solicitacao) {
  if (USE_MOCK) {
    return { ok: true };
  }
  const boot = getBoot();
  const r = await fetch(`${boot.paths.apiSaveTicket}${encodeURIComponent(ticketId)}`, {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ solicitacao }),
  });
  if (!r.ok) return { ok: false, error: r.statusText };
  const json = await r.json();
  return { ok: !!json.ok, error: json.error };
}

/** Relato do técnico (o que foi feito no atendimento). Qualquer usuário role técnico. */
export async function saveTicketDescricaoAtendimento(ticketId, descricaoAtendimento) {
  if (USE_MOCK) {
    return { ok: true };
  }
  const boot = getBoot();
  const r = await fetch(`${boot.paths.apiSaveTicket}${encodeURIComponent(ticketId)}`, {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ descricaoAtendimento }),
  });
  if (!r.ok) return { ok: false, error: r.statusText };
  const json = await r.json();
  return { ok: !!json.ok, error: json.error };
}

export async function fetchDashboardOperacional() {
  if (USE_MOCK) {
    return {
      ok: true,
      dashboard: {
        empresa_id: 1,
        gerado_em: new Date().toISOString(),
        colunas_sla_ativas: true,
        backlog: 12,
        resolvidos_hoje: 3,
        p1_abertos: 2,
        por_prioridade: { P1: 2, P2: 5, P3: 10, P4: 3 },
        por_sla_status: { dentro_sla: 18, em_risco: 4, violado: 2 },
        por_situacao: { 1: 5, 2: 8, 3: 2 },
        por_fila_id: { 1: 10, 2: 2, '(sem fila)': 1 },
        alertas_sla_violado: [
          {
            id: 101,
            prioridade: 'P1',
            sla_status: 'violado',
            sla_percentual_consumido: 118.5,
            data_limite_resolucao: new Date(Date.now() - 3600000).toISOString(),
            queue_id: 1,
            fila_nome: 'N1 — Triagem',
          },
        ],
        sla_por_etapa: {
          overdue: 2,
          near_due: 4,
          paused: 1,
          avg_seconds_by_state: [
            { workflow_state_id: 2, label: 'Em execução', avg_seconds: 9800 },
            { workflow_state_id: 3, label: 'Pendente', avg_seconds: 5400 },
          ],
        },
        alertas_sla_state: {
          overdue: [
            { id: 101, workflow_state_id: 2, prioridade: 'P1', data_limite_resolucao: new Date(Date.now() - 3600000).toISOString() },
          ],
          near_due: [
            { id: 110, workflow_state_id: 2, prioridade: 'P2', data_limite_resolucao: new Date(Date.now() + 1200000).toISOString() },
          ],
          paused: [
            { id: 115, workflow_state_id: 3, prioridade: 'P3', data_limite_resolucao: new Date(Date.now() + 7200000).toISOString() },
          ],
        },
        sla_operational_kpis: {
          escalados_hoje: 0,
          criticos_abertos: 1,
          sem_tecnico: 3,
          aguardando_cliente: 2,
        },
        sla_future: {
          proactive_alerts: false,
          metrics_per_stage: true,
          per_technician: false,
          bottlenecks: false,
          business_hours_vs_24h: false,
          rules_by_client_queue_team: false,
        },
      },
    };
  }
  const boot = getBoot();
  const url = boot?.paths?.apiDashboardOperacional;
  if (!url) {
    return { ok: false, error: 'no_api', dashboard: null };
  }
  let r;
  try {
    r = await fetch(url, {
      credentials: 'same-origin',
      headers: { Accept: 'application/json' },
      cache: 'no-store',
    });
  } catch (err) {
    return {
      ok: false,
      error: err instanceof Error && err.message ? err.message : 'network_error',
      dashboard: null,
    };
  }
  if (!r.ok) {
    return { ok: false, error: r.statusText, dashboard: null };
  }
  let json = {};
  try {
    json = await r.json();
  } catch (_) {
    return { ok: false, error: 'invalid_json', dashboard: null };
  }
  if (!json.ok) {
    return { ok: false, error: json.error || 'erro', dashboard: null };
  }
  return { ok: true, dashboard: json.dashboard || null };
}

export async function postTimerAction(ticketId, action, extra = {}) {
  if (USE_MOCK) {
    await new Promise((r) => setTimeout(r, 150));
    const nowStr = new Date().toISOString().slice(0, 19).replace('T', ' ');
    if (action === 'iniciar') {
      mockTimerSessao = {
        id: 1,
        status: 'running',
        startedAt: nowStr,
        pausedAt: null,
        horaInicio: nowStr,
        horaPausa: null,
        pausado: false,
      };
    } else if (action === 'pausar' && mockTimerSessao) {
      mockTimerSessao = { ...mockTimerSessao, status: 'paused', pausedAt: nowStr, pausado: true, horaPausa: nowStr };
    } else if (action === 'retomar' && mockTimerSessao) {
      const parseMockLocal = (s) => {
        if (!s || typeof s !== 'string') return null;
        const m = /^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2}):(\d{2})/.exec(s.trim());
        if (!m) return null;
        const d = new Date(+m[1], +m[2] - 1, +m[3], +m[4], +m[5], +m[6], 0);
        return Number.isNaN(d.getTime()) ? null : d.getTime();
      };
      const hi = parseMockLocal(mockTimerSessao.horaInicio);
      const hp = parseMockLocal(mockTimerSessao.horaPausa);
      let next = { ...mockTimerSessao, status: 'running', pausedAt: null, pausado: false, horaPausa: null };
      if (hi != null && hp != null) {
        const elapsed = hp - hi;
        const d = new Date(Date.now() - elapsed);
        const p = (n) => (n < 10 ? `0${n}` : String(n));
        const resumedAt = `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())} ${p(d.getHours())}:${p(d.getMinutes())}:${p(d.getSeconds())}`;
        next.horaInicio = resumedAt;
        next.startedAt = resumedAt;
      }
      mockTimerSessao = next;
    } else if (action === 'editar_duracao_sessao' && mockTimerSessao) {
      const secs = Math.max(0, Number(extra?.durationSeconds) || 0);
      const d = new Date(Date.now() - (secs * 1000));
      const p = (n) => (n < 10 ? `0${n}` : String(n));
      const adjusted = `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())} ${p(d.getHours())}:${p(d.getMinutes())}:${p(d.getSeconds())}`;
      mockTimerSessao = { ...mockTimerSessao, horaInicio: adjusted, startedAt: adjusted };
    } else if (action === 'finalizar') {
      mockTimerSessao = null;
    }
    const ultimaFinalizacao =
      action === 'finalizar'
        ? {
            duracaoHms: '00:15:00',
            periodoInicio: '23/04/2026 09:00',
            periodoFim: '23/04/2026 09:15',
          }
        : null;
    return {
      ok: true,
      message: 'ok (mock)',
      horasTecnicas: {
        canUseTimer: true,
        minutosRegistrados: action === 'finalizar' ? 15 : 0,
        sessao: mockTimerSessao,
        serverUnix: Math.floor(Date.now() / 1000),
        timerDisponivel: true,
        ultimaFinalizacao,
      },
    };
  }
  const boot = getBoot();
  const base = boot.paths?.apiTimer;
  if (!base) return { ok: false, error: 'no_api', message: 'apiTimer não configurado no boot.' };
  const r = await fetch(`${base}${encodeURIComponent(ticketId)}`, {
    method: 'POST',
    credentials: 'same-origin',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    body: JSON.stringify({ action, ...(extra && typeof extra === 'object' ? extra : {}) }),
  });
  let json = {};
  try {
    json = await r.json();
  } catch (_) {
    json = {};
  }
  if (!r.ok || !json.ok) {
    return {
      ok: false,
      error: json.error || r.statusText,
      message: json.message || json.error || 'Falha no timer',
      // O PHP envia horasTecnicas mesmo em 400 (ex.: already_running) — necessário para alinhar a UI ao BD.
      horasTecnicas: json.horasTecnicas,
    };
  }
  return {
    ok: true,
    message: json.message,
    horasTecnicas: json.horasTecnicas,
    duracaoMinutosFinal: json.duracaoMinutosFinal,
    durationSecondsFinal: json.durationSecondsFinal,
  };
}

/**
 * Pausa o SLA manualmente (ciclo + relógios do ticket).
 * @param {string|number} ticketId
 */
export async function postTicketSlaPause(ticketId) {
  if (USE_MOCK) {
    return { ok: true, workflow: null, slaByState: null, slaDetail: null, message: 'ok (mock)' };
  }
  const boot = getBoot();
  const base = boot.paths?.apiSlaPause;
  if (!base) return { ok: false, error: 'no_api', message: 'apiSlaPause não configurado no boot.' };
  const r = await fetch(`${base}${encodeURIComponent(ticketId)}`, {
    method: 'POST',
    credentials: 'same-origin',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    body: JSON.stringify({}),
  });
  let json = {};
  try {
    json = await r.json();
  } catch (_) {
    json = {};
  }
  if (!r.ok || !json.ok) {
    return {
      ok: false,
      error: json.error || r.statusText,
      message: json.message || json.error || 'Falha ao pausar SLA',
      workflow: json.workflow,
      slaByState: json.slaByState,
      slaDetail: json.slaDetail,
    };
  }
  return {
    ok: true,
    message: json.message,
    workflow: json.workflow,
    slaByState: json.slaByState,
    slaDetail: json.slaDetail,
  };
}

/**
 * Retoma o SLA manualmente.
 * @param {string|number} ticketId
 */
export async function postTicketSlaResume(ticketId) {
  if (USE_MOCK) {
    return { ok: true, workflow: null, slaByState: null, slaDetail: null, message: 'ok (mock)' };
  }
  const boot = getBoot();
  const base = boot.paths?.apiSlaResume;
  if (!base) return { ok: false, error: 'no_api', message: 'apiSlaResume não configurado no boot.' };
  const r = await fetch(`${base}${encodeURIComponent(ticketId)}`, {
    method: 'POST',
    credentials: 'same-origin',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    body: JSON.stringify({}),
  });
  let json = {};
  try {
    json = await r.json();
  } catch (_) {
    json = {};
  }
  if (!r.ok || !json.ok) {
    return {
      ok: false,
      error: json.error || r.statusText,
      message: json.message || json.error || 'Falha ao retomar SLA',
      workflow: json.workflow,
      slaByState: json.slaByState,
      slaDetail: json.slaDetail,
    };
  }
  return {
    ok: true,
    message: json.message,
    workflow: json.workflow,
    slaByState: json.slaByState,
    slaDetail: json.slaDetail,
  };
}

export async function fetchTimeEntries(ticketId) {
  if (USE_MOCK) {
    return { ok: true, entries: [], technicians: [] };
  }
  const boot = getBoot();
  const base = boot?.paths?.apiTimeEntries;
  if (!base) return { ok: false, error: 'no_api' };
  const r = await fetch(`${base}${encodeURIComponent(ticketId)}`, {
    credentials: 'same-origin',
    cache: 'no-store',
    headers: { Accept: 'application/json' },
  });
  const j = await r.json().catch(() => ({}));
  if (!r.ok || !j.ok) return { ok: false, error: j.error || r.statusText, entries: [], technicians: [] };
  return {
    ok: true,
    entries: Array.isArray(j.entries) ? j.entries : [],
    technicians: Array.isArray(j.technicians) ? j.technicians : [],
  };
}

export async function upsertTimeEntry(ticketId, payload) {
  if (USE_MOCK) {
    return { ok: true, id: Date.now() };
  }
  const boot = getBoot();
  const base = boot?.paths?.apiTimeEntries;
  if (!base) return { ok: false, error: 'no_api' };
  const r = await fetch(`${base}${encodeURIComponent(ticketId)}`, {
    method: 'POST',
    credentials: 'same-origin',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    body: JSON.stringify(payload || {}),
  });
  const j = await r.json().catch(() => ({}));
  if (!r.ok || !j.ok) return { ok: false, error: j.error || r.statusText };
  return { ok: true, id: j.id };
}

export async function deleteTimeEntry(ticketId, entryId, audit = {}) {
  if (USE_MOCK) {
    return { ok: true };
  }
  const boot = getBoot();
  const base = boot?.paths?.apiTimeEntries;
  if (!base) return { ok: false, error: 'no_api' };
  const r = await fetch(`${base}${encodeURIComponent(ticketId)}?id=${encodeURIComponent(entryId)}`, {
    method: 'DELETE',
    credentials: 'same-origin',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    body: JSON.stringify({
      id: entryId,
      auditReason: audit?.reason || '',
      auditAuthKey: audit?.authKey || '',
    }),
  });
  const j = await r.json().catch(() => ({}));
  if (!r.ok || !j.ok) return { ok: false, error: j.error || r.statusText };
  return { ok: true };
}

/**
 * Registo de auditoria de tempo (senha verificada no servidor; não ajusta o timer no estado).
 * @param {{ ticketId: number, userId: number, oldTime: string, newTime: string, reason: string, authKey: string }} p
 */
export async function postAuditValidate(p) {
  if (USE_MOCK) {
    await new Promise((r) => setTimeout(r, 100));
    return { ok: true, message: 'ok (mock)' };
  }
  const boot = getBoot();
  const url = boot?.paths?.apiAuditValidate;
  if (!url) {
    return { ok: false, error: 'no_api', message: 'apiAuditValidate não está no boot.' };
  }
  const body = {
    user_id: p.userId,
    ticket_id: p.ticketId,
    old_time: p.oldTime,
    new_time: p.newTime,
    reason: p.reason,
    authKey: p.authKey,
  };
  const r = await fetch(url, {
    method: 'POST',
    credentials: 'same-origin',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  });
  let json = {};
  try {
    json = await r.json();
  } catch (_) {
    json = {};
  }
  if (!r.ok || !json.ok) {
    return {
      ok: false,
      error: json.error || r.statusText,
      message: json.message || json.error || (r.status === 503 ? 'Serviço indisponível (migrações?)' : 'Falha na autorização'),
      httpStatus: r.status,
    };
  }
  return { ok: true, message: json.message || null };
}

export async function postAlterarSituacao(ticketId, situacao) {
  if (USE_MOCK) {
    await new Promise((r) => setTimeout(r, 120));
    return { ok: true, situacao: Number(situacao), situacaoLabel: 'ok (mock)' };
  }
  const boot = getBoot();
  const base = boot.paths?.apiAlterarSituacao;
  if (!base) {
    return { ok: false, error: 'no_api', message: 'apiAlterarSituacao não configurado no boot.' };
  }
  const r = await fetch(`${base}${encodeURIComponent(ticketId)}`, {
    method: 'POST',
    credentials: 'same-origin',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    body: JSON.stringify({ situacao: Number(situacao) }),
  });
  let json = {};
  try {
    json = await r.json();
  } catch (_) {
    json = {};
  }
  if (!r.ok || !json.ok) {
    return {
      ok: false,
      error: json.error || r.statusText,
      message: json.message || json.error || 'Falha ao alterar situação',
    };
  }
  return {
    ok: true,
    situacao: json.situacao,
    situacaoLabel: json.situacaoLabel,
  };
}

/**
 * Vincula um CI (ativo) ao ticket. Devolve { ok, id }.
 */
export async function attachAssetToTicket(ticketId, assetId, papel = 'afetado') {
  if (USE_MOCK) {
    await new Promise((r) => setTimeout(r, 120));
    return { ok: true, id: Date.now(), asset_id: Number(assetId) };
  }
  const boot = getBoot();
  const base = boot?.paths?.apiTicketAssetsAttach;
  if (!base) return { ok: false, error: 'no_api' };
  const r = await fetch(`${base}${encodeURIComponent(ticketId)}`, {
    method: 'POST',
    credentials: 'same-origin',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    body: JSON.stringify({ asset_id: Number(assetId), papel }),
  });
  let json = {};
  try {
    json = await r.json();
  } catch (_) {
    json = {};
  }
  if (!r.ok || !json.ok) {
    return { ok: false, error: json.error || r.statusText, errors: json.errors };
  }
  return { ok: true, id: json.id, asset_id: json.asset_id, alreadyLinked: !!json.already_linked };
}

/**
 * Desvincula um CI do ticket. Aceita `assetId` ou `ticketAssetId`.
 */
export async function detachAssetFromTicket(ticketId, { assetId, ticketAssetId } = {}) {
  if (USE_MOCK) {
    await new Promise((r) => setTimeout(r, 120));
    return { ok: true };
  }
  const boot = getBoot();
  const base = boot?.paths?.apiTicketAssetsDetach;
  if (!base) return { ok: false, error: 'no_api' };
  const body = ticketAssetId
    ? { ticket_asset_id: Number(ticketAssetId) }
    : { asset_id: Number(assetId) };
  const r = await fetch(`${base}${encodeURIComponent(ticketId)}`, {
    method: 'POST',
    credentials: 'same-origin',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  });
  let json = {};
  try {
    json = await r.json();
  } catch (_) {
    json = {};
  }
  if (!r.ok || !json.ok) {
    return { ok: false, error: json.error || r.statusText };
  }
  return { ok: true, id: json.id };
}

/** Webroot do Cake (`/portal/` ou `/`); sempre com barra final para concatenar rotas da app. */
function normalizeWebrootForFetch(w) {
  if (w === null || w === undefined || w === '') return '/';
  let s = String(w);
  if (!s.startsWith('/')) s = `/${s}`;
  if (!s.endsWith('/')) s = `${s}/`;
  return s;
}

function pickFirstBootPath(paths, keys) {
  for (let i = 0; i < keys.length; i += 1) {
    const v = paths[keys[i]];
    if (typeof v === 'string' && v.trim() !== '') return v.trim();
  }
  return null;
}

/** Garante URL absoluta no site (path começando em `/` ou URL completa). */
function wfSlaAbsUrl(u, webrootNorm) {
  const t = (u || '').trim();
  if (!t) return null;
  if (t.startsWith('http://') || t.startsWith('https://')) return t;
  if (t.startsWith('/')) return t;
  return `${webrootNorm}${t.replace(/^\//, '')}`;
}

/**
 * Garante path absoluto no site (evita POST relativo a partir de /portal/servicedesk/… virar …/servicedesk/servicedesk/…).
 */
function wfSlaAbsolutePathFromBoot(boot, relativeOrAbsolute) {
  const t = (relativeOrAbsolute || '').trim();
  if (!t) return null;
  if (t.startsWith('http://') || t.startsWith('https://')) return t.replace(/\/+$/, '');
  if (t.startsWith('/')) return t.replace(/\/+$/, '');
  const w = normalizeWebrootForFetch(boot?.webroot);
  return `${w}${t.replace(/^\//, '')}`.replace(/\/+$/, '');
}

/** Lista de políticas: usa só `boot.paths.workflowSlaPolicies` quando definido (evita duplicar webroot). */
function wfSlaPolicyListUrl(boot) {
  const v = typeof boot?.paths?.workflowSlaPolicies === 'string' ? boot.paths.workflowSlaPolicies.trim() : '';
  if (v) {
    const abs = wfSlaAbsolutePathFromBoot(boot, v);
    return abs ? abs.replace(/\/+$/, '') : v.replace(/\/+$/, '');
  }
  return `${normalizeWebrootForFetch(boot?.webroot)}servicedesk/workflow-sla-policies`.replace(/\/+$/, '');
}

function wfSlaPolicyBaseFromBoot(boot) {
  const v = typeof boot?.paths?.workflowSlaPolicyBase === 'string' ? boot.paths.workflowSlaPolicyBase.trim() : '';
  if (v) {
    const abs = wfSlaAbsolutePathFromBoot(boot, v);
    const u = abs || v;
    return u.endsWith('/') ? u : `${u}/`;
  }
  return `${normalizeWebrootForFetch(boot?.webroot)}servicedesk/workflow-sla/`;
}

/** Empresas da sessão: só `boot.paths.workflowSlaEmpresas` quando definido. */
function wfSlaEmpresasUrl(boot) {
  const v = typeof boot?.paths?.workflowSlaEmpresas === 'string' ? boot.paths.workflowSlaEmpresas.trim() : '';
  if (v) {
    const abs = wfSlaAbsolutePathFromBoot(boot, v);
    return abs ? abs.replace(/\/+$/, '') : v.replace(/\/+$/, '');
  }
  return `${normalizeWebrootForFetch(boot?.webroot)}servicedesk/workflow-sla-empresas`.replace(/\/+$/, '');
}

function wfSlaPaths() {
  const boot = getBoot();
  const p = boot?.paths || {};
  const w = normalizeWebrootForFetch(boot?.webroot);
  const pick = (keys, relUnderWebroot) => wfSlaAbsUrl(pickFirstBootPath(p, keys), w) || `${w}${String(relUnderWebroot).replace(/^\//, '')}`;
  return {
    policies: wfSlaPolicyListUrl(boot),
    policyBase: wfSlaPolicyBaseFromBoot(boot),
    states: pick(['workflowSlaStates', 'workflowStates'], 'servicedesk/workflow-states').replace(/\/+$/, ''),
    transitions: pick(['workflowSlaTransitions', 'workflowTransitions'], 'servicedesk/workflow-transitions').replace(/\/+$/, ''),
    transitionBase: pick(['workflowTransitionBase'], 'servicedesk/workflow-transitions/').replace(/\/?$/, '/'),
    logs: pick(['workflowSlaLogs'], 'servicedesk/workflow-sla-logs').replace(/\/+$/, ''),
    empresas: wfSlaEmpresasUrl(boot),
  };
}

/** Id inteiro > 0 ou null (criação / id ausente ou inválido — evita PATCH em …/undefined). */
function snippetResponseBody(s, max = 320) {
  const t = String(s || '')
    .replace(/\s+/g, ' ')
    .trim();
  return t.length > max ? `${t.slice(0, max)}…` : t;
}

/**
 * Interpreta resposta do CRUD de políticas SLA (JSON, HTML ou corpo inválido).
 * @returns {Promise<object>}
 */
async function parseWorkflowSlaPolicyResponse(r, url, method) {
  let text = '';
  try {
    text = await r.text();
  } catch (_) {
    text = '';
  }
  const meta = {
    httpStatus: r.status,
    statusText: r.statusText || '',
    requestUrl: url,
    requestMethod: method || '',
  };
  let body = {};
  if (text && text.trim()) {
    try {
      body = JSON.parse(text);
    } catch (_) {
      const line1 = snippetResponseBody((text.split('\n')[0] || text).trim(), 220);
      return {
        ok: false,
        ...meta,
        responseBodySnippet: snippetResponseBody(text),
        error: 'non_json',
        errorMessages: [
          `HTTP ${r.status} — resposta não é JSON válido${line1 ? ` (${line1})` : ''}`,
        ],
      };
    }
  }
  const businessOk = body && body.ok === true;
  if (!r.ok || !businessOk) {
    const msgs = [];
    if (Array.isArray(body.error_messages)) msgs.push(...body.error_messages);
    if (body.error_message) msgs.push(body.error_message);
    if (msgs.length === 0 && body.error) msgs.push(String(body.error));
    if (msgs.length === 0) msgs.push(`HTTP ${r.status} ${meta.statusText}`.trim());
    const sn = snippetResponseBody(text);
    const hasServerMsgs = Array.isArray(body.error_messages) && body.error_messages.length > 0;
    if (sn && !hasServerMsgs) {
      msgs.push(sn);
    }
    return {
      ok: false,
      ...meta,
      responseBodySnippet: sn,
      error: body.error,
      errorMessage: body.error_message,
      errors: body.errors,
      errorMessages: [...new Set(msgs.filter(Boolean))],
    };
  }
  return {
    ok: true,
    policy: body.policy,
    ...meta,
  };
}

export function normalizeWorkflowSlaPolicyId(id) {
  if (id === null || id === undefined || id === '') return null;
  if (typeof id === 'string') {
    const t = id.trim();
    if (t === '' || t === 'undefined' || t === 'null' || t === 'NaN') return null;
  }
  const n = typeof id === 'number' && Number.isFinite(id) ? id : parseInt(String(id).trim(), 10);
  if (!Number.isFinite(n) || n <= 0) return null;
  return n;
}


export async function fetchWorkflowSlaPolicies(filters = {}) {
  if (USE_MOCK) {
    return { ok: true, policies: [], prioridade: '' };
  }
  const boot = getBoot();
  const url = wfSlaPolicyListUrl(boot) + qs(filters);
  const r = await fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' }, cache: 'no-store' });
  const json = await r.json().catch(() => ({}));
  if (!r.ok || !json.ok) {
    return {
      ok: false,
      error: json.error || r.statusText,
      policies: [],
      errors: json.errors,
      errorMessages: Array.isArray(json.error_messages) ? json.error_messages : [],
    };
  }
  return { ok: true, policies: json.policies || [], prioridade: json.prioridade || '' };
}

export async function fetchWorkflowSlaPolicy(id) {
  if (USE_MOCK) return { ok: false, error: 'mock' };
  const nid = normalizeWorkflowSlaPolicyId(id);
  if (nid === null) {
    return {
      ok: false,
      error: 'invalid_policy_id',
      errorMessage: 'Identificador de política inválido.',
      errorMessages: ['Identificador de política inválido.'],
    };
  }
  const r = await fetch(`${wfSlaPaths().policyBase}${encodeURIComponent(nid)}`, {
    credentials: 'same-origin',
    headers: { Accept: 'application/json' },
    cache: 'no-store',
  });
  const json = await r.json().catch(() => ({}));
  if (!r.ok || !json.ok) {
    return {
      ok: false,
      error: json.error || r.statusText,
      errorMessage: json.error_message,
      errors: json.errors,
      errorMessages: Array.isArray(json.error_messages) ? json.error_messages : [],
    };
  }
  return { ok: true, policy: json.policy };
}

/** POST criação — sempre `…/workflow-sla-policies` (sem id na URL). */
export async function createWorkflowSlaPolicy(body) {
  if (USE_MOCK) return { ok: true, policy: { id: 1, ...body } };
  const boot = getBoot();
  const url = wfSlaPolicyListUrl(boot);
  const r = await fetch(url, {
    method: 'POST',
    credentials: 'same-origin',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  });
  return parseWorkflowSlaPolicyResponse(r, url, 'POST');
}

/** PATCH atualização — `…/workflow-sla/{id}` com id inteiro > 0. */
export async function updateWorkflowSlaPolicy(id, body) {
  if (USE_MOCK) return { ok: true, policy: { id, ...body } };
  const nid = normalizeWorkflowSlaPolicyId(id);
  if (nid === null) {
    return {
      ok: false,
      error: 'invalid_policy_id',
      errorMessage: 'Identificador de política inválido para edição.',
      errorMessages: ['Identificador de política inválido para edição.'],
    };
  }
  const boot = getBoot();
  const base = wfSlaPolicyBaseFromBoot(boot);
  const url = `${base}${encodeURIComponent(nid)}`;
  const r = await fetch(url, {
    method: 'PATCH',
    credentials: 'same-origin',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  });
  return parseWorkflowSlaPolicyResponse(r, url, 'PATCH');
}

/** @deprecated Prefer {@link createWorkflowSlaPolicy} / {@link updateWorkflowSlaPolicy}. */
export async function saveWorkflowSlaPolicy(id, body) {
  const nid = normalizeWorkflowSlaPolicyId(id);
  if (nid !== null) return updateWorkflowSlaPolicy(nid, body);
  return createWorkflowSlaPolicy(body);
}

export async function deleteWorkflowSlaPolicy(id) {
  if (USE_MOCK) return { ok: true };
  const nid = normalizeWorkflowSlaPolicyId(id);
  if (nid === null) {
    return {
      ok: false,
      error: 'invalid_policy_id',
      errorMessage: 'Identificador de política inválido.',
      errorMessages: ['Identificador de política inválido.'],
    };
  }
  const r = await fetch(`${wfSlaPaths().policyBase}${encodeURIComponent(nid)}`, {
    method: 'DELETE',
    credentials: 'same-origin',
    headers: { Accept: 'application/json' },
  });
  const json = await r.json().catch(() => ({}));
  if (!r.ok || !json.ok) {
    return {
      ok: false,
      error: json.error || r.statusText,
      errorMessage: json.error_message,
      errors: json.errors,
      errorMessages: Array.isArray(json.error_messages) ? json.error_messages : [],
    };
  }
  return { ok: true };
}

export async function duplicateWorkflowSlaPolicy(id, body = {}) {
  if (USE_MOCK) return { ok: true, policy: { id: 2 } };
  const nid = normalizeWorkflowSlaPolicyId(id);
  if (nid === null) {
    return {
      ok: false,
      error: 'invalid_policy_id',
      errorMessage: 'Identificador de política inválido.',
      errorMessages: ['Identificador de política inválido.'],
    };
  }
  const base = wfSlaPaths().policyBase.replace(/\/?$/, '/');
  const r = await fetch(`${base}${encodeURIComponent(nid)}/duplicate`, {
    method: 'POST',
    credentials: 'same-origin',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  });
  const json = await r.json().catch(() => ({}));
  if (!r.ok || !json.ok) {
    return {
      ok: false,
      error: json.error || r.statusText,
      errorMessage: json.error_message,
      errors: json.errors,
      errorMessages: Array.isArray(json.error_messages) ? json.error_messages : [],
    };
  }
  return { ok: true, policy: json.policy };
}

export async function fetchWorkflowStates() {
  if (USE_MOCK) {
    return {
      ok: true,
      states: [
        { id: 1, nome: 'Aberto', codigo: 'aberto', is_inicial: true, is_final: false },
        { id: 2, nome: 'Em execução', codigo: 'emandamento', is_inicial: false, is_final: false },
      ],
    };
  }
  const r = await fetch(wfSlaPaths().states, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
  const json = await r.json().catch(() => ({}));
  if (!r.ok || !json.ok) return { ok: false, states: [], error: json.error || r.statusText };
  return { ok: true, states: json.states || [] };
}

export async function fetchWorkflowTransitions() {
  if (USE_MOCK) return { ok: true, transitions: [] };
  const r = await fetch(wfSlaPaths().transitions, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
  const json = await r.json().catch(() => ({}));
  if (!r.ok || !json.ok) return { ok: false, transitions: [], error: json.error || r.statusText };
  return { ok: true, transitions: json.transitions || [] };
}

export async function saveWorkflowTransition(body, id = null) {
  if (USE_MOCK) return { ok: true, transition: { id: 1 } };
  const url = id ? `${wfSlaPaths().transitionBase}${encodeURIComponent(id)}` : wfSlaPaths().transitions;
  const r = await fetch(url, {
    method: id ? 'PATCH' : 'POST',
    credentials: 'same-origin',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  });
  const json = await r.json().catch(() => ({}));
  if (!r.ok || !json.ok) return { ok: false, error: json.error || r.statusText, errors: json.errors };
  return { ok: true, transition: json.transition };
}

export async function deleteWorkflowTransition(id) {
  if (USE_MOCK) return { ok: true };
  const r = await fetch(`${wfSlaPaths().transitionBase}${encodeURIComponent(id)}`, {
    method: 'DELETE',
    credentials: 'same-origin',
    headers: { Accept: 'application/json' },
  });
  const json = await r.json().catch(() => ({}));
  if (!r.ok || !json.ok) return { ok: false, error: json.error || r.statusText };
  return { ok: true };
}

export async function fetchWorkflowSlaLogs(limit = 80) {
  if (USE_MOCK) return { ok: true, logs: [] };
  const r = await fetch(`${wfSlaPaths().logs}${qs({ limit })}`, {
    credentials: 'same-origin',
    headers: { Accept: 'application/json' },
  });
  const json = await r.json().catch(() => ({}));
  if (!r.ok || !json.ok) return { ok: false, logs: [], error: json.error || r.statusText };
  return { ok: true, logs: json.logs || [] };
}

export async function fetchWorkflowSlaEmpresas() {
  if (USE_MOCK) return { ok: true, empresas: [{ id: 1, nome: 'PGM', label: 'PGM' }] };
  const boot = getBoot();
  const url = wfSlaEmpresasUrl(boot);
  const r = await fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
  const json = await r.json().catch(() => ({}));
  if (!r.ok || !json.ok) {
    return {
      ok: false,
      empresas: [],
      error: json.error || r.statusText,
      debug: json.debug && typeof json.debug === 'object' ? json.debug : undefined,
    };
  }
  const raw = json.empresas || [];
  const trimStr = (v) => (v != null && String(v).trim() !== '' ? String(v).trim() : '');
  const empresas = raw.map((e) => {
    const label =
      trimStr(e.label) ||
      trimStr(e.nome) ||
      trimStr(e.razaosocial) ||
      trimStr(e.nomefantasia) ||
      (e.id != null ? `Empresa #${e.id}` : '');
    return {
      ...e,
      label,
      nome: label,
      nomefantasia: trimStr(e.nomefantasia),
      razaosocial: trimStr(e.razaosocial),
    };
  });
  const out = { ok: true, empresas };
  if (json.debug && typeof json.debug === 'object') {
    out.debug = json.debug;
  }
  if (Object.prototype.hasOwnProperty.call(json, 'allowedEmpresaIds') || Object.prototype.hasOwnProperty.call(json, 'workflowEmpresasConfigured')) {
    out.slaEmpresasDebug = {
      allowedEmpresaIds: json.allowedEmpresaIds,
      workflowEmpresasConfigured: json.workflowEmpresasConfigured,
    };
  }
  return out;
}

export { MOCK_SESSION_CLIENTE };
