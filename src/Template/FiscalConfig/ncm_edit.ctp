<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Configuração fiscal', ['action' => 'index']);
$this->Breadcrumbs->add('NCM', ['action' => 'ncm']);
$this->Breadcrumbs->add('Editar');
echo $this->element('Fiscal/styles');
?>
<div class="fpm-wrap">
    <div class="fpm-topbar">
        <h1 class="fpm-h1">Editar NCM <?= h($ncm->codigo) ?></h1>
        <?= $this->Html->link('Voltar', ['action' => 'ncm'], ['class' => 'btn btn-default btn-sm']) ?>
    </div>
    <div class="px-3">
        <?= $this->Form->create($ncm) ?>
        <div class="fpm-card">
            <p class="fpm-muted">Código: <strong><?= h($ncm->codigo) ?></strong> (não pode ser alterado)</p>
            <?= $this->Form->control('descricao', ['label' => 'Descrição', 'class' => 'form-control']) ?>
            <?= $this->Form->control('aliquota_ipi', ['label' => 'Alíquota IPI (%)', 'class' => 'form-control']) ?>
            <?= $this->Form->control('ex_tipi', ['label' => 'EX TIPI', 'class' => 'form-control']) ?>
        </div>
        <div class="fpm-footer">
            <button type="submit" class="btn btn-pgm btn-pgm-salvar">Atualizar</button>
        </div>
        <?= $this->Form->end() ?>
    </div>
</div>
