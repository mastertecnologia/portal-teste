<?php
$this->assign('title', $title ?? 'Assinatura');
?>
<style>
.pgm-adv-page .btn-default,.pgm-adv-page a.btn-default{background-color:#546e7a!important;border-color:#546e7a!important;color:#fff!important;}
.pgm-adv-page .btn-default:hover,.pgm-adv-page a.btn-default:hover{background-color:#607d8b!important;border-color:#607d8b!important;color:#fff!important;}
</style>
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
			<div class="checkbox" style="margin:12px 0;">
				<label>
					<?= $this->Form->checkbox('enviar_email_signatarios', ['value' => '1', 'checked' => true]) ?>
					<?= __('Enviar e-mail a cada signatário com o link de assinatura (após gerar links na Autentique). Requer Contract.notifications.from_email configurado.') ?>
				</label>
			</div>
			<p class="text-muted small"><?= __('Sem Autentique ativa não há link por signatário — o e-mail só será enviado quando existir link gravado.') ?></p>
			<?= $this->Form->button(__('Confirmar envio / marcar aguardando assinatura'), ['class' => 'btn btn-primary', 'disabled' => empty($contract->contract_signatories)]) ?>
			<?= $this->Html->link(__('Voltar'), ['action' => 'view', $contract->id], ['class' => 'btn btn-default']) ?>
			<?= $this->Form->end() ?>
		</div>
	</div>
</div>
