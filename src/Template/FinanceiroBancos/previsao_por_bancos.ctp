<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Financeiro', ['controller' => 'Financeiro', 'action' => 'index']);
$this->Breadcrumbs->add('Bancos', ['controller' => 'FinanceiroBancos', 'action' => 'index']);
$this->Breadcrumbs->add('Relatórios Bancários', ['controller' => 'FinanceiroBancos', 'action' => 'relatorios']);
$this->Breadcrumbs->add('Previsão por Bancos');

$resumo = $resumo ?? [];
$codigo = $codigo ?? '';
$nome = $nome ?? '';
$situacao = $situacao ?? '';
$metricasResumo = $metricasResumo ?? [
    'bancos' => count($resumo),
    'saldo_positivo' => 0,
    'saldo_negativo' => 0,
    'conta_incompleta' => 0,
    'com_movimento' => 0,
    'sem_movimento' => 0,
    'total_receber' => 0.0,
    'total_recebido' => 0.0,
    'total_pagar' => 0.0,
    'total_pago' => 0.0,
    'saldo_total' => 0.0,
];

$linhas = [];
$maiorMovimento = 0.0;
$temFiltros = $codigo !== '' || $nome !== '' || $situacao !== '';
$ultimoMovimentoGeral = null;

foreach ($resumo as $item) {
    $banco = $item['banco'] ?? null;
    if (!$banco) {
        continue;
    }

    $receber = (float)($item['receber'] ?? 0);
    $recebido = (float)($item['recebido'] ?? 0);
    $pagar = (float)($item['pagar'] ?? 0);
    $pago = (float)($item['pago'] ?? 0);
    $saldo = ($receber + $recebido) - ($pagar + $pago);
    $movimento = $receber + $recebido + $pagar + $pago;
    $contaIncompleta = !empty($item['conta_incompleta']);
    $ultimoMovimento = $item['ultimo_movimento'] ?? null;

    if ($movimento > $maiorMovimento) {
        $maiorMovimento = $movimento;
    }

    if (!empty($ultimoMovimento) && ($ultimoMovimentoGeral === null || $ultimoMovimento > $ultimoMovimentoGeral)) {
        $ultimoMovimentoGeral = $ultimoMovimento;
    }

    $agencia = trim((string)($banco->numero_agencia ?? ''));
    if ($agencia !== '' && !empty($banco->digito_agencia)) {
        $agencia .= '-' . trim((string)$banco->digito_agencia);
    }
    if ($agencia === '') {
        $agencia = '—';
    }

    $conta = trim((string)($banco->numero_conta ?? ''));
    if ($conta !== '' && !empty($banco->digito_conta)) {
        $conta .= '-' . trim((string)$banco->digito_conta);
    }
    if ($conta === '') {
        $conta = '—';
    }

    $statusLabel = 'Sem movimento';
    $statusClass = 'fb-badge fb-badge--neutral';
    $prioridade = 5;
    $statusAjuda = 'Sem lançamentos financeiros relacionados ao banco no período consolidado.';

    if ($saldo < 0 && $contaIncompleta) {
        $statusLabel = 'Saldo negativo com atenção';
        $statusClass = 'fb-badge fb-badge--danger';
        $prioridade = 1;
        $statusAjuda = 'Há pressão negativa no saldo previsto e o cadastro bancário precisa de revisão.';
    } elseif ($saldo < 0) {
        $statusLabel = 'Saldo negativo';
        $statusClass = 'fb-badge fb-badge--danger';
        $prioridade = 2;
        $statusAjuda = 'O consolidado do banco está com saldo previsto abaixo de zero.';
    } elseif ($contaIncompleta && $movimento > 0) {
        $statusLabel = 'Movimento com cadastro incompleto';
        $statusClass = 'fb-badge fb-badge--warn';
        $prioridade = 3;
        $statusAjuda = 'Há movimentação financeira, mas agência e conta precisam ser revisadas.';
    } elseif ($movimento > 0) {
        $statusLabel = 'Com movimento';
        $statusClass = 'fb-badge fb-badge--ok';
        $prioridade = 4;
        $statusAjuda = 'Banco com movimentação financeira consolidada e saldo monitorado.';
    } elseif ($contaIncompleta) {
        $statusLabel = 'Conta incompleta';
        $statusClass = 'fb-badge fb-badge--warn';
        $prioridade = 4;
        $statusAjuda = 'Sem movimento relevante, mas o cadastro não está completo para operação bancária.';
    }

    $linhas[] = [
        'banco' => $banco,
        'receber' => $receber,
        'recebido' => $recebido,
        'pagar' => $pagar,
        'pago' => $pago,
        'saldo' => $saldo,
        'movimento' => $movimento,
        'conta_incompleta' => $contaIncompleta,
        'ultimo_movimento' => $ultimoMovimento,
        'agencia_fmt' => $agencia,
        'conta_fmt' => $conta,
        'status_label' => $statusLabel,
        'status_class' => $statusClass,
        'status_ajuda' => $statusAjuda,
        'prioridade' => $prioridade,
    ];
}

