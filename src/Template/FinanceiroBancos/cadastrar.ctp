<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Financeiro', ['controller' => 'Financeiro', 'action' => 'index']);
$this->Breadcrumbs->add('Bancos', ['controller' => 'FinanceiroBancos', 'action' => 'index']);
$this->Breadcrumbs->add('Cadastro de bancos');

$bancos = $bancos ?? [];
$catalogo = $catalogo ?? [];
$codigo = $codigo ?? '';
$nome = $nome ?? '';
$ativo = isset($ativo) ? (string)$ativo : '';
$contaStatus = isset($contaStatus) ? (string)$contaStatus : '';
$metricasCadastro = $metricasCadastro ?? [
    'bancos' => count($bancos),
    'ativos' => 0,
    'inativos' => 0,
    'conta_completa' => 0,
    'conta_incompleta' => 0,
];
$bancoSelecionado = $bancoSelecionado ?? (!empty($bancos) ? $bancos[0] : null);

$fmtConta = function ($banco) {
    $ag = trim((string)($banco->numero_agencia ?? ''));
    $dag = trim((string)($banco->digito_agencia ?? ''));
    $cc = trim((string)($banco->numero_conta ?? ''));
    $dcc = trim((string)($banco->digito_conta ?? ''));

    $agFmt = $ag !== '' ? $ag . ($dag !== '' ? '-' . $dag : '') : '—';
    $ccFmt = $cc !== '' ? $cc . ($dcc !== '' ? '-' . $dcc : '') : '—';

    return 'Ag. ' . $agFmt . ' / Cc. ' . $ccFmt;
};

$temSelecionado = !empty($bancoSelecionado);
$temAgencia = $temSelecionado && trim((string)($bancoSelecionado->numero_agencia ?? '')) !== '';
$temConta = $temSelecionado && trim((string)($bancoSelecionado->numero_conta ?? '')) !== '';
$cadastroIncompleto = !$temAgencia || !$temConta;
?>
<style>
.fb-root {
    font-family:'DM Sans',Tahoma,Arial,sans-serif;
    color:#1f2937;
}
.fb-topbar {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    padding:16px 20px 12px;
    border-bottom:1px solid rgba(255,255,255,.08);
    flex-wrap:wrap;
}
.fb-title {
    display:flex;
    align-items:center;
    gap:8px;
    font-size:20px;
    font-weight:700;
    color:#e6edf3;
}
.fb-title i {
    color:#5cdbc0;
}
.fb-subtitle {
    margin-top:4px;
    color:#8b949e;
    font-size:12.5px;
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
    padding:20px 20px 0;
}
.fb-kpi {
    background:#161b22;
    border:1px solid rgba(255,255,255,.07);
    border-radius:12px;
    padding:16px 18px;
}
.fb-kpi-label {
    color:#7d8590;
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:.05em;
    font-weight:700;
    margin-bottom:6px;
}
.fb-kpi-value {
    color:#e6edf3;
    font-size:26px;
    font-weight:700;
    line-height:1.1;
}
.fb-kpi-help {
    margin-top:8px;
    color:#8b949e;
    font-size:11.5px;
    line-height:1.45;
}

