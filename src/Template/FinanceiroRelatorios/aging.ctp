<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Financeiro', ['controller' => 'Financeiro', 'action' => 'index']);
$this->Breadcrumbs->add('Relatórios', ['action' => 'index']);
$this->Breadcrumbs->add('Aging');
?>
<style>
.ag-root { font-family:'DM Sans',sans-serif; }
.ag-topbar { display:flex; align-items:center; justify-content:space-between; padding:16px 24px 12px; border-bottom:1px solid rgba(255,255,255,.07); flex-wrap:wrap; gap:10px; }
.ag-h1 { font-size:18px; font-weight:600; color:#e6edf3; }
.ag-h1-ico { color:#5cdbc0; margin-right:8px; }
.ag-filters { display:flex; gap:10px; padding:12px 20px; align-items:center; border-bottom:1px solid rgba(255,255,255,.07); }
.ag-filters select { background:#161b22; border:1px solid rgba(255,255,255,.10); border-radius:7px; color:#c9d1d9; padding:6px 10px; font-size:12.5px; height:34px; }
.ag-body { padding:20px 24px; }
.ag-faixa { background:#161b22; border:1px solid rgba(255,255,255,.07); border-radius:10px; padding:14px 18px; margin-bottom:12px; }
.ag-faixa-header { display:flex; justify-content:space-between; align-items:center; cursor:pointer; }
.ag-faixa-label { font-size:13px; font-weight:700; color:#e6edf3; }
.ag-faixa-total { font-size:14px; font-weight:700; color:#5cdbc0; }
.ag-faixa-count { font-size:11px; color:#7d8590; margin-left:8px; }
.ag-items { margin-top:10px; font-size:12.5px; }
.ag-item { display:flex; justify-content:space-between; padding:4px 0; border-bottom:1px solid rgba(255,255,255,.03); color:#c9d1d9; }
.ag-item-cli { flex:1; }
.ag-item-val { min-width:120px; text-align:right; font-weight:600; }
.ag-vencido { color:#f85149; }
.ag-avencer { color:#3fb950; }
</style>

<div class="ag-root">
    <div class="ag-topbar">
        <div class="ag-h1"><i class="fas fa-hourglass-half ag-h1-ico"></i>Aging</div>
        <div>
            <?= $this->Html->link('<i class="fas fa-file-excel"></i> Excel', ['action' => 'exportarAgingExcel', '?' => ['tipo' => $tipo]], ['class' => 'btn btn-pgm btn-pgm-situacao btn-sm', 'escape' => false]) ?>
            <?= $this->Html->link('Voltar', ['action' => 'index'], ['class' => 'btn btn-default btn-sm']) ?>
        </div>
    </div>

    <div class="ag-filters">
        <?= $this->Form->create(null, ['type' => 'get']) ?>
        <select name="tipo" onchange="this.form.submit()">
            <option value="receita" <?= $tipo === 'receita' ? 'selected' : '' ?>>Contas a Receber</option>
            <option value="despesa" <?= $tipo === 'despesa' ? 'selected' : '' ?>>Contas a Pagar</option>
        </select>
        <?= $this->Form->end() ?>
    </div>

    <div class="ag-body">
        <?php
        $cores = ['a_vencer' => 'ag-avencer', '1_30' => 'ag-vencido', '31_60' => 'ag-vencido', '61_90' => 'ag-vencido', '90_mais' => 'ag-vencido'];
        foreach ($faixas as $key => $f):
        ?>
        <div class="ag-faixa">
            <div class="ag-faixa-header">
                <span class="ag-faixa-label <?= $cores[$key] ?? '' ?>"><?= $f['label'] ?> <span class="ag-faixa-count">(<?= count($f['items']) ?>)</span></span>
                <span class="ag-faixa-total">R$ <?= number_format($f['total'], 2, ',', '.') ?></span>
            </div>
            <?php if (!empty($f['items'])): ?>
            <div class="ag-items">
                <?php foreach (array_slice($f['items'], 0, 20) as $l): ?>
                <div class="ag-item">
                    <span class="ag-item-cli">
                        <?php
                            $nome = '—';
                            if (!empty($l->cliente)) {
                                $nome = ($l->cliente->tipo == 1) ? ($l->cliente->nome ?? '—') : ($l->cliente->razaosocial ?? '—');
                            }
                        ?>
                        <?= h($nome) ?> — <?= h($l->descricao) ?>
                        <small style="color:#7d8590;">(<?= $l->data_vencimento ? $l->data_vencimento->format('d/m/Y') : '' ?>)</small>
                    </span>
                    <span class="ag-item-val">R$ <?= number_format((float)$l->valor, 2, ',', '.') ?></span>
                </div>
                <?php endforeach; ?>
                <?php if (count($f['items']) > 20): ?>
                <div style="color:#7d8590; font-size:11px; padding:4px 0;">... e mais <?= count($f['items']) - 20 ?> título(s)</div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
