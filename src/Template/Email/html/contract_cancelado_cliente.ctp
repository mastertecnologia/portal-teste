<?php
/** @var \Cake\Datasource\EntityInterface $contract */
/** @var string $motivo */
$c = $contract;
$m = (string)($motivo ?? '');
?>
<p>Olá,</p>
<p>O contrato <strong><?= h((string)($c->code ?? '')) ?></strong> foi cancelado.</p>
<?php if ($m !== '') : ?>
<p>Motivo: <?= nl2br(h($m)) ?></p>
<?php endif; ?>
<p><small>PGM Soluções em TI</small></p>
