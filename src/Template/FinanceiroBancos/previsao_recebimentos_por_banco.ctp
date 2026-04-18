<?php
/**
 * Financeiro > Bancos
 * Relatório: Previsão de Recebimentos por Banco
 *
 * Variáveis esperadas:
 * - array $previsao
 * - string $codigo
 * - string $nome
 * - string $situacao
 * - array $metricasPrevisao
 */

$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Financeiro', ['controller' => 'Financeiro', 'action' => 'index']);
$this->Breadcrumbs->add('Bancos', ['controller' => 'FinanceiroBancos', 'action' => 'index']);
$this->Breadcrumbs->add('Relatórios', ['controller' => 'FinanceiroBancos', 'action' => 'relatorios']);
$this->Breadcrumbs->add('Previsão de recebimentos por banco');

$previsao = $previsao ?? [];
$codigo = $codigo ?? '';
$nome = $nome ?? '';
$situacao = $situacao ?? '';
$metricasPrevisao = $metricasPrevisao ?? [
    'bancos' => count($previsao),
    'com_previsao' => 0,
    'sem_previsao' => 0,
    'conta_incompleta' => 0,
    'qtd_titulos' => 0,
    'valor_total' => 0.0,
    'vence_hoje' => 0,
    'vence_semana' => 0,
    'vencidos' => 0,
];

$linhas = [];
$maiorQtd = 0;
$maiorValor = 0.0;
$maisProximoVencimento = null;
$temFiltros = $codigo !== '' || $nome !== '' || $situacao !== '';

foreach ($previsao as $item) {
    $banco = $item['banco'] ?? null;
    if (!$banco) {
        continue;
    }

    $qtd = (int)($item['qtd'] ?? 0);
    $total = (float)($item['total'] ?? 0);
    $proximoVencimento = $item['proximo_vencimento'] ?? null;
    $dias = $item['dias_para_proximo_vencimento'] ?? null;
    $contaIncompleta = !empty($item['conta_incompleta']);

    if ($qtd > $maiorQtd) {
        $maiorQtd = $qtd;
    }
    if ($total > $maiorValor) {
        $maiorValor = $total;
    }

    if (!empty($proximoVencimento) && ($maisProximoVencimento === null || $proximoVencimento < $maisProximoVencimento)) {
        $maisProximoVencimento = $proximoVencimento;
    }

    $criticidadeLabel = 'Sem previsão';
    $criticidadeClasse = 'fb-rel-badge fb-rel-badge--neutral';
    $criticidadePeso = 5;
    $criticidadeTexto = 'Sem títulos em aberto para este banco no momento.';

    if ($qtd > 0 && $dias !== null && (int)$dias < 0) {
        $criticidadeLabel = 'Vencido';
        $criticidadeClasse = 'fb-rel-badge fb-rel-badge--danger';
        $criticidadePeso = 1;
        $criticidadeTexto = 'Há títulos já vencidos vinculados a este banco.';
    } elseif ($qtd > 0 && $dias !== null && (int)$dias === 0) {
        $criticidadeLabel = 'Vence hoje';
        $criticidadeClasse = 'fb-rel-badge fb-rel-badge--danger';
        $criticidadePeso = 2;
        $criticidadeTexto = 'O próximo vencimento deste banco ocorre hoje.';
    } elseif ($qtd > 0 && $dias !== null && (int)$dias >= 0 && (int)$dias <= 7) {
        $criticidadeLabel = 'Vence na semana';
        $criticidadeClasse = 'fb-rel-badge fb-rel-badge--warn';
        $criticidadePeso = 3;
        $criticidadeTexto = 'O próximo vencimento está dentro dos próximos 7 dias.';
    } elseif ($qtd > 0) {
        $criticidadeLabel = 'Com previsão';
        $criticidadeClasse = 'fb-rel-badge fb-rel-badge--ok';
        $criticidadePeso = 4;
        $criticidadeTexto = 'Existe carteira prevista sem urgência imediata.';
    }

    if ($qtd > 0 && $contaIncompleta) {
        $criticidadeTexto .= ' Cadastro bancário incompleto exige revisão.';
    }

    $agencia = !empty($banco->numero_agencia) ? $banco->numero_agencia : '—';
    if (!empty($banco->digito_agencia) && $agencia !== '—') {
        $agencia .= '-' . $banco->digito_agencia;
    }

    $conta = !empty($banco->numero_conta) ? $banco->numero_conta : '—';
    if (!empty($banco->digito_conta) && $conta !== '—') {
        $conta .= '-' . $banco->digito_conta;
    }

    $linhas[] = [
        'banco' => $banco,
        'qtd' => $qtd,
        'total' => $total,
        'proximo_vencimento' => $proximoVencimento,
        'dias' => $dias,
        'conta_incompleta' => $contaIncompleta,
        'criticidade_label' => $criticidadeLabel,
        'criticidade_classe' => $criticidadeClasse,
        'criticidade_peso' => $criticidadePeso,
        'criticidade_texto' => $criticidadeTexto,
        'agencia' => $agencia,
        'conta' => $conta,
    ];
}

