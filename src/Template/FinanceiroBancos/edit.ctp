<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Financeiro', ['controller' => 'Financeiro', 'action' => 'index']);
$this->Breadcrumbs->add('Bancos', ['controller' => 'FinanceiroBancos', 'action' => 'index']);
$this->Breadcrumbs->add('Cadastro de Bancos', ['controller' => 'FinanceiroBancos', 'action' => 'cadastrar']);
$this->Breadcrumbs->add('Editar banco');

$catalogo = $catalogo ?? [];
$banco = $banco ?? null;

$ativoValue = isset($banco->ativo) ? (bool)$banco->ativo : true;
$codigoBanco = trim((string)($banco->codigo_banco ?? ''));
$numeroBanco = trim((string)($banco->numero_banco ?? ''));
$cnabBanco = trim((string)($banco->cnab ?? ''));
$nomeBanco = trim((string)($banco->nome ?? ''));
$agencia = trim((string)($banco->numero_agencia ?? ''));
$digitoAgencia = trim((string)($banco->digito_agencia ?? ''));
$conta = trim((string)($banco->numero_conta ?? ''));
$digitoConta = trim((string)($banco->digito_conta ?? ''));
$codigoInterno = trim((string)($banco->codigo_banco_interno ?? ''));
$verificaReceber = trim((string)($banco->verifica_receber ?? ''));
$utilizaEndosso = trim((string)($banco->utiliza_endosso ?? ''));
$logotipo = trim((string)($banco->logotipo ?? ''));
$observacoes = trim((string)($banco->observacoes ?? ''));

$agenciaFmt = $agencia !== '' ? $agencia . ($digitoAgencia !== '' ? '-' . $digitoAgencia : '') : '—';
$contaFmt = $conta !== '' ? $conta . ($digitoConta !== '' ? '-' . $digitoConta : '') : '—';
$cadastroIncompleto = $agencia === '' || $conta === '';
$temCnab = $cnabBanco !== '';
$temCodigoInterno = $codigoInterno !== '';
$temObservacoes = $observacoes !== '';

$statusOperacional = 'Pronto para uso';
$statusOperacionalClasse = 'fb-op-badge--ok';
$statusOperacionalTexto = 'Cadastro apto para uso em rotinas de remessa, retorno, conciliação e relatórios.';

if ($cadastroIncompleto && !$ativoValue) {
    $statusOperacional = 'Inativo e incompleto';
    $statusOperacionalClasse = 'fb-op-badge--off';
    $statusOperacionalTexto = 'O banco está inativo e ainda precisa de revisão cadastral antes de qualquer uso operacional.';
} elseif ($cadastroIncompleto) {
    $statusOperacional = 'Revisão obrigatória';
    $statusOperacionalClasse = 'fb-op-badge--warn';
    $statusOperacionalTexto = 'Preencha agência e conta para tornar o banco confiável nas rotinas bancárias.';
} elseif (!$ativoValue) {
    $statusOperacional = 'Inativo';
    $statusOperacionalClasse = 'fb-op-badge--off';
    $statusOperacionalTexto = 'O banco está bem cadastrado, mas marcado como inativo para operação.';
}

$conferencia = [
    [
        'label' => 'Código bancário',
        'ok' => $codigoBanco !== '',
        'texto_ok' => 'Código informado.',
        'texto_erro' => 'Informe o código bancário oficial.',
    ],
    [
        'label' => 'Número do banco',
        'ok' => $numeroBanco !== '',
        'texto_ok' => 'Número do banco preenchido.',
        'texto_erro' => 'Revise o número do banco.',
    ],
    [
        'label' => 'CNAB',
        'ok' => $temCnab,
        'texto_ok' => 'CNAB preenchido.',
        'texto_erro' => 'CNAB não informado. Revise se o banco exige esse dado.',
    ],
    [
        'label' => 'Nome do banco',
        'ok' => $nomeBanco !== '',
        'texto_ok' => 'Nome informado.',
        'texto_erro' => 'Preencha o nome do banco.',
    ],
    [
        'label' => 'Agência',
        'ok' => $agencia !== '',
        'texto_ok' => 'Agência preenchida.',
        'texto_erro' => 'Agência não informada.',
    ],
    [
        'label' => 'Conta',
        'ok' => $conta !== '',
        'texto_ok' => 'Conta preenchida.',
        'texto_erro' => 'Conta não informada.',
    ],
    [
        'label' => 'Código interno',
        'ok' => $temCodigoInterno,
        'texto_ok' => 'Código interno informado.',
        'texto_erro' => 'Código interno opcional não informado.',
    ],
];

