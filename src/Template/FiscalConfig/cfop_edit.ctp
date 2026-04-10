<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Configuração fiscal', ['action' => 'index']);
$this->Breadcrumbs->add('CFOP', ['action' => 'cfop']);
$this->Breadcrumbs->add('Editar');
echo $this->element('Fiscal/styles');
?>
<div class="fpm-wrap">
    <div class="fpm-topbar">
        <h1 class="fpm-h1">Editar CFOP <?= h($cfop->codigo) ?></h1>
        <?= $this->Html->link('Voltar', ['action' => 'cfop'], ['class' => 'btn btn-default btn-sm']) ?>
    </div>
    <?= $this->element('Fiscal/regime_context') ?>
    <div class="px-3">
        <?= $this->Form->create($cfop) ?>
        <div class="fpm-card">
            <p class="fpm-muted">Código: <strong><?= h($cfop->codigo) ?></strong> (não pode ser alterado)</p>
            <?= $this->Form->control('descricao', ['label' => 'Descrição', 'class' => 'form-control']) ?>
            <?= $this->Form->control('tipo', ['type' => 'select', 'options' => $tiposCfop, 'label' => 'Tipo', 'class' => 'form-control']) ?>
            <?= $this->Form->control('aplicacao', ['type' => 'textarea', 'label' => 'Aplicação', 'class' => 'form-control', 'rows' => 3]) ?>
        </div>
        <div class="fpm-footer">
            <button type="submit" class="btn btn-pgm btn-pgm-salvar">Atualizar</button>
        </div>
        <?= $this->Form->end() ?>
    </div>
</div>
