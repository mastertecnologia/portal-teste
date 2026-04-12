<?php
$fpmCtrl = $this->request->getParam('controller');
$isEntrada = ($fpmCtrl === 'FiscalNotasEntrada');
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Módulo Fiscal', ['controller' => 'Fiscal', 'action' => 'index']);
$this->Breadcrumbs->add($isEntrada ? 'Notas de entrada' : 'Notas de saída', ['controller' => $fpmCtrl, 'action' => 'index']);
$this->Breadcrumbs->add('Nova');
echo $this->element('Fiscal/styles');

$finalOpts = [1 => 'Normal', 2 => 'Complementar', 3 => 'Ajuste', 4 => 'Devolução'];
$presOpts = [0 => 'Não se aplica', 1 => 'Presencial', 9 => 'Outros'];
?>
<div class="fpm-wrap">
    <div class="fpm-topbar">
        <h1 class="fpm-h1"><i class="fas fa-plus"></i>Nova nota fiscal</h1>
        <?= $this->Html->link('Cancelar', ['controller' => $fpmCtrl, 'action' => 'index'], ['class' => 'btn btn-default btn-sm']) ?>
    </div>

    <?= $this->element('Fiscal/regime_context') ?>

    <?= $this->Form->create($nota, ['id' => 'fpmFormNota']) ?>

    <div class="fpm-card">
        <div class="fpm-card-title">Cabeçalho</div>
        <div class="fpm-row">
            <div class="fpm-field">
                <label>Cliente</label>
                <?= $this->Form->control('idcliente', [
                    'type' => 'select', 'options' => $clientes, 'empty' => '— Selecione —',
                    'label' => false, 'class' => 'form-control',
                ]) ?>
            </div>
            <div class="fpm-field">
                <label>Modelo</label>
                <?= $this->Form->control('modelo', [
                    'type' => 'select', 'options' => $modelos, 'label' => false, 'class' => 'form-control',
                ]) ?>
            </div>
            <div class="fpm-field" style="max-width:120px;">
                <label>Série</label>
                <?= $this->Form->control('serie', ['type' => 'number', 'label' => false, 'class' => 'form-control', 'value' => $nota->serie ?? 1]) ?>
            </div>
            <div class="fpm-field">
                <label>Natureza de operação</label>
                <?= $this->Form->control('natureza_operacao_id', [
                    'type' => 'select', 'options' => $naturezas, 'empty' => '— Selecione —',
                    'label' => false, 'class' => 'form-control', 'id' => 'fpmNatId',
                ]) ?>
                <?= $this->Form->hidden('natureza_operacao', ['id' => 'fpmNatStr', 'value' => $nota->natureza_operacao ?? '']) ?>
            </div>
        </div>
        <div class="fpm-row">
            <div class="fpm-field" style="max-width:200px;">
                <label>Finalidade</label>
                <?= $this->Form->control('finalidade', ['type' => 'select', 'options' => $finalOpts, 'label' => false, 'class' => 'form-control']) ?>
            </div>
            <div class="fpm-field" style="max-width:200px;">
                <label>Presença</label>
                <?= $this->Form->control('presenca', ['type' => 'select', 'options' => $presOpts, 'label' => false, 'class' => 'form-control']) ?>
            </div>
        </div>
    </div>

    <div class="fpm-card">
        <div class="fpm-card-title">Itens <span class="fpm-muted">(uma linha de série por linha no campo séries)</span></div>
        <table class="fpm-items-table" id="fpmTblItens">
            <thead>
                <tr>
                    <th style="width:32px;">#</th>
                    <th>Descrição *</th>
                    <th style="width:90px;">CFOP *</th>
                    <th style="width:110px;">NCM</th>
                    <th style="width:70px;">UN</th>
                    <th style="width:90px;">Qtd *</th>
                    <th style="width:110px;">V. unit. *</th>
                    <th style="width:160px;">Nºs de série</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="fpmItensBody">
                <tr class="fpm-item-row" data-idx="0">
                    <td>1</td>
                    <td>
                        <div style="display:flex; gap:4px;">
                            <input name="fiscal_notas_itens[0][descricao]" required class="form-control" placeholder="Descrição" style="flex:1;">
                            <button type="button" class="btn btn-sm btn-pgm" onclick="fpmSugerirIa(this)" title="Sugerir NCM e CFOP com IA" style="padding:4px 8px; font-size:12px; background:#e0a024; border:none;"><i class="fas fa-magic"></i></button>
                        </div>
                    </td>
                    <td><input name="fiscal_notas_itens[0][cfop]" required class="form-control" placeholder="5102" maxlength="5"></td>
                    <td><input name="fiscal_notas_itens[0][ncm]" class="form-control" maxlength="10"></td>
                    <td><input name="fiscal_notas_itens[0][unidade]" class="form-control" value="UN" maxlength="10"></td>
                    <td><input name="fiscal_notas_itens[0][quantidade]" type="number" step="0.0001" min="0.0001" value="1" required class="form-control"></td>
                    <td><input name="fiscal_notas_itens[0][valor_unitario]" type="number" step="0.0001" min="0" value="0" required class="form-control"></td>
                    <td><textarea class="fpm-serial-area" rows="2" placeholder="Um por linha" data-serial-for="0"></textarea></td>
                    <td></td>
                </tr>
            </tbody>
        </table>
        <button type="button" class="btn btn-default btn-sm mt-2" id="fpmAddItem"><i class="fa fa-plus"></i> Item</button>
    </div>

    <div class="fpm-card">
        <div class="fpm-card-title">Pagamento (opcional)</div>
        <div class="fpm-row">
            <div class="fpm-field">
                <label>Forma</label>
                <?= $this->Form->control('fiscal_notas_pagamentos.0.forma_pagamento', [
                    'type' => 'select', 'options' => $formasPagamento, 'label' => false, 'class' => 'form-control', 'default' => '01',
                ]) ?>
            </div>
            <div class="fpm-field" style="max-width:200px;">
                <label>Valor (R$)</label>
                <?= $this->Form->control('fiscal_notas_pagamentos.0.valor', [
                    'type' => 'number', 'step' => '0.01', 'label' => false, 'class' => 'form-control', 'value' => '0',
                ]) ?>
            </div>
        </div>
    </div>

    <div class="fpm-card">
        <div class="fpm-card-title">Observações</div>
        <?= $this->Form->control('informacoes_complementares', ['type' => 'textarea', 'label' => false, 'class' => 'form-control', 'rows' => 2]) ?>
    </div>

    <div class="fpm-footer px-3">
        <button type="submit" class="btn btn-pgm btn-pgm-salvar"><i class="fas fa-save"></i> Salvar rascunho</button>
    </div>

    <?= $this->Form->end() ?>
