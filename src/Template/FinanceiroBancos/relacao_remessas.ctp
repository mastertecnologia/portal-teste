<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Financeiro', ['controller' => 'Financeiro', 'action' => 'index']);
$this->Breadcrumbs->add('Bancos', ['controller' => 'FinanceiroBancos', 'action' => 'index']);
$this->Breadcrumbs->add('Relatórios', ['controller' => 'FinanceiroBancos', 'action' => 'relatorios']);
$this->Breadcrumbs->add('Relação de Remessas Bancárias');

$resumo = $resumo ?? [];
$codigo = $codigo ?? '';
$nome = $nome ?? '';
$situacao = $situacao ?? '';
$metricasRemessas = $metricasRemessas ?? [
    'bancos' => count($resumo),
    'com_remessa' => 0,
    'sem_remessa' => 0,
    'conta_incompleta' => 0,
    'qtd_titulos' => 0,
    'valor_total' => 0.0,
];

$linhas = [];
$maiorQuantidade = 0;
$maiorValor = 0.0;
$proximoVencimentoGeral = null;
$ultimoRecebimentoGeral = null;

foreach ($resumo as $item) {
    $banco = $item['banco'] ?? null;
    if (!$banco) {
        continue;
    }

    $quantidade = (int)($item['quantidade'] ?? 0);
    $total = (float)($item['total'] ?? 0);
    $contaIncompleta = !empty($item['conta_incompleta']);
    $proximoVencimento = $item['proximo_vencimento'] ?? null;
    $ultimoRecebimento = $item['ultimo_recebimento'] ?? null;

    if ($quantidade > $maiorQuantidade) {
        $maiorQuantidade = $quantidade;
    }
    if ($total > $maiorValor) {
        $maiorValor = $total;
    }

    if (!empty($proximoVencimento) && ($proximoVencimentoGeral === null || $proximoVencimento < $proximoVencimentoGeral)) {
        $proximoVencimentoGeral = $proximoVencimento;
    }

    if (!empty($ultimoRecebimento) && ($ultimoRecebimentoGeral === null || $ultimoRecebimento > $ultimoRecebimentoGeral)) {
        $ultimoRecebimentoGeral = $ultimoRecebimento;
    }

    $statusLabel = 'Sem títulos pendentes';
    $statusClass = 'fb-badge fb-badge--neutral';
    $prioridade = 4;
    $statusAjuda = 'Sem carteira aberta para geração de remessa neste momento.';

    if ($quantidade > 0 && $contaIncompleta) {
        $statusLabel = 'Remessa com atenção';
        $statusClass = 'fb-badge fb-badge--warn';
        $prioridade = 1;
        $statusAjuda = 'Há títulos em aberto, mas o cadastro bancário precisa de revisão.';
    } elseif ($quantidade > 0) {
        $statusLabel = 'Com remessa prevista';
        $statusClass = 'fb-badge fb-badge--ok';
        $prioridade = 2;
        $statusAjuda = 'Carteira pronta para análise operacional e geração da remessa.';
    } elseif ($contaIncompleta) {
        $statusLabel = 'Conta incompleta';
        $statusClass = 'fb-badge fb-badge--warn';
        $prioridade = 3;
        $statusAjuda = 'Sem títulos agora, mas vale corrigir agência e conta para uso futuro.';
    }

    $agenciaFmt = trim((string)($banco->numero_agencia ?? ''));
    if ($agenciaFmt !== '' && !empty($banco->digito_agencia)) {
        $agenciaFmt .= '-' . trim((string)$banco->digito_agencia);
    }
    if ($agenciaFmt === '') {
        $agenciaFmt = '—';
    }

    $contaFmt = trim((string)($banco->numero_conta ?? ''));
    if ($contaFmt !== '' && !empty($banco->digito_conta)) {
        $contaFmt .= '-' . trim((string)$banco->digito_conta);
    }
    if ($contaFmt === '') {
        $contaFmt = '—';
    }

    $linhas[] = [
        'banco' => $banco,
        'quantidade' => $quantidade,
        'total' => $total,
        'conta_incompleta' => $contaIncompleta,
        'proximo_vencimento' => $proximoVencimento,
        'ultimo_recebimento' => $ultimoRecebimento,
        'status_label' => $statusLabel,
        'status_class' => $statusClass,
        'status_ajuda' => $statusAjuda,
        'prioridade' => $prioridade,
        'agencia_fmt' => $agenciaFmt,
        'conta_fmt' => $contaFmt,
    ];
}

