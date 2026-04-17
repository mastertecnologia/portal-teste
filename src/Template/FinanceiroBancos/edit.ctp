<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Financeiro', ['controller' => 'Financeiro', 'action' => 'index']);
$this->Breadcrumbs->add('Bancos', ['controller' => 'FinanceiroBancos', 'action' => 'index']);
$this->Breadcrumbs->add('Cadastro de Bancos', ['controller' => 'FinanceiroBancos', 'action' => 'cadastrar']);
$this->Breadcrumbs->add('Editar banco');

$catalogo = $catalogo ?? [];
$banco = $banco ?? null;

$ativoValue = isset($banco->ativo) ? (bool)$banco->ativo : true;
?>
<style>
.fb-form-root { font-family:'DM Sans',sans-serif; }
.fb-form-topbar {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    padding:18px 24px 14px;
    border-bottom:1px solid rgba(255,255,255,.07);
    flex-wrap:wrap;
}
.fb-form-h1 {
    font-size:20px;
    font-weight:600;
    color:#e6edf3;
    margin:0;
}
.fb-form-h1-ico {
    color:#5cdbc0;
    margin-right:8px;
}
.fb-form-actions {
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}
.fb-form-card {
    background:#161b22;
    border:1px solid rgba(255,255,255,.07);
    border-radius:10px;
    padding:18px 20px;
    margin:16px 24px;
}
.fb-form-grid {
    display:grid;
    grid-template-columns:repeat(12, minmax(0, 1fr));
    gap:14px 16px;
}
.fb-col-12 { grid-column:span 12; }
.fb-col-9 { grid-column:span 9; }
.fb-col-8 { grid-column:span 8; }
.fb-col-6 { grid-column:span 6; }
.fb-col-4 { grid-column:span 4; }
.fb-col-3 { grid-column:span 3; }
.fb-col-2 { grid-column:span 2; }

.fb-field label,
.fb-block-title {
    display:block;
    font-size:11px;
    color:#7d8590;
    text-transform:uppercase;
    letter-spacing:.05em;
    font-weight:600;
    margin-bottom:5px;
}
.fb-field .form-control,
.fb-field select,
.fb-field textarea {
    background:#0d1117;
    color:#c9d1d9;
    border:1px solid rgba(255,255,255,.10);
    border-radius:8px;
}
.fb-field textarea {
    min-height:90px;
    resize:vertical;
}
.fb-switch-wrap {
    display:flex;
    align-items:center;
    gap:10px;
    min-height:38px;
    padding-top:18px;
}
.fb-switch-wrap input[type=checkbox] {
    transform:scale(1.12);
}
.fb-help {
    font-size:11px;
    color:#8b949e;
    margin-top:4px;
}
.fb-side-card {
    background:#0d1117;
    border:1px solid rgba(255,255,255,.06);
    border-radius:10px;
    padding:14px;
    height:100%;
}
.fb-side-title {
    font-size:13px;
    font-weight:600;
    color:#e6edf3;
    margin-bottom:10px;
}
.fb-catalogo-list {
    max-height:260px;
    overflow:auto;
    border:1px solid rgba(255,255,255,.06);
    border-radius:8px;
}
.fb-catalogo-item {
    padding:8px 10px;
    border-bottom:1px solid rgba(255,255,255,.05);
    color:#c9d1d9;
    font-size:12px;
}
.fb-catalogo-item:last-child {
    border-bottom:none;
}
.fb-catalogo-cod {
    color:#5cdbc0;
    font-weight:700;
    margin-right:6px;
}
.fb-form-footer {
    display:flex;
    justify-content:space-between;
    gap:10px;
    margin-top:18px;
    flex-wrap:wrap;
}
.fb-danger-box {
    margin:16px 24px 0;
    background:rgba(248,81,73,.08);
    color:#ffd7d5;
    border:1px solid rgba(248,81,73,.18);
    border-radius:10px;
    padding:12px 14px;
    font-size:12px;
}
@media (max-width: 980px) {
    .fb-col-9, .fb-col-8, .fb-col-6, .fb-col-4, .fb-col-3, .fb-col-2 {
        grid-column:span 12;
    }
}
</style>

