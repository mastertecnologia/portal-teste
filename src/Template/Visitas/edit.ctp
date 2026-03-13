<?php 
	use Cake\Routing\Router;
	$this->Breadcrumbs->add('Agenda', ['controller' => 'Agenda', 'action' => 'index'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Editar', [], ['class' => 'breadcrumb-item active']);
	error_reporting(E_ERROR | E_PARSE);
?>

<div class="col-md-12">
	<div class="card" >
		<div class="card-body visita">
        <?= $this->Form->create($visita, ['class' => 'form-material']) ?>
        	<div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
					<label class="control-label text-muted">Cliente</label>
					<?= $this->Form->control('idcliente', ['data-live-search' => true, 'class' => 'form-control selectpicker', 'id' => 'idcliente', 'options' => $clientes, 'label' => false]) ?>
                </div>
            </div>
			<br>
            <div class="row">
				<div class="col-lg-4 col-md-4 col-sm-4 col-xs-4">
					<div class="form-group">
						<label class="control-label text-muted">Data</label>
						<?= $this->Form->text('data', ['class' => 'form-control datepicker ', 'id' => 'data', 'default' => date('d/m/Y'), 'placeholder' => 'Insira a data', 'required' => true]) ?>
					</div>
				</div>
				<div class="col-lg-2 col-md-2 col-sm-2 col-xs-2">
					<div class="form-group">
						<label class="control-label text-muted">Início</label>
						<?= $this->Form->text('horaini', ['autocomplete' => "off", 'class' => 'form-control hora clockpicker', 'label' => false, 'required' => true])?>
					</div>
				</div>
				<div class="col-lg-2 col-md-2 col-sm-2 col-xs-2">
					<div class="form-group">
						<label class="control-label text-muted">Fim</label>
						<?= $this->Form->text('horafim', ['autocomplete' => "off", 'class' => 'form-control hora clockpicker', 'label' => false, 'required' => true])?>
					</div>
				</div>
				<div class="col-lg-4 col-md-4 col-sm-4 col-xs-4">
					<div class="form-group">
						<label class="control-label text-muted">Situação</label>
						<?= $this->Form->control('situacao', ['class' => 'form-control', 'options' => C_VisitasSituacaoQuery, 'label' => false]) ?>
					</div>
				</div>
			</div>
			<div class="row">
                <div class="col-lg-12">
                    <label class="control-label text-muted">Lista de membros</label>
                    <?= $this->Form->control('users._ids', ['options' => $users, 'label' => false, 'multiple', 'required' => true, 'class' => 'selectpicker']) ?>
                </div>
            </div>
            <br>
			<div class="row">
				<div class="col-3">
                    <label class="control-label text-muted">Valor (R$)</label>
					<?= $this->Form->text('valor', ['class' => 'form-control', 'label' => false, 'placeholder' =>'Insira o valor'])?>
				</div>
			</div>
            <div class="row">
                <div class="col-lg-12">
				<br>
                    <div class="form-group ">
                        <label class="control-label text-muted">Motivo</label>
                        <?= $this->Form->textarea('motivo', ['class' => 'form-control', 'label' => false, 'required' => true, 'placeholder' =>'Insira o motivo']) ?>
                    </div>
                </div>
            </div>
                
			<div class="row">
				<div class="col-lg-12">
					<?= $this->Form->button('Salvar visita', ['class' => 'btn btn-success']) ?>
					<?php if($admin) echo $this->Html->link('Deletar visita', ['controller' => 'Agenda', 'action' => 'delete', $visita->id], ['class' => 'btn btn-danger']) ?>
				</div>
			</div>
			<?= $this->Form->end(); ?>
		</div>
	</div>
</div>
<script>
	$('.datepicker').bootstrapMaterialDatePicker({ format : 'DD/MM/YYYY', lang : 'pt-br', time : false, switchOnClick : true, nowButton : true, cancelText : 'Cancelar' , 'setDate' : 'currentDate', nowText : 'Hoje', 'disabled': false });
	$('.clockpicker').clockpicker({ donetext: 'Confirmar' })
	$('.hora').mask('99:99');
</script>