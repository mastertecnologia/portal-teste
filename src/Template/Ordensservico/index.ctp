<?php use Cake\Routing\Router; ?>
<style>
	.table td, .table th { padding: 0.2rem;	}
</style>
<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<?php if($role == 0) echo $this->Html->link('Abrir ordem de serviço', ['action' => 'add'], ['class' => 'btn btn-success m-t-5 m-r-5 m-b-20', 'target' => '_blank']) ?>
			<?php if($role == 0) echo $this->Html->link('Alterar situação', ['#'], ['class' => 'btn btn-info btn-acao m-t-5 m-r-5 m-b-5 float-right hide']); ?>
			<?php if($role == 0) echo $this->Html->link('Imprimir', ['#'], ['class' => 'btn-imprimir btn btn-purple m-t-5 m-r-5 m-b-5 float-right hide', 'target' => '_blank']); ?>
			<?= $this->Form->create(null, ['id' => 'formimprimir', 'url' => ['controller' => 'Ordensservico', 'action' => 'imprimirordens'], 'target' => '_blank']); ?>
			<?= $this->Form->control('idsimprimir', ['type' => 'hidden', 'label' => false]);?>
			<?= $this->Form->end() ?>
			<?= $this->Form->create('Ordem', ['type' => 'get', 'class' => 'form-material']); ?>
				<div class="row">
					<?php if($role==0){ ?>
						<div class="col-md-1 col-xs-12 m-t-10 p-r-0 float-left">
							<p style='font-weight: 500;'> Situação: </p>
						</div>
						<div class="col-md-2 col-xs-12 p-l-0 float-right">
							<?= $this->Form->control('situacao', ['data-live-search' => true, 'title' => 'Todas', 'value' => $situacao, 'id' => 'situacao', 'class' => 'form-control selectpicker', 'options' => C_OrdensSituacao, 'label' => false]) ?>
						</div>
						<div class="col-md-1 col-xs-12 m-t-10 p-r-0 float-left">
							<p style='font-weight: 500;'> Problema: </p>
						</div>
						<div class="col-md-2 col-xs-12 p-l-0 float-right">
							<?= $this->Form->control('problema', ['data-live-search' => true, 'title' => 'Todos', 'value' => $problema, 'id' => 'problema', 'class' => 'form-control selectpicker', 'options' => $problemas, 'label' => false]) ?>
						</div>
						<div class="col-md-1 col-xs-12 m-t-10 p-r-0 float-left">
							<p style='font-weight: 500;'> Cliente: </p>
						</div>
						<div class="col-md-5 col-xs-12 p-l-0 float-right">
							<?= $this->Form->control('cliente', ['data-live-search' => true, 'title' => 'Todos', 'data-live-search' => true, 'value' => strtoupper($cliente), 'class' => 'form-control selectpicker', 'id' => 'cliente', 'options' => $clientes, 'label' => false]) ?>
						</div>
						<div class="col-md-1 col-xs-12 m-t-10 p-r-0 float-left">
							<p style='font-weight: 500;'> Tipo: </p>
						</div>
						<div class="col-md-2 col-xs-12 p-l-0 float-right">
							<?= $this->Form->control('locacao', ['data-live-search' => true, 'title' => 'Todos', 'value' => $locacao, 'id' => 'locacao', 'class' => 'form-control selectpicker', 'options' => C_OrdensLocacao, 'label' => false]) ?>
						</div>
					<?php } else { ?>
						<div class="col-md-1 col-xs-12 m-t-10 p-r-0 float-left">
							<p style='font-weight: 500;'> Situação: </p>
						</div>
						<div class="col-md-3 col-xs-12 p-l-0 float-right">
							<?= $this->Form->control('situacao', ['data-live-search' => true, 'title' => 'Todas', 'value' => $situacao, 'id' => 'situacao', 'class' => 'form-control selectpicker', 'options' => C_OrdensSituacao, 'label' => false]) ?>
						</div>
					<?php } ?>
				</div>
			<?= $this->Form->end(); ?>
			<div class="table-responsive">	
				<table class="table table-hover table-row-clickable" id="tableOrdens">
					<thead class="text-primary">
						<th> Número </th>
						<th> Abertura </th>
						<th> Previsão </th>
						<th> Cliente </th>
						<th> Contrato </th>
						<th> Técnico </th>
						<th> Vl. Total </th>
						<th> Situação </th>
						<th><i class="fa fa-check-square"></i></th>
					</thead>
					<tbody>
						<?php $action = $role == 0 ? "edit" : "view"; foreach ($ordens as $reg): ?>
							<tr <?= $reg->locacao ? 'style="background-color:#fabee8;"' : '' ?> >
								<td> <a class="link" target='_blank' href='<?= $this->Url->build(["action" => $action, $reg->id]) ?>'><?= $reg->id ?></a> </td>
								<td data-order="<?= date_format($reg->dataabertura, 'Ymd') ?>"><a class="link" target='_blank' href='<?= $this->Url->build(["action" => $action, $reg->id]) ?>'><?= date_format($reg->dataabertura, 'd/m/Y') ?></a> </td>
								<td data-order="<?= date_format($reg->dataprevisao, 'Ymd') ?>"><a class="link" target='_blank' href='<?= $this->Url->build(["action" => $action, $reg->id]) ?>'><?= date_format($reg->dataprevisao, 'd/m/Y') ?></a> </td>
								<td> <a class="link" target='_blank' href='<?= $this->Url->build(["action" => $action, $reg->id]) ?>'><?= $reg->cliente->tipo == C_ClientesTipoFisica ? $reg->cliente->nome : $reg->cliente->razaosocial ?></a> </td>
								<td> <a class="link" target='_blank' href='<?= $this->Url->build(["action" => $action, $reg->id]) ?>'><?= OrdensContrato($reg->contrato) ?></a> </td>
								<td> <a class="link" target='_blank' href='<?= $this->Url->build(["action" => $action, $reg->id]) ?>'><?= $reg->user->name ?></a> </td>
								<td> <a class="link" target='_blank' href='<?= $this->Url->build(["action" => $action, $reg->id]) ?>'><?= number_format($reg->valortotal, 2, ',', '.') ?></a> </td>
								<td> <a class="link" target='_blank' href='<?= $this->Url->build(["action" => $action, $reg->id]) ?>'><?= SituacaoOrdem($reg->situacao) ?></a> </td>
								<td>
									<div class="custom-control custom-checkbox mr-sm-2 mb-3">
										<input data-id='<?= $reg->id ?>' type="checkbox" class="custom-control-input checkbox"  id="checkbox<?= $reg->id ?>" value="check">
										<label class="custom-control-label" for="checkbox<?= $reg->id ?>"></label>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
