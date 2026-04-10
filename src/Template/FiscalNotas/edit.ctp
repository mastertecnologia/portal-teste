<?php
$fpmCtrl = $this->request->getParam('controller');
$isEntrada = ($fpmCtrl === 'FiscalNotasEntrada');
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Módulo Fiscal', ['controller' => 'Fiscal', 'action' => 'index']);
$this->Breadcrumbs->add($isEntrada ? 'Notas de entrada' : 'Notas de saída', ['controller' => $fpmCtrl, 'action' => 'index']);
$this->Breadcrumbs->add('Editar #' . (int)$nota->id);
echo $this->element('Fiscal/styles');

$finalOpts = [1 => 'Normal', 2 => 'Complementar', 3 => 'Ajuste', 4 => 'Devolução'];
$presOpts = [0 => 'Não se aplica', 1 => 'Presencial', 9 => 'Outros'];
$itens = $nota->fiscal_notas_itens ?: [];
$pags = $nota->fiscal_notas_pagamentos ?: [];
$p0 = $pags[0] ?? null;
?>
<div class="fpm-wrap">
    <div class="fpm-topbar">
        <h1 class="fpm-h1"><i class="fas fa-edit"></i>Editar nota #<?= (int)$nota->id ?></h1>
        <?= $this->Html->link('Cancelar', ['controller' => $fpmCtrl, 'action' => 'view', $nota->id], ['class' => 'btn btn-default btn-sm']) ?>
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
                <?= $this->Form->control('modelo', ['type' => 'select', 'options' => $modelos, 'label' => false, 'class' => 'form-control']) ?>
            </div>
            <div class="fpm-field" style="max-width:120px;">
                <label>Série</label>
                <?= $this->Form->control('serie', ['type' => 'number', 'label' => false, 'class' => 'form-control']) ?>
            </div>
            <div class="fpm-field">
                <label>Natureza de operação</label>
                <?= $this->Form->control('natureza_operacao_id', [
                    'type' => 'select', 'options' => $naturezas, 'empty' => '— Selecione —',
                    'label' => false, 'class' => 'form-control', 'id' => 'fpmNatId',
                ]) ?>
                <?= $this->Form->hidden('natureza_operacao', ['id' => 'fpmNatStr']) ?>
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
        <div class="fpm-card-title">Itens</div>
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
                <?php foreach ($itens as $i => $it):
                    $ser = [];
                    if (!empty($it->fiscal_notas_itens_series)) {
                        foreach ($it->fiscal_notas_itens_series as $s) {
                            $ser[] = $s->numero_serie;
                        }
                    }
                    $serTxt = implode("\n", $ser);
                ?>
                <tr class="fpm-item-row" data-idx="<?= (int)$i ?>">
                    <td><?= (int)$i + 1 ?></td>
                    <td>
                        <input type="hidden" name="fiscal_notas_itens[<?= (int)$i ?>][id]" value="<?= (int)$it->id ?>">
                        <input name="fiscal_notas_itens[<?= (int)$i ?>][descricao]" required class="form-control" value="<?= h($it->descricao) ?>">
                    </td>
                    <td><input name="fiscal_notas_itens[<?= (int)$i ?>][cfop]" required class="form-control" value="<?= h($it->cfop) ?>" maxlength="5"></td>
                    <td><input name="fiscal_notas_itens[<?= (int)$i ?>][ncm]" class="form-control" value="<?= h($it->ncm) ?>"></td>
                    <td><input name="fiscal_notas_itens[<?= (int)$i ?>][unidade]" class="form-control" value="<?= h($it->unidade) ?>"></td>
                    <td><input name="fiscal_notas_itens[<?= (int)$i ?>][quantidade]" type="number" step="0.0001" min="0.0001" required class="form-control" value="<?= h($it->quantidade) ?>"></td>
                    <td><input name="fiscal_notas_itens[<?= (int)$i ?>][valor_unitario]" type="number" step="0.0001" min="0" required class="form-control" value="<?= h($it->valor_unitario) ?>"></td>
                    <td><textarea class="fpm-serial-area" rows="2" data-serial-for="<?= (int)$i ?>"><?= h($serTxt) ?></textarea></td>
                    <td><?php if ($i > 0): ?><button type="button" class="btn btn-xs btn-danger fpmRmItem">&times;</button><?php endif; ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (count($itens) === 0): ?>
                <tr class="fpm-item-row" data-idx="0">
                    <td>1</td>
                    <td><input name="fiscal_notas_itens[0][descricao]" required class="form-control"></td>
                    <td><input name="fiscal_notas_itens[0][cfop]" required class="form-control" maxlength="5"></td>
                    <td><input name="fiscal_notas_itens[0][ncm]" class="form-control"></td>
                    <td><input name="fiscal_notas_itens[0][unidade]" class="form-control" value="UN"></td>
                    <td><input name="fiscal_notas_itens[0][quantidade]" type="number" step="0.0001" value="1" required class="form-control"></td>
                    <td><input name="fiscal_notas_itens[0][valor_unitario]" type="number" step="0.0001" value="0" required class="form-control"></td>
                    <td><textarea class="fpm-serial-area" rows="2" data-serial-for="0"></textarea></td>
                    <td></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <button type="button" class="btn btn-default btn-sm mt-2" id="fpmAddItem"><i class="fa fa-plus"></i> Item</button>
    </div>

    <div class="fpm-card">
        <div class="fpm-card-title">Pagamento</div>
        <div class="fpm-row">
            <div class="fpm-field">
                <label>Forma</label>
                <?= $this->Form->control('fiscal_notas_pagamentos.0.forma_pagamento', [
                    'type' => 'select', 'options' => $formasPagamento, 'label' => false, 'class' => 'form-control',
                    'value' => $p0 ? $p0->forma_pagamento : '01',
                ]) ?>
            </div>
            <div class="fpm-field" style="max-width:200px;">
                <label>Valor (R$)</label>
                <?= $this->Form->control('fiscal_notas_pagamentos.0.valor', [
                    'type' => 'number', 'step' => '0.01', 'label' => false, 'class' => 'form-control',
                    'value' => $p0 ? $p0->valor : 0,
                ]) ?>
            </div>
        </div>
        <?php if ($p0 && $p0->id): ?>
            <?= $this->Form->hidden('fiscal_notas_pagamentos.0.id', ['value' => $p0->id]) ?>
        <?php endif; ?>
    </div>

    <div class="fpm-card">
        <div class="fpm-card-title">Observações</div>
        <?= $this->Form->control('informacoes_complementares', ['type' => 'textarea', 'label' => false, 'class' => 'form-control', 'rows' => 2]) ?>
    </div>

    <div class="fpm-footer px-3">
        <button type="submit" class="btn btn-pgm btn-pgm-salvar"><i class="fas fa-save"></i> Salvar</button>
    </div>

    <?= $this->Form->end() ?>
</div>

<script>
(function() {
    var itemIdx = <?= max(0, count($itens) - 1) ?>;
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
            '<td><input name="fiscal_notas_itens[' + itemIdx + '][descricao]" required class="form-control"></td>' +
            '<td><input name="fiscal_notas_itens[' + itemIdx + '][cfop]" required class="form-control" maxlength="5"></td>' +
            '<td><input name="fiscal_notas_itens[' + itemIdx + '][ncm]" class="form-control"></td>' +
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
</script>
