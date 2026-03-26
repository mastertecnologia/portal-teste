<?php
  $this->Breadcrumbs->add('Cadastros', '#', ['class' => 'breadcrumb-item']);
  $this->Breadcrumbs->add('Produtos & Serviços', ['controller' => 'Produtos', 'action' => 'index'], ['class' => 'breadcrumb-item active']);
  $this->append('css', $this->Html->css('/css/produtos-premium', ['timestamp' => true]));

  $cntProd  = count($produtos  ?? []);
  $cntServ  = count($servicos  ?? []);
  $cntContr = count($contratos ?? []);
  $cntTotal = $cntProd + $cntServ + $cntContr;

  function prdBadge($ativo) {
    return $ativo
      ? '<span class="prd-badge prd-badge-on"><i class="fas fa-circle" style="font-size:5px"></i> Ativo</span>'
      : '<span class="prd-badge prd-badge-off"><i class="fas fa-circle" style="font-size:5px"></i> Inativo</span>';
  }
?>

<div class="prd-root">

  <!-- ── Topbar ─────────────────────────────────────────────── -->
  <div class="prd-topbar">
    <div>
      <div class="prd-eyebrow">Cadastros &rsaquo; Catálogo</div>
      <h1 class="prd-h1">Produtos &amp; Serviços</h1>
    </div>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
      <?= $this->Html->link(
        '<i class="fas fa-warehouse"></i> Estoque',
        ['action' => 'estoque'],
        ['class' => 'btn-prd-outline', 'escape' => false, 'target' => '_blank']
      ) ?>
      <?= $this->Html->link(
        '<i class="fas fa-plus"></i> Novo',
        ['action' => 'add'],
        ['class' => 'btn-prd-new', 'escape' => false, 'target' => '_blank']
      ) ?>
    </div>
  </div>

  <!-- ── KPI Strip ──────────────────────────────────────────── -->
  <div class="prd-kpi-strip">
    <div class="prd-kpi kpi-active" id="kpi-produtos" onclick="showPanel('produtos')">
      <div class="prd-kpi-icon"><i class="fas fa-box-open"></i></div>
      <div class="prd-kpi-value"><?= $cntProd ?></div>
      <div class="prd-kpi-label">Produtos</div>
    </div>
    <div class="prd-kpi" id="kpi-servicos" onclick="showPanel('servicos')">
      <div class="prd-kpi-icon"><i class="fas fa-laptop-code"></i></div>
      <div class="prd-kpi-value"><?= $cntServ ?></div>
      <div class="prd-kpi-label">Serviços</div>
    </div>
    <div class="prd-kpi" id="kpi-contratos" onclick="showPanel('contratos')">
      <div class="prd-kpi-icon"><i class="fas fa-file-contract"></i></div>
      <div class="prd-kpi-value"><?= $cntContr ?></div>
      <div class="prd-kpi-label">Contratos</div>
    </div>
    <div class="prd-kpi" id="kpi-todos" onclick="showPanel('todos')">
      <div class="prd-kpi-icon"><i class="fas fa-layer-group"></i></div>
      <div class="prd-kpi-value"><?= $cntTotal ?></div>
      <div class="prd-kpi-label">Total</div>
    </div>
  </div>

  <!-- ── Filter Bar ─────────────────────────────────────────── -->
  <div class="prd-filter-bar">
    <div class="prd-pill-group">
      <button class="prd-pill pill-active" id="pill-produtos"  onclick="showPanel('produtos')">Produtos</button>
      <button class="prd-pill"             id="pill-servicos"  onclick="showPanel('servicos')">Serviços</button>
      <button class="prd-pill"             id="pill-contratos" onclick="showPanel('contratos')">Contratos</button>
    </div>
    <div class="prd-filter-search">
      <i class="fas fa-search prd-search-icon"></i>
      <input type="text" id="prd-search" placeholder="Buscar…" oninput="doSearch(this.value)">
    </div>
  </div>

  <!-- ── Tabelas ────────────────────────────────────────────── -->
  <div class="prd-table-wrap">
    <div class="prd-table-card">

      <!-- Produtos -->
      <div class="prd-table-panel panel-active" id="panel-produtos">
        <table class="prd-table" id="tbl-produtos">
          <thead>
            <tr>
              <th>Código</th>
              <th>Descrição</th>
              <th style="text-align:right">Vl. Unitário</th>
              <th style="text-align:right">Qtd. Atual</th>
              <th style="text-align:right">Preço Custo</th>
              <th style="text-align:right">Preço Venda</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($produtos as $reg): ?>
            <tr onclick="window.open('<?= $this->Url->build(['action' => 'edit', $reg->id]) ?>', '_blank')">
              <td class="prd-td-code"><?= h($reg->codigo) ?></td>
              <td class="prd-td-desc"><?= h($reg->descricao) ?></td>
              <td class="prd-td-val"><?= number_format($reg->vlunitario,  2, ',', '.') ?></td>
              <td class="prd-td-qty"><?= $reg->nQtdeAtual ?></td>
              <td class="prd-td-val"><?= number_format($reg->nPrecoCusto, 2, ',', '.') ?></td>
              <td class="prd-td-val"><?= number_format($reg->nPrecoVenda, 2, ',', '.') ?></td>
              <td><?= prdBadge($reg->ativo) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Serviços -->
      <div class="prd-table-panel" id="panel-servicos">
        <table class="prd-table" id="tbl-servicos">
          <thead>
            <tr>
              <th>Código</th>
              <th>Descrição</th>
              <th style="text-align:right">Vl. Hora</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($servicos as $reg): ?>
            <tr onclick="window.open('<?= $this->Url->build(['action' => 'edit', $reg->id]) ?>', '_blank')">
              <td class="prd-td-code"><?= h($reg->codigo) ?></td>
              <td class="prd-td-desc"><?= h($reg->descricao) ?></td>
              <td class="prd-td-val"><?= number_format($reg->vlunitario, 2, ',', '.') ?></td>
              <td><?= prdBadge($reg->ativo) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Contratos -->
      <div class="prd-table-panel" id="panel-contratos">
        <table class="prd-table" id="tbl-contratos">
          <thead>
            <tr>
              <th>Código</th>
              <th>Descrição</th>
              <th style="text-align:right">Vl. Mensal</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($contratos as $reg): ?>
            <tr onclick="window.open('<?= $this->Url->build(['action' => 'edit', $reg->id]) ?>', '_blank')">
              <td class="prd-td-code"><?= h($reg->codigo) ?></td>
              <td class="prd-td-desc"><?= h($reg->descricao) ?></td>
              <td class="prd-td-val"><?= number_format($reg->vlunitario, 2, ',', '.') ?></td>
              <td><?= prdBadge($reg->ativo) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

    </div><!-- /.prd-table-card -->
  </div><!-- /.prd-table-wrap -->

