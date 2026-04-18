<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Financeiro', ['controller' => 'Financeiro', 'action' => 'index']);
$this->Breadcrumbs->add('Bancos', ['controller' => 'FinanceiroBancos', 'action' => 'index']);
$this->Breadcrumbs->add('Retornos bancários');

$bancos = $bancos ?? [];
$resumoRetorno = $resumoRetorno ?? [];

$totais = [
    'bancos' => count($bancos),
    'com_conta' => 0,
    'sem_conta' => 0,
    'com_extrato' => 0,
    'sem_extrato' => 0,
    'pendentes' => 0,
    'conciliados' => 0,
    'eventos' => 0,
];

$linhas = [];

foreach ($bancos as $banco) {
    $resumo = $resumoRetorno[(int)$banco->id] ?? [
        'quantidade' => 0,
        'conciliados' => 0,
        'pendentes' => 0,
        'ultimo_evento' => null,
    ];

    $temConta = !empty($banco->numero_agencia) && !empty($banco->numero_conta);
    $quantidade = (int)($resumo['quantidade'] ?? 0);
    $conciliados = (int)($resumo['conciliados'] ?? 0);
    $pendentes = (int)($resumo['pendentes'] ?? 0);
    $ultimoEvento = $resumo['ultimo_evento'] ?? null;

    if ($temConta) {
        $totais['com_conta']++;
    } else {
        $totais['sem_conta']++;
    }

    if ($quantidade > 0) {
        $totais['com_extrato']++;
    } else {
        $totais['sem_extrato']++;
    }

    $totais['pendentes'] += $pendentes;
    $totais['conciliados'] += $conciliados;
    $totais['eventos'] += $quantidade;

    $statusLabel = 'Sem conta bancária';
    $statusClass = 'fb-ret-badge--semconta';
    $statusPeso = 4;
    $descricaoStatus = 'Cadastre agência e conta para permitir relacionamento com extratos importados.';

    if ($temConta) {
        if ($quantidade <= 0) {
            $statusLabel = 'Aguardando extrato';
            $statusClass = 'fb-ret-badge--implantacao';
            $statusPeso = 3;
            $descricaoStatus = 'Conta configurada, mas ainda sem movimentos importados no extrato.';
        } elseif ($pendentes > 0) {
            $statusLabel = 'Com pendências';
            $statusClass = 'fb-ret-badge--pendente';
            $statusPeso = 1;
            $descricaoStatus = 'Há movimentos importados aguardando conciliação.';
        } else {
            $statusLabel = 'Conciliado';
            $statusClass = 'fb-ret-badge--ok';
            $statusPeso = 2;
            $descricaoStatus = 'Todos os movimentos vinculados a esta conta estão conciliados.';
        }
    }

    $agencia = trim((string)($banco->numero_agencia ?? ''));
    if ($agencia !== '' && !empty($banco->digito_agencia)) {
        $agencia .= '-' . trim((string)$banco->digito_agencia);
    }

    $conta = trim((string)($banco->numero_conta ?? ''));
    if ($conta !== '' && !empty($banco->digito_conta)) {
        $conta .= '-' . trim((string)$banco->digito_conta);
    }

    $linhas[] = [
        'banco' => $banco,
        'tem_conta' => $temConta,
        'quantidade' => $quantidade,
        'conciliados' => $conciliados,
        'pendentes' => $pendentes,
        'ultimo_evento' => $ultimoEvento,
        'status_label' => $statusLabel,
        'status_class' => $statusClass,
        'status_peso' => $statusPeso,
        'descricao_status' => $descricaoStatus,
        'agencia_fmt' => $agencia !== '' ? $agencia : '—',
        'conta_fmt' => $conta !== '' ? $conta : '—',
    ];
}

usort($linhas, function ($a, $b) {
    if ($a['status_peso'] !== $b['status_peso']) {
        return $a['status_peso'] <=> $b['status_peso'];
    }

    if ($a['pendentes'] !== $b['pendentes']) {
        return $b['pendentes'] <=> $a['pendentes'];
    }

    return strcmp(
        (string)($a['banco']->nome ?? ''),
        (string)($b['banco']->nome ?? '')
    );
});

