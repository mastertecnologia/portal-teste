<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Financeiro', ['controller' => 'Financeiro', 'action' => 'index']);
$this->Breadcrumbs->add('Bancos', ['controller' => 'FinanceiroBancos', 'action' => 'index']);
$this->Breadcrumbs->add('Relatórios', ['controller' => 'FinanceiroBancos', 'action' => 'relatorios']);
$this->Breadcrumbs->add('Relação de Remessas Bancárias');

$resumo = $resumo ?? [];
?>
<style>
.fb-root { font-family:'DM Sans',sans-serif; }
.fb-topbar {
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:18px 24px 14px;
    border-bottom:1px solid rgba(255,255,255,.07);
    gap:12px;
    flex-wrap:wrap;
}
.fb-h1 {
    font-size:20px;
    font-weight:600;
    color:#e6edf3;
}
.fb-h1-ico {
    color:#5cdbc0;
    margin-right:8px;
}
.fb-actions {
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}
.fb-card {
    background:#161b22;
    border:1px solid rgba(255,255,255,.07);
    border-radius:10px;
    padding:18px 20px;
    margin:16px 24px;
}
.fb-muted {
    color:#7d8590;
    font-size:12px;
}
.fb-kpi-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));
    gap:14px;
    margin-bottom:16px;
}
.fb-kpi {
    background:rgba(255,255,255,.02);
    border:1px solid rgba(255,255,255,.06);
    border-radius:10px;
    padding:14px 16px;
}
.fb-kpi-label {
    color:#7d8590;
    font-size:10.5px;
    text-transform:uppercase;
    letter-spacing:.05em;
    font-weight:700;
    margin-bottom:6px;
}
.fb-kpi-value {
    color:#e6edf3;
    font-size:24px;
    font-weight:700;
    line-height:1.1;
}
.fb-table {
    width:100%;
    border-collapse:collapse;
    font-size:12.5px;
}
.fb-table th {
    color:#7d8590;
    font-size:10.5px;
    text-transform:uppercase;
    letter-spacing:.05em;
    font-weight:600;
    padding:8px 10px;
    border-bottom:1px solid rgba(255,255,255,.07);
    text-align:left;
}
.fb-table td {
    padding:10px;
    border-bottom:1px solid rgba(255,255,255,.04);
    color:#c9d1d9;
    vertical-align:middle;
}
.fb-table tr:hover td {
    background:rgba(255,255,255,.02);
}
.fb-zero {
    text-align:center;
    padding:40px 20px;
    color:#7d8590;
}
.fb-badge {
    display:inline-block;
    padding:3px 8px;
    border-radius:999px;
    font-size:11px;
    font-weight:600;
    background:rgba(92,219,192,.12);
    color:#5cdbc0;
}
.fb-badge--warn {
    background:rgba(255,193,7,.14);
    color:#ffd166;
}
.fb-total {
    text-align:right;
    font-weight:700;
    color:#5cdbc0;
}
.fb-money {
    white-space:nowrap;
    font-weight:600;
}
.fb-note {
    display:block;
    margin-top:4px;
    color:#8b949e;
    font-size:11px;
    line-height:1.45;
}
.fb-warning {
    color:#ffd166;
}
</style>