usort($linhas, function ($a, $b) {
    if ($a['criticidade_peso'] !== $b['criticidade_peso']) {
        return $a['criticidade_peso'] <=> $b['criticidade_peso'];
    }

    if ($a['qtd'] !== $b['qtd']) {
        return $b['qtd'] <=> $a['qtd'];
    }

    if ((float)$a['total'] !== (float)$b['total']) {
        return ((float)$b['total'] <=> (float)$a['total']);
    }

    return strcmp((string)($a['banco']->nome ?? ''), (string)($b['banco']->nome ?? ''));
});
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
.fb-rel-sub {
    color:#7d8590;
    font-size:12.5px;
    margin-top:4px;
}
.fb-rel-actions {
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}

.fb-rel-filters-card,
.fb-rel-kpi,
.fb-rel-card {
    background:#161b22;
    border:1px solid rgba(255,255,255,.07);
    border-radius:10px;
}

.fb-rel-filters-card {
    margin:20px 24px 0;
    padding:16px 18px;
}
.fb-rel-filters-title {
    color:#e6edf3;
    font-size:14px;
    font-weight:600;
    margin:0 0 12px;
}
.fb-rel-filters-grid {
    display:grid;
    grid-template-columns:minmax(170px,190px) minmax(250px,1fr) minmax(220px,240px) auto auto;
    gap:12px;
    align-items:end;
}
.fb-rel-field label {
    display:block;
    color:#7d8590;
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:.05em;
    font-weight:600;
    margin-bottom:4px;
}
.fb-rel-field .form-control {
    height:36px;
    border-radius:8px;
    border:1px solid rgba(255,255,255,.08);
    background:#0f141a;
    color:#e6edf3;
    box-shadow:none;
}
.fb-rel-filter-actions {
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}
.fb-rel-filter-note {
    margin-top:10px;
    color:#8b949e;
    font-size:12px;
    line-height:1.55;
}