<!-- Modal Ação -->
<div class="modal fade none-border" id="modal-acao">
	<div class="modal-dialog modal-lg">
		<div class="modal-content  m-20">
			<?= $this->Form->create(null, ['class' => 'form-material', 'url' => ['controller' => 'Ordensservico', 'action' => 'acaoindex']]); ?>
				<div class="row m-20">
					<?= $this->Form->control('ids', ['type' => 'hidden', 'label' => false]);?>
					<div class="col-md-4 col-xs-12 ">
						<label class="control-label">Situação</label>
							<?= $this->Form->control('situacao', ['value' => C_OrdensSituacaoLiberadaParaFaturamento, 'id' => 'situacaomodal', 'class' => 'form-control', 'options' => C_OrdensSituacaoOpcoes, 'label' => false, 'required' => true]) ?>
					</div>
					<div class="col-md-4 col-xs-12 liberada">
						<label class="control-label">Pagamento</label>
						<?= $this->Form->control('pagamento', ['options' => C_OrdensPagamento, 'class' => 'form-control', 'label' => false]) ?>
					</div>
					<div class="col-md-4 col-xs-12 liberada">
						<div class="custom-control custom-checkbox mr-sm-2 m-r-10 m-l-10 m-t-25">
							<?= $this->Form->checkbox('entrada', ['class' => 'custom-control-input', 'id' => 'entrada']); ?>
							<label class="custom-control-label text-muted" for="entrada">Primeira parcela recebida como Entrada</label>
						</div>
					</div>
					<div class="col-md-2  col-xs-12 liberada">
						<label class="control-label">Parcelas</label>
						<?= $this->Form->control('nmrparcelas', ['id' => 'nmrparcelas', 'options' => C_OrdensParcelas, 'class' => 'form-control', 'label' => false]) ?>
					</div>
					<div class="col-md-2  col-xs-12 liberada">
						<label class="control-label text-muted dataval1">Parcela 1 </label>
						<?= $this->Form->text('dataval1', ['id' => 'dataval1', 'default' => date('d/m/Y'), 'class' => 'form-control datepicker dataval1', 'label' => false]) ?>
					</div>
					<div class="col-md-2  col-xs-12 liberada">
						<label class="control-label text-muted dataval2">Parcela 2 </label>
						<?= $this->Form->text('dataval2', ['id' => 'dataval2', 'default' => date('d/m/Y'), 'class' => 'form-control datepicker dataval2', 'label' => false]) ?>
					</div>
					<div class="col-md-2  col-xs-12 liberada">
						<label class="control-label text-muted dataval3">Parcela 3 </label>
						<?= $this->Form->text('dataval3', ['id' => 'dataval3', 'default' => date('d/m/Y'), 'class' => 'form-control datepicker dataval3', 'label' => false]) ?>
					</div>
					<div class="col-md-2  col-xs-12 liberada">
						<label class="control-label text-muted dataval4">Parcela 4 </label>
						<?= $this->Form->text('dataval4', ['id' => 'dataval4', 'default' => date('d/m/Y'), 'class' => 'form-control datepicker dataval4', 'label' => false]) ?>
					</div>
					<div class="col-md-2  col-xs-12 liberada">
						<label class="control-label text-muted dataval5">Parcela 5 </label>
						<?= $this->Form->text('dataval5', ['id' => 'dataval5', 'default' => date('d/m/Y'), 'class' => 'form-control datepicker dataval5', 'label' => false]) ?>
					</div>
				</div>
				<div class="modal-footer">
					<?= $this->Form->button('Salvar', ['class' => 'btn btn-success text-white m-l-5']) ?>
					<button type="button" class="btn btn-danger waves-effect" data-dismiss="modal">Fechar</button>
				</div>
			<?= $this->Form->end() ?>
		</div>
	</div>
