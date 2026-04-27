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

function mockTicketToTechRow(t) {
  const open =
    t.status === 'Aguardando técnico' ||
    t.status === 'Em execução' ||
    t.status === 'Em andamento';
  const pendente = t.status === 'Aguardando técnico';
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
    assunto: t.assunto,
    situacao: open ? 1 : 2,
    situacaoLabel: t.status,
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
  };
}

export async function fetchTicketsTecnico(filters = {}) {
  if (USE_MOCK) {
    const all = listTicketsForTecnico().map(mockTicketToTechRow);
    const groups = {
      todos: all,
      pendentes: all.filter((t) => t.situacaoLabel === 'Aguardando técnico'),
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
  if (!r.ok) return { ok: false, error: json.error || r.statusText };
  if (!json.ok) return { ok: false, error: json.error || 'erro' };
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
        assunto: t.assunto,
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
    return { ok: false, error: (json && json.error) || r.statusText || 'erro' };
  }
  if (!json || !json.ok) return { ok: false, error: (json && json.error) || 'erro' };
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
    return { ok: false, error: (json && json.error) || r.statusText || 'erro' };
  }
  if (!json || !json.ok) return { ok: false, error: (json && json.error) || 'erro' };
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
      },
    };
  }
  const boot = getBoot();
  const url = boot?.paths?.apiDashboardOperacional;
  if (!url) {
    return { ok: false, error: 'no_api', dashboard: null };
  }
  const r = await fetch(url, {
    credentials: 'same-origin',
    headers: { Accept: 'application/json' },
    cache: 'no-store',
  });
  if (!r.ok) {
    return { ok: false, error: r.statusText, dashboard: null };
  }
  const json = await r.json();
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
      mockTimerSessao = { id: 1, horaInicio: nowStr, horaPausa: null, pausado: false };
    } else if (action === 'pausar' && mockTimerSessao) {
      mockTimerSessao = { ...mockTimerSessao, pausado: true, horaPausa: nowStr };
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
      let next = { ...mockTimerSessao, pausado: false, horaPausa: null };
      if (hi != null && hp != null) {
        const elapsed = hp - hi;
        const d = new Date(Date.now() - elapsed);
        const p = (n) => (n < 10 ? `0${n}` : String(n));
        next.horaInicio = `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())} ${p(d.getHours())}:${p(d.getMinutes())}:${p(d.getSeconds())}`;
      }
      mockTimerSessao = next;
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
    body: JSON.stringify({ action, ...(action === 'iniciar' && extra && typeof extra === 'object' ? extra : {}) }),
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
    };
  }
  return {
    ok: true,
    message: json.message,
    horasTecnicas: json.horasTecnicas,
    duracaoMinutosFinal: json.duracaoMinutosFinal,
  };
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

export { MOCK_SESSION_CLIENTE };
