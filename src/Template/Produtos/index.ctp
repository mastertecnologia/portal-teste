<?php
  $this->Breadcrumbs->add('Cadastros', '#', ['class' => 'breadcrumb-item']);
  $this->Breadcrumbs->add('Produtos & Serviços', ['controller' => 'Produtos', 'action' => 'index'], ['class' => 'breadcrumb-item active']);
  $this->append('css', $this->element('pgm_premium_css', ['name' => 'produtos-premium']));

  $cntProd  = count($produtos  ?? []);
  $cntServ  = count($servicos  ?? []);
  $cntContr = count($contratos ?? []);
  $cntTotal = $cntProd + $cntServ + $cntContr;

  // Contagem ativos/inativos produtos
  $prodAtivos = 0; $prodInativos = 0;
  foreach (($produtos ?? []) as $r) { $r->ativo ? $prodAtivos++ : $prodInativos++; }

  // Valor total em estoque (produtos)
  $vlEstoque = 0;
  foreach (($produtos ?? []) as $r) { $vlEstoque += ($r->nPrecoVenda ?? 0) * ($r->nQtdeAtual ?? 0); }

  // Margem média
  $margemTotal = 0; $margemCnt = 0;
  foreach (($produtos ?? []) as $r) {
    if (($r->nPrecoVenda ?? 0) > 0 && ($r->nPrecoCusto ?? 0) > 0) {
      $margemTotal += (($r->nPrecoVenda - $r->nPrecoCusto) / $r->nPrecoVenda) * 100;
      $margemCnt++;
    }
  }
  $margemMedia = $margemCnt > 0 ? round($margemTotal / $margemCnt, 1) : 0;

  if (!function_exists('prdBadge')) {
  function prdBadge($ativo) {
    return $ativo
      ? '<span class="prd-badge prd-badge-on"><i class="fas fa-circle" style="font-size:5px"></i> Ativo</span>'
      : '<span class="prd-badge prd-badge-off"><i class="fas fa-circle" style="font-size:5px"></i> Inativo</span>';
  }
  }

  if (!function_exists('prdStock')) {
  function prdStock($qty) {
    if ($qty <= 0)  return '<span class="prd-stock"><span class="prd-stock-dot out"></span>' . (int)$qty . '</span>';
    if ($qty <= 5)  return '<span class="prd-stock"><span class="prd-stock-dot low"></span>' . (int)$qty . '</span>';
    return '<span class="prd-stock"><span class="prd-stock-dot ok"></span>' . (int)$qty . '</span>';
  }
  }

  if (!function_exists('prdMargin')) {
  function prdMargin($custo, $venda) {
    if ($venda <= 0 || $custo <= 0) return '<span class="prd-td-margin" style="color:var(--prd-dim)">—</span>';
    $m = round((($venda - $custo) / $venda) * 100, 1);
    $cls = $m >= 30 ? 'margin-good' : ($m >= 15 ? 'margin-ok' : 'margin-low');
    return '<span class="prd-td-margin ' . $cls . '">' . $m . '%</span>';
  }
  }

  /*
   * Referência PGM layout-system (como Ordensservico/index):
   * .pgm-layout-ref | .pgm-page-toolbar | .pgm-content-section | .pgm-page-section | .pgm-filter-bar | .pgm-data-region
   */