usort($linhas, function ($a, $b) {
    if ($a['prioridade'] !== $b['prioridade']) {
        return $a['prioridade'] <=> $b['prioridade'];
    }

    if ((float)$a['movimento'] !== (float)$b['movimento']) {
        return ((float)$b['movimento'] <=> (float)$a['movimento']);
    }

    if ((float)$a['saldo'] !== (float)$b['saldo']) {
        return ((float)$a['saldo'] <=> (float)$b['saldo']);
    }

    return strcmp((string)($a['banco']->nome ?? ''), (string)($b['banco']->nome ?? ''));
});

$maiorSaldoAbsoluto = 0.0;
foreach ($linhas as $linha) {
    $absSaldo = abs((float)$linha['saldo']);
    if ($absSaldo > $maiorSaldoAbsoluto) {
        $maiorSaldoAbsoluto = $absSaldo;
    }
}
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
    margin:0;
}
.fb-h1-ico {
    color:#5cdbc0;
    margin-right:8px;
}
.fb-sub {
    color:#7d8590;
    font-size:12.5px;
    margin-top:4px;
}
.fb-actions {
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}

.fb-filters-card,
.fb-kpi,
.fb-card {
    background:#161b22;
    border:1px solid rgba(255,255,255,.07);
    border-radius:10px;
}

.fb-filters-card {
    margin:20px 24px 0;
    padding:16px 18px;
}
.fb-filters-title {
    color:#e6edf3;
    font-size:14px;
    font-weight:600;
    margin:0 0 12px;
}
.fb-filters-grid {
    display:grid;
    grid-template-columns:minmax(160px, 180px) minmax(240px, 1fr) minmax(220px, 240px) auto auto;
    gap:12px;
    align-items:end;
}
.fb-field label {
    display:block;
    color:#7d8590;
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:.05em;
    font-weight:600;
    margin-bottom:4px;
}
.fb-field .form-control {
    height:36px;
    border-radius:8px;
    border:1px solid rgba(255,255,255,.08);
    background:#0f141a;
    color:#e6edf3;
    box-shadow:none;
}
.fb-filter-actions {
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}
.fb-filter-note {
    margin-top:10px;
    color:#8b949e;
    font-size:12px;
    line-height:1.55;
}

.fb-kpis {
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:14px;
    padding:20px 24px 0;
}
.fb-kpi {
    padding:16px 18px;
}
.fb-kpi-label {
    color:#7d8590;
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:.05em;
    font-weight:600;
    margin-bottom:6px;
}
.fb-kpi-value {
    color:#e6edf3;
    font-size:24px;
    font-weight:700;
    line-height:1.1;
}
.fb-kpi-help {
    margin-top:8px;
    color:#8b949e;
    font-size:11.5px;
    line-height:1.5;
}
.fb-kpi-value--money {
    font-size:20px;
}