usort($linhas, function ($a, $b) {
    if ($a['prioridade'] !== $b['prioridade']) {
        return $a['prioridade'] <=> $b['prioridade'];
    }

    if ($a['quantidade'] !== $b['quantidade']) {
        return $b['quantidade'] <=> $a['quantidade'];
    }

    if ((float)$a['total'] !== (float)$b['total']) {
        return ((float)$b['total'] <=> (float)$a['total']);
    }

    return strcmp((string)($a['banco']->nome ?? ''), (string)($b['banco']->nome ?? ''));
});

$temFiltros = $codigo !== '' || $nome !== '' || $situacao !== '';
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
.fb-card,
.fb-kpi {
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
    grid-template-columns:minmax(160px, 180px) minmax(260px, 1fr) minmax(220px, 240px) auto auto;
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

.fb-kpi-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));
    gap:14px;
    padding:20px 24px 0;
}
.fb-kpi {
    padding:16px 18px;
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
.fb-kpi-help {
    margin-top:8px;
    color:#8b949e;
    font-size:11.5px;
    line-height:1.5;
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
.fb-pill--muted {
    background:rgba(255,255,255,.06);
    color:#9ca3af;
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
    min-width:1380px;
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
.fb-money {
    white-space:nowrap;
    font-weight:600;
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
.fb-badge--neutral {
    background:rgba(255,255,255,.08);
    color:#9ca3af;
}
.fb-row-actions {
    display:flex;
    gap:6px;
    flex-wrap:wrap;
}
.fb-zero {
    text-align:center;
    padding:40px 20px;
    color:#7d8590;
}
.fb-zero i {
    display:block;
    font-size:32px;
    margin-bottom:10px;
    opacity:.35;
    color:#5cdbc0;
}
.fb-total {
    text-align:right;
    font-weight:700;
    color:#5cdbc0;
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
            <h1 class="fb-h1"><i class="fas fa-file-export fb-h1-ico"></i>Relação de Remessas Bancárias</h1>
            <div class="fb-sub">Resumo operacional dos títulos a receber em aberto agrupados por banco, com foco em priorização de remessa.</div>
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

    <div class="fb-filters-card">
        <h2 class="fb-filters-title">Filtros da relação</h2>

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
                        'com_remessa' => 'Com remessa prevista',
                        'sem_remessa' => 'Sem remessa',
                        'conta_incompleta' => 'Conta incompleta',
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
                        ['controller' => 'FinanceiroBancos', 'action' => 'relacaoRemessas'],
                        ['class' => 'btn btn-default btn-sm']
                    ) ?>
                </div>
            </div>
        <?= $this->Form->end() ?>

        <div class="fb-filter-note">
            A grade é priorizada automaticamente pelos bancos com maior necessidade operacional:
            primeiro os que têm remessa com atenção, depois os com carteira pronta, em seguida os com conta incompleta e por fim os sem títulos pendentes.
        </div>
    </div>

    <div class="fb-kpi-grid">
        <div class="fb-kpi">
            <div class="fb-kpi-label">Bancos no relatório</div>
            <div class="fb-kpi-value"><?= number_format((int)$metricasRemessas['bancos'], 0, ',', '.') ?></div>
            <div class="fb-kpi-help">Quantidade de bancos retornados após a aplicação dos filtros atuais.</div>
        </div>

        <div class="fb-kpi">
            <div class="fb-kpi-label">Com remessa prevista</div>
            <div class="fb-kpi-value"><?= number_format((int)$metricasRemessas['com_remessa'], 0, ',', '.') ?></div>
            <div class="fb-kpi-help">Bancos com títulos em aberto e carteira pronta para análise de remessa.</div>
        </div>

        <div class="fb-kpi">
            <div class="fb-kpi-label">Qtd. total de títulos</div>
            <div class="fb-kpi-value"><?= number_format((int)$metricasRemessas['qtd_titulos'], 0, ',', '.') ?></div>
            <div class="fb-kpi-help">Soma de títulos abertos vinculados aos bancos do relatório.</div>
        </div>

        <div class="fb-kpi">
            <div class="fb-kpi-label">Valor total previsto</div>
            <div class="fb-kpi-value">R$ <?= number_format((float)$metricasRemessas['valor_total'], 2, ',', '.') ?></div>
            <div class="fb-kpi-help">Valor financeiro total das remessas potenciais encontradas.</div>
        </div>

        <div class="fb-kpi">
            <div class="fb-kpi-label">Conta incompleta</div>
            <div class="fb-kpi-value"><?= number_format((int)$metricasRemessas['conta_incompleta'], 0, ',', '.') ?></div>
            <div class="fb-kpi-help">Bancos que exigem revisão de agência/conta para operação bancária completa.</div>
        </div>

        <div class="fb-kpi">
            <div class="fb-kpi-label">Próximo vencimento geral</div>
            <div class="fb-kpi-value" style="font-size:20px;">
                <?= $proximoVencimentoGeral ? h($proximoVencimentoGeral->format('d/m/Y')) : '—' ?>
            </div>
            <div class="fb-kpi-help">Data mais próxima entre os títulos previstos na relação atual.</div>
        </div>
    </div>

    <?php if ($metricasRemessas['conta_incompleta'] > 0): ?>
        <div class="fb-note-box">
            <strong>Atenção:</strong> existem bancos com carteira potencial de remessa e cadastro bancário incompleto.
            Revise agência e conta antes de avançar com retorno, conciliação e geração operacional das remessas.
        </div>
    <?php endif; ?>

    <div class="fb-card">
        <div class="fb-card-head">
            <div>
                <h2 class="fb-card-title">Resumo executivo da carteira</h2>
                <div class="fb-card-sub">Indicadores rápidos para leitura financeira e operacional da relação de remessas.</div>
            </div>

            <div class="fb-pill-list">
                <span class="fb-pill"><i class="fas fa-layer-group"></i><?= number_format((int)$metricasRemessas['bancos'], 0, ',', '.') ?> banco(s)</span>
                <span class="fb-pill"><i class="fas fa-file-invoice-dollar"></i><?= number_format((int)$metricasRemessas['qtd_titulos'], 0, ',', '.') ?> título(s)</span>
                <span class="fb-pill fb-pill--warn"><i class="fas fa-exclamation-triangle"></i><?= number_format((int)$metricasRemessas['conta_incompleta'], 0, ',', '.') ?> com atenção</span>
            </div>
        </div>

        <div class="fb-summary-grid">
            <div class="fb-summary-box">
                <div class="fb-summary-title">Priorização da rotina</div>
                <div class="fb-summary-text">
                    Comece pelos bancos com <strong>remessa com atenção</strong>, pois eles combinam carteira aberta com necessidade de ajuste cadastral.
                    Em seguida, avance para os bancos com remessa prevista e cadastro completo.
                </div>
            </div>

            <div class="fb-summary-box">
                <div class="fb-summary-title">Maior concentração</div>
                <div class="fb-summary-text">
                    A maior carteira atual possui <strong><?= number_format((int)$maiorQuantidade, 0, ',', '.') ?></strong> título(s)
                    e o maior valor consolidado por banco chega a <strong>R$ <?= number_format((float)$maiorValor, 2, ',', '.') ?></strong>.
                </div>
            </div>

            <div class="fb-summary-box">
                <div class="fb-summary-title">Rastreabilidade financeira</div>
                <div class="fb-summary-text">
                    O último recebimento identificado na amostra atual foi em
                    <strong><?= $ultimoRecebimentoGeral ? h($ultimoRecebimentoGeral->format('d/m/Y')) : '—' ?></strong>.
                    Isso ajuda a comparar bancos com carteira aberta versus histórico recente de liquidação.
                </div>
            </div>
        </div>
    </div>

    <div class="fb-card">
        <div class="fb-card-head">
            <div>
                <h2 class="fb-card-title">Grade detalhada por banco</h2>
                <div class="fb-card-sub">
                    <?= $temFiltros ? 'Resultado filtrado da relação de remessas bancárias.' : 'Visão completa e priorizada dos bancos ativos com potencial de remessa.' ?>
                </div>
            </div>
        </div>

        <?php if (empty($linhas)): ?>
            <div class="fb-zero">
                <i class="fas fa-folder-open"></i>
                Nenhuma previsão de remessa bancária encontrada.
            </div>
        <?php else: ?>
            <div class="fb-table-wrap">
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
                            <th>Próx. vencimento</th>
                            <th>Últ. recebimento</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($linhas as $linha): ?>
                            <?php
                            $banco = $linha['banco'];
                            $qtdPercent = $maiorQuantidade > 0 ? (($linha['quantidade'] / $maiorQuantidade) * 100) : 0;
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
                                    <?php if (!empty($banco->codigo_banco_interno)): ?>
                                        <div class="fb-muted">Código interno: <?= h($banco->codigo_banco_interno) ?></div>
                                    <?php endif; ?>
                                    <?php if ($linha['conta_incompleta']): ?>
                                        <div class="fb-muted">Cadastro incompleto para conciliação/retorno.</div>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= h($banco->cnab ?: '—') ?>
                                    <?php if (empty($banco->cnab)): ?>
                                        <div class="fb-muted">CNAB não informado</div>
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

                                <td>
                                    <strong><?= number_format((int)$linha['quantidade'], 0, ',', '.') ?></strong>
                                    <?php if ((int)$linha['quantidade'] > 0): ?>
                                        <div class="fb-bar">
                                            <div class="fb-bar-fill" style="width:<?= number_format($qtdPercent, 2, '.', '') ?>%;"></div>
                                        </div>
                                        <div class="fb-muted">Participação relativa na carteira aberta</div>
                                    <?php else: ?>
                                        <div class="fb-muted">Sem títulos em aberto</div>
                                    <?php endif; ?>
                                </td>

                                <td class="fb-money">
                                    R$ <?= number_format((float)$linha['total'], 2, ',', '.') ?>
                                </td>

                                <td>
                                    <?php if (!empty($linha['proximo_vencimento'])): ?>
                                        <?= h($linha['proximo_vencimento']->format('d/m/Y')) ?>
                                        <div class="fb-muted">Título mais próximo para cobrança/remessa</div>
                                    <?php else: ?>
                                        <span class="fb-muted">Sem vencimento previsto</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (!empty($linha['ultimo_recebimento'])): ?>
                                        <?= h($linha['ultimo_recebimento']->format('d/m/Y')) ?>
                                        <div class="fb-muted">Última liquidação registrada</div>
                                    <?php else: ?>
                                        <span class="fb-muted">Sem recebimento encontrado</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span class="<?= h($linha['status_class']) ?>"><?= h($linha['status_label']) ?></span>
                                    <div class="fb-muted" style="margin-top:6px;"><?= h($linha['status_ajuda']) ?></div>
                                </td>

                                <td>
                                    <div class="fb-row-actions">
                                        <?= $this->Html->link(
                                            'Ver remessa',
                                            ['controller' => 'FinanceiroBancos', 'action' => 'remessa', '?' => ['banco_id' => $banco->id]],
                                            ['class' => 'btn btn-xs btn-pgm btn-pgm-salvar']
                                        ) ?>

                                        <?= $this->Html->link(
                                            'Editar banco',
                                            ['controller' => 'FinanceiroBancos', 'action' => 'edit', $banco->id],
                                            ['class' => 'btn btn-xs btn-default']
                                        ) ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" class="fb-total">Totais</td>
                            <td class="fb-total"><?= number_format((int)$metricasRemessas['qtd_titulos'], 0, ',', '.') ?></td>
                            <td class="fb-total">R$ <?= number_format((float)$metricasRemessas['valor_total'], 2, ',', '.') ?></td>
                            <td colspan="4"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <p class="fb-footer-note">
        Esta relação foi organizada para apoiar a rotina de remessa com leitura mais prática: filtre a carteira,
        priorize bancos com atenção cadastral e use os atalhos da grade para abrir a remessa ou ajustar rapidamente o cadastro bancário.
    </p>
</div>
