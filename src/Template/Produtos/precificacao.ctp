<?php
/**
 * Precificacao.ctp — Gestão de Preços / Custos (Produtos e Serviços)
 * Carrega produtos do BD + custos do ERP (SOAP), calcula preços via
 * 3 métodos: Markup, Fator Multiplicador, Fator Divisor.
 */
$this->append('css', $this->element('pgm_premium_css', ['name' => 'produtos-premium']));
?>
<?php /* Turbo / frame: reaplicar folha (padrão Produtos / Clientes). */ ?>
<?= $this->element('pgm_premium_css', ['name' => 'produtos-premium']) ?>

<?php if (empty($embedEstoque)) : ?>
<div class="col-md-12 p-0">
<?php endif; ?>
<div class="prec-root prec-layout-unificado">

  <!-- Topbar -->
  <div class="prec-topbar">
    <div class="prec-topbar-left">
      <div>
        <h1>Gestão de Preços</h1>
        <div class="prec-breadcrumb">Produtos e Serviços &rsaquo; Precificação</div>
      </div>
      <span class="prec-badge-erp">ERP Integrado</span>
    </div>
    <?php if (empty($embedEstoque)) : ?>
    <div class="prec-topbar-back">
    <?php if (!empty($returnUrlEstoque)) : ?>
      <a href="<?= h($returnUrlEstoque) ?>" class="prec-link-back">&larr; Voltar ao estoque</a>
      <a href="<?= $this->Url->build(['controller' => 'Produtos', 'action' => 'index']) ?>" class="prec-link-back prec-link-back--secondary">Cadastro de produtos</a>
    <?php else : ?>
      <a href="<?= $this->Url->build(['controller' => 'Produtos', 'action' => 'index']) ?>" class="prec-link-back">
        &larr; Voltar para Produtos
      </a>
    <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

  <div class="prec-body">

    <!-- ── Painel de Estratégia ─────────────────────────────────── -->
    <aside class="prec-panel">

      <div>
        <div class="prec-panel-title">Método de Cálculo</div>
        <div class="prec-method-tabs">
          <button class="prec-method-tab active" data-method="markup" onclick="setMethod('markup')">Markup</button>
          <button class="prec-method-tab" data-method="fmult" onclick="setMethod('fmult')">F. Mult.</button>
          <button class="prec-method-tab" data-method="fdiv" onclick="setMethod('fdiv')">F. Div.</button>
        </div>
      </div>

      <!-- Parâmetros ligados -->
      <div class="prec-param-block">
        <div class="prec-param-row" id="row-markup">
          <label for="inp-markup">Markup (%)</label>
          <input class="prec-param-input" id="inp-markup" name="inp_markup" type="number" min="0" step="0.01" placeholder="ex.: 40" oninput="syncParams('markup')">
          <div class="prec-param-hint">PV = Custo × (1 + Markup/100)</div>
        </div>
        <div class="prec-param-row" id="row-fmult">
          <label for="inp-fmult">Fator Multiplicador</label>
          <input class="prec-param-input" id="inp-fmult" name="inp_fmult" type="number" min="0" step="0.001" placeholder="ex.: 1.40" oninput="syncParams('fmult')">
          <div class="prec-param-hint">PV = Custo × Fator</div>
        </div>
        <div class="prec-param-row" id="row-fdiv">
          <label for="inp-fdiv">Fator Divisor</label>
          <input class="prec-param-input" id="inp-fdiv" name="inp_fdiv" type="number" min="0" max="0.9999" step="0.0001" placeholder="ex.: 0.7143" oninput="syncParams('fdiv')">
          <div class="prec-param-hint">PV = Custo ÷ FD &nbsp;·&nbsp; FD = 1 − Margem%/100</div>
        </div>
        <div class="prec-param-row" id="row-margem">
          <label for="inp-margem">Margem Líquida (%)</label>
          <input class="prec-param-input" id="inp-margem" name="inp_margem" type="number" min="0" max="99" step="0.01" placeholder="ex.: 28.57" oninput="syncParams('margem')">
          <div class="prec-param-hint">% da receita (não do custo)</div>
        </div>
      </div>

      <!-- Fórmula dinâmica -->
      <div class="prec-formula" id="prec-formula">
        <div class="formula-label">Fórmula ativa</div>
        <div class="formula-eq" id="formula-txt">PV = Custo × (1 + Markup/100)</div>
        <div class="prec-formula-meta">
          Markup: <span id="fml-markup-v">—</span> % &nbsp;·&nbsp;
          F.Mult: <span id="fml-fmult-v">—</span> &nbsp;·&nbsp;
          F.Div: <span id="fml-fdiv-v">—</span> &nbsp;·&nbsp;
          Margem: <span id="fml-margem-v">—</span> %
        </div>
      </div>

      <!-- Custos operacionais (Fator Divisor composto) -->
      <div>
        <div class="prec-panel-title">Custos Operacionais (opcional)</div>
        <div class="prec-param-hint prec-param-hint--mb8">Preenchendo, o Fator Divisor é calculado automaticamente: FD = 1 − Σcustos/100</div>
        <div class="prec-costs" id="prec-costs">
          <div class="prec-cost-row">
            <label for="cost-impostos">Impostos (%)</label>
            <input class="prec-cost-input" id="cost-impostos" name="cost_impostos" type="number" min="0" max="100" step="0.01" value="0" oninput="calcFatorDivComposto()">
          </div>
          <div class="prec-cost-row">
            <label for="cost-comissao">Comissão (%)</label>
            <input class="prec-cost-input" id="cost-comissao" name="cost_comissao" type="number" min="0" max="100" step="0.01" value="0" oninput="calcFatorDivComposto()">
          </div>
          <div class="prec-cost-row">
            <label for="cost-despesas">Despesas Fixas (%)</label>
            <input class="prec-cost-input" id="cost-despesas" name="cost_despesas" type="number" min="0" max="100" step="0.01" value="0" oninput="calcFatorDivComposto()">
          </div>
          <div class="prec-cost-row">
            <label for="cost-frete">Frete (%)</label>
            <input class="prec-cost-input" id="cost-frete" name="cost_frete" type="number" min="0" max="100" step="0.01" value="0" oninput="calcFatorDivComposto()">
          </div>
          <div class="prec-cost-row">
            <label for="cost-lucro">Lucro Desejado (%)</label>
            <input class="prec-cost-input" id="cost-lucro" name="cost_lucro" type="number" min="0" max="100" step="0.01" value="0" oninput="calcFatorDivComposto()">
          </div>
          <div class="prec-costs-total">
            <span>Σ Custos</span>
            <span class="val" id="costs-sum">0,00 %</span>
          </div>
          <div class="prec-costs-total prec-costs-total--fd">
            <span>Fator Divisor resultante</span>
            <span class="val" id="costs-fd">1,0000</span>
          </div>
        </div>
      </div>

      <!-- Margem mínima alerta -->
      <div>
        <div class="prec-panel-title">Margem Mínima</div>
        <div class="prec-cost-row">
          <label for="margem-min">Alertar abaixo de (%)</label>
          <input class="prec-cost-input" id="margem-min" name="margem_min" type="number" min="0" max="99" step="1" value="10" oninput="refreshTable()">
        </div>
      </div>
      <div class="prec-margin-alert" id="margem-alert">
        ⚠ Alguns itens estão abaixo da margem mínima
      </div>

      <!-- Ações -->
      <div class="prec-panel-actions">
        <button class="prec-btn-apply-sel" onclick="aplicarSelecionados()">Aplicar aos Selecionados</button>
        <button class="prec-btn-apply-all" onclick="aplicarTodos()">Aplicar a Todos os Visíveis</button>
        <button class="prec-btn-reset" onclick="resetPrecos()">Resetar Alterações</button>
      </div>

    </aside><!-- /prec-panel -->

    <!-- ── Grade de Produtos ────────────────────────────────────── -->
    <div class="prec-grid-wrap">

      <div class="prec-grid-toolbar">
        <label for="prec-search" class="prec-sr-only">Buscar itens da precificação</label>
        <input class="prec-search" id="prec-search" name="prec_search" type="text" placeholder="Buscar código ou descrição…" oninput="refreshTable()">
        <div class="prec-tipo-pills">
          <button class="prec-tipo-pill active" data-tipo="0" onclick="setTipo(0,this)">Todos</button>
          <button class="prec-tipo-pill" data-tipo="1" onclick="setTipo(1,this)">Produtos</button>
          <button class="prec-tipo-pill" data-tipo="2" onclick="setTipo(2,this)">Serviços</button>
          <button class="prec-tipo-pill" data-tipo="3" onclick="setTipo(3,this)">Contratos</button>
        </div>
        <label class="prec-toolbar-label">
          <input type="checkbox" id="chk-only-changed" class="prec-check" onchange="refreshTable()"> Só alterados
        </label>
        <div class="prec-sel-count" id="sel-count">0 selecionados</div>
      </div>

      <div class="prec-grid-scroll">
        <table class="prec-table" id="prec-table">
          <thead>
            <tr>
              <th class="prec-th-check">
                <label for="chk-all" class="prec-sr-only">Selecionar todos os itens</label>
                <input type="checkbox" class="prec-check" id="chk-all" name="chk_all" onchange="toggleAll(this)">
              </th>
              <th class="prec-th-avatar"></th>
              <th>Código</th>
              <th>Descrição</th>
              <th>Tipo</th>
              <th>Custo ERP</th>
              <th>Preço Atual</th>
              <th>Markup Atual</th>
              <th>Novo Preço</th>
              <th>Nova Margem</th>
              <th>Δ Diferença</th>
              <th class="prec-th-warn"></th>
            </tr>
          </thead>
          <tbody id="prec-tbody"></tbody>
        </table>
      </div>

    </div><!-- /prec-grid-wrap -->

  </div><!-- /prec-body -->

  <!-- ── Footer ──────────────────────────────────────────────────── -->
  <div class="prec-footer">
    <div class="prec-footer-stat">Itens: <strong id="ft-total">0</strong></div>
    <div class="prec-footer-divider"></div>
    <div class="prec-footer-stat">Alterados: <strong id="ft-changed">0</strong></div>
    <div class="prec-footer-divider"></div>
    <div class="prec-footer-stat">Selecionados: <strong id="ft-selected">0</strong></div>
    <div class="prec-footer-divider"></div>
    <div class="prec-footer-stat">Impacto estoque: <strong id="ft-impacto">R$ 0,00</strong></div>
    <div class="prec-footer-actions">
      <button type="button" class="prec-btn-cancel" onclick="precCancelar()">Cancelar</button>
      <button class="prec-btn-save" id="btn-save" onclick="salvarPrecos()" disabled>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
        Salvar Preços
      </button>
    </div>
  </div>

