<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Financeiro', ['controller' => 'Financeiro', 'action' => 'index']);
$this->Breadcrumbs->add('Bancos', ['controller' => 'FinanceiroBancos', 'action' => 'index']);
$this->Breadcrumbs->add('Cadastrar banco');

$catalogo = $catalogo ?? [];
$codigoPrefill = $codigoPrefill ?? '';

$prefillItem = null;
if ($codigoPrefill !== '') {
    foreach ($catalogo as $item) {
        if ((string)($item['codigo'] ?? '') === (string)$codigoPrefill) {
            $prefillItem = $item;
            break;
        }
    }
}

$valorCodigo = (string)($banco->codigo_banco ?? ($prefillItem['codigo'] ?? ''));
$valorNumeroBanco = (string)($banco->numero_banco ?? ($prefillItem['codigo'] ?? ''));
$valorCnab = (string)($banco->cnab ?? ($prefillItem['cnab'] ?? ''));
$valorNome = (string)($banco->nome ?? ($prefillItem['nome'] ?? ''));
$valorAtivo = isset($banco->ativo) ? (bool)$banco->ativo : true;

$temAgencia = trim((string)($banco->numero_agencia ?? '')) !== '';
$temConta = trim((string)($banco->numero_conta ?? '')) !== '';
$cadastroOperacional = $temAgencia && $temConta;

$catalogoInicialTexto = 'Escolha um banco no catálogo para preencher automaticamente os dados principais.';
if (!empty($prefillItem)) {
    $catalogoInicialTexto = trim((string)($prefillItem['codigo'] ?? '')) . ' - ' . trim((string)($prefillItem['nome'] ?? ''));
    if (!empty($prefillItem['cnab'])) {
        $catalogoInicialTexto .= ' | CNAB: ' . trim((string)$prefillItem['cnab']);
    }
}
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
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
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
    font-size:24px;
    font-weight:700;
    line-height:1.1;
}
.fb-kpi-help {
    margin-top:8px;
    color:#8b949e;
    font-size:11.5px;
    line-height:1.45;
}

