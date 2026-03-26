<?php
  $this->Breadcrumbs->add('Cadastros', '#', ['class' => 'breadcrumb-item']);
  $this->Breadcrumbs->add('Produtos', ['controller' => 'Produtos', 'action' => 'index'], ['class' => 'breadcrumb-item']);
  $this->Breadcrumbs->add('Cadastrar', [], ['class' => 'breadcrumb-item active']);
  $this->append('css', $this->Html->css('/css/produtos-premium', ['timestamp' => true]));
?>

<div class="prd-form-root">

  <?= $this->Form->create($produto, ['class' => 'prd-main-form', 'id' => 'prdForm']) ?>

  <!-- ── Topbar ─────────────────────────────────────────────── -->
  <div class="prd-form-topbar">
    <div>
      <div class="prd-eyebrow">Cadastros &rsaquo; Produtos &rsaquo; Novo</div>
    </div>
    <div class="prd-form-topbar-info">
      <h1 class="prd-h1" style="font-size:20px;">Cadastrar Produto / Serviço</h1>
    </div>
    <?= $this->Html->link(
      '<i class="fas fa-arrow-left"></i> Voltar',
      ['action' => 'index'],
      ['class' => 'btn-prd-secondary', 'escape' => false]
    ) ?>
  </div>

  <!-- ── Corpo do formulário ────────────────────────────────── -->
  <div class="prd-form-body">

    <!-- Seção 1: Tipo -->
    <div class="prd-section">
      <div class="prd-section-head">
        <div class="prd-section-icon"><i class="fas fa-tag"></i></div>
        <span class="prd-section-title">Tipo de Cadastro</span>
      </div>
      <div class="prd-section-body">
        <div class="prd-tipo-group" id="prdTipoGroup">
          <!-- Buttons populated by JS after reading select options -->
        </div>
        <!-- Select real — oculto, movimentado pelo JS -->
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
              'required'    => true,
              'placeholder' => 'Ex: 001',
            ]) ?>
          </div>

          <div class="prd-fgroup">
            <label for="descricao">Descrição <span style="color:#f85149">*</span></label>
            <?= $this->Form->control('descricao', [
              'class'       => 'form-control',
              'id'          => 'descricao',
              'label'       => false,
              'required'    => true,
              'placeholder' => 'Descrição do produto ou serviço',
            ]) ?>
          </div>

          <div class="prd-fgroup">
            <label for="unidade">Unidade</label>
            <?= $this->Form->text('unidade', [
              'class'       => 'form-control',
              'id'          => 'unidade',
              'placeholder' => 'Ex: UN, KG, M²',
            ]) ?>
          </div>

          <div class="prd-fgroup">
            <label for="vlunitario">Valor Unitário <span style="color:#f85149">*</span></label>
            <div class="prd-input-prefix">
              <?= $this->Form->text('vlunitario', [
                'class'       => 'mascaramonetaria form-control',
                'id'          => 'vlunitario',
                'required'    => true,
                'placeholder' => '0,00',
              ]) ?>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- Seção 3: Locação (só para Produto) -->
    <div class="prd-section" id="sectionLocacao" style="display:none;">
      <div class="prd-section-head">
        <div class="prd-section-icon"><i class="fas fa-calendar-alt"></i></div>
        <span class="prd-section-title">Valores de Locação</span>
        <span style="font-size:11px;color:var(--prd-muted);margin-left:auto;">Opcional — preencha se o produto for locado</span>
      </div>
      <div class="prd-section-body">
        <div class="prd-fg prd-fg-4">

          <div class="prd-fgroup">
            <label for="vllocdiario">Diário</label>
            <div class="prd-input-prefix">
              <?= $this->Form->text('vllocdiario', [
                'class'       => 'mascaramonetaria form-control',
                'id'          => 'vllocdiario',
                'placeholder' => '0,00',
              ]) ?>
            </div>
          </div>

          <div class="prd-fgroup">
            <label for="vllocsemanal">Semanal</label>
            <div class="prd-input-prefix">
              <?= $this->Form->text('vllocsemanal', [
                'class'       => 'mascaramonetaria form-control',
                'id'          => 'vllocsemanal',
                'placeholder' => '0,00',
              ]) ?>
            </div>
          </div>

          <div class="prd-fgroup">
            <label for="vllocquinzenal">Quinzenal</label>
            <div class="prd-input-prefix">
              <?= $this->Form->text('vllocquinzenal', [
                'class'       => 'mascaramonetaria form-control',
                'id'          => 'vllocquinzenal',
                'placeholder' => '0,00',
              ]) ?>
            </div>
          </div>

          <div class="prd-fgroup">
            <label for="vllocmensal">Mensal</label>
            <div class="prd-input-prefix">
              <?= $this->Form->text('vllocmensal', [
                'class'       => 'mascaramonetaria form-control',
                'id'          => 'vllocmensal',
                'placeholder' => '0,00',
              ]) ?>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- Seção 4: Configurações -->
    <div class="prd-section">
      <div class="prd-section-head">
        <div class="prd-section-icon"><i class="fas fa-sliders-h"></i></div>
        <span class="prd-section-title">Configurações</span>
      </div>
      <div class="prd-section-body">
        <div class="prd-fg prd-fg-2">

          <div class="prd-fgroup">
            <label for="ativo">Situação</label>
            <?= $this->Form->control('ativo', [
              'options' => C_ProdutosAtivo,
              'value'   => C_ProdutosAtivoSim,
              'class'   => 'form-control',
              'id'      => 'ativo',
              'label'   => false,
              'required' => true,
            ]) ?>
          </div>

        </div>
      </div>
    </div>

  </div><!-- /.prd-form-body -->

  <!-- ── Footer fixo ────────────────────────────────────────── -->
  <div class="prd-form-footer">
    <?= $this->Html->link(
      '<i class="fas fa-times"></i> Cancelar',
      ['action' => 'index'],
      ['class' => 'btn-prd-secondary', 'escape' => false]
    ) ?>
    <?= $this->Form->button(
      '<i class="fas fa-check"></i> Cadastrar',
      ['class' => 'btn-prd-primary', 'escape' => false]
    ) ?>
  </div>

  <?= $this->Form->end() ?>

