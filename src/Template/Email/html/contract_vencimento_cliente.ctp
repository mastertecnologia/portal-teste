<?php
/** @var \Cake\Datasource\EntityInterface $contract */
/** @var int $dias */
$c = $contract;
$d = (int)($dias ?? 0);
?>
<p>Olá,</p>
<p>O contrato <strong><?= h((string)($c->code ?? '')) ?></strong> (<?= h((string)($c->name ?? '')) ?>) vence em <strong><?= h((string)$d) ?></strong> dia(s).</p>
<p>Data fim da vigência: <?= h($c->end_date instanceof \DateTimeInterface ? $c->end_date->format('d/m/Y') : (string)$c->end_date) ?>.</p>
<p>Em caso de dúvidas, fale connosco.</p>
<p><small>PGM Soluções em TI</small></p>