</div><!-- /prec-root -->

<!-- Floating selection bar -->
<div class="prec-sel-bar" id="prec-sel-bar">
  <span class="prec-sel-bar-count" id="sel-bar-count">0</span>
  <span class="prec-sel-bar-label">itens selecionados</span>
  <span class="prec-sel-bar-spacer"></span>
  <button class="prec-btn-sel-clear" onclick="clearSelection()">Limpar seleção</button>
  <button class="prec-btn-sel-apply" onclick="aplicarSelecionados()">&#9654; Aplicar cálculo</button>
</div>

<!-- Toast container -->
<div class="prec-toast" id="prec-toast"></div>

<?php if (empty($embedEstoque)) : ?>
</div><!-- /.col-md-12 -->
<?php endif; ?>

<script>
/* ── Dados do servidor ─────────────────────────────────────────── */
var PREC_DATA = <?= $produtosJson ?? '[]' ?>;

/* ── Estado ────────────────────────────────────────────────────── */
var state = {
  method: 'markup',
  tipoFiltro: 0,
  params: { markup: null, fmult: null, fdiv: null, margem: null },
  novosPrecos: {},   // id → float
  selected: new Set()
};
var saveUrl = <?= json_encode($this->Url->build(['controller' => 'Produtos', 'action' => 'salvarPrecos'])) ?>;

