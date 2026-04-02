<?php
/** @var \Cake\Datasource\EntityInterface $contract */
/** @var int $dias */
$c = $contract;
$d = (int)($dias ?? 0);
?>
<p><strong>Alerta interno — vencimento de contrato</strong></p>
<p>Contrato: <strong><?= h((string)($c->code ?? '')) ?></strong> — <?= h((string)($c->name ?? '')) ?></p>
<p>Vence em <strong><?= h((string)$d) ?></strong> dia(s). Data fim: <?= h($c->end_date instanceof \DateTimeInterface ? $c->end_date->format('d/m/Y') : (string)$c->end_date) ?>.</p>
