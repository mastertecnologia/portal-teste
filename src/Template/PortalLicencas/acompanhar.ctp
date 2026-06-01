<?php /** @var array<string,mixed> $licSolicitacao */ $s = (array)($licSolicitacao ?? []);
$statusLabels = [
	'aberta' => __('Aberta'),
	'em_analise' => __('Em análise'),
	'aprovada' => __('Aprovada'),
	'recusada' => __('Recusada'),
	'cancelada' => __('Cancelada'),
];
$payload = (array)($s['payload'] ?? []);
?>
<h1 style="font-size:22px;font-weight:600;margin:0 0 14px;"><?= h(__('Solicitação #{0}', (int)($s['id'] ?? 0))) ?></h1>
<div class="card g2">
	<div><strong><?= h(__('Status')) ?></strong><br><?= h($statusLabels[$s['status'] ?? ''] ?? $s['status'] ?? '') ?></div>
	<div><strong><?= h(__('Criada em')) ?></strong><br><?= h($s['created'] ?? '') ?></div>
	<div><strong><?= h(__('Produto')) ?></strong><br><?= h($payload['produto'] ?? '—') ?></div>
	<div><strong><?= h(__('Assentos')) ?></strong><br><?= (int)($payload['assentos'] ?? 0) ?></div>
	<?php if (!empty($payload['observacao'])) : ?>
	<div style="grid-column:1/-1;"><strong><?= h(__('Observações')) ?></strong><br><?= h($payload['observacao']) ?></div>
	<?php endif; ?>
</div>
<p style="margin-top:12px;"><?= $this->Html->link('← ' . __('Painel'), ['action' => 'index'], ['class' => 'btn btn-ghost btn-sm']) ?></p>
