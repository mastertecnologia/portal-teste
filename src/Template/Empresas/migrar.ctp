<?php
	$this->Breadcrumbs->add('Empresas', ['controller' => 'Empresas', 'action' => 'index'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Migração de dados', [], ['class' => 'breadcrumb-item active']); 
?>
<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<?= $this->Form->create(null, ['class' => 'form-material']) ?>
				<div class="row">
					<div class="col-md-4 col-xs-12">
						<div class="form-group">
							<label class="control-label text-muted">Selecione a empresa para qual os dados serão migrados:</label>
							<?= $this->Form->control('empresapara', ['class' => 'form-control', 'label' => false, 'required' => true, 'empty' => 'Empresa', 'options' => $empresasOptSidebar]) ?>
						</div>
					</div>
				</div>
				<?= $this->Form->button('Migrar dados', ['class' => 'btn btn-pgm btn-pgm-salvar btn-success']) ?>
			<?= $this->Form->end(); ?>
		</div>
	</div>
</div>
