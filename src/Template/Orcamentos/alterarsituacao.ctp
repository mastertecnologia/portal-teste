<?php
	$this->append('css', $this->element('pgm_premium_css', ['name' => 'orcamentos-premium']));
	$this->Breadcrumbs->add('Orçamentos', ['controller' => 'Orcamentos', 'action' => 'index'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Editar', ['controller' => 'Orcamentos', 'action' => 'edit', $orcamento->id], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Alterar Situação', [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12 orc-premium-page-root">
<div class="orc-premium-wrap orc-premium-form">
	<div class="orc-page-head">
		<div>
			<div class="orc-eyebrow">Orçamento #<?= $orcamento->id ?></div>
			<h1 class="orc-h1">Alterar Situação</h1>
		</div>
		<div class="orc-page-head-actions">
			<?= $this->Html->link(
				'<i class="fa fa-arrow-left"></i> Voltar',
				['action' => 'edit', $orcamento->id],
				['class' => 'btn btn-orc-form-secondary btn-orc-compact', 'escape' => false]
			) ?>
		</div>
	</div>
	<div class="card orc-premium-card-inner">
		<div class="card-body">
			<?= $this->Form->create($orcamento, ['class' => 'form-material']); ?>

			<div class="orc-sec-title">Nova situação</div>
			<div class="row">
				<div class="col-lg-6 col-md-12">
					<div class="form-group">
						<label class="control-label">Situação</label>
						<?= $this->Form->control('status', ['class' => 'form-control', 'options' => C_OrcamentoStatus, 'label' => false]) ?>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-lg-12">
					<div class="form-group">
						<label class="control-label">Motivo da alteração</label>
						<?= $this->Form->textarea('motivo', ['class' => 'form-control', 'label' => false, 'required' => true, 'placeholder' => 'Insira o motivo da alteração', 'rows' => 3]) ?>
					</div>
				</div>
			</div>

			<div class="orc-footer-bar">
				<?= $this->Html->link(
					'<i class="fa fa-arrow-left"></i> Voltar',
					['action' => 'edit', $orcamento->id],
					['class' => 'btn btn-orc-form-secondary btn-orc-compact', 'escape' => false]
				) ?>
				<?= $this->Form->button(
					'<i class="fa fa-exchange"></i> Alterar situação',
					['class' => 'btn btn-orc-premium-primary btn-orc-compact', 'escape' => false]
				) ?>
			</div>

			<?= $this->Form->end(); ?>
		</div>
	</div>
</div>
</div>

