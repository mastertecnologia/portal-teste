<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Naturezas', ['action' => 'naturezas']);
$this->Breadcrumbs->add('Editar');
echo $this->element('Fiscal/styles');
?>
<div class="fpm-wrap">
    <div class="fpm-topbar">
        <h1 class="fpm-h1">Editar natureza</h1>
        <?= $this->Html->link('Voltar', ['action' => 'naturezas'], ['class' => 'btn btn-default btn-sm']) ?>
    </div>
    <div class="px-3">
        <?= $this->Form->create($natureza) ?>
        <div class="fpm-card">
            <?= $this->Form->control('codigo', ['label' => 'Código', 'class' => 'form-control']) ?>
            <?= $this->Form->control('descricao', ['label' => 'Descrição', 'class' => 'form-control']) ?>
            <?= $this->Form->control('tipo', [
                'type' => 'select',
                'options' => ['entrada' => 'Entrada', 'saida' => 'Saída'],
                'label' => 'Tipo', 'class' => 'form-control',
            ]) ?>
            <?= $this->Form->control('cfop_padrao', [
                'type' => 'select', 'options' => $cfops, 'empty' => '—',
                'label' => 'CFOP padrão', 'class' => 'form-control',
            ]) ?>
            <?= $this->Form->control('gera_financeiro', ['type' => 'checkbox', 'label' => 'Gera financeiro']) ?>
            <?= $this->Form->control('ativo', ['type' => 'checkbox', 'label' => 'Ativo']) ?>
        </div>
        <div class="fpm-footer">
            <button type="submit" class="btn btn-pgm btn-pgm-salvar">Atualizar</button>
        </div>
        <?= $this->Form->end() ?>
    </div>
</div>
