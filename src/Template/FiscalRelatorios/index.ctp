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
    <?= $this->element('Fiscal/regime_context') ?>
    <div class="fpm-nav-cards">
        <?= $this->Html->link('<i class="fas fa-arrow-up"></i>Livro de saídas', ['action' => 'livroSaidas'], ['class' => 'fpm-nav-card', 'escape' => false]) ?>
        <?= $this->Html->link('<i class="fas fa-arrow-down"></i>Livro de entradas', ['action' => 'livroEntradas'], ['class' => 'fpm-nav-card', 'escape' => false]) ?>
        <?= $this->Html->link('<i class="fas fa-calendar-alt"></i>Resumo mensal', ['action' => 'resumoMensal'], ['class' => 'fpm-nav-card', 'escape' => false]) ?>
        <?= $this->Html->link('<i class="fas fa-users"></i>Por cliente', ['action' => 'porCliente'], ['class' => 'fpm-nav-card', 'escape' => false]) ?>
        <?= $this->Html->link('<i class="fas fa-barcode"></i>Por número de série', ['action' => 'porNumeroSerie'], ['class' => 'fpm-nav-card', 'escape' => false]) ?>
    </div>
    <div style="padding:0 20px 24px;">
        <div class="fpm-card" style="margin:0;">
            <div class="fpm-card-title"><i class="fas fa-file-export" style="color:#1D9E75;margin-right:6px;"></i>SPED Fiscal (EFD-ICMS/IPI)</div>
            <p class="fpm-muted small" style="margin:0 0 12px;">Gera arquivo texto (EFD-ICMS/IPI) com notas <strong>autorizadas</strong> no mês selecionado, incluindo blocos estruturais previstos no leiaute (entre eles B, D, G, H, K e 1 quando sem movimento). Conferir no PVA da Receita Federal antes da entrega; o contador assina e encaminha o arquivo oficial.</p>
            <?= $this->Form->create(null, ['type' => 'get', 'url' => ['action' => 'exportarSped'], 'class' => 'fpm-filters', 'style' => 'padding:0;border:0;']) ?>
            <div>
                <label>Mês de referência</label>
                <input type="month" name="mes_ano" value="<?= h(date('Y-m')) ?>" required>
            </div>
            <div><button type="submit" class="btn btn-pgm btn-sm" style="margin-top:18px;">Baixar .txt</button></div>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
