<?php
  $this->Breadcrumbs->add('Cadastros', '#', ['class' => 'breadcrumb-item']);
  $this->Breadcrumbs->add('Produtos', ['controller' => 'Produtos', 'action' => 'index'], ['class' => 'breadcrumb-item']);
  $this->Breadcrumbs->add('Novo Cadastro', [], ['class' => 'breadcrumb-item active']);
  $this->append('css', $this->element('pgm_premium_css', ['name' => 'produtos-premium']));
?>

<div class="prd-form-root">
<?= $this->Form->create($produto, ['id' => 'prdForm', 'novalidate' => true]) ?>

  <!-- ── Topbar ─────────────────────────────────────────────── -->
  <div class="prd-form-topbar">
    <div class="prd-form-topbar-info">
      <div class="prd-eyebrow">Cadastros &rsaquo; Produtos &rsaquo; Novo</div>
      <h1 class="prd-h1" style="font-size:20px">Cadastrar Produto / Serviço / Contrato</h1>
    </div>
    <?= $this->Html->link(
      '<i class="fas fa-arrow-left"></i> Voltar',
      ['action' => 'index'],
      ['class' => 'btn-prd-secondary', 'escape' => false]
    ) ?>
  </div>

  <!-- ── Layout: main + aside ───────────────────────────────── -->
  <div class="prd-form-layout">

    <!-- COLUNA PRINCIPAL -->
    <div class="prd-form-main">

      <!-- Seção 1: Tipo -->
      <div class="prd-section">
        <div class="prd-section-head">
          <div class="prd-section-icon"><i class="fas fa-tag"></i></div>
          <span class="prd-section-title">Tipo de Cadastro</span>
        </div>
        <div class="prd-section-body">
          <div class="prd-tipo-cards" id="prdTipoCards">
            <!-- Populado pelo JS com base nas opções do select -->
          </div>
          <!-- Select real oculto — sincronizado pelo JS -->
          <div class="prd-tipo-select-wrap">
            <?= $this->Form->control('tipo', [
              'options'  => C_ProdutosTipo,
              'class'    => 'form-control',
              'label'    => false,
              'id'       => 'tipo',
              'required' => true,
            ]) ?>
          </div>
        </div>
      </div>

      <!-- Seção 2: Dados Principais -->
      <div class="prd-section">
        <div class="prd-section-head">
          <div class="prd-section-icon"><i class="fas fa-info-circle"></i></div>
          <span class="prd-section-title">Dados Principais</span>
        </div>
        <div class="prd-section-body">
          <div class="prd-fg prd-fg-main">

            <div class="prd-fgroup">
              <label for="codigo">Código</label>
              <?= $this->Form->text('codigo', [
                'class'       => 'form-control',
                'id'          => 'codigo',
                'placeholder' => '001',
                'oninput'     => 'prdPreview()',
              ]) ?>
            </div>

            <div class="prd-fgroup">
              <label for="descricao">Descrição <span style="color:var(--prd-red)">*</span></label>
              <?= $this->Form->control('descricao', [
                'class'       => 'form-control',
                'id'          => 'descricao',
                'label'       => false,
                'required'    => true,
                'placeholder' => 'Nome completo do produto ou serviço',
                'oninput'     => 'prdPreview()',
              ]) ?>
            </div>

            <div class="prd-fgroup">
              <label for="unidade">Unidade de Medida</label>
              <?= $this->Form->text('unidade', [
                'class'       => 'form-control',
                'id'          => 'unidade',
                'placeholder' => 'UN, KG, M², HR…',
                'oninput'     => 'prdPreview()',
              ]) ?>
            </div>

            <div class="prd-fgroup">
              <label for="ativo">Situação <span style="color:var(--prd-red)">*</span></label>
              <?= $this->Form->control('ativo', [
                'options'  => C_ProdutosAtivo,
                'value'    => C_ProdutosAtivoSim,
                'class'    => 'form-control',
                'id'       => 'ativo',
                'label'    => false,
                'required' => true,
              ]) ?>
            </div>

          </div>
        </div>
      </div>

      <!-- Seção 3: Precificação -->
      <div class="prd-section">
        <div class="prd-section-head">
          <div class="prd-section-icon"><i class="fas fa-tags"></i></div>
          <span class="prd-section-title">Precificação</span>
          <span class="prd-section-hint">Preço de venda calculado automaticamente</span>
        </div>
        <div class="prd-section-body">

          <!-- Grade de inputs -->
          <div class="prd-fg prd-fg-price">

            <div class="prd-fgroup">
              <label for="vlcusto">Preço de Custo</label>
              <div class="prd-input-pfx" data-prefix="R$">
                <input type="text" id="vlcusto" class="prd-input mascaramonetaria"
                       placeholder="0,00" oninput="calcPreco()">
              </div>
              <span class="hint">Não é salvo — usado para calcular o preço de venda</span>
            </div>

            <div class="prd-fgroup">
              <label for="vlmarkup">Markup (%)</label>
              <div class="prd-input-sfx" data-suffix="%">
                <input type="number" id="vlmarkup" class="prd-input"
                       placeholder="0" min="0" max="9999" step="0.1"
                       oninput="calcPreco()">
              </div>
            </div>

            <div class="prd-fgroup">
              <label for="vlunitario">Preço de Venda <span style="color:var(--prd-red)">*</span></label>
              <div class="prd-input-pfx" data-prefix="R$">
                <?= $this->Form->text('vlunitario', [
                  'class'       => 'mascaramonetaria form-control',
                  'id'          => 'vlunitario',
                  'required'    => true,
                  'placeholder' => '0,00',
                  'oninput'     => 'prdPreview()',
                ]) ?>
              </div>
            </div>

          </div>

          <!-- Fórmula visual -->
          <div class="prd-price-formula" id="prdFormula">
            <div class="prd-price-formula-item">
              <label>Custo</label>
              <div class="val custo" id="fml-custo">R$ 0,00</div>
            </div>
            <div class="prd-price-formula-op">+</div>
            <div class="prd-price-formula-item">
              <label>Markup</label>
              <div class="val markup" id="fml-markup">0%</div>
            </div>
            <div class="prd-price-formula-op">=</div>
            <div class="prd-price-formula-item">
              <label>Venda</label>
              <div class="val venda" id="fml-venda">R$ 0,00</div>
            </div>
            <div style="flex:1;min-width:120px">
              <div class="prd-markup-bar">
                <div class="prd-markup-bar-fill" id="markup-bar" style="width:0%"></div>
              </div>
              <div style="font-size:10px;color:var(--prd-muted);margin-top:4px" id="fml-lucro">Lucro: R$ 0,00</div>
            </div>
          </div>

        </div>
      </div>

      <!-- Seção 4: Locação (só Produto) -->
      <div class="prd-section" id="sectionLocacao" style="display:none">
        <div class="prd-section-head">
          <div class="prd-section-icon"><i class="fas fa-calendar-alt"></i></div>
          <span class="prd-section-title">Valores de Locação</span>
          <span class="prd-section-hint">Preencha se este produto puder ser locado</span>
        </div>
        <div class="prd-section-body">

          <div class="prd-loc-auto">
            <label class="prd-loc-toggle">
              <input type="checkbox" id="locAutoCalc" onchange="locAutoFill()">
              Calcular automaticamente a partir do valor mensal
            </label>
          </div>

          <div class="prd-fg prd-fg-4">

            <div class="prd-fgroup">
              <label for="vllocmensal">Mensal <span style="color:var(--prd-red)">*</span></label>
              <div class="prd-input-pfx" data-prefix="R$">
                <?= $this->Form->text('vllocmensal', [
                  'class'       => 'mascaramonetaria form-control',
                  'id'          => 'vllocmensal',
                  'placeholder' => '0,00',
                  'oninput'     => 'locAutoFill()',
                ]) ?>
              </div>
            </div>

            <div class="prd-fgroup">
              <label for="vllocsemanal">Semanal</label>
              <div class="prd-input-pfx" data-prefix="R$">
                <?= $this->Form->text('vllocsemanal', [
                  'class'       => 'mascaramonetaria form-control',
                  'id'          => 'vllocsemanal',
                  'placeholder' => '0,00',
                ]) ?>
              </div>
            </div>

            <div class="prd-fgroup">
              <label for="vllocquinzenal">Quinzenal</label>
              <div class="prd-input-pfx" data-prefix="R$">
                <?= $this->Form->text('vllocquinzenal', [
                  'class'       => 'mascaramonetaria form-control',
                  'id'          => 'vllocquinzenal',
                  'placeholder' => '0,00',
                ]) ?>
              </div>
            </div>

            <div class="prd-fgroup">
              <label for="vllocdiario">Diário</label>
              <div class="prd-input-pfx" data-prefix="R$">
                <?= $this->Form->text('vllocdiario', [
                  'class'       => 'mascaramonetaria form-control',
                  'id'          => 'vllocdiario',
                  'placeholder' => '0,00',
                ]) ?>
              </div>
            </div>

          </div>

          <!-- Preview de valores calculados -->
          <div class="prd-loc-preview" id="locPreview" style="display:none">
            <div class="prd-loc-chip">
              <div class="prd-loc-chip-lbl">Mensal</div>
              <div class="prd-loc-chip-val" id="lc-mensal">—</div>
            </div>
            <div class="prd-loc-chip">
              <div class="prd-loc-chip-lbl">Quinzenal</div>
              <div class="prd-loc-chip-val" id="lc-quinzenal">—</div>
            </div>
            <div class="prd-loc-chip">
              <div class="prd-loc-chip-lbl">Semanal</div>
              <div class="prd-loc-chip-val" id="lc-semanal">—</div>
            </div>
            <div class="prd-loc-chip">
              <div class="prd-loc-chip-lbl">Diário</div>
              <div class="prd-loc-chip-val" id="lc-diario">—</div>
            </div>
          </div>

        </div>
      </div>

    </div><!-- /.prd-form-main -->

    <!-- COLUNA ASIDE: Preview card -->
    <div class="prd-form-aside">

      <div class="prd-preview-card">
        <div class="prd-preview-card-head">
          <i class="fas fa-eye"></i> Pré-visualização
        </div>
        <div class="prd-preview-card-body">
          <div class="prd-preview-av" id="prev-av"><i class="fas fa-box-open"></i></div>
          <div class="prd-preview-title empty" id="prev-title">Sem descrição</div>

          <div class="prd-preview-row">
            <span class="prd-preview-row-label">Código</span>
            <span class="prd-preview-row-val" id="prev-codigo">—</span>
          </div>
          <div class="prd-preview-row">
            <span class="prd-preview-row-label">Tipo</span>
            <span class="prd-preview-row-val" id="prev-tipo">—</span>
          </div>
          <div class="prd-preview-row">
            <span class="prd-preview-row-label">Unidade</span>
            <span class="prd-preview-row-val" id="prev-unidade">—</span>
          </div>
          <div class="prd-preview-row">
            <span class="prd-preview-row-label">Preço Venda</span>
            <span class="prd-preview-row-val" id="prev-preco" style="color:var(--prd-teal-lt)">—</span>
          </div>
          <div class="prd-preview-row">
            <span class="prd-preview-row-label">Situação</span>
            <span class="prd-preview-row-val" id="prev-ativo">—</span>
          </div>
        </div>
      </div>

      <!-- Dicas rápidas -->
      <div class="prd-section">
        <div class="prd-section-head">
          <div class="prd-section-icon"><i class="fas fa-lightbulb"></i></div>
          <span class="prd-section-title">Dicas</span>
        </div>
        <div class="prd-section-body" style="font-size:12px;color:var(--prd-muted);line-height:1.6">
          <p style="margin:0 0 8px">
            <i class="fas fa-box-open" style="color:var(--prd-teal);width:14px"></i>
            <strong style="color:var(--prd-text2)">Produto</strong> — item físico com controle de estoque e opção de locação.
          </p>
          <p style="margin:0 0 8px">
            <i class="fas fa-laptop-code" style="color:var(--prd-blue);width:14px"></i>
            <strong style="color:var(--prd-text2)">Serviço</strong> — prestação de serviço cobrada por hora ou unidade.
          </p>
          <p style="margin:0">
            <i class="fas fa-file-contract" style="color:var(--prd-orange);width:14px"></i>
            <strong style="color:var(--prd-text2)">Contrato</strong> — cobrança recorrente (assinatura, mensalidade).
          </p>
        </div>
      </div>

    </div><!-- /.prd-form-aside -->

  </div><!-- /.prd-form-layout -->

  <!-- ── Footer fixo ────────────────────────────────────────── -->
  <div class="prd-form-footer">
    <div class="prd-form-footer-left">
      <i class="fas fa-shield-alt" style="color:var(--prd-teal)"></i>
      Todos os campos obrigatórios marcados com <span style="color:var(--prd-red)">*</span>
    </div>
    <div class="prd-form-footer-right">
      <?= $this->Html->link(
        '<i class="fas fa-times"></i> Cancelar',
        ['action' => 'index'],
        ['class' => 'btn-prd-secondary', 'escape' => false]
      ) ?>
      <?= $this->Form->button(
        '<i class="fas fa-check"></i> Cadastrar',
        ['class' => 'btn-prd-primary', 'escape' => false, 'id' => 'btnSalvar']
      ) ?>
    </div>
  </div>