?>
<div class="col-md-12 p-0">
<div class="prd-root pgm-layout-ref">

  <!-- ── Topbar ─────────────────────────────────────────────── -->
  <div class="prd-topbar pgm-page-toolbar">
    <div>
      <div class="prd-eyebrow">Cadastros &rsaquo; Catálogo</div>
      <h1 class="prd-h1">Produtos &amp; Serviços</h1>
    </div>
    <div class="prd-topbar-actions">
      <?= $this->Html->link(
        '<i class="fas fa-warehouse"></i> Estoque',
        ['action' => 'estoque'],
        ['class' => 'btn-prd-outline', 'escape' => false, 'target' => '_blank']
      ) ?>
      <?= $this->Html->link(
        '<i class="fas fa-tags"></i> Precificação',
        ['action' => 'precificacao'],
        ['class' => 'btn-prd-price', 'escape' => false]
      ) ?>
      <?= $this->Html->link(
        '<i class="fas fa-plus"></i> Novo Cadastro',
        ['action' => 'add'],
        ['class' => 'btn-prd-new', 'escape' => false, 'target' => '_blank']
      ) ?>
    </div>
  </div>

  <!-- ── Stats Bar ──────────────────────────────────────────── -->
  <div class="prd-stats-bar pgm-content-section">
    <div class="prd-stat">
      <div class="prd-stat-icon teal"><i class="fas fa-cubes"></i></div>
      <div>
        <div class="prd-stat-val"><?= $cntTotal ?></div>
        <div class="prd-stat-lbl">Cadastros</div>
      </div>
    </div>
    <div class="prd-stat">
      <div class="prd-stat-icon green"><i class="fas fa-check-circle"></i></div>
      <div>
        <div class="prd-stat-val"><?= $prodAtivos ?></div>
        <div class="prd-stat-lbl">Ativos</div>
      </div>
    </div>
    <div class="prd-stat">
      <div class="prd-stat-icon red"><i class="fas fa-times-circle"></i></div>
      <div>
        <div class="prd-stat-val"><?= $prodInativos ?></div>
        <div class="prd-stat-lbl">Inativos</div>
      </div>
    </div>
    <div class="prd-stat">
      <div class="prd-stat-icon blue"><i class="fas fa-dollar-sign"></i></div>
      <div>
        <div class="prd-stat-val" style="font-size:14px">R$ <?= number_format($vlEstoque, 0, ',', '.') ?></div>
        <div class="prd-stat-lbl">Valor Estoque</div>
      </div>
    </div>
    <div class="prd-stat">
      <div class="prd-stat-icon orange"><i class="fas fa-percentage"></i></div>
      <div>
        <div class="prd-stat-val"><?= $margemMedia ?>%</div>
        <div class="prd-stat-lbl">Margem Média</div>
      </div>
    </div>
  </div>

  <!-- ── KPI Strip ──────────────────────────────────────────── -->
  <div class="prd-kpi-strip pgm-page-section">
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
  <div class="prd-filter-bar pgm-filter-bar">
    <div class="prd-pill-group">
      <button class="prd-pill pill-active" id="pill-produtos"  onclick="showPanel('produtos')">
        <i class="fas fa-box-open" style="font-size:10px"></i> Produtos
      </button>
      <button class="prd-pill" id="pill-servicos"  onclick="showPanel('servicos')">
        <i class="fas fa-laptop-code" style="font-size:10px"></i> Serviços
      </button>
      <button class="prd-pill" id="pill-contratos" onclick="showPanel('contratos')">
        <i class="fas fa-file-contract" style="font-size:10px"></i> Contratos
      </button>
    </div>

    <div class="prd-status-filter">
      <button class="prd-status-pill sp-active" id="sp-todos"    onclick="applyStatus('todos')">Todos</button>
      <button class="prd-status-pill"           id="sp-ativos"   onclick="applyStatus('ativos')">Ativos</button>
      <button class="prd-status-pill"           id="sp-inativos" onclick="applyStatus('inativos')">Inativos</button>
    </div>

    <div class="prd-filter-search">
      <i class="fas fa-search prd-search-icon"></i>
      <input type="text" id="prd-search" placeholder="Buscar produto, código, descrição…" oninput="doSearch(this.value)">
    </div>

    <div class="prd-export-group">
      <button class="prd-export-btn" onclick="exportCSV()" title="Exportar CSV">
        <i class="fas fa-file-csv"></i> CSV
      </button>
      <button class="prd-export-btn" onclick="printTable()" title="Imprimir">
        <i class="fas fa-print"></i>
      </button>
    </div>
  </div>

  <!-- ── Tables ─────────────────────────────────────────────── -->
  <div class="prd-table-wrap pgm-data-region">
    <div class="prd-table-card">

      <!-- ─ Produtos ─ -->
      <div class="prd-table-panel panel-active" id="panel-produtos">
        <table class="prd-table" id="tbl-produtos">
          <thead>
            <tr>
              <th style="width:36px"></th>
              <th>Código</th>
              <th>Descrição</th>
              <th style="text-align:right">Custo</th>
              <th style="text-align:right">Venda</th>
              <th style="text-align:center">Margem</th>
              <th style="text-align:center">Estoque</th>
              <th>Status</th>
              <th style="width:70px"></th>
            </tr>
          </thead>
          <tfoot>
            <tr class="prd-col-filter">
              <th></th>
              <th><input class="prd-col-input" placeholder="Código…"></th>
              <th><input class="prd-col-input" placeholder="Descrição…"></th>
              <th></th><th></th><th></th><th></th><th></th><th></th>
            </tr>
          </tfoot>
          <tbody>
            <?php foreach ($produtos as $reg): ?>
            <?php
              $urlEdit = $this->Url->build(['action' => 'edit', $reg->id]);
            ?>
            <tr onclick="window.open('<?= $urlEdit ?>', '_blank')">
              <td><div class="prd-av prd-av-prod"><i class="fas fa-box-open"></i></div></td>
              <td class="prd-td-code"><?= h($reg->codigo) ?></td>
              <td class="prd-td-desc"><?= h($reg->descricao) ?></td>
              <td class="prd-td-cost" style="text-align:right">
                <?= $reg->nPrecoCusto > 0 ? 'R$ ' . number_format($reg->nPrecoCusto, 2, ',', '.') : '<span style="color:var(--prd-dim)">—</span>' ?>
              </td>
              <td class="prd-td-val" style="text-align:right">R$ <?= number_format($reg->nPrecoVenda ?? $reg->vlunitario, 2, ',', '.') ?></td>
              <td style="text-align:center"><?= prdMargin($reg->nPrecoCusto ?? 0, $reg->nPrecoVenda ?? $reg->vlunitario) ?></td>
              <td style="text-align:center"><?= prdStock($reg->nQtdeAtual ?? 0) ?></td>
              <td><?= prdBadge($reg->ativo) ?></td>
              <td onclick="event.stopPropagation()">
                <div class="prd-actions">
                  <a href="<?= $urlEdit ?>" target="_blank" class="prd-act-btn edit" title="Editar">
                    <i class="fas fa-pen"></i>
                  </a>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <div class="prd-dt-bottom" id="dt-bottom-produtos">
          <span class="prd-dt-total">
            Valor total em estoque: <strong>R$ <?= number_format($vlEstoque, 2, ',', '.') ?></strong>
          </span>
          <span class="prd-dt-total">
            Margem média: <strong><?= $margemMedia ?>%</strong>
          </span>
        </div>
      </div>

      <!-- ─ Serviços ─ -->
      <div class="prd-table-panel" id="panel-servicos">
        <table class="prd-table" id="tbl-servicos">
          <thead>
            <tr>
              <th style="width:36px"></th>
              <th>Código</th>
              <th>Descrição</th>
              <th style="text-align:right">Vl. Hora</th>
              <th>Status</th>
              <th style="width:70px"></th>
            </tr>
          </thead>
          <tfoot>
            <tr class="prd-col-filter">
              <th></th>
              <th><input class="prd-col-input" placeholder="Código…"></th>
              <th><input class="prd-col-input" placeholder="Descrição…"></th>
              <th></th><th></th><th></th>
            </tr>
          </tfoot>
          <tbody>
            <?php foreach ($servicos as $reg): ?>
            <?php $urlEdit = $this->Url->build(['action' => 'edit', $reg->id]); ?>
            <tr onclick="window.open('<?= $urlEdit ?>', '_blank')">
              <td><div class="prd-av prd-av-serv"><i class="fas fa-laptop-code"></i></div></td>
              <td class="prd-td-code"><?= h($reg->codigo) ?></td>
              <td class="prd-td-desc"><?= h($reg->descricao) ?></td>
              <td class="prd-td-val" style="text-align:right">R$ <?= number_format($reg->vlunitario, 2, ',', '.') ?>/h</td>
              <td><?= prdBadge($reg->ativo) ?></td>
              <td onclick="event.stopPropagation()">
                <div class="prd-actions">
                  <a href="<?= $urlEdit ?>" target="_blank" class="prd-act-btn edit" title="Editar"><i class="fas fa-pen"></i></a>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- ─ Contratos ─ -->
      <div class="prd-table-panel" id="panel-contratos">
        <table class="prd-table" id="tbl-contratos">
          <thead>
            <tr>
              <th style="width:36px"></th>
              <th>Código</th>
              <th>Descrição</th>
              <th style="text-align:right">Vl. Mensal</th>
              <th>Status</th>
              <th style="width:70px"></th>
            </tr>
          </thead>
          <tfoot>
            <tr class="prd-col-filter">
              <th></th>
              <th><input class="prd-col-input" placeholder="Código…"></th>
              <th><input class="prd-col-input" placeholder="Descrição…"></th>
              <th></th><th></th><th></th>
            </tr>
          </tfoot>
          <tbody>
            <?php foreach ($contratos as $reg): ?>
            <?php $urlEdit = $this->Url->build(['action' => 'edit', $reg->id]); ?>
            <tr onclick="window.open('<?= $urlEdit ?>', '_blank')">
              <td><div class="prd-av prd-av-contr"><i class="fas fa-file-contract"></i></div></td>
              <td class="prd-td-code"><?= h($reg->codigo) ?></td>
              <td class="prd-td-desc"><?= h($reg->descricao) ?></td>
              <td class="prd-td-val" style="text-align:right">R$ <?= number_format($reg->vlunitario, 2, ',', '.') ?>/mês</td>
              <td><?= prdBadge($reg->ativo) ?></td>
              <td onclick="event.stopPropagation()">
                <div class="prd-actions">
                  <a href="<?= $urlEdit ?>" target="_blank" class="prd-act-btn edit" title="Editar"><i class="fas fa-pen"></i></a>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

    </div><!-- /.prd-table-card -->
  </div><!-- /.prd-table-wrap -->