</div><!-- /.prd-form-root -->

<script>
(function() {
  // Ícones para cada tipo de item — mapeados por label (case-insensitive)
  var TIPO_ICONS = { produto: 'fa-box-open', serviço: 'fa-laptop-code', servico: 'fa-laptop-code', contrato: 'fa-file-contract' };

  function iconForLabel(lbl) {
    var key = lbl.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    for (var k in TIPO_ICONS) {
      if (key.indexOf(k) !== -1) return TIPO_ICONS[k];
    }
    return 'fa-tag';
  }

  var $sel = document.getElementById('tipo');
  var group = document.getElementById('prdTipoGroup');

  // Build toggle buttons from select options
  Array.from($sel.options).forEach(function(opt) {
    if (!opt.value) return; // skip empty
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'prd-tipo-btn';
    btn.dataset.val = opt.value;
    btn.innerHTML = '<i class="fas ' + iconForLabel(opt.text) + '"></i> ' + opt.text;
    btn.addEventListener('click', function() { prdSetTipo(opt.value); });
    group.appendChild(btn);
  });

  // Show/hide Locação section based on tipo
  var LOCACAO_TIPOS = ['produto', 'p', '1']; // common values for "Produto"

  function updateSections(val, label) {
    var isProduct = LOCACAO_TIPOS.indexOf(String(val).toLowerCase()) !== -1
      || (label || '').toLowerCase().indexOf('produto') !== -1;
    document.getElementById('sectionLocacao').style.display = isProduct ? '' : 'none';
  }

  window.prdSetTipo = function(val) {
    $sel.value = val;
    var selectedLabel = $sel.options[$sel.selectedIndex] ? $sel.options[$sel.selectedIndex].text : '';
    group.querySelectorAll('.prd-tipo-btn').forEach(function(btn) {
      btn.classList.toggle('tipo-active', btn.dataset.val === String(val));
    });
    updateSections(val, selectedLabel);
  };

  // Init: activate first option
  if ($sel.options.length > 0) {
    var firstVal = $sel.value || $sel.options[0].value;
    prdSetTipo(firstVal);
  }

  // Sync if user somehow changes the hidden select directly
  $sel.addEventListener('change', function() { prdSetTipo(this.value); });
}());
</script>
