<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Financeiro', ['controller' => 'Financeiro', 'action' => 'index']);
$this->Breadcrumbs->add('Bancos', ['controller' => 'FinanceiroBancos', 'action' => 'index']);
$this->Breadcrumbs->add('Relação de Bancos');

$bancos = $bancos ?? [];
?>
<style>
.fb-root { font-family:'DM Sans',sans-serif; }
.fb-topbar { display:flex; align-items:center; justify-content:space-between; padding:18px 24px 14px; border-bottom:1px solid rgba(255,255,255,.07); gap:12px; flex-wrap:wrap; }
.fb-h1 { font-size:20px; font-weight:600; color:#e6edf3; margin:0; }
.fb-h1-ico { color:#5cdbc0; margin-right:8px; }
.fb-card { background:#161b22; border:1px solid rgba(255,255,255,.07); border-radius:10px; padding:18px 20px; margin:16px 24px; }
.fb-meta { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:14px; }
.fb-pill { display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:999px; background:rgba(92,219,192,.08); color:#9ee7d6; font-size:12px; font-weight:600; }
.fb-table-wrap { overflow:auto; }
.fb-table { width:100%; border-collapse:collapse; font-size:12.5px; min-width:1100px; }
.fb-table th { color:#7d8590; font-size:10.5px; text-transform:uppercase; letter-spacing:.05em; font-weight:600; padding:7px 8px; border-bottom:1px solid rgba(255,255,255,.07); text-align:left; white-space:nowrap; }
.fb-table td { padding:9px 8px; border-bottom:1px solid rgba(255,255,255,.04); color:#c9d1d9; vertical-align:top; }
.fb-table tr:hover td { background:rgba(255,255,255,.02); }
.fb-code { display:inline-block; padding:2px 7px; border-radius:6px; background:rgba(255,255,255,.06); color:#e6edf3; font-family:Consolas,Monaco,monospace; font-size:12px; }
.fb-muted { color:#7d8590; }
.fb-status { display:inline-flex; align-items:center; gap:6px; font-size:12px; font-weight:600; }
.fb-status-dot { width:8px; height:8px; border-radius:50%; display:inline-block; }
.fb-status--on { color:#3fb950; }
.fb-status--on .fb-status-dot { background:#3fb950; }
.fb-status--off { color:#f85149; }
.fb-status--off .fb-status-dot { background:#f85149; }
.fb-empty { text-align:center; color:#7d8590; padding:36px 20px; }
.fb-empty i { display:block; font-size:32px; margin-bottom:10px; opacity:.35; }
.fb-actions { display:flex; gap:8px; flex-wrap:wrap; }
.fb-small { font-size:11px; color:#8b949e; }
</style>

<div class="fb-root">
    <div class="fb-topbar">
        <h1 class="fb-h1"><i class="fas fa-university fb-h1-ico"></i>Relação de Bancos</h1>
        <div class="fb-actions">
            <?= $this->Html->link('<i class="fas fa-arrow-left"></i> Relatórios bancários', ['action' => 'relatorios'], ['class' => 'btn btn-default btn-sm', 'escape' => false]) ?>
            <?= $this->Html->link('<i class="fas fa-list"></i> Cadastro de bancos', ['action' => 'cadastrar'], ['class' => 'btn btn-pgm btn-pgm-situacao btn-sm', 'escape' => false]) ?>
            <?= $this->Html->link('<i class="fas fa-wallet"></i> Financeiro', ['controller' => 'Financeiro', 'action' => 'index'], ['class' => 'btn btn-default btn-sm', 'escape' => false]) ?>
        </div>
    </div>

    <div class="fb-card">
        <div class="fb-meta">
            <span class="fb-pill"><i class="fas fa-layer-group"></i>Total: <?= count($bancos) ?></span>
            <span class="fb-pill"><i class="fas fa-check-circle"></i>Ativos: <?= count(array_filter($bancos, function ($b) { return !empty($b->ativo); })) ?></span>
            <span class="fb-pill"><i class="fas fa-times-circle"></i>Inativos: <?= count(array_filter($bancos, function ($b) { return empty($b->ativo); })) ?></span>
        </div>

        <?php if (empty($bancos)): ?>
            <div class="fb-empty">
                <i class="fas fa-university"></i>
                Nenhum banco cadastrado para esta empresa.
            </div>
        <?php else: ?>
            <div class="fb-table-wrap">
                <table class="fb-table" id="relacaoBancosTable">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Núm. banco</th>
                            <th>CNAB</th>
                            <th>Banco</th>
                            <th>Agência</th>
                            <th>Conta</th>
                            <th>Cód. interno</th>
                            <th>Verifica receber</th>
                            <th>Utiliza endosso</th>
                            <th>Status</th>
                            <th>Cadastro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bancos as $banco): ?>
                            <?php
                                $agencia = trim((string)($banco->numero_agencia ?? ''));
                                $digAgencia = trim((string)($banco->digito_agencia ?? ''));
                                $conta = trim((string)($banco->numero_conta ?? ''));
                                $digConta = trim((string)($banco->digito_conta ?? ''));
                                $agenciaFmt = $agencia !== '' ? $agencia . ($digAgencia !== '' ? '-' . $digAgencia : '') : '—';
                                $contaFmt = $conta !== '' ? $conta . ($digConta !== '' ? '-' . $digConta : '') : '—';
                            ?>
                            <tr>
                                <td><span class="fb-code"><?= h($banco->codigo_banco ?: '—') ?></span></td>
                                <td><?= h($banco->numero_banco ?: '—') ?></td>
                                <td><?= h($banco->cnab ?: '—') ?></td>
                                <td>
                                    <strong><?= h($banco->nome) ?></strong>
                                    <?php if (!empty($banco->observacoes)): ?>
                                        <div class="fb-small"><?= h($banco->observacoes) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= h($agenciaFmt) ?></td>
                                <td><?= h($contaFmt) ?></td>
                                <td><?= h($banco->codigo_banco_interno ?: '—') ?></td>
                                <td><?= h($banco->verifica_receber ?: '—') ?></td>
                                <td><?= h($banco->utiliza_endosso ?: '—') ?></td>
                                <td>
                                    <?php if (!empty($banco->ativo)): ?>
                                        <span class="fb-status fb-status--on"><span class="fb-status-dot"></span>Ativo</span>
                                    <?php else: ?>
                                        <span class="fb-status fb-status--off"><span class="fb-status-dot"></span>Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($banco->created)): ?>
                                        <?= h($banco->created->format('d/m/Y H:i')) ?>
                                    <?php else: ?>
                                        <span class="fb-muted">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
$(function() {
    if ($.fn.DataTable && $('#relacaoBancosTable').length) {
        $('#relacaoBancosTable').DataTable({
            order: [[0, 'asc']],
            pageLength: <?= $pagelength ?? 25 ?>,
            language: { url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Portuguese-Brasil.json' }
        });
    }
});
</script>