function precNormalizeReturnPath(u) {
  if (u === null || u === undefined || u === '') return u;
  var s = String(u).trim();
  if (/^[a-z][a-z0-9+.-]*:/i.test(s)) return s;
  if (s.charAt(0) !== '/') s = '/' + s.replace(/^\/+/, '');
  var dup = '/portal/portal';
  while (s.length >= dup.length && s.indexOf(dup) === 0) {
    s = '/portal' + s.substring(dup.length);
  }
  return s;
}
var _precEmbRaw = <?= json_encode(!empty($embedEstoque) && !empty($returnUrlEstoque) ? $returnUrlEstoque : null) ?>;
var _precRetRaw = <?= json_encode(!empty($returnUrlEstoque) ? $returnUrlEstoque : null) ?>;
var PREC_EMBED_RETURN = _precEmbRaw ? precNormalizeReturnPath(_precEmbRaw) : null;
var PREC_RETURN_URL = _precRetRaw ? precNormalizeReturnPath(_precRetRaw) : null;

/* ── Formatação ────────────────────────────────────────────────── */
function fmt(n, dec) {
  if (n === null || n === undefined || isNaN(n)) return '—';
  return parseFloat(n).toLocaleString('pt-BR', { minimumFractionDigits: dec ?? 2, maximumFractionDigits: dec ?? 2 });
}
function fmtBRL(n) {
  if (n === null || isNaN(n)) return '—';
  return 'R$ ' + fmt(n, 2);
}
function parseFl(s) {
  if (s === '' || s === null || s === undefined) return NaN;
  var raw = String(s).trim();
  if (!raw) return NaN;
  var hasComma = raw.indexOf(',') !== -1;
  if (hasComma) {
    raw = raw.replace(/\./g, '').replace(',', '.');
  } else {
    raw = raw.replace(',', '.');
  }
  return parseFloat(raw);
}