<div class="fb-root">
    <div class="fb-topbar">
        <div>
            <div class="fb-h1"><i class="fas fa-file-export fb-h1-ico"></i>Relação de Remessas Bancárias</div>
            <div class="fb-muted">Resumo dos títulos a receber em aberto agrupados por banco.</div>
        </div>

        <div class="fb-actions">
            <?= $this->Html->link(
                '<i class="fas fa-chart-bar"></i> Relatórios bancários',
                ['controller' => 'FinanceiroBancos', 'action' => 'relatorios'],
                ['class' => 'btn btn-pgm btn-pgm-situacao btn-sm', 'escape' => false]
            ) ?>
            <?= $this->Html->link(
                '<i class="fas fa-exchange-alt"></i> Remessa',
                ['controller' => 'FinanceiroBancos', 'action' => 'remessa'],
                ['class' => 'btn btn-pgm btn-pgm-salvar btn-sm', 'escape' => false]
            ) ?>
            <?= $this->Html->link(
                '<i class="fas fa-arrow-left"></i> Bancos',
                ['controller' => 'FinanceiroBancos', 'action' => 'index'],
                ['class' => 'btn btn-default btn-sm', 'escape' => false]
            ) ?>
        </div>
    </div>

    <div class="fb-card">
        <?php if (empty($resumo)): ?>
            <div class="fb-zero">
                Nenhuma previsão de remessa bancária encontrada.
            </div>
        <?php else: ?>
            <?php
            $totalGeral = 0.0;
            $qtdGeral = 0;
            $bancosComRemessa = 0;
            $bancosContaIncompleta = 0;
            foreach ($resumo as $item) {
                $totalGeral += (float)($item['total'] ?? 0);
                $qtdGeral += (int)($item['quantidade'] ?? 0);

                $banco = $item['banco'] ?? null;
                if ((int)($item['quantidade'] ?? 0) > 0) {
                    $bancosComRemessa++;
                }
                if (
                    empty($banco->numero_agencia) ||
                    empty($banco->numero_conta)
                ) {
                    $bancosContaIncompleta++;
                }
            }
            ?>
            <div class="fb-kpi-grid">
                <div class="fb-kpi">
                    <div class="fb-kpi-label">Bancos com remessa prevista</div>
                    <div class="fb-kpi-value"><?= $bancosComRemessa ?></div>
                </div>
                <div class="fb-kpi">
                    <div class="fb-kpi-label">Qtd. total de títulos</div>
                    <div class="fb-kpi-value"><?= $qtdGeral ?></div>
                </div>
                <div class="fb-kpi">
                    <div class="fb-kpi-label">Valor total previsto</div>
                    <div class="fb-kpi-value">R$ <?= number_format($totalGeral, 2, ',', '.') ?></div>
                </div>
                <div class="fb-kpi">
                    <div class="fb-kpi-label">Bancos com conta incompleta</div>
                    <div class="fb-kpi-value"><?= $bancosContaIncompleta ?></div>
                </div>
            </div>

            <table class="fb-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Banco</th>
                        <th>CNAB</th>
                        <th>Agência</th>
                        <th>Conta</th>
                        <th>Qtd. títulos</th>
                        <th>Total previsto</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resumo as $item): ?>
                        <?php
                            $banco = $item['banco'];
                            $temContaCompleta = !empty($banco->numero_agencia) && !empty($banco->numero_conta);
                        ?>
                        <tr>
                            <td><code><?= h($banco->codigo_banco ?? '—') ?></code></td>
                            <td>
                                <?= h($banco->nome ?? '—') ?>
                                <?php if (!$temContaCompleta): ?>
                                    <span class="fb-note fb-warning">Cadastro incompleto para conciliação/retorno.</span>
                                <?php endif; ?>
                            </td>
                            <td><?= h($banco->cnab ?: '—') ?></td>
                            <td>
                                <?= h($banco->numero_agencia ?: '—') ?>
                                <?php if (!empty($banco->digito_agencia)): ?>
                                    -<?= h($banco->digito_agencia) ?>
                                <?php endif; ?>
                                <?php if (empty($banco->numero_agencia)): ?>
                                    <span class="fb-note fb-warning">Agência não informada</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= h($banco->numero_conta ?: '—') ?>
                                <?php if (!empty($banco->digito_conta)): ?>
                                    -<?= h($banco->digito_conta) ?>
                                <?php endif; ?>
                                <?php if (empty($banco->numero_conta)): ?>
                                    <span class="fb-note fb-warning">Conta não informada</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= (int)($item['quantidade'] ?? 0) ?>
                                <?php if ((int)($item['quantidade'] ?? 0) > 0): ?>
                                    <span class="fb-note">Carteira pronta para análise operacional.</span>
                                <?php endif; ?>
                            </td>
                            <td class="fb-money">
                                R$ <?= number_format((float)($item['total'] ?? 0), 2, ',', '.') ?>
                            </td>
                            <td>
                                <?php if ((int)($item['quantidade'] ?? 0) > 0): ?>
                                    <span class="fb-badge">Com remessa prevista</span>
                                <?php elseif (!$temContaCompleta): ?>
                                    <span class="fb-badge fb-badge--warn">Conta incompleta</span>
                                <?php else: ?>
                                    <span class="fb-badge">Sem títulos pendentes</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" class="fb-total">Totais</td>
                        <td class="fb-total"><?= $qtdGeral ?></td>
                        <td class="fb-total">R$ <?= number_format($totalGeral, 2, ',', '.') ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        <?php endif; ?>
    </div>
</div>
