<?php
$this->assign('title', $title ?? 'Novo contrato');
?>
<div class="col-12 pgm-adv-page">
	<div class="pgm-adv-panel card">
		<div class="card-body">
			<h4 class="card-title"><?= h($title) ?></h4>
			<?= $this->element('ContractManagement/wizard_steps', [
				'step' => 'add',
				'contractId' => (int)($contract->id ?? 0),
				'podeEditarDadosPasso' => !empty($contractMayEditCore),
			]) ?>
			<div class="alert alert-info small mb-3">
				<strong><?= __('Fluxo sugerido') ?>:</strong>
				<?= __('Ao gravar com "Continuar", segue para serviços; use "Abrir ficha" para ir direto à ficha do contrato.') ?>
				<?= $this->Html->link(__('Modelos'), '/contract-templates', ['class' => 'alert-link']) ?>.
			</div>
			<?= $this->Form->create($contract, ['class' => 'contract-management-form']) ?>
			<?= $this->Form->hidden('idempresa') ?>
			<?= $this->Form->control('idcliente', ['options' => $clientesList, 'empty' => __('Selecione…'), 'label' => __('Cliente'), 'class' => 'form-control']) ?>
			<?= $this->Form->control('code', ['label' => __('Código'), 'class' => 'form-control']) ?>
			<?= $this->Form->control('name', ['label' => __('Nome'), 'class' => 'form-control']) ?>
			<?= $this->Form->control('type', ['label' => __('Tipo'), 'class' => 'form-control']) ?>
			<?= $this->Form->control('template_id', ['options' => $templatesList, 'empty' => true, 'label' => __('Modelo'), 'class' => 'form-control']) ?>
			<div class="row contract-date-row">
				<div class="col-sm-6">
					<?= $this->Form->control('start_date', ['type' => 'date', 'label' => __('Início'), 'class' => 'form-control']) ?>
				</div>
				<div class="col-sm-6">
					<?= $this->Form->control('end_date', ['type' => 'date', 'label' => __('Fim'), 'class' => 'form-control']) ?>
				</div>
			</div>
			<?= $this->Form->control('monthly_value', [
				'label' => __('Valor mensal'),
				'class' => 'form-control',
				'placeholder' => '0,00',
				'title' => __('Use vírgula para centavos (ex.: 1500,99)'),
			]) ?>
			<?= $this->Form->control('valor_total', [
				'label' => __('Valor total'),
				'class' => 'form-control',
				'placeholder' => '0,00',
				'title' => __('Use vírgula para centavos (ex.: 1500,99)'),
			]) ?>
			<?= $this->Form->control('nivel_sla', ['label' => __('Nível SLA'), 'class' => 'form-control']) ?>
			<?= $this->Form->control('auto_renew', ['type' => 'checkbox', 'label' => __('Renovação automática')]) ?>
			<?= $this->Form->control('observacoes_cli', ['type' => 'textarea', 'label' => __('Obs. cliente'), 'class' => 'form-control', 'rows' => 4]) ?>
			<?= $this->Form->control('notes', ['type' => 'textarea', 'label' => __('Notas internas'), 'class' => 'form-control', 'rows' => 4]) ?>
			<div class="btn-toolbar contract-form-actions">
			<?= $this->Form->button(__('Gravar e continuar'), ['class' => 'btn btn-primary', 'name' => 'gravar_destino', 'value' => 'wizard']) ?>
			<?= $this->Form->button(__('Gravar e abrir ficha'), ['class' => 'btn btn-default', 'name' => 'gravar_destino', 'value' => 'ficha']) ?>
			<?= $this->Html->link(__('Cancelar'), ['action' => 'index'], ['class' => 'btn btn-default']) ?>
			</div>
			<?= $this->Form->end() ?>
		</div>
	</div>
</div>
