<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Configuração fiscal', ['action' => 'index']);
$this->Breadcrumbs->add('CFOP', ['action' => 'cfop']);
$this->Breadcrumbs->add('Novo');
echo $this->element('Fiscal/styles');
?>
<div class="fpm-wrap">
    <div class="fpm-topbar">
        <h1 class="fpm-h1">Novo CFOP</h1>
        <?= $this->Html->link('Voltar', ['action' => 'cfop'], ['class' => 'btn btn-default btn-sm']) ?>
    </div>
    <div class="px-3">
        <?= $this->Form->create($cfop) ?>
        <div class="fpm-card">
            <?= $this->Form->control('codigo', ['label' => 'Código (até 5 dígitos)', 'class' => 'form-control', 'maxlength' => 5]) ?>
            <?= $this->Form->control('descricao', ['label' => 'Descrição', 'class' => 'form-control']) ?>
            <?= $this->Form->control('tipo', ['type' => 'select', 'options' => $tiposCfop, 'label' => 'Tipo', 'class' => 'form-control']) ?>
            <?= $this->Form->control('aplicacao', ['type' => 'textarea', 'label' => 'Aplicação (opcional)', 'class' => 'form-control', 'rows' => 3]) ?>
        </div>
        <div class="fpm-footer">
            <button type="submit" class="btn btn-pgm btn-pgm-salvar">Salvar</button>
        </div>
        <?= $this->Form->end() ?>
    </div>
</div>
