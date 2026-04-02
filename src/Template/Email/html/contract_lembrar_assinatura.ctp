<?php
/** @var \Cake\Datasource\EntityInterface $contract */
/** @var \Cake\Datasource\EntityInterface $signatory */
$c = $contract;
$s = $signatory;
?>
<p>Olá <?= h((string)($s->nome ?? '')) ?>,</p>
<p>É necessária a sua assinatura no contrato <strong><?= h((string)($c->code ?? '')) ?></strong>.</p>
<p><a href="<?= h((string)($s->link_assinatura ?? '')) ?>">Abrir link de assinatura</a></p>
<p><small>PGM Soluções em TI</small></p>
