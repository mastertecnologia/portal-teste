<?php
/** @var \Cake\Datasource\EntityInterface $contract */
$c = $contract;
?>
<p>Olá,</p>
<p>Existe um novo contrato disponível para consulta: <strong><?= h((string)($c->code ?? '')) ?></strong> — <?= h((string)($c->name ?? '')) ?>.</p>
<p>Aceda ao portal do cliente para mais detalhes.</p>
<p><small>PGM Soluções em TI</small></p>
