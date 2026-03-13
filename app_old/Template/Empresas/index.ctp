<?php use Cake\Routing\Router; ?>
<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<ul class="nav nav-tabs customtab" role="tablist">
                <li class="nav-item"> <a class="nav-link active" data-toggle="tab" href="#ativas" role="tab" aria-selected="true"><span class="hidden-sm-up"><i class="fas fa-users"></i></span> <span class="hidden-xs-down">Ativas</span></a> </li>
                <li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#inativas" role="tab" aria-selected="false"><span class="hidden-sm-up"><i class="fa fa-ban"></i></span> <span class="hidden-xs-down">Inativas</span></a> </li>
            </ul>
			<div class="tab-content">
				<div class="tab-pane active" id="ativas">
					<div class="table-responsive">	
						<table class="table table-hover table-row-clickable" id="tableAtivas">
							<thead class="text-primary">
								<th>Nome</th>
								<th>E-mail</th>
								<th>Token</th>
								<th>Usuários</th>
							</thead>
							<tbody>
								<?php foreach ($ativas as $ativa): ?>
									<tr>
										<td width="30%"><a target='_blank' class="link" href='<?= $this->Url->build(["controller" => "Empresas", "action" => "edit", $ativa->id]) ?>'><?= $ativa->nomefantasia ?></a></td>
										<td width="30%"><a target='_blank' class="link" href='<?= $this->Url->build(["controller" => "Empresas", "action" => "edit", $ativa->id]) ?>'><?= $ativa->email ?></a></td>
										<td width="20%"><a class="link token" id="<?=$ativa->token?>" href="#">Exibir</a></td>
										<td width="20%"><a target='_blank' class="link" href='<?= $this->Url->build(["controller" => "Empresasusers", "action" => "index"]) ?>'><?= $ativa->nrousuarios ?></a></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
				<div class="tab-pane" id="inativas">
					<div class="table-responsive">	
						<table class="table table-hover table-row-clickable" id="tableInativas">
							<thead class="text-primary">
								<th>Nome</th>
								<th>E-mail</th>
								<th>Token</th>
								<th>Usuários</th>
							</thead>
							<tbody>
								<?php foreach ($inativas as $inativa): ?>
									<tr>
										<td width="30%"><a target='_blank'class="link" href='<?= $this->Url->build(["controller" => "Empresas", "action" => "edit", $inativa->id]) ?>'><?= $inativa->nomefantasia ?></a></td>
										<td width="30%"><a target='_blank'class="link" href='<?= $this->Url->build(["controller" => "Empresas", "action" => "edit", $inativa->id]) ?>'><?= $inativa->email ?></a></td>
										<td width="20%"><a class="link token" id="<?=$inativa->token?>" href="#">Exibir</a></td>
										<td width="20%"><a target='_blank' class="link" href='<?= $this->Url->build(["controller" => "Empresasusers", "action" => "index"]) ?>'><?= $inativa->nrousuarios ?></a></td>
									</td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					</div>	
				</div>
			</div>
			<?php if($admin){
				echo $this->Html->link('Adicionar empresa', ['action' => 'add'], ['class' => 'btn btn-success m-t-20 m-r-5', 'target' => '_blank']);
				echo $this->Html->link('Voltar para as configurações', ["controller" => "config", "action" => "index"], ['class' => 'btn btn-primary m-t-20 m-r-5']);
				// echo $this->Html->link('Migrar dados', ['action' => 'migrar'], ['class' => 'btn btn-secondary m-t-20 m-r-5']);
				// echo $this->Html->link('Migrar dados de clientes', ['action' => 'migrarcliente'], ['class' => 'btn btn-queequaseinfo m-t-20']);
			} ?>
		</div>
	</div>
</div>

<script>
	$(document).ready(function() {
		var $window = $(window);

		table = $('#tableAtivas, #tableInativas');
		table.on( 'length.dt', function ( e, settings, len ) {
			pagelength(len);
		} )
		table.DataTable({
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
				"drawCallback": function( settings ) {
					if ($('body').hasClass('dark-mode') ) $('td').each(function(){$(this).addClass('dark-mode');});
					else $('td').each(function(){$(this).removeClass('dark-mode');});
				},
			}
		});
		table.search(filters).draw();
	});

	window.onload = function() {
		$('#ativas [type="search"]').focus();
	}

	$('.token').click(function(e) {
		var token = e.target.id;
		bootbox.alert(token);
	});
</script>
