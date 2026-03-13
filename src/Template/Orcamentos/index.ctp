<style>
	.table td, .table th { padding: 0.4rem;	}
</style>
<div class="col-md-12">
	<?php if($role == 0){ ?>
		<div class="card-group">
			<!-- Pendentes -->
			<div class="card">
				<div class="card-body">
					<div class="row">
						<div class="col-md-12">
							<div class="d-flex no-block align-items-center">
								<div>
									<span style="color: #fb8c00;" ><h3><i class="ti-alert"></i></h3></span>
									<!-- <p class="text-muted">PENDENTES</p> -->
									<p class="text-muted">EM ANDAMENTO</p>
								</div>
								<div class="ml-auto">
									<h2 class="counter text-warning"><?= count($orcamentosPendentes) ?></h2>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<!-- Enviados -->
			<div class="card">
				<div class="card-body">
					<div class="row">
						<div class="col-md-12">
							<div class="d-flex no-block align-items-center">
								<div>
									<span style="color: rgba(3, 169, 244, 0.7)"><h3><i class="ti-email"></i></h3></span>
									<p class="text-muted">ENVIADOS</p>
								</div>
								<div class="ml-auto">
									<h2 class="counter text-primary"><?= count($orcamentosEnviados) ?></h2>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<!-- Aprovados -->
			<div class="card">
				<div class="card-body">
					<div class="row">
						<div class="col-md-12">
							<div class="d-flex no-block align-items-center">
								<div>
									<span class="display-5" style="color: rgba(0, 150, 136, 0.7)"><h3><i class="ti-unlock"></i></h3></span>
									<p class="text-muted">APROVADOS</p>
								</div>
								<div class="ml-auto">
									<h2 class="counter text-success"><?= count($orcamentosAprovados) ?></h2>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<!-- Recusados-->
			<div class="card">
				<div class="card-body">
					<div class="row">
						<div class="col-md-12">
							<div class="d-flex no-block align-items-center">
								<div>
									<span style="color: rgba(228, 106, 118, 0.7) ;" ><h3><i class="ti-lock"></i></h3></span>
									<p class="text-muted">RECUSADOS</p>
								</div>
								<div class="ml-auto">
									<h2 class="counter text-danger"><?= count($orcamentosRecusados) ?></h2>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<!-- Arquivados -->
			<div class="card">
				<div class="card-body">
					<div class="row">
						<div class="col-md-12">
							<div class="d-flex no-block align-items-center">
								<div>
									<span><h3><i class="ti-harddrives"></i></h3></span>
									<p class="text-muted">ARQUIVADOS</p>
								</div>
								<div class="ml-auto">
									<h2 class="counter"><?= count($orcamentosArquivados) ?></h2>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	<?php } ?>
	<div class="card">
		<div class="card-body">
			<?php if($role == 0){ ?>
				<?= $this->Html->link('Gerar Orçamento', ['action' => 'add'], ['class' => 'm-b-10 btn btn-success', 'escape' => false, 'target' => '_blank']) ?>
				<ul class="nav nav-tabs customtab" role="tablist">
					<li class="nav-item"> <a class="nav-link active" data-toggle="tab" href="#pendentes" role="tab" aria-selected="true"><span class="hidden-sm-up"></i></span> <span class="hidden-xs-down">Pendentes</span></a> </li>
					<li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#enviados" role="tab" aria-selected="false"><span class="hidden-sm-up">       </i></span> <span class="hidden-xs-down">Enviados</span></a> </li>
					<li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#aprovados" role="tab" aria-selected="false"><span class="hidden-sm-up">      </i></span> <span class="hidden-xs-down">Aprovados</span></a> </li>
					<li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#recusados" role="tab" aria-selected="false"><span class="hidden-sm-up">		</i></span> <span class="hidden-xs-down">Recusados</span></a> </li>
					<li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#arquivados" role="tab" aria-selected="false"><span class="hidden-sm-up">		</i></span> <span class="hidden-xs-down">Arquivados</span></a> </li>
				</ul>
			<?php } ?>
			<div class="tab-content">
				<?php if($role != 0){ ?>
					<div class="tab-pane active" id="cliente">
						<?= $this->Html->link('Solicitar Orçamento', ["controller" => "Tickets", "action" => "add", 4], ['class' => 'btn btn-success', 'escape' => false]) ?>
						<div class="table-responsive">
							<table class="table table-hover table-row-clickable" id="tableCliente">
								<thead class="text-primary">
									<th>ID</th>
									<th>Autor</th>
									<th>Status</th>
									<th>Data</th>
								</thead>
								<tbody>
									<?php foreach ($orcamentosCliente as $reg): ?>
										<tr>
											<td><a class='link' target='_blank' href='<?= $this->Url->build(["action" => "view", $reg->id]) ?>'> <?= $reg->id ?></a></td>
											<td><a class='link' target='_blank' href='<?= $this->Url->build(["action" => "view", $reg->id]) ?>'> <?= $reg->user->name ?></a></td>
											<td><a class='link' target='_blank' href='<?= $this->Url->build(["action" => "view", $reg->id]) ?>'> <?= orcamentoStatus($reg->status) ?></a></td>
											<td><a class='link' target='_blank' href='<?= $this->Url->build(["action" => "view", $reg->id]) ?>'> <?= @date_format($reg->created, 'd/m/Y') ?></a></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>
				<?php }else{ ?>
					<div class="tab-pane active" id="pendentes">
						<div class="table-responsive">
							<table class="table table-hover table-row-clickable" id="tablePendentes">
								<thead class="text-primary">
									<th>ID</th>
									<th>Empresa</th>
									<th>Data</th>
								</thead>
								<tbody>
									<?php foreach ($orcamentosPendentes as $reg): ?>
										<tr>
											<td><a class='link' target='_blank' href='<?= $this->Url->build(["action" => "edit", $reg->id]) ?>'> <?= $reg->id ?> </a> </td>
											<td><a class='link' target='_blank' href='<?= $this->Url->build(["action" => "edit", $reg->id]) ?>'> <?php if(!empty($reg->cliente->razaosocial)) echo $reg->cliente->razaosocial; else echo $reg->cliente->nome ?> </a> </td>
											<td><a class='link' target='_blank' href='<?= $this->Url->build(["action" => "edit", $reg->id]) ?>'> <?= @date_format($reg->created, 'd/m/Y')  ?> </a> </td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>
					<div class="tab-pane" id="enviados">
						<div class="table-responsive">
							<table class="table table-hover table-row-clickable" id="tableEnviados">
								<thead class="text-primary">
									<th>ID</th>
									<th>Empresa</th>
									<th>Data</th>
								</thead>
								<tbody>
									<?php foreach ($orcamentosEnviados as $reg): ?>
										<tr>
											<td><a class='link' target='_blank' href='<?= $this->Url->build(["action" => "edit", $reg->id]) ?>'><?= $reg->numero ?><?= $reg->id ?></a></td>
											<td><a class='link' target='_blank' href='<?= $this->Url->build(["action" => "edit", $reg->id]) ?>'> <?php if(!empty($reg->cliente->razaosocial)) echo $reg->cliente->razaosocial; else echo $reg->cliente->nome ?>				</a>	</td>
											<td><a class='link' target='_blank' href='<?= $this->Url->build(["action" => "edit", $reg->id]) ?>'><?= $reg->numero ?><?= @date_format($reg->created, 'd/m/Y')  ?></a></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>
					<div class="tab-pane" id="aprovados">
						<div class="table-responsive">
							<table class="table table-hover table-row-clickable" id="tableAprovados">
								<thead class="text-primary">
									<th>ID</th>	
									<th>Empresa</th>
									<th>Data</th>
								</thead>
								<tbody>
									<?php foreach ($orcamentosAprovados as $reg): ?>
										<tr>
											<td><a class='link' target='_blank' href='<?= $this->Url->build(["action" => "edit", $reg->id]) ?>'><?= $reg->numero ?><?= $reg->id ?></a></td>
											<td><a class='link' target='_blank' href='<?= $this->Url->build(["action" => "edit", $reg->id]) ?>'> <?php if(!empty($reg->cliente->razaosocial)) echo $reg->cliente->razaosocial; else echo $reg->cliente->nome ?>				</a>	</td>
											<td><a class='link' target='_blank' href='<?= $this->Url->build(["action" => "edit", $reg->id]) ?>'><?= $reg->numero ?><?= @date_format($reg->created, 'd/m/Y')  ?></a></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>
					<div class="tab-pane" id="recusados">
						<div class="table-responsive">
							<table class="table table-hover table-row-clickable" id="tableRecusados">
								<thead class="text-primary">
									<th>ID</th>	
									<th>Empresa</th>
									<th>Data</th>
								</thead>
								<tbody>
									<?php foreach ($orcamentosRecusados as $reg): ?>
										<tr>
											<td><a class='link' target='_blank' href='<?= $this->Url->build(["action" => "edit", $reg->id]) ?>'><?= $reg->numero ?><?= $reg->id ?></a></td>
											<td><a class='link' target='_blank' href='<?= $this->Url->build(["action" => "edit", $reg->id]) ?>'> <?php if(!empty($reg->cliente->razaosocial)) echo $reg->cliente->razaosocial; else echo $reg->cliente->nome ?>				</a>	</td>
											<td><a class='link' target='_blank' href='<?= $this->Url->build(["action" => "edit", $reg->id]) ?>'><?= $reg->numero ?><?= @date_format($reg->created, 'd/m/Y')  ?></a></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>
					<div class="tab-pane" id="arquivados">
						<div class="table-responsive">
							<table class="table table-hover table-row-clickable" id="tableArquivados">
								<thead class="text-primary">
									<th>ID</th>	
									<th>Empresa</th>
									<th>Data</th>
								</thead>
								<tbody>
									<?php foreach ($orcamentosArquivados as $reg): ?>
										<tr>
											<td><a class='link' target='_blank' href='<?= $this->Url->build(["action" => "edit", $reg->id]) ?>'><?= $reg->numero ?><?= $reg->id ?></a></td>
											<td><a class='link' target='_blank' href='<?= $this->Url->build(["action" => "edit", $reg->id]) ?>'> <?php if(!empty($reg->cliente->razaosocial)) echo $reg->cliente->razaosocial; else echo $reg->cliente->nome ?>				</a>	</td>
											<td><a class='link' target='_blank' href='<?= $this->Url->build(["action" => "edit", $reg->id]) ?>'><?= $reg->numero ?><?= @date_format($reg->created, 'd/m/Y')  ?></a></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>
					
				<?php } ?>
			</div>
		</div>
	</div>
</div>

<script>
	$(document).ready(function() {
		var $window = $(window);

		table = $('#tableCliente, #tablePendentes, #tableEnviados, #tableAprovados, #tableRecusados, #tableArquivados');
		table.on( 'length.dt', function ( e, settings, len ) {
			pagelength(len);
		} )
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
				}
			},
			"drawCallback": function( settings ) {
				if ($('body').hasClass('dark-mode') ) $('td').each(function(){$(this).addClass('dark-mode');});
				else $('td').each(function(){$(this).removeClass('dark-mode');});
			},
		});
		table.search(filters).draw();
	});

	window.onload = function() {
		$('#admins [type="search"]').focus();
	}
</script>