</div>
<script>
	$(document).ready(function() {
		$('#situacao, #cliente, #problema, #locacao').on('change', function() {
			this.form.submit();
		});
		var $window = $(window);
		table = $('#tableOrdens');
		table.on( 'length.dt', function ( e, settings, len ) {
			pagelength(len);
		});
		table.DataTable({
			"order": [[ 0, "desc" ]],
			"pageLength": <?= $pagelength ?>,
			"language": {
				"sProcessing":    "Procesando...",
				"sLengthMenu":    "Mostrar _MENU_ registros",
				"sZeroRecords":   "Nenhum registro encontrado",
				"sEmptyTable":    "Nenhum dado disponível",
				"sInfo":          "Mostrando registros de _START_ até _END_ de um total de _TOTAL_ registros",
				"sInfoEmpty":     "Mostrando registros de 0 a 0 de um total de 0 registros",
				"sInfoFiltered":  "(filtrado de um total de _MAX_ registros)",
				"sInfoPostFix":   "",
				"sSearch":        "Buscar:",
				"sUrl":           "",
				"sInfoThousands":  ",",
				"sLoadingRecords": "Carregando...",
				"oPaginate": {
					"sFirst":    "<<",
					"sLast":    ">>",
					"sNext":    ">",
					"sPrevious": "<"
				},
				"oAria": {
					"sSortAscending":  ": Ordem Ascendente",
					"sSortDescending": ": Ordem descendente"
				},
			}
		});
		table.search(filters).draw();
	});

	window.onload = function() {
		$('[type="search"]').focus();
	}

	window.ids = '';

	$(document).on('click', '.checkbox', function(e) {
		id = $(this).attr('data-id');
		if (!$( this ).is(':checked')){
			array = window.ids.split(',');
			if (array.indexOf(id) > -1) array.splice(array.indexOf(id), 1);
			window.ids = '';
			array.forEach(function(value){
				if(value != '') window.ids += value + ',';
			})
		} else window.ids += id + ',';
		if(window.ids != '') $('.btn-acao, .btn-imprimir').show();
		else $('.btn-acao, .btn-imprimir').hide();
		$('#ids').val(window.ids);
		$('#idsimprimir').val(window.ids);
	})

	$(document).on('click', '.btn-acao', function(e) {
		e.preventDefault()
		$('#modal-acao').modal('toggle');
	})

	$(document).on('click', '.btn-imprimir', function(e) {
		e.preventDefault()
		$('#formimprimir').submit();
	})

	$('.dataval2, .dataval3, .dataval4, .dataval5').hide();
	$("#situacaomodal").change(function() {
		if($(this).val() == <?= C_OrdensSituacaoLiberadaParaFaturamento ?>) $('.liberada').show();
		else $('.liberada').hide();
	});
	$("#nmrparcelas").change(function() {
		var nmrparcelas = $(this).val();
		switch (nmrparcelas) {
			case '1': $('.dataval2, .dataval3, .dataval4, .dataval5').hide(); break;
			case '2': $('.dataval3, .dataval4, .dataval5').hide(); 
					$('.dataval2').show(); break;	
			case '3': $('.dataval4, .dataval5').hide();
					$('.dataval2, .dataval3').show(); break;
			case '4': $('.dataval5').hide();
					$('.dataval2, .dataval3, .dataval4').show(); break;
			case '5': $('.dataval2, .dataval3, .dataval4, .dataval5').show(); break;
			default: break;
		}
	});
</script>
