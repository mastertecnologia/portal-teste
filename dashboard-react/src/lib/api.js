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

export async function fetchTicketsTecnico() {
  if (USE_MOCK) {
    const all = listTicketsForTecnico();
    const groups = {
      todos: all,
      pendentes: all.filter((t) => t.status === 'Aguardando técnico'),
      emandamento: all.filter((t) => t.status === 'Em execução' || t.status === 'Em andamento'),
      resolvidos: all.filter((t) => t.status === 'Resolvido'),
      fechados: all.filter((t) => t.status === 'Cancelado' || t.status === 'Fechado'),
    };
    return { ok: true, groups };
  }
  const boot = getBoot();
  const r = await fetch(boot.paths.apiIndex, { credentials: 'same-origin' });
  if (!r.ok) return { ok: false, error: r.statusText, groups: null };
  const json = await r.json();
  if (!json.ok) return { ok: false, error: json.error || 'erro', groups: null };
  return { ok: true, groups: json.groups };
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
  const r = await fetch(`${boot.paths.apiView}${encodeURIComponent(id)}`, { credentials: 'same-origin' });
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
