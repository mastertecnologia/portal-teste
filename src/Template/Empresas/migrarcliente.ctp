<?php
	$this->Breadcrumbs->add('Empresas', ['controller' => 'Empresas', 'action' => 'index'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Migração de dados', [], ['class' => 'breadcrumb-item active']); 
?>
<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<?= $this->Form->create(null, ['class' => 'form-material']) ?>
				<div class="row">
					<div class="col-md-6 col-xs-12">
						<label class="control-label text-muted">Selecione os clientes que serão migrados para a empresa <?= $empresaParaNome ?>:</label>
						<?= $this->Form->control('clientes._ids', ['id' => 'clientes', 'data-live-search' => true, 'class' => 'form-control selectpicker', 'label' => false, 'multiple' => true, 'options' => $clientes]) ?>
					</div>
				</div>
				<?= $this->Form->button('Migrar dados', ['class' => 'btn btn-pgm btn-pgm-salvar btn-success']) ?>
			<?= $this->Form->end(); ?>
		</div>
	</div>
</div>
<script>
	$("#clientes option").prop("selected", "selected");
</script>