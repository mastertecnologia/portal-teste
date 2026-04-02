<?php
$this->assign('title', $title ?? 'Signatários');
$sig = $signatory;
?>
<div class="col-12 pgm-adv-page">
	<div class="pgm-adv-panel card mb-3">
		<div class="card-body">
			<h4 class="card-title"><?= h($title) ?> — <?= h($contract->code) ?></h4>
			<?= $this->element('ContractManagement/wizard_steps', ['step' => 'signatarios', 'contractId' => (int)$contract->id]) ?>
			<p class="mb-2">
				<?= $this->Html->link(__('Ir para ficha (PDF / assinatura)'), ['action' => 'view', $contract->id], ['class' => 'btn btn-sm btn-primary']) ?>
				<?= $this->Html->link(__('Voltar a serviços'), ['action' => 'addServicos', $contract->id], ['class' => 'btn btn-sm btn-default']) ?>
			</p>
			<?php if (!empty($contract->contract_signatories)): ?>
			<table class="table table-sm mt-2">
				<thead><tr><th><?= __('Nome') ?></th><th><?= __('E-mail') ?></th><th><?= __('Ordem') ?></th></tr></thead>
				<tbody>
					<?php foreach ($contract->contract_signatories as $s): ?>
					<tr><td><?= h($s->nome) ?></td><td><?= h($s->email) ?></td><td><?= (int)$s->ordem ?></td></tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php endif; ?>
			<hr>
			<?= $this->Form->create($sig) ?>
			<?= $this->Form->control('nome', ['label' => __('Nome')]) ?>
			<?= $this->Form->control('email', ['label' => __('E-mail')]) ?>
			<?= $this->Form->control('cpf', ['label' => 'CPF']) ?>
			<?= $this->Form->control('tipo', ['label' => __('Tipo'), 'default' => 'cliente']) ?>
			<?= $this->Form->control('ordem', ['label' => __('Ordem'), 'default' => 1]) ?>
			<?= $this->Form->control('auth_type', ['label' => __('Auth'), 'default' => 'email']) ?>
			<?= $this->Form->control('action_type', ['label' => __('Ação'), 'default' => 'SIGN']) ?>
			<?= $this->Form->button(__('Adicionar'), ['class' => 'btn btn-primary']) ?>
			<?= $this->Form->end() ?>
		</div>
	</div>
</div>
