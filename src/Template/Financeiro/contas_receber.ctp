<?php
/**
 * Financeiro — Contas a Receber
 */
use Cake\Routing\Router;
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Financeiro', ['controller' => 'Financeiro', 'action' => 'index']);
$this->Breadcrumbs->add('Contas a Receber');

$statusMap = [
    'aberto'    => ['label' => 'Aberto',    'class' => 'badge-warning'],
    'recebido'  => ['label' => 'Recebido',  'class' => 'badge-success'],
    'vencido'   => ['label' => 'Vencido',   'class' => 'badge-danger'],
    'cancelado' => ['label' => 'Cancelado', 'class' => 'badge-secondary'],
];
?>
<style>
.cr-root { font-family:'DM Sans',sans-serif; }
.cr-topbar { display:flex; align-items:center; justify-content:space-between; padding:16px 24px 12px; border-bottom:1px solid rgba(255,255,255,.07); flex-wrap:wrap; gap:10px; }
.cr-h1 { font-size:18px; font-weight:600; color:#e6edf3; }
.cr-filters { display:flex; gap:10px; padding:12px 20px; align-items:center; border-bottom:1px solid rgba(255,255,255,.07); flex-wrap:wrap; }
.cr-filters select { background:#161b22; border:1px solid rgba(255,255,255,.10); border-radius:7px; color:#c9d1d9; padding:6px 10px; font-size:12.5px; height:34px; }
.cr-table-wrap { padding:20px 24px; }
table.cr-table { width:100%; border-collapse:collapse; font-size:13px; }
.cr-table th { background:#161b22; color:#7d8590; font-size:11px; text-transform:uppercase; letter-spacing:.05em; font-weight:600; padding:8px 12px; border-bottom:1px solid rgba(255,255,255,.07); text-align:left; }
.cr-table td { padding:10px 12px; border-bottom:1px solid rgba(255,255,255,.04); color:#c9d1d9; vertical-align:middle; }
.cr-table tr:hover td { background:rgba(255,255,255,.03); }
.cr-badge { display:inline-block; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:600; }
.cr-badge.badge-warning  { background:rgba(255,193,7,.15); color:#ffc107; }
.cr-badge.badge-success  { background:rgba(63,185,80,.13); color:#3fb950; }
.cr-badge.badge-danger   { background:rgba(248,81,73,.13); color:#f85149; }
.cr-badge.badge-secondary{ background:rgba(255,255,255,.08); color:#9ca3af; }
.cr-total-row { font-size:13.5px; font-weight:700; background:rgba(29,158,117,.07) !important; }
.cr-total-row td { color:#5cdbc0 !important; border-top:1px solid rgba(29,158,117,.2); }
/* Modo claro */
body.pgm-theme-light .cr-h1 { color:#1a1f2e; }
body.pgm-theme-light .cr-topbar, body.pgm-theme-light .cr-filters { border-color:#e1e4e8; }
body.pgm-theme-light .cr-filters select { background:#fff; border-color:#d0d7de; color:#1a1f2e; }
body.pgm-theme-light .cr-table th { background:#f3f4f6; color:#6b7280; border-color:#e5e7eb; }
body.pgm-theme-light .cr-table td { color:#374151; border-color:#f3f4f6; }
body.pgm-theme-light .cr-table tr:hover td { background:rgba(0,168,118,.04); }
body.pgm-theme-light .cr-total-row { background:rgba(0,168,118,.06) !important; }
body.pgm-theme-light .cr-total-row td { color:#006d4a !important; }
</style>

<div class="cr-root">
    <div class="cr-topbar">
        <div class="cr-h1"><i class="fas fa-hand-holding-usd" style="color:#5cdbc0;margin-right:8px"></i>Contas a Receber</div>
        <div>
            <?= $this->Html->link('<i class="fas fa-arrow-left"></i> Dashboard', ['action' => 'index'], ['class' => 'btn btn-default btn-sm', 'escape' => false]) ?>
        </div>
    </div>

    <!-- Filtros -->
    <div class="cr-filters">
        <?= $this->Form->create(null, ['type' => 'get', 'style' => 'display:contents']) ?>
        <select name="status" onchange="this.form.submit()">
            <option value="">Todos os status</option>
            <?php foreach (['aberto','recebido','vencido','cancelado'] as $s): ?>
            <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="cliente" onchange="this.form.submit()">
            <option value="0">Todos os clientes</option>
            <?php foreach ($clientes as $cid => $cnome): ?>
            <option value="<?= $cid ?>" <?= (string)$cliente === (string)$cid ? 'selected' : '' ?>><?= h($cnome) ?></option>
            <?php endforeach; ?>
        </select>
        <?= $this->Form->end() ?>
    </div>

    <!-- Tabela -->
    <div class="cr-table-wrap">
        <?php if (empty($lancamentos)): ?>
            <div style="text-align:center;padding:48px;color:#484f58">
                <i class="fas fa-hand-holding-usd" style="font-size:32px;display:block;margin-bottom:10px;opacity:.3"></i>
                Nenhum lançamento encontrado.
            </div>
        <?php else: ?>
        <?php
            $total = array_sum(array_column($lancamentos, 'valor'));
        ?>
        <table class="cr-table" id="tabContasReceber">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Descrição</th>
                    <th>Cliente</th>
                    <th>Faturamento</th>
                    <th>Valor</th>
                    <th>Vencimento</th>
                    <th>Recebimento</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lancamentos as $l): ?>
                <?php
                    $nomeCli = '—';
                    if (!empty($l->cliente)) {
                        $nomeCli = ($l->cliente->tipo == 1) ? ($l->cliente->nome ?? '—') : ($l->cliente->razaosocial ?? '—');
                    }
                    $stMap = $statusMap[$l->status] ?? ['label' => $l->status, 'class' => 'badge-secondary'];
                    $hoje  = date('Y-m-d');
                    $vencido = $l->status === 'aberto' && $l->data_vencimento && $l->data_vencimento->format('Y-m-d') < $hoje;
                ?>
                <tr>
                    <td><small style="color:#7d8590">#<?= $l->id ?></small></td>
                    <td><?= h($l->descricao) ?></td>
                    <td style="max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= h($nomeCli) ?></td>
                    <td>
                        <?php if (!empty($l->faturamento)): ?>
                        <?= $this->Html->link(h($l->faturamento->numero ?? '#' . $l->faturamento->id), ['controller' => 'Faturamento', 'action' => 'view', $l->faturamento->id]) ?>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td><strong>R$ <?= number_format($l->valor, 2, ',', '.') ?></strong></td>
                    <td <?= $vencido ? 'style="color:#f85149;font-weight:600"' : '' ?>>
                        <?= $l->data_vencimento ? $l->data_vencimento->format('d/m/Y') : '—' ?>
                    </td>
                    <td><?= $l->data_recebimento ? $l->data_recebimento->format('d/m/Y') : '—' ?></td>
                    <td>
                        <?php if ($vencido): ?>
                            <span class="cr-badge badge-danger">Vencido</span>
                        <?php else: ?>
                            <span class="cr-badge <?= $stMap['class'] ?>"><?= $stMap['label'] ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= $this->Html->link('<i class="fas fa-eye"></i>', ['action' => 'fatura', $l->id], ['class' => 'btn btn-default btn-xs m-r-5', 'escape' => false, 'title' => 'Detalhe da fatura']) ?>
                        <?php if ($l->status === 'aberto'): ?>
                        <button type="button" class="btn btn-pgm btn-pgm-salvar btn-xs btn-receber"
                            data-id="<?= $l->id ?>" title="Registrar Recebimento">
                            <i class="fas fa-check"></i> Receber
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="cr-total-row">
                    <td colspan="4" style="text-align:right;padding-right:12px">TOTAL</td>
                    <td>R$ <?= number_format($total, 2, ',', '.') ?></td>
                    <td colspan="4"></td>
                </tr>
            </tfoot>
        </table>
        <?php endif; ?>
    </div>
</div>

<script>
$(function() {
    if ($.fn.DataTable) {
        $('#tabContasReceber').DataTable({
            order: [[5, 'asc']],
            pageLength: <?= $pagelength ?? 25 ?>,
            language: { url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Portuguese-Brasil.json' }
        });
    }

    $(document).on('click', '.btn-receber', function() {
        var id = $(this).data('id');
        bootbox.confirm('Confirmar recebimento deste lançamento?', function(r) {
            if (!r) return;
            $.ajax({
                type: 'POST',
                url: "<?= Router::url(['controller' => 'Financeiro', 'action' => 'registrarRecebimento']) ?>/" + id,
                data: { data_recebimento: new Date().toISOString().slice(0, 10) },
                success: function(res) {
                    if (res.ok) location.reload();
                    else bootbox.alert('Erro: ' + (res.msg || 'Tente novamente.'));
                },
                error: function() { bootbox.alert('Erro ao registrar recebimento.'); }
            });
        });
    });
});
</script>