/* ── Sincronização dos 4 campos ────────────────────────────────── */
function syncParams(src) {
  var mk = parseFl(document.getElementById('inp-markup').value);
  var fm = parseFl(document.getElementById('inp-fmult').value);
  var fd = parseFl(document.getElementById('inp-fdiv').value);
  var mg = parseFl(document.getElementById('inp-margem').value);

  if (src === 'markup' && !isNaN(mk)) {
    fm = 1 + mk / 100;
    fd = (fm > 0) ? 1 / fm : 0;
    mg = (1 - fd) * 100;
  } else if (src === 'fmult' && !isNaN(fm) && fm > 0) {
    mk = (fm - 1) * 100;
    fd = 1 / fm;
    mg = (1 - fd) * 100;
  } else if (src === 'fdiv' && !isNaN(fd) && fd > 0 && fd < 1) {
    fm = 1 / fd;
    mk = (fm - 1) * 100;
    mg = (1 - fd) * 100;
  } else if (src === 'margem' && !isNaN(mg) && mg >= 0 && mg < 100) {
    fd = 1 - mg / 100;
    fm = (fd > 0) ? 1 / fd : 0;
    mk = (fm - 1) * 100;
  } else {
    return;
  }

  state.params = { markup: mk, fmult: fm, fdiv: fd, margem: mg };

  // Atualizar campos (sem disparar oninput)
  silentSet('inp-markup', isNaN(mk) ? '' : mk.toFixed(4));
  silentSet('inp-fmult',  isNaN(fm) ? '' : fm.toFixed(6));
  silentSet('inp-fdiv',   isNaN(fd) ? '' : fd.toFixed(6));
  silentSet('inp-margem', isNaN(mg) ? '' : mg.toFixed(4));

  // Atualizar resumo fórmula
  document.getElementById('fml-markup-v').textContent = isNaN(mk) ? '—' : fmt(mk, 2);
  document.getElementById('fml-fmult-v').textContent  = isNaN(fm) ? '—' : fmt(fm, 4);
  document.getElementById('fml-fdiv-v').textContent   = isNaN(fd) ? '—' : fmt(fd, 4);
  document.getElementById('fml-margem-v').textContent = isNaN(mg) ? '—' : fmt(mg, 2);

  refreshTable();
}

function silentSet(id, val) {
  var el = document.getElementById(id);
  if (el) el.value = val;
}

/* ── Custos operacionais → Fator Divisor composto ─────────────── */
function calcFatorDivComposto() {
  var ids = ['cost-impostos','cost-comissao','cost-despesas','cost-frete','cost-lucro'];
  var soma = 0;
  ids.forEach(function(id) {
    var v = parseFl(document.getElementById(id).value);
    if (!isNaN(v)) soma += v;
  });
  var fd = 1 - soma / 100;
  document.getElementById('costs-sum').textContent = fmt(soma, 2) + ' %';
  document.getElementById('costs-fd').textContent  = (fd > 0 && fd < 1) ? fd.toFixed(4) : '—';
  if (fd > 0 && fd < 1) {
    silentSet('inp-fdiv', fd.toFixed(6));
    syncParams('fdiv');
  }
}

/* ── Método ativo ──────────────────────────────────────────────── */
var formulaTexts = {
  markup: 'PV = Custo × (1 + Markup / 100)',
  fmult:  'PV = Custo × Fator Multiplicador',
  fdiv:   'PV = Custo ÷ Fator Divisor'
};
function setMethod(m) {
  state.method = m;
  document.querySelectorAll('.prec-method-tab').forEach(function(b) {
    b.classList.toggle('active', b.dataset.method === m);
  });
  document.getElementById('formula-txt').textContent = formulaTexts[m] || '';
}

/* ── Calcular novo preço ───────────────────────────────────────── */
function calcNewPrice(custo) {
  if (!custo || custo <= 0) return null;
  var p = state.params;
  if (state.method === 'markup') {
    if (p.markup === null) return null;
    return isNaN(p.markup) ? null : custo * (1 + p.markup / 100);
  } else if (state.method === 'fmult') {
    if (p.fmult === null) return null;
    return (isNaN(p.fmult) || p.fmult <= 0) ? null : custo * p.fmult;
  } else {
    if (p.fdiv === null) return null;
    return (isNaN(p.fdiv) || p.fdiv <= 0) ? null : custo / p.fdiv;
  }
}

