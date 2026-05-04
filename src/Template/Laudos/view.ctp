<?php
/**
 * Laudos / Parecer Técnico — Edição
 * Carrega dados via /api/laudos/pareceres/:id e persiste com PUT/PATCH
 */
?>
<div id="pgm-parecer-root" data-id="<?= h($parecerId) ?>">

  <!-- Barra de status -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <a href="/laudos/pareceres" class="btn btn-sm btn-outline-secondary me-2">
        <i data-lucide="arrow-left" style="width:14px;height:14px"></i> Lista
      </a>
      <span id="parecer-numero" class="fs-5 fw-semibold"></span>
      <span id="parecer-status-badge" class="badge ms-2"></span>
    </div>
    <div class="d-flex gap-2 align-items-center">
      <span id="save-indicator" class="text-muted small" style="display:none">
        <span class="spinner-border spinner-border-sm me-1" id="save-spinner" style="display:none"></span>
        <span id="save-msg">Tudo salvo</span>
      </span>
      <button id="btn-change-status" class="btn btn-sm btn-outline-primary" style="display:none">
        Avançar status
      </button>
      <a id="btn-pdf" href="#" target="_blank" class="btn btn-sm btn-outline-danger">
        <i data-lucide="file-text" style="width:14px;height:14px"></i> PDF
      </a>
      <button id="btn-email" class="btn btn-sm btn-outline-secondary">
        <i data-lucide="mail" style="width:14px;height:14px"></i> E-mail
      </button>
    </div>
  </div>

  <div id="parecer-loading" class="text-center py-5 text-muted">
    <div class="spinner-border"></div><p class="mt-3">Carregando parecer…</p>
  </div>
  <div id="parecer-error" class="alert alert-danger" style="display:none"></div>

  <!-- Formulário principal -->
  <div id="parecer-form-area" style="display:none">
    <div class="row g-3">

      <!-- Coluna esquerda: dados do parecer -->
      <div class="col-lg-8">

        <!-- Dados gerais -->
        <div class="card mb-3">
          <div class="card-header fw-medium">Dados gerais</div>
          <div class="card-body">
            <div class="row g-2">
              <div class="col-md-12">
                <label class="form-label form-label-sm">Título</label>
                <input type="text" id="f-titulo" class="form-control form-control-sm auto-save" data-field="titulo">
              </div>
              <div class="col-md-6">
                <label class="form-label form-label-sm">Técnico responsável</label>
                <input type="text" id="f-tecnico-nome" class="form-control form-control-sm auto-save" data-field="tecnico_nome">
              </div>
              <div class="col-md-3">
                <label class="form-label form-label-sm">Registro (CRT/CRA)</label>
                <input type="text" id="f-tecnico-registro" class="form-control form-control-sm auto-save" data-field="tecnico_registro">
              </div>
              <div class="col-md-3">
                <label class="form-label form-label-sm">Data de emissão</label>
                <input type="date" id="f-data-emissao" class="form-control form-control-sm auto-save" data-field="data_emissao">
              </div>
              <div class="col-md-6">
                <label class="form-label form-label-sm">Cidade</label>
                <input type="text" id="f-cidade" class="form-control form-control-sm auto-save" data-field="cidade">
              </div>
            </div>
          </div>
        </div>

        <!-- Cliente / Requerente -->
        <div class="card mb-3">
          <div class="card-header fw-medium d-flex justify-content-between align-items-center">
            Cliente / Requerente
            <button id="btn-buscar-cliente" class="btn btn-xs btn-outline-secondary" title="Buscar cliente cadastrado">
              <i data-lucide="search" style="width:12px;height:12px"></i> Buscar no cadastro
            </button>
          </div>
          <div class="card-body">
            <!-- Busca de clientes (oculta inicialmente) -->
            <div id="cliente-search-box" class="mb-3" style="display:none">
              <div class="input-group input-group-sm">
                <input type="text" id="cliente-search-input" class="form-control"
                       placeholder="Nome ou CNPJ do cliente…">
                <button id="btn-executar-busca-cliente" class="btn btn-outline-secondary">Buscar</button>
              </div>
              <div id="cliente-search-results" class="list-group mt-1" style="max-height:200px;overflow-y:auto"></div>
            </div>
            <div class="row g-2">
              <div class="col-md-6">
                <label class="form-label form-label-sm">Empresa / Nome</label>
                <input type="text" id="f-req-company" class="form-control form-control-sm auto-save" data-field="requester_company_name">
              </div>
              <div class="col-md-6">
                <label class="form-label form-label-sm">CNPJ / CPF</label>
                <div class="input-group input-group-sm">
                  <input type="text" id="f-req-cnpj" class="form-control auto-save" data-field="requester_cnpj"
                         placeholder="00.000.000/0001-00">
                  <button id="btn-buscar-cnpj" class="btn btn-outline-secondary" title="Buscar CNPJ na Receita Federal">
                    <i data-lucide="search" style="width:12px;height:12px"></i>
                  </button>
                </div>
              </div>
              <div class="col-md-6">
                <label class="form-label form-label-sm">A/C (atenção)</label>
                <input type="text" id="f-req-attention" class="form-control form-control-sm auto-save" data-field="requester_attention_to">
              </div>
              <div class="col-md-6">
                <label class="form-label form-label-sm">Telefone</label>
                <input type="text" id="f-req-phone" class="form-control form-control-sm auto-save" data-field="requester_phone">
              </div>
              <div class="col-md-6">
                <label class="form-label form-label-sm">E-mail</label>
                <input type="email" id="f-req-email" class="form-control form-control-sm auto-save" data-field="requester_email">
              </div>
              <div class="col-md-3">
                <label class="form-label form-label-sm">CEP</label>
                <input type="text" id="f-req-cep" class="form-control form-control-sm auto-save" data-field="requester_cep">
              </div>
              <div class="col-md-9">
                <label class="form-label form-label-sm">Endereço</label>
                <input type="text" id="f-req-address" class="form-control form-control-sm auto-save" data-field="requester_address">
              </div>
            </div>
          </div>
        </div>

        <!-- Objetivo e Documentação -->
        <div class="card mb-3">
          <div class="card-header fw-medium">Objetivo do Parecer</div>
          <div class="card-body">
            <div class="d-flex justify-content-end mb-1">
              <button class="btn btn-xs btn-outline-secondary" onclick="laudosApp.abrirTemplates('objetivo','f-objetivo')">
                Usar template
              </button>
            </div>
            <textarea id="f-objetivo" class="form-control form-control-sm auto-save" rows="3"
                      data-field="objetivo"></textarea>
          </div>
        </div>

        <!-- Equipamentos -->
        <div class="card mb-3">
          <div class="card-header fw-medium d-flex justify-content-between align-items-center">
            Equipamentos avaliados
            <button id="btn-add-produto" class="btn btn-xs btn-primary">
              <i data-lucide="plus" style="width:12px;height:12px"></i> Adicionar equipamento
            </button>
          </div>
          <div class="card-body p-0">
            <div id="produtos-container"></div>
            <div id="produtos-empty" class="text-center py-4 text-muted">
              Nenhum equipamento adicionado ainda.
            </div>
          </div>
        </div>

        <!-- Conclusão -->
        <div class="card mb-3">
          <div class="card-header fw-medium">Conclusão</div>
          <div class="card-body">
            <div class="d-flex justify-content-end mb-1">
              <button class="btn btn-xs btn-outline-secondary" onclick="laudosApp.abrirTemplates('conclusao','f-conclusao')">
                Usar template
              </button>
            </div>
            <textarea id="f-conclusao" class="form-control form-control-sm auto-save" rows="6"
                      data-field="conclusao"></textarea>
          </div>
        </div>

        <!-- Documentação considerada -->
        <div class="card mb-3">
          <div class="card-header fw-medium">Documentação considerada</div>
          <div class="card-body">
            <textarea id="f-documentacao" class="form-control form-control-sm auto-save" rows="3"
                      data-field="documentacao"></textarea>
          </div>
        </div>

      </div><!-- /col-lg-8 -->

      <!-- Coluna direita: resumo financeiro + histórico -->
      <div class="col-lg-4">

        <!-- Comparativo -->
        <div class="card mb-3">
          <div class="card-header fw-medium">Resumo financeiro</div>
          <div class="card-body">
            <div class="d-flex justify-content-between mb-1">
              <small class="text-muted">Total peças</small>
              <span id="resumo-total-pecas" class="fw-medium">—</span>
            </div>
            <div class="d-flex justify-content-between mb-1">
              <small class="text-muted">Total serviços</small>
              <span id="resumo-total-servicos" class="fw-medium">—</span>
            </div>
            <hr class="my-2">
            <div class="d-flex justify-content-between mb-2">
              <small class="fw-semibold">Total reparo</small>
              <span id="resumo-total-geral" class="fw-semibold text-primary">—</span>
            </div>
            <div class="mb-2">
              <label class="form-label form-label-sm">Valor equip. novo equivalente</label>
              <input type="number" id="f-estimated-new" class="form-control form-control-sm auto-save"
                     data-field="estimated_new_equipment" step="0.01" placeholder="0.00">
            </div>
            <div id="resumo-percentual" class="text-center" style="display:none">
              <div class="fs-3 fw-bold" id="resumo-pct-num"></div>
              <small class="text-muted">do custo de substituição</small>
            </div>
          </div>
        </div>

        <!-- Histórico -->
        <div class="card mb-3">
          <div class="card-header fw-medium d-flex justify-content-between">
            Histórico de alterações
            <button class="btn btn-xs btn-outline-secondary" id="btn-reload-historico">↺</button>
          </div>
          <div class="card-body p-2">
            <div id="historico-container" style="max-height:250px;overflow-y:auto">
              <p class="text-muted small text-center py-3">Carregando…</p>
            </div>
          </div>
        </div>

      </div><!-- /col-lg-4 -->
    </div><!-- /row -->
  </div><!-- /parecer-form-area -->