$ultimoMovimentoGeral = null;
foreach ($linhas as $linha) {
    if (!empty($linha['ultimo_evento']) && ($ultimoMovimentoGeral === null || $linha['ultimo_evento'] > $ultimoMovimentoGeral)) {
        $ultimoMovimentoGeral = $linha['ultimo_evento'];
    }
}
?>
<style>
.fb-ret-root { font-family:'DM Sans',sans-serif; }
.fb-ret-topbar {
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:18px 24px 14px;
    border-bottom:1px solid rgba(255,255,255,.07);
    gap:12px;
    flex-wrap:wrap;
}
.fb-ret-title {
    font-size:20px;
    font-weight:600;
    color:#e6edf3;
    margin:0;
}
.fb-ret-title i {
    color:#5cdbc0;
    margin-right:8px;
}
.fb-ret-subtitle {
    color:#7d8590;
    font-size:12.5px;
    margin-top:4px;
}
.fb-ret-actions {
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}
.fb-ret-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:14px;
    padding:20px 24px 0;
}
.fb-ret-kpi {
    background:#161b22;
    border:1px solid rgba(255,255,255,.07);
    border-radius:10px;
    padding:16px 18px;
}
.fb-ret-kpi-label {
    color:#7d8590;
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:.05em;
    font-weight:600;
    margin-bottom:6px;
}
.fb-ret-kpi-value {
    color:#e6edf3;
    font-size:24px;
    font-weight:700;
    line-height:1.1;
}
.fb-ret-kpi-help {
    margin-top:8px;
    color:#8b949e;
    font-size:12px;
    line-height:1.45;
}
.fb-ret-card {
    background:#161b22;
    border:1px solid rgba(255,255,255,.07);
    border-radius:10px;
    padding:18px 20px;
    margin:16px 24px;
}
.fb-ret-card-head {
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:12px;
    margin-bottom:14px;
    flex-wrap:wrap;
}
.fb-ret-card-title {
    margin:0;
    font-size:16px;
    color:#e6edf3;
    font-weight:600;
}
.fb-ret-card-sub {
    color:#7d8590;
    font-size:12.5px;
    margin-top:4px;
    line-height:1.55;
}
.fb-ret-highlights {
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
    gap:16px;
}
.fb-ret-mini {
    background:rgba(255,255,255,.02);
    border:1px solid rgba(255,255,255,.06);
    border-radius:10px;
    padding:16px;
}
.fb-ret-mini h3 {
    margin:0 0 8px;
    font-size:14px;
    font-weight:600;
    color:#e6edf3;
}
.fb-ret-mini p {
    margin:0;
    color:#8b949e;
    font-size:12.5px;
    line-height:1.55;
}
.fb-ret-pill-list {
    display:flex;
    flex-wrap:wrap;
    gap:8px;
    margin-top:10px;
}
.fb-ret-pill {
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
.fb-ret-pill--warn {
    background:rgba(255,193,7,.10);
    color:#ffd166;
}
.fb-ret-pill--muted {
    background:rgba(255,255,255,.06);
    color:#9ca3af;
}
.fb-ret-table-wrap {
    overflow:auto;
}
.fb-ret-table {
    width:100%;
    border-collapse:collapse;
    font-size:12.5px;
    min-width:1180px;
}
.fb-ret-table th {
    text-align:left;
    color:#7d8590;
    font-size:10.5px;
    text-transform:uppercase;
    letter-spacing:.05em;
    font-weight:600;
    padding:8px 10px;
    border-bottom:1px solid rgba(255,255,255,.07);
    white-space:nowrap;
}
.fb-ret-table td {
    padding:10px;
    color:#c9d1d9;
    border-bottom:1px solid rgba(255,255,255,.04);
    vertical-align:top;
}
.fb-ret-table tr:hover td {
    background:rgba(255,255,255,.02);
}
.fb-ret-code {
    display:inline-block;
    padding:2px 7px;
    border-radius:6px;
    background:rgba(92,219,192,.10);
    color:#5cdbc0;
    font-weight:700;
    font-size:12px;
}
.fb-ret-bank-name {
    font-weight:600;
    color:#e6edf3;
}
.fb-ret-muted {
    color:#8b949e;
    font-size:11px;
    line-height:1.45;
}
.fb-ret-badge {
    display:inline-block;
    padding:3px 8px;
    border-radius:999px;
    font-size:11px;
    font-weight:600;
}
.fb-ret-badge--implantacao {
    background:rgba(255,193,7,.12);
    color:#ffc107;
}
.fb-ret-badge--ok {
    background:rgba(63,185,80,.14);
    color:#3fb950;
}
.fb-ret-badge--pendente {
    background:rgba(249,196,74,.16);
    color:#ffd166;
}
.fb-ret-badge--semconta {
    background:rgba(255,255,255,.08);
    color:#9ca3af;
}
.fb-ret-counter {
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:4px 8px;
    border-radius:999px;
    font-size:11px;
    font-weight:700;
    background:rgba(255,255,255,.06);
    color:#c9d1d9;
}
.fb-ret-counter--ok {
    background:rgba(63,185,80,.12);
    color:#3fb950;
}
.fb-ret-counter--warn {
    background:rgba(255,193,7,.12);
    color:#ffd166;
}
.fb-ret-row-actions {
    display:flex;
    gap:6px;
    flex-wrap:wrap;
}
.fb-ret-empty {
    text-align:center;
    padding:40px 18px;
    color:#8b949e;
}
.fb-ret-empty i {
    display:block;
    font-size:30px;
    opacity:.45;
    margin-bottom:10px;
    color:#5cdbc0;
}
.fb-ret-note {
    margin:0 24px 24px;
    background:rgba(29,158,117,.08);
    border:1px solid rgba(29,158,117,.16);
    border-radius:10px;
    padding:14px 16px;
    color:#c9d1d9;
    font-size:12.5px;
    line-height:1.6;
}
.fb-ret-note strong {
    color:#5cdbc0;
}
</style>

<div class="fb-ret-root">
    <div class="fb-ret-topbar">
        <div>
            <h1 class="fb-ret-title"><i class="fas fa-file-import"></i>Retornos bancários</h1>
            <div class="fb-ret-subtitle">
                Painel operacional para acompanhar contas bancárias, extratos importados e pendências de conciliação.
            </div>
        </div>

        <div class="fb-ret-actions">
            <?= $this->Html->link(
                '<i class="fas fa-history"></i> Histórico de retorno',
                ['controller' => 'FinanceiroBancos', 'action' => 'historicoRetorno'],
                ['class' => 'btn btn-pgm btn-pgm-situacao btn-sm', 'escape' => false]
            ) ?>
            <?= $this->Html->link(
                '<i class="fas fa-list"></i> Cadastro de bancos',
                ['controller' => 'FinanceiroBancos', 'action' => 'cadastrar'],
                ['class' => 'btn btn-default btn-sm', 'escape' => false]
            ) ?>
            <?= $this->Html->link(
                '<i class="fas fa-arrow-left"></i> Voltar para Bancos',
                ['controller' => 'FinanceiroBancos', 'action' => 'index'],
                ['class' => 'btn btn-default btn-sm', 'escape' => false]
            ) ?>
        </div>
    </div>

    <div class="fb-ret-grid">
        <div class="fb-ret-kpi">
            <div class="fb-ret-kpi-label">Bancos monitorados</div>
            <div class="fb-ret-kpi-value"><?= number_format((int)$totais['bancos'], 0, ',', '.') ?></div>
            <div class="fb-ret-kpi-help">Total de cadastros bancários disponíveis para relacionamento com retorno.</div>
        </div>
        <div class="fb-ret-kpi">
            <div class="fb-ret-kpi-label">Com conta configurada</div>
            <div class="fb-ret-kpi-value"><?= number_format((int)$totais['com_conta'], 0, ',', '.') ?></div>
            <div class="fb-ret-kpi-help">Bancos com agência e conta preenchidas, aptos para cruzamento com extrato.</div>
        </div>
        <div class="fb-ret-kpi">
            <div class="fb-ret-kpi-label">Bancos com extrato</div>
            <div class="fb-ret-kpi-value"><?= number_format((int)$totais['com_extrato'], 0, ',', '.') ?></div>
            <div class="fb-ret-kpi-help">Cadastros que já possuem movimentos de extrato associados.</div>
        </div>
        <div class="fb-ret-kpi">
            <div class="fb-ret-kpi-label">Pendências de conciliação</div>
            <div class="fb-ret-kpi-value"><?= number_format((int)$totais['pendentes'], 0, ',', '.') ?></div>
            <div class="fb-ret-kpi-help">Movimentos importados que ainda precisam ser conciliados.</div>
        </div>
        <div class="fb-ret-kpi">
            <div class="fb-ret-kpi-label">Eventos conciliados</div>
            <div class="fb-ret-kpi-value"><?= number_format((int)$totais['conciliados'], 0, ',', '.') ?></div>
            <div class="fb-ret-kpi-help">Quantidade de movimentos já conciliados nas contas bancárias.</div>
        </div>
        <div class="fb-ret-kpi">
            <div class="fb-ret-kpi-label">Último movimento geral</div>
            <div class="fb-ret-kpi-value" style="font-size:20px;">
                <?= $ultimoMovimentoGeral ? h($ultimoMovimentoGeral->format('d/m/Y')) : '—' ?>
            </div>
            <div class="fb-ret-kpi-help">Data mais recente identificada entre os extratos vinculados aos bancos.</div>
        </div>
    </div>

    <div class="fb-ret-card">
        <div class="fb-ret-card-head">
            <div>
                <h2 class="fb-ret-card-title">Leitura operacional do retorno</h2>
                <div class="fb-ret-card-sub">
                    Use este painel para priorizar bancos com pendências, revisar contas sem configuração completa
                    e acompanhar o avanço da conciliação dos movimentos importados.
                </div>
            </div>
        </div>

        <div class="fb-ret-highlights">
            <div class="fb-ret-mini">
                <h3>Prioridade imediata</h3>
                <p>
                    Bancos com status <strong>Com pendências</strong> devem ser tratados primeiro, pois já possuem extratos
                    importados aguardando conciliação operacional.
                </p>
                <div class="fb-ret-pill-list">
                    <span class="fb-ret-pill fb-ret-pill--warn">
                        <i class="fas fa-exclamation-triangle"></i><?= (int)$totais['pendentes'] ?> pendência(s)
                    </span>
                    <span class="fb-ret-pill">
                        <i class="fas fa-check-circle"></i><?= (int)$totais['conciliados'] ?> conciliado(s)
                    </span>
                </div>
            </div>

            <div class="fb-ret-mini">
                <h3>Qualidade do cadastro</h3>
                <p>
                    O relacionamento com extratos depende do preenchimento correto de agência e conta. Bancos sem esses dados
                    ficam limitados no cruzamento automático.
                </p>
                <div class="fb-ret-pill-list">
                    <span class="fb-ret-pill"><?= (int)$totais['com_conta'] ?> com conta completa</span>
                    <span class="fb-ret-pill fb-ret-pill--muted"><?= (int)$totais['sem_conta'] ?> sem conta completa</span>
                </div>
            </div>

            <div class="fb-ret-mini">
                <h3>Cobertura de extrato</h3>
                <p>
                    Nem todo banco configurado já recebeu importação. Isso ajuda a separar bancos efetivamente usados no financeiro
                    dos que ainda estão em preparação.
                </p>
                <div class="fb-ret-pill-list">
                    <span class="fb-ret-pill"><?= (int)$totais['com_extrato'] ?> com extrato</span>
                    <span class="fb-ret-pill fb-ret-pill--muted"><?= (int)$totais['sem_extrato'] ?> sem extrato</span>
                </div>
            </div>
        </div>
    </div>

    <div class="fb-ret-card">
        <div class="fb-ret-card-head">
            <div>
                <h2 class="fb-ret-card-title">Painel por banco</h2>
                <div class="fb-ret-card-sub">
                    A lista abaixo já vem priorizada por pendências de conciliação, seguida pelos bancos aguardando extrato,
                    conciliados e, por fim, cadastros sem conta bancária completa.
                </div>
            </div>
        </div>

        <?php if (empty($linhas)): ?>
            <div class="fb-ret-empty">
                <i class="fas fa-folder-open"></i>
                Nenhum banco cadastrado para a empresa.
            </div>
        <?php else: ?>
            <div class="fb-ret-table-wrap">
                <table class="fb-ret-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Banco</th>
                            <th>CNAB</th>
                            <th>Agência</th>
                            <th>Conta</th>
                            <th>Status do retorno</th>
                            <th>Extratos</th>
                            <th>Último movimento</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($linhas as $linha): ?>
                            <?php $banco = $linha['banco']; ?>
                            <tr>
                                <td>
                                    <span class="fb-ret-code"><?= h($banco->codigo_banco ?: '—') ?></span>
                                </td>
                                <td>
                                    <div class="fb-ret-bank-name"><?= h($banco->nome ?: '—') ?></div>
                                    <?php if (!empty($banco->codigo_banco_interno)): ?>
                                        <div class="fb-ret-muted">Código interno: <?= h($banco->codigo_banco_interno) ?></div>
                                    <?php endif; ?>
                                    <?php if (empty($banco->ativo)): ?>
                                        <div class="fb-ret-muted">Cadastro marcado como inativo.</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= h($banco->cnab ?: '—') ?>
                                    <?php if (empty($banco->cnab)): ?>
                                        <div class="fb-ret-muted">CNAB não informado</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= h($linha['agencia_fmt']) ?>
                                    <?php if ($linha['agencia_fmt'] === '—'): ?>
                                        <div class="fb-ret-muted">Agência não cadastrada</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= h($linha['conta_fmt']) ?>
                                    <?php if ($linha['conta_fmt'] === '—'): ?>
                                        <div class="fb-ret-muted">Conta não cadastrada</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="fb-ret-badge <?= h($linha['status_class']) ?>">
                                        <?= h($linha['status_label']) ?>
                                    </span>
                                    <div class="fb-ret-muted" style="margin-top:6px;">
                                        <?= h($linha['descricao_status']) ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                        <span class="fb-ret-counter"><?= (int)$linha['quantidade'] ?> extrato(s)</span>
                                        <span class="fb-ret-counter fb-ret-counter--ok"><?= (int)$linha['conciliados'] ?> conciliado(s)</span>
                                        <span class="fb-ret-counter fb-ret-counter--warn"><?= (int)$linha['pendentes'] ?> pendente(s)</span>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($linha['ultimo_evento'])): ?>
                                        <?= h($linha['ultimo_evento']->format('d/m/Y')) ?>
                                        <div class="fb-ret-muted">Último evento vinculado ao banco</div>
                                    <?php else: ?>
                                        <span class="fb-ret-muted">Sem movimentação identificada</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fb-ret-row-actions">
                                        <?= $this->Html->link(
                                            'Editar banco',
                                            ['controller' => 'FinanceiroBancos', 'action' => 'edit', $banco->id],
                                            ['class' => 'btn btn-xs btn-default']
                                        ) ?>
                                        <?= $this->Html->link(
                                            'Histórico',
                                            ['controller' => 'FinanceiroBancos', 'action' => 'historicoRetorno'],
                                            ['class' => 'btn btn-xs btn-pgm btn-pgm-situacao']
                                        ) ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="fb-ret-note">
        <strong>Dica operacional:</strong> mantenha o cadastro bancário com <strong>agência</strong>, <strong>conta</strong> e,
        quando aplicável, <strong>CNAB</strong> corretamente preenchidos. Isso melhora o relacionamento com o extrato importado,
        facilita a conciliação e prepara o módulo para evoluções futuras de retorno bancário.
    </div>
</div>