/* ── Filtrar dados ─────────────────────────────────────────────── */
function getVisible() {
  var termo = (document.getElementById('prec-search').value || '').toLowerCase();
  var tipo  = state.tipoFiltro;
  var soAlterados = document.getElementById('chk-only-changed').checked;
  return PREC_DATA.filter(function(p) {
    if (tipo && p.tipo !== tipo) return false;
    if (soAlterados && !state.novosPrecos.hasOwnProperty(p.id)) return false;
    if (termo) {
      var haystack = (p.codigo + ' ' + p.descricao).toLowerCase();
      if (!haystack.includes(termo)) return false;
    }
    return true;
  });
}

/* ── Renderizar tabela ─────────────────────────────────────────── */
function avatarHtml(tipo) {
  var icons = {1: '📦', 2: '⚙️', 3: '📄'};
  var cls   = {1: 't1', 2: 't2', 3: 't3'};
  return '<div class="prec-avatar ' + (cls[tipo]||'t1') + '">' + (icons[tipo]||'?') + '</div>';
}
function tipoLabel(t) {
  return {1:'Produto',2:'Serviço',3:'Contrato'}[t] || 'Outro';
}
function margemClass(m) {
  if (m === null || isNaN(m)) return 'none';
  if (m >= 20) return 'good';
  if (m >= 10) return 'ok';
  return 'low';
}

