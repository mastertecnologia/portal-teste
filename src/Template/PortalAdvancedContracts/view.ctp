<?php $this->assign('title', $title ?? 'Contrato'); ?>
<div class="col-12 pgm-adv-page">
	<div class="pgm-adv-panel card mb-3">
		<div class="card-body">
			<h4 class="card-title"><?= h($contract->name) ?></h4>
			<p class="small"><?= h($contract->code) ?> · <?= h($contract->status) ?></p>
			<p class="small">Vigência: <?= h($contract->start_date ? $contract->start_date->format('d/m/Y') : '') ?> — <?= h($contract->end_date ? $contract->end_date->format('d/m/Y') : '') ?></p>
			<?php if (!empty($contract->observacoes_cli)): ?>
			<p class="small mb-2"><?= nl2br(h($contract->observacoes_cli)) ?></p>
			<?php endif; ?>
			<?php if (!empty($contract->contract_template)): ?>
			<p class="small text-muted mb-2">Modelo: <?= h($contract->contract_template->nome) ?></p>
			<?php endif; ?>
			<?php if (!empty($contract->pdf_path) && is_readable((string)$contract->pdf_path)): ?>
			<?= $this->Html->link('Descarregar PDF', '/cliente/contratos-avancados/export-pdf/' . (int)$contract->id, ['class' => 'btn btn-sm btn-primary mr-1']) ?>
			<?php endif; ?>
			<?= $this->Html->link('Voltar', ['action' => 'index'], ['class' => 'btn btn-sm btn-secondary']) ?>
		</div>
	</div>
	<?php if (!empty($contract->contract_services)): ?>
	<div class="pgm-adv-panel card mb-3">
		<div class="card-body">
			<h5>Serviços</h5>
			<ul class="mb-0"><?php foreach ($contract->contract_services as $s): ?><li><?= h($s->service_name) ?></li><?php endforeach; ?></ul>
		</div>
	</div>
	<?php endif; ?>
	<?php if (!empty($contract->contract_documents)): ?>
	<div class="pgm-adv-panel card mb-3">
		<div class="card-body">
			<h5>Documentos públicos</h5>
			<ul class="mb-0"><?php foreach ($contract->contract_documents as $d): ?><li><?= h($d->title) ?></li><?php endforeach; ?></ul>
		</div>
	</div>
	<?php endif; ?>
	<?php if (!empty($contract->contract_signatories)): ?>
	<div class="pgm-adv-panel card">
		<div class="card-body">
			<h5>Assinaturas</h5>
			<ul class="mb-0 small">
				<?php foreach ($contract->contract_signatories as $sig): ?>
				<li><?= h($sig->nome) ?> — <?= h($sig->status ?: 'pendente') ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
	<?php endif; ?>
</div>
