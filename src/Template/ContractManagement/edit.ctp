<?php
$this->assign('title', $title ?? 'Editar');
$__fmtContractMoney = static function ($v) {
	if ($v === null || $v === '') {
		return '';
	}

	return number_format((float)$v, 2, ',', '.');
};
?>
<div class="col-12 pgm-adv-page">
	<div class="pgm-adv-panel card">
		<div class="card-body">
			<h4 class="card-title"><?= h($title) ?></h4>
			<?= $this->element('ContractManagement/wizard_steps', ['step' => 'add', 'contractId' => (int)$contract->id]) ?>
			<?= $this->Form->create($contract, ['class' => 'contract-management-form']) ?>
			<?= $this->Form->control('idcliente', ['options' => $clientesList, 'empty' => __('Selecione…'), 'label' => __('Cliente'), 'class' => 'form-control']) ?>
			<?= $this->Form->control('code', ['label' => __('Código'), 'class' => 'form-control']) ?>
			<?= $this->Form->control('name', ['label' => __('Nome'), 'class' => 'form-control']) ?>
			<?= $this->Form->control('type', ['label' => __('Tipo'), 'class' => 'form-control']) ?>
			<?php
			$__stEdit = [];
			$__map = \App\Model\Entity\Contract::statusLabelMap();
			foreach (\App\Model\Table\ContractsTable::allowedStatusValues() as $__v) {
				$__stEdit[$__v] = $__map[$__v] ?? $__v;
			}
			?>
			<?= $this->Form->control('status', ['label' => __('Status'), 'options' => $__stEdit, 'class' => 'form-control']) ?>
			<?= $this->Form->control('template_id', ['options' => $templatesList, 'empty' => true, 'label' => __('Modelo'), 'class' => 'form-control']) ?>
			<div class="row contract-date-row">
				<div class="col-sm-6">
					<?= $this->Form->control('start_date', ['type' => 'date', 'label' => __('Início'), 'class' => 'form-control']) ?>
				</div>
				<div class="col-sm-6">
					<?= $this->Form->control('end_date', ['type' => 'date', 'label' => __('Fim'), 'class' => 'form-control']) ?>
				</div>
			</div>
			<?php
			$__mvOpts = [
				'label' => __('Valor mensal'),
				'class' => 'form-control',
				'placeholder' => '0,00',
				'title' => __('Use vírgula para centavos (ex.: 1500,99)'),
			];
			$__vtOpts = [
				'label' => __('Valor total'),
				'class' => 'form-control',
				'placeholder' => '0,00',
				'title' => __('Use vírgula para centavos (ex.: 1500,99)'),
			];
			if (!$this->request->is(['post', 'put'])) {
				$__mvOpts['value'] = $__fmtContractMoney($contract->monthly_value);
				$__vtOpts['value'] = $__fmtContractMoney($contract->valor_total);
			}
			?>
			<?= $this->Form->control('monthly_value', $__mvOpts) ?>
			<?= $this->Form->control('valor_total', $__vtOpts) ?>
			<?= $this->Form->control('nivel_sla', ['label' => __('Nível SLA'), 'class' => 'form-control']) ?>
			<?= $this->Form->control('auto_renew', ['type' => 'checkbox', 'label' => __('Renovação automática')]) ?>
			<?= $this->Form->control('observacoes_cli', ['type' => 'textarea', 'label' => __('Obs. cliente'), 'class' => 'form-control', 'rows' => 4]) ?>
			<?= $this->Form->control('notes', ['type' => 'textarea', 'label' => __('Notas internas'), 'class' => 'form-control', 'rows' => 4]) ?>
			<div class="btn-toolbar contract-form-actions">
			<?= $this->Form->button(__('Salvar'), ['class' => 'btn btn-primary']) ?>
			<?= $this->Html->link(__('Voltar'), ['action' => 'view', $contract->id], ['class' => 'btn btn-default']) ?>
			</div>
			<?= $this->Form->end() ?>
		</div>
	</div>
</div>