.fb-note-box {
    margin:16px 24px 0;
    background:rgba(29,158,117,.08);
    border:1px solid rgba(29,158,117,.16);
    border-radius:10px;
    padding:14px 16px;
    color:#c9d1d9;
    font-size:12.5px;
    line-height:1.6;
}
.fb-note-box strong {
    color:#5cdbc0;
}

.fb-card {
    padding:18px 20px;
    margin:16px 24px;
}
.fb-card-head {
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:12px;
    margin-bottom:14px;
    flex-wrap:wrap;
}
.fb-card-title {
    color:#e6edf3;
    font-size:15px;
    font-weight:600;
    margin:0;
}
.fb-card-sub {
    color:#7d8590;
    font-size:12px;
    margin-top:4px;
    line-height:1.55;
}
.fb-pill-list {
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}
.fb-pill {
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:6px 10px;
    border-radius:999px;
    background:rgba(92,219,192,.08);
    color:#9ee7d6;
    font-size:12px;
    font-weight:600;
}
.fb-pill--warn {
    background:rgba(255,193,7,.12);
    color:#ffd166;
}
.fb-pill--danger {
    background:rgba(248,81,73,.14);
    color:#ff8f8f;
}

.fb-summary-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(250px, 1fr));
    gap:14px;
}
.fb-summary-box {
    background:rgba(255,255,255,.02);
    border:1px solid rgba(255,255,255,.06);
    border-radius:10px;
    padding:14px 16px;
}
.fb-summary-title {
    color:#e6edf3;
    font-size:13px;
    font-weight:700;
    margin-bottom:6px;
}
.fb-summary-text {
    color:#8b949e;
    font-size:12px;
    line-height:1.6;
}

