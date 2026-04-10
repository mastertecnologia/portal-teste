<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Certificados', ['action' => 'index']);
$this->Breadcrumbs->add('Novo');
echo $this->element('Fiscal/styles');
?>
<div class="fpm-wrap">
    <div class="fpm-topbar">
        <h1 class="fpm-h1">Upload certificado A1</h1>
        <?= $this->Html->link('Voltar', ['action' => 'index'], ['class' => 'btn btn-default btn-sm']) ?>
    </div>
    <?= $this->element('Fiscal/regime_context') ?>
    <div class="px-3">
        <?= $this->Form->create($certificado, ['type' => 'file']) ?>
        <div class="fpm-card">
            <?= $this->Form->control('nome', ['label' => 'Nome de referência', 'class' => 'form-control']) ?>
            <?= $this->Form->control('arquivo_upload', [
                'type' => 'file', 'label' => 'Arquivo .pfx / .p12', 'class' => 'form-control',
            ]) ?>
            <?= $this->Form->control('senha', ['type' => 'password', 'label' => 'Senha do certificado', 'class' => 'form-control', 'required' => true]) ?>
        </div>
        <div class="fpm-footer">
            <button type="submit" class="btn btn-pgm btn-pgm-salvar">Enviar</button>
        </div>
        <?= $this->Form->end() ?>
    </div>
</div>
