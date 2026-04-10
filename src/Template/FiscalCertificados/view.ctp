<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Certificados', ['action' => 'index']);
$this->Breadcrumbs->add(h($certificado->nome));
echo $this->element('Fiscal/styles');
?>
<div class="fpm-wrap">
    <div class="fpm-topbar">
        <h1 class="fpm-h1"><?= h($certificado->nome) ?></h1>
        <?= $this->Html->link('Lista', ['action' => 'index'], ['class' => 'btn btn-default btn-sm']) ?>
    </div>
    <?= $this->element('Fiscal/regime_context') ?>
    <div class="fpm-card mx-3">
        <div class="fpm-row">
            <div class="fpm-field"><span class="fpm-muted">Tipo</span><div><?= h($certificado->tipo) ?></div></div>
            <div class="fpm-field"><span class="fpm-muted">CNPJ</span><div><?= h($certificado->cnpj_certificado ?: '—') ?></div></div>
            <div class="fpm-field"><span class="fpm-muted">Serial</span><div><small><?= h($certificado->serial_number ?: '—') ?></small></div></div>
            <div class="fpm-field"><span class="fpm-muted">Validade</span>
                <div><?= $certificado->validade_fim ? h($certificado->validade_fim->format('d/m/Y H:i')) : '—' ?>
                    <?php if ($valido): ?><span class="fpm-badge ok"><?= (int)$diasRestantes ?> dias</span><?php endif; ?>
                </div>
            </div>
        </div>
        <div class="mt-2"><span class="fpm-muted">Subject (CN)</span><div style="word-break:break-all;font-size:12px;"><?= h($certificado->cn_subject ?: '—') ?></div></div>
    </div>
</div>
