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

function getBoot() {
  return typeof window !== 'undefined' ? window.__TICKETS_BOOT__ : null;
}

const USE_MOCK = typeof window !== 'undefined' && !window.__TICKETS_BOOT__;

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
  });
  const r = await fetch(`${boot.paths.apiIndex}${q}`, { credentials: 'same-origin' });
  if (!r.ok) return { ok: false, error: r.statusText, groups: null, workflow: null };
  const json = await r.json();
  if (!json.ok) return { ok: false, error: json.error || 'erro', groups: null, workflow: null };
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
      return { ok: false, error: jPut.error || rPut.statusText };
    }
  }
  if (!legacy) return { ok: false, error: 'no_api' };
  const r = await fetch(`${legacy}${encodeURIComponent(ticketId)}`, { method: 'POST', ...init });
  const json = await readBody(r);
  if (!r.ok) return { ok: false, error: json.error || r.statusText };
  if (!json.ok) return { ok: false, error: json.error || 'erro' };
  return { ok: true };
}

export async function fetchTecnicosParaTransferencia(queueId) {
  if (USE_MOCK) {
    return {
      ok: true,
      tecnicos: [
        { id: 1, name: 'NOC 02' },
        { id: 2, name: 'Service Desk' },
        { id: 3, name: 'Suporte N2' },
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
  });
  const r = await fetch(`${boot.paths.apiIndexCliente}${q}`, { credentials: 'same-origin' });
  if (!r.ok) return { ok: false, error: r.statusText, data: [] };
  const json = await r.json();
  if (!json.ok) return { ok: false, error: json.error || 'erro', data: [] };
  return { ok: true, data: json.tickets || [] };
}

export async function fetchTicketDetail(id) {
  if (USE_MOCK) {
    const t = getTicketById(id);
    return t ? { ok: true, data: t } : { ok: false, error: 'Não encontrado' };
  }
  const boot = getBoot();
  const r = await fetch(`${boot.paths.apiView}${encodeURIComponent(id)}`, {
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
  };
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
  if (!r.ok) return { ok: false, error: r.statusText };
  const json = await r.json();
  if (!json.ok) return { ok: false, error: json.error || 'erro' };
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
  if (!r.ok) return { ok: false, error: r.statusText };
  const json = await r.json();
  if (!json.ok) return { ok: false, error: json.error || 'erro' };
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

export { getBoot, USE_MOCK, MOCK_SESSION_CLIENTE };
