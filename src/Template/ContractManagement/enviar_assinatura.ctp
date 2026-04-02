<?php
$this->assign('title', $title ?? 'Assinatura');
?>
<div class="col-12 pgm-adv-page">
	<div class="pgm-adv-panel card">
		<div class="card-body">
			<h4 class="card-title"><?= h($title) ?> — <?= h($contract->code) ?></h4>
			<p class="text-muted small"><?= __('Confira signatários e PDF. Com CONTRACT_AUTENTIQUE_ENABLED o envio cria o documento na Autentique; sem API o contrato fica só “aguardando assinatura” internamente.') ?></p>
			<?php if (!empty($contract->contract_signatories)): ?>
			<table class="table table-sm">
				<thead><tr><th><?= __('Ordem') ?></th><th><?= __('Nome') ?></th><th><?= __('E-mail') ?></th></tr></thead>
				<tbody>
					<?php foreach ($contract->contract_signatories as $s): ?>
					<tr><td><?= (int)$s->ordem ?></td><td><?= h($s->nome) ?></td><td><?= h($s->email) ?></td></tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php else: ?>
			<p class="text-warning"><?= __('Cadastre signatários antes.') ?> <?= $this->Html->link(__('Ir'), ['action' => 'addSignatarios', $contract->id]) ?></p>
			<?php endif; ?>
			<?= $this->Form->create(null, ['url' => ['action' => 'enviarAssinatura', $contract->id]]) ?>
			<?= $this->Form->button(__('Confirmar envio / marcar aguardando assinatura'), ['class' => 'btn btn-primary', 'disabled' => empty($contract->contract_signatories)]) ?>
			<?= $this->Html->link(__('Voltar'), ['action' => 'view', $contract->id], ['class' => 'btn btn-default']) ?>
			<?= $this->Form->end() ?>
		</div>
	</div>
</div>
