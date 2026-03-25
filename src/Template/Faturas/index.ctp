<?php use Cake\Routing\Router; ?>
<style>
	.table td, .table th { padding: 0.2rem;	}
</style>
<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<?php if($role == 0) echo $this->Html->link('Nova locação', ['controller'=> 'locacao', 'action' => 'add'], ['class' => 'btn btn-pgm btn-pgm-salvar btn-success m-t-5 m-r-5 m-b-20', 'target' => '_blank']) ?>
			<?= $this->Form->create('Fatura', ['type' => 'get', 'class' => 'form-material']); ?>
				<div class="row">
					<?php if($role == 0){ ?>
						<div class="col-md-1 col-xs-12 m-t-10 p-r-0 float-left">
							<p style='font-weight: 500;'> Cliente: </p>
						</div>
						<div class="col-md-5 col-xs-12 p-l-0 float-right">
							<?= $this->Form->control('cliente', ['data-live-search' => true, 'title' => 'Todos', 'data-live-search' => true, 'value' => strtoupper($cliente), 'class' => 'form-control selectpicker', 'id' => 'cliente', 'options' => $clientes, 'label' => false]) ?>
						</div>
					<?php } ?>
				</div>
			<?= $this->Form->end(); ?>
			<ul class="nav nav-tabs customtab" role="tablist">
				<li class="nav-item"> <a class="nav-link active" data-toggle="tab" href="#pendentes" role="tab" aria-selected="true"><span class="hidden-sm-up"></span> <span class="hidden-xs-down"> Pendentes </span></a> </li>
				<li class="nav-item"> <a class="nav-link " data-toggle="tab" href="#aprovados" role="tab" aria-selected="false"><span class="hidden-sm-up"></span> <span class="hidden-xs-down"> Aprovados </span></a> </li>
				<li class="nav-item"> <a class="nav-link " data-toggle="tab" href="#rejeitadas" role="tab" aria-selected="false"><span class="hidden-sm-up"></span> <span class="hidden-xs-down"> Rejeitados </span></a> </li>
				<li class="nav-item"> <a class="nav-link " data-toggle="tab" href="#finalizadas" role="tab" aria-selected="false"><span class="hidden-sm-up"></span> <span class="hidden-xs-down"> Finalizadas </span></a> </li>
			</ul>
			<div class="tab-content">
				<div class="tab-pane active" id="pendentes">
					<div class="table-responsive">	
						<table class="table table-hover table-row-clickable" id="tablePendentes">
							<thead class="text-primary">
								<th> Número </th>
								<th> Cliente </th>
								<th> Abertura </th>
								<th> Vencimento </th>
								<th> Vl. Total </th>
							</thead>
							<tbody>
								<?php $action = $role == 0 ? "edit" : "view"; foreach ($faturasPendentes as $reg): ?>
									<tr>
										<td> <a class="link" target='_blank' href='<?= $this->Url->build(["action" => $action, $reg->id]) ?>'><?= $reg->nro ?></a> </td>
										<td> <a class="link" target='_blank' href='<?= $this->Url->build(["action" => $action, $reg->id]) ?>'><?= $reg->cliente->tipo == C_ClientesTipoFisica ? $reg->cliente->nome : $reg->cliente->razaosocial ?></a> </td>
										<td data-order="<?= date_format($reg->created, 'Ymd') ?>"><a class="link" target='_blank' href='<?= $this->Url->build(["action" => $action, $reg->id]) ?>'><?= date_format($reg->created, 'd/m/Y') ?></a> </td>
										<td data-order="<?= date_format($reg->vencimento, 'Ymd') ?>"><a class="link" target='_blank' href='<?= $this->Url->build(["action" => $action, $reg->id]) ?>'><?= date_format($reg->vencimento, 'd/m/Y') ?></a> </td>
										<td> <a class="link" target='_blank' href='<?= $this->Url->build(["action" => $action, $reg->id]) ?>'><?= number_format($reg->valor, 2, ',', '.') ?></a> </td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
				<div class="tab-pane" id="aprovados">
					<div class="table-responsive">	
						<table class="table table-hover table-row-clickable" id="tableAprovadas">
							<thead class="text-primary">
								<th> Número </th>
								<th> Cliente </th>
								<th> Abertura </th>
								<th> Vencimento </th>
								<th> Vl. Total </th>
							</thead>
							<tbody>
								<?php $action = $role == 0 ? "edit" : "view"; foreach ($faturasAprovadas as $reg): ?>
									<tr>
										<td> <a class="link" target='_blank' href='<?= $this->Url->build(["action" => $action, $reg->id]) ?>'><?= $reg->nro ?></a> </td>
										<td> <a class="link" target='_blank' href='<?= $this->Url->build(["action" => $action, $reg->id]) ?>'><?= $reg->cliente->tipo == C_ClientesTipoFisica ? $reg->cliente->nome : $reg->cliente->razaosocial ?></a> </td>
										<td data-order="<?= date_format($reg->created, 'Ymd') ?>"><a class="link" target='_blank' href='<?= $this->Url->build(["action" => $action, $reg->id]) ?>'><?= date_format($reg->created, 'd/m/Y') ?></a> </td>
										<td data-order="<?= date_format($reg->vencimento, 'Ymd') ?>"><a class="link" target='_blank' href='<?= $this->Url->build(["action" => $action, $reg->id]) ?>'><?= date_format($reg->vencimento, 'd/m/Y') ?></a> </td>
										<td> <a class="link" target='_blank' href='<?= $this->Url->build(["action" => $action, $reg->id]) ?>'><?= number_format($reg->valor, 2, ',', '.') ?></a> </td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
				<div class="tab-pane" id="rejeitadas">
					<div class="table-responsive">	
						<table class="table table-hover table-row-clickable" id="tableRejeitadas">
							<thead class="text-primary">
								<th> Número </th>
								<th> Cliente </th>
								<th> Abertura </th>
								<th> Vencimento </th>
								<th> Vl. Total </th>
							</thead>
							<tbody>
								<?php $action = $role == 0 ? "edit" : "view"; foreach ($faturasRejeitadas as $reg): ?>
									<tr>
										<td> <a class="link" target='_blank' href='<?= $this->Url->build(["action" => $action, $reg->id]) ?>'><?= $reg->nro ?></a> </td>
										<td> <a class="link" target='_blank' href='<?= $this->Url->build(["action" => $action, $reg->id]) ?>'><?= $reg->cliente->tipo == C_ClientesTipoFisica ? $reg->cliente->nome : $reg->cliente->razaosocial ?></a> </td>
										<td data-order="<?= date_format($reg->created, 'Ymd') ?>"><a class="link" target='_blank' href='<?= $this->Url->build(["action" => $action, $reg->id]) ?>'><?= date_format($reg->created, 'd/m/Y') ?></a> </td>
										<td data-order="<?= date_format($reg->vencimento, 'Ymd') ?>"><a class="link" target='_blank' href='<?= $this->Url->build(["action" => $action, $reg->id]) ?>'><?= date_format($reg->vencimento, 'd/m/Y') ?></a> </td>
										<td> <a class="link" target='_blank' href='<?= $this->Url->build(["action" => $action, $reg->id]) ?>'><?= number_format($reg->valor, 2, ',', '.') ?></a> </td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
				<div class="tab-pane" id="finalizadas">
					<div class="table-responsive">	
						<table class="table table-hover table-row-clickable" id="tableFinalizadas">
							<thead class="text-primary">
								<th> Número </th>
								<th> Cliente </th>
								<th> Abertura </th>
								<th> Vencimento </th>
								<th> Vl. Total </th>
							</thead>
							<tbody>
								<?php $action = $role == 0 ? "edit" : "view"; foreach ($faturasFinalizadas as $reg): ?>
									<tr>
										<td> <a class="link" target='_blank' href='<?= $this->Url->build(["action" => $action, $reg->id]) ?>'><?= $reg->nro ?></a> </td>
										<td> <a class="link" target='_blank' href='<?= $this->Url->build(["action" => $action, $reg->id]) ?>'><?= $reg->cliente->tipo == C_ClientesTipoFisica ? $reg->cliente->nome : $reg->cliente->razaosocial ?></a> </td>
										<td data-order="<?= date_format($reg->created, 'Ymd') ?>"><a class="link" target='_blank' href='<?= $this->Url->build(["action" => $action, $reg->id]) ?>'><?= date_format($reg->created, 'd/m/Y') ?></a> </td>
										<td data-order="<?= date_format($reg->vencimento, 'Ymd') ?>"><a class="link" target='_blank' href='<?= $this->Url->build(["action" => $action, $reg->id]) ?>'><?= date_format($reg->vencimento, 'd/m/Y') ?></a> </td>
										<td> <a class="link" target='_blank' href='<?= $this->Url->build(["action" => $action, $reg->id]) ?>'><?= number_format($reg->valor, 2, ',', '.') ?></a> </td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
	$(document).ready(function() {
		$('#cliente').on('change', function() { this.form.submit();	});

		var $window = $(window);
		table = $('#tableFinalizadas, #tableRejeitadas, #tableAprovadas, #tablePendentes');
		table.on( 'length.dt', function ( e, settings, len ) { pagelength(len);	});
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
	});

	window.onload = function() { $('[type="search"]').focus();	}
</script>
