<?php
$this->assign('title', $title ?? 'Editar');
?>
<div class="col-12 pgm-adv-page">
	<div class="pgm-adv-panel card">
		<div class="card-body">
			<h4 class="card-title"><?= h($title) ?></h4>
			<?= $this->element('ContractManagement/wizard_steps', ['step' => 'add', 'contractId' => (int)$contract->id]) ?>
			<?= $this->Form->create($contract) ?>
			<?= $this->Form->control('idcliente', ['options' => $clientesList, 'empty' => __('Selecione…'), 'label' => __('Cliente')]) ?>
			<?= $this->Form->control('code', ['label' => __('Código')]) ?>
			<?= $this->Form->control('name', ['label' => __('Nome')]) ?>
			<?= $this->Form->control('type', ['label' => __('Tipo')]) ?>
			<?php
			$__stEdit = [];
			$__map = \App\Model\Entity\Contract::statusLabelMap();
			foreach (\App\Model\Table\ContractsTable::allowedStatusValues() as $__v) {
				$__stEdit[$__v] = $__map[$__v] ?? $__v;
			}
			?>
			<?= $this->Form->control('status', ['label' => __('Status'), 'options' => $__stEdit]) ?>
			<?= $this->Form->control('template_id', ['options' => $templatesList, 'empty' => true, 'label' => __('Modelo')]) ?>
			<?= $this->Form->control('start_date', ['type' => 'date', 'label' => __('Início')]) ?>
			<?= $this->Form->control('end_date', ['type' => 'date', 'label' => __('Fim')]) ?>
			<?= $this->Form->control('monthly_value', ['label' => __('Valor mensal')]) ?>
			<?= $this->Form->control('valor_total', ['label' => __('Valor total')]) ?>
			<?= $this->Form->control('nivel_sla', ['label' => __('Nível SLA')]) ?>
			<?= $this->Form->control('auto_renew', ['type' => 'checkbox', 'label' => __('Renovação automática')]) ?>
			<?= $this->Form->control('observacoes_cli', ['type' => 'textarea', 'label' => __('Obs. cliente')]) ?>
			<?= $this->Form->control('notes', ['type' => 'textarea', 'label' => __('Notas internas')]) ?>
			<?= $this->Form->button(__('Salvar'), ['class' => 'btn btn-primary']) ?>
			<?= $this->Html->link(__('Voltar'), ['action' => 'view', $contract->id], ['class' => 'btn btn-default']) ?>
			<?= $this->Form->end() ?>
		</div>
	</div>
</div>