function refreshTable() {
  var vis = getVisible();
  var tbody = document.getElementById('prec-tbody');
  var margemMin = parseFl(document.getElementById('margem-min').value) || 0;
  var hasLow = false;
  var totalChanged = Object.keys(state.novosPrecos).length;
  var totalSelected = vis.filter(function(p) { return state.selected.has(p.id); }).length;
  var impacto = 0;

  var rows = vis.map(function(p) {
    var novoPreco = state.novosPrecos.hasOwnProperty(p.id)
      ? state.novosPrecos[p.id]
      : calcNewPrice(p.custo);
    var changed  = state.novosPrecos.hasOwnProperty(p.id);
    var precoRef = p.vendaAtual;

    // Margem nova
    var novaMargem = null;
    if (novoPreco && p.custo > 0) {
      novaMargem = (1 - p.custo / novoPreco) * 100;
    }

    // Delta
    var delta = changed && novoPreco !== null ? (novoPreco - precoRef) : null;
    var deltaPct = (delta !== null && precoRef > 0) ? (delta / precoRef * 100) : null;

    // Markup atual
    var mkAtual = (p.custo > 0 && p.vendaAtual > 0) ? ((p.vendaAtual / p.custo - 1) * 100) : null;
    var barW = mkAtual !== null ? Math.min(mkAtual / 2, 100) : 0;  // escala: 200% = 100px

    // Impacto no estoque
    if (changed && novoPreco && p.qtde) {
      impacto += (novoPreco - precoRef) * p.qtde;
    }

    // Alerta margem
    if (novaMargem !== null && novaMargem < margemMin) hasLow = true;

    // Warn icon
    var showWarn = novaMargem !== null && novaMargem < margemMin;

    var sel = state.selected.has(p.id);
    var isPrecoZero = p.temCusto && (!p.vendaAtual || p.vendaAtual <= 0);
    var rowCls = (sel ? 'selected' : '') + (isPrecoZero ? (sel ? ' prec-row-zero-price' : 'prec-row-zero-price') : '');

    return '<tr class="' + rowCls + '" data-id="' + p.id + '">' +
      '<td><input type="checkbox" class="prec-check row-chk" data-id="' + p.id + '" name="row_chk_' + p.id + '" aria-label="Selecionar item ' + escHtml(p.codigo) + '" ' + (sel ? 'checked' : '') + ' onchange="toggleRow(' + p.id + ',this)"></td>' +
      '<td>' + avatarHtml(p.tipo) + '</td>' +
      '<td class="prec-td-code">' + escHtml(p.codigo) + '</td>' +
      '<td class="prec-td-desc" title="' + escHtml(p.descricao) + '">' + escHtml(p.descricao) + '</td>' +
      '<td><span class="prec-cell-tipo-pill">' + tipoLabel(p.tipo) + '</span></td>' +
      '<td class="prec-td-custo' + (!p.temCusto ? ' no-data' : '') + '">' + (p.temCusto ? fmtBRL(p.custo) : 'Sem dado ERP') + '</td>' +
      '<td class="prec-td-mono">' + (isPrecoZero ? '<span class="prec-badge-zero">⚠ Sem preço</span>' : fmtBRL(p.vendaAtual)) + '</td>' +
      '<td>' +
        (mkAtual !== null
          ? ('<div class="prec-mk-row"><span class="prec-td-mono prec-td-mono--sm">' + fmt(mkAtual, 1) + '%</span><div class="prec-bar-mini"><div class="prec-bar-fill" data-pct="' + Math.round(barW) + '"></div></div></div>')
          : (isPrecoZero
              ? '<span class="prec-mk-msg--amber">Sem preço</span>'
              : '<span class="prec-mk-msg--muted">Sem custo ERP</span>')) +
      '</td>' +
      '<td><input type="number" class="prec-novo-preco' + (changed ? ' changed' : '') + '" data-id="' + p.id + '" name="novo_preco_' + p.id + '" aria-label="Novo preço para item ' + escHtml(p.codigo) + '" value="' + (novoPreco !== null ? novoPreco.toFixed(2) : '') + '" step="0.01" min="0" oninput="onPrecoEdit(this)" onblur="onPrecoBlur(this)"></td>' +
      '<td class="prec-td-margem ' + margemClass(novaMargem) + '">' + (novaMargem !== null ? fmt(novaMargem, 2) + '%' : '—') + '</td>' +
      '<td class="prec-delta ' + (delta === null ? 'same' : delta > 0 ? 'up' : delta < 0 ? 'down' : 'same') + '">' +
        (delta !== null ? (delta >= 0 ? '+' : '') + fmtBRL(delta) + (deltaPct !== null ? '<br><span class="prec-delta-pct">' + (deltaPct >= 0 ? '+' : '') + fmt(deltaPct, 1) + '%</span>' : '') : '—') +
      '</td>' +
      '<td><span class="prec-warn' + (showWarn ? ' show' : '') + '" title="Margem abaixo do mínimo">⚠</span></td>' +
    '</tr>';
  });

  tbody.innerHTML = rows.join('');
  tbody.querySelectorAll('.prec-bar-fill[data-pct]').forEach(function (el) {
    var w = el.getAttribute('data-pct');
    if (w !== null && w !== '') el.style.width = w + '%';
    el.removeAttribute('data-pct');
  });

  // Atualizar footer
  document.getElementById('ft-total').textContent    = vis.length;
  document.getElementById('ft-changed').textContent  = totalChanged;
  document.getElementById('ft-selected').textContent = totalSelected;
  document.getElementById('ft-impacto').textContent  = fmtBRL(impacto);
  document.getElementById('sel-count').textContent   = totalSelected + ' selecionado' + (totalSelected !== 1 ? 's' : '');

  // Alerta margem
  var alertEl = document.getElementById('margem-alert');
  alertEl.classList.toggle('show', hasLow);

  // Floating selection bar
  var selBar = document.getElementById('prec-sel-bar');
  if (selBar) {
    selBar.classList.toggle('visible', totalSelected > 0);
    var selBarCount = document.getElementById('sel-bar-count');
    if (selBarCount) selBarCount.textContent = totalSelected;
  }

  // Btn salvar
  document.getElementById('btn-save').disabled = totalChanged === 0;

  // Checkbox all
  var chkAll = document.getElementById('chk-all');
  chkAll.indeterminate = totalSelected > 0 && totalSelected < vis.length;
  chkAll.checked = vis.length > 0 && totalSelected === vis.length;
}

function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ── Edição manual de novo preço ──────────────────────────────── */
function onPrecoEdit(el) {
  var id  = parseInt(el.dataset.id);
  var val = parseFl(el.value);
  if (!isNaN(val) && val >= 0) {
    state.novosPrecos[id] = val;
    el.classList.add('changed');
  } else {
    delete state.novosPrecos[id];
    el.classList.remove('changed');
  }
  // Atualizar apenas o footer e a linha (leve)
  refreshFooterOnly();
}

function onPrecoBlur(el) {
  // Reformatar ao sair
  var val = parseFl(el.value);
  if (!isNaN(val)) el.value = val.toFixed(2);
  refreshTable();
}

function refreshFooterOnly() {
  var changed = Object.keys(state.novosPrecos).length;
  document.getElementById('ft-changed').textContent  = changed;
  document.getElementById('btn-save').disabled = changed === 0;
}