$totalConferenciaOk = 0;
foreach ($conferencia as $itemConferencia) {
    if (!empty($itemConferencia['ok'])) {
        $totalConferenciaOk++;
    }
}

$catalogoRelacionado = [];
foreach ($catalogo as $item) {
    $itemCodigo = trim((string)($item['codigo'] ?? ''));
    $itemCnab = trim((string)($item['cnab'] ?? ''));
    $itemNome = trim((string)($item['nome'] ?? ''));

    if (
        ($codigoBanco !== '' && $itemCodigo === $codigoBanco) ||
        ($cnabBanco !== '' && $itemCnab !== '' && $itemCnab === $cnabBanco) ||
        ($nomeBanco !== '' && mb_strtolower($itemNome) === mb_strtolower($nomeBanco))
    ) {
        $catalogoRelacionado[] = $item;
    }
}

if (empty($catalogoRelacionado) && !empty($catalogo)) {
    $catalogoRelacionado = array_slice($catalogo, 0, 10);
}
?>
<style>
.fb-op-root {
    font-family:'DM Sans',Tahoma,Arial,sans-serif;
    color:#1f2937;
}
.fb-op-topbar {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    padding:16px 20px 12px;
    border-bottom:1px solid rgba(255,255,255,.08);
    flex-wrap:wrap;
}
.fb-op-title {
    font-size:20px;
    font-weight:700;
    color:#e6edf3;
    margin:0;
}
.fb-op-title i {
    color:#5cdbc0;
    margin-right:8px;
}
.fb-op-subtitle {
    margin-top:4px;
    color:#8b949e;
    font-size:12.5px;
}
.fb-op-actions {
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}

.fb-op-kpis {
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(210px,1fr));
    gap:14px;
    padding:20px 20px 0;
}
.fb-op-kpi {
    background:#161b22;
    border:1px solid rgba(255,255,255,.07);
    border-radius:12px;
    padding:16px 18px;
}
.fb-op-kpi-label {
    color:#7d8590;
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:.05em;
    font-weight:700;
    margin-bottom:6px;
}
.fb-op-kpi-value {
    color:#e6edf3;
    font-size:24px;
    font-weight:700;
    line-height:1.1;
}
.fb-op-kpi-help {
    margin-top:8px;
    color:#8b949e;
    font-size:11.5px;
    line-height:1.45;
}

.fb-op-alert {
    margin:16px 20px 0;
    background:rgba(29,158,117,.08);
    border:1px solid rgba(29,158,117,.16);
    color:#c9d1d9;
    border-radius:10px;
    padding:12px 14px;
    font-size:12.5px;
    line-height:1.6;
}
.fb-op-alert strong {
    color:#5cdbc0;
}