</div>

<script>
(function() {
    var itemIdx = 0;
    function syncNatureza() {
        var sel = document.getElementById('fpmNatId');
        var hid = document.getElementById('fpmNatStr');
        if (!sel || !hid) return;
        var opt = sel.options[sel.selectedIndex];
        hid.value = opt ? opt.text.trim() : '';
    }
    document.getElementById('fpmNatId') && document.getElementById('fpmNatId').addEventListener('change', syncNatureza);
    syncNatureza();

    function renumber() {
        var rows = document.querySelectorAll('#fpmItensBody .fpm-item-row');
        rows.forEach(function(row, i) {
            row.setAttribute('data-idx', String(i));
            row.querySelector('td:first-child').textContent = String(i + 1);
            row.querySelectorAll('input, textarea').forEach(function(el) {
                var n = el.getAttribute('name');
                if (n && n.indexOf('fiscal_notas_itens[') === 0) {
                    el.setAttribute('name', n.replace(/fiscal_notas_itens\[\d+\]/, 'fiscal_notas_itens[' + i + ']'));
                }
            });
            var ta = row.querySelector('[data-serial-for]');
            if (ta) ta.setAttribute('data-serial-for', String(i));
        });
        itemIdx = rows.length - 1;
    }

    document.getElementById('fpmAddItem').addEventListener('click', function() {
        itemIdx++;
        var tr = document.createElement('tr');
        tr.className = 'fpm-item-row';
        tr.innerHTML = '<td></td>' +
            '<td><div style="display:flex; gap:4px;"><input name="fiscal_notas_itens[' + itemIdx + '][descricao]" required class="form-control" placeholder="Descrição" style="flex:1;"><button type="button" class="btn btn-sm btn-pgm" onclick="fpmSugerirIa(this)" title="Sugerir NCM e CFOP com IA" style="padding:4px 8px; font-size:12px; background:#e0a024; border:none;"><i class="fas fa-magic"></i></button></div></td>' +
            '<td><input name="fiscal_notas_itens[' + itemIdx + '][cfop]" required class="form-control" maxlength="5"></td>' +
            '<td><input name="fiscal_notas_itens[' + itemIdx + '][ncm]" class="form-control" maxlength="10"></td>' +
            '<td><input name="fiscal_notas_itens[' + itemIdx + '][unidade]" class="form-control" value="UN"></td>' +
            '<td><input name="fiscal_notas_itens[' + itemIdx + '][quantidade]" type="number" step="0.0001" min="0.0001" value="1" required class="form-control"></td>' +
            '<td><input name="fiscal_notas_itens[' + itemIdx + '][valor_unitario]" type="number" step="0.0001" min="0" value="0" required class="form-control"></td>' +
            '<td><textarea class="fpm-serial-area" rows="2" data-serial-for="' + itemIdx + '"></textarea></td>' +
            '<td><button type="button" class="btn btn-xs btn-danger fpmRmItem">&times;</button></td>';
        document.getElementById('fpmItensBody').appendChild(tr);
        renumber();
    });

    document.getElementById('fpmItensBody').addEventListener('click', function(e) {
        if (e.target.closest('.fpmRmItem')) {
            var rows = document.querySelectorAll('#fpmItensBody .fpm-item-row');
            if (rows.length <= 1) return;
            e.target.closest('tr').remove();
            renumber();
        }
    });

    document.getElementById('fpmFormNota').addEventListener('submit', function() {
        var form = document.getElementById('fpmFormNota');
        form.querySelectorAll('.fpm-serial-hidden').forEach(function(n) { n.remove(); });
        document.querySelectorAll('#fpmItensBody .fpm-item-row').forEach(function(row, i) {
            var ni = document.createElement('input');
            ni.type = 'hidden';
            ni.className = 'fpm-serial-hidden';
            ni.name = 'fiscal_notas_itens[' + i + '][numero_item]';
            ni.value = String(i + 1);
            form.appendChild(ni);
            var ta = row.querySelector('.fpm-serial-area');
            if (!ta) return;
            var lines = ta.value.split(/\r?\n/).map(function(s) { return s.trim(); }).filter(Boolean);
            lines.forEach(function(sn, j) {
                var inp = document.createElement('input');
                inp.type = 'hidden';
                inp.className = 'fpm-serial-hidden';
                inp.name = 'fiscal_notas_itens[' + i + '][fiscal_notas_itens_series][' + j + '][numero_serie]';
                inp.value = sn;
                form.appendChild(inp);
            });
        });
    });
})();

