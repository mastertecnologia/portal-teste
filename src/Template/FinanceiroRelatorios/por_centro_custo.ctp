<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Financeiro', ['controller' => 'Financeiro', 'action' => 'index']);
$this->Breadcrumbs->add('Relatórios', ['action' => 'index']);
$this->Breadcrumbs->add('Por Centro de Custo');
?>
<style>
.cc-root { font-family:'DM Sans',sans-serif; }
.cc-topbar { display:flex; align-items:center; justify-content:space-between; padding:16px 24px 12px; border-bottom:1px solid rgba(255,255,255,.07); flex-wrap:wrap; gap:10px; }
.cc-h1 { font-size:18px; font-weight:600; color:#e6edf3; }
.cc-h1-ico { color:#5cdbc0; margin-right:8px; }
.cc-filters { display:flex; gap:10px; padding:12px 20px; align-items:center; border-bottom:1px solid rgba(255,255,255,.07); }
.cc-filters input { background:#161b22; border:1px solid rgba(255,255,255,.10); border-radius:7px; color:#c9d1d9; padding:6px 10px; font-size:12.5px; height:34px; }
.cc-table-wrap { padding:20px 24px; }
table.cc-table { width:100%; border-collapse:collapse; font-size:13px; }
.cc-table th { background:#161b22; color:#7d8590; font-size:11px; text-transform:uppercase; letter-spacing:.05em; font-weight:600; padding:8px 12px; border-bottom:1px solid rgba(255,255,255,.07); text-align:left; }
.cc-table td { padding:10px 12px; border-bottom:1px solid rgba(255,255,255,.04); color:#c9d1d9; }
.cc-table tr:hover td { background:rgba(255,255,255,.03); }
.cc-receita { color:#3fb950; font-weight:600; }
.cc-despesa { color:#f85149; font-weight:600; }
.cc-saldo-pos { color:#3fb950; font-weight:700; }
.cc-saldo-neg { color:#f85149; font-weight:700; }
.cc-total-row td { font-weight:700; border-top:1px solid rgba(29,158,117,.2); color:#5cdbc0; }
</style>

<div class="cc-root">
    <div class="cc-topbar">
        <div class="cc-h1"><i class="fas fa-sitemap cc-h1-ico"></i>Relatório por Centro de Custo</div>
        <div>
            <?= $this->Html->link('<i class="fas fa-file-excel"></i> Excel', ['action' => 'exportarCentroCustoExcel', '?' => ['periodo' => $periodo]], ['class' => 'btn btn-pgm btn-pgm-situacao btn-sm', 'escape' => false]) ?>
            <?= $this->Html->link('Voltar', ['action' => 'index'], ['class' => 'btn btn-default btn-sm']) ?>
        </div>
    </div>

    <div class="cc-filters">
        <?= $this->Form->create(null, ['type' => 'get']) ?>
        <label style="color:#7d8590; font-size:12px; font-weight:600;">Período:</label>
        <input type="month" name="periodo" value="<?= h($periodo) ?>" onchange="this.form.submit()" />
        <?= $this->Form->end() ?>
    </div>

    <div class="cc-table-wrap">
        <?php if (empty($porCentro)): ?>
            <div style="text-align:center; padding:48px; color:#484f58;">
                <i class="fas fa-sitemap" style="font-size:32px; display:block; margin-bottom:10px; opacity:.3;"></i>
                Nenhum lançamento no período selecionado.
            </div>
        <?php else: ?>
        <?php
            $tRec = 0; $tDesp = 0;
            foreach ($porCentro as $c) { $tRec += $c['receita']; $tDesp += $c['despesa']; }
        ?>
        <table class="cc-table">
            <thead>
                <tr>
                    <th>Centro de Custo</th>
                    <th>Receitas</th>
                    <th>Despesas</th>
                    <th>Saldo</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($porCentro as $c): ?>
                <?php $saldo = $c['receita'] - $c['despesa']; ?>
                <tr>
                    <td><?= h($c['label']) ?></td>
                    <td class="cc-receita">R$ <?= number_format($c['receita'], 2, ',', '.') ?></td>
                    <td class="cc-despesa">R$ <?= number_format($c['despesa'], 2, ',', '.') ?></td>
                    <td class="<?= $saldo >= 0 ? 'cc-saldo-pos' : 'cc-saldo-neg' ?>">
                        R$ <?= number_format($saldo, 2, ',', '.') ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="cc-total-row">
                    <td>TOTAL</td>
                    <td>R$ <?= number_format($tRec, 2, ',', '.') ?></td>
                    <td>R$ <?= number_format($tDesp, 2, ',', '.') ?></td>
                    <td>R$ <?= number_format($tRec - $tDesp, 2, ',', '.') ?></td>
                </tr>
            </tfoot>
        </table>
        <?php endif; ?>
    </div>
</div>
