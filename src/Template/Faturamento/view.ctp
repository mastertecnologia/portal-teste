<?php
/**
 * Faturamento — Visualizar documento
 */
use Cake\Routing\Router;
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Faturamento', ['controller' => 'Faturamento', 'action' => 'index']);
$this->Breadcrumbs->add($doc->numero ?? '#' . $doc->id);

$statusLabel = [
    'rascunho'  => ['label' => 'Rascunho',  'color' => '#9ca3af'],
    'pendente'  => ['label' => 'Pendente',  'color' => '#ffc107'],
    'enviado'   => ['label' => 'Enviado',   'color' => '#33ccff'],
    'pago'      => ['label' => 'Pago',      'color' => '#3fb950'],
    'cancelado' => ['label' => 'Cancelado', 'color' => '#f85149'],
];
$st = $statusLabel[$doc->status] ?? ['label' => $doc->status, 'color' => '#9ca3af'];

$nomeCliente = '';
if (!empty($doc->cliente)) {
    $nomeCliente = ($doc->cliente->tipo == 1) ? ($doc->cliente->nome ?? '') : ($doc->cliente->razaosocial ?? '');
}
?>
<style>
.fatview-root { font-family:'DM Sans',sans-serif; max-width:900px; margin:0 auto; }
.fatview-header { display:flex; align-items:flex-start; justify-content:space-between; padding:20px 24px 16px; border-bottom:1px solid rgba(255,255,255,.07); gap:16px; flex-wrap:wrap; }
.fatview-num { font-size:22px; font-weight:700; color:#e6edf3; }
.fatview-sub { font-size:12px; color:#7d8590; margin-top:3px; }
.fatview-badge { display:inline-block; padding:4px 14px; border-radius:20px; font-size:12px; font-weight:700; background:rgba(255,255,255,.07); }
.fatview-actions { display:flex; gap:8px; flex-wrap:wrap; }
.fatview-card { background:#161b22; border:1px solid rgba(255,255,255,.07); border-radius:10px; margin:16px 24px; padding:18px 20px; }
.fatview-card-title { font-size:11px; text-transform:uppercase; letter-spacing:.08em; color:#7d8590; font-weight:600; margin-bottom:12px; }
.fatview-row { display:flex; gap:24px; flex-wrap:wrap; }
.fatview-field { flex:1; min-width:140px; }
.fatview-field label { font-size:10.5px; color:#7d8590; display:block; margin-bottom:2px; font-weight:600; text-transform:uppercase; letter-spacing:.05em; }
.fatview-field span { font-size:14px; color:#e6edf3; font-weight:500; }
.fatview-items table { width:100%; border-collapse:collapse; font-size:13px; margin-top:8px; }
.fatview-items th { color:#7d8590; font-size:11px; text-transform:uppercase; letter-spacing:.05em; font-weight:600; padding:6px 10px; border-bottom:1px solid rgba(255,255,255,.07); text-align:left; }
.fatview-items td { padding:9px 10px; border-bottom:1px solid rgba(255,255,255,.04); color:#c9d1d9; }
.fatview-total { text-align:right; padding:12px 0 0; }
.fatview-total-val { font-size:20px; font-weight:700; color:#5cdbc0; }
.fatview-financ { }
.fatview-financ td { font-size:12.5px; }
/* Modo claro */
body.pgm-theme-light .fatview-num { color:#1a1f2e; }
body.pgm-theme-light .fatview-sub { color:#6b7280; }
body.pgm-theme-light .fatview-card { background:#fff; border-color:#e1e4e8; }
body.pgm-theme-light .fatview-card-title { color:#6b7280; }
body.pgm-theme-light .fatview-field label { color:#6b7280; }
body.pgm-theme-light .fatview-field span { color:#1a1f2e; }
body.pgm-theme-light .fatview-items th { color:#6b7280; border-color:#e5e7eb; }
body.pgm-theme-light .fatview-items td { color:#374151; border-color:#f3f4f6; }
body.pgm-theme-light .fatview-header { border-color:#e1e4e8; }
body.pgm-theme-light .fatview-total-val { color:#00a876; }
</style>

<div class="fatview-root">
    <!-- Header -->
    <div class="fatview-header">
        <div>
            <div class="fatview-num">
                <?= h($doc->numero ?? 'Documento #' . $doc->id) ?>
            </div>
            <div class="fatview-sub">
                <?= h($nomeCliente) ?> &middot;
                <?= $doc->data_emissao ? $doc->data_emissao->format('d/m/Y') : '—' ?>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <span class="fatview-badge" style="color:<?= $st['color'] ?>;border-color:<?= $st['color'] ?>20;background:<?= $st['color'] ?>15">
                <?= $st['label'] ?>
            </span>
            <div class="fatview-actions">
                <?= $this->Html->link('<i class="fas fa-arrow-left"></i> Voltar', ['action' => 'index'], ['class' => 'btn btn-default btn-sm', 'escape' => false]) ?>
                <?php if (in_array($doc->status, ['rascunho', 'pendente'])): ?>
                <?= $this->Html->link('<i class="fas fa-edit"></i> Editar', ['action' => 'edit', $doc->id], ['class' => 'btn btn-pgm btn-pgm-salvar btn-sm', 'escape' => false]) ?>
                <?php endif; ?>
                <button type="button" class="btn btn-pgm btn-pgm-situacao btn-sm" data-toggle="modal" data-target="#modalStatus">
                    <i class="fas fa-exchange-alt"></i> Alterar Status
                </button>
            </div>
        </div>
    </div>

    <!-- Dados gerais -->
    <div class="fatview-card">
        <div class="fatview-card-title">Dados do Documento</div>
        <div class="fatview-row">
            <div class="fatview-field">
                <label>Número</label>
                <span><?= h($doc->numero ?? '—') ?></span>
            </div>
            <div class="fatview-field">
                <label>Tipo</label>
                <span><?= ucfirst(h($doc->tipo ?? '—')) ?></span>
            </div>
            <div class="fatview-field">
                <label>Status</label>
                <span style="color:<?= $st['color'] ?>"><?= $st['label'] ?></span>
            </div>
            <div class="fatview-field">
                <label>Emissão</label>
                <span><?= $doc->data_emissao ? $doc->data_emissao->format('d/m/Y') : '—' ?></span>
            </div>
            <div class="fatview-field">
                <label>Vencimento</label>
                <span><?= $doc->data_vencimento ? $doc->data_vencimento->format('d/m/Y') : '—' ?></span>
            </div>
        </div>
    </div>

    <!-- Cliente -->
    <div class="fatview-card">
        <div class="fatview-card-title">Cliente</div>
        <div class="fatview-row">
            <div class="fatview-field">
                <label>Nome / Razão Social</label>
                <span><?= h($nomeCliente ?: '—') ?></span>
            </div>
            <?php if (!empty($doc->idordem)): ?>
            <div class="fatview-field">
                <label>OS de Origem</label>
                <span>
                    <?= $this->Html->link('#' . $doc->idordem, ['controller' => 'Ordensservico', 'action' => 'view', $doc->idordem]) ?>
                </span>
            </div>
            <?php endif; ?>
            <?php if (!empty($doc->descricao)): ?>
            <div class="fatview-field" style="flex-basis:100%">
                <label>Descrição</label>
                <span><?= h($doc->descricao) ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Itens -->
    <?php if (!empty($doc->faturamento_itens)): ?>
    <div class="fatview-card fatview-items">
        <div class="fatview-card-title">Itens / Serviços</div>
        <table>
            <thead>
                <tr>
                    <th>Descrição</th>
                    <th style="text-align:right">Qtd</th>
                    <th style="text-align:right">Vlr Unit.</th>
                    <th style="text-align:right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($doc->faturamento_itens as $item): ?>
                <tr>
                    <td><?= h($item->descricao) ?></td>
                    <td style="text-align:right"><?= number_format($item->quantidade, 2, ',', '.') ?></td>
                    <td style="text-align:right">R$ <?= number_format($item->valor_unitario, 2, ',', '.') ?></td>
                    <td style="text-align:right"><strong>R$ <?= number_format($item->valor_total, 2, ',', '.') ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="fatview-total">
            <?php if ($doc->valor_desconto > 0): ?>
            <div style="font-size:12px;color:#7d8590;margin-bottom:4px">
                Desconto: R$ <?= number_format($doc->valor_desconto, 2, ',', '.') ?>
            </div>
            <?php endif; ?>
            <div class="fatview-total-val">Total: R$ <?= number_format($doc->valor_total, 2, ',', '.') ?></div>
        </div>
    </div>
    <?php else: ?>
    <div class="fatview-card">
        <div class="fatview-card-title">Valor</div>
        <div class="fatview-total">
            <div class="fatview-total-val">R$ <?= number_format($doc->valor_total, 2, ',', '.') ?></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Lançamentos financeiros vinculados -->
    <?php if (!empty($doc->financeiro_lancamentos)): ?>
    <div class="fatview-card fatview-financ">
        <div class="fatview-card-title">Lançamentos Financeiros</div>
        <table class="table table-sm" style="margin:0">
            <thead>
                <tr>
                    <th>Descrição</th>
                    <th>Valor</th>
                    <th>Vencimento</th>
                    <th>Recebimento</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($doc->financeiro_lancamentos as $l): ?>
                <tr>
                    <td><?= h($l->descricao) ?></td>
                    <td>R$ <?= number_format($l->valor, 2, ',', '.') ?></td>
                    <td><?= $l->data_vencimento ? $l->data_vencimento->format('d/m/Y') : '—' ?></td>
                    <td><?= $l->data_recebimento ? $l->data_recebimento->format('d/m/Y') : '—' ?></td>
                    <td><span class="badge badge-<?= $l->status === 'recebido' ? 'success' : ($l->status === 'aberto' ? 'warning' : 'secondary') ?>"><?= ucfirst($l->status) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Modal alterar status -->
<div class="modal fade" id="modalStatus" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Alterar Status</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <select id="selNovoStatus" class="form-control">
                    <?php foreach (['rascunho','pendente','enviado','pago','cancelado'] as $s): ?>
                    <option value="<?= $s ?>" <?= $doc->status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-pgm btn-pgm-salvar btn-sm" id="btnSalvarStatus">Salvar</button>
            </div>
        </div>
    </div>
</div>

<script>
$('#btnSalvarStatus').on('click', function() {
    var status = $('#selNovoStatus').val();
    $.ajax({
        type: 'POST',
        url: "<?= Router::url(['controller' => 'Faturamento', 'action' => 'alterarStatus', $doc->id]) ?>",
        data: { status: status },
        success: function(r) {
            if (r.ok) { location.reload(); }
            else { bootbox.alert('Erro: ' + (r.msg || 'Tente novamente.')); }
        },
        error: function() { bootbox.alert('Erro ao alterar status.'); }
    });
});
</script>
