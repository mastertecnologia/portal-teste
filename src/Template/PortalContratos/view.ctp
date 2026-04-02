<?php
$this->assign('title', $title ?? __('Contrato'));
$id = (int)$contract->id;
?>
<div class="col-12 pgm-adv-page">
	<div class="pgm-adv-panel card mb-3">
		<div class="card-body">
			<h4 class="card-title"><?= h($contract->name) ?></h4>
			<p class="small"><?= h($contract->code) ?> · <?= h($contract->status_label) ?></p>
			<p class="small"><?= __('Vigência') ?>:
				<?= h($contract->start_date ? $contract->start_date->format('d/m/Y') : '') ?> — <?= h($contract->end_date ? $contract->end_date->format('d/m/Y') : '') ?>
				<?php if ($contract->dias_para_vencer !== null && $contract->end_date): ?>
					<?php $d = (int)$contract->dias_para_vencer; ?>
					<span class="text-muted"> · <?= $d >= 0
						? __n('{0} dia até o fim', '{0} dias até o fim', $d, $d)
						: __n('{0} dia após o fim', '{0} dias após o fim', abs($d), abs($d)); ?></span>
				<?php endif; ?>
			</p>
			<?php if (!empty($contract->observacoes_cli)): ?>
			<p class="small mb-2"><?= nl2br(h($contract->observacoes_cli)) ?></p>
			<?php endif; ?>
			<?php if (!empty($contract->contract_template)): ?>
			<p class="small text-muted mb-2"><?= __('Modelo') ?>: <?= h($contract->contract_template->nome) ?></p>
			<?php endif; ?>
			<?php if (!empty($contract->pdf_path) && is_readable((string)$contract->pdf_path)): ?>
			<?= $this->Html->link(__('Descarregar PDF'), '/cliente/contratos/pdf/' . $id, ['class' => 'btn btn-sm btn-primary mr-1']) ?>
			<?php endif; ?>
			<?php if (!empty($contract->signed_pdf_path) && is_readable((string)$contract->signed_pdf_path)): ?>
			<?= $this->Html->link(__('PDF assinado'), '/cliente/contratos/pdf-assinado/' . $id, ['class' => 'btn btn-sm btn-success mr-1']) ?>
			<?php endif; ?>
			<?= $this->Form->postLink(
				__('Solicitar renovação'),
				['action' => 'solicitarRenovacao', $id],
				['class' => 'btn btn-sm btn-default mr-1', 'confirm' => __('Enviar pedido de renovação à equipe?')]
			) ?>
			<?= $this->Html->link(__('Voltar'), ['action' => 'index'], ['class' => 'btn btn-sm btn-secondary']) ?>
		</div>
	</div>
	<?php if (!empty($contract->contract_services)): ?>
	<div class="pgm-adv-panel card mb-3">
		<div class="card-body">
			<h5><?= __('Serviços') ?></h5>
			<ul class="mb-0"><?php foreach ($contract->contract_services as $s): ?><li><?= h($s->service_name) ?></li><?php endforeach; ?></ul>
		</div>
	</div>
	<?php endif; ?>
	<?php if (!empty($contract->contract_documents)): ?>
	<div class="pgm-adv-panel card mb-3">
		<div class="card-body">
			<h5><?= __('Documentos públicos') ?></h5>
			<ul class="mb-0"><?php foreach ($contract->contract_documents as $d): ?><li><?= h($d->title) ?></li><?php endforeach; ?></ul>
		</div>
	</div>
	<?php endif; ?>
	<?php if (!empty($contract->contract_signatories)): ?>
	<div class="pgm-adv-panel card">
		<div class="card-body">
			<h5><?= __('Assinaturas') ?></h5>
			<ul class="mb-0 small">
				<?php foreach ($contract->contract_signatories as $sig): ?>
				<li><?= h($sig->nome) ?> — <?= h($sig->status ?: __('pendente')) ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
	<?php endif; ?>
</div>
