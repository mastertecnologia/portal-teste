<?php
/**
 * Financeiro > Bancos
 * Tela: Criação de Remessa Bancária
 *
 * Variáveis esperadas:
 * - array $bancos
 * - int $bancoId
 * - array $lancamentos
 * - float $total
 */

use Cake\Routing\Router;

$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Financeiro', ['controller' => 'Financeiro', 'action' => 'index']);
$this->Breadcrumbs->add('Bancos', ['controller' => 'FinanceiroBancos', 'action' => 'index']);
$this->Breadcrumbs->add('Criação de Remessa Bancária');

$bancos = $bancos ?? [];
$bancoId = (int)($bancoId ?? 0);
$lancamentos = $lancamentos ?? [];
$total = (float)($total ?? 0);

$qtdTitulos = count($lancamentos);
?>
<style>
.fb-rem-root { font-family:'DM Sans',sans-serif; }
.fb-rem-topbar {
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:18px 24px 14px;
    border-bottom:1px solid rgba(255,255,255,.07);
    gap:12px;
    flex-wrap:wrap;
}
.fb-rem-title {
    font-size:20px;
    font-weight:600;
    color:#e6edf3;
    margin:0;
}
.fb-rem-title i {
    color:#5cdbc0;
    margin-right:8px;
}
.fb-rem-actions {
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}
.fb-rem-filters {
    display:flex;
    gap:12px;
    align-items:end;
    flex-wrap:wrap;
    padding:16px 24px 0;
}
.fb-rem-filter-group {
    min-width:260px;
}
.fb-rem-filter-group label {
    display:block;
    font-size:11px;
    color:#7d8590;
    text-transform:uppercase;
    letter-spacing:.05em;
    font-weight:600;
    margin-bottom:4px;
}
.fb-rem-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:14px;
    padding:16px 24px 0;
}
.fb-rem-kpi {
    background:#161b22;
    border:1px solid rgba(255,255,255,.07);
    border-radius:10px;
    padding:16px 18px;
}
.fb-rem-kpi-label {
    color:#7d8590;
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:.05em;
    font-weight:600;
    margin-bottom:6px;
}
.fb-rem-kpi-value {
    color:#e6edf3;
    font-size:24px;
    font-weight:700;
    line-height:1.1;
}
.fb-rem-card {
    background:#161b22;
    border:1px solid rgba(255,255,255,.07);
    border-radius:10px;
    padding:18px 20px;
    margin:16px 24px 24px;
}
.fb-rem-card-title {
    color:#e6edf3;
    font-size:15px;
    font-weight:600;
    margin:0 0 6px;
}
.fb-rem-card-sub {
    color:#7d8590;
    font-size:12.5px;
    margin-bottom:14px;
}
.fb-rem-table-wrap {
    overflow:auto;
}
.fb-rem-table {
    width:100%;
    border-collapse:collapse;
    font-size:12.5px;
    min-width:980px;
}
.fb-rem-table th {
    color:#7d8590;
    font-size:10.5px;
    text-transform:uppercase;
    letter-spacing:.05em;
    font-weight:600;
    padding:8px 10px;
    border-bottom:1px solid rgba(255,255,255,.07);
    text-align:left;
    white-space:nowrap;
}
.fb-rem-table td {
    padding:10px;
    border-bottom:1px solid rgba(255,255,255,.04);
    color:#c9d1d9;
    vertical-align:middle;
}
.fb-rem-table tr:hover td {
    background:rgba(255,255,255,.02);
}
.fb-rem-table tfoot td {
    font-weight:700;
    color:#5cdbc0;
    border-top:1px solid rgba(92,219,192,.18);
    background:rgba(92,219,192,.05);
}
.fb-rem-code {
    display:inline-block;
    padding:2px 7px;
    border-radius:6px;
    background:rgba(92,219,192,.10);
    color:#5cdbc0;
    font-weight:700;
    font-size:12px;
}
.fb-rem-badge {
    display:inline-block;
    padding:3px 8px;
    border-radius:999px;
    font-size:11px;
    font-weight:700;
}
.fb-rem-badge--open {
    background:rgba(255,193,7,.15);
    color:#ffc107;
}
.fb-rem-badge--ok {
    background:rgba(63,185,80,.15);
    color:#3fb950;
}
.fb-rem-money {
    white-space:nowrap;
    font-weight:600;
}
.fb-rem-muted {
    color:#7d8590;
    font-size:11px;
}
.fb-rem-empty {
    text-align:center;
    padding:36px 20px;
    color:#7d8590;
}
.fb-rem-empty i {
    display:block;
    font-size:32px;
    margin-bottom:10px;
    opacity:.35;
}
.fb-rem-note {
    margin-top:14px;
    color:#8b949e;
    font-size:12px;
    line-height:1.6;
}
</style>