.fb-op-shell {
    margin:16px 20px 20px;
    background:#d9d9d9;
    border:1px solid #8d8d8d;
    border-radius:4px;
    overflow:hidden;
}
.fb-op-windowbar {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    padding:8px 12px;
    background:linear-gradient(to bottom,#efefef,#d6d6d6);
    border-bottom:1px solid #a8a8a8;
    flex-wrap:wrap;
}
.fb-op-windowtitle {
    font-size:13px;
    font-weight:700;
    color:#374151;
}
.fb-op-windowmeta {
    font-size:11px;
    color:#6b7280;
}
.fb-op-body {
    padding:14px;
    background:#dcdcdc;
}
.fb-op-form {
    background:#efefef;
    border:1px solid #b8b8b8;
    padding:12px;
}
.fb-op-layout {
    display:grid;
    grid-template-columns:minmax(0,1.35fr) minmax(320px,.85fr);
    gap:14px;
}
.fb-op-main,
.fb-op-side {
    min-width:0;
}
.fb-op-panel {
    border:1px solid #b8b8b8;
    background:#ffffff;
    padding:12px;
}
.fb-op-panel + .fb-op-panel {
    margin-top:12px;
}
.fb-op-panel-title {
    font-size:13px;
    font-weight:700;
    color:#374151;
    margin:0 0 10px;
}
.fb-op-panel-sub {
    color:#6b7280;
    font-size:11px;
    line-height:1.5;
    margin-bottom:12px;
}
.fb-op-row {
    display:grid;
    grid-template-columns:90px 1fr 90px 1fr 70px 120px;
    gap:8px 10px;
    align-items:center;
    margin-bottom:8px;
}
.fb-op-row--name {
    grid-template-columns:90px 1fr;
}
.fb-op-row--status {
    grid-template-columns:90px 1fr 90px 1fr;
}
.fb-op-label {
    font-size:12px;
    color:#111827;
    text-align:right;
}
.fb-op-input,
.fb-op-form textarea,
.fb-op-form select {
    width:100%;
    min-height:29px;
    padding:4px 6px;
    background:#ffffff;
    border:1px solid #9ca3af;
    border-radius:2px;
    color:#111827;
    font-size:12px;
    box-sizing:border-box;
}
.fb-op-form textarea {
    min-height:92px;
    resize:vertical;
}
.fb-op-input--sm {
    max-width:96px;
}
.fb-op-input--xs {
    max-width:64px;
}
.fb-op-checkboxwrap {
    display:flex;
    align-items:center;
    gap:8px;
    font-size:12px;
    color:#111827;
}
.fb-op-checkboxwrap input[type=checkbox] {
    margin:0;
}
.fb-op-tabs {
    display:flex;
    gap:4px;
    margin:14px 0 0;
}
.fb-op-tab {
    padding:6px 12px;
    background:#f4f4f4;
    border:1px solid #b8b8b8;
    border-bottom:none;
    border-top-left-radius:4px;
    border-top-right-radius:4px;
    font-size:12px;
    color:#374151;
}
.fb-op-tab--active {
    background:#ffffff;
    font-weight:700;
}
.fb-op-tab--muted {
    color:#6b7280;
}
.fb-op-tabpanel {
    border:1px solid #b8b8b8;
    background:#ffffff;
    padding:12px;
}
.fb-op-tabpanel + .fb-op-tabpanel {
    margin-top:10px;
}
.fb-op-panel-grid {
    display:grid;
    grid-template-columns:minmax(0,1.2fr) minmax(280px,.8fr);
    gap:14px;
}
.fb-op-fieldblock {
    display:grid;
    grid-template-columns:130px 1fr;
    gap:8px 10px;
    align-items:center;
    margin-bottom:8px;
}
.fb-op-fieldblock--obs {
    align-items:start;
}
.fb-op-sidebox {
    border:1px solid #d1d5db;
    background:#f8fafc;
    padding:10px;
}
.fb-op-sidebox + .fb-op-sidebox {
    margin-top:12px;
}
.fb-op-side-title {
    font-size:12px;
    font-weight:700;
    color:#374151;
    margin:0 0 8px;
}
.fb-op-side-meta {
    font-size:11px;
    color:#6b7280;
    margin:0 0 10px;
    line-height:1.5;
}
.fb-op-side-list {
    border:1px solid #c7c7c7;
    background:#ffffff;
    max-height:240px;
    overflow:auto;
}
.fb-op-side-item {
    padding:8px 10px;
    border-bottom:1px solid #ececec;
    font-size:12px;
    color:#111827;
}
.fb-op-side-item:last-child {
    border-bottom:none;
}
.fb-op-side-code {
    color:#0f766e;
    font-weight:700;
    margin-right:6px;
}
.fb-op-note {
    margin-top:8px;
    font-size:11px;
    color:#6b7280;
    line-height:1.45;
}
.fb-op-preview {
    border:1px dashed #c4c4c4;
    background:#fafafa;
    min-height:150px;
    display:flex;
    align-items:center;
    justify-content:center;
    text-align:center;
    padding:16px;
    color:#6b7280;
    font-size:12px;
    line-height:1.55;
}
.fb-op-preview strong {
    display:block;
    color:#374151;
    margin-bottom:6px;
}
.fb-op-badge {
    display:inline-block;
    padding:3px 8px;
    border-radius:999px;
    font-size:10.5px;
    font-weight:700;
}
.fb-op-badge--ok {
    background:rgba(63,185,80,.14);
    color:#198754;
}
.fb-op-badge--warn {
    background:rgba(255,193,7,.18);
    color:#9a6700;
}
.fb-op-badge--off {
    background:rgba(248,81,73,.16);
    color:#b42318;
}
.fb-op-checklist {
    display:flex;
    flex-direction:column;
    gap:8px;
}
.fb-op-checkitem {
    display:flex;
    align-items:flex-start;
    gap:8px;
    padding:8px 10px;
    border:1px solid #d9dde3;
    border-radius:8px;
    background:#fff;
}
.fb-op-checkicon {
    width:18px;
    height:18px;
    border-radius:50%;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    font-size:11px;
    font-weight:700;
    flex:0 0 auto;
    margin-top:1px;
}
.fb-op-checkicon--ok {
    background:#dcfce7;
    color:#166534;
}
.fb-op-checkicon--warn {
    background:#fff2cf;
    color:#946200;
}
.fb-op-checktext strong {
    display:block;
    color:#374151;
    font-size:12px;
    margin-bottom:2px;
}
.fb-op-checktext span {
    color:#6b7280;
    font-size:11px;
    line-height:1.45;
}
.fb-op-summary {
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:8px;
}
.fb-op-summary-box {
    border:1px solid #d9dde3;
    border-radius:8px;
    background:#fff;
    padding:10px;
}
.fb-op-summary-label {
    color:#6b7280;
    font-size:10.5px;
    text-transform:uppercase;
    letter-spacing:.05em;
    font-weight:700;
    margin-bottom:4px;
}
.fb-op-summary-value {
    color:#111827;
    font-size:13px;
    font-weight:700;
}
.fb-op-statusline {
    margin-top:12px;
    padding-top:10px;
    border-top:1px solid #d7d7d7;
    display:flex;
    gap:12px;
    flex-wrap:wrap;
    font-size:11px;
    color:#6b7280;
}
.fb-op-footer {
    display:flex;
    justify-content:space-between;
    gap:10px;
    margin-top:14px;
    flex-wrap:wrap;
}
.fb-op-footer-left,
.fb-op-footer-right {
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}
.fb-op-inline-help {
    margin-top:8px;
    font-size:11px;
    color:#6b7280;
    line-height:1.45;
}
.fb-op-emphasis {
    color:#374151;
    font-weight:700;
}

@media (max-width: 1180px) {
    .fb-op-layout,
    .fb-op-panel-grid {
        grid-template-columns:1fr;
    }
}
@media (max-width: 1040px) {
    .fb-op-row,
    .fb-op-row--name,
    .fb-op-row--status,
    .fb-op-fieldblock,
    .fb-op-fieldblock--obs {
        grid-template-columns:1fr;
    }
    .fb-op-label {
        text-align:left;
    }
}
@media (max-width: 640px) {
    .fb-op-summary {
        grid-template-columns:1fr;
    }
}
</style>

<div class="fb-op-root">
    <div class="fb-op-topbar">
        <div>
            <h1 class="fb-op-title">
                <i class="fas fa-university"></i>Editar banco<?= !empty($banco->id) ? ' #' . (int)$banco->id : '' ?>
            </h1>
            <div class="fb-op-subtitle">
                Refinamento do formulário com conferência guiada, status operacional e revisão rápida do cadastro bancário.
            </div>
        </div>

        <div class="fb-op-actions">
            <?= $this->Html->link(
                '<i class="fas fa-list"></i> Cadastro',
                ['action' => 'cadastrar'],
                ['class' => 'btn btn-default btn-sm', 'escape' => false]
            ) ?>
            <?= $this->Html->link(
                '<i class="fas fa-file-export"></i> Remessas',
                ['action' => 'relacaoRemessas'],
                ['class' => 'btn btn-default btn-sm', 'escape' => false]
            ) ?>
            <?= $this->Html->link(
                '<i class="fas fa-arrow-left"></i> Voltar',
                ['action' => 'index'],
                ['class' => 'btn btn-default btn-sm', 'escape' => false]
            ) ?>
        </div>
    </div>

    <div class="fb-op-kpis">
        <div class="fb-op-kpi">
            <div class="fb-op-kpi-label">Status operacional</div>
            <div class="fb-op-kpi-value" style="font-size:20px;"><?= h($statusOperacional) ?></div>
            <div class="fb-op-kpi-help"><?= h($statusOperacionalTexto) ?></div>
        </div>

        <div class="fb-op-kpi">
            <div class="fb-op-kpi-label">Conferência concluída</div>
            <div class="fb-op-kpi-value"><?= number_format($totalConferenciaOk, 0, ',', '.') ?>/<?= number_format(count($conferencia), 0, ',', '.') ?></div>
            <div class="fb-op-kpi-help">Itens essenciais validados no cadastro atual do banco.</div>
        </div>

        <div class="fb-op-kpi">
            <div class="fb-op-kpi-label">Conta formatada</div>
            <div class="fb-op-kpi-value" style="font-size:18px;"><?= h($agenciaFmt) ?> / <?= h($contaFmt) ?></div>
            <div class="fb-op-kpi-help">Leitura rápida da conta bancária configurada no cadastro.</div>
        </div>

        <div class="fb-op-kpi">
            <div class="fb-op-kpi-label">Situação do cadastro</div>
            <div class="fb-op-kpi-value"><?= $cadastroIncompleto ? 'Atenção' : 'OK' ?></div>
            <div class="fb-op-kpi-help"><?= $cadastroIncompleto ? 'Agência e conta precisam de revisão.' : 'Estrutura mínima preenchida para uso bancário.' ?></div>
        </div>

        <div class="fb-op-kpi">
            <div class="fb-op-kpi-label">Catálogo relacionado</div>
            <div class="fb-op-kpi-value"><?= number_format(count($catalogoRelacionado), 0, ',', '.') ?></div>
            <div class="fb-op-kpi-help">Itens de referência encontrados para conferência rápida do banco.</div>
        </div>
    </div>

    <div class="fb-op-alert">
        <strong>Conferência guiada:</strong> revise primeiro <strong>código</strong>, <strong>CNAB</strong>, <strong>agência</strong> e <strong>conta</strong>.
        Se o banco estiver ativo e com cadastro incompleto, a confiabilidade das rotinas de remessa, retorno e conciliação fica reduzida.
    </div>

    <div class="fb-op-shell">
        <div class="fb-op-windowbar">
            <div class="fb-op-windowtitle">Pc0030 - Bancos</div>
            <div class="fb-op-windowmeta">Versão web • Financeiro • edição assistida</div>
        </div>

        <div class="fb-op-body">
            <div class="fb-op-form">
                <?= $this->Form->create($banco) ?>

                <div class="fb-op-layout">
                    <div class="fb-op-main">
                        <div class="fb-op-panel">
                            <h2 class="fb-op-panel-title">Identificação principal</h2>
                            <div class="fb-op-panel-sub">
                                Ajuste os dados básicos do banco e confira a aderência ao catálogo e às rotinas do financeiro.
                            </div>

                            <div class="fb-op-row">
                                <div class="fb-op-label">Código:</div>
                                <div>
                                    <?= $this->Form->control('codigo_banco', [
                                        'label' => false,
                                        'class' => 'fb-op-input',
                                        'maxlength' => 10,
                                        'id' => 'codigo-banco',
                                        'placeholder' => 'Ex: 756'
                                    ]) ?>
                                </div>

                                <div class="fb-op-label">Núm. Banco:</div>
                                <div>
                                    <?= $this->Form->control('numero_banco', [
                                        'label' => false,
                                        'class' => 'fb-op-input',
                                        'maxlength' => 10,
                                        'id' => 'numero-banco',
                                        'placeholder' => 'Ex: 756'
                                    ]) ?>
                                </div>

                                <div class="fb-op-label">CNAB:</div>
                                <div>
                                    <?= $this->Form->control('cnab', [
                                        'label' => false,
                                        'class' => 'fb-op-input',
                                        'maxlength' => 10,
                                        'id' => 'cnab-banco',
                                        'placeholder' => 'Ex: 240'
                                    ]) ?>
                                </div>
                            </div>

                            <div class="fb-op-row fb-op-row--name">
                                <div class="fb-op-label">Nome:</div>
                                <div>
                                    <?= $this->Form->control('nome', [
                                        'label' => false,
                                        'class' => 'fb-op-input',
                                        'maxlength' => 255,
                                        'id' => 'nome-banco',
                                        'placeholder' => 'Razão social / nome do banco'
                                    ]) ?>
                                </div>
                            </div>

                            <div class="fb-op-row fb-op-row--status">
                                <div class="fb-op-label">Situação:</div>
                                <div class="fb-op-checkboxwrap">
                                    <?= $this->Form->checkbox('ativo', [
                                        'checked' => $ativoValue,
                                        'hiddenField' => true,
                                        'value' => 1
                                    ]) ?>
                                    <span>Ativo</span>
                                </div>

                                <div class="fb-op-label">Operação:</div>
                                <div>
                                    <span class="fb-op-badge <?= h($statusOperacionalClasse) ?>"><?= h($statusOperacional) ?></span>
                                </div>
                            </div>

                            <div class="fb-op-inline-help">
                                Dica: mantenha <span class="fb-op-emphasis">código</span>, <span class="fb-op-emphasis">número do banco</span> e
                                <span class="fb-op-emphasis">CNAB</span> consistentes com a referência operacional usada pela empresa.
                            </div>

                            <div class="fb-op-tabs">
                                <div class="fb-op-tab fb-op-tab--active">Configurações</div>
                                <div class="fb-op-tab fb-op-tab--muted">Contas Contábeis</div>
                            </div>

                            <div class="fb-op-tabpanel">
                                <div class="fb-op-panel-grid">
                                    <div>
                                        <div class="fb-op-fieldblock">
                                            <div class="fb-op-label">Número Agência:</div>
                                            <div>
                                                <?= $this->Form->control('numero_agencia', [
                                                    'label' => false,
                                                    'class' => 'fb-op-input fb-op-input--sm',
                                                    'maxlength' => 20,
                                                    'placeholder' => '3037'
                                                ]) ?>
                                            </div>
                                        </div>

                                        <div class="fb-op-fieldblock">
                                            <div class="fb-op-label">Dígito Agência:</div>
                                            <div>
                                                <?= $this->Form->control('digito_agencia', [
                                                    'label' => false,
                                                    'class' => 'fb-op-input fb-op-input--xs',
                                                    'maxlength' => 5,
                                                    'placeholder' => '0'
                                                ]) ?>
                                            </div>
                                        </div>

                                        <div class="fb-op-fieldblock">
                                            <div class="fb-op-label">Número Conta:</div>
                                            <div>
                                                <?= $this->Form->control('numero_conta', [
                                                    'label' => false,
                                                    'class' => 'fb-op-input',
                                                    'maxlength' => 30,
                                                    'placeholder' => '123456'
                                                ]) ?>
                                            </div>
                                        </div>

                                        <div class="fb-op-fieldblock">
                                            <div class="fb-op-label">Dígito Conta:</div>
                                            <div>
                                                <?= $this->Form->control('digito_conta', [
                                                    'label' => false,
                                                    'class' => 'fb-op-input fb-op-input--xs',
                                                    'maxlength' => 5,
                                                    'placeholder' => '7'
                                                ]) ?>
                                            </div>
                                        </div>

                                        <div class="fb-op-fieldblock">
                                            <div class="fb-op-label">Código Interno:</div>
                                            <div>
                                                <?= $this->Form->control('codigo_banco_interno', [
                                                    'label' => false,
                                                    'class' => 'fb-op-input',
                                                    'maxlength' => 50,
                                                    'placeholder' => 'Código interno / ERP'
                                                ]) ?>
                                            </div>
                                        </div>

                                        <div class="fb-op-fieldblock">
                                            <div class="fb-op-label">Verifica Receber:</div>
                                            <div>
                                                <?= $this->Form->control('verifica_receber', [
                                                    'label' => false,
                                                    'class' => 'fb-op-input',
                                                    'maxlength' => 100,
                                                    'placeholder' => 'Parâmetro de recebimento'
                                                ]) ?>
                                            </div>
                                        </div>

                                        <div class="fb-op-fieldblock">
                                            <div class="fb-op-label">Utiliza Endosso:</div>
                                            <div>
                                                <?= $this->Form->control('utiliza_endosso', [
                                                    'label' => false,
                                                    'class' => 'fb-op-input fb-op-input--sm',
                                                    'maxlength' => 10,
                                                    'placeholder' => 'N'
                                                ]) ?>
                                            </div>
                                        </div>

                                        <div class="fb-op-fieldblock">
                                            <div class="fb-op-label">Logotipo:</div>
                                            <div>
                                                <?= $this->Form->control('logotipo', [
                                                    'label' => false,
                                                    'class' => 'fb-op-input',
                                                    'maxlength' => 255,
                                                    'placeholder' => 'URL ou caminho do logotipo'
                                                ]) ?>
                                            </div>
                                        </div>

                                        <div class="fb-op-fieldblock fb-op-fieldblock--obs">
                                            <div class="fb-op-label">Observações:</div>
                                            <div>
                                                <?= $this->Form->textarea('observacoes', [
                                                    'class' => 'fb-op-input',
                                                    'rows' => 4,
                                                    'placeholder' => 'Observações sobre cobrança, remessa, retorno, convênio e uso interno.'
                                                ]) ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <div class="fb-op-sidebox">
                                            <div class="fb-op-side-title">Resumo operacional</div>
                                            <p class="fb-op-side-meta">
                                                Esta visão resume rapidamente o estado do cadastro antes de salvar alterações.
                                            </p>

                                            <div class="fb-op-summary">
                                                <div class="fb-op-summary-box">
                                                    <div class="fb-op-summary-label">Conta</div>
                                                    <div class="fb-op-summary-value"><?= h($agenciaFmt) ?> / <?= h($contaFmt) ?></div>
                                                </div>
                                                <div class="fb-op-summary-box">
                                                    <div class="fb-op-summary-label">CNAB</div>
                                                    <div class="fb-op-summary-value"><?= h($cnabBanco !== '' ? $cnabBanco : '—') ?></div>
                                                </div>
                                                <div class="fb-op-summary-box">
                                                    <div class="fb-op-summary-label">Código interno</div>
                                                    <div class="fb-op-summary-value"><?= h($codigoInterno !== '' ? $codigoInterno : '—') ?></div>
                                                </div>
                                                <div class="fb-op-summary-box">
                                                    <div class="fb-op-summary-label">Endosso</div>
                                                    <div class="fb-op-summary-value"><?= h($utilizaEndosso !== '' ? $utilizaEndosso : '—') ?></div>
                                                </div>
                                            </div>

                                            <div class="fb-op-note">
                                                <?= h($statusOperacionalTexto) ?>
                                            </div>
                                        </div>

                                        <div class="fb-op-sidebox">
                                            <div class="fb-op-side-title">Conferência guiada</div>
                                            <p class="fb-op-side-meta">
                                                Antes de salvar, confirme os itens abaixo para reduzir inconsistências operacionais.
                                            </p>

                                            <div class="fb-op-checklist">
                                                <?php foreach ($conferencia as $itemConferencia): ?>
                                                    <div class="fb-op-checkitem">
                                                        <span class="fb-op-checkicon <?= !empty($itemConferencia['ok']) ? 'fb-op-checkicon--ok' : 'fb-op-checkicon--warn' ?>">
                                                            <?= !empty($itemConferencia['ok']) ? '✓' : '!' ?>
                                                        </span>
                                                        <div class="fb-op-checktext">
                                                            <strong><?= h($itemConferencia['label']) ?></strong>
                                                            <span><?= h(!empty($itemConferencia['ok']) ? $itemConferencia['texto_ok'] : $itemConferencia['texto_erro']) ?></span>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="fb-op-tabpanel">
                                <div class="fb-op-panel-sub" style="margin-bottom:0;">
                                    <strong>Contas contábeis:</strong> área preservada para evolução futura do módulo.
                                    O foco atual da edição bancária está na consistência cadastral para uso em:
                                    <ul style="margin:8px 0 0 18px; padding:0; color:#5f6368;">
                                        <li>remessa bancária</li>
                                        <li>retorno bancário</li>
                                        <li>conciliação</li>
                                        <li>relatórios e previsões por banco</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="fb-op-statusline">
                                <div><strong>ID:</strong> <?= !empty($banco->id) ? (int)$banco->id : '—' ?></div>
                                <div><strong>Código:</strong> <?= h($codigoBanco !== '' ? $codigoBanco : '—') ?></div>
                                <div><strong>Núm. Banco:</strong> <?= h($numeroBanco !== '' ? $numeroBanco : '—') ?></div>
                                <div><strong>CNAB:</strong> <?= h($cnabBanco !== '' ? $cnabBanco : '—') ?></div>
                                <div><strong>Status:</strong> <?= h($statusOperacional) ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="fb-op-side">
                        <div class="fb-op-sidebox">
                            <div class="fb-op-side-title">Catálogo / apoio de conferência</div>
                            <p class="fb-op-side-meta">
                                Referência para conferir código, nome e CNAB antes de salvar o cadastro alterado.
                            </p>

                            <div class="fb-op-side-list">
                                <?php if (empty($catalogoRelacionado)): ?>
                                    <div class="fb-op-preview">
                                        <div>
                                            <strong>Nenhuma referência encontrada</strong>
                                            Revise código e CNAB para localizar melhor o banco no catálogo de apoio.
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($catalogoRelacionado as $item): ?>
                                        <div class="fb-op-side-item">
                                            <span class="fb-op-side-code"><?= h($item['codigo'] ?? '') ?></span>
                                            <?= h($item['nome'] ?? '') ?>
                                            <?php if (!empty($item['cnab'])): ?>
                                                <div class="fb-op-note">CNAB: <?= h($item['cnab']) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($item['nome_completo'])): ?>
                                                <div class="fb-op-note"><?= h($item['nome_completo']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <div class="fb-op-note">
                                Use esta referência principalmente se o código bancário ou o CNAB tiverem sido alterados durante a edição.
                            </div>
                        </div>

                        <div class="fb-op-sidebox">
                            <div class="fb-op-side-title">Leitura rápida do cadastro</div>
                            <p class="fb-op-side-meta">
                                Resumo textual para conferência antes da gravação final.
                            </p>

                            <div class="fb-op-preview">
                                <div>
                                    <strong><?= h($nomeBanco !== '' ? $nomeBanco : 'Banco sem nome') ?></strong>
                                    Código <?= h($codigoBanco !== '' ? $codigoBanco : '—') ?>
                                    • Núm. banco <?= h($numeroBanco !== '' ? $numeroBanco : '—') ?>
                                    • CNAB <?= h($cnabBanco !== '' ? $cnabBanco : '—') ?>
                                    <br><br>
                                    Agência <?= h($agenciaFmt) ?> / Conta <?= h($contaFmt) ?>
                                    <br><br>
                                    <?= h($statusOperacionalTexto) ?>
                                </div>
                            </div>

                            <?php if ($cadastroIncompleto): ?>
                                <div class="fb-op-note" style="color:#9a6700;">
                                    Para operação bancária consistente, revise principalmente agência e conta antes de salvar.
                                </div>
                            <?php endif; ?>

                            <?php if (!$temObservacoes): ?>
                                <div class="fb-op-note">
                                    Nenhuma observação cadastrada. Considere registrar detalhes de convênio, cobrança ou rotina interna.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="fb-op-footer">
                    <div class="fb-op-footer-left">
                        <?= $this->Form->postLink(
                            '<i class="fas fa-trash-alt"></i> Excluir',
                            ['action' => 'delete', $banco->id],
                            [
                                'class' => 'btn btn-danger',
                                'escape' => false,
                                'confirm' => 'Deseja realmente excluir este banco? Esta ação não pode ser desfeita.'
                            ]
                        ) ?>
                    </div>

                    <div class="fb-op-footer-right">
                        <?= $this->Html->link(
                            'Cancelar',
                            ['action' => 'cadastrar'],
                            ['class' => 'btn btn-default']
                        ) ?>
                        <?= $this->Form->button(
                            '<i class="fas fa-save"></i> Salvar alterações',
                            ['class' => 'btn btn-pgm btn-pgm-salvar', 'escape' => false]
                        ) ?>
                    </div>
                </div>

                <?= $this->Form->end() ?>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var catalogo = <?= json_encode($catalogo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    var codigoInput = document.getElementById('codigo-banco');
    var numeroInput = document.getElementById('numero-banco');
    var cnabInput = document.getElementById('cnab-banco');
    var nomeInput = document.getElementById('nome-banco');

    function normalizarNumero(valor) {
        return String(valor || '').replace(/\D+/g, '');
    }

    function buscarNoCatalogo(codigo) {
        var codigoLimpo = normalizarNumero(codigo);
        if (!codigoLimpo) {
            return null;
        }

        for (var i = 0; i < catalogo.length; i++) {
            var item = catalogo[i] || {};
            var cod = normalizarNumero(item.codigo || '');
            var cnab = normalizarNumero(item.cnab || '');
            if (cod === codigoLimpo || cnab === codigoLimpo) {
                return item;
            }
        }

        return null;
    }

    function preencherPeloCodigo() {
        if (!codigoInput) {
            return;
        }

        var item = buscarNoCatalogo(codigoInput.value);
        if (!item) {
            return;
        }

        if (nomeInput && !String(nomeInput.value || '').trim()) {
            nomeInput.value = item.nome || '';
        }
        if (numeroInput && !String(numeroInput.value || '').trim()) {
            numeroInput.value = item.codigo || '';
        }
        if (cnabInput && !String(cnabInput.value || '').trim()) {
            cnabInput.value = item.cnab || '';
        }
    }

    if (codigoInput) {
        codigoInput.addEventListener('blur', preencherPeloCodigo);
    }
})();
</script>
