<?php
$this->assign('title', $title ?? 'Contrato');
?>
<div class="col-12 pgm-adv-page">
	<div class="pgm-adv-panel card mb-3">
		<div class="card-body">
			<h4 class="card-title"><?= h($contract->name) ?></h4>
			<p class="small text-muted mb-2">Código <?= h($contract->code) ?> · <?= h($contract->status) ?></p>
			<dl class="row small mb-0">
				<dt class="col-sm-3">Vigência</dt>
				<dd class="col-sm-9"><?= h($contract->start_date ? $contract->start_date->format('d/m/Y') : '') ?> — <?= h($contract->end_date ? $contract->end_date->format('d/m/Y') : '') ?></dd>
				<dt class="col-sm-3">Mensalidade</dt>
				<dd class="col-sm-9"><?= h($contract->monthly_value) ?></dd>
				<dt class="col-sm-3">SLA (h)</dt>
				<dd class="col-sm-9"><?= h($contract->sla_hours) ?></dd>
			</dl>
			<?= $this->Html->link('Voltar', ['action' => 'index'], ['class' => 'btn btn-sm btn-secondary mt-2']) ?>
		</div>
	</div>
	<?php if (!empty($contract->contract_services)): ?>
	<div class="pgm-adv-panel card mb-3">
		<div class="card-body">
			<h5 class="card-title">Serviços</h5>
			<ul class="mb-0">
				<?php foreach ($contract->contract_services as $s): ?>
				<li><?= h($s->service_name) ?><?= !empty($s->is_included) ? ' <span class="badge badge-info">incluso</span>' : '' ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
	<?php endif; ?>
	<?php if (!empty($contract->contract_documents)): ?>
	<div class="pgm-adv-panel card">
		<div class="card-body">
			<h5 class="card-title">Documentos</h5>
			<ul class="mb-0">
				<?php foreach ($contract->contract_documents as $d): ?>
				<li><?= h($d->title) ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
	<?php endif; ?>
</div>
