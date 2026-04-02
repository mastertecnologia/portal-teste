<?php
$this->assign('title', $title ?? 'Serviços');
$svc = $service;
?>
<div class="col-12 pgm-adv-page">
	<div class="pgm-adv-panel card mb-3">
		<div class="card-body">
			<h4 class="card-title"><?= h($title) ?> — <?= h($contract->code) ?></h4>
			<?= $this->element('ContractManagement/wizard_steps', ['step' => 'servicos', 'contractId' => (int)$contract->id]) ?>
			<p class="mb-2">
				<?= $this->Html->link(__('Continuar para signatários'), ['action' => 'addSignatarios', $contract->id], ['class' => 'btn btn-sm btn-primary']) ?>
				<?= $this->Html->link(__('Ir para ficha do contrato'), ['action' => 'view', $contract->id], ['class' => 'btn btn-sm btn-default']) ?>
				<?= $this->Html->link(__('Ajustar dados'), ['action' => 'edit', $contract->id], ['class' => 'btn btn-sm btn-link']) ?>
			</p>
			<?php if (!empty($contract->contract_services)): ?>
			<ul class="mt-2">
				<?php foreach ($contract->contract_services as $s): ?>
				<li><?= h($s->service_name) ?> <?= !empty($s->is_included) ? '<span class="label label-info">incluso</span>' : '' ?></li>
				<?php endforeach; ?>
			</ul>
			<?php endif; ?>
			<hr>
			<h5><?= __('Adicionar linha') ?></h5>
			<?= $this->Form->create($svc) ?>
			<?= $this->Form->control('service_name', ['label' => __('Serviço')]) ?>
			<?= $this->Form->control('service_description', ['type' => 'textarea', 'label' => __('Descrição')]) ?>
			<?= $this->Form->control('is_included', ['type' => 'checkbox', 'label' => __('Incluso na franquia')]) ?>
			<?= $this->Form->control('max_hours', ['label' => __('Horas / quantidade')]) ?>
			<?= $this->Form->control('tipo_item', ['label' => __('Tipo item'), 'default' => 'servico']) ?>
			<?= $this->Form->control('unidade', ['label' => __('Unidade'), 'default' => 'unid']) ?>
			<?= $this->Form->control('valor_unitario', ['label' => __('Valor unitário')]) ?>
			<?= $this->Form->control('valor_total', ['label' => __('Valor total linha')]) ?>
			<?= $this->Form->button(__('Adicionar'), ['class' => 'btn btn-primary']) ?>
			<?= $this->Form->end() ?>
		</div>
	</div>
</div>