.fb-rel-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));
    gap:14px;
    padding:20px 24px 0;
}
.fb-rel-kpi {
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
.fb-rel-kpi-help {
    margin-top:8px;
    color:#8b949e;
    font-size:11.5px;
    line-height:1.45;
}

.fb-rel-note {
    margin:16px 24px 0;
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

.fb-rel-card {
    padding:18px 20px;
    margin:16px 24px;
}
.fb-rel-card-head {
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:12px;
    margin-bottom:14px;
    flex-wrap:wrap;
}
.fb-rel-card-title {
    color:#e6edf3;
    font-size:15px;
    font-weight:600;
    margin:0;
}
.fb-rel-card-sub {
    color:#7d8590;
    font-size:12px;
    margin-top:4px;
    line-height:1.55;
}
.fb-rel-pill-list {
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}
.fb-rel-pill {
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
.fb-rel-pill--warn {
    background:rgba(255,193,7,.12);
    color:#ffd166;
}
.fb-rel-pill--danger {
    background:rgba(248,81,73,.14);
    color:#ff8f88;
}

.fb-rel-summary-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
    gap:14px;
}
.fb-rel-summary-box {
    background:rgba(255,255,255,.02);
    border:1px solid rgba(255,255,255,.06);
    border-radius:10px;
    padding:14px 16px;
}
.fb-rel-summary-title {
    color:#e6edf3;
    font-size:13px;
    font-weight:700;
    margin-bottom:6px;
}
.fb-rel-summary-text {
    color:#8b949e;
    font-size:12px;
    line-height:1.6;
}

.fb-rel-table-wrap {
    overflow:auto;
}
.fb-rel-table {
    width:100%;
    border-collapse:collapse;
    font-size:13px;
    min-width:1320px;
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
    vertical-align:top;
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
.fb-rel-code {
    display:inline-block;
    padding:2px 7px;
    border-radius:6px;
    background:rgba(92,219,192,.10);
    color:#5cdbc0;
    font-weight:700;
    font-size:12px;
}
.fb-rel-bank-name {
    font-weight:600;
    color:#e6edf3;
}
.fb-rel-muted {
    color:#7d8590;
    font-size:11px;
    line-height:1.45;
}
.fb-rel-badge {
    display:inline-block;
    padding:3px 8px;
    border-radius:999px;
    font-size:11px;
    font-weight:700;
}
.fb-rel-badge--ok {
    background:rgba(63,185,80,.13);
    color:#3fb950;
}
.fb-rel-badge--warn {
    background:rgba(255,193,7,.14);
    color:#ffd166;
}
.fb-rel-badge--danger {
    background:rgba(248,81,73,.16);
    color:#ff8f88;
}
.fb-rel-badge--neutral {
    background:rgba(255,255,255,.08);
    color:#9ca3af;
}
.fb-rel-money {
    white-space:nowrap;
    font-weight:600;
}
.fb-rel-bar {
    width:100%;
    height:8px;
    border-radius:999px;
    background:rgba(255,255,255,.06);
    overflow:hidden;
    margin-top:6px;
}
.fb-rel-bar-fill {
    height:100%;
    border-radius:999px;
    background:linear-gradient(90deg, #1d9e75, #5cdbc0);
}
.fb-rel-row-actions {
    display:flex;
    gap:6px;
    flex-wrap:wrap;
}
.fb-rel-empty {
    text-align:center;
    color:#7d8590;
    padding:36px 10px;
}
.fb-rel-empty i {
    display:block;
    font-size:32px;
    margin-bottom:10px;
    opacity:.4;
    color:#5cdbc0;
}
.fb-rel-footer-note {
    margin:0 24px 24px;
    color:#8b949e;
    font-size:12px;
    line-height:1.7;
}

@media (max-width: 1180px) {
    .fb-rel-filters-grid {
        grid-template-columns:1fr 1fr;
    }
}
@media (max-width: 760px) {
    .fb-rel-filters-grid,
    .fb-rel-summary-grid {
        grid-template-columns:1fr;
    }
}
</style>

<div class="fb-rel-root">
    <div class="fb-rel-topbar">
        <div>
            <h1 class="fb-rel-h1">
                <i class="fas fa-coins fb-rel-h1-ico"></i>
                Previsão de Recebimentos por Banco
            </h1>
            <div class="fb-rel-sub">
                Painel de previsão com filtros, criticidade por vencimento e leitura operacional da carteira a receber por banco.
            </div>
        </div>

        <div class="fb-rel-actions">
            <?= $this->Html->link(
                '<i class="fas fa-chart-bar"></i> Relatórios bancários',
                ['controller' => 'FinanceiroBancos', 'action' => 'relatorios'],
                ['class' => 'btn btn-pgm btn-pgm-situacao btn-sm', 'escape' => false]
            ) ?>
            <?= $this->Html->link(
                '<i class="fas fa-file-export"></i> Remessas',
                ['controller' => 'FinanceiroBancos', 'action' => 'relacaoRemessas'],
                ['class' => 'btn btn-default btn-sm', 'escape' => false]
            ) ?>
            <?= $this->Html->link(
                '<i class="fas fa-arrow-left"></i> Bancos',
                ['controller' => 'FinanceiroBancos', 'action' => 'index'],
                ['class' => 'btn btn-default btn-sm', 'escape' => false]
            ) ?>
        </div>
    </div>

    <div class="fb-rel-filters-card">
        <h2 class="fb-rel-filters-title">Filtros da previsão</h2>

        <?= $this->Form->create(null, ['type' => 'get']) ?>
            <div class="fb-rel-filters-grid">
                <div class="fb-rel-field">
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

                <div class="fb-rel-field">
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

                <div class="fb-rel-field">
                    <label for="filtro-situacao">Situação</label>
                    <?= $this->Form->select('situacao', [
                        '' => 'Todas',
                        'com_previsao' => 'Com previsão',
                        'sem_previsao' => 'Sem previsão',
                        'conta_incompleta' => 'Conta incompleta',
                        'vence_hoje' => 'Vence hoje',
                        'vence_semana' => 'Vence na semana',
                        'vencido' => 'Vencido',
                    ], [
                        'label' => false,
                        'id' => 'filtro-situacao',
                        'class' => 'form-control',
                        'value' => $situacao,
                        'templates' => ['inputContainer' => '{{content}}']
                    ]) ?>
                </div>

                <div class="fb-rel-filter-actions">
                    <?= $this->Form->button('<i class="fas fa-search"></i> Filtrar', [
                        'type' => 'submit',
                        'class' => 'btn btn-pgm btn-pgm-salvar btn-sm',
                        'escape' => false,
                    ]) ?>
                </div>

                <div class="fb-rel-filter-actions">
                    <?= $this->Html->link(
                        'Limpar',
                        ['controller' => 'FinanceiroBancos', 'action' => 'previsaoRecebimentosPorBanco'],
                        ['class' => 'btn btn-default btn-sm']
                    ) ?>
                </div>
            </div>
        <?= $this->Form->end() ?>

        <div class="fb-rel-filter-note">
            A listagem é ordenada automaticamente por criticidade: primeiro bancos com títulos vencidos, depois os que vencem hoje,
            os que vencem na semana, os demais com previsão e, por último, os sem carteira em aberto.
        </div>
    </div>

    <div class="fb-rel-grid">
        <div class="fb-rel-kpi">
            <div class="fb-rel-kpi-label">Bancos no relatório</div>
            <div class="fb-rel-kpi-value"><?= number_format((int)$metricasPrevisao['bancos'], 0, ',', '.') ?></div>
            <div class="fb-rel-kpi-help">Quantidade de bancos retornados conforme os filtros atuais.</div>
        </div>

        <div class="fb-rel-kpi">
            <div class="fb-rel-kpi-label">Com previsão</div>
            <div class="fb-rel-kpi-value"><?= number_format((int)$metricasPrevisao['com_previsao'], 0, ',', '.') ?></div>
            <div class="fb-rel-kpi-help">Bancos com títulos a receber em aberto vinculados.</div>
        </div>

        <div class="fb-rel-kpi">
            <div class="fb-rel-kpi-label">Qtd. títulos em aberto</div>
            <div class="fb-rel-kpi-value"><?= number_format((int)$metricasPrevisao['qtd_titulos'], 0, ',', '.') ?></div>
            <div class="fb-rel-kpi-help">Soma total da carteira prevista entre os bancos filtrados.</div>
        </div>

        <div class="fb-rel-kpi">
            <div class="fb-rel-kpi-label">Previsão total</div>
            <div class="fb-rel-kpi-value">R$ <?= number_format((float)$metricasPrevisao['valor_total'], 2, ',', '.') ?></div>
            <div class="fb-rel-kpi-help">Valor financeiro previsto dos recebimentos em aberto.</div>
        </div>

        <div class="fb-rel-kpi">
            <div class="fb-rel-kpi-label">Vence hoje</div>
            <div class="fb-rel-kpi-value"><?= number_format((int)$metricasPrevisao['vence_hoje'], 0, ',', '.') ?></div>
            <div class="fb-rel-kpi-help">Bancos cujo próximo vencimento ocorre hoje.</div>
        </div>

        <div class="fb-rel-kpi">
            <div class="fb-rel-kpi-label">Vencidos</div>
            <div class="fb-rel-kpi-value"><?= number_format((int)$metricasPrevisao['vencidos'], 0, ',', '.') ?></div>
            <div class="fb-rel-kpi-help">Bancos com próximo vencimento já ultrapassado.</div>
        </div>
    </div>

    <?php if ((int)$metricasPrevisao['conta_incompleta'] > 0): ?>
        <div class="fb-rel-note">
            <strong>Atenção:</strong> há bancos com previsão financeira e cadastro bancário incompleto.
            Revise agência e conta para evitar impacto operacional em conciliação, remessa e leitura dos recebimentos.
        </div>
    <?php endif; ?>

    <div class="fb-rel-card">
        <div class="fb-rel-card-head">
            <div>
                <h2 class="fb-rel-card-title">Resumo executivo da previsão</h2>
                <div class="fb-rel-card-sub">Leitura rápida da carteira, urgência de vencimento e qualidade cadastral dos bancos.</div>
            </div>

            <div class="fb-rel-pill-list">
                <span class="fb-rel-pill"><i class="fas fa-layer-group"></i><?= number_format((int)$metricasPrevisao['bancos'], 0, ',', '.') ?> banco(s)</span>
                <span class="fb-rel-pill fb-rel-pill--warn"><i class="fas fa-calendar-week"></i><?= number_format((int)$metricasPrevisao['vence_semana'], 0, ',', '.') ?> vence(m) na semana</span>
                <span class="fb-rel-pill fb-rel-pill--danger"><i class="fas fa-exclamation-circle"></i><?= number_format((int)$metricasPrevisao['vencidos'], 0, ',', '.') ?> vencido(s)</span>
            </div>
        </div>

        <div class="fb-rel-summary-grid">
            <div class="fb-rel-summary-box">
                <div class="fb-rel-summary-title">Urgência de cobrança</div>
                <div class="fb-rel-summary-text">
                    O próximo vencimento geral está em
                    <strong><?= $maisProximoVencimento ? h($maisProximoVencimento->format('d/m/Y')) : '—' ?></strong>.
                    Priorize bancos com status <strong>Vencido</strong> e <strong>Vence hoje</strong>.
                </div>
            </div>

            <div class="fb-rel-summary-box">
                <div class="fb-rel-summary-title">Concentração da carteira</div>
                <div class="fb-rel-summary-text">
                    O maior banco da amostra possui <strong><?= number_format((int)$maiorQtd, 0, ',', '.') ?></strong> título(s)
                    e o maior valor individual chega a <strong>R$ <?= number_format((float)$maiorValor, 2, ',', '.') ?></strong>.
                </div>
            </div>

            <div class="fb-rel-summary-box">
                <div class="fb-rel-summary-title">Leitura operacional</div>
                <div class="fb-rel-summary-text">
                    Use os filtros para separar bancos sem previsão, localizar vencimentos críticos e identificar
                    cadastros que ainda precisam de ajuste antes de avançar com as rotinas bancárias.
                </div>
            </div>
        </div>
    </div>

    <div class="fb-rel-card">
        <div class="fb-rel-card-head">
            <div>
                <h2 class="fb-rel-card-title">Recebimentos previstos por banco</h2>
                <div class="fb-rel-card-sub">
                    <?= $temFiltros ? 'Resultado filtrado da previsão de recebimentos.' : 'Visão completa e priorizada da previsão de recebimentos por banco.' ?>
                </div>
            </div>
        </div>

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
                        <th>Dias</th>
                        <th>Total previsto</th>
                        <th>Criticidade</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($linhas)): ?>
                        <tr>
                            <td colspan="10" class="fb-rel-empty">
                                <i class="fas fa-search-dollar"></i>
                                Nenhuma previsão de recebimento encontrada por banco.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($linhas as $linha): ?>
                            <?php
                            $banco = $linha['banco'];
                            $qtdPercent = $maiorQtd > 0 ? (($linha['qtd'] / $maiorQtd) * 100) : 0;
                            ?>
                            <tr>
                                <td>
                                    <span class="fb-rel-code"><?= h($banco->codigo_banco ?? '—') ?></span>
                                    <?php if (!empty($banco->numero_banco) && (string)$banco->numero_banco !== (string)$banco->codigo_banco): ?>
                                        <div class="fb-rel-muted">Núm.: <?= h($banco->numero_banco) ?></div>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="fb-rel-bank-name"><?= h($banco->nome ?? 'Banco não informado') ?></div>
                                    <?php if (!empty($banco->cnab)): ?>
                                        <div class="fb-rel-muted">CNAB: <?= h($banco->cnab) ?></div>
                                    <?php endif; ?>
                                    <?php if ($linha['conta_incompleta']): ?>
                                        <div class="fb-rel-muted">Cadastro bancário incompleto</div>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= h($linha['agencia']) ?>
                                    <?php if ($linha['agencia'] === '—'): ?>
                                        <div class="fb-rel-muted">Agência não informada</div>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= h($linha['conta']) ?>
                                    <?php if ($linha['conta'] === '—'): ?>
                                        <div class="fb-rel-muted">Conta não informada</div>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <strong><?= number_format((int)$linha['qtd'], 0, ',', '.') ?></strong>
                                    <?php if ((int)$linha['qtd'] > 0): ?>
                                        <div class="fb-rel-bar">
                                            <div class="fb-rel-bar-fill" style="width:<?= number_format($qtdPercent, 2, '.', '') ?>%;"></div>
                                        </div>
                                        <div class="fb-rel-muted">Participação relativa na carteira</div>
                                    <?php else: ?>
                                        <div class="fb-rel-muted">Sem títulos em aberto</div>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (!empty($linha['proximo_vencimento'])): ?>
                                        <?= h($linha['proximo_vencimento']->format('d/m/Y')) ?>
                                        <div class="fb-rel-muted">Próximo título previsto</div>
                                    <?php else: ?>
                                        <span class="fb-rel-muted">Sem vencimento previsto</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ($linha['dias'] !== null): ?>
                                        <?php if ((int)$linha['dias'] < 0): ?>
                                            <span class="fb-rel-muted"><?= abs((int)$linha['dias']) ?> dia(s) em atraso</span>
                                        <?php elseif ((int)$linha['dias'] === 0): ?>
                                            <span class="fb-rel-muted">Vence hoje</span>
                                        <?php else: ?>
                                            <span class="fb-rel-muted">Em <?= (int)$linha['dias'] ?> dia(s)</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="fb-rel-muted">Sem cálculo disponível</span>
                                    <?php endif; ?>
                                </td>

                                <td class="fb-rel-money">R$ <?= number_format((float)$linha['total'], 2, ',', '.') ?></td>

                                <td>
                                    <span class="<?= h($linha['criticidade_classe']) ?>"><?= h($linha['criticidade_label']) ?></span>
                                    <div class="fb-rel-muted" style="margin-top:6px;"><?= h($linha['criticidade_texto']) ?></div>
                                </td>

                                <td>
                                    <div class="fb-rel-row-actions">
                                        <?= $this->Html->link(
                                            'Remessa',
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
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($linhas)): ?>
                    <tfoot>
                        <tr>
                            <td colspan="4">Totais</td>
                            <td><?= number_format((int)$metricasPrevisao['qtd_titulos'], 0, ',', '.') ?></td>
                            <td>—</td>
                            <td>—</td>
                            <td>R$ <?= number_format((float)$metricasPrevisao['valor_total'], 2, ',', '.') ?></td>
                            <td>Consolidado</td>
                            <td></td>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <p class="fb-rel-footer-note">
        Esta tela foi reorganizada para facilitar a cobrança e a priorização dos recebimentos:
        filtre por criticidade, revise os bancos com cadastro incompleto e use os atalhos para abrir remessa ou ajustar rapidamente o cadastro bancário.
    </p>
</div>

<script>
$(function () {
    if ($.fn.DataTable && $('#tbPrevisaoRecebimentosBanco').length) {
        $('#tbPrevisaoRecebimentosBanco').DataTable({
            order: [[7, 'desc']],
            pageLength: <?= (int)($pagelength ?? 25) ?>,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Portuguese-Brasil.json'
            }
        });
    }
});
</script>
