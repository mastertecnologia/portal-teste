<?php $this->append('css', $this->Html->css('/dist/css/orcamentos-premium')); ?>
<div class="col-md-12 orc-premium-wrap">
	<?php if($role == 0){ ?>
		<div class="orc-premium-kpi-grid">
			<div class="orc-premium-kpi orc-premium-kpi--pending">
				<div class="d-flex align-items-center" style="gap:12px;min-width:0;">
					<div class="orc-premium-kpi-icon"><i class="ti-reload"></i></div>
					<div class="orc-premium-kpi-body">
						<p class="orc-premium-kpi-label">Em andamento</p>
						<p class="orc-premium-kpi-value"><?= count($orcamentosPendentes) ?></p>
					</div>
				</div>
			</div>
			<div class="orc-premium-kpi orc-premium-kpi--sent">
				<div class="d-flex align-items-center" style="gap:12px;min-width:0;">
					<div class="orc-premium-kpi-icon"><i class="ti-email"></i></div>
					<div class="orc-premium-kpi-body">
						<p class="orc-premium-kpi-label">Enviados</p>
						<p class="orc-premium-kpi-value"><?= count($orcamentosEnviados) ?></p>
					</div>
				</div>
			</div>
			<div class="orc-premium-kpi orc-premium-kpi--approved">
				<div class="d-flex align-items-center" style="gap:12px;min-width:0;">
					<div class="orc-premium-kpi-icon"><i class="ti-unlock"></i></div>
					<div class="orc-premium-kpi-body">
						<p class="orc-premium-kpi-label">Aprovados</p>
						<p class="orc-premium-kpi-value"><?= count($orcamentosAprovados) ?></p>
					</div>
				</div>
			</div>
			<div class="orc-premium-kpi orc-premium-kpi--rejected">
				<div class="d-flex align-items-center" style="gap:12px;min-width:0;">
					<div class="orc-premium-kpi-icon"><i class="ti-lock"></i></div>
					<div class="orc-premium-kpi-body">
						<p class="orc-premium-kpi-label">Recusados</p>
						<p class="orc-premium-kpi-value"><?= count($orcamentosRecusados) ?></p>
					</div>
				</div>
			</div>
			<div class="orc-premium-kpi orc-premium-kpi--archived">
				<div class="d-flex align-items-center" style="gap:12px;min-width:0;">
					<div class="orc-premium-kpi-icon"><i class="ti-harddrives"></i></div>
					<div class="orc-premium-kpi-body">
						<p class="orc-premium-kpi-label">Arquivados</p>
						<p class="orc-premium-kpi-value"><?= count($orcamentosArquivados) ?></p>
					</div>
				</div>
			</div>
		</div>
	<?php } ?>
	<div class="card orc-premium-card-inner">
		<div class="card-body">
			<?php if($role == 0){ ?>
				<div class="orc-premium-toolbar">
					<?= $this->Html->link('<i class="ti-plus"></i> Gerar orçamento', ['action' => 'add'], ['class' => 'btn btn-orc-premium-primary m-b-10', 'escape' => false, 'target' => '_blank']) ?>
				</div>
				<ul class="nav nav-tabs customtab orc-premium-tabs" role="tablist">
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
						<div class="orc-premium-toolbar">
							<?= $this->Html->link('<i class="ti-plus"></i> Solicitar orçamento', ["controller" => "Tickets", "action" => "add", 4], ['class' => 'btn btn-orc-premium-primary', 'escape' => false]) ?>
						</div>
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
