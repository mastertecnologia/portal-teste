<?php
/**
 * Faturamento — Listagem de documentos
 */
use Cake\Routing\Router;
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Faturamento', ['controller' => 'Faturamento', 'action' => 'index']);

$statusLabel = [
    'rascunho'  => ['label' => 'Rascunho',  'class' => 'badge-secondary'],
    'pendente'  => ['label' => 'Pendente',  'class' => 'badge-warning'],
    'enviado'   => ['label' => 'Enviado',   'class' => 'badge-info'],
    'pago'      => ['label' => 'Pago',      'class' => 'badge-success'],
    'cancelado' => ['label' => 'Cancelado', 'class' => 'badge-danger'],
];

function nomeCliente($doc) {
    if (empty($doc->cliente)) return '—';
    return $doc->cliente->tipo == 1 ? ($doc->cliente->nome ?? '—') : ($doc->cliente->razaosocial ?? '—');
}
?>
<style>
.fat-root { font-family: 'DM Sans', sans-serif; }
.fat-topbar { display:flex; align-items:center; justify-content:space-between; padding:18px 24px 14px; border-bottom:1px solid rgba(255,255,255,.07); }
.fat-h1 { font-size:20px; font-weight:600; color:#e6edf3; margin:0; }
.fat-kpi-strip { display:flex; gap:0; border-bottom:1px solid rgba(255,255,255,.07); }
.fat-kpi { flex:1; padding:14px 20px; border-right:1px solid rgba(255,255,255,.07); }
.fat-kpi:last-child { border-right:none; }
.fat-kpi-label { font-size:10px; text-transform:uppercase; letter-spacing:.08em; color:#7d8590; font-weight:600; margin-bottom:4px; }
.fat-kpi-val { font-size:22px; font-weight:700; color:#e6edf3; }
.fat-kpi-val.teal { color:#5cdbc0; }
.fat-kpi-val.orange { color:#ff8833; }
.fat-kpi-val.green { color:#3fb950; }
.fat-kpi-val.red { color:#f85149; }
.fat-filters { display:flex; gap:10px; padding:14px 20px; align-items:center; border-bottom:1px solid rgba(255,255,255,.07); flex-wrap:wrap; }
.fat-filters select, .fat-filters input {
    background:#161b22; border:1px solid rgba(255,255,255,.10); border-radius:7px;
    color:#c9d1d9; padding:6px 10px; font-size:12.5px; height:34px;
}
.fat-table-wrap { padding:20px 24px; }
table.fat-table { width:100%; border-collapse:collapse; font-size:13px; }
.fat-table th { background:#161b22; color:#7d8590; font-size:11px; text-transform:uppercase; letter-spacing:.06em; font-weight:600; padding:8px 12px; border-bottom:1px solid rgba(255,255,255,.07); text-align:left; }
.fat-table td { padding:10px 12px; border-bottom:1px solid rgba(255,255,255,.04); color:#c9d1d9; vertical-align:middle; }
.fat-table tr:hover td { background:rgba(255,255,255,.03); }
.fat-badge { display:inline-block; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:600; }
.fat-badge.badge-secondary { background:rgba(255,255,255,.10); color:#9ca3af; }
.fat-badge.badge-warning { background:rgba(255,204,0,.15); color:#ffc107; }
.fat-badge.badge-info { background:rgba(51,204,255,.13); color:#33ccff; }
.fat-badge.badge-success { background:rgba(63,185,80,.13); color:#3fb950; }
.fat-badge.badge-danger { background:rgba(248,81,73,.13); color:#f85149; }
.fat-empty { text-align:center; padding:48px; color:#484f58; }
/* Modo claro */
body.pgm-theme-light .fat-topbar, body.pgm-theme-light .fat-kpi-strip,
body.pgm-theme-light .fat-filters, body.pgm-theme-light .fat-table-wrap { border-color:#e1e4e8; }
body.pgm-theme-light .fat-h1 { color:#1a1f2e; }
body.pgm-theme-light .fat-kpi-label { color:#6b7280; }
body.pgm-theme-light .fat-kpi-val { color:#1a1f2e; }
body.pgm-theme-light .fat-kpi { border-color:#e1e4e8; }
body.pgm-theme-light .fat-table th { background:#f3f4f6; color:#6b7280; border-color:#e5e7eb; }
body.pgm-theme-light .fat-table td { color:#374151; border-color:#f3f4f6; }
body.pgm-theme-light .fat-table tr:hover td { background:rgba(0,168,118,.04); }
body.pgm-theme-light .fat-filters select, body.pgm-theme-light .fat-filters input { background:#fff; border-color:#d0d7de; color:#1a1f2e; }
body.pgm-theme-light .fat-empty { color:#9ca3af; }
</style>

<div class="fat-root">
    <!-- Topbar -->
    <div class="fat-topbar">
        <div>
            <div class="fat-h1"><i class="fas fa-file-alt" style="color:#5cdbc0;margin-right:8px"></i>Faturamento</div>
        </div>
        <div>
            <?= $this->Html->link(
                '<i class="fas fa-plus"></i> Novo Documento',
                ['action' => 'add'],
                ['class' => 'btn btn-pgm btn-pgm-salvar btn-sm', 'escape' => false, 'target' => '_blank']
            ) ?>
        </div>
    </div>

    <!-- KPIs -->
    <div class="fat-kpi-strip">
        <div class="fat-kpi">
            <div class="fat-kpi-label">Rascunho</div>
            <div class="fat-kpi-val"><?= $kpi['rascunho'] ?></div>
        </div>
        <div class="fat-kpi">
            <div class="fat-kpi-label">Pendente</div>
            <div class="fat-kpi-val orange"><?= $kpi['pendente'] ?></div>
        </div>
        <div class="fat-kpi">
            <div class="fat-kpi-label">Enviado</div>
            <div class="fat-kpi-val teal"><?= $kpi['enviado'] ?></div>
        </div>
        <div class="fat-kpi">
            <div class="fat-kpi-label">Pago</div>
            <div class="fat-kpi-val green"><?= $kpi['pago'] ?></div>
        </div>
        <div class="fat-kpi">
            <div class="fat-kpi-label">Cancelado</div>
            <div class="fat-kpi-val red"><?= $kpi['cancelado'] ?></div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="fat-filters">
        <?= $this->Form->create(null, ['type' => 'get', 'style' => 'display:contents']) ?>
        <select name="status" onchange="this.form.submit()">
            <option value="">Todos os status</option>
            <?php foreach (['rascunho','pendente','enviado','pago','cancelado'] as $s): ?>
            <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="cliente" onchange="this.form.submit()">
            <option value="0">Todos os clientes</option>
            <?php foreach ($clientes as $cli): ?>
            <option value="<?= $cli->id ?>" <?= (string)$cliente === (string)$cli->id ? 'selected' : '' ?>>
                <?= h($cli->tipo == 1 ? $cli->nome : $cli->razaosocial) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <?= $this->Form->end() ?>
    </div>

    <!-- Tabela -->
    <div class="fat-table-wrap">
        <?php if (empty($documentos)): ?>
            <div class="fat-empty">
                <i class="fas fa-file-alt" style="font-size:36px;margin-bottom:12px;display:block;opacity:.3"></i>
                Nenhum documento encontrado.
            </div>
        <?php else: ?>
        <table class="fat-table" id="tabFaturamento">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Número</th>
                    <th>Cliente</th>
                    <th>Tipo</th>
                    <th>Emissão</th>
                    <th>Vencimento</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($documentos as $d): ?>
                <tr>
                    <td><small style="color:#7d8590">#<?= $d->id ?></small></td>
                    <td><strong><?= h($d->numero ?? '—') ?></strong></td>
                    <td><?= h(nomeCliente($d)) ?></td>
                    <td><small><?= ucfirst(h($d->tipo)) ?></small></td>
                    <td><?= $d->data_emissao ? $d->data_emissao->format('d/m/Y') : '—' ?></td>
                    <td><?= $d->data_vencimento ? $d->data_vencimento->format('d/m/Y') : '—' ?></td>
                    <td><strong>R$ <?= number_format($d->valor_total, 2, ',', '.') ?></strong></td>
                    <td>
                        <?php $st = $statusLabel[$d->status] ?? ['label' => $d->status, 'class' => 'badge-secondary']; ?>
                        <span class="fat-badge <?= $st['class'] ?>"><?= $st['label'] ?></span>
                    </td>
                    <td>
                        <?= $this->Html->link('<i class="fas fa-eye"></i>', ['action' => 'view', $d->id], ['class' => 'btn btn-pgm btn-pgm-situacao btn-xs', 'escape' => false, 'title' => 'Visualizar']) ?>
                        <?php if (in_array($d->status, ['rascunho', 'pendente'])): ?>
                        <?= $this->Html->link('<i class="fas fa-edit"></i>', ['action' => 'edit', $d->id], ['class' => 'btn btn-pgm btn-pgm-salvar btn-xs', 'escape' => false, 'title' => 'Editar']) ?>
                        <?php endif; ?>
                    </td>
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
        $('#tabFaturamento').DataTable({
            order: [[0, 'desc']],
            pageLength: <?= $pagelength ?? 25 ?>,
            language: { url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Portuguese-Brasil.json' }
        });
    }
});
</script>
