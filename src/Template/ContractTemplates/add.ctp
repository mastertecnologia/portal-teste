<?php
$this->assign('title', $title ?? 'Novo modelo');
?>
<div class="col-12 pgm-adv-page">
	<div class="pgm-adv-panel card mb-3">
		<div class="card-body">
			<h4 class="card-title"><?= h($title) ?></h4>
			<?= $this->Form->create($template, ['url' => '/modulo-avancado/modelos-contrato/add']) ?>
			<?= $this->element('ContractTemplates/form_fields') ?>
			<?= $this->Form->button(__('Gravar'), ['class' => 'btn btn-primary']) ?>
			<?= $this->Html->link(__('Cancelar'), '/modulo-avancado/modelos-contrato', ['class' => 'btn btn-secondary']) ?>
			<?= $this->Form->end() ?>
		</div>
	</div>
</div>
