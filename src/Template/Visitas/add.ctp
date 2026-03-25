<?php 
	$this->Breadcrumbs->add('Visitas', ['controller' => 'Agenda', 'action' => 'index'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Adicionar', [], ['class' => 'breadcrumb-item active']);

	$clientes1 = "''";
	foreach($clientes as $key=>$reg){
		$clientes1 = $clientes1 . ",  '". $reg . "'";
	}

	?>
<style>
	select[multiple]{
		width: 400px;
	}
</style>

<div class="col-md-12">
	<div class="card" >
		<div class="card-body">
            <?= $this->Form->create($visita, ['class' => 'form-material']) ?>
            <div class="row">
                <div class="col-12">
					<label class="control-label text-muted">Cliente</label>
					<?= $this->Form->control('idcliente', ['title' => 'Selecione o cliente que irá visitar', 'data-live-search' => true, 'class' => 'form-control selectpicker', 'id' => 'idcliente', 'options' => $clientes, 'label' => false, 'required' => true]) ?>
                </div>
            </div>
			<br>
            <div class="row">
				<div class="col-lg-4 col-md-12">
					<div class="form-group">
						<label class="control-label text-muted">Data</label>
						<?= $this->Form->text('data', ['class' => 'form-control datepicker ', 'id' => 'data', 'default' => date('d/m/Y'), 'placeholder' => 'Insira a data', 'required' => true, 'data-mask' => '99/99/9999']) ?>
					</div>
				</div>
				<div class="col-lg-2 col-md-6">
					<div class="form-group">
						<label class="control-label text-muted">Hora Inicial</label>
						<?= $this->Form->text('horaini', ['autocomplete' => "off", 'class' => 'form-control hora clockpicker', 'label' => false, 'required' => true, 'placeholder' =>'Insira a hora inicial'])?>
					</div>
				</div>
				<div class="col-lg-2 col-md-6">
					<div class="form-group">
						<label class="control-label text-muted">Hora Final</label>
						<?= $this->Form->text('horafim', ['autocomplete' => "off", 'class' => 'form-control hora clockpicker', 'label' => false, 'required' => true, 'placeholder' =>'Insira a hora final'])?>
					</div>
				</div>
				<div class="col-lg-4 col-md-12">
					<div class="form-group">
						<label class="control-label text-muted">Situação</label>
						<?= $this->Form->control('situacao', ['class' => 'form-control', 'options' => C_VisitasSituacaoQuery, 'default' => 0, 'label' => false]) ?>
					</div>
				</div>
			</div>
            <div class="row">
                <div class="col-12">
                    <label class="control-label text-muted">Lista de membros</label>
                    <?= $this->Form->control('users._ids', ['options' => $users, 'label' => false, 'multiple', 'required' => true, 'class' => 'selectpicker']) ?>
                </div>
            </div>
            <br>
			<div class="row">
				<div class="col-3">
                    <label class="control-label text-muted">Valor R$</label>
					<?= $this->Form->text('valor', ['class' => 'form-control mascaramonetaria', 'label' => false,'placeholder' =>'Insira o valor'])?>
				</div>
			</div>
			<br>
            <div class="row">
                <div class="col-lg-12">
                    <div class="form-group">
                        <label class="control-label text-muted">Motivo</label>
                        <?= $this->Form->textarea('motivo', ['class' => 'form-control', 'label' => false, 'required' => true, 'placeholder' =>'Insira o motivo']) ?>
                    </div>
                </div>
            </div>
			<div class="row">
				<div class="col-lg-12">
					<?= $this->Form->button('Cadastrar visita', ['class' => 'btn btn-pgm btn-pgm-salvar btn-success']) ?>
				</div>
			</div>
			<div class="clearfix"></div>
			<?= $this->Form->end(); ?>
		</div>
	</div>
</div>
<script>
	$('.datepicker').bootstrapMaterialDatePicker({ format : 'DD/MM/YYYY', lang : 'pt-br', time : false, switchOnClick : true, nowButton : true, cancelText : 'Cancelar' , 'setDate' : 'currentDate', nowText : 'Hoje'});

	$('.clockpicker').clockpicker({
		donetext: 'Confirmar',
	})

	$('.hora').mask('99:99');
</script>