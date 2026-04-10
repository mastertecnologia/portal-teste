<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Alíquotas', ['action' => 'aliquotas']);
$this->Breadcrumbs->add('Nova');
echo $this->element('Fiscal/styles');
?>
<div class="fpm-wrap">
    <div class="fpm-topbar">
        <h1 class="fpm-h1">Nova alíquota</h1>
        <?= $this->Html->link('Voltar', ['action' => 'aliquotas'], ['class' => 'btn btn-default btn-sm']) ?>
    </div>
    <div class="px-3">
        <?= $this->Form->create($aliquota) ?>
        <div class="fpm-card">
            <div class="fpm-row">
                <div class="fpm-field"><?= $this->Form->control('uf_origem', ['type' => 'select', 'options' => $ufsOptions, 'label' => 'UF origem', 'class' => 'form-control']) ?></div>
                <div class="fpm-field"><?= $this->Form->control('uf_destino', ['type' => 'select', 'options' => $ufsOptions, 'label' => 'UF destino', 'class' => 'form-control']) ?></div>
                <div class="fpm-field"><?= $this->Form->control('ncm_codigo', ['label' => 'NCM (vazio = genérico)', 'class' => 'form-control']) ?></div>
            </div>
            <div class="fpm-row">
                <div class="fpm-field"><?= $this->Form->control('icms_aliquota', ['label' => 'ICMS %', 'class' => 'form-control']) ?></div>
                <div class="fpm-field"><?= $this->Form->control('icms_reducao', ['label' => 'Redução BC %', 'class' => 'form-control']) ?></div>
                <div class="fpm-field"><?= $this->Form->control('icms_st_mva', ['label' => 'MVA ST %', 'class' => 'form-control']) ?></div>
            </div>
            <div class="fpm-row">
                <div class="fpm-field"><?= $this->Form->control('ipi_aliquota', ['label' => 'IPI %', 'class' => 'form-control']) ?></div>
                <div class="fpm-field"><?= $this->Form->control('pis_aliquota', ['label' => 'PIS', 'class' => 'form-control']) ?></div>
                <div class="fpm-field"><?= $this->Form->control('cofins_aliquota', ['label' => 'COFINS', 'class' => 'form-control']) ?></div>
                <div class="fpm-field"><?= $this->Form->control('iss_aliquota', ['label' => 'ISS %', 'class' => 'form-control']) ?></div>
            </div>
        </div>
        <div class="fpm-footer">
            <button type="submit" class="btn btn-pgm btn-pgm-salvar">Salvar</button>
        </div>
        <?= $this->Form->end() ?>
    </div>
</div>
