<?php
use Cake\Routing\Router;
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Financeiro', ['controller' => 'Financeiro', 'action' => 'index']);
$this->Breadcrumbs->add('Conciliação Bancária');
?>
<style>
.cb-root { font-family:'DM Sans',sans-serif; }
.cb-topbar { display:flex; align-items:center; justify-content:space-between; padding:16px 24px 12px; border-bottom:1px solid rgba(255,255,255,.07); flex-wrap:wrap; gap:10px; }
.cb-h1 { font-size:18px; font-weight:600; color:#e6edf3; }
.cb-h1-ico { color:#5cdbc0; margin-right:8px; }
.cb-upload { padding:14px 24px; border-bottom:1px solid rgba(255,255,255,.07); }
.cb-upload-form { display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
.cb-filters { display:flex; gap:10px; padding:12px 20px; align-items:center; border-bottom:1px solid rgba(255,255,255,.07); }
.cb-filters select { background:#161b22; border:1px solid rgba(255,255,255,.10); border-radius:7px; color:#c9d1d9; padding:6px 10px; font-size:12.5px; height:34px; }
.cb-table-wrap { padding:20px 24px; }
table.cb-table { width:100%; border-collapse:collapse; font-size:13px; }
.cb-table th { background:#161b22; color:#7d8590; font-size:11px; text-transform:uppercase; letter-spacing:.05em; font-weight:600; padding:8px 12px; border-bottom:1px solid rgba(255,255,255,.07); text-align:left; }
.cb-table td { padding:10px 12px; border-bottom:1px solid rgba(255,255,255,.04); color:#c9d1d9; vertical-align:middle; }
.cb-table tr:hover td { background:rgba(255,255,255,.03); }
.cb-badge { display:inline-block; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:600; }
.cb-badge-ok { background:rgba(63,185,80,.13); color:#3fb950; }
.cb-badge-pend { background:rgba(255,193,7,.15); color:#ffc107; }
.cb-credito { color:#3fb950; }
.cb-debito { color:#f85149; }
.cb-zero { text-align:center; padding:48px; color:#484f58; }
.cb-zero-ico { font-size:32px; display:block; margin-bottom:10px; opacity:.3; }
</style>

<div class="cb-root">
    <div class="cb-topbar">
        <div class="cb-h1"><i class="fas fa-exchange-alt cb-h1-ico"></i>Conciliação Bancária</div>
        <div>
            <?= $this->Html->link('<i class="fas fa-arrow-left"></i> Dashboard', ['action' => 'index'], ['class' => 'btn btn-default btn-sm', 'escape' => false]) ?>
        </div>
    </div>

    <!-- Upload -->
    <div class="cb-upload">
        <?= $this->Form->create(null, ['url' => ['action' => 'importarExtrato'], 'type' => 'file', 'class' => 'cb-upload-form']) ?>
        <label style="color:#7d8590; font-size:12px; font-weight:600;">Importar extrato:</label>
        <?= $this->Form->file('arquivo', ['accept' => '.ofx,.csv', 'style' => 'color:#c9d1d9; font-size:12.5px;']) ?>
        <?= $this->Form->button('<i class="fas fa-upload"></i> Importar', ['class' => 'btn btn-pgm btn-pgm-salvar btn-sm', 'escape' => false]) ?>
        <?= $this->Form->end() ?>
    </div>

    <!-- Filtros -->
    <div class="cb-filters">
        <?= $this->Form->create(null, ['type' => 'get']) ?>
        <select name="filtro" onchange="this.form.submit()">
            <option value="pendentes" <?= $filtro === 'pendentes' ? 'selected' : '' ?>>Pendentes</option>
            <option value="conciliados" <?= $filtro === 'conciliados' ? 'selected' : '' ?>>Conciliados</option>
            <option value="todos" <?= $filtro === 'todos' ? 'selected' : '' ?>>Todos</option>
        </select>
        <?= $this->Form->end() ?>
    </div>

    <!-- Tabela -->
    <div class="cb-table-wrap">
        <?php if (empty($extratos)): ?>
            <div class="cb-zero">
                <i class="fas fa-exchange-alt cb-zero-ico"></i>
                Nenhuma transação encontrada. Importe um extrato OFX ou CSV.
            </div>
        <?php else: ?>
        <table class="cb-table" id="tabConciliacao">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Descrição</th>
                    <th>Valor</th>
                    <th>Tipo</th>
                    <th>Conta</th>
                    <th>Status</th>
                    <th>Lançamento vinculado</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($extratos as $e): ?>
                <tr>
                    <td><?= $e->data ? $e->data->format('d/m/Y') : '—' ?></td>
                    <td><?= h($e->descricao) ?></td>
                    <td class="<?= $e->tipo === 'credito' ? 'cb-credito' : 'cb-debito' ?>">
                        <strong>R$ <?= number_format((float)$e->valor, 2, ',', '.') ?></strong>
                    </td>
                    <td><?= $e->tipo === 'credito' ? 'Crédito' : 'Débito' ?></td>
                    <td><?= h($e->conta_bancaria ?? '—') ?></td>
                    <td>
                        <?php if ($e->conciliado): ?>
                            <span class="cb-badge cb-badge-ok">Conciliado</span>
                        <?php else: ?>
                            <span class="cb-badge cb-badge-pend">Pendente</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($e->financeiro_lancamento)): ?>
                            <?= $this->Html->link('#' . $e->financeiro_lancamento->id . ' — ' . h($e->financeiro_lancamento->descricao), ['action' => 'fatura', $e->financeiro_lancamento->id]) ?>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td>
                        <?php if (!$e->conciliado && !empty($lancNaoConciliados)): ?>
                        <select class="form-control input-sm sel-conciliar" data-extrato="<?= $e->id ?>" style="width:auto; display:inline-block; font-size:12px; background:#161b22; color:#c9d1d9; border:1px solid rgba(255,255,255,.1); border-radius:5px; padding:3px 6px;">
                            <option value="">Vincular...</option>
                            <?php foreach ($lancNaoConciliados as $lnc): ?>
                            <option value="<?= $lnc->id ?>">#<?= $lnc->id ?> — <?= h(mb_substr($lnc->descricao, 0, 40)) ?> (R$ <?= number_format((float)$lnc->valor, 2, ',', '.') ?>)</option>
                            <?php endforeach; ?>
                        </select>
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
        $('#tabConciliacao').DataTable({
            order: [[0, 'desc']],
            pageLength: 50,
            language: { url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Portuguese-Brasil.json' }
        });
    }

    $(document).on('change', '.sel-conciliar', function() {
        var idExtrato = $(this).data('extrato');
        var idLanc = $(this).val();
        if (!idLanc) return;

        bootbox.confirm('Conciliar esta transação com o lançamento selecionado?', function(r) {
            if (!r) return;
            $.ajax({
                type: 'POST',
                url: "<?= Router::url(['controller' => 'Financeiro', 'action' => 'conciliarExtrato']) ?>/" + idExtrato,
                data: { financeiro_lancamento_id: idLanc },
                success: function(res) {
                    if (res.ok) location.reload();
                    else bootbox.alert('Erro: ' + (res.msg || 'Tente novamente.'));
                },
                error: function() { bootbox.alert('Erro ao conciliar.'); }
            });
        });
    });
});
</script>
