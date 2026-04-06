<?php
/**
 * Faturamento — Novo documento
 */
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Faturamento', ['controller' => 'Faturamento', 'action' => 'index']);
$this->Breadcrumbs->add('Novo Documento');
?>
<style>
.fatadd-root { font-family:'DM Sans',sans-serif; max-width:860px; margin:0 auto; }
.fatadd-card { background:#161b22; border:1px solid rgba(255,255,255,.07); border-radius:10px; margin:16px 0; padding:20px 22px; }
.fatadd-card-title { font-size:11px; text-transform:uppercase; letter-spacing:.08em; color:#7d8590; font-weight:600; margin-bottom:16px; }
.fatadd-row { display:flex; gap:16px; flex-wrap:wrap; }
.fatadd-field { flex:1; min-width:180px; }
.fatadd-field label { font-size:11.5px; color:#7d8590; font-weight:600; display:block; margin-bottom:4px; }
.fatadd-field .form-control { background:#0d1117; border:1px solid rgba(255,255,255,.10); border-radius:7px; color:#e6edf3; font-size:13.5px; }
.fatadd-field .form-control:focus { border-color:rgba(29,158,117,.5); box-shadow:0 0 0 3px rgba(29,158,117,.12); outline:none; }
.fatadd-items-table { width:100%; border-collapse:collapse; font-size:13px; margin-top:8px; }
.fatadd-items-table th { color:#7d8590; font-size:11px; text-transform:uppercase; letter-spacing:.05em; font-weight:600; padding:6px 8px; border-bottom:1px solid rgba(255,255,255,.07); text-align:left; }
.fatadd-items-table td { padding:6px 4px; }
.fatadd-items-table td input { background:#0d1117; border:1px solid rgba(255,255,255,.08); border-radius:5px; color:#e6edf3; padding:5px 8px; font-size:12.5px; width:100%; }
.fatadd-items-table td input.fatadd-input-qtd { width:70px; }
.fatadd-items-table td input.fatadd-input-vuni { width:100px; }
.fatadd-items-table td input.fatadd-input-vtot { width:100px; background:rgba(0,0,0,.2); cursor:default; }
body.pgm-theme-light .fatadd-items-table td input.fatadd-input-vtot { background:#f3f4f6; }
.fatadd-add-item { margin-top:10px; }
/* Modo claro */
body.pgm-theme-light .fatadd-card { background:#fff; border-color:#e1e4e8; }
body.pgm-theme-light .fatadd-card-title { color:#6b7280; }
body.pgm-theme-light .fatadd-field label { color:#374151; }
body.pgm-theme-light .fatadd-field .form-control { background:#fff; border-color:#d0d7de; color:#1a1f2e; }
body.pgm-theme-light .fatadd-items-table th { color:#6b7280; border-color:#e5e7eb; }
body.pgm-theme-light .fatadd-items-table td input { background:#fff; border-color:#d0d7de; color:#1a1f2e; }
.fatadd-th-w45 { width:45%; }
.fatadd-th-w12 { width:12%; }
.fatadd-th-w18 { width:18%; }
.fatadd-th-w7 { width:7%; }
.fatadd-total-row { text-align:right; margin-top:12px; }
.fatadd-total-strong { font-size:15px; color:#5cdbc0; }
body.pgm-theme-light .fatadd-total-strong { color:#00a876; }
.fatadd-footer-actions { display:flex; gap:10px; justify-content:flex-end; padding:8px 0 24px; }
</style>

<div class="fatadd-root">
    <?= $this->Form->create($doc, ['id' => 'formFaturamento']) ?>

    <div class="fatadd-card">
        <div class="fatadd-card-title">Dados do Documento</div>
        <div class="fatadd-row">
            <div class="fatadd-field">
                <label>Cliente *</label>
                <?= $this->Form->control('idcliente', [
                    'type' => 'select',
                    'options' => $clientes,
                    'empty' => '— Selecione —',
                    'label' => false,
                    'class' => 'form-control',
                ]) ?>
            </div>
            <div class="fatadd-field">
                <label>Tipo</label>
                <?= $this->Form->control('tipo', [
                    'type' => 'select',
                    'options' => ['servico' => 'Serviço', 'locacao' => 'Locação', 'produto' => 'Produto', 'misto' => 'Misto'],
                    'label' => false,
                    'class' => 'form-control',
                ]) ?>
            </div>
            <div class="fatadd-field">
                <label>Data de Emissão *</label>
                <?= $this->Form->control('data_emissao', [
                    'type' => 'text',
                    'class' => 'form-control datepicker',
                    'label' => false,
                    'value' => date('d/m/Y'),
                ]) ?>
            </div>
            <div class="fatadd-field">
                <label>Vencimento</label>
                <?= $this->Form->control('data_vencimento', [
                    'type' => 'text',
                    'class' => 'form-control datepicker',
                    'label' => false,
                ]) ?>
            </div>
        </div>
    </div>

    <div class="fatadd-card">
        <div class="fatadd-card-title">Itens / Serviços</div>
        <table class="fatadd-items-table" id="tblItens">
            <thead>
                <tr>
                    <th class="fatadd-th-w45">Descrição</th>
                    <th class="fatadd-th-w12">Qtd</th>
                    <th class="fatadd-th-w18">Vlr Unit. (R$)</th>
                    <th class="fatadd-th-w18">Total (R$)</th>
                    <th class="fatadd-th-w7"></th>
                </tr>
            </thead>
            <tbody id="itensBody">
                <tr class="item-row">
                    <td><input type="text" name="itens[0][descricao]" placeholder="Descrição do serviço/produto" class="item-desc"></td>
                    <td><input type="number" name="itens[0][quantidade]" value="1" min="0.01" step="0.01" class="item-qtd fatadd-input-qtd"></td>
                    <td><input type="number" name="itens[0][valor_unitario]" value="0" min="0" step="0.01" class="item-vuni fatadd-input-vuni"></td>
                    <td><input type="text" name="itens[0][valor_total]" value="0,00" readonly class="item-vtotal fatadd-input-vtot"></td>
                    <td><button type="button" class="btn btn-xs btn-danger rm-item" title="Remover"><i class="fa fa-times"></i></button></td>
                </tr>
            </tbody>
        </table>
        <div class="fatadd-add-item">
            <button type="button" class="btn btn-default btn-sm" id="btnAddItem">
                <i class="fa fa-plus"></i> Adicionar Item
            </button>
        </div>
        <div class="fatadd-total-row">
            <strong class="fatadd-total-strong">Total: R$ <span id="totalGeral">0,00</span></strong>
        </div>
        <?= $this->Form->hidden('valor_total', ['id' => 'hiddenTotal', 'value' => '0']) ?>
    </div>

    <div class="fatadd-card">
        <div class="fatadd-card-title">Observações</div>
        <?= $this->Form->control('descricao', ['type' => 'textarea', 'label' => false, 'class' => 'form-control', 'rows' => 2, 'placeholder' => 'Referência, observações…']) ?>
    </div>

    <div class="fatadd-footer-actions">
        <?= $this->Html->link('Cancelar', ['action' => 'index'], ['class' => 'btn btn-default btn-sm']) ?>
        <button type="submit" class="btn btn-pgm btn-pgm-salvar btn-sm">
            <i class="fas fa-save"></i> Criar Documento
        </button>
    </div>

    <?= $this->Form->end() ?>
</div>

<script>
var itemIdx = 1;

function calcRow($row) {
    var qtd  = parseFloat($row.find('.item-qtd').val()) || 0;
    var vuni = parseFloat($row.find('.item-vuni').val()) || 0;
    var tot  = qtd * vuni;
    $row.find('.item-vtotal').val(tot.toFixed(2).replace('.', ','));
    return tot;
}

function recalcTotal() {
    var total = 0;
    $('.item-row').each(function() { total += calcRow($(this)); });
    $('#totalGeral').text(total.toFixed(2).replace('.', ','));
    $('#hiddenTotal').val(total.toFixed(2));
}

$(document).on('input', '.item-qtd, .item-vuni', function() {
    calcRow($(this).closest('.item-row'));
    recalcTotal();
});

$('#btnAddItem').on('click', function() {
    var $newRow = $('<tr class="item-row">'
        + '<td><input type="text" name="itens[' + itemIdx + '][descricao]" placeholder="Descrição" class="item-desc"></td>'
        + '<td><input type="number" name="itens[' + itemIdx + '][quantidade]" value="1" min="0.01" step="0.01" class="item-qtd fatadd-input-qtd"></td>'
        + '<td><input type="number" name="itens[' + itemIdx + '][valor_unitario]" value="0" min="0" step="0.01" class="item-vuni fatadd-input-vuni"></td>'
        + '<td><input type="text" name="itens[' + itemIdx + '][valor_total]" value="0,00" readonly class="item-vtotal fatadd-input-vtot"></td>'
        + '<td><button type="button" class="btn btn-xs btn-danger rm-item"><i class="fa fa-times"></i></button></td>'
        + '</tr>');
    $('#itensBody').append($newRow);
    itemIdx++;
});

$(document).on('click', '.rm-item', function() {
    if ($('.item-row').length > 1) {
        $(this).closest('.item-row').remove();
        recalcTotal();
    }
});

recalcTotal();
</script>
