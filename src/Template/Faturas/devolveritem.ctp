<?php
  	use Cake\Routing\Router;
	$this->Breadcrumbs->add('Locações', ['controller' => 'Faturas', 'action' => 'index'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add("Locação $fatura->nro", ['controller' => 'Faturas', 'action' => 'edit', $fatura->id], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Receber', [], ['class' => 'breadcrumb-item active']);
?>
<style>
	.dtp table.dtp-picker-days tr > td{
		font-weight: 700	 !important;
		font-size: 0.8em	 !important;
		text-align: center	 !important;
		padding: 0.5em 0.3em !important;
	}
</style>
<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<?= $this->Form->create($item, ['class' => 'form-material']); ?> 
				<div class="row m-t-5">
					<div class="col-xl-2 col-lg-2 com-md-3 col-sm-6 col-xs-12">
						<div class="form-group">
							<label class="control-label text-muted"> Data retorno </label>
							<?= $this->Form->text('dataretorno', ['class' => 'form-control datepicker ', 'id' => 'dataretorno', 'default' => date('d/m/Y'), 'placeholder' => 'Insira a data', 'required' => true]) ?>
						</div>
					</div>
					<div class="col-xl-2 col-lg-2 com-md-3 col-sm-6 col-xs-12">
						<div class="form-group">
							<label class="control-label text-muted"> Adicional cobrado: </label>
							<?= $this->Form->text('adicionalcobrado', ['class' => 'form-control mascaramonetaria', 'id' => 'adicionalcobrado', 'label' => false]) ?>
						</div>
					</div>
					<div class="col-xl-2 col-lg-2 com-md-3 col-sm-6 col-xs-12">
						<div class="form-group">
							<label class="control-label text-muted"> Quantidade devolvida </label>
							<?= $this->Form->control('qtddevolvida', ['class' => 'form-control', 'max' => $item->quantidade, 'min' => 0, 'label' => false]) ?>
						</div>
					</div>
					<div class="col-xl-2 col-lg-2 com-md-3 col-sm-6 col-xs-12">
						<div class="form-group">
							<label class="control-label text-muted"> Necessita reparos </label>
							<?= $this->Form->control('reparos', ['class' => 'form-control', 'options' => [0 => 'Não', 1 => 'Sim'], 'label' => false]) ?>
						</div>
					</div>
					<div class="col-xl-2 col-lg-2 com-md-3 col-sm-6 col-xs-12">
						<div class="form-group">
							<label class="control-label text-muted"> Acessórios devolvidos </label>
							<?= $this->Form->control('acessorios', ['class' => 'form-control', 'options' => [0 => 'Não', 1 => 'Sim'], 'label' => false]) ?>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-12">
						<div class="form-group">
							<label class="control-label text-muted"> Observações </label>
							<?= $this->Form->textarea('obs', ['class' => 'form-control', 'label' => false]) ?>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-12">
						<?= $this->Form->button('Confirmar devolução', ['class' => 'btn btn-pgm btn-pgm-salvar btn-success float-right']) ?>
					</div>
				</div>
			<?= $this->Form->end(); ?>
		</div>
	</div>
</div>
<script>
	
</script>