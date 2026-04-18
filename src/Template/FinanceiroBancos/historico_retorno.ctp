<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Financeiro', ['controller' => 'Financeiro', 'action' => 'index']);
$this->Breadcrumbs->add('Bancos', ['controller' => 'FinanceiroBancos', 'action' => 'index']);
$this->Breadcrumbs->add('Histórico de Retorno Bancário');

$historico = $historico ?? [];

$totalBancos = count($historico);
$totalSucesso = 0;
$totalPendente = 0;
$totalImplantacao = 0;
$totalEventos = 0;
$totalConciliados = 0;
$totalPendentes = 0;
$ultimaData = null;

foreach ($historico as $item) {
    $status = mb_strtolower(trim((string)($item['status'] ?? 'Em implantação')));
    $descricao = (string)($item['descricao'] ?? '');
    $banco = $item['banco'] ?? null;

    if ($status === 'sucesso') {
        $totalSucesso++;
    } elseif ($status === 'pendente') {
        $totalPendente++;
    } else {
        $totalImplantacao++;
    }

    if (preg_match('/^(\d+)\s+lançamento/i', $descricao, $m)) {
        $totalEventos += (int)$m[1];
    }

    if (preg_match('/sendo\s+(\d+)\s+conciliado/i', $descricao, $m)) {
        $totalConciliados += (int)$m[1];
    }

    if (preg_match('/e\s+(\d+)\s+pendente/i', $descricao, $m)) {
        $totalPendentes += (int)$m[1];
    }

    if (!empty($item['ultimo_evento']) && is_object($item['ultimo_evento']) && method_exists($item['ultimo_evento'], 'format')) {
        if ($ultimaData === null || $item['ultimo_evento'] > $ultimaData) {
            $ultimaData = $item['ultimo_evento'];
        }
    } elseif (!empty($banco->modified) && is_object($banco->modified) && method_exists($banco->modified, 'format')) {
        if ($ultimaData === null || $banco->modified > $ultimaData) {
            $ultimaData = $banco->modified;
        }
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
    color:#8b949e;
    font-size:12.5px;
    margin-top:4px;
}
.fb-actions {
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}

.fb-kpis {
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(210px,1fr));
    gap:14px;
    padding:20px 24px 0;
}
.fb-kpi {
    background:#161b22;
    border:1px solid rgba(255,255,255,.07);
    border-radius:10px;
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

.fb-callout {
    margin:16px 24px 0;
    background:rgba(29,158,117,.08);
    border:1px solid rgba(29,158,117,.16);
    border-radius:10px;
    padding:14px 16px;
    color:#c9d1d9;
    font-size:12.5px;
    line-height:1.6;
}
.fb-callout strong {
    color:#5cdbc0;
}

.fb-card {
    background:#161b22;
    border:1px solid rgba(255,255,255,.07);
    border-radius:10px;
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
    color:#8b949e;
    font-size:12px;
    margin-top:4px;
}

.fb-summary-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
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
    min-width:1180px;
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

.fb-status {
    display:inline-block;
    padding:3px 9px;
    border-radius:999px;
    font-size:11px;
    font-weight:700;
}
.fb-status--implantacao {
    background:rgba(255,193,7,.16);
    color:#ffd166;
}
.fb-status--sucesso {
    background:rgba(63,185,80,.16);
    color:#3fb950;
}
.fb-status--pendente {
    background:rgba(255,193,7,.14);
    color:#ffcd57;
}
.fb-status--erro {
    background:rgba(248,81,73,.16);
    color:#f85149;
}

.fb-muted {
    color:#8b949e;
    font-size:11px;
}
.fb-note {
    display:block;
    margin-top:5px;
    color:#8b949e;
    font-size:11px;
    line-height:1.5;
}
.fb-alert {
    display:inline-block;
    margin-top:6px;
    padding:3px 8px;
    border-radius:999px;
    font-size:10.5px;
    font-weight:700;
    background:rgba(255,193,7,.15);
    color:#ffd166;
}
.fb-ok {
    display:inline-block;
    margin-top:6px;
    padding:3px 8px;
    border-radius:999px;
    font-size:10.5px;
    font-weight:700;
    background:rgba(63,185,80,.14);
    color:#3fb950;
}
.fb-empty {
    text-align:center;
    padding:36px 18px;
    color:#8b949e;
}
.fb-empty i {
    display:block;
    font-size:32px;
    margin-bottom:10px;
    opacity:.4;
    color:#5cdbc0;
}
.fb-footer-note {
    margin:0 24px 20px;
    color:#8b949e;
    font-size:12px;
    line-height:1.7;
}
</style>

<div class="fb-root">
    <div class="fb-topbar">
        <div>
            <h1 class="fb-h1"><i class="fas fa-history fb-h1-ico"></i>Histórico de Retorno Bancário</h1>
            <div class="fb-sub">
                Visão executiva e operacional dos bancos vinculados ao processo de retorno, com foco em conciliação e acompanhamento do módulo.
            </div>
        </div>

        <div class="fb-actions">
            <?= $this->Html->link(
                '<i class="fas fa-reply"></i> Retornos bancários',
                ['controller' => 'FinanceiroBancos', 'action' => 'retorno'],
                ['class' => 'btn btn-default btn-sm', 'escape' => false]
            ) ?>
            <?= $this->Html->link(
                '<i class="fas fa-chart-bar"></i> Relatórios bancários',
                ['controller' => 'FinanceiroBancos', 'action' => 'relatorios'],
                ['class' => 'btn btn-pgm btn-pgm-situacao btn-sm', 'escape' => false]
            ) ?>
            <?= $this->Html->link(
                '<i class="fas fa-university"></i> Bancos',
                ['controller' => 'FinanceiroBancos', 'action' => 'index'],
                ['class' => 'btn btn-default btn-sm', 'escape' => false]
            ) ?>
        </div>
    </div>

    <div class="fb-kpis">
        <div class="fb-kpi">
            <div class="fb-kpi-label">Bancos analisados</div>
            <div class="fb-kpi-value"><?= number_format($totalBancos, 0, ',', '.') ?></div>
            <div class="fb-kpi-help">Quantidade de bancos exibidos no histórico atual.</div>
        </div>
        <div class="fb-kpi">
            <div class="fb-kpi-label">Situação com sucesso</div>
            <div class="fb-kpi-value"><?= number_format($totalSucesso, 0, ',', '.') ?></div>
            <div class="fb-kpi-help">Bancos sem pendências no cruzamento do histórico retornado.</div>
        </div>
        <div class="fb-kpi">
            <div class="fb-kpi-label">Com pendências</div>
            <div class="fb-kpi-value"><?= number_format($totalPendente, 0, ',', '.') ?></div>
            <div class="fb-kpi-help">Bancos que ainda possuem itens aguardando conciliação.</div>
        </div>
        <div class="fb-kpi">
            <div class="fb-kpi-label">Em implantação</div>
            <div class="fb-kpi-value"><?= number_format($totalImplantacao, 0, ',', '.') ?></div>
            <div class="fb-kpi-help">Contas sem histórico processado ou ainda sem extrato vinculado.</div>
        </div>
        <div class="fb-kpi">
            <div class="fb-kpi-label">Eventos mapeados</div>
            <div class="fb-kpi-value"><?= number_format($totalEventos, 0, ',', '.') ?></div>
            <div class="fb-kpi-help">Total inferido de lançamentos identificados no histórico.</div>
        </div>
        <div class="fb-kpi">
            <div class="fb-kpi-label">Último movimento</div>
            <div class="fb-kpi-value" style="font-size:20px;">
                <?= $ultimaData ? h($ultimaData->format('d/m/Y')) : '—' ?>
            </div>
            <div class="fb-kpi-help">Data mais recente encontrada entre os históricos consolidados.</div>
        </div>
    </div>

    <div class="fb-callout">
        <strong>Leitura operacional:</strong> use esta tela para identificar rapidamente quais bancos já possuem histórico coerente,
        quais ainda estão em fase de implantação e quais exigem atuação por conta de pendências de conciliação.
        Sempre que houver conta bancária incompleta, revise o cadastro do banco antes de avançar com remessa, retorno e leitura financeira.
    </div>

    <div class="fb-card">
        <div class="fb-card-head">
            <div>
                <h2 class="fb-card-title">Resumo executivo do histórico</h2>
                <div class="fb-card-sub">Indicadores auxiliares para leitura rápida do estado do módulo bancário.</div>
            </div>
        </div>

        <div class="fb-summary-grid">
            <div class="fb-summary-box">
                <div class="fb-summary-title">Conciliação consolidada</div>
                <div class="fb-summary-text">
                    Foram identificados <strong><?= number_format($totalConciliados, 0, ',', '.') ?></strong> item(ns) conciliado(s)
                    e <strong><?= number_format($totalPendentes, 0, ',', '.') ?></strong> item(ns) pendente(s) no histórico atual.
                </div>
            </div>

            <div class="fb-summary-box">
                <div class="fb-summary-title">Maturidade do processo</div>
                <div class="fb-summary-text">
                    Bancos em <strong>sucesso</strong> indicam processo já estabilizado.
                    Bancos com status <strong>pendente</strong> exigem revisão operacional.
                    Itens em <strong>implantação</strong> sinalizam ausência de histórico consolidado.
                </div>
            </div>

            <div class="fb-summary-box">
                <div class="fb-summary-title">Ação recomendada</div>
                <div class="fb-summary-text">
                    Revise especialmente bancos com conta incompleta, sem último evento ou com pendências abertas,
                    pois isso pode afetar retorno, conciliação e rastreabilidade do financeiro.
                </div>
            </div>
        </div>
    </div>

    <div class="fb-card">
        <div class="fb-card-head">
            <div>
                <h2 class="fb-card-title">Grade detalhada por banco</h2>
                <div class="fb-card-sub">Situação individual dos bancos no histórico de retorno.</div>
            </div>
        </div>

        <div class="fb-table-wrap">
            <table class="fb-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Banco</th>
                        <th>CNAB</th>
                        <th>Agência</th>
                        <th>Conta</th>
                        <th>Status</th>
                        <th>Último evento</th>
                        <th>Descrição</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($historico)): ?>
                        <tr>
                            <td colspan="9" class="fb-empty">
                                <i class="fas fa-folder-open"></i>
                                Nenhum histórico de retorno bancário disponível.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($historico as $item): ?>
                            <?php
                                $banco = $item['banco'] ?? null;
                                $status = trim((string)($item['status'] ?? 'Em implantação'));
                                $descricao = trim((string)($item['descricao'] ?? ''));
                                $statusLower = mb_strtolower($status);
                                $statusClass = 'fb-status--implantacao';

                                if ($statusLower === 'sucesso') {
                                    $statusClass = 'fb-status--sucesso';
                                } elseif ($statusLower === 'pendente') {
                                    $statusClass = 'fb-status--pendente';
                                } elseif ($statusLower === 'erro') {
                                    $statusClass = 'fb-status--erro';
                                }

                                $agencia = trim((string)($banco->numero_agencia ?? ''));
                                $digAgencia = trim((string)($banco->digito_agencia ?? ''));
                                $conta = trim((string)($banco->numero_conta ?? ''));
                                $digConta = trim((string)($banco->digito_conta ?? ''));

                                $agenciaFmt = $agencia !== '' ? $agencia . ($digAgencia !== '' ? '-' . $digAgencia : '') : '—';
                                $contaFmt = $conta !== '' ? $conta . ($digConta !== '' ? '-' . $digConta : '') : '—';

                                $contaIncompleta = empty($banco->numero_agencia) || empty($banco->numero_conta);

                                $ultimoEvento = $item['ultimo_evento'] ?? null;
                                if (empty($ultimoEvento) && !empty($banco->modified)) {
                                    $ultimoEvento = $banco->modified;
                                }
                            ?>
                            <tr>
                                <td>
                                    <?php if (!empty($banco->codigo_banco)): ?>
                                        <span class="fb-code"><?= h($banco->codigo_banco) ?></span>
                                    <?php else: ?>
                                        <span class="fb-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= h($banco->nome ?? '—') ?></strong>
                                    <?php if (!empty($banco->codigo_banco_interno)): ?>
                                        <div class="fb-muted">Interno: <?= h($banco->codigo_banco_interno) ?></div>
                                    <?php endif; ?>
                                    <?php if ($contaIncompleta): ?>
                                        <span class="fb-alert">Cadastro incompleto</span>
                                    <?php else: ?>
                                        <span class="fb-ok">Conta pronta para conciliação</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($banco->cnab)): ?>
                                        <span class="fb-code"><?= h($banco->cnab) ?></span>
                                    <?php else: ?>
                                        <span class="fb-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= h($agenciaFmt) ?>
                                    <?php if (empty($banco->numero_agencia)): ?>
                                        <span class="fb-note">Agência não informada.</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= h($contaFmt) ?>
                                    <?php if (empty($banco->numero_conta)): ?>
                                        <span class="fb-note">Conta não informada.</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="fb-status <?= h($statusClass) ?>"><?= h($status) ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($ultimoEvento) && is_object($ultimoEvento) && method_exists($ultimoEvento, 'format')): ?>
                                        <?= h($ultimoEvento->format('d/m/Y')) ?>
                                        <div class="fb-muted">
                                            <?= h($ultimoEvento->format('H:i')) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="fb-muted">Sem evento registrado</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= h($descricao !== '' ? $descricao : 'Sem detalhes informados.') ?>
                                    <?php if (!empty($banco->observacoes)): ?>
                                        <span class="fb-note">Obs. cadastro: <?= h($banco->observacoes) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($item['retorno_arquivo_id'])): ?>
                                        <?= $this->Html->link(
                                            'Detalhe retorno',
                                            ['controller' => 'FinanceiroBancos', 'action' => 'detalheRetorno', $item['retorno_arquivo_id']],
                                            ['class' => 'btn btn-xs btn-pgm btn-pgm-salvar']
                                        ) ?>
                                        <?php if (!empty($item['download_disponivel'])): ?>
                                            <?= $this->Html->link(
                                                'Download',
                                                ['controller' => 'FinanceiroBancos', 'action' => 'downloadRetorno', $item['retorno_arquivo_id']],
                                                ['class' => 'btn btn-xs btn-default', 'style' => 'margin-left:6px;']
                                            ) ?>
                                        <?php endif; ?>
                                        <div style="height:6px;"></div>
                                    <?php endif; ?>

                                    <?= $this->Html->link(
                                        'Abrir cadastro',
                                        ['controller' => 'FinanceiroBancos', 'action' => 'edit', $banco->id ?? null],
                                        ['class' => 'btn btn-xs btn-default']
                                    ) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <p class="fb-footer-note">
        Este relatório foi preparado para acompanhar a evolução do módulo de retornos bancários.
        Conforme novas rotinas de importação e processamento forem integradas, esta grade poderá refletir
        com mais precisão ocorrências, liquidações, rejeições e reconciliações por banco. Quando houver
        arquivo persistido, use os atalhos de detalhe e download para auditoria operacional do retorno.
    </p>
</div>
