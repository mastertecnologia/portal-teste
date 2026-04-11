<?php
/**
 * Financeiro — Contas a Pagar
 */
use Cake\Routing\Router;
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Financeiro', ['controller' => 'Financeiro', 'action' => 'index']);
$this->Breadcrumbs->add('Contas a Pagar');

$statusMap = [
    'aberto'    => ['label' => 'Aberto',    'class' => 'badge-warning'],
    'pago'      => ['label' => 'Pago',      'class' => 'badge-success'],
    'vencido'   => ['label' => 'Vencido',   'class' => 'badge-danger'],
    'cancelado' => ['label' => 'Cancelado', 'class' => 'badge-secondary'],
];
?>
<style>
.cp-root { font-family:'DM Sans',sans-serif; }
.cp-topbar { display:flex; align-items:center; justify-content:space-between; padding:16px 24px 12px; border-bottom:1px solid rgba(255,255,255,.07); flex-wrap:wrap; gap:10px; }
.cp-h1 { font-size:18px; font-weight:600; color:#e6edf3; }
.cp-filters { display:flex; gap:10px; padding:12px 20px; align-items:center; border-bottom:1px solid rgba(255,255,255,.07); flex-wrap:wrap; }
.cp-filters select { background:#161b22; border:1px solid rgba(255,255,255,.10); border-radius:7px; color:#c9d1d9; padding:6px 10px; font-size:12.5px; height:34px; }
.cp-table-wrap { padding:20px 24px; }
table.cp-table { width:100%; border-collapse:collapse; font-size:13px; }
.cp-table th { background:#161b22; color:#7d8590; font-size:11px; text-transform:uppercase; letter-spacing:.05em; font-weight:600; padding:8px 12px; border-bottom:1px solid rgba(255,255,255,.07); text-align:left; }
.cp-table td { padding:10px 12px; border-bottom:1px solid rgba(255,255,255,.04); color:#c9d1d9; vertical-align:middle; }
.cp-table tr:hover td { background:rgba(255,255,255,.03); }
.cp-badge { display:inline-block; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:600; }
.cp-badge.badge-warning  { background:rgba(255,193,7,.15); color:#ffc107; }
.cp-badge.badge-success  { background:rgba(63,185,80,.13); color:#3fb950; }
.cp-badge.badge-danger   { background:rgba(248,81,73,.13); color:#f85149; }
.cp-badge.badge-secondary{ background:rgba(255,255,255,.08); color:#9ca3af; }
.cp-total-row { font-size:13.5px; font-weight:700; background:rgba(29,158,117,.07) !important; }
.cp-total-row td { color:#5cdbc0 !important; border-top:1px solid rgba(29,158,117,.2); }
.cp-h1-ico { color:#5cdbc0; margin-right:8px; }
.cp-form-filters { display:contents; }
.cp-zero { text-align:center; padding:48px; color:#484f58; }
.cp-zero-ico { font-size:32px; display:block; margin-bottom:10px; opacity:.3; }
.cp-id-muted { color:#7d8590; }
.cp-td-ellipsis { max-width:130px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.cp-td-vencido { color:#f85149; font-weight:600; }
.cp-total-label { text-align:right; padding-right:12px; }
.cp-conta-muted { font-size:11px; color:#7d8590; }
</style>

<div class="cp-root">
    <div class="cp-topbar">
        <div class="cp-h1"><i class="fas fa-file-invoice-dollar cp-h1-ico"></i>Contas a Pagar</div>
        <div>
            <?= $this->Html->link('<i class="fas fa-plus"></i> Nova despesa', ['action' => 'addDespesa'], ['class' => 'btn btn-pgm btn-pgm-salvar btn-sm', 'escape' => false]) ?>
            <?= $this->Html->link('<i class="fas fa-arrow-left"></i> Dashboard', ['action' => 'index'], ['class' => 'btn btn-default btn-sm', 'escape' => false]) ?>
        </div>
    </div>

    <!-- Filtros -->
    <div class="cp-filters">
        <?= $this->Form->create(null, ['type' => 'get', 'class' => 'cp-form-filters']) ?>
        <select name="status" onchange="this.form.submit()">
            <option value="">Todos os status</option>
            <?php foreach (['aberto','pago','vencido','cancelado'] as $s): ?>
            <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="fornecedor" onchange="this.form.submit()">
            <option value="0">Todos os fornecedores</option>
            <?php foreach ($fornecedores as $fid => $fnome): ?>
            <option value="<?= $fid ?>" <?= (string)$fornecedor === (string)$fid ? 'selected' : '' ?>><?= h($fnome) ?></option>
            <?php endforeach; ?>
        </select>
        <?= $this->Form->end() ?>
    </div>

    <!-- Tabela -->
    <div class="cp-table-wrap">
        <?php if (empty($lancamentos)): ?>
            <div class="cp-zero">
                <i class="fas fa-file-invoice-dollar cp-zero-ico"></i>
                Nenhuma despesa encontrada.
            </div>
        <?php else: ?>
        <?php
            $total = array_sum(array_column($lancamentos, 'valor'));
        ?>
        <table class="cp-table" id="tabContasPagar">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Descrição</th>
                    <th>Fornecedor</th>
                    <th>Plano de contas</th>
                    <th>Centro de custo</th>
                    <th>Valor</th>
                    <th>Vencimento</th>
                    <th>Pagamento</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lancamentos as $l): ?>
                <?php
                    $nomeForn = '—';
                    if (!empty($l->cliente)) {
                        $nomeForn = ($l->cliente->tipo == 1) ? ($l->cliente->nome ?? '—') : ($l->cliente->razaosocial ?? '—');
                    }
                    $stMap = $statusMap[$l->status] ?? ['label' => $l->status, 'class' => 'badge-secondary'];
                    $hoje  = date('Y-m-d');
                    $vencido = $l->status === 'aberto' && $l->data_vencimento && $l->data_vencimento->format('Y-m-d') < $hoje;
                ?>
                <tr>
                    <td><small class="cp-id-muted">#<?= $l->id ?></small></td>
                    <td><?= h($l->descricao) ?></td>
                    <td class="cp-td-ellipsis"><?= h($nomeForn) ?></td>
                    <td>
                        <?php if (!empty($l->financeiro_plano_conta)): ?>
                            <span class="cp-conta-muted"><?= h($l->financeiro_plano_conta->codigo) ?></span>
                            <?= h($l->financeiro_plano_conta->descricao) ?>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($l->financeiro_centros_custo)): ?>
                            <?= h($l->financeiro_centros_custo->codigo) ?> — <?= h($l->financeiro_centros_custo->descricao) ?>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td><strong>R$ <?= number_format($l->valor, 2, ',', '.') ?></strong></td>
                    <td<?= $vencido ? ' class="cp-td-vencido"' : '' ?>>
                        <?= $l->data_vencimento ? $l->data_vencimento->format('d/m/Y') : '—' ?>
                    </td>
                    <td><?= $l->data_recebimento ? $l->data_recebimento->format('d/m/Y') : '—' ?></td>
                    <td>
                        <?php if ($vencido): ?>
                            <span class="cp-badge badge-danger">Vencido</span>
                        <?php else: ?>
                            <span class="cp-badge <?= $stMap['class'] ?>"><?= $stMap['label'] ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="white-space:nowrap;">
                        <?= $this->Html->link('<i class="fas fa-eye"></i>', ['action' => 'fatura', $l->id], ['class' => 'btn btn-default btn-xs m-r-5', 'escape' => false, 'title' => 'Detalhe']) ?>
                        <?php if ($l->status === 'aberto'): ?>
                        <?= $this->Html->link('<i class="fas fa-edit"></i>', ['action' => 'editDespesa', $l->id], ['class' => 'btn btn-default btn-xs m-r-5', 'escape' => false, 'title' => 'Editar']) ?>
                        <button type="button" class="btn btn-pgm btn-pgm-salvar btn-xs btn-pagar"
                            data-id="<?= $l->id ?>" title="Registrar Pagamento">
                            <i class="fas fa-check"></i> Pagar
                        </button>
                        <button type="button" class="btn btn-danger btn-xs btn-cancelar-desp"
                            data-id="<?= $l->id ?>" title="Cancelar">
                            <i class="fas fa-ban"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="cp-total-row">
                    <td colspan="5" class="cp-total-label">TOTAL</td>
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
        $('#tabContasPagar').DataTable({
            order: [[6, 'asc']],
            pageLength: <?= $pagelength ?? 25 ?>,
            language: { url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Portuguese-Brasil.json' }
        });
    }

    $(document).on('click', '.btn-pagar', function() {
        var id = $(this).data('id');
        bootbox.confirm('Confirmar pagamento desta despesa?', function(r) {
            if (!r) return;
            $.ajax({
                type: 'POST',
                url: "<?= Router::url(['controller' => 'Financeiro', 'action' => 'registrarPagamento']) ?>/" + id,
                data: { data_pagamento: new Date().toISOString().slice(0, 10) },
                success: function(res) {
                    if (res.ok) location.reload();
                    else bootbox.alert('Erro: ' + (res.msg || 'Tente novamente.'));
                },
                error: function() { bootbox.alert('Erro ao registrar pagamento.'); }
            });
        });
    });

    $(document).on('click', '.btn-cancelar-desp', function() {
        var id = $(this).data('id');
        bootbox.confirm('Cancelar esta despesa? Esta ação não pode ser desfeita.', function(r) {
            if (!r) return;
            $.ajax({
                type: 'POST',
                url: "<?= Router::url(['controller' => 'Financeiro', 'action' => 'cancelarDespesa']) ?>/" + id,
                success: function(res) {
                    if (res.ok) location.reload();
                    else bootbox.alert('Erro: ' + (res.msg || 'Tente novamente.'));
                },
                error: function() { bootbox.alert('Erro ao cancelar despesa.'); }
            });
        });
    });
});
</script>
