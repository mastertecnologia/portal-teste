<?php
$this->append('css', $this->element('pgm_premium_css', ['name' => 'orcamentos-premium']));
// Breadcumbs
$this->Breadcrumbs->add('Orçamentos', ['controller' => 'Orcamentos', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Editar', ['controller' => 'Orcamentos', 'action' => 'edit', $orcamento->id], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Enviar Orçamento', [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12 orc-premium-page-root">
<div class="orc-premium-wrap orc-premium-form">
	<div class="orc-page-head">
		<div>
			<div class="orc-eyebrow">Orçamento #<?= $orcamento->id ?></div>
			<h1 class="orc-h1">Enviar orçamento</h1>
		</div>
	</div>
	<div class="card orc-premium-card-inner">
		<div class="card-body">
			<?= $this->Form->create($orcamento, ['class' => 'form-material']); ?>
				<div class="tab-content">
					<div class="row">
						<div class="col-lg-12">
							<div class="form-group">
								<label class="control-label text-muted">Observação</label>
								<?= $this->Form->textarea('motivo', ['class' => 'form-control', 'label' => false, 'required' => false, 'placeholder' => 'Insira a obervação']) ?>
							</div>
						</div>
					</div>
					<div class="orc-footer-bar">
						<?= $this->Html->link('Voltar para o Orçamento', ["action" => "edit", $orcamento->id], ['class' => 'btn btn-orc-form-secondary btn-orc-compact']); ?>
						<?= $this->Form->button('Enviar Orçamento', ['class' => 'btn btn-orc-premium-primary btn-orc-compact']) ?>
					</div>
				</div>
			<?= $this->Form->end(); ?>
		</div>
	</div>
</div>
</div>
