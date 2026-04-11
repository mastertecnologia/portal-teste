<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Financeiro', ['controller' => 'Financeiro', 'action' => 'index']);
$this->Breadcrumbs->add('Relatórios', ['action' => 'index']);
$this->Breadcrumbs->add('Inadimplência');
?>
<style>
.in-root { font-family:'DM Sans',sans-serif; }
.in-topbar { display:flex; align-items:center; justify-content:space-between; padding:16px 24px 12px; border-bottom:1px solid rgba(255,255,255,.07); }
.in-h1 { font-size:18px; font-weight:600; color:#e6edf3; }
.in-h1-ico { color:#5cdbc0; margin-right:8px; }
.in-table-wrap { padding:20px 24px; }
table.in-table { width:100%; border-collapse:collapse; font-size:13px; }
.in-table th { background:#161b22; color:#7d8590; font-size:11px; text-transform:uppercase; letter-spacing:.05em; font-weight:600; padding:8px 12px; border-bottom:1px solid rgba(255,255,255,.07); text-align:left; }
.in-table td { padding:10px 12px; border-bottom:1px solid rgba(255,255,255,.04); color:#c9d1d9; }
.in-table tr:hover td { background:rgba(255,255,255,.03); }
.in-zero { text-align:center; padding:48px; color:#484f58; }
.in-zero-ico { font-size:32px; display:block; margin-bottom:10px; opacity:.3; }
.in-dias-alta { color:#f85149; font-weight:700; }
</style>

<div class="in-root">
    <div class="in-topbar">
        <div class="in-h1"><i class="fas fa-exclamation-triangle in-h1-ico"></i>Inadimplência</div>
        <div>
            <?= $this->Html->link('<i class="fas fa-file-excel"></i> Excel', ['action' => 'exportarInadimplenciaExcel'], ['class' => 'btn btn-pgm btn-pgm-situacao btn-sm', 'escape' => false]) ?>
            <?= $this->Html->link('Voltar', ['action' => 'index'], ['class' => 'btn btn-default btn-sm']) ?>
        </div>
    </div>

    <div class="in-table-wrap">
        <?php if (empty($porCliente)): ?>
            <div class="in-zero">
                <i class="fas fa-check-circle in-zero-ico"></i>
                Nenhuma inadimplência encontrada.
            </div>
        <?php else: ?>
        <table class="in-table" id="tabInadimplencia">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Títulos vencidos</th>
                    <th>Total em atraso</th>
                    <th>Maior atraso (dias)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($porCliente as $c): ?>
                <tr>
                    <td><?= h($c['nome']) ?></td>
                    <td><?= $c['qtd'] ?></td>
                    <td><strong style="color:#f85149;">R$ <?= number_format($c['total'], 2, ',', '.') ?></strong></td>
                    <td class="in-dias-alta"><?= $c['max_dias'] ?> dias</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<script>
$(function() {
    if ($.fn.DataTable) {
        $('#tabInadimplencia').DataTable({
            order: [[2, 'desc']],
            pageLength: 25,
            language: { url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Portuguese-Brasil.json' }
        });
    }
});
</script>