/* ── Aplicar cálculo aos selecionados / todos ─────────────────── */
function aplicarSelecionados() {
  if (state.selected.size === 0) { showToast('Selecione ao menos um item', 'error'); return; }
  if (state.params.markup === null && state.params.fmult === null && state.params.fdiv === null) {
    showToast('Preencha um parâmetro de cálculo antes de aplicar', 'error');
    return;
  }
  var aplicados = 0;
  state.selected.forEach(function(id) {
    var p = PREC_DATA.find(function(x) { return x.id === id; });
    if (!p) return;
    var novo = calcNewPrice(p.custo);
    if (novo !== null) { state.novosPrecos[id] = parseFloat(novo.toFixed(2)); aplicados++; }
  });
  refreshTable();
  showToast(aplicados + ' preço(s) calculado(s)', 'success');
}

function aplicarTodos() {
  if (state.params.markup === null && state.params.fmult === null && state.params.fdiv === null) {
    showToast('Preencha um parâmetro de cálculo antes de aplicar', 'error');
    return;
  }
  var vis = getVisible();
  var aplicados = 0;
  vis.forEach(function(p) {
    var novo = calcNewPrice(p.custo);
    if (novo !== null) { state.novosPrecos[p.id] = parseFloat(novo.toFixed(2)); aplicados++; }
  });
  refreshTable();
  showToast(aplicados + ' preço(s) calculado(s)', 'success');
}

function resetPrecos() {
  state.novosPrecos = {};
  refreshTable();
}

function precCancelar() {
  if (PREC_RETURN_URL) {
    if (window.top && window.top !== window.self) {
      window.top.location.href = PREC_RETURN_URL;
    } else {
      window.location.href = PREC_RETURN_URL;
    }
    return;
  }
  resetPrecos();
}

/* ── Seleção ───────────────────────────────────────────────────── */
function toggleRow(id, chk) {
  if (chk.checked) state.selected.add(id);
  else state.selected.delete(id);
  var tr = chk.closest('tr');
  if (tr) tr.classList.toggle('selected', chk.checked);
  refreshFooterOnly();
  document.getElementById('sel-count').textContent = state.selected.size + ' selecionado' + (state.selected.size !== 1 ? 's' : '');
  document.getElementById('ft-selected').textContent = state.selected.size;
}

function toggleAll(chk) {
  var vis = getVisible();
  vis.forEach(function(p) {
    if (chk.checked) state.selected.add(p.id);
    else state.selected.delete(p.id);
  });
  refreshTable();
}

function clearSelection() {
  state.selected.clear();
  refreshTable();
}

/* ── Filtros ───────────────────────────────────────────────────── */
function setTipo(t, btn) {
  state.tipoFiltro = t;
  document.querySelectorAll('.prec-tipo-pill').forEach(function(b) {
    b.classList.toggle('active', parseInt(b.dataset.tipo) === t);
  });
  refreshTable();
}

/* ── Salvar no servidor ────────────────────────────────────────── */
function salvarPrecos() {
  var ids = Object.keys(state.novosPrecos);
  if (!ids.length) return;

  var payload = ids.map(function(id) {
    return { id: parseInt(id), vlunitario: state.novosPrecos[id] };
  });

  var btn = document.getElementById('btn-save');
  btn.disabled = true;
  btn.textContent = 'Salvando…';

  fetch(saveUrl, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': document.querySelector('meta[name="csrfToken"]') ? document.querySelector('meta[name="csrfToken"]').content : ''
    },
    body: JSON.stringify({ precos: payload })
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    if (data.salvos > 0) {
      // Atualizar vendaAtual nos dados locais
      payload.forEach(function(item) {
        var p = PREC_DATA.find(function(x) { return x.id === item.id; });
        if (p) p.vendaAtual = item.vlunitario;
      });
      state.novosPrecos = {};
      showToast(data.salvos + ' preço(s) salvo(s) com sucesso!', 'success');
      refreshTable();
      if (PREC_EMBED_RETURN) {
        if (window.top && window.top !== window.self) {
          window.top.location.href = PREC_EMBED_RETURN;
        } else {
          window.location.href = PREC_EMBED_RETURN;
        }
        return;
      }
    }
    if (data.erros && data.erros.length) {
      showToast(data.erros.length + ' erro(s) ao salvar', 'error');
    }
  })
  .catch(function(e) {
    showToast('Erro de comunicação: ' + e.message, 'error');
  })
  .finally(function() {
    btn.disabled = Object.keys(state.novosPrecos).length === 0;
    btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Salvar Preços';
  });
}