<div class="fb-form-root">
    <div class="fb-form-topbar">
        <h1 class="fb-form-h1">
            <i class="fas fa-university fb-form-h1-ico"></i>
            Editar banco<?= !empty($banco->id) ? ' #' . (int)$banco->id : '' ?>
        </h1>
        <div class="fb-form-actions">
            <?= $this->Html->link(
                '<i class="fas fa-list"></i> Cadastro de bancos',
                ['action' => 'cadastrar'],
                ['class' => 'btn btn-default btn-sm', 'escape' => false]
            ) ?>
            <?= $this->Html->link(
                '<i class="fas fa-arrow-left"></i> Voltar',
                ['action' => 'index'],
                ['class' => 'btn btn-default btn-sm', 'escape' => false]
            ) ?>
        </div>
    </div>

    <div class="fb-danger-box">
        <strong>Atenção:</strong>
        esta tela altera o banco já vinculado ao seu financeiro. Se este banco já estiver associado a lançamentos,
        revise com cuidado os campos de código, agência e conta.
    </div>

    <div class="fb-form-card">
        <?= $this->Form->create($banco) ?>

        <div class="fb-form-grid">
            <div class="fb-field fb-col-3">
                <label>Código bancário</label>
                <?= $this->Form->control('codigo_banco', [
                    'label' => false,
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 341',
                    'maxlength' => 10,
                    'id' => 'codigo-banco'
                ]) ?>
                <div class="fb-help">Você pode localizar pelo código FEBRABAN/COMPE.</div>
            </div>

            <div class="fb-field fb-col-3">
                <label>Núm. banco</label>
                <?= $this->Form->control('numero_banco', [
                    'label' => false,
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 341',
                    'maxlength' => 10,
                    'id' => 'numero-banco'
                ]) ?>
            </div>

            <div class="fb-field fb-col-3">
                <label>CNAB</label>
                <?= $this->Form->control('cnab', [
                    'label' => false,
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 341',
                    'maxlength' => 10,
                    'id' => 'cnab-banco'
                ]) ?>
            </div>

            <div class="fb-field fb-col-3">
                <div class="fb-switch-wrap">
                    <?= $this->Form->checkbox('ativo', [
                        'checked' => $ativoValue,
                        'hiddenField' => true,
                        'value' => 1
                    ]) ?>
                    <label style="margin:0; text-transform:none; letter-spacing:0; font-size:13px; color:#c9d1d9; font-weight:600;">
                        Banco ativo
                    </label>
                </div>
                <div class="fb-help">Marque para disponibilizar este banco nas rotinas do financeiro.</div>
            </div>

            <div class="fb-field fb-col-8">
                <label>Nome do banco</label>
                <?= $this->Form->control('nome', [
                    'label' => false,
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Itaú Unibanco',
                    'maxlength' => 255,
                    'id' => 'nome-banco'
                ]) ?>
            </div>

            <div class="fb-field fb-col-4">
                <label>Código banco interno</label>
                <?= $this->Form->control('codigo_banco_interno', [
                    'label' => false,
                    'class' => 'form-control',
                    'placeholder' => 'Código no ERP',
                    'maxlength' => 50
                ]) ?>
            </div>

            <div class="fb-field fb-col-3">
                <label>Número agência</label>
                <?= $this->Form->control('numero_agencia', [
                    'label' => false,
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 3037',
                    'maxlength' => 20
                ]) ?>
            </div>

            <div class="fb-field fb-col-2">
                <label>Dígito agência</label>
                <?= $this->Form->control('digito_agencia', [
                    'label' => false,
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 0',
                    'maxlength' => 5
                ]) ?>
            </div>

            <div class="fb-field fb-col-4">
                <label>Número conta</label>
                <?= $this->Form->control('numero_conta', [
                    'label' => false,
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 123456',
                    'maxlength' => 30
                ]) ?>
            </div>

            <div class="fb-field fb-col-3">
                <label>Dígito conta</label>
                <?= $this->Form->control('digito_conta', [
                    'label' => false,
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 7',
                    'maxlength' => 5
                ]) ?>
            </div>

            <div class="fb-field fb-col-4">
                <label>Verifica receber</label>
                <?= $this->Form->control('verifica_receber', [
                    'label' => false,
                    'class' => 'form-control',
                    'placeholder' => 'Ex: rotina/flag de recebimento',
                    'maxlength' => 100
                ]) ?>
            </div>

            <div class="fb-field fb-col-2">
                <label>Utiliza endosso</label>
                <?= $this->Form->control('utiliza_endosso', [
                    'label' => false,
                    'class' => 'form-control',
                    'placeholder' => 'S/N',
                    'maxlength' => 10
                ]) ?>
            </div>

            <div class="fb-field fb-col-6">
                <label>Logotipo</label>
                <?= $this->Form->control('logotipo', [
                    'label' => false,
                    'class' => 'form-control',
                    'placeholder' => 'URL ou caminho do logotipo',
                    'maxlength' => 255
                ]) ?>
                <div class="fb-help">Opcional. Pode ser usado futuramente em relatórios e remessas.</div>
            </div>

            <div class="fb-field fb-col-9">
                <label>Observações</label>
                <?= $this->Form->textarea('observacoes', [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Informações adicionais sobre integração, layout CNAB, instruções internas, etc.'
                ]) ?>
            </div>

            <div class="fb-col-3">
                <div class="fb-side-card">
                    <div class="fb-side-title">Catálogo de bancos</div>
                    <div class="fb-help" style="margin-bottom:10px;">
                        Referência para localizar códigos bancários e conferir o cadastro.
                    </div>

                    <div class="fb-catalogo-list">
                        <?php if (empty($catalogo)): ?>
                            <div class="fb-catalogo-item">Nenhum banco de referência disponível.</div>
                        <?php else: ?>
                            <?php foreach ($catalogo as $item): ?>
                                <div class="fb-catalogo-item">
                                    <span class="fb-catalogo-cod"><?= h($item['codigo'] ?? '') ?></span>
                                    <?= h($item['nome'] ?? '') ?>
                                    <?php if (!empty($item['cnab'])): ?>
                                        <div class="fb-help" style="margin:4px 0 0;">CNAB: <?= h($item['cnab']) ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="fb-form-footer">
            <div>
                <?= $this->Form->postLink(
                    '<i class="fas fa-trash-alt"></i> Excluir banco',
                    ['action' => 'delete', $banco->id],
                    [
                        'class' => 'btn btn-danger',
                        'escape' => false,
                        'confirm' => 'Deseja realmente excluir este banco? Esta ação não pode ser desfeita.'
                    ]
                ) ?>
            </div>
            <div class="fb-form-actions">
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

<script>
(function() {
    var catalogo = <?= json_encode($catalogo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    var codigoInput = document.getElementById('codigo-banco');
    var numeroInput = document.getElementById('numero-banco');
    var cnabInput = document.getElementById('cnab-banco');
    var nomeInput = document.getElementById('nome-banco');

    function buscarNoCatalogo(codigo) {
        codigo = String(codigo || '').replace(/\D+/g, '');
        if (!codigo) {
            return null;
        }

        for (var i = 0; i < catalogo.length; i++) {
            var item = catalogo[i] || {};
            var cod = String(item.codigo || '').replace(/\D+/g, '');
            var cnab = String(item.cnab || '').replace(/\D+/g, '');
            if (cod === codigo || cnab === codigo) {
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

        if (nomeInput && !nomeInput.value.trim()) {
            nomeInput.value = item.nome || '';
        }
        if (numeroInput && !numeroInput.value.trim()) {
            numeroInput.value = item.codigo || '';
        }
        if (cnabInput && !cnabInput.value.trim()) {
            cnabInput.value = item.cnab || '';
        }
    }

    if (codigoInput) {
        codigoInput.addEventListener('blur', preencherPeloCodigo);
    }
})();
</script>
