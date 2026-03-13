<div class="card">
	<div class="card-body">
		<div class="col-12">
			<?= $this->Form->create($item, ['class' => 'form-material']); ?> 
				<div class="row m-t-10">
					<div class="col-lg-2 col-md-12">
						<label class="control-label text-muted"> Código </label>
						<?= $this->Form->control('codigo', ['class' => 'form-control', 'label' => false, 'disabled']) ?>
					</div>
					<div class="col-lg-8 col-md-12">
						<label class="control-label text-muted"> Descrição da Locação </label>
						<?= $this->Form->control('descricao', ['class' => 'descricao form-control', 'label' => false, 'disabled']) ?>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-2 col-md-6">
						<div class="form-group ">
							<label class="control-label text-muted"> Quantidade </label>
							<?= $this->Form->control('quantidade', ['disabled', 'class' => 'quantidade form-control', 'label' => false]) ?>
						</div>
					</div>
					<div class="col-lg-2 col-md-6">
						<div class="form-group ">
							<label class="control-label text-muted"> Qtd devolvida </label>
							<?= $this->Form->control('qtddevolvida', ['disabled', 'class' => 'quantidade form-control', 'label' => false]) ?>
						</div>
					</div>
					<div class="col-lg-3 col-md-6">
						<div class="form-group ">
							<label class="control-label text-muted"> Valor Item </label>
							<?= $this->Form->text('valoritem', [ 'disabled', 'class' => 'form-control mascaramonetaria', 'label' => false]) ?>
						</div>
					</div>
					<div class="col-lg-3 col-md-12">
						<div class="form-group ">
							<label class="control-label text-muted"> Valor Total Item </label>
							<?= $this->Form->text('valortotal', ['disabled', 'class' => 'form-control mascaramonetaria', 'label' => false]) ?>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-3 col-md-12">
						<div class="form-group">
							<label class="control-label text-muted"> Data retorno </label>
							<?= $this->Form->text('dataretorno', ['class' => 'form-control datepicker ', 'id' => 'dataretorno', 'disabled', 'default' => date('d/m/Y'), 'placeholder' => 'Insira a data', 'required' => true]) ?>
						</div>
					</div>
					<div class="col-lg-3 col-md-12">
						<div class="form-group">
							<label class="control-label text-muted"> Adicional cobrado: </label>
							<?= $this->Form->text('adicionalcobrado', ['class' => 'form-control mascaramonetaria', 'disabled', 'id' => 'adicionalcobrado', 'label' => false]) ?>
						</div>
					</div>
					<div class="col-lg-3 col-md-12">
						<div class="form-group">
							<label class="control-label text-muted"> Necessita reparos </label>
							<?= $this->Form->control('reparos', ['class' => 'form-control', 'disabled', 'options' => [0 => 'Não', 1 => 'Sim'], 'label' => false]) ?>
						</div>
					</div>
					<div class="col-lg-3 col-md-12">
						<div class="form-group">
							<label class="control-label text-muted"> Acessórios devolvidos </label>
							<?= $this->Form->control('acessorios', ['class' => 'form-control', 'disabled', 'options' => [0 => 'Não', 1 => 'Sim'], 'label' => false]) ?>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-12">
						<div class="form-group">
							<label class="control-label text-muted"> Observações </label>
							<?= $this->Form->textarea('obs', ['class' => 'form-control', 'disabled', 'label' => false]) ?>
						</div>
					</div>
				</div>
				<?php if($item->qtddevolvida < $item->quantidade) { ?>
					<?= $this->Html->link('Devolver', ['action' => 'devolveritem', $item->id], ['class' => 'btn btn-queequaseinfo float-right m-r-5 m-l-5']) ?>
				<?php } ?>
				<button type="button" class="btn btn-danger waves-effect float-right" data-dismiss="modal"> Fechar </button>
			<?= $this->Form->end(); ?>
		</div>
	</div>
</div>