.fb-table-wrap {
    overflow:auto;
}
.fb-table {
    width:100%;
    border-collapse:collapse;
    font-size:12.5px;
    min-width:1480px;
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
    white-space:nowrap;
}
.fb-table td {
    padding:10px;
    border-bottom:1px solid rgba(255,255,255,.04);
    color:#c9d1d9;
    vertical-align:top;
}
.fb-table tr:hover td {
    background:rgba(255,255,255,.02);
}
.fb-code {
    display:inline-block;
    padding:2px 7px;
    border-radius:6px;
    background:rgba(92,219,192,.10);
    color:#5cdbc0;
    font-weight:700;
    font-size:12px;
}
.fb-bank-name {
    font-weight:600;
    color:#e6edf3;
}
.fb-muted {
    color:#8b949e;
    font-size:11px;
    line-height:1.45;
}
.fb-badge {
    display:inline-block;
    padding:3px 8px;
    border-radius:999px;
    font-size:11px;
    font-weight:600;
}
.fb-badge--ok {
    background:rgba(63,185,80,.14);
    color:#3fb950;
}
.fb-badge--warn {
    background:rgba(255,193,7,.14);
    color:#ffd166;
}
.fb-badge--danger {
    background:rgba(248,81,73,.14);
    color:#ff8f8f;
}
.fb-badge--neutral {
    background:rgba(255,255,255,.08);
    color:#9ca3af;
}
.fb-money {
    white-space:nowrap;
    font-weight:600;
}
.fb-money-pos {
    color:#3fb950;
    font-weight:700;
}
.fb-money-neg {
    color:#f85149;
    font-weight:700;
}
.fb-bar {
    width:100%;
    height:8px;
    border-radius:999px;
    background:rgba(255,255,255,.06);
    overflow:hidden;
    margin-top:6px;
}
.fb-bar-fill {
    height:100%;
    border-radius:999px;
    background:linear-gradient(90deg, #1d9e75, #5cdbc0);
}
.fb-bar-fill--neg {
    background:linear-gradient(90deg, #f85149, #ff9b9b);
}
.fb-row-actions {
    display:flex;
    gap:6px;
    flex-wrap:wrap;
}
.fb-zero {
    color:#8b949e;
    text-align:center;
    padding:36px 12px;
}
.fb-zero i {
    display:block;
    font-size:32px;
    margin-bottom:10px;
    opacity:.35;
    color:#5cdbc0;
}
.fb-total-row td {
    border-top:1px solid rgba(92,219,192,.25);
    font-weight:700;
    color:#e6edf3;
}
.fb-total {
    text-align:right;
    color:#5cdbc0 !important;
}
.fb-footer-note {
    margin:0 24px 24px;
    color:#8b949e;
    font-size:12px;
    line-height:1.7;
}

@media (max-width: 1180px) {
    .fb-filters-grid {
        grid-template-columns:1fr 1fr;
    }
}
@media (max-width: 760px) {
    .fb-filters-grid,
    .fb-summary-grid {
        grid-template-columns:1fr;
    }
}
</style>

<div class="fb-root">
    <div class="fb-topbar">
        <div>
            <h1 class="fb-h1"><i class="fas fa-chart-line fb-h1-ico"></i>Previsão por Bancos</h1>
            <div class="fb-sub">Leitura executiva e operacional dos saldos previstos por banco, consolidando receber, recebido, pagar e pago.</div>
        </div>

        <div class="fb-actions">
            <?= $this->Html->link(
                '<i class="fas fa-chart-bar"></i> Relatórios bancários',
                ['action' => 'relatorios'],
                ['class' => 'btn btn-pgm btn-pgm-situacao btn-sm', 'escape' => false]
            ) ?>
            <?= $this->Html->link(
                '<i class="fas fa-hand-holding-usd"></i> Previsão de recebimentos',
                ['action' => 'previsaoRecebimentosPorBanco'],
                ['class' => 'btn btn-default btn-sm', 'escape' => false]
            ) ?>
            <?= $this->Html->link(
                '<i class="fas fa-arrow-left"></i> Bancos',
                ['action' => 'index'],
                ['class' => 'btn btn-default btn-sm', 'escape' => false]
            ) ?>
        </div>
    </div>

    <div class="fb-filters-card">
        <h2 class="fb-filters-title">Filtros da consolidação</h2>

        <?= $this->Form->create(null, ['type' => 'get']) ?>
            <div class="fb-filters-grid">
                <div class="fb-field">
                    <label for="filtro-codigo">Código / Núm. banco / CNAB</label>
                    <?= $this->Form->control('codigo', [
                        'label' => false,
                        'id' => 'filtro-codigo',
                        'class' => 'form-control',
                        'value' => $codigo,
                        'placeholder' => '001, 341, 756...',
                        'templates' => ['inputContainer' => '{{content}}']
                    ]) ?>
                </div>

                <div class="fb-field">
                    <label for="filtro-nome">Banco</label>
                    <?= $this->Form->control('nome', [
                        'label' => false,
                        'id' => 'filtro-nome',
                        'class' => 'form-control',
                        'value' => $nome,
                        'placeholder' => 'Nome do banco',
                        'templates' => ['inputContainer' => '{{content}}']
                    ]) ?>
                </div>

                <div class="fb-field">
                    <label for="filtro-situacao">Situação</label>
                    <?= $this->Form->select('situacao', [
                        '' => 'Todas',
                        'saldo_positivo' => 'Saldo positivo',
                        'saldo_negativo' => 'Saldo negativo',
                        'conta_incompleta' => 'Conta incompleta',
                        'com_movimento' => 'Com movimento',
                        'sem_movimento' => 'Sem movimento',
                    ], [
                        'label' => false,
                        'id' => 'filtro-situacao',
                        'class' => 'form-control',
                        'value' => $situacao,
                        'templates' => ['inputContainer' => '{{content}}']
                    ]) ?>
                </div>

                <div class="fb-filter-actions">
                    <?= $this->Form->button('<i class="fas fa-search"></i> Filtrar', [
                        'type' => 'submit',
                        'class' => 'btn btn-pgm btn-pgm-salvar btn-sm',
                        'escape' => false,
                    ]) ?>
                </div>

                <div class="fb-filter-actions">
                    <?= $this->Html->link(
                        'Limpar',
                        ['controller' => 'FinanceiroBancos', 'action' => 'previsaoPorBancos'],
                        ['class' => 'btn btn-default btn-sm']
                    ) ?>
                </div>
            </div>
        <?= $this->Form->end() ?>

        <div class="fb-filter-note">
            A grade é priorizada para destacar primeiro os bancos com saldo negativo e maior atenção operacional,
            especialmente quando também houver conta bancária incompleta.
        </div>
    </div>

    <div class="fb-kpis">
        <div class="fb-kpi">
            <div class="fb-kpi-label">Bancos consolidados</div>
            <div class="fb-kpi-value"><?= number_format((int)$metricasResumo['bancos'], 0, ',', '.') ?></div>
            <div class="fb-kpi-help">Quantidade de bancos considerados após a aplicação dos filtros atuais.</div>
        </div>

        <div class="fb-kpi">
            <div class="fb-kpi-label">Com movimento</div>
            <div class="fb-kpi-value"><?= number_format((int)$metricasResumo['com_movimento'], 0, ',', '.') ?></div>
            <div class="fb-kpi-help">Bancos com algum volume financeiro registrado no consolidado.</div>
        </div>

        <div class="fb-kpi">
            <div class="fb-kpi-label">Conta incompleta</div>
            <div class="fb-kpi-value"><?= number_format((int)$metricasResumo['conta_incompleta'], 0, ',', '.') ?></div>
            <div class="fb-kpi-help">Cadastros que exigem revisão de agência e conta para operação bancária plena.</div>
        </div>

        <div class="fb-kpi">
            <div class="fb-kpi-label">Total a receber</div>
            <div class="fb-kpi-value fb-kpi-value--money">R$ <?= number_format((float)$metricasResumo['total_receber'], 2, ',', '.') ?></div>
            <div class="fb-kpi-help">Receitas em aberto vinculadas aos bancos do relatório.</div>
        </div>

        <div class="fb-kpi">
            <div class="fb-kpi-label">Total a pagar</div>
            <div class="fb-kpi-value fb-kpi-value--money">R$ <?= number_format((float)$metricasResumo['total_pagar'], 2, ',', '.') ?></div>
            <div class="fb-kpi-help">Despesas em aberto vinculadas aos mesmos bancos.</div>
        </div>

        <div class="fb-kpi">
            <div class="fb-kpi-label">Saldo consolidado</div>
            <div class="fb-kpi-value fb-kpi-value--money <?= (float)$metricasResumo['saldo_total'] >= 0 ? 'fb-money-pos' : 'fb-money-neg' ?>">
                R$ <?= number_format((float)$metricasResumo['saldo_total'], 2, ',', '.') ?>
            </div>
            <div class="fb-kpi-help">Diferença entre entradas e saídas já consolidadas no relatório.</div>
        </div>
    </div>

    <?php if ((int)$metricasResumo['conta_incompleta'] > 0 || (int)$metricasResumo['saldo_negativo'] > 0): ?>
        <div class="fb-note-box">
            <strong>Atenção executiva:</strong> existem bancos com saldo negativo ou com cadastro bancário incompleto.
            Revise primeiro os itens no topo da grade, pois eles podem afetar remessa, retorno, conciliação e leitura financeira do módulo.
        </div>
    <?php endif; ?>

    <div class="fb-card">
        <div class="fb-card-head">
            <div>
                <h2 class="fb-card-title">Resumo executivo da previsão</h2>
                <div class="fb-card-sub">Leitura rápida para priorização de bancos com maior impacto financeiro.</div>
            </div>

            <div class="fb-pill-list">
                <span class="fb-pill"><i class="fas fa-wallet"></i><?= number_format((int)$metricasResumo['com_movimento'], 0, ',', '.') ?> com movimento</span>
                <span class="fb-pill fb-pill--warn"><i class="fas fa-exclamation-triangle"></i><?= number_format((int)$metricasResumo['conta_incompleta'], 0, ',', '.') ?> incompleto(s)</span>
                <span class="fb-pill fb-pill--danger"><i class="fas fa-arrow-down"></i><?= number_format((int)$metricasResumo['saldo_negativo'], 0, ',', '.') ?> saldo(s) negativo(s)</span>
            </div>
        </div>

        <div class="fb-summary-grid">
            <div class="fb-summary-box">
                <div class="fb-summary-title">Balanço previsto</div>
                <div class="fb-summary-text">
                    O consolidado atual mostra <strong>R$ <?= number_format((float)$metricasResumo['total_receber'], 2, ',', '.') ?></strong> a receber
                    contra <strong>R$ <?= number_format((float)$metricasResumo['total_pagar'], 2, ',', '.') ?></strong> a pagar,
                    com saldo total de <strong>R$ <?= number_format((float)$metricasResumo['saldo_total'], 2, ',', '.') ?></strong>.
                </div>
            </div>

            <div class="fb-summary-box">
                <div class="fb-summary-title">Qualidade cadastral</div>
                <div class="fb-summary-text">
                    Há <strong><?= number_format((int)$metricasResumo['conta_incompleta'], 0, ',', '.') ?></strong> banco(s) com agência ou conta ausentes.
                    Isso reduz a confiabilidade operacional para conciliação, retorno e algumas leituras gerenciais.
                </div>
            </div>

            <div class="fb-summary-box">
                <div class="fb-summary-title">Último movimento geral</div>
                <div class="fb-summary-text">
                    O movimento mais recente identificado no consolidado ocorreu em
                    <strong><?= $ultimoMovimentoGeral ? h($ultimoMovimentoGeral->format('d/m/Y')) : '—' ?></strong>.
                    Use este dado para avaliar bancos com carteira ativa versus bancos sem movimentação recente.
                </div>
            </div>
        </div>
    </div>

    <div class="fb-card">
        <div class="fb-card-head">
            <div>
                <h2 class="fb-card-title">Grade detalhada por banco</h2>
                <div class="fb-card-sub">
                    <?= $temFiltros ? 'Resultado filtrado da previsão consolidada por bancos.' : 'Visão completa e priorizada dos bancos conforme o consolidado financeiro.' ?>
                </div>
            </div>
        </div>

        <?php if (empty($linhas)): ?>
            <div class="fb-zero">
                <i class="fas fa-folder-open"></i>
                Nenhum banco encontrado para consolidar a previsão.
            </div>
        <?php else: ?>
            <div class="fb-table-wrap">
                <table class="fb-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Banco</th>
                            <th>Status</th>
                            <th>Agência</th>
                            <th>Conta</th>
                            <th>A receber</th>
                            <th>Recebido</th>
                            <th>A pagar</th>
                            <th>Pago</th>
                            <th>Saldo previsto</th>
                            <th>Volume</th>
                            <th>Último movimento</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($linhas as $linha): ?>
                            <?php
                            $banco = $linha['banco'];
                            $movPercent = $maiorMovimento > 0 ? (($linha['movimento'] / $maiorMovimento) * 100) : 0;
                            $saldoPercent = $maiorSaldoAbsoluto > 0 ? ((abs((float)$linha['saldo']) / $maiorSaldoAbsoluto) * 100) : 0;
                            ?>
                            <tr>
                                <td>
                                    <span class="fb-code"><?= h($banco->codigo_banco ?: '—') ?></span>
                                    <?php if (!empty($banco->numero_banco) && (string)$banco->numero_banco !== (string)$banco->codigo_banco): ?>
                                        <div class="fb-muted">Núm.: <?= h($banco->numero_banco) ?></div>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="fb-bank-name"><?= h($banco->nome ?: '—') ?></div>
                                    <?php if (!empty($banco->cnab)): ?>
                                        <div class="fb-muted">CNAB: <?= h($banco->cnab) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($banco->codigo_banco_interno)): ?>
                                        <div class="fb-muted">Código interno: <?= h($banco->codigo_banco_interno) ?></div>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span class="<?= h($linha['status_class']) ?>"><?= h($linha['status_label']) ?></span>
                                    <div class="fb-muted" style="margin-top:6px;"><?= h($linha['status_ajuda']) ?></div>
                                    <?php if (empty($banco->ativo)): ?>
                                        <div class="fb-muted" style="margin-top:4px;">Cadastro marcado como inativo.</div>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= h($linha['agencia_fmt']) ?>
                                    <?php if ($linha['agencia_fmt'] === '—'): ?>
                                        <div class="fb-muted">Agência não informada</div>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= h($linha['conta_fmt']) ?>
                                    <?php if ($linha['conta_fmt'] === '—'): ?>
                                        <div class="fb-muted">Conta não informada</div>
                                    <?php endif; ?>
                                </td>

                                <td class="fb-money">R$ <?= number_format((float)$linha['receber'], 2, ',', '.') ?></td>
                                <td class="fb-money">R$ <?= number_format((float)$linha['recebido'], 2, ',', '.') ?></td>
                                <td class="fb-money">R$ <?= number_format((float)$linha['pagar'], 2, ',', '.') ?></td>
                                <td class="fb-money">R$ <?= number_format((float)$linha['pago'], 2, ',', '.') ?></td>

                                <td>
                                    <div class="<?= (float)$linha['saldo'] >= 0 ? 'fb-money-pos' : 'fb-money-neg' ?>">
                                        R$ <?= number_format((float)$linha['saldo'], 2, ',', '.') ?>
                                    </div>
                                    <div class="fb-bar">
                                        <div class="fb-bar-fill <?= (float)$linha['saldo'] < 0 ? 'fb-bar-fill--neg' : '' ?>" style="width:<?= number_format($saldoPercent, 2, '.', '') ?>%;"></div>
                                    </div>
                                    <div class="fb-muted">Intensidade relativa do saldo</div>
                                </td>

                                <td>
                                    <strong>R$ <?= number_format((float)$linha['movimento'], 2, ',', '.') ?></strong>
                                    <div class="fb-bar">
                                        <div class="fb-bar-fill" style="width:<?= number_format($movPercent, 2, '.', '') ?>%;"></div>
                                    </div>
                                    <div class="fb-muted">Volume financeiro consolidado</div>
                                </td>

                                <td>
                                    <?php if (!empty($linha['ultimo_movimento'])): ?>
                                        <?= h($linha['ultimo_movimento']->format('d/m/Y')) ?>
                                        <div class="fb-muted">Último recebimento identificado</div>
                                    <?php else: ?>
                                        <span class="fb-muted">Sem movimento registrado</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="fb-row-actions">
                                        <?= $this->Html->link(
                                            'Editar banco',
                                            ['controller' => 'FinanceiroBancos', 'action' => 'edit', $banco->id],
                                            ['class' => 'btn btn-xs btn-default']
                                        ) ?>
                                        <?= $this->Html->link(
                                            'Recebimentos',
                                            ['controller' => 'FinanceiroBancos', 'action' => 'previsaoRecebimentosPorBanco', '?' => ['codigo' => $banco->codigo_banco]],
                                            ['class' => 'btn btn-xs btn-pgm btn-pgm-situacao']
                                        ) ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="fb-total-row">
                            <td colspan="5" class="fb-total">Totais</td>
                            <td>R$ <?= number_format((float)$metricasResumo['total_receber'], 2, ',', '.') ?></td>
                            <td>R$ <?= number_format((float)$metricasResumo['total_recebido'], 2, ',', '.') ?></td>
                            <td>R$ <?= number_format((float)$metricasResumo['total_pagar'], 2, ',', '.') ?></td>
                            <td>R$ <?= number_format((float)$metricasResumo['total_pago'], 2, ',', '.') ?></td>
                            <td class="<?= (float)$metricasResumo['saldo_total'] >= 0 ? 'fb-money-pos' : 'fb-money-neg' ?>">
                                R$ <?= number_format((float)$metricasResumo['saldo_total'], 2, ',', '.') ?>
                            </td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <p class="fb-footer-note">
        Esta tela foi reorganizada para apoiar leitura executiva e ação operacional no mesmo fluxo:
        filtre os bancos, identifique rapidamente saldos negativos ou cadastros incompletos,
        e use os atalhos da grade para aprofundar a análise do banco ou abrir a previsão de recebimentos relacionada.
    </p>
</div>