function fpmSugerirIa(btn) {
    let tr = btn.closest('tr');
    let descInp = tr.querySelector('input[name$="[descricao]"]');
    if(!descInp || !descInp.value.trim()) { alert("Escreva a descrição do produto antes de pedir sugestão à IA."); return; }
    
    let originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;

    let csrfInput = document.querySelector('input[name="_csrfToken"]');
    let cToken = csrfInput ? csrfInput.value : '';

    fetch('<?= $this->Url->build(['action' => 'ajaxSugerirNcm']) ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': cToken
        },
        body: JSON.stringify({descricao: descInp.value.trim()})
    }).then(r => r.json()).then(res => {
        btn.innerHTML = originalHtml;
        btn.disabled = false;
        if (res.error) {
            alert("⚠️ " + res.error);
        } else {
            let ncmInp = tr.querySelector('input[name$="[ncm]"]');
            let cfopInp = tr.querySelector('input[name$="[cfop]"]');
            if(ncmInp && res.ncm) ncmInp.value = res.ncm;
            if(cfopInp && res.cfop) cfopInp.value = res.cfop;
            btn.style.background = '#1D9E75';
            setTimeout(() => btn.style.background = '#e0a024', 2000);
        }
    }).catch(e => {
        btn.innerHTML = originalHtml;
        btn.disabled = false;
        alert("Falha de conexão com a infraestrutura de IA.");
    });
}
</script>
