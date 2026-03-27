<?php
$this->Html->css('/dist/css/pages/queues-admin-shell.css', ['block' => true]);
$this->Breadcrumbs->add('Configurações', ['controller' => 'config', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Pastas', [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12 col-lg-10 p-0 queues-page-ambient">
	<div class="queues-shell queues-shell--elevated">
		<header class="queues-page-head">
			<div>
				<h1>Diretórios do sistema</h1>
				<p class="queues-page-sub">Caminhos de origem e destino usados por rotinas que manipulam programas ou arquivos no servidor.</p>
			</div>
			<div class="queues-page-actions">
				<?= $this->Html->link('<i class="fa fa-th-large"></i> Visão geral', ['action' => 'index'], ['class' => 'queues-btn', 'escape' => false]) ?>
			</div>
		</header>

		<div class="queues-form-panel queues-form-panel--wide">
			<?= $this->Form->create($config, ['class' => 'form-material']); ?>
			<div class="row">
				<div class="col-lg-12">
					<div class="form-group">
						<label class="control-label text-muted">Diretório de origem dos programas</label>
						<?= $this->Form->text('dirorigem', ['class' => 'form-control', 'label' => false, 'required' => true, 'placeholder' => 'C:\\… ou /var/…']) ?>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-lg-12">
					<div class="form-group">
						<label class="control-label text-muted">Diretório de destino dos programas</label>
						<?= $this->Form->text('dirdestino', ['class' => 'form-control', 'label' => false, 'required' => true]) ?>
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
