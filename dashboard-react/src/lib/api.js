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

export { getBoot, USE_MOCK, MOCK_SESSION_CLIENTE };