</div><!-- /pgm-parecer-root -->

<!-- Modal: template de texto -->
<div class="modal fade" id="modal-templates" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title mb-0">Selecionar template</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-2" id="modal-templates-body">
        <p class="text-muted text-center py-3">Carregando templates…</p>
      </div>
    </div>
  </div>
</div>

<!-- Modal: enviar e-mail -->
<div class="modal fade" id="modal-email" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title mb-0">Enviar por e-mail</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
          <label class="form-label form-label-sm">Para (e-mail)</label>
          <input type="email" id="email-to" class="form-control form-control-sm" placeholder="cliente@empresa.com">
        </div>
        <div class="mb-2">
          <label class="form-label form-label-sm">CC (opcional)</label>
          <input type="email" id="email-cc" class="form-control form-control-sm">
        </div>
        <div class="mb-2">
          <label class="form-label form-label-sm">Assunto</label>
          <input type="text" id="email-subject" class="form-control form-control-sm">
        </div>
      </div>
      <div class="modal-footer py-2">
        <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-sm btn-primary" id="btn-enviar-email-confirm">Enviar</button>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  'use strict';

  const parecerId = parseInt(document.getElementById('pgm-parecer-root').dataset.id, 10);
  const BASE_PARECERES = '/api/laudos/pareceres';
  const BASE_PRODUTOS = '/api/laudos/produtos';
  const BASE_CATALOGO = '/api/laudos/catalogo';
  const BASE_TEMPLATES = '/api/laudos/templates';

  let parecer = null;
  let totais = {};
  let saveTimeout = null;
  let pendingFields = {};

  const statusLabels = {
    rascunho: { label: 'Rascunho', cls: 'secondary', next: 'em_analise', nextLabel: 'Enviar para análise' },
    em_analise: { label: 'Em análise', cls: 'warning', next: 'aprovado', nextLabel: 'Aprovar' },
    aprovado: { label: 'Aprovado', cls: 'info', next: 'concluido', nextLabel: 'Concluir' },
    concluido: { label: 'Concluído', cls: 'success', next: 'enviado', nextLabel: 'Marcar enviado' },
    enviado: { label: 'Enviado', cls: 'primary', next: null, nextLabel: '' },
  };

  function fmtBrl(v) {
    return v !== null && v !== undefined
      ? parseFloat(v).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
      : '—';
  }

  function h(str) {
    if (!str && str !== 0) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  // ---- Carregamento do parecer ----
  function loadParecer() {
    fetch(`${BASE_PARECERES}/${parecerId}`, { credentials: 'same-origin' })
      .then(r => r.json())
      .then(json => {
        if (!json.success) throw new Error(json.message || 'Erro');
        parecer = json.data;
        totais = json.totais || {};
        renderForm();
        renderProdutos();
        loadHistorico();
      })
      .catch(err => {
        document.getElementById('parecer-loading').style.display = 'none';
        document.getElementById('parecer-error').style.display = '';
        document.getElementById('parecer-error').textContent = 'Erro ao carregar parecer: ' + err.message;
      });
  }

  function renderForm() {
    document.getElementById('parecer-loading').style.display = 'none';
    document.getElementById('parecer-form-area').style.display = '';

    // Cabeçalho
    document.getElementById('parecer-numero').textContent = parecer.numero;
    const st = statusLabels[parecer.status] || { label: parecer.status, cls: 'secondary' };
    const badge = document.getElementById('parecer-status-badge');
    badge.textContent = st.label;
    badge.className = `badge bg-${st.cls} ms-2`;

    // Botão de avançar status
    const btnStatus = document.getElementById('btn-change-status');
    if (st.next) {
      btnStatus.textContent = st.nextLabel;
      btnStatus.style.display = '';
      btnStatus.dataset.nextStatus = st.next;
    } else {
      btnStatus.style.display = 'none';
    }

    // Link PDF
    document.getElementById('btn-pdf').href = `${BASE_PARECERES}/${parecerId}/pdf`;

    // Campos do formulário
    const fields = {
      'f-titulo': 'titulo',
      'f-tecnico-nome': 'tecnico_nome',
      'f-tecnico-registro': 'tecnico_registro',
      'f-data-emissao': 'data_emissao',
      'f-cidade': 'cidade',
      'f-req-company': 'requester_company_name',
      'f-req-cnpj': 'requester_cnpj',
      'f-req-attention': 'requester_attention_to',
      'f-req-phone': 'requester_phone',
      'f-req-email': 'requester_email',
      'f-req-cep': 'requester_cep',
      'f-req-address': 'requester_address',
      'f-objetivo': 'objetivo',
      'f-conclusao': 'conclusao',
      'f-documentacao': 'documentacao',
      'f-estimated-new': 'estimated_new_equipment',
    };

    for (const [elId, field] of Object.entries(fields)) {
      const el = document.getElementById(elId);
      if (el) {
        const val = parecer[field];
        el.value = val !== null && val !== undefined ? val : '';
        // Desabilita se não pode editar
        if (!parecer.pode_editar) el.disabled = true;
      }
    }

    renderTotais(totais);
    document.getElementById('save-indicator').style.display = '';
    document.getElementById('save-msg').textContent = 'Tudo salvo';
    if (window.lucide) lucide.createIcons();
  }

  function renderTotais(t) {
    document.getElementById('resumo-total-pecas').textContent = fmtBrl(t.total_pecas);
    document.getElementById('resumo-total-servicos').textContent = fmtBrl(t.total_servicos);
    document.getElementById('resumo-total-geral').textContent = fmtBrl(t.total_geral);

    if (t.percentual_reparo !== null && t.percentual_reparo !== undefined) {
      document.getElementById('resumo-percentual').style.display = '';
      const pct = parseFloat(t.percentual_reparo);
      const el = document.getElementById('resumo-pct-num');
      el.textContent = pct.toFixed(1) + '%';
      el.className = pct < 60 ? 'fs-3 fw-bold text-success' : 'fs-3 fw-bold text-danger';
    } else {
      document.getElementById('resumo-percentual').style.display = 'none';
    }
  }

  // ---- Auto-save ----
  function scheduleAutoSave() {
    document.getElementById('save-spinner').style.display = '';
    document.getElementById('save-msg').textContent = 'Salvando…';
    clearTimeout(saveTimeout);
    saveTimeout = setTimeout(executeSave, 800);
  }

  function executeSave() {
    const payload = Object.assign({}, pendingFields);
    pendingFields = {};

    fetch(`${BASE_PARECERES}/${parecerId}`, {
      method: 'PATCH',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })
    .then(r => r.json())
    .then(json => {
      document.getElementById('save-spinner').style.display = 'none';
      if (json.success) {
        document.getElementById('save-msg').textContent = 'Tudo salvo ✓';
        if (json.totais) renderTotais(json.totais);
      } else {
        document.getElementById('save-msg').textContent = 'Erro ao salvar';
      }
    })
    .catch(() => {
      document.getElementById('save-spinner').style.display = 'none';
      document.getElementById('save-msg').textContent = 'Erro ao salvar';
    });
  }

  // ---- Produtos / Equipamentos ----
  function renderProdutos() {
    const container = document.getElementById('produtos-container');
    const empty = document.getElementById('produtos-empty');
    const produtos = parecer.laudos_produtos || [];

    if (!produtos.length) {
      container.innerHTML = '';
      empty.style.display = '';
      return;
    }

    empty.style.display = 'none';
    container.innerHTML = produtos.map(p => renderProdutoCard(p)).join('');
    if (window.lucide) lucide.createIcons();
  }

  function renderProdutoCard(p) {
    const recomendacaoOpts = [
      { v: 'replace', l: 'Substituir' },
      { v: 'repair', l: 'Reparar' },
      { v: 'partial', l: 'Parcial' },
    ].map(o => `<option value="${o.v}" ${p.recomendacao === o.v ? 'selected' : ''}>${o.l}</option>`).join('');

    const imagens = (p.laudos_produto_imagens || []).map(img =>
      `<div class="d-inline-block me-1 mb-1 position-relative">
        <img src="${h(img.url || img.file_path)}" style="width:64px;height:64px;object-fit:cover;border-radius:4px">
        <button class="btn btn-xxs btn-danger position-absolute top-0 end-0"
                onclick="laudosApp.deleteImagem(${img.id}, ${p.id})"
                title="Remover foto" style="padding:1px 4px;font-size:10px">✕</button>
      </div>`
    ).join('');

    const pecas = (p.laudos_produto_pecas || []).map(pe =>
      `<tr>
        <td>${h(pe.nome)}</td>
        <td class="text-center">${pe.quantidade}</td>
        <td class="text-end">${fmtBrl(pe.preco_unitario)}</td>
        <td class="text-end">${fmtBrl(pe.subtotal || pe.quantidade * pe.preco_unitario)}</td>
        <td><button class="btn btn-xxs btn-outline-danger" onclick="laudosApp.deletePeca(${pe.id}, ${p.id})">✕</button></td>
      </tr>`
    ).join('');

    return `<div class="border-bottom p-3" id="produto-card-${p.id}">
      <div class="row g-2 align-items-start">
        <div class="col-md-4">
          <label class="form-label form-label-sm">Nome / modelo</label>
          <input type="text" class="form-control form-control-sm produto-field"
                 data-produto-id="${p.id}" data-field="nome" value="${h(p.nome || '')}">
        </div>
        <div class="col-md-3">
          <label class="form-label form-label-sm">Tipo</label>
          <input type="text" class="form-control form-control-sm produto-field"
                 data-produto-id="${p.id}" data-field="tipo" value="${h(p.tipo || '')}">
        </div>
        <div class="col-md-3">
          <label class="form-label form-label-sm">Nº de série</label>
          <input type="text" class="form-control form-control-sm produto-field"
                 data-produto-id="${p.id}" data-field="serial_number" value="${h(p.serial_number || '')}">
        </div>
        <div class="col-md-2">
          <label class="form-label form-label-sm">Recomendação</label>
          <select class="form-select form-select-sm produto-field"
                  data-produto-id="${p.id}" data-field="recomendacao">
            ${recomendacaoOpts}
          </select>
        </div>
        <div class="col-md-12">
          <label class="form-label form-label-sm d-flex justify-content-between">
            Diagnóstico
            <button class="btn btn-xxs btn-outline-secondary"
                    onclick="laudosApp.abrirTemplates('diagnostico', null, ${p.id}, 'diagnostico')">
              Template
            </button>
          </label>
          <textarea class="form-control form-control-sm produto-field" rows="3"
                    data-produto-id="${p.id}" data-field="diagnostico">${h(p.diagnostico || '')}</textarea>
        </div>
        <!-- Peças -->
        <div class="col-md-12">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <small class="fw-medium">Peças / componentes</small>
            <button class="btn btn-xxs btn-outline-primary" onclick="laudosApp.addPeca(${p.id})">+ Adicionar peça</button>
          </div>
          ${pecas ? `<table class="table table-sm table-bordered mb-2"><thead class="table-light"><tr><th>Descrição</th><th class="text-center" style="width:80px">Qtd</th><th class="text-end" style="width:100px">Unitário</th><th class="text-end" style="width:100px">Subtotal</th><th style="width:40px"></th></tr></thead><tbody id="pecas-tbody-${p.id}">${pecas}</tbody></table>` : `<div id="pecas-tbody-wrap-${p.id}"><table class="table table-sm table-bordered mb-2" style="display:none"><thead class="table-light"><tr><th>Descrição</th><th class="text-center" style="width:80px">Qtd</th><th class="text-end" style="width:100px">Unitário</th><th class="text-end" style="width:100px">Subtotal</th><th style="width:40px"></th></tr></thead><tbody id="pecas-tbody-${p.id}"></tbody></table></div>`}
        </div>
        <!-- Fotos -->
        <div class="col-md-12">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <small class="fw-medium">Fotos</small>
            <label class="btn btn-xxs btn-outline-secondary mb-0">
              + Foto
              <input type="file" accept="image/*" style="display:none"
                     onchange="laudosApp.uploadImagem(this, ${p.id})">
            </label>
          </div>
          <div id="imagens-container-${p.id}" class="d-flex flex-wrap">${imagens || '<span class="text-muted small">Sem fotos</span>'}</div>
        </div>
        <!-- Rodapé do card -->
        <div class="col-md-12 d-flex justify-content-end">
          <button class="btn btn-xs btn-outline-danger"
                  onclick="laudosApp.deleteProduto(${p.id})">
            <i data-lucide="trash-2" style="width:12px;height:12px"></i> Remover equipamento
          </button>
        </div>
      </div>
    </div>`;
  }

  // ---- Histórico ----
  function loadHistorico() {
    fetch(`${BASE_PARECERES}/${parecerId}/historico`, { credentials: 'same-origin' })
      .then(r => r.json())
      .then(json => {
        const container = document.getElementById('historico-container');
        if (!json.success || !json.data.length) {
          container.innerHTML = '<p class="text-muted small text-center py-2">Sem histórico.</p>';
          return;
        }
        container.innerHTML = json.data.map(ev => {
          const d = new Date(ev.created);
          const dt = isNaN(d) ? ev.created : d.toLocaleString('pt-BR');
          return `<div class="border-bottom py-1 px-1">
            <div class="d-flex justify-content-between">
              <small class="fw-medium">${h(ev.action)}</small>
              <small class="text-muted">${dt}</small>
            </div>
            <small class="text-muted">${h(ev.user_name_snapshot || '')}</small>
          </div>`;
        }).join('');
      })
      .catch(() => {
        document.getElementById('historico-container').innerHTML =
          '<p class="text-danger small text-center py-2">Erro ao carregar histórico.</p>';
      });
  }

  // =====================================================================
  // Expõe API global para handlers inline do HTML
  // =====================================================================
  window.laudosApp = {

    abrirTemplates: function(tipo, targetId, produtoId, field) {
      const body = document.getElementById('modal-templates-body');
      body.innerHTML = '<p class="text-muted text-center py-3">Carregando…</p>';

      const modal = new bootstrap.Modal(document.getElementById('modal-templates'));
      modal.show();

      fetch(`${BASE_TEMPLATES}/${tipo}`, { credentials: 'same-origin' })
        .then(r => r.json())
        .then(json => {
          if (!json.success || !json.data.length) {
            body.innerHTML = '<p class="text-muted text-center py-2">Nenhum template disponível.</p>';
            return;
          }
          body.innerHTML = json.data.map(t => `
            <div class="card card-body mb-2 p-2 cursor-pointer" style="cursor:pointer"
                 onclick="laudosApp.aplicarTemplate(${JSON.stringify(t.conteudo)}, '${targetId}', ${produtoId || 'null'}, '${field || ''}')">
              <div class="fw-medium small">${h(t.nome)}</div>
              <div class="text-muted small mt-1" style="white-space:pre-wrap;max-height:80px;overflow:hidden">${h(t.conteudo.substring(0, 200))}…</div>
            </div>
          `).join('');
        })
        .catch(() => {
          body.innerHTML = '<p class="text-danger small text-center py-2">Erro ao carregar templates.</p>';
        });
    },

    aplicarTemplate: function(conteudo, targetId, produtoId, field) {
      if (targetId) {
        const el = document.getElementById(targetId);
        if (el) {
          el.value = conteudo;
          el.dispatchEvent(new Event('input'));
        }
      } else if (produtoId && field) {
        saveProdutoField(produtoId, field, conteudo);
        const el = document.querySelector(`[data-produto-id="${produtoId}"][data-field="${field}"]`);
        if (el) el.value = conteudo;
      }
      bootstrap.Modal.getInstance(document.getElementById('modal-templates'))?.hide();
    },

    deleteProduto: function(id) {
      if (!confirm('Remover este equipamento e todos os seus dados?')) return;
      fetch(`${BASE_PRODUTOS}/${id}`, { method: 'DELETE', credentials: 'same-origin' })
        .then(r => r.json())
        .then(json => {
          if (json.success) {
            const card = document.getElementById('produto-card-' + id);
            if (card) card.remove();
            // Verifica se ficou vazio
            if (!document.querySelector('[id^="produto-card-"]')) {
              document.getElementById('produtos-empty').style.display = '';
            }
          } else {
            alert('Erro ao remover equipamento.');
          }
        });
    },

    uploadImagem: function(input, produtoId) {
      const file = input.files[0];
      if (!file) return;
      const fd = new FormData();
      fd.append('produto_id', produtoId);
      fd.append('file', file);

      fetch('/api/laudos/produto-imagens', { method: 'POST', credentials: 'same-origin', body: fd })
        .then(r => r.json())
        .then(json => {
          if (json.success) {
            loadParecer(); // Recarrega para mostrar nova imagem
          } else {
            alert('Erro ao enviar foto: ' + JSON.stringify(json.errors || json.message));
          }
        })
        .catch(err => alert('Erro: ' + err.message));
    },

    deleteImagem: function(imgId, produtoId) {
      if (!confirm('Remover esta foto?')) return;
      fetch(`/api/laudos/produto-imagens/${imgId}`, { method: 'DELETE', credentials: 'same-origin' })
        .then(r => r.json())
        .then(json => {
          if (json.success) {
            const container = document.getElementById('imagens-container-' + produtoId);
            // Re-renderiza buscando a imagem pelo botão pai
            loadParecer();
          }
        });
    },

    addPeca: function(produtoId) {
      const nome = prompt('Nome da peça/componente:');
      if (!nome) return;
      const qtd = parseFloat(prompt('Quantidade:', '1') || '1');
      const preco = parseFloat(prompt('Preço unitário (R$):', '0') || '0');

      // Salva via PATCH do produto com peça aninhada — o controller aceita associated data
      fetch(`${BASE_PRODUTOS}/${produtoId}`, {
        method: 'PATCH',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          laudos_produto_pecas: [{ nome, quantidade: qtd, preco_unitario: preco }]
        }),
      })
      .then(r => r.json())
      .then(json => {
        if (json.success) loadParecer();
        else alert('Erro ao adicionar peça: ' + JSON.stringify(json.errors));
      });
    },

    deletePeca: function(pecaId, produtoId) {
      if (!confirm('Remover esta peça?')) return;
      // Não há rota DELETE para peça individual — remove via PATCH do produto enviando lista sem o item
      // Por simplicidade, recarrega o parecer e o usuário pode remover via interface
      alert('Para remover peças individualmente, edite o equipamento. Esta funcionalidade será expandida em breve.');
    },
  };

  // ---- Eventos de auto-save dos campos do parecer ----
  document.querySelectorAll('.auto-save').forEach(function(el) {
    el.addEventListener('input', function() {
      pendingFields[el.dataset.field] = el.value;
      scheduleAutoSave();
    });
  });

  // ---- Eventos de save dos campos de produto ----
  document.addEventListener('input', function(e) {
    const el = e.target;
    if (!el.classList.contains('produto-field')) return;
    const produtoId = parseInt(el.dataset.produtoId, 10);
    const field = el.dataset.field;
    clearTimeout(el._saveTimeout);
    el._saveTimeout = setTimeout(function() {
      saveProdutoField(produtoId, field, el.value);
    }, 800);
  });

  function saveProdutoField(produtoId, field, value) {
    const payload = {};
    payload[field] = value;
    fetch(`${BASE_PRODUTOS}/${produtoId}`, {
      method: 'PATCH',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    }).then(r => r.json()).then(json => {
      if (json.success && json.data) {
        // Atualiza totais se o campo afeta valores
        loadTotais();
      }
    });
  }

  function loadTotais() {
    fetch(`${BASE_PARECERES}/${parecerId}`, { credentials: 'same-origin' })
      .then(r => r.json())
      .then(json => { if (json.totais) renderTotais(json.totais); });
  }

  // ---- Botão de avançar status ----
  document.getElementById('btn-change-status').addEventListener('click', function() {
    const nextStatus = this.dataset.nextStatus;
    if (!nextStatus) return;
    if (!confirm(`Avançar status para "${statusLabels[nextStatus]?.label || nextStatus}"?`)) return;

    fetch(`${BASE_PARECERES}/${parecerId}/status`, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ status: nextStatus }),
    })
    .then(r => r.json())
    .then(json => {
      if (json.success) loadParecer();
      else alert('Erro ao alterar status: ' + JSON.stringify(json.errors));
    });
  });

  // ---- Botão de adicionar produto ----
  document.getElementById('btn-add-produto').addEventListener('click', function() {
    fetch(BASE_PRODUTOS, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ parecer_id: parecerId, nome: 'Equipamento' }),
    })
    .then(r => r.json())
    .then(json => {
      if (json.success) loadParecer();
      else alert('Erro ao adicionar equipamento');
    });
  });

  // ---- E-mail ----
  document.getElementById('btn-email').addEventListener('click', function() {
    // Preenche destinatário padrão
    const emailTo = document.getElementById('f-req-email')?.value || '';
    document.getElementById('email-to').value = emailTo;
    document.getElementById('email-subject').value =
      'Parecer Técnico nº ' + (document.getElementById('parecer-numero').textContent || '');
    new bootstrap.Modal(document.getElementById('modal-email')).show();
  });

  document.getElementById('btn-enviar-email-confirm').addEventListener('click', function() {
    const to = document.getElementById('email-to').value;
    const cc = document.getElementById('email-cc').value;
    const subject = document.getElementById('email-subject').value;

    this.disabled = true;
    this.textContent = 'Enviando…';

    fetch(`${BASE_PARECERES}/${parecerId}/enviar-email`, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ to, cc, subject }),
    })
    .then(r => r.json())
    .then(json => {
      bootstrap.Modal.getInstance(document.getElementById('modal-email'))?.hide();
      alert(json.success ? '✓ ' + json.message : '✗ ' + json.message);
      if (json.success) loadParecer();
    })
    .catch(err => alert('Erro: ' + err.message))
    .finally(() => {
      this.disabled = false;
      this.textContent = 'Enviar';
    });
  });

  // ---- Recarregar histórico ----
  document.getElementById('btn-reload-historico').addEventListener('click', loadHistorico);

  // ---- Busca de cliente ----
  document.getElementById('btn-buscar-cliente').addEventListener('click', function() {
    const box = document.getElementById('cliente-search-box');
    box.style.display = box.style.display === 'none' ? '' : 'none';
  });

  document.getElementById('btn-executar-busca-cliente').addEventListener('click', function() {
    const q = document.getElementById('cliente-search-input').value.trim();
    if (!q) return;
    fetch('/clientes/index.json?q=' + encodeURIComponent(q) + '&limit=10', { credentials: 'same-origin' })
      .then(r => r.json())
      .then(json => {
        const results = document.getElementById('cliente-search-results');
        const items = json.data || json.clientes || [];
        if (!items.length) {
          results.innerHTML = '<div class="list-group-item text-muted small">Nenhum resultado</div>';
          return;
        }
        results.innerHTML = items.map(c => `
          <button class="list-group-item list-group-item-action py-1 px-2" style="font-size:13px"
                  onclick="laudosApp.aplicarCliente(${c.id}, '${h(c.razao_social || c.nome || '')}', '${h(c.cnpj || '')}', '${h(c.telefone || '')}', '${h(c.email || '')}', '${h(c.cep || '')}', '${h(c.endereco || '')}')">
            <div class="fw-medium">${h(c.razao_social || c.nome || '')}</div>
            <div class="text-muted">${h(c.cnpj || '')}</div>
          </button>
        `).join('');
      })
      .catch(() => {});
  });

  // Adiciona o método aplicarCliente à API global
  window.laudosApp.aplicarCliente = function(id, nome, cnpj, tel, email, cep, end) {
    // Preenche os campos do requerente
    const fields = {
      'f-req-company': nome,
      'f-req-cnpj': cnpj,
      'f-req-phone': tel,
      'f-req-email': email,
      'f-req-cep': cep,
      'f-req-address': end,
    };
    for (const [elId, val] of Object.entries(fields)) {
      const el = document.getElementById(elId);
      if (el) {
        el.value = val;
        pendingFields[el.dataset.field] = val;
      }
    }
    pendingFields['requester_client_id'] = id;
    scheduleAutoSave();
    document.getElementById('cliente-search-box').style.display = 'none';
  };

  // ---- Busca CNPJ ----
  document.getElementById('btn-buscar-cnpj').addEventListener('click', function() {
    const cnpj = document.getElementById('f-req-cnpj').value.trim();
    if (!cnpj) return;
    fetch('/api/util/cnpj/' + encodeURIComponent(cnpj), { credentials: 'same-origin' })
      .then(r => r.json())
      .then(json => {
        if (!json.success || !json.data) return alert('CNPJ não encontrado.');
        const d = json.data;
        const map = {
          'f-req-company': d.razao_social,
          'f-req-phone': d.telefone,
          'f-req-email': d.email,
          'f-req-cep': d.cep,
          'f-req-address': d.endereco,
        };
        for (const [elId, val] of Object.entries(map)) {
          const el = document.getElementById(elId);
          if (el && val) {
            el.value = val;
            pendingFields[el.dataset.field] = val;
          }
        }
        scheduleAutoSave();
      })
      .catch(err => alert('Erro: ' + err.message));
  });

  // ---- Inicialização ----
  loadParecer();

  if (window.lucide) lucide.createIcons();
})();
</script>
