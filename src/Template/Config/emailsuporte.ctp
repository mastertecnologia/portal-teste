<?php
$this->Html->css('/dist/css/pages/queues-admin-shell.css', ['block' => true]);
$this->Breadcrumbs->add('Configurações', ['controller' => 'config', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('E-mail suporte', [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12 col-lg-8 p-0 queues-page-ambient">
	<div class="queues-shell queues-shell--elevated">
		<header class="queues-page-head">
			<div>
				<h1>E-mail do suporte</h1>
				<p class="queues-page-sub">Endereço usado como destino padrão em fluxos de tickets e comunicações de suporte.</p>
			</div>
			<div class="queues-page-actions">
				<?= $this->Html->link('<i class="fa fa-th-large"></i> Visão geral', ['action' => 'index'], ['class' => 'queues-btn', 'escape' => false]) ?>
			</div>
		</header>

		<div class="queues-form-panel queues-form-panel--wide">
			<?= $this->Form->create($config, ['class' => 'form-material']); ?>
			<div class="form-group">
				<label class="control-label text-muted">E-mail do suporte</label>
				<?= $this->Form->text('emailtickets', ['class' => 'form-control', 'label' => false, 'placeholder' => 'suporte@empresa.com.br']) ?>
			</div>
			<div class="queues-form-actions m-t-20">
				<?= $this->Form->button('Salvar', ['class' => 'queues-btn queues-btn--success']) ?>
				<?= $this->Html->link('Cancelar', ['action' => 'index'], ['class' => 'queues-btn']) ?>
			</div>
			<?= $this->Form->end(); ?>
		</div>
	</div>
</div>
