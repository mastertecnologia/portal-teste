<?php
	$this->Breadcrumbs->add('Orçamentos', ['controller' => 'Orcamentos', 'action' => 'index'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Editar', ['controller' => 'Orcamentos', 'action' => 'edit', $orcamento->id], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Alterar Situação', [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<?= $this->Form->create($orcamento, ['class' => 'form-material']); ?>
			<div class="tab-content">
				<div class="row">
					<div class="col-lg-12">
						<div class="form-group">
							<label class="control-label text-muted">Situação</label>
							<?= $this->Form->control('status', ['class' => 'form-control', 'options' => C_OrcamentoStatus, 'label' => false]) ?>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-12">
						<div class="form-group ">
							<label class="control-label text-muted">Motivo da alteração</label>
							<?= $this->Form->textarea('motivo', ['class' => 'form-control', 'label' => false, 'required' => true, 'placeholder' => 'Insira o motivo da alteração']) ?>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-12">
						<?= $this->Form->button('Alterar situação do orçamento', ['class' => 'btn btn-pgm btn-pgm-situacao btn-queequaseinfo btn-options m-l-5']) ?>						
						<?= $this->Html->link('Voltar para o orçamento', ["action" => "edit", $orcamento->id], ['class' => 'btn btn-pgm btn-pgm-situacao btn-info btn-options ']); ?>
					</div>
				</div>
			</div>

			<div class="clearfix"></div>
			<?= $this->Form->end(); ?>
		</div>
	</div>
</div>