<div class="fb-rem-root">
    <div class="fb-rem-topbar">
        <h1 class="fb-rem-title"><i class="fas fa-file-export"></i>Criação de Remessa Bancária</h1>

        <div class="fb-rem-actions">
            <?= $this->Html->link(
                '<i class="fas fa-layer-group"></i> Multiempresas',
                ['controller' => 'FinanceiroBancos', 'action' => 'remessaMultiempresas'],
                ['class' => 'btn btn-pgm btn-pgm-situacao btn-sm', 'escape' => false]
            ) ?>
            <?= $this->Html->link(
                '<i class="fas fa-chart-bar"></i> Relatórios bancários',
                ['controller' => 'FinanceiroBancos', 'action' => 'relatorios'],
                ['class' => 'btn btn-default btn-sm', 'escape' => false]
            ) ?>
            <?= $this->Html->link(
                '<i class="fas fa-arrow-left"></i> Bancos',
                ['controller' => 'FinanceiroBancos', 'action' => 'index'],
                ['class' => 'btn btn-default btn-sm', 'escape' => false]
            ) ?>
        </div>
    </div>

    <?= $this->Form->create(null, ['type' => 'get', 'class' => 'fb-rem-filters']) ?>
        <div class="fb-rem-filter-group">
            <label>Banco</label>
            <?= $this->Form->select('banco_id', $bancos, [
                'empty' => '— Selecione um banco —',
                'class' => 'form-control',
                'value' => $bancoId,
            ]) ?>
        </div>

        <div>
            <?= $this->Form->button('<i class="fas fa-search"></i> Consultar títulos', [
                'type' => 'submit',
                'class' => 'btn btn-pgm btn-pgm-salvar btn-sm',
                'escape' => false,
            ]) ?>
        </div>

        <div>
            <?= $this->Html->link(
                'Limpar',
                ['controller' => 'FinanceiroBancos', 'action' => 'remessa'],
                ['class' => 'btn btn-default btn-sm']
            ) ?>
        </div>
    <?= $this->Form->end() ?>

    <div class="fb-rem-grid">
        <div class="fb-rem-kpi">
            <div class="fb-rem-kpi-label">Banco selecionado</div>
            <div class="fb-rem-kpi-value"><?= $bancoId > 0 ? h($bancos[$bancoId] ?? '—') : '—' ?></div>
        </div>

        <div class="fb-rem-kpi">
            <div class="fb-rem-kpi-label">Qtd. títulos</div>
            <div class="fb-rem-kpi-value"><?= number_format($qtdTitulos, 0, ',', '.') ?></div>
        </div>

        <div class="fb-rem-kpi">
            <div class="fb-rem-kpi-label">Total da remessa</div>
            <div class="fb-rem-kpi-value">R$ <?= number_format($total, 2, ',', '.') ?></div>
        </div>
    </div>

    <div class="fb-rem-card">
        <h2 class="fb-rem-card-title">Títulos em aberto vinculados ao banco</h2>
        <div class="fb-rem-card-sub">
            Esta visão organiza os lançamentos de contas a receber em aberto para apoiar a geração operacional da remessa bancária.
        </div>

        <div class="fb-rem-table-wrap">
            <table class="fb-rem-table" id="tbRemessaBancaria">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Cliente</th>
                        <th>Descrição</th>
                        <th>Vencimento</th>
                        <th>Valor</th>
                        <th>Status</th>
                        <th>Observação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($lancamentos)): ?>
                        <tr>
                            <td colspan="7" class="fb-rem-empty">
                                <i class="fas fa-folder-open"></i>
                                <?php if ($bancoId > 0): ?>
                                    Nenhum título em aberto encontrado para o banco selecionado.
                                <?php else: ?>
                                    Selecione um banco para visualizar os títulos da remessa.
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($lancamentos as $l): ?>
                            <?php
                            $nomeCli = '—';
                            if (!empty($l->cliente)) {
                                $nomeCli = ((int)($l->cliente->tipo ?? 0) === 1)
                                    ? ($l->cliente->nome ?? '—')
                                    : ($l->cliente->razaosocial ?? '—');
                            }
                            ?>
                            <tr>
                                <td><span class="fb-rem-code">#<?= (int)$l->id ?></span></td>
                                <td><?= h($nomeCli) ?></td>
                                <td>
                                    <?= h($l->descricao ?? '—') ?>
                                    <?php if (!empty($l->financeiro_banco_id)): ?>
                                        <div class="fb-rem-muted">Banco vinculado ao financeiro</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($l->data_vencimento)): ?>
                                        <?= h($l->data_vencimento->format('d/m/Y')) ?>
                                    <?php else: ?>
                                        <span class="fb-rem-muted">Sem vencimento</span>
                                    <?php endif; ?>
                                </td>
                                <td class="fb-rem-money">R$ <?= number_format((float)$l->valor, 2, ',', '.') ?></td>
                                <td>
                                    <span class="fb-rem-badge fb-rem-badge--open">
                                        <?= h(ucfirst((string)($l->status ?? 'aberto'))) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($l->observacoes)): ?>
                                        <?= h($l->observacoes) ?>
                                    <?php else: ?>
                                        <span class="fb-rem-muted">Pronto para compor remessa</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($lancamentos)): ?>
                    <tfoot>
                        <tr>
                            <td colspan="4">Total consolidado</td>
                            <td>R$ <?= number_format($total, 2, ',', '.') ?></td>
                            <td colspan="2"><?= number_format($qtdTitulos, 0, ',', '.') ?> título(s)</td>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>

        <div class="fb-rem-note">
            <strong>Observação:</strong> esta tela já está alinhada ao módulo financeiro para que a seleção por banco organize a futura geração de arquivos de remessa bancária.
        </div>
    </div>
</div>

<script>
$(function () {
    if ($.fn.DataTable && $('#tbRemessaBancaria').length) {
        $('#tbRemessaBancaria').DataTable({
            order: [[3, 'asc']],
            pageLength: <?= (int)($pagelength ?? 25) ?>,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Portuguese-Brasil.json'
            }
        });
    }
});
</script>