/* ── Toast ─────────────────────────────────────────────────────── */
function showToast(msg, type) {
  var container = document.getElementById('prec-toast');
  var item = document.createElement('div');
  item.className = 'prec-toast-item ' + (type || 'success');
  item.textContent = msg;
  container.appendChild(item);
  setTimeout(function() { item.remove(); }, 4000);
}

function cleanupGhostBackdrop() {
  var hasOpenModal = !!document.querySelector('.modal.show, .bootbox.modal.show');
  if (hasOpenModal) return;
  document.querySelectorAll('.modal-backdrop').forEach(function(el) {
    if (el && el.parentNode) el.parentNode.removeChild(el);
  });
  document.querySelectorAll('.preloader').forEach(function(el) {
    if (el) {
      el.style.display = 'none';
      el.style.opacity = '0';
      el.style.visibility = 'hidden';
      el.style.pointerEvents = 'none';
    }
  });
  document.body.classList.remove('modal-open');
}

function isDarkOverlayCandidate(el) {
  if (!el || el === document.body || el === document.documentElement) return false;
  if (el.classList && (el.classList.contains('modal') || el.classList.contains('bootbox') || el.classList.contains('prec-toast'))) return false;
  if (el.closest && el.closest('.modal.show, .bootbox.modal.show, .prec-root, .left-sidebar, .topbar')) return false;

  var st = window.getComputedStyle(el);
  if (st.display === 'none' || st.visibility === 'hidden') return false;
  if (st.position !== 'fixed' && st.position !== 'absolute') return false;

  var rect = el.getBoundingClientRect();
  var vw = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);
  var vh = Math.max(document.documentElement.clientHeight, window.innerHeight || 0);
  var coversScreen = rect.width >= vw * 0.95 && rect.height >= vh * 0.95;
  if (!coversScreen) return false;

  var zi = parseInt(st.zIndex, 10);
  if (!isNaN(zi) && zi < 100) return false;

  var bg = st.backgroundColor || '';
  var isDarkBg = /rgba?\(\s*0\s*,\s*0\s*,\s*0(?:\s*,\s*(0\.[1-9]|1))?\s*\)/.test(bg);
  var hasBackdrop = st.backdropFilter && st.backdropFilter !== 'none';
  var hasBlend = st.mixBlendMode && st.mixBlendMode !== 'normal';
  return isDarkBg || hasBackdrop || hasBlend;
}

function neutralizeDarkOverlays() {
  var nodes = document.body ? document.body.querySelectorAll('*') : [];
  for (var i = 0; i < nodes.length; i++) {
    var el = nodes[i];
    if (isDarkOverlayCandidate(el)) {
      el.classList.add('prec-overlay-killed');
    }
  }
}

/* ── Altura exata: mede posição real do .prec-root no viewport (sem mexer em largura — evita deslocar o bloco para a esquerda) ── */
function fitPrecRoot() {
  var el = document.querySelector('.prec-root');
  if (!el) return;
  var top = el.getBoundingClientRect().top;
  var h = Math.max(300, window.innerHeight - top);
  el.style.height = h + 'px';
  el.style.removeProperty('margin-left');
  el.style.removeProperty('width');
}

/* ── Init ──────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function() {
  if (!document.body.classList.contains('prec-screen-active')) {
    document.body.classList.add('prec-screen-active');
  }
  cleanupGhostBackdrop();
  neutralizeDarkOverlays();
  setTimeout(cleanupGhostBackdrop, 250);
  setTimeout(neutralizeDarkOverlays, 300);
  setTimeout(cleanupGhostBackdrop, 1200);
  setTimeout(neutralizeDarkOverlays, 1300);

  // Altura real após o layout ter renderizado completamente
  fitPrecRoot();
  window.addEventListener('resize', fitPrecRoot);

  // Alguns scripts globais podem reinserir overlay após load.
  var obs = new MutationObserver(function() {
    cleanupGhostBackdrop();
    neutralizeDarkOverlays();
  });
  obs.observe(document.body, { childList: true, subtree: true });
  window.addEventListener('beforeunload', function() { obs.disconnect(); });

  // Markup padrão 30% — exibe colunas "Novo Preço / Nova Margem / Δ Diferença" imediatamente
  silentSet('inp-markup', '30');
  syncParams('markup'); // já chama refreshTable() internamente

  try {
    var qs = new URLSearchParams(window.location.search || '');
    var cod = qs.get('codigo');
    if (cod) {
      var ps = document.getElementById('prec-search');
      if (ps) {
        ps.value = cod;
        refreshTable();
      }
    }
  } catch (e) { /* ignore */ }
});
</script>