.fb-shell {
    padding:16px 20px 24px;
}
.fb-window {
    background:#d7d7d7;
    border:1px solid #8e8e8e;
    border-radius:4px;
    overflow:hidden;
}
.fb-window-head {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    padding:10px 12px;
    background:linear-gradient(#efefef,#d6d6d6);
    border-bottom:1px solid #a8a8a8;
    flex-wrap:wrap;
}
.fb-window-title {
    font-size:13px;
    font-weight:700;
    color:#374151;
}
.fb-window-sub {
    color:#6b7280;
    font-size:11px;
    margin-top:2px;
}
.fb-window-actions {
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}

.fb-toolbar {
    padding:12px;
    background:#ececec;
    border-bottom:1px solid #cfcfcf;
}
.fb-toolbar-grid {
    display:grid;
    grid-template-columns:110px minmax(120px,160px) 60px minmax(220px,1fr) 70px 140px 110px 170px auto;
    gap:8px 10px;
    align-items:end;
}
.fb-toolbar label {
    font-size:12px;
    color:#2f2f2f;
    font-weight:700;
}
.fb-field .form-control,
.fb-toolbar .form-control,
.fb-toolbar select {
    width:100%;
    height:29px;
    border:1px solid #9f9f9f;
    border-radius:2px;
    background:#fff;
    color:#222;
    box-shadow:none;
    padding:4px 6px;
    font-size:12px;
}
.fb-toolbar-actions {
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}
.fb-toolbar-note {
    padding:8px 12px 10px;
    color:#666;
    font-size:11px;
    background:#ececec;
    border-bottom:1px solid #cfcfcf;
}

.fb-layout {
    display:grid;
    grid-template-columns:minmax(0, 1.45fr) minmax(320px, .85fr);
    gap:14px;
    padding:14px;
    background:#d7d7d7;
}
.fb-card {
    background:#f5f5f5;
    border:1px solid #bdbdbd;
    border-radius:3px;
    padding:12px;
}
.fb-card-title {
    font-size:13px;
    font-weight:700;
    color:#374151;
    margin:0 0 10px;
}
.fb-card-sub {
    font-size:11px;
    color:#6b7280;
    margin-bottom:12px;
    line-height:1.5;
}
.fb-summary-line {
    display:flex;
    gap:8px;
    flex-wrap:wrap;
    margin-bottom:12px;
}
.fb-pill {
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:5px 10px;
    border-radius:999px;
    background:#e8eef6;
    color:#36506c;
    font-size:11px;
    font-weight:700;
}
.fb-pill--warn {
    background:#fff2cf;
    color:#946200;
}
.fb-pill--ok {
    background:#dff2df;
    color:#267326;
}
.fb-pill--muted {
    background:#ececec;
    color:#5f6368;
}

.fb-panel-grid {
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:10px 12px;
    align-items:end;
}
.fb-panel-grid--wide {
    grid-template-columns:150px minmax(120px,1fr) 140px minmax(120px,1fr);
}
.fb-panel-grid label {
    font-size:12px;
    color:#2f2f2f;
    font-weight:700;
}
.fb-readonly {
    width:100%;
    min-height:29px;
    border:1px solid #9f9f9f;
    background:#fff;
    border-radius:2px;
    padding:5px 6px;
    font-size:12px;
    color:#111827;
    box-sizing:border-box;
}
.fb-readonly--tall {
    min-height:84px;
    line-height:1.5;
    white-space:pre-wrap;
}
.fb-logo-box {
    min-height:170px;
    border:1px solid #c8c8c8;
    background:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#9a9a9a;
    font-size:11px;
    text-align:center;
    padding:12px;
}
.fb-state-box {
    margin-top:12px;
    padding:10px 12px;
    border-radius:8px;
    border:1px dashed #c8c8c8;
    background:#fafafa;
}
.fb-state-box--warn {
    background:#fff8e8;
    border-color:#e5c97a;
}
.fb-state-box--ok {
    background:#eefaf0;
    border-color:#a9d3af;
}
.fb-state-title {
    font-size:12px;
    font-weight:700;
    color:#374151;
    margin-bottom:5px;
}
.fb-state-text {
    font-size:11.5px;
    color:#5f6368;
    line-height:1.5;
}

.fb-tabs {
    display:flex;
    gap:3px;
    margin:14px 0 0;
}
.fb-tab {
    padding:7px 12px;
    border:1px solid #bcbcbc;
    border-bottom:none;
    background:#ececec;
    font-size:12px;
    color:#444;
}
.fb-tab.is-active {
    background:#f7f7f7;
    font-weight:700;
}
.fb-tab-panel {
    border:1px solid #bcbcbc;
    background:#f7f7f7;
    padding:12px;
}
.fb-tab-panel + .fb-tab-panel {
    margin-top:10px;
}

.fb-table-wrap {
    overflow:auto;
    border:1px solid #bcbcbc;
    background:#fff;
}
.fb-table {
    width:100%;
    border-collapse:collapse;
    font-size:12px;
    min-width:980px;
}
.fb-table th {
    background:#efefef;
    color:#333;
    font-size:11px;
    font-weight:700;
    border-bottom:1px solid #bcbcbc;
    border-right:1px solid #d7d7d7;
    padding:7px 8px;
    text-align:left;
    white-space:nowrap;
}
.fb-table td {
    border-bottom:1px solid #ececec;
    border-right:1px solid #f0f0f0;
    padding:8px 8px;
    color:#2e2e2e;
    vertical-align:top;
}
.fb-table tr:hover td {
    background:#f7fbff;
}
.fb-row-selected td {
    background:#1777c8 !important;
    color:#fff;
}
.fb-row-selected .fb-code,
.fb-row-selected .fb-muted,
.fb-row-selected .fb-status,
.fb-row-selected .fb-status--warn,
.fb-row-selected .fb-note {
    color:#fff !important;
}
.fb-code {
    color:#0c65ad;
    font-weight:700;
}
.fb-muted {
    color:#6c6c6c;
    font-size:11px;
}
.fb-note {
    display:block;
    margin-top:4px;
    color:#6c6c6c;
    font-size:11px;
    line-height:1.45;
}
.fb-status {
    display:inline-block;
    padding:2px 7px;
    border-radius:10px;
    font-size:10px;
    font-weight:700;
}
.fb-status--on {
    background:#dff2df;
    color:#267326;
}
.fb-status--off {
    background:#f6dddd;
    color:#a12c2c;
}
.fb-status--warn {
    background:#fff2cf;
    color:#946200;
}
.fb-grid-footer {
    display:flex;
    justify-content:space-between;
    gap:10px;
    align-items:center;
    padding-top:10px;
    flex-wrap:wrap;
}
.fb-empty {
    text-align:center;
    padding:22px 12px;
    color:#787878;
    font-size:12px;
}
.fb-quick-actions {
    display:flex;
    gap:8px;
    flex-wrap:wrap;
    margin-top:10px;
}

.fb-catalogo-list {
    display:flex;
    flex-direction:column;
    gap:8px;
    max-height:620px;
    overflow:auto;
}
.fb-catalogo-item {
    border:1px solid #c6c6c6;
    background:#fff;
    padding:9px 10px;
    border-radius:3px;
}
.fb-catalogo-top {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    margin-bottom:4px;
}
.fb-catalogo-cod {
    color:#0c65ad;
    font-weight:700;
    font-size:12px;
}
.fb-catalogo-meta {
    color:#666;
    font-size:11px;
}
.fb-catalogo-nome {
    color:#2f2f2f;
    font-size:12px;
    font-weight:700;
}

.fb-callout {
    margin:0 20px;
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

@media (max-width: 1280px) {
    .fb-toolbar-grid {
        grid-template-columns:repeat(2,minmax(0,1fr));
    }
    .fb-layout {
        grid-template-columns:1fr;
    }
}
@media (max-width: 960px) {
    .fb-panel-grid,
    .fb-panel-grid--wide {
        grid-template-columns:1fr;
    }
}
</style>

<div class="fb-root">
    <div class="fb-topbar">
        <div>
            <div class="fb-title">
                <i class="fas fa-landmark"></i>
                <span>Cadastro de Bancos</span>
            </div>
            <div class="fb-subtitle">
                Gestão central dos bancos do financeiro com filtros corrigidos, leitura operacional e acesso rápido ao formulário clássico.
            </div>
        </div>

        <div class="fb-actions">
            <?= $this->Html->link(
                '<i class="fas fa-plus"></i> Novo cadastro',
                ['action' => 'add'],
                ['class' => 'btn btn-pgm btn-pgm-salvar btn-sm', 'escape' => false]
            ) ?>
            <?= $this->Html->link(
                '<i class="fas fa-th-large"></i> Painel',
                ['action' => 'index'],
                ['class' => 'btn btn-pgm btn-pgm-situacao btn-sm', 'escape' => false]
            ) ?>
            <?= $this->Html->link(
                '<i class="fas fa-chart-bar"></i> Relatórios',
                ['action' => 'relatorios'],
                ['class' => 'btn btn-default btn-sm', 'escape' => false]
            ) ?>
            <?= $this->Html->link(
                '<i class="fas fa-arrow-left"></i> Financeiro',
                ['controller' => 'Financeiro', 'action' => 'index'],
                ['class' => 'btn btn-default btn-sm', 'escape' => false]
            ) ?>
        </div>
    </div>

    <div class="fb-kpis">
        <div class="fb-kpi">
            <div class="fb-kpi-label">Bancos exibidos</div>
            <div class="fb-kpi-value"><?= number_format((int)$metricasCadastro['bancos'], 0, ',', '.') ?></div>
            <div class="fb-kpi-help">Quantidade de registros retornados conforme os filtros atuais.</div>
        </div>
        <div class="fb-kpi">
            <div class="fb-kpi-label">Ativos</div>
            <div class="fb-kpi-value"><?= number_format((int)$metricasCadastro['ativos'], 0, ',', '.') ?></div>
            <div class="fb-kpi-help">Bancos aptos para uso nas rotinas do módulo financeiro.</div>
        </div>
        <div class="fb-kpi">
            <div class="fb-kpi-label">Inativos</div>
            <div class="fb-kpi-value"><?= number_format((int)$metricasCadastro['inativos'], 0, ',', '.') ?></div>
            <div class="fb-kpi-help">Cadastros mantidos apenas para histórico ou revisão.</div>
        </div>
        <div class="fb-kpi">
            <div class="fb-kpi-label">Conta completa</div>
            <div class="fb-kpi-value"><?= number_format((int)$metricasCadastro['conta_completa'], 0, ',', '.') ?></div>
            <div class="fb-kpi-help">Bancos com agência e conta preenchidas corretamente.</div>
        </div>
        <div class="fb-kpi">
            <div class="fb-kpi-label">Conta incompleta</div>
            <div class="fb-kpi-value"><?= number_format((int)$metricasCadastro['conta_incompleta'], 0, ',', '.') ?></div>
            <div class="fb-kpi-help">Cadastros que precisam de revisão para remessa, retorno e conciliação.</div>
        </div>
    </div>

    <div class="fb-shell">
        <div class="fb-window">
            <div class="fb-window-head">
                <div>
                    <div class="fb-window-title">Pc0030 - Bancos</div>
                    <div class="fb-window-sub">Cadastro, pesquisa e manutenção no padrão operacional do ERP.</div>
                </div>

                <div class="fb-window-actions">
                    <?= $this->Html->link(
                        '<i class="fas fa-plus"></i> Incluir',
                        ['action' => 'add'],
                        ['class' => 'btn btn-default btn-sm', 'escape' => false]
                    ) ?>
                    <?= $this->Html->link(
                        '<i class="fas fa-sync"></i> Limpar filtros',
                        ['action' => 'cadastrar'],
                        ['class' => 'btn btn-default btn-sm', 'escape' => false]
                    ) ?>
                </div>
            </div>

            <div class="fb-toolbar">
                <?= $this->Form->create(null, ['type' => 'get']) ?>
                    <div class="fb-toolbar-grid">
                        <div>
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

                        <div>
                            <label for="filtro-nome">Nome do banco</label>
                            <?= $this->Form->control('nome', [
                                'label' => false,
                                'id' => 'filtro-nome',
                                'class' => 'form-control',
                                'value' => $nome,
                                'placeholder' => 'Pesquisar por nome...',
                                'templates' => ['inputContainer' => '{{content}}']
                            ]) ?>
                        </div>

                        <div>
                            <label for="filtro-ativo">Ativo</label>
                            <?= $this->Form->select('ativo', [
                                '' => 'Todos',
                                '1' => 'Ativos',
                                '0' => 'Inativos',
                            ], [
                                'id' => 'filtro-ativo',
                                'class' => 'form-control',
                                'value' => $ativo,
                                'templates' => ['inputContainer' => '{{content}}']
                            ]) ?>
                        </div>

                        <div>
                            <label for="filtro-conta-status">Situação da conta</label>
                            <?= $this->Form->select('conta_status', [
                                '' => 'Todas',
                                'completa' => 'Conta completa',
                                'incompleta' => 'Conta incompleta',
                            ], [
                                'id' => 'filtro-conta-status',
                                'class' => 'form-control',
                                'value' => $contaStatus,
                                'templates' => ['inputContainer' => '{{content}}']
                            ]) ?>
                        </div>

                        <div class="fb-toolbar-actions">
                            <?= $this->Form->button('<i class="fas fa-search"></i> Buscar', [
                                'type' => 'submit',
                                'class' => 'btn btn-pgm btn-pgm-salvar btn-sm',
                                'escape' => false,
                            ]) ?>

                            <?= $this->Html->link(
                                'Limpar',
                                ['action' => 'cadastrar'],
                                ['class' => 'btn btn-default btn-sm']
                            ) ?>
                        </div>
                    </div>
                <?= $this->Form->end() ?>
            </div>

            <div class="fb-toolbar-note">
                Filtros corrigidos: agora o campo superior permite pesquisar por <strong>código bancário</strong>, <strong>número do banco</strong> ou <strong>CNAB</strong>, além do filtro por status da conta.
            </div>

            <div class="fb-layout">
                <div class="fb-card">
                    <div class="fb-card-title">Registro selecionado / painel operacional</div>
                    <div class="fb-card-sub">
                        A primeira linha do resultado é destacada como referência visual. Use a listagem abaixo para abrir o cadastro completo do banco.
                    </div>

                    <div class="fb-summary-line">
                        <span class="fb-pill">
                            <i class="fas fa-layer-group"></i>Total filtrado: <?= number_format((int)$metricasCadastro['bancos'], 0, ',', '.') ?>
                        </span>
                        <span class="fb-pill fb-pill--ok">
                            <i class="fas fa-check-circle"></i>Conta completa: <?= number_format((int)$metricasCadastro['conta_completa'], 0, ',', '.') ?>
                        </span>
                        <span class="fb-pill fb-pill--warn">
                            <i class="fas fa-exclamation-triangle"></i>Conta incompleta: <?= number_format((int)$metricasCadastro['conta_incompleta'], 0, ',', '.') ?>
                        </span>
                    </div>

                    <div class="fb-panel-grid">
                        <div>
                            <label>Código</label>
                            <div class="fb-readonly"><?= h($temSelecionado ? ($bancoSelecionado->codigo_banco ?: '—') : '—') ?></div>
                        </div>
                        <div>
                            <label>Núm. banco</label>
                            <div class="fb-readonly"><?= h($temSelecionado ? ($bancoSelecionado->numero_banco ?: '—') : '—') ?></div>
                        </div>
                        <div>
                            <label>CNAB</label>
                            <div class="fb-readonly"><?= h($temSelecionado ? ($bancoSelecionado->cnab ?: '—') : '—') ?></div>
                        </div>
                        <div>
                            <label>Status</label>
                            <div class="fb-readonly">
                                <?php if ($temSelecionado): ?>
                                    <?= !empty($bancoSelecionado->ativo) ? 'Ativo' : 'Inativo' ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </div>
                        </div>

                        <div style="grid-column:1 / -1;">
                            <label>Nome</label>
                            <div class="fb-readonly"><?= h($temSelecionado ? ($bancoSelecionado->nome ?: '—') : '—') ?></div>
                        </div>
                    </div>

                    <div class="fb-tabs">
                        <div class="fb-tab is-active">Configurações</div>
                        <div class="fb-tab">Contas contábeis</div>
                    </div>

                    <div class="fb-tab-panel">
                        <div class="fb-panel-grid--wide">
                            <label>Número Agência</label>
                            <div class="fb-readonly"><?= h($temSelecionado ? ($bancoSelecionado->numero_agencia ?: '—') : '—') ?></div>

                            <label>Dígito Agência</label>
                            <div class="fb-readonly"><?= h($temSelecionado ? ($bancoSelecionado->digito_agencia ?: '—') : '—') ?></div>

                            <label>Número Conta</label>
                            <div class="fb-readonly"><?= h($temSelecionado ? ($bancoSelecionado->numero_conta ?: '—') : '—') ?></div>

                            <label>Dígito Conta</label>
                            <div class="fb-readonly"><?= h($temSelecionado ? ($bancoSelecionado->digito_conta ?: '—') : '—') ?></div>

                            <label>Código Banco Interno</label>
                            <div class="fb-readonly"><?= h($temSelecionado ? ($bancoSelecionado->codigo_banco_interno ?: '—') : '—') ?></div>

                            <label>Verifica Receber</label>
                            <div class="fb-readonly"><?= h($temSelecionado ? ($bancoSelecionado->verifica_receber ?: '—') : '—') ?></div>

                            <label>Utiliza Endosso</label>
                            <div class="fb-readonly"><?= h($temSelecionado ? ($bancoSelecionado->utiliza_endosso ?: '—') : '—') ?></div>

                            <label>Conta formatada</label>
                            <div class="fb-readonly"><?= h($temSelecionado ? $fmtConta($bancoSelecionado) : '—') ?></div>

                            <label>Observações</label>
                            <div class="fb-readonly fb-readonly--tall" style="grid-column:span 3;"><?= h($temSelecionado ? ($bancoSelecionado->observacoes ?: 'Sem observações cadastradas.') : 'Sem registro selecionado.') ?></div>

                            <label>Logotipo</label>
                            <div style="grid-column:span 3;">
                                <div class="fb-logo-box">
                                    <?php if ($temSelecionado && !empty($bancoSelecionado->logotipo)): ?>
                                        <?= h($bancoSelecionado->logotipo) ?>
                                    <?php else: ?>
                                        Sem logotipo cadastrado
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <?php if ($temSelecionado): ?>
                            <div class="fb-state-box <?= $cadastroIncompleto ? 'fb-state-box--warn' : 'fb-state-box--ok' ?>">
                                <div class="fb-state-title">
                                    <?= $cadastroIncompleto ? 'Cadastro bancário precisa de revisão' : 'Cadastro bancário pronto para uso operacional' ?>
                                </div>
                                <div class="fb-state-text">
                                    <?php if ($cadastroIncompleto): ?>
                                        Preencha agência e conta no formulário clássico para melhorar o uso do banco em conciliação, remessa, retorno e relatórios financeiros.
                                    <?php else: ?>
                                        Este banco já possui dados mínimos de conta preenchidos para uso mais consistente nas rotinas do módulo.
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="fb-quick-actions">
                            <?php if ($temSelecionado): ?>
                                <?= $this->Html->link(
                                    '<i class="fas fa-edit"></i> Abrir formulário clássico',
                                    ['action' => 'edit', $bancoSelecionado->id],
                                    ['class' => 'btn btn-pgm btn-pgm-situacao btn-sm', 'escape' => false]
                                ) ?>
                                <?= $this->Html->link(
                                    '<i class="fas fa-file-export"></i> Ver remessa',
                                    ['action' => 'remessa', '?' => ['banco_id' => $bancoSelecionado->id]],
                                    ['class' => 'btn btn-default btn-sm', 'escape' => false]
                                ) ?>
                                <?= $this->Html->link(
                                    '<i class="fas fa-file-import"></i> Ver retorno',
                                    ['action' => 'retorno'],
                                    ['class' => 'btn btn-default btn-sm', 'escape' => false]
                                ) ?>
                            <?php else: ?>
                                <?= $this->Html->link(
                                    '<i class="fas fa-plus"></i> Criar novo cadastro',
                                    ['action' => 'add'],
                                    ['class' => 'btn btn-pgm btn-pgm-salvar btn-sm', 'escape' => false]
                                ) ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="fb-tab-panel">
                        <div class="fb-card-sub" style="margin-bottom:0;">
                            <strong>Contas contábeis:</strong> área preparada para evolução futura, mantendo a mesma lógica visual do Grid. Hoje o foco operacional do módulo está em:
                            <ul style="margin:8px 0 0 18px; padding:0; color:#5f6368;">
                                <li>cadastro bancário por empresa</li>
                                <li>vínculo de receitas e despesas ao banco</li>
                                <li>conciliação bancária</li>
                                <li>retorno, remessa e relatórios bancários</li>
                            </ul>
                        </div>
                    </div>

                    <div class="fb-card-title" style="margin-top:14px;">Bancos cadastrados</div>
                    <div class="fb-table-wrap">
                        <table class="fb-table">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Banco</th>
                                    <th>Núm Banco</th>
                                    <th>CNAB</th>
                                    <th>Agência</th>
                                    <th>Conta</th>
                                    <th>Situação</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($bancos)): ?>
                                    <tr>
                                        <td colspan="8" class="fb-empty">Nenhum banco encontrado com os filtros informados.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($bancos as $index => $banco): ?>
                                        <?php
                                            $temAgenciaLinha = trim((string)($banco->numero_agencia ?? '')) !== '';
                                            $temContaLinha = trim((string)($banco->numero_conta ?? '')) !== '';
                                            $contaIncompletaLinha = !$temAgenciaLinha || !$temContaLinha;
                                        ?>
                                        <tr class="<?= $index === 0 ? 'fb-row-selected' : '' ?>">
                                            <td><span class="fb-code"><?= h($banco->codigo_banco ?: '—') ?></span></td>
                                            <td>
                                                <strong><?= h($banco->nome ?: '—') ?></strong>
                                                <?php if (!empty($banco->codigo_banco_interno)): ?>
                                                    <span class="fb-note">Interno: <?= h($banco->codigo_banco_interno) ?></span>
                                                <?php endif; ?>
                                                <?php if ($contaIncompletaLinha): ?>
                                                    <span class="fb-note">Cadastro incompleto para rotina bancária.</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= h($banco->numero_banco ?: '—') ?></td>
                                            <td><?= h($banco->cnab ?: '—') ?></td>
                                            <td>
                                                <?= h($banco->numero_agencia ?: '—') ?><?= !empty($banco->digito_agencia) ? '-' . h($banco->digito_agencia) : '' ?>
                                                <?php if (!$temAgenciaLinha): ?>
                                                    <span class="fb-note">Agência não informada</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?= h($banco->numero_conta ?: '—') ?><?= !empty($banco->digito_conta) ? '-' . h($banco->digito_conta) : '' ?>
                                                <?php if (!$temContaLinha): ?>
                                                    <span class="fb-note">Conta não informada</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($banco->ativo)): ?>
                                                    <span class="fb-status fb-status--on">Ativo</span>
                                                <?php else: ?>
                                                    <span class="fb-status fb-status--off">Inativo</span>
                                                <?php endif; ?>

                                                <?php if ($contaIncompletaLinha): ?>
                                                    <div style="margin-top:5px;">
                                                        <span class="fb-status fb-status--warn">Conta incompleta</span>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td style="white-space:nowrap;">
                                                <?= $this->Html->link(
                                                    'Editar',
                                                    ['action' => 'edit', $banco->id],
                                                    ['class' => 'btn btn-xs btn-pgm btn-pgm-situacao']
                                                ) ?>
                                                <?= $this->Html->link(
                                                    'Remessa',
                                                    ['action' => 'remessa', '?' => ['banco_id' => $banco->id]],
                                                    ['class' => 'btn btn-xs btn-default']
                                                ) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="fb-grid-footer">
                        <div class="fb-card-sub" style="margin:0;">
                            Fluxo sugerido: filtre no topo, revise o painel operacional e depois abra o formulário clássico do banco desejado.
                        </div>
                        <div class="fb-actions">
                            <?= $this->Html->link(
                                '<i class="fas fa-plus"></i> Incluir',
                                ['action' => 'add'],
                                ['class' => 'btn btn-pgm btn-pgm-salvar btn-sm', 'escape' => false]
                            ) ?>
                        </div>
                    </div>
                </div>

                <div class="fb-card">
                    <div class="fb-card-title">Busca / catálogo bancário</div>
                    <div class="fb-card-sub">
                        Apoio rápido para localizar o banco e abrir a inclusão já com o código desejado. O catálogo usa o termo informado em <strong>código</strong> ou <strong>nome</strong>.
                    </div>

                    <div class="fb-catalogo-list">
                        <?php if (empty($catalogo)): ?>
                            <div class="fb-empty">
                                Nenhum banco localizado no catálogo para esta busca.
                            </div>
                        <?php else: ?>
                            <?php foreach ($catalogo as $item): ?>
                                <div class="fb-catalogo-item">
                                    <div class="fb-catalogo-top">
                                        <div class="fb-catalogo-cod"><?= h($item['codigo'] ?? '—') ?></div>
                                        <div class="fb-catalogo-meta">CNAB: <?= h($item['cnab'] ?? '—') ?></div>
                                    </div>
                                    <div class="fb-catalogo-nome"><?= h($item['nome'] ?? '—') ?></div>
                                    <?php if (!empty($item['nome_completo'])): ?>
                                        <div class="fb-catalogo-meta"><?= h($item['nome_completo']) ?></div>
                                    <?php endif; ?>

                                    <div class="fb-quick-actions">
                                        <?= $this->Html->link(
                                            'Incluir com este código',
                                            ['action' => 'add', '?' => ['codigo' => $item['codigo'] ?? '']],
                                            ['class' => 'btn btn-xs btn-pgm btn-pgm-salvar']
                                        ) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="fb-card-title" style="margin-top:14px;">Leitura operacional</div>
                    <div class="fb-card-sub">
                        Esta tela foi reorganizada para seguir um fluxo mais claro:
                        <ul style="margin:8px 0 0 18px; padding:0; color:#5f6368;">
                            <li>filtrar corretamente por código, nome, ativo e situação da conta</li>
                            <li>ler os KPIs do topo para avaliar o estado do cadastro</li>
                            <li>usar o painel central para inspeção rápida do primeiro resultado</li>
                            <li>abrir o formulário clássico para inclusão ou edição completa</li>
                            <li>usar o catálogo como apoio para código bancário e CNAB</li>
                        </ul>
                    </div>

                    <div class="fb-summary-line" style="margin-top:12px;">
                        <span class="fb-pill">Filtro código: <?= h($codigo !== '' ? $codigo : '—') ?></span>
                        <span class="fb-pill">Filtro nome: <?= h($nome !== '' ? $nome : '—') ?></span>
                        <span class="fb-pill">Ativo: <?= h($ativo === '' ? 'Todos' : ($ativo === '1' ? 'Ativos' : 'Inativos')) ?></span>
                        <span class="fb-pill">Conta: <?= h($contaStatus === '' ? 'Todas' : ($contaStatus === 'completa' ? 'Completa' : 'Incompleta')) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