</div><!-- /.prd-root -->
</div>

<script>
(function () {
  var ptLang = {
    sProcessing:'Processando...', sLengthMenu:'_MENU_ por página',
    sZeroRecords:'Nenhum registro encontrado', sEmptyTable:'Nenhum dado disponível',
    sInfo:'_START_–_END_ de _TOTAL_', sInfoEmpty:'0 registros',
    sInfoFiltered:'(filtrado de _MAX_)', sSearch:'', sLoadingRecords:'Carregando...',
    oPaginate:{ sFirst:'«', sLast:'»', sNext:'›', sPrevious:'‹' }
  };

  var pageLen = <?= (int)($pagelength ?? 25) ?>;

  // Init all 3 tables
  var tables = {};

  ['produtos','servicos','contratos'].forEach(function(key) {
    var tbl = $('#tbl-' + key);
    var dt  = tbl.DataTable({
      language: ptLang,
      pageLength: pageLen,
      dom: 'lrtip',
      columnDefs: [
        { orderable: false, targets: [0, -1] }
      ],
      initComplete: function() {
        // Per-column filter — tfoot inputs
        var api = this.api();
        api.columns().every(function(i) {
          var col   = this;
          var input = tbl.find('tfoot tr.prd-col-filter th').eq(i).find('input');
          if (!input.length) return;
          input.on('keyup change', function() {
            if (col.search() !== this.value) { col.search(this.value).draw(); }
          });
        });
        api.columns.adjust();
      }
    });
    tables[key] = dt;

    // length change hook
    tbl.on('length.dt', function(e, s, len) {
      if (typeof pagelength === 'function') pagelength(len);
    });
  });

  /* ── Panel switch ───────────────────────────── */
  var active = 'produtos';
  var KEYS   = ['produtos','servicos','contratos'];

  window.showPanel = function(key) {
    if (key === 'todos') {
      key = <?= $cntProd ?> > 0 ? 'produtos'
          : (<?= $cntServ ?> > 0 ? 'servicos' : 'contratos');
    }
    KEYS.forEach(function(k) {
      document.getElementById('panel-' + k).classList.toggle('panel-active', k === key);
      var kpi  = document.getElementById('kpi-' + k);
      var pill = document.getElementById('pill-' + k);
      if (kpi)  kpi.classList.toggle('kpi-active', k === key);
      if (pill) pill.classList.toggle('pill-active', k === key);
    });
    document.getElementById('kpi-todos').classList.remove('kpi-active');
    active = key;
    setTimeout(function() { tables[key].columns.adjust().draw(false); }, 50);
    // carry search
    var q = document.getElementById('prd-search').value;
    if (q) tables[key].search(q).draw();
    // carry status
    applyStatus(currentStatus, true);
  };

  /* ── Global search ──────────────────────────── */
  window.doSearch = function(q) {
    if (tables[active]) tables[active].search(q).draw();
  };

  /* ── Status filter ──────────────────────────── */
  var currentStatus = 'todos';

  window.applyStatus = function(st, silent) {
    if (!silent) {
      currentStatus = st;
      ['todos','ativos','inativos'].forEach(function(s) {
        document.getElementById('sp-' + s).classList.toggle('sp-active', s === st);
      });
    }
    // Use DataTables column search on "Status" column
    // Column index for status: produtos=7, servicos=4, contratos=4
    var statusColIdx = (active === 'produtos') ? 7 : 4;
    var search = st === 'ativos' ? '^\\s*Ativo\\s*$' : (st === 'inativos' ? '^\\s*Inativo\\s*$' : '');
    var isRegex = st !== 'todos';
    tables[active].column(statusColIdx).search(search, isRegex, false).draw();
  };

  /* ── Export CSV ─────────────────────────────── */
  window.exportCSV = function() {
    var dt   = tables[active];
    var rows = dt.rows({ search: 'applied' }).data();
    var lines = [];

    if (active === 'produtos') {
      lines.push(['Código','Descrição','Custo','Venda','Margem','Estoque','Status'].join(';'));
    } else {
      lines.push(['Código','Descrição','Valor','Status'].join(';'));
    }

    rows.each(function(row) {
      // row is array of HTML strings — strip tags
      var cols = [];
      for (var i = 1; i < row.length - 1; i++) {
        var tmp = document.createElement('div');
        tmp.innerHTML = row[i];
        cols.push('"' + (tmp.textContent || '').trim().replace(/"/g,'""') + '"');
      }
      lines.push(cols.join(';'));
    });

    var blob = new Blob(['\uFEFF' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8' });
    var url  = URL.createObjectURL(blob);
    var a    = document.createElement('a');
    a.href = url; a.download = 'produtos-' + active + '.csv';
    a.click(); URL.revokeObjectURL(url);
  };

  /* ── Print ──────────────────────────────────── */
  window.printTable = function() {
    var dt   = tables[active];
    var rows = dt.rows({ search: 'applied' }).nodes().toArray();
    var head = document.querySelector('#tbl-' + active + ' thead tr:first-child').outerHTML;
    var body = rows.map(function(r) { return r.outerHTML; }).join('');
    var win  = window.open('', '_blank');
    win.document.write(
      '<html><head><title>Produtos</title>' +
      '<style>body{font-family:sans-serif;font-size:12px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #ccc;padding:6px 8px;text-align:left}th{background:#f0f0f0}</style>' +
      '</head><body><h2>Produtos &amp; Serviços</h2><table><thead>' + head + '</thead><tbody>' + body + '</tbody></table></body></html>'
    );
    win.document.close();
    win.focus();
    setTimeout(function(){ win.print(); win.close(); }, 400);
  };

  /* ── Init ───────────────────────────────────── */
  $(function() {
    if (typeof filters !== 'undefined' && filters) {
      KEYS.forEach(function(k) { tables[k].search(filters).draw(); });
    }
    document.getElementById('prd-search').focus();
  });

}());
</script>
