<?php $this->assign('title', $title ?? 'Contrato'); ?>
<div class="card mb-3">
	<div class="card-body">
		<h4 class="card-title"><?= h($contract->name) ?></h4>
		<p class="small"><?= h($contract->code) ?> · <?= h($contract->status) ?></p>
		<p class="small">Vigência: <?= h($contract->start_date ? $contract->start_date->format('d/m/Y') : '') ?> — <?= h($contract->end_date ? $contract->end_date->format('d/m/Y') : '') ?></p>
		<?= $this->Html->link('Voltar', ['action' => 'index'], ['class' => 'btn btn-sm btn-secondary']) ?>
	</div>
</div>
<?php if (!empty($contract->contract_services)): ?>
<div class="card mb-3">
	<div class="card-body">
		<h5>Serviços</h5>
		<ul class="mb-0"><?php foreach ($contract->contract_services as $s): ?><li><?= h($s->service_name) ?></li><?php endforeach; ?></ul>
	</div>
</div>
<?php endif; ?>
<?php if (!empty($contract->contract_documents)): ?>
<div class="card">
	<div class="card-body">
		<h5>Documentos públicos</h5>
		<ul class="mb-0"><?php foreach ($contract->contract_documents as $d): ?><li><?= h($d->title) ?></li><?php endforeach; ?></ul>
	</div>
</div>
<?php endif; ?>
