<?php
// Breadcumbs
$this->Breadcrumbs->add('Orçamentos', ['controller' => 'Orcamentos', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Editar', ['controller' => 'Orcamentos', 'action' => 'edit', $orcamento->id], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Enviar Orçamento', [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12">
	<div class="card">
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
					<div class="row">
						<div class="col-lg-12">
							<?= $this->Form->button('Enviar Orçamento', ['class' => 'btn btn-success m-l-5']) ?>
							<?= $this->Html->link('Voltar para o Orçamento', ["action" => "edit", $orcamento->id], ['class' => 'btn btn-info']); ?>
						</div>
					</div>
				</div>
			<?= $this->Form->end(); ?>
		</div>
	</div>
</div>
