<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Financeiro', ['controller' => 'Financeiro', 'action' => 'index']);
$this->Breadcrumbs->add('Bancos', ['controller' => 'FinanceiroBancos', 'action' => 'index']);
$this->Breadcrumbs->add('Relatórios Bancários', ['controller' => 'FinanceiroBancos', 'action' => 'relatorios']);
$this->Breadcrumbs->add('Previsão por Bancos');

$resumo = $resumo ?? [];

$totais = [
    'bancos' => count($resumo),
    'ativos' => 0,
    'inativos' => 0,
    'incompletos' => 0,
    'saldo_positivo' => 0,
    'saldo_negativo' => 0,
];
?>
<style>
.fb-root { font-family:'DM Sans',sans-serif; }
.fb-topbar { display:flex; align-items:center; justify-content:space-between; padding:18px 24px 14px; border-bottom:1px solid rgba(255,255,255,.07); gap:12px; flex-wrap:wrap; }
.fb-h1 { font-size:20px; font-weight:600; color:#e6edf3; }
.fb-h1-ico { color:#5cdbc0; margin-right:8px; }
.fb-kpis { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:14px; padding:20px 24px 0; }
.fb-kpi { background:#161b22; border:1px solid rgba(255,255,255,.07); border-radius:10px; padding:16px 18px; }
.fb-kpi-label { color:#7d8590; font-size:11px; text-transform:uppercase; letter-spacing:.05em; font-weight:600; margin-bottom:6px; }
.fb-kpi-value { color:#e6edf3; font-size:24px; font-weight:700; line-height:1.1; }
.fb-card { background:#161b22; border:1px solid rgba(255,255,255,.07); border-radius:10px; padding:18px 20px; margin:16px 24px; }
.fb-table { width:100%; border-collapse:collapse; font-size:12.5px; }
.fb-table th { color:#7d8590; font-size:10.5px; text-transform:uppercase; letter-spacing:.05em; font-weight:600; padding:8px; border-bottom:1px solid rgba(255,255,255,.07); text-align:left; }
.fb-table td { padding:10px 8px; border-bottom:1px solid rgba(255,255,255,.04); color:#c9d1d9; vertical-align:middle; }
.fb-table tr:hover td { background:rgba(255,255,255,.02); }
.fb-zero { color:#8b949e; text-align:center; padding:32px 12px; }
.fb-tag { display:inline-block; padding:3px 8px; border-radius:999px; font-size:11px; font-weight:600; }
.fb-tag--ok { background:rgba(63,185,80,.15); color:#3fb950; }
.fb-tag--off { background:rgba(248,81,73,.15); color:#f85149; }
.fb-tag--warn { background:rgba(255,193,7,.15); color:#ffd166; }
.fb-money-pos { color:#3fb950; font-weight:700; }
.fb-money-neg { color:#f85149; font-weight:700; }
.fb-total-row td { border-top:1px solid rgba(92,219,192,.25); font-weight:700; color:#e6edf3; }
.fb-muted { color:#8b949e; font-size:11px; }
.fb-alert { display:inline-block; margin-top:6px; padding:3px 8px; border-radius:999px; font-size:11px; font-weight:600; background:rgba(255,193,7,.15); color:#ffd166; }
</style>

<div class="fb-root">
    <div class="fb-topbar">
        <h1 class="fb-h1"><i class="fas fa-chart-line fb-h1-ico"></i>Previsão por Bancos</h1>
        <div>
            <?= $this->Html->link('Relatórios bancários', ['action' => 'relatorios'], ['class' => 'btn btn-pgm btn-pgm-situacao btn-sm']) ?>
            <?= $this->Html->link('Bancos', ['action' => 'index'], ['class' => 'btn btn-default btn-sm']) ?>
        </div>
    </div>

    <?php
    foreach ($resumo as $item) {
        $banco = $item['banco'] ?? null;
        $receber = (float)($item['receber'] ?? 0);
        $recebido = (float)($item['recebido'] ?? 0);
        $pagar = (float)($item['pagar'] ?? 0);
        $pago = (float)($item['pago'] ?? 0);
        $saldo = ($receber + $recebido) - ($pagar + $pago);

        if (!empty($banco->ativo)) {
            $totais['ativos']++;
        } else {
            $totais['inativos']++;
        }

        if (empty($banco->numero_agencia) || empty($banco->numero_conta)) {
            $totais['incompletos']++;
        }

        if ($saldo >= 0) {
            $totais['saldo_positivo']++;
        } else {
            $totais['saldo_negativo']++;
        }
    }
    ?>

    <div class="fb-kpis">
        <div class="fb-kpi">
            <div class="fb-kpi-label">Bancos consolidados</div>
            <div class="fb-kpi-value"><?= number_format((int)$totais['bancos'], 0, ',', '.') ?></div>
        </div>
        <div class="fb-kpi">
            <div class="fb-kpi-label">Bancos ativos</div>
            <div class="fb-kpi-value"><?= number_format((int)$totais['ativos'], 0, ',', '.') ?></div>
        </div>
        <div class="fb-kpi">
            <div class="fb-kpi-label">Cadastros incompletos</div>
            <div class="fb-kpi-value"><?= number_format((int)$totais['incompletos'], 0, ',', '.') ?></div>
        </div>
        <div class="fb-kpi">
            <div class="fb-kpi-label">Saldos positivos</div>
            <div class="fb-kpi-value"><?= number_format((int)$totais['saldo_positivo'], 0, ',', '.') ?></div>
        </div>
    </div>

    <div class="fb-card">
        <?php if (empty($resumo)): ?>
            <div class="fb-zero">
                Nenhum banco cadastrado para consolidar a previsão.
            </div>
        <?php else: ?>
            <?php
            $totReceber = 0.0;
            $totRecebido = 0.0;
            $totPagar = 0.0;
            $totPago = 0.0;
            ?>
            <table class="fb-table">
                <thead>
                    <tr>
                        <th>Banco</th>
                        <th>Status</th>
                        <th>Agência / Conta</th>
                        <th>A receber</th>
                        <th>Recebido</th>
                        <th>A pagar</th>
                        <th>Pago</th>
                        <th>Saldo previsto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resumo as $item): ?>
                        <?php
                        $banco = $item['banco'] ?? null;
                        $receber = (float)($item['receber'] ?? 0);
                        $recebido = (float)($item['recebido'] ?? 0);
                        $pagar = (float)($item['pagar'] ?? 0);
                        $pago = (float)($item['pago'] ?? 0);
                        $saldo = ($receber + $recebido) - ($pagar + $pago);

                        $totReceber += $receber;
                        $totRecebido += $recebido;
                        $totPagar += $pagar;
                        $totPago += $pago;

                        $codigo = !empty($banco->codigo_banco) ? $banco->codigo_banco : '—';
                        $nome = !empty($banco->nome) ? $banco->nome : 'Banco não informado';
                        $agencia = !empty($banco->numero_agencia) ? $banco->numero_agencia : '—';
                        if (!empty($banco->digito_agencia) && $agencia !== '—') {
                            $agencia .= '-' . $banco->digito_agencia;
                        }
                        $conta = !empty($banco->numero_conta) ? $banco->numero_conta : '—';
                        if (!empty($banco->digito_conta) && $conta !== '—') {
                            $conta .= '-' . $banco->digito_conta;
                        }
                        $cadastroIncompleto = empty($banco->numero_agencia) || empty($banco->numero_conta);
                        ?>
                        <tr>
                            <td>
                                <strong><?= h($codigo) ?> — <?= h($nome) ?></strong>
                                <?php if (!empty($banco->cnab)): ?>
                                    <div class="fb-muted">CNAB: <?= h($banco->cnab) ?></div>
                                <?php endif; ?>
                                <?php if ($cadastroIncompleto): ?>
                                    <div class="fb-alert">Cadastro incompleto para conciliação/retorno</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($banco->ativo)): ?>
                                    <span class="fb-tag fb-tag--ok">Ativo</span>
                                <?php else: ?>
                                    <span class="fb-tag fb-tag--off">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                Ag. <?= h($agencia) ?><br>
                                <span class="fb-muted">Cc. <?= h($conta) ?></span>
                                <?php if ($cadastroIncompleto): ?>
                                    <div class="fb-muted">Preencha agência e conta para uso bancário completo.</div>
                                <?php endif; ?>
                            </td>
                            <td>R$ <?= number_format($receber, 2, ',', '.') ?></td>
                            <td>R$ <?= number_format($recebido, 2, ',', '.') ?></td>
                            <td>R$ <?= number_format($pagar, 2, ',', '.') ?></td>
                            <td>R$ <?= number_format($pago, 2, ',', '.') ?></td>
                            <td class="<?= $saldo >= 0 ? 'fb-money-pos' : 'fb-money-neg' ?>">
                                R$ <?= number_format($saldo, 2, ',', '.') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <?php $saldoTotal = ($totReceber + $totRecebido) - ($totPagar + $totPago); ?>
                    <tr class="fb-total-row">
                        <td colspan="3">Total consolidado</td>
                        <td>R$ <?= number_format($totReceber, 2, ',', '.') ?></td>
                        <td>R$ <?= number_format($totRecebido, 2, ',', '.') ?></td>
                        <td>R$ <?= number_format($totPagar, 2, ',', '.') ?></td>
                        <td>R$ <?= number_format($totPago, 2, ',', '.') ?></td>
                        <td class="<?= $saldoTotal >= 0 ? 'fb-money-pos' : 'fb-money-neg' ?>">
                            R$ <?= number_format($saldoTotal, 2, ',', '.') ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        <?php endif; ?>
    </div>
</div>
