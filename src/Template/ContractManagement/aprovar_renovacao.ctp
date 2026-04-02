<?php
$this->assign('title', $title ?? 'Renovação');
?>
<div class="col-12 pgm-adv-page">
	<div class="pgm-adv-panel card">
		<div class="card-body">
			<h4 class="card-title"><?= h($title) ?></h4>
			<p class="small"><?= __('Contrato') ?>: <?= h($contract->code) ?> · <?= h($renewal->status) ?></p>
			<?= $this->Form->create(null, ['url' => ['action' => 'aprovarRenovacao', $renewal->id]]) ?>
			<?= $this->Form->control('vigencia_inicio', ['type' => 'date', 'label' => __('Nova vigência início'), 'value' => $renewal->nova_vigencia_inicio]) ?>
			<?= $this->Form->control('vigencia_fim', ['type' => 'date', 'label' => __('Nova vigência fim'), 'value' => $renewal->nova_vigencia_fim]) ?>
			<?= $this->Form->control('valor_mensal', ['label' => __('Valor mensal'), 'value' => $renewal->novo_valor_mensal]) ?>
			<?= $this->Form->button(__('Aprovar e gerar novo contrato'), ['class' => 'btn btn-success']) ?>
			<?= $this->Html->link(__('Cancelar'), ['action' => 'view', $contract->id], ['class' => 'btn btn-default']) ?>
			<?= $this->Form->end() ?>
		</div>
	</div>
</div>