</div><!-- /.prd-root -->

<script>
(function() {
  var ptLang = {
    sProcessing: 'Processando...', sLengthMenu: 'Mostrar _MENU_ registros',
    sZeroRecords: 'Nenhum registro encontrado', sEmptyTable: 'Nenhum dado disponível',
    sInfo: '_START_ – _END_ de _TOTAL_', sInfoEmpty: '0 registros',
    sInfoFiltered: '(de _MAX_)', sSearch: '', sLoadingRecords: 'Carregando...',
    oPaginate: { sFirst: '«', sLast: '»', sNext: '›', sPrevious: '‹' }
  };

  var dtOpts = {
    language: ptLang,
    pageLength: <?= (int)($pagelength ?? 25) ?>,
    dom: 'lrtip',
    columnDefs: [{ orderable: false, targets: -1 }],
    initComplete: function() { this.api().columns.adjust(); }
  };

  var tables = {
    produtos:  $('#tbl-produtos').DataTable(dtOpts),
    servicos:  $('#tbl-servicos').DataTable(dtOpts),
    contratos: $('#tbl-contratos').DataTable(dtOpts)
  };

  var active = 'produtos';
  var KEYS = ['produtos', 'servicos', 'contratos'];

  window.showPanel = function(key) {
    if (key === 'todos') {
      key = <?= $cntProd ?> > 0 ? 'produtos' : (<?= $cntServ ?> > 0 ? 'servicos' : 'contratos');
    }
    KEYS.forEach(function(k) {
      document.getElementById('panel-' + k).classList.toggle('panel-active', k === key);
      var kpi = document.getElementById('kpi-' + k);
      if (kpi) kpi.classList.toggle('kpi-active', k === key);
      var pill = document.getElementById('pill-' + k);
      if (pill) pill.classList.toggle('pill-active', k === key);
    });
    document.getElementById('kpi-todos').classList.remove('kpi-active');
    active = key;
    setTimeout(function() { tables[key].columns.adjust().draw(false); }, 50);
    var q = document.getElementById('prd-search').value;
    if (q) tables[key].search(q).draw();
  };

  window.doSearch = function(q) {
    if (tables[active]) tables[active].search(q).draw();
  };

  // carry existing search filter
  $(function() {
    if (typeof filters !== 'undefined' && filters) {
      KEYS.forEach(function(k) { tables[k].search(filters).draw(); });
    }
    $('[type="search"]').first().focus();
  });

  // length change
  $(document).on('length.dt', function(e, settings, len) {
    if (typeof pagelength !== 'undefined') pagelength(len);
  });
}());
</script>
