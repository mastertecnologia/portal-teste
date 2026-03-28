<?php use Cake\Routing\Router; ?>
<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<ul class="nav nav-tabs customtab" role="tablist">
                <li class="nav-item"> <a class="nav-link active" data-toggle="tab" href="#funcrelacoes" role="tab" aria-selected="true"><span class="hidden-sm-up"><i class="fa fa-user"></i></span> <span class="hidden-xs-down"> Funcionários</span></a> </li>
                <li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#clirelacoes" role="tab" aria-selected="false"><span class="hidden-sm-up"><i class="fa fa-building"></i></span> <span class="hidden-xs-down"> Clientes</span></a> </li>
            </ul>
			<div class="tab-content">
				<div class="tab-pane active" id="funcrelacoes">
					<div class="table-responsive">	
						<table class="table table-hover table-row-clickable" id="tableFuncrelacoes">
							<thead class="text-primary">
								<th>Empresa</th>
								<th>Usuário</th>
								<th></th>
							</thead>
							<tbody>
								<?php foreach ($funcrelacoes as $funcrelacao): ?>
									<tr>
										<td width="40%"><a class="link"><?= $funcrelacao['empresa']['nomefantasia'] ?></a></td>
										<td width="45%"><a class="link"><?= $funcrelacao['user']['name'] ?></a></td>
										<td width="5%"><?= $this->Html->link('Excluir', ["controller" => "Empresasusers", "action" => "delete", $funcrelacao->id], ['class' => 'label label-danger']) ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<?php if($admin){
						echo $this->Html->link('Adicionar relação', ['action' => 'add'], ['class' => 'btn btn-pgm btn-pgm-salvar btn-success  m-t-20 m-r-5', 'style' => 'margin-bottom: 20px;', 'target' => '_blank']);
						echo $this->Html->link('Voltar para as configurações', ["controller" => "config", "action" => "index"], ['class' => 'btn btn-pgm btn-pgm-situacao btn-primary  m-t-20', 'style' => 'margin-bottom: 20px;']);
					} ?>
                </div>
				<div class="tab-pane" id="clirelacoes">
					<div class="table-responsive">	
						<table class="table table-hover table-row-clickable" id="tableClirelacoes">
							<thead class="text-primary">
								<th>Empresa</th>
								<th>Usuário</th>
								<th></th>
							</thead>
							<tbody>
								<?php foreach ($clirelacoes as $clirelacao): ?>
									<tr>
										<td width="40%"><a class="link"><?= $clirelacao['empresa']['nomefantasia'] ?></a></td>
										<td width="45%"><a class="link"><?= $clirelacao['user']['name'] ?></a></td>
										<td width="5%"><?= $this->Html->link('Excluir', ["controller" => "Empresasusers", "action" => "delete", $clirelacao->id], ['class' => 'label label-danger']) ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<?php if($admin){
						echo $this->Html->link('Adicionar relação', ['action' => 'add'], ['class' => 'btn btn-pgm btn-pgm-salvar btn-success  m-t-20 m-r-5', 'style' => 'margin-bottom: 20px;', 'target' => '_blank']);
						echo $this->Html->link('Voltar para as configurações', ["controller" => "config", "action" => "index"], ['class' => 'btn btn-pgm btn-pgm-situacao btn-primary  m-t-20', 'style' => 'margin-bottom: 20px;']);
					} ?>
                </div>
            </div>      	
		</div>
	</div>
</div>

<script>
$(document).ready(function() {
	var $window = $(window);

	table = $('#tableFuncrelacoes, #tableClirelacoes');
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
				$('td').removeClass('dark-mode');
			},
		}
	});
    table.search(filters).draw();
});

window.onload = function() {
	$('#funcrelacoes [type="search"]').focus();
}

</script>