<?= $this->Form->end() ?>
</div><!-- /.prd-form-root -->

<script>
(function () {

  /* ── Configurações dos tipos ─────────────────────────────── */
  var TIPO_CONFIG = {
    produto:  { icon: 'fa-box-open',     cls: '',      avCls: '',      label: 'Produto',  desc: 'Item físico com estoque', feats: ['Controle de estoque','Preço de custo/venda','Valores de locação','Margem de lucro'] },
    servico:  { icon: 'fa-laptop-code',  cls: 'serv',  avCls: 'serv',  label: 'Serviço',  desc: 'Mão de obra ou consultoria', feats: ['Cobrança por hora','Sem controle de estoque','Integra Ordens de Serviço'] },
    servico2: { icon: 'fa-laptop-code',  cls: 'serv',  avCls: 'serv',  label: 'Serviço',  desc: 'Mão de obra ou consultoria', feats: ['Cobrança por hora','Sem controle de estoque','Integra Ordens de Serviço'] },
    contrato: { icon: 'fa-file-contract',cls: 'contr', avCls: 'contr', label: 'Contrato', desc: 'Cobrança recorrente/assinatura', feats: ['Valor mensal fixo','Faturamento automático','Suporte e SLA'] },
  };

  function matchConfig(label) {
    var l = label.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'');
    if (l.indexOf('produto') !== -1) return TIPO_CONFIG.produto;
    if (l.indexOf('servi')   !== -1) return TIPO_CONFIG.servico;
    if (l.indexOf('contrat') !== -1) return TIPO_CONFIG.contrato;
    return { icon: 'fa-tag', cls: '', avCls: '', label: label, desc: '', feats: [] };
  }

  /* ── Build tipo cards ────────────────────────────────────── */
  var $sel  = document.getElementById('tipo');
  var cards = document.getElementById('prdTipoCards');
  var cardEls = [];

  Array.from($sel.options).forEach(function(opt) {
    if (!opt.value) return;
    var cfg = matchConfig(opt.text);
    var featsHtml = cfg.feats.map(function(f) { return '<li>' + f + '</li>'; }).join('');

    var card = document.createElement('div');
    card.className = 'prd-tipo-card';
    card.dataset.val  = opt.value;
    card.dataset.tipo = cfg.cls || 'produto';
    card.innerHTML =
      '<div class="prd-tipo-card-icon"><i class="fas ' + cfg.icon + '"></i></div>' +
      '<div class="prd-tipo-card-title">' + opt.text + '</div>' +
      '<div class="prd-tipo-card-desc">' + cfg.desc + '</div>' +
      (featsHtml ? '<ul class="prd-tipo-card-feats">' + featsHtml + '</ul>' : '');

    card.addEventListener('click', function() { prdSetTipo(opt.value, cfg); });
    cards.appendChild(card);
    cardEls.push({ el: card, val: opt.value, cfg: cfg });
  });

  /* ── Set tipo ────────────────────────────────────────────── */
  var currentTipoCfg = TIPO_CONFIG.produto;

  window.prdSetTipo = function(val, cfg) {
    $sel.value = val;
    if (!cfg) {
      var found = cardEls.find(function(c) { return c.val === val; });
      cfg = found ? found.cfg : TIPO_CONFIG.produto;
    }
    currentTipoCfg = cfg;
    cardEls.forEach(function(c) {
      c.el.classList.toggle('tipo-active', c.val === val);
    });
    // Locação só para Produto
    var isProduct = cfg.label.toLowerCase().indexOf('produto') !== -1;
    document.getElementById('sectionLocacao').style.display = isProduct ? '' : 'none';

    // Update preview avatar
    var av = document.getElementById('prev-av');
    av.className = 'prd-preview-av' + (cfg.avCls ? ' ' + cfg.avCls : '');
    av.innerHTML = '<i class="fas ' + cfg.icon + '"></i>';
    document.getElementById('prev-tipo').textContent = cfg.label;

    prdPreview();
  };

  // Init first option
  if ($sel.options.length > 0) {
    prdSetTipo($sel.value || $sel.options[0].value);
  }

  /* ── Preview card update ─────────────────────────────────── */
  window.prdPreview = function() {
    var desc  = document.getElementById('descricao').value.trim();
    var cod   = document.getElementById('codigo').value.trim();
    var unid  = document.getElementById('unidade').value.trim();
    var preco = document.getElementById('vlunitario').value.trim();
    var ativoEl = document.getElementById('ativo');
    var ativoTxt = ativoEl.options[ativoEl.selectedIndex]?.text || '—';

    var titleEl = document.getElementById('prev-title');
    titleEl.textContent = desc || 'Sem descrição';
    titleEl.className = 'prd-preview-title' + (desc ? '' : ' empty');

    document.getElementById('prev-codigo').textContent  = cod   || '—';
    document.getElementById('prev-unidade').textContent = unid  || '—';
    document.getElementById('prev-preco').textContent   = preco ? 'R$ ' + preco : '—';
    document.getElementById('prev-ativo').textContent   = ativoTxt;
  };

  document.getElementById('ativo').addEventListener('change', prdPreview);

  /* ── Calculadora de preço ────────────────────────────────── */
  function parseMoeda(str) {
    // "1.234,56" → 1234.56
    return parseFloat((str || '0').replace(/\./g,'').replace(',','.')) || 0;
  }

  function fmtMoeda(n) {
    return n.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function setMoeda(el, n) {
    // Set value in "1.234,56" format so jquery.mask doesn't break
    el.value = fmtMoeda(n);
    // Trigger input event for mask plugin
    el.dispatchEvent(new Event('input', { bubbles: true }));
  }

  window.calcPreco = function() {
    var custo  = parseMoeda(document.getElementById('vlcusto').value);
    var markup = parseFloat(document.getElementById('vlmarkup').value) || 0;

    var venda  = custo > 0 ? custo * (1 + markup / 100) : 0;
    var lucro  = venda - custo;
    var pct    = venda > 0 ? Math.min((markup / (markup + 100)) * 100, 100) : 0;

    // Fórmula visual
    document.getElementById('fml-custo').textContent  = 'R$ ' + fmtMoeda(custo);
    document.getElementById('fml-markup').textContent = markup.toFixed(1) + '%';
    document.getElementById('fml-venda').textContent  = 'R$ ' + fmtMoeda(venda);
    document.getElementById('fml-lucro').textContent  = 'Lucro: R$ ' + fmtMoeda(lucro);
    document.getElementById('markup-bar').style.width = pct + '%';

    // Se há custo calculado, preenche vlunitario
    if (custo > 0) {
      setMoeda(document.getElementById('vlunitario'), venda);
      prdPreview();
    }
  };

  /* ── Locação auto-calculada ──────────────────────────────── */
  window.locAutoFill = function() {
    var chk = document.getElementById('locAutoCalc').checked;
    var preview = document.getElementById('locPreview');

    if (!chk) { preview.style.display = 'none'; return; }

    var mensal    = parseMoeda(document.getElementById('vllocmensal').value);
    var quinzenal = mensal > 0 ? mensal * 0.6 : 0;
    var semanal   = mensal > 0 ? mensal * 0.3 : 0;
    var diario    = mensal > 0 ? mensal / 22  : 0;  // ~22 dias úteis

    if (mensal > 0) {
      setMoeda(document.getElementById('vllocquinzenal'), quinzenal);
      setMoeda(document.getElementById('vllocsemanal'),   semanal);
      setMoeda(document.getElementById('vllocdiario'),    diario);

      document.getElementById('lc-mensal').textContent    = 'R$ ' + fmtMoeda(mensal);
      document.getElementById('lc-quinzenal').textContent = 'R$ ' + fmtMoeda(quinzenal);
      document.getElementById('lc-semanal').textContent   = 'R$ ' + fmtMoeda(semanal);
      document.getElementById('lc-diario').textContent    = 'R$ ' + fmtMoeda(diario);
      preview.style.display = '';
    } else {
      preview.style.display = 'none';
    }
  };

  // Init preview
  prdPreview();

}());
</script>
