<?php
/**
 * Laudos / Pareceres Técnicos — Lista
 * Toda a lógica de dados é carregada via fetch() contra /api/laudos/pareceres
 */
?>
<div id="pgm-laudos-list-root" class="pgm-content-area" data-empresa-id="<?= h($empresaId) ?>">

  <!-- Cabeçalho da página -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-0"><i data-lucide="clipboard-list" class="icon-sm me-2"></i>Pareceres Técnicos</h4>
      <small class="text-muted">Laudos de avaliação e diagnóstico de equipamentos</small>
    </div>
    <button id="btn-novo-parecer" class="btn btn-primary btn-sm">
      <i data-lucide="plus" class="icon-sm me-1"></i> Novo Parecer
    </button>
  </div>

  <!-- Filtros -->
  <div class="card card-body mb-3 py-2">
    <div class="row g-2 align-items-end">
      <div class="col-md-4">
        <input type="text" id="laudos-search" class="form-control form-control-sm"
               placeholder="Buscar por título, número, cliente, CNPJ…">
      </div>
      <div class="col-md-3">
        <select id="laudos-status-filter" class="form-select form-select-sm">
          <option value="">Todos os status</option>
          <option value="rascunho">Rascunho</option>
          <option value="em_analise">Em análise</option>
          <option value="aprovado">Aprovado</option>
          <option value="concluido">Concluído</option>
          <option value="enviado">Enviado</option>
        </select>
      </div>
      <div class="col-md-auto">
        <button id="btn-filtrar" class="btn btn-outline-secondary btn-sm">Filtrar</button>
      </div>
    </div>
  </div>

  <!-- Tabela -->
  <div class="card">
    <div class="card-body p-0">
      <div id="laudos-loading" class="text-center py-4 text-muted" style="display:none">
        <div class="spinner-border spinner-border-sm me-2"></div> Carregando…
      </div>
      <div id="laudos-empty" class="text-center py-5 text-muted" style="display:none">
        <i data-lucide="inbox" style="width:48px;height:48px;opacity:.3"></i>
        <p class="mt-2 mb-0">Nenhum parecer encontrado.</p>
        <button class="btn btn-primary btn-sm mt-3" id="btn-novo-parecer-empty">
          Criar primeiro parecer
        </button>
      </div>
      <div id="laudos-error" class="alert alert-danger m-3" style="display:none"></div>
      <table id="laudos-table" class="table table-hover table-sm mb-0" style="display:none">
        <thead class="table-light">
          <tr>
            <th style="width:110px">Número</th>
            <th>Título / Cliente</th>
            <th style="width:120px">Status</th>
            <th style="width:130px">Técnico</th>
            <th style="width:120px">Emissão</th>
            <th class="text-end" style="width:120px">Total (R$)</th>
            <th style="width:80px"></th>
          </tr>
        </thead>
        <tbody id="laudos-tbody"></tbody>
      </table>
    </div>
    <!-- Paginação -->
    <div id="laudos-pagination" class="card-footer d-flex justify-content-between align-items-center py-2" style="display:none!important">
      <span id="laudos-pagination-info" class="text-muted small"></span>
      <div class="btn-group btn-group-sm">
        <button id="btn-prev" class="btn btn-outline-secondary" disabled>‹</button>
        <button id="btn-next" class="btn btn-outline-secondary" disabled>›</button>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  'use strict';

  const ROOT = document.getElementById('pgm-laudos-list-root');
  const BASE = '/api/laudos/pareceres';
  let currentPage = 1;
  let totalPages = 1;

  const statusLabels = {
    rascunho: { label: 'Rascunho', cls: 'secondary' },
    em_analise: { label: 'Em análise', cls: 'warning' },
    aprovado: { label: 'Aprovado', cls: 'info' },
    concluido: { label: 'Concluído', cls: 'success' },
    enviado: { label: 'Enviado', cls: 'primary' },
  };

  function getBadge(status) {
    const s = statusLabels[status] || { label: status, cls: 'secondary' };
    return `<span class="badge bg-${s.cls}">${s.label}</span>`;
  }

  function fmtBrl(v) {
    return v !== null && v !== undefined
      ? parseFloat(v).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
      : '—';
  }

  function fmtDate(d) {
    if (!d) return '—';
    const parts = d.split ? d.split('T')[0].split('-') : [];
    return parts.length === 3 ? parts[2] + '/' + parts[1] + '/' + parts[0] : d;
  }

  function showLoading(v) {
    document.getElementById('laudos-loading').style.display = v ? '' : 'none';
    document.getElementById('laudos-table').style.display = v ? 'none' : '';
  }

  function fetchPareceres(page) {
    currentPage = page || 1;
    const q = document.getElementById('laudos-search').value.trim();
    const status = document.getElementById('laudos-status-filter').value;
    let url = `${BASE}?page=${currentPage}&limit=20`;
    if (q) url += '&q=' + encodeURIComponent(q);
    if (status) url += '&status=' + encodeURIComponent(status);

    document.getElementById('laudos-error').style.display = 'none';
    showLoading(true);

    fetch(url, { credentials: 'same-origin' })
      .then(r => r.json())
      .then(json => {
        showLoading(false);
        if (!json.success) throw new Error(json.message || 'Erro');
        renderTable(json.data || []);
        updatePagination(json.pagination || {});
      })
      .catch(err => {
        showLoading(false);
        document.getElementById('laudos-error').style.display = '';
        document.getElementById('laudos-error').textContent = 'Erro ao carregar pareceres: ' + err.message;
      });
  }

  function renderTable(rows) {
    const tbody = document.getElementById('laudos-tbody');
    const empty = document.getElementById('laudos-empty');
    const table = document.getElementById('laudos-table');

    if (!rows.length) {
      table.style.display = 'none';
      empty.style.display = '';
      return;
    }

    empty.style.display = 'none';
    table.style.display = '';

    tbody.innerHTML = rows.map(p => `
      <tr>
        <td><code>${h(p.numero)}</code></td>
        <td>
          <a href="/laudos/pareceres/${p.id}" class="fw-medium">${h(p.titulo)}</a>
          ${p.requester_company_name ? `<br><small class="text-muted">${h(p.requester_company_name)}</small>` : ''}
        </td>
        <td>${getBadge(p.status)}</td>
        <td><small>${h(p.tecnico_nome || '—')}</small></td>
        <td><small>${fmtDate(p.data_emissao)}</small></td>
        <td class="text-end"><small>${fmtBrl(p.total_geral)}</small></td>
        <td class="text-end">
          <a href="/laudos/pareceres/${p.id}" class="btn btn-xs btn-outline-primary" title="Editar">
            <i data-lucide="pencil" style="width:14px;height:14px"></i>
          </a>
        </td>
      </tr>
    `).join('');

    // Re-inicializa ícones Lucide nos novos elementos
    if (window.lucide) lucide.createIcons();
  }

  function h(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function updatePagination(pag) {
    totalPages = pag.pageCount || 1;
    const footer = document.getElementById('laudos-pagination');
    if (totalPages <= 1) {
      footer.style.cssText = 'display:none!important';
      return;
    }
    footer.style.cssText = '';
    document.getElementById('laudos-pagination-info').textContent =
      `Página ${currentPage} de ${totalPages} (${pag.count || 0} registros)`;
    document.getElementById('btn-prev').disabled = currentPage <= 1;
    document.getElementById('btn-next').disabled = currentPage >= totalPages;
  }

  function criarParecer() {
    fetch(BASE, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({})
    })
    .then(r => r.json())
    .then(json => {
      if (json.success && json.data) {
        window.location.href = '/laudos/pareceres/' + json.data.id;
      } else {
        alert('Erro ao criar parecer: ' + (json.errors ? JSON.stringify(json.errors) : 'Tente novamente.'));
      }
    })
    .catch(err => alert('Erro: ' + err.message));
  }

  // Eventos
  document.getElementById('btn-novo-parecer').addEventListener('click', criarParecer);
  const btnNovoEmpty = document.getElementById('btn-novo-parecer-empty');
  if (btnNovoEmpty) btnNovoEmpty.addEventListener('click', criarParecer);

  document.getElementById('btn-filtrar').addEventListener('click', () => fetchPareceres(1));
  document.getElementById('laudos-search').addEventListener('keypress', e => {
    if (e.key === 'Enter') fetchPareceres(1);
  });
  document.getElementById('btn-prev').addEventListener('click', () => fetchPareceres(currentPage - 1));
  document.getElementById('btn-next').addEventListener('click', () => fetchPareceres(currentPage + 1));

  // Carga inicial
  fetchPareceres(1);

  if (window.lucide) lucide.createIcons();
})();
</script>