.fb-callout {
    margin:16px 20px 0;
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
.fb-window-body {
    padding:14px;
    background:#dcdcdc;
}

.fb-form {
    display:grid;
    grid-template-columns:minmax(0,1.45fr) minmax(320px,.85fr);
    gap:16px;
}
.fb-main-panel,
.fb-side-panel {
    background:#efefef;
    border:1px solid #b8b8b8;
    border-radius:3px;
    padding:12px;
}
.fb-main-title,
.fb-side-title {
    font-size:13px;
    font-weight:700;
    color:#374151;
    margin:0 0 10px;
}
.fb-main-sub,
.fb-side-help {
    color:#6b7280;
    font-size:11.5px;
    line-height:1.55;
    margin-bottom:12px;
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
.fb-pill--ok {
    background:#dff2df;
    color:#267326;
}
.fb-pill--warn {
    background:#fff2cf;
    color:#946200;
}
.fb-pill--muted {
    background:#ececec;
    color:#5f6368;
}

.fb-grid {
    display:grid;
    grid-template-columns:160px 140px 170px 120px;
    gap:10px 12px;
    align-items:end;
    margin-bottom:12px;
}
.fb-grid--config {
    grid-template-columns:150px 120px 160px 110px;
}
.fb-grid--full {
    grid-template-columns:1fr;
}
.fb-field {
    min-width:0;
}
.fb-field--name {
    grid-column:1 / -1;
}
.fb-field--span-2 {
    grid-column:span 2;
}
.fb-field--span-4 {
    grid-column:1 / -1;
}
.fb-field label {
    display:block;
    font-size:12px;
    color:#2f2f2f;
    font-weight:700;
    margin-bottom:4px;
}
.fb-field .form-control,
.fb-field select,
.fb-field textarea {
    width:100%;
    min-height:29px;
    border:1px solid #9f9f9f;
    border-radius:2px;
    background:#fff;
    color:#222;
    box-shadow:none;
    padding:4px 6px;
    font-size:12px;
}
.fb-field textarea {
    min-height:92px;
    resize:vertical;
}

.fb-code-lookup {
    display:flex;
    gap:6px;
}
.fb-code-lookup .form-control {
    flex:1;
}
.fb-mini-btn {
    width:30px;
    min-width:30px;
    height:29px;
    padding:0;
    border:1px solid #9ca3af;
    border-radius:2px;
    background:linear-gradient(#ffffff,#e5e7eb);
    color:#374151;
    display:inline-flex;
    align-items:center;
    justify-content:center;
}

.fb-tabs {
    display:flex;
    gap:3px;
    margin:14px 0 0;
}
.fb-tab {
    padding:7px 12px;
    background:#f4f4f4;
    border:1px solid #b8b8b8;
    border-bottom:none;
    border-top-left-radius:4px;
    border-top-right-radius:4px;
    font-size:12px;
    color:#374151;
    font-weight:700;
}
.fb-tab--active {
    background:#ffffff;
}
.fb-tab--muted {
    color:#6b7280;
}
.fb-tab-panel {
    border:1px solid #b8b8b8;
    background:#ffffff;
    padding:12px;
}
.fb-tab-panel + .fb-tab-panel {
    margin-top:10px;
}

.fb-panel-grid {
    display:grid;
    grid-template-columns:minmax(0,1.2fr) minmax(250px,.8fr);
    gap:14px;
}
.fb-preview-box {
    min-height:210px;
    border:1px solid #cfd6df;
    background:#f9fafb;
    display:flex;
    align-items:center;
    justify-content:center;
    text-align:center;
    border-radius:3px;
    padding:12px;
    color:#6b7280;
    font-size:12px;
    line-height:1.6;
}
.fb-preview-box strong {
    display:block;
    color:#374151;
    margin-bottom:6px;
}

.fb-status-box {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    padding:10px 12px;
    background:#f8fafc;
    border:1px dashed #c7ced8;
    border-radius:3px;
    margin-top:12px;
    flex-wrap:wrap;
}
.fb-switch {
    display:flex;
    align-items:center;
    gap:8px;
    color:#374151;
    font-size:12px;
    font-weight:700;
}
.fb-switch input {
    margin:0;
}
.fb-hint {
    color:#6b7280;
    font-size:11px;
    line-height:1.5;
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

.fb-side-toolbar {
    display:flex;
    gap:8px;
    flex-wrap:wrap;
    margin-bottom:10px;
}
.fb-side-table-wrap {
    max-height:520px;
    overflow:auto;
    border:1px solid #cfd6df;
    background:#fff;
}
.fb-side-table {
    width:100%;
    border-collapse:collapse;
    font-size:12px;
}
.fb-side-table th {
    position:sticky;
    top:0;
    z-index:1;
    background:#eef2f7;
    color:#374151;
    font-weight:700;
    padding:7px 8px;
    border-bottom:1px solid #cfd6df;
    text-align:left;
}
.fb-side-table td {
    padding:7px 8px;
    border-bottom:1px solid #edf0f4;
    color:#374151;
    vertical-align:top;
}
.fb-side-table tr:hover td {
    background:#f8fbff;
}
.fb-code {
    color:#0f766e;
    font-weight:700;
}
.fb-badge {
    display:inline-block;
    padding:2px 7px;
    border-radius:999px;
    font-size:10px;
    font-weight:700;
    background:#dcfce7;
    color:#166534;
}
.fb-empty {
    text-align:center;
    color:#6b7280;
    padding:20px 10px;
    font-size:12px;
}

.fb-footer {
    display:flex;
    justify-content:space-between;
    gap:10px;
    margin-top:14px;
    flex-wrap:wrap;
}
.fb-footer-left,
.fb-footer-right {
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}

.fb-help-list {
    margin:8px 0 0 18px;
    padding:0;
    color:#5f6368;
    font-size:11.5px;
    line-height:1.5;
}

@media (max-width: 1180px) {
    .fb-form,
    .fb-panel-grid {
        grid-template-columns:1fr;
    }
}
@media (max-width: 960px) {
    .fb-grid,
    .fb-grid--config {
        grid-template-columns:1fr 1fr;
    }
}
@media (max-width: 640px) {
    .fb-grid,
    .fb-grid--config {
        grid-template-columns:1fr;
    }
}
</style>

<div class="fb-root">
    <div class="fb-topbar">
        <div>
            <div class="fb-title">
                <i class="fas fa-university"></i>
                <span>Novo banco</span>
            </div>
            <div class="fb-subtitle">
                Inclusão assistida com catálogo bancário, preenchimento guiado e leitura operacional alinhada ao módulo financeiro.
            </div>
        </div>

        <div class="fb-actions">
            <?= $this->Html->link(
                '<i class="fas fa-list"></i> Cadastro',
                ['action' => 'cadastrar'],
                ['class' => 'btn btn-default btn-sm', 'escape' => false]
            ) ?>
            <?= $this->Html->link(
                '<i class="fas fa-th-large"></i> Painel',
                ['action' => 'index'],
                ['class' => 'btn btn-default btn-sm', 'escape' => false]
            ) ?>
            <?= $this->Html->link(
                '<i class="fas fa-arrow-left"></i> Voltar',
                ['action' => 'index'],
                ['class' => 'btn btn-default btn-sm', 'escape' => false]
            ) ?>
        </div>
    </div>

    <div class="fb-kpis">
        <div class="fb-kpi">
            <div class="fb-kpi-label">Origem sugerida</div>
            <div class="fb-kpi-value"><?= $codigoPrefill !== '' ? h($codigoPrefill) : 'Catálogo' ?></div>
            <div class="fb-kpi-help">Use o catálogo para preencher automaticamente os dados principais do banco.</div>
        </div>

        <div class="fb-kpi">
            <div class="fb-kpi-label">Situação inicial</div>
            <div class="fb-kpi-value"><?= $valorAtivo ? 'Ativo' : 'Inativo' ?></div>
            <div class="fb-kpi-help">Novos bancos já nascem ativos por padrão, prontos para configuração.</div>
        </div>

        <div class="fb-kpi">
            <div class="fb-kpi-label">Conta operacional</div>
            <div class="fb-kpi-value"><?= $cadastroOperacional ? 'Completa' : 'Pendente' ?></div>
            <div class="fb-kpi-help">Para remessa, retorno e conciliação, preencha agência e conta bancária.</div>
        </div>

        <div class="fb-kpi">
            <div class="fb-kpi-label">Catálogo disponível</div>
            <div class="fb-kpi-value"><?= number_format(count($catalogo), 0, ',', '.') ?></div>
            <div class="fb-kpi-help">Quantidade de referências bancárias disponíveis para inclusão assistida.</div>
        </div>
    </div>

    <div class="fb-callout">
        <strong>Dica operacional:</strong> primeiro selecione o banco no catálogo, depois revise código, CNAB e nome.
        Em seguida, complete agência e conta para deixar o cadastro pronto para remessa, retorno, conciliação e relatórios.
    </div>

    <div class="fb-shell">
        <div class="fb-window">
            <div class="fb-window-head">
                <div>
                    <div class="fb-window-title">Pc0030 - Bancos</div>
                    <div class="fb-window-sub">Inclusão web inspirada no cadastro clássico do ERP/Grid, com reforço operacional.</div>
                </div>

                <div class="fb-window-actions">
                    <?= $this->Html->link(
                        '<i class="fas fa-list"></i> Ver cadastros',
                        ['action' => 'cadastrar'],
                        ['class' => 'btn btn-default btn-sm', 'escape' => false]
                    ) ?>
                    <?= $this->Html->link(
                        '<i class="fas fa-chart-bar"></i> Relatórios',
                        ['action' => 'relatorios'],
                        ['class' => 'btn btn-default btn-sm', 'escape' => false]
                    ) ?>
                </div>
            </div>

            <div class="fb-window-body">
                <?= $this->Form->create($banco) ?>

                <div class="fb-form">
                    <div class="fb-main-panel">
                        <h2 class="fb-main-title">Formulário de inclusão</h2>
                        <div class="fb-main-sub">
                            Preencha os dados principais do banco e use os blocos de apoio para conferir o cadastro antes de gravar.
                        </div>

                        <div class="fb-summary-line">
                            <span class="fb-pill">
                                <i class="fas fa-plus-circle"></i>Novo cadastro
                            </span>
                            <span class="fb-pill <?= $cadastroOperacional ? 'fb-pill--ok' : 'fb-pill--warn' ?>">
                                <i class="fas fa-wallet"></i><?= $cadastroOperacional ? 'Conta completa' : 'Conta pendente' ?>
                            </span>
                            <span class="fb-pill fb-pill--muted">
                                <i class="fas fa-book"></i>Catálogo assistido
                            </span>
                        </div>

                        <div class="fb-grid">
                            <div class="fb-field">
                                <label for="codigo-banco-select">Catálogo bancário</label>
                                <div class="fb-code-lookup">
                                    <select id="codigo-banco-select" class="form-control">
                                        <option value="">Selecione</option>
                                        <?php foreach ($catalogo as $item): ?>
                                            <?php
                                            $selected = ((string)($item['codigo'] ?? '') === (string)$valorCodigo) ? 'selected' : '';
                                            ?>
                                            <option
                                                value="<?= h($item['codigo'] ?? '') ?>"
                                                data-codigo="<?= h($item['codigo'] ?? '') ?>"
                                                data-nome="<?= h($item['nome'] ?? '') ?>"
                                                data-cnab="<?= h($item['cnab'] ?? '') ?>"
                                                data-nome-completo="<?= h($item['nome_completo'] ?? '') ?>"
                                                <?= $selected ?>
                                            >
                                                <?= h($item['codigo'] ?? '') ?> - <?= h($item['nome'] ?? '') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="fb-mini-btn" id="catalogo-search-btn" title="Focar catálogo">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="fb-field">
                                <label for="codigo-banco">Código bancário</label>
                                <?= $this->Form->control('codigo_banco', [
                                    'label' => false,
                                    'class' => 'form-control',
                                    'id' => 'codigo-banco',
                                    'placeholder' => 'Ex: 756',
                                    'maxlength' => 10,
                                    'value' => $valorCodigo
                                ]) ?>
                            </div>

                            <div class="fb-field">
                                <label for="numero-banco">Núm. banco</label>
                                <?= $this->Form->control('numero_banco', [
                                    'label' => false,
                                    'class' => 'form-control',
                                    'id' => 'numero-banco',
                                    'placeholder' => 'Ex: 756',
                                    'maxlength' => 10,
                                    'value' => $valorNumeroBanco
                                ]) ?>
                            </div>

                            <div class="fb-field">
                                <label for="cnab-banco">CNAB</label>
                                <?= $this->Form->control('cnab', [
                                    'label' => false,
                                    'class' => 'form-control',
                                    'id' => 'cnab-banco',
                                    'placeholder' => 'Ex: 240',
                                    'maxlength' => 10,
                                    'value' => $valorCnab
                                ]) ?>
                            </div>

                            <div class="fb-field fb-field--name">
                                <label for="nome-banco">Nome</label>
                                <?= $this->Form->control('nome', [
                                    'label' => false,
                                    'class' => 'form-control',
                                    'id' => 'nome-banco',
                                    'placeholder' => 'Razão social / nome do banco',
                                    'value' => $valorNome
                                ]) ?>
                            </div>
                        </div>

                        <div class="fb-tabs">
                            <div class="fb-tab fb-tab--active">Configurações</div>
                            <div class="fb-tab fb-tab--muted">Contas contábeis</div>
                        </div>

                        <div class="fb-tab-panel">
                            <div class="fb-panel-grid">
                                <div>
                                    <div class="fb-grid fb-grid--config">
                                        <div class="fb-field">
                                            <label>Número Agência</label>
                                            <?= $this->Form->control('numero_agencia', [
                                                'label' => false,
                                                'class' => 'form-control',
                                                'placeholder' => 'Ex: 3037'
                                            ]) ?>
                                        </div>

                                        <div class="fb-field">
                                            <label>Dígito Agência</label>
                                            <?= $this->Form->control('digito_agencia', [
                                                'label' => false,
                                                'class' => 'form-control',
                                                'placeholder' => 'Ex: 0'
                                            ]) ?>
                                        </div>

                                        <div class="fb-field">
                                            <label>Número Conta</label>
                                            <?= $this->Form->control('numero_conta', [
                                                'label' => false,
                                                'class' => 'form-control',
                                                'placeholder' => 'Ex: 987654'
                                            ]) ?>
                                        </div>

                                        <div class="fb-field">
                                            <label>Dígito Conta</label>
                                            <?= $this->Form->control('digito_conta', [
                                                'label' => false,
                                                'class' => 'form-control',
                                                'placeholder' => 'Ex: 1'
                                            ]) ?>
                                        </div>

                                        <div class="fb-field">
                                            <label>Código Banco Interno</label>
                                            <?= $this->Form->control('codigo_banco_interno', [
                                                'label' => false,
                                                'class' => 'form-control',
                                                'placeholder' => 'Código interno ERP'
                                            ]) ?>
                                        </div>

                                        <div class="fb-field">
                                            <label>Verifica Receber</label>
                                            <?= $this->Form->control('verifica_receber', [
                                                'label' => false,
                                                'class' => 'form-control',
                                                'placeholder' => 'Configuração opcional'
                                            ]) ?>
                                        </div>

                                        <div class="fb-field">
                                            <label>Utiliza Endosso</label>
                                            <?= $this->Form->control('utiliza_endosso', [
                                                'label' => false,
                                                'class' => 'form-control',
                                                'placeholder' => 'Ex: N'
                                            ]) ?>
                                        </div>

                                        <div class="fb-field">
                                            <label>Logotipo</label>
                                            <?= $this->Form->control('logotipo', [
                                                'label' => false,
                                                'class' => 'form-control',
                                                'placeholder' => 'URL, caminho ou referência'
                                            ]) ?>
                                        </div>

                                        <div class="fb-field fb-field--span-4">
                                            <label>Observações</label>
                                            <?= $this->Form->textarea('observacoes', [
                                                'class' => 'form-control',
                                                'rows' => 4,
                                                'placeholder' => 'Observações sobre cobrança, retorno, convênio, CNAB e uso interno.'
                                            ]) ?>
                                        </div>
                                    </div>

                                    <div class="fb-status-box">
                                        <label class="fb-switch">
                                            <?= $this->Form->checkbox('ativo', [
                                                'hiddenField' => true,
                                                'value' => 1,
                                                'checked' => $valorAtivo
                                            ]) ?>
                                            <span>Ativo</span>
                                        </label>

                                        <div class="fb-hint">
                                            Bancos ativos podem ser usados nas rotinas de remessa, retorno, conciliação e relatórios.
                                        </div>
                                    </div>

                                    <div class="fb-state-box <?= $cadastroOperacional ? 'fb-state-box--ok' : 'fb-state-box--warn' ?>">
                                        <div class="fb-state-title">
                                            <?= $cadastroOperacional ? 'Cadastro bancário pronto para operação' : 'Cadastro bancário ainda precisa de conta operacional' ?>
                                        </div>
                                        <div class="fb-state-text">
                                            <?php if ($cadastroOperacional): ?>
                                                Agência e conta já estão preenchidas, deixando o banco apto para uso mais completo no módulo financeiro.
                                            <?php else: ?>
                                                Preencha agência e conta para deixar o banco pronto para conciliação, retorno e leitura operacional das previsões.
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <div class="fb-preview-box" id="catalogo-preview">
                                        <div>
                                            <strong>Banco selecionado</strong>
                                            <span id="catalogo-preview-text"><?= h($catalogoInicialTexto) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="fb-tab-panel">
                            <div class="fb-main-sub" style="margin-bottom:0;">
                                <strong>Contas contábeis:</strong> área preparada para evolução futura. Hoje o foco operacional desta inclusão está em:
                                <ul class="fb-help-list">
                                    <li>cadastro bancário por empresa</li>
                                    <li>vínculo com lançamentos financeiros</li>
                                    <li>conciliação, remessa e retorno bancário</li>
                                    <li>relatórios e previsões por banco</li>
                                </ul>
                            </div>
                        </div>

                        <div class="fb-footer">
                            <div class="fb-footer-left">
                                <?= $this->Html->link(
                                    '<i class="fas fa-times"></i> Cancelar',
                                    ['action' => 'cadastrar'],
                                    ['class' => 'btn btn-default', 'escape' => false]
                                ) ?>
                            </div>

                            <div class="fb-footer-right">
                                <?= $this->Form->button(
                                    '<i class="fas fa-save"></i> Gravar',
                                    ['class' => 'btn btn-pgm btn-pgm-salvar', 'escape' => false]
                                ) ?>
                            </div>
                        </div>
                    </div>

                    <div class="fb-side-panel">
                        <h2 class="fb-side-title">Catálogo assistido</h2>
                        <div class="fb-side-help">
                            Use esta referência como apoio visual, semelhante à pesquisa do ERP atual.
                            Ao escolher um banco no topo, o sistema pode sugerir o preenchimento automático dos campos principais.
                        </div>

                        <div class="fb-side-toolbar">
                            <?= $this->Html->link(
                                '<i class="fas fa-list"></i> Ver cadastros',
                                ['action' => 'cadastrar'],
                                ['class' => 'btn btn-default btn-sm', 'escape' => false]
                            ) ?>
                        </div>

                        <div class="fb-side-table-wrap">
                            <?php if (empty($catalogo)): ?>
                                <div class="fb-empty">Nenhum banco disponível no catálogo.</div>
                            <?php else: ?>
                                <table class="fb-side-table">
                                    <thead>
                                        <tr>
                                            <th>Código</th>
                                            <th>Banco</th>
                                            <th>CNAB</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($catalogo as $item): ?>
                                            <tr>
                                                <td><span class="fb-code"><?= h($item['codigo'] ?? '') ?></span></td>
                                                <td>
                                                    <?= h($item['nome'] ?? '') ?>
                                                    <?php if (!empty($item['nome_completo'])): ?>
                                                        <div class="fb-hint"><?= h($item['nome_completo']) ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><span class="fb-badge"><?= h($item['cnab'] ?? '—') ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?= $this->Form->end() ?>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var select = document.getElementById('codigo-banco-select');
    var searchBtn = document.getElementById('catalogo-search-btn');
    var codigo = document.getElementById('codigo-banco');
    var numero = document.getElementById('numero-banco');
    var cnab = document.getElementById('cnab-banco');
    var nome = document.getElementById('nome-banco');
    var previewText = document.getElementById('catalogo-preview-text');

    function atualizarPreview(option) {
        if (!previewText) {
            return;
        }

        if (!option || !option.value) {
            previewText.textContent = 'Escolha um banco no catálogo para preencher automaticamente os dados principais.';
            return;
        }

        var bancoCodigo = option.getAttribute('data-codigo') || '';
        var bancoNome = option.getAttribute('data-nome') || '';
        var bancoCnab = option.getAttribute('data-cnab') || '';
        var nomeCompleto = option.getAttribute('data-nome-completo') || '';

        var texto = bancoCodigo + ' - ' + bancoNome;
        if (bancoCnab) {
            texto += ' | CNAB: ' + bancoCnab;
        }
        if (nomeCompleto) {
            texto += ' | ' + nomeCompleto;
        }

        previewText.textContent = texto;
    }

    function preencherBancoSelecionado() {
        if (!select) {
            return;
        }

        var option = select.options[select.selectedIndex];
        if (!option || !option.value) {
            atualizarPreview(null);
            return;
        }

        var bancoCodigo = option.getAttribute('data-codigo') || '';
        var bancoNome = option.getAttribute('data-nome') || '';
        var bancoCnab = option.getAttribute('data-cnab') || '';

        if (codigo) {
            codigo.value = bancoCodigo;
        }
        if (numero) {
            numero.value = bancoCodigo;
        }
        if (cnab) {
            cnab.value = bancoCnab;
        }
        if (nome && !String(nome.value || '').trim()) {
            nome.value = bancoNome;
        }

        atualizarPreview(option);
    }

    if (select) {
        select.addEventListener('change', preencherBancoSelecionado);

        if (select.value) {
            atualizarPreview(select.options[select.selectedIndex]);
        }
    }

    if (searchBtn) {
        searchBtn.addEventListener('click', function() {
            if (select) {
                select.focus();
            }
        });
    }
})();
</script>
