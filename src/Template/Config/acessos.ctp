<?php
$this->Html->css('/dist/css/pages/queues-admin-shell.css', ['block' => true]);
$this->Breadcrumbs->add('Configurações', ['controller' => 'config', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Acessos', [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12 col-lg-10 p-0 queues-page-ambient">
	<div class="queues-shell queues-shell--elevated">
		<header class="queues-page-head">
			<div>
				<h1>Login externo e horários</h1>
				<p class="queues-page-sub">URL pública do portal, e-mails para relatórios e janela de horário comercial usados nas integrações e notificações.</p>
			</div>
			<div class="queues-page-actions">
				<?= $this->Html->link('<i class="fa fa-th-large"></i> Visão geral', ['action' => 'index'], ['class' => 'queues-btn', 'escape' => false]) ?>
			</div>
		</header>

		<div class="queues-form-panel queues-form-panel--wide">
			<?= $this->Form->create($config, ['class' => 'form-material']); ?>
			<h6 class="text-muted m-t-0 m-b-10">Acesso externo</h6>
			<div class="row">
				<div class="col-lg-12">
					<div class="form-group">
						<label class="control-label text-muted">URL de acesso externo</label>
						<?= $this->Form->text('urlfora', ['class' => 'form-control', 'label' => false, 'required' => true, 'placeholder' => 'https://portal.exemplo.com/portal (com /portal se aplicável, sem barra no final)']) ?>
					</div>
				</div>
			</div>
			<h6 class="text-muted m-t-20 m-b-10">Relatórios e horário</h6>
			<div class="row">
				<div class="col-lg-12">
					<div class="form-group">
						<label class="control-label text-muted">E-mails diretores (relatório pós-atendimento — separados por ;)</label>
						<?= $this->Form->textarea('email_diretores_relatorio', ['class' => 'form-control', 'label' => false, 'rows' => 2]) ?>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-lg-4 col-md-6">
					<div class="form-group">
						<label class="control-label text-muted">Horário comercial início (ex.: 08:00)</label>
						<?= $this->Form->text('horario_comercial_inicio', ['class' => 'form-control', 'label' => false]) ?>
					</div>
				</div>
				<div class="col-lg-4 col-md-6">
					<div class="form-group">
						<label class="control-label text-muted">Horário comercial fim (ex.: 18:00)</label>
						<?= $this->Form->text('horario_comercial_fim', ['class' => 'form-control', 'label' => false]) ?>
					</div>
				</div>
			</div>
			<div class="queues-form-actions m-t-20">
				<?= $this->Form->button('Salvar', ['class' => 'queues-btn queues-btn--success']) ?>
				<?= $this->Html->link('Cancelar', ['action' => 'index'], ['class' => 'queues-btn']) ?>
			</div>
			<?= $this->Form->end(); ?>
		</div>
	</div>
</div>
