<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Módulo Fiscal', ['controller' => 'Fiscal', 'action' => 'index']);
$this->Breadcrumbs->add('Relatórios fiscais');
echo $this->element('Fiscal/styles');
?>
<div class="fpm-wrap">
    <div class="fpm-topbar">
        <h1 class="fpm-h1"><i class="fas fa-chart-bar"></i>Relatórios fiscais</h1>
        <?= $this->Html->link('Voltar', ['controller' => 'Fiscal', 'action' => 'index'], ['class' => 'btn btn-default btn-sm']) ?>
    </div>
    <div class="fpm-nav-cards">
        <?= $this->Html->link('<i class="fas fa-arrow-up"></i>Livro de saídas', ['action' => 'livroSaidas'], ['class' => 'fpm-nav-card', 'escape' => false]) ?>
        <?= $this->Html->link('<i class="fas fa-arrow-down"></i>Livro de entradas', ['action' => 'livroEntradas'], ['class' => 'fpm-nav-card', 'escape' => false]) ?>
        <?= $this->Html->link('<i class="fas fa-calendar-alt"></i>Resumo mensal', ['action' => 'resumoMensal'], ['class' => 'fpm-nav-card', 'escape' => false]) ?>
        <?= $this->Html->link('<i class="fas fa-users"></i>Por cliente', ['action' => 'porCliente'], ['class' => 'fpm-nav-card', 'escape' => false]) ?>
        <?= $this->Html->link('<i class="fas fa-barcode"></i>Por número de série', ['action' => 'porNumeroSerie'], ['class' => 'fpm-nav-card', 'escape' => false]) ?>
    </div>
</div>
