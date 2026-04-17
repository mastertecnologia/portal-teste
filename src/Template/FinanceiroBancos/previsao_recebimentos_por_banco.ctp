<?php
/**
 * Financeiro > Bancos
 * Relatório: Previsão de Recebimentos por Banco
 *
 * Variáveis esperadas:
 * - array $previsao
 */

$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Financeiro', ['controller' => 'Financeiro', 'action' => 'index']);
$this->Breadcrumbs->add('Bancos', ['controller' => 'FinanceiroBancos', 'action' => 'index']);
$this->Breadcrumbs->add('Relatórios', ['controller' => 'FinanceiroBancos', 'action' => 'relatorios']);
$this->Breadcrumbs->add('Previsão de recebimentos por banco');

$previsao = $previsao ?? [];

$totalGeral = 0.0;
$totalQtd = 0;
$bancosComPrevisao = 0;
$bancosSemContaCompleta = 0;
$maisProximoVencimento = null;

foreach ($previsao as $item) {
    $total = (float)($item['total'] ?? 0);
    $qtd = (int)($item['qtd'] ?? 0);
    $banco = $item['banco'] ?? null;
    $proximo = $item['proximo_vencimento'] ?? null;

    $totalGeral += $total;
    $totalQtd += $qtd;

    if ($qtd > 0) {
        $bancosComPrevisao++;
    }

    $temAgencia = !empty($banco->numero_agencia);
    $temConta = !empty($banco->numero_conta);
    if (!$temAgencia || !$temConta) {
        $bancosSemContaCompleta++;
    }

    if (!empty($proximo) && ($maisProximoVencimento === null || $proximo < $maisProximoVencimento)) {
        $maisProximoVencimento = $proximo;
    }
}
?>
<style>
.fb-rel-root { font-family:'DM Sans',sans-serif; }
.fb-rel-topbar {
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:18px 24px 14px;
    border-bottom:1px solid rgba(255,255,255,.07);
    gap:12px;
    flex-wrap:wrap;
}
.fb-rel-h1 {
    font-size:20px;
    font-weight:600;
    color:#e6edf3;
    margin:0;
}
.fb-rel-h1-ico {
    color:#5cdbc0;
    margin-right:8px;
}
.fb-rel-actions {
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}
.fb-rel-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));
    gap:14px;
    padding:20px 24px 0;
}
.fb-rel-kpi {
    background:#161b22;
    border:1px solid rgba(255,255,255,.07);
    border-radius:10px;
    padding:16px 18px;
}
.fb-rel-kpi-label {
    color:#7d8590;
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:.05em;
    font-weight:600;
    margin-bottom:6px;
}
.fb-rel-kpi-value {
    color:#e6edf3;
    font-size:24px;
    font-weight:700;
    line-height:1.1;
}
.fb-rel-card {
    background:#161b22;
    border:1px solid rgba(255,255,255,.07);
    border-radius:10px;
    padding:18px 20px;
    margin:16px 24px 24px;
}
.fb-rel-note {
    margin:0 24px 16px;
    background:rgba(255,193,7,.08);
    border:1px solid rgba(255,193,7,.16);
    border-radius:10px;
    padding:12px 14px;
    color:#c9d1d9;
    font-size:12.5px;
    line-height:1.6;
}
.fb-rel-note strong {
    color:#ffd166;
}
.fb-rel-card-title {
    color:#e6edf3;
    font-size:15px;
    font-weight:600;
    margin:0 0 14px;
}
.fb-rel-table-wrap {
    overflow:auto;
}
.fb-rel-table {
    width:100%;
    border-collapse:collapse;
    font-size:13px;
}
.fb-rel-table th {
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
.fb-rel-table td {
    padding:10px 10px;
    border-bottom:1px solid rgba(255,255,255,.04);
    color:#c9d1d9;
    vertical-align:middle;
}
.fb-rel-table tr:hover td {
    background:rgba(255,255,255,.02);
}
.fb-rel-table tfoot td {
    font-weight:700;
    color:#5cdbc0;
    border-top:1px solid rgba(92,219,192,.18);
    background:rgba(92,219,192,.05);
}
.fb-rel-muted {
    color:#7d8590;
    font-size:12px;
}
.fb-rel-status {
    display:inline-block;
    padding:3px 8px;
    border-radius:999px;
    font-size:11px;
    font-weight:600;
    background:rgba(63,185,80,.13);
    color:#3fb950;
}
.fb-rel-empty {
    text-align:center;
    color:#7d8590;
    padding:32px 10px;
}
.fb-rel-empty i {
    display:block;
    font-size:32px;
    margin-bottom:10px;
    opacity:.4;
}
.fb-rel-money {
    white-space:nowrap;
    font-weight:600;
}
</style>

<div class="fb-rel-root">
    <div class="fb-rel-topbar">
        <h1 class="fb-rel-h1">
            <i class="fas fa-coins fb-rel-h1-ico"></i>
            Previsão de Recebimentos por Banco
        </h1>

        <div class="fb-rel-actions">
            <?= $this->Html->link(
                '<i class="fas fa-university"></i> Relatórios bancários',
                ['controller' => 'FinanceiroBancos', 'action' => 'relatorios'],
                ['class' => 'btn btn-pgm btn-pgm-situacao btn-sm', 'escape' => false]
            ) ?>
            <?= $this->Html->link(
                '<i class="fas fa-arrow-left"></i> Bancos',
                ['controller' => 'FinanceiroBancos', 'action' => 'index'],
                ['class' => 'btn btn-default btn-sm', 'escape' => false]
            ) ?>
        </div>
    </div>

    <div class="fb-rel-grid">
        <div class="fb-rel-kpi">
            <div class="fb-rel-kpi-label">Bancos no relatório</div>
            <div class="fb-rel-kpi-value"><?= number_format(count($previsao), 0, ',', '.') ?></div>
        </div>
        <div class="fb-rel-kpi">
            <div class="fb-rel-kpi-label">Qtd. títulos em aberto</div>
            <div class="fb-rel-kpi-value"><?= number_format($totalQtd, 0, ',', '.') ?></div>
        </div>
        <div class="fb-rel-kpi">
            <div class="fb-rel-kpi-label">Previsão total</div>
            <div class="fb-rel-kpi-value">R$ <?= number_format($totalGeral, 2, ',', '.') ?></div>
        </div>
        <div class="fb-rel-kpi">
            <div class="fb-rel-kpi-label">Bancos com previsão</div>
            <div class="fb-rel-kpi-value"><?= number_format($bancosComPrevisao, 0, ',', '.') ?></div>
        </div>
        <div class="fb-rel-kpi">
            <div class="fb-rel-kpi-label">Próximo vencimento geral</div>
            <div class="fb-rel-kpi-value" style="font-size:20px;">
                <?= $maisProximoVencimento ? h($maisProximoVencimento->format('d/m/Y')) : '—' ?>
            </div>
        </div>
        <div class="fb-rel-kpi">
            <div class="fb-rel-kpi-label">Cadastros incompletos</div>
            <div class="fb-rel-kpi-value"><?= number_format($bancosSemContaCompleta, 0, ',', '.') ?></div>
        </div>
    </div>

    <?php if ($bancosSemContaCompleta > 0): ?>
        <div class="fb-rel-note">
            <strong>Atenção:</strong> há banco(s) sem agência ou conta preenchida. Isso pode prejudicar a conciliação bancária, o retorno e a leitura operacional da previsão.
        </div>
    <?php endif; ?>

    <div class="fb-rel-card">
        <h2 class="fb-rel-card-title">Recebimentos previstos por banco</h2>

        <div class="fb-rel-table-wrap">
            <table class="fb-rel-table" id="tbPrevisaoRecebimentosBanco">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Banco</th>
                        <th>Agência</th>
                        <th>Conta</th>
                        <th>Qtd. títulos</th>
                        <th>Próximo vencimento</th>
                        <th>Total previsto</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($previsao)): ?>
                        <tr>
                            <td colspan="8" class="fb-rel-empty">
                                <i class="fas fa-search-dollar"></i>
                                Nenhuma previsão de recebimento encontrada por banco.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($previsao as $item): ?>
                            <?php
                            $banco = $item['banco'] ?? null;
                            $codigo = $banco->codigo_banco ?? '—';
                            $nome = $banco->nome ?? 'Banco não informado';
                            $agencia = !empty($banco->numero_agencia) ? $banco->numero_agencia : '—';
                            if (!empty($banco->digito_agencia)) {
                                $agencia .= '-' . $banco->digito_agencia;
                            }

                            $conta = !empty($banco->numero_conta) ? $banco->numero_conta : '—';
                            if (!empty($banco->digito_conta)) {
                                $conta .= '-' . $banco->digito_conta;
                            }

                            $qtd = (int)($item['qtd'] ?? 0);
                            $total = (float)($item['total'] ?? 0);
                            $proximoVencimento = $item['proximo_vencimento'] ?? null;
                            $temContaCompleta = !empty($banco->numero_agencia) && !empty($banco->numero_conta);
                            ?>
                            <tr>
                                <td><code><?= h($codigo) ?></code></td>
                                <td>
                                    <?= h($nome) ?>
                                    <?php if (!$temContaCompleta): ?>
                                        <div class="fb-rel-muted">Cadastro bancário incompleto</div>
                                    <?php endif; ?>
                                </td>
                                <td><?= h($agencia) ?></td>
                                <td><?= h($conta) ?></td>
                                <td><?= number_format($qtd, 0, ',', '.') ?></td>
                                <td>
                                    <?php if (!empty($proximoVencimento)): ?>
                                        <?= h($proximoVencimento->format('d/m/Y')) ?>
                                    <?php else: ?>
                                        <span class="fb-rel-muted">Sem vencimento previsto</span>
                                    <?php endif; ?>
                                </td>
                                <td class="fb-rel-money">R$ <?= number_format($total, 2, ',', '.') ?></td>
                                <td>
                                    <?php if ($qtd > 0 && $temContaCompleta): ?>
                                        <span class="fb-rel-status">Com previsão</span>
                                    <?php elseif ($qtd > 0): ?>
                                        <span class="fb-rel-muted">Com previsão, revisar cadastro bancário</span>
                                    <?php else: ?>
                                        <span class="fb-rel-muted">Sem títulos em aberto</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($previsao)): ?>
                    <tfoot>
                        <tr>
                            <td colspan="4">Totais</td>
                            <td><?= number_format($totalQtd, 0, ',', '.') ?></td>
                            <td>—</td>
                            <td>R$ <?= number_format($totalGeral, 2, ',', '.') ?></td>
                            <td>Consolidado</td>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<script>
$(function () {
    if ($.fn.DataTable && $('#tbPrevisaoRecebimentosBanco').length) {
        $('#tbPrevisaoRecebimentosBanco').DataTable({
            order: [[6, 'desc']],
            pageLength: <?= (int)($pagelength ?? 25) ?>,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Portuguese-Brasil.json'
            }
        });
    }
});
</script>
