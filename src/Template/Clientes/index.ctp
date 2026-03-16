<?php
	function Mask($mask,$str){
		$str = str_replace(" ","",$str);
		for($i=0;$i<strlen($str);$i++) $mask[strpos($mask,"#")] = $str[$i];
		return $mask;
	}
?>
<style>
	.table td, .table th { padding: 0.4rem;	}
</style>
<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<div class="d-flex justify-content-between align-items-center m-b-15">
				<div>
					<h5 class="card-title m-b-0">Lista de Clientes</h5>
					<small class="text-muted">Clientes vinculados ao ERP e habilitados para acesso ao portal.</small>
				</div>
				<div class="text-right">
					<?= $this->Html->link('Cadastrar cliente', ['action' => 'add'], ['class' => 'btn btn-success btn-sm m-r-5', 'target' => '_blank']) ?>
				</div>
			</div>
			<ul class="nav nav-tabs customtab" role="tablist">
				<li class="nav-item"> <a class="nav-link active" data-toggle="tab" href="#ativos" role="tab" aria-selected="true"><span class="hidden-sm-up"><i class="ti-check"></i></span> <span class="hidden-xs-down">Ativos</span></a> </li>
				<li class="nav-item"> <a class="nav-link " data-toggle="tab" href="#inativos" role="tab" aria-selected="false"><span class="hidden-sm-up"><i class="ti-na"></i></span> <span class="hidden-xs-down">Inativos</span></a> </li>
			</ul>
			<div class="tab-content">
				<div class="tab-pane active" id="ativos">
					<div class="table-responsive">
						<table class="table table-hover table-row-clickable" id="tableAtivos">
							<thead class="text-primary">
								<th style="width:35%">Nome</th>
								<th style="width:15%">Tipo</th>
								<th style="width:15%">CPF/CNPJ</th>
								<th style="width:15%">E-mail</th>
								<th style="width:10%">Telefone</th>
							</thead>
							<tbody>
								<?php foreach ($clientesAtivos as $reg): ?>
									<tr>
										<td> <a class='link' target='_blank' href='<?= $this->Url->build(["controller" => "Clientes", "action" => "edit", $reg->id]) ?>'><?= $reg->tipo == C_ClientesTipoFisica ? $reg->nome : $reg->razaosocial ?></td>
										<td> <a class='link' target='_blank' href='<?= $this->Url->build(["controller" => "Clientes", "action" => "edit", $reg->id]) ?>'><?= ClientesTipo($reg->tipo) ?></td>
										<td> <a class='link' target='_blank' href='<?= $this->Url->build(["controller" => "Clientes", "action" => "edit", $reg->id]) ?>'><?= $reg->tipo == C_ClientesTipoFisica ? formatCnpjCpf($reg->cpf) : formatCnpjCpf($reg->cnpj) ?></td>
										<td> <a class='link' target='_blank' href='<?= $this->Url->build(["controller" => "Clientes", "action" => "edit", $reg->id]) ?>'><?= $reg->email ?></td>
										<td> <a class='link' target='_blank' href='<?= $this->Url->build(["controller" => "Clientes", "action" => "edit", $reg->id]) ?>'><?php if(!empty($reg->fone)) echo Mask("(###) ####-####",$reg->fone).'<br>'; if(!empty($reg->fone2))echo Mask("(###) #####-####",$reg->fone2) ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
				<div class="tab-pane" id="inativos">
					<div class="table-responsive">
						<table class="table table-hover table-row-clickable" id="tableInativos">
							<thead class="text-primary">
								<th style="width:35%">Nome</th>
								<th style="width:15%">Tipo</th>
								<th style="width:15%">CPF/CNPJ</th>
								<th style="width:15%">E-mail</th>
								<th style="width:10%">Telefone</th>
							</thead>
							<tbody>
								<?php foreach ($clientesInativos as $reg): ?>
									<tr>
										<td> <a class='link' target='_blank' href='<?= $this->Url->build(["controller" => "Clientes", "action" => "edit", $reg->id]) ?>'><?= $reg->tipo == C_ClientesTipoFisica ? $reg->nome : $reg->razaosocial ?></td>
										<td> <a class='link' target='_blank' href='<?= $this->Url->build(["controller" => "Clientes", "action" => "edit", $reg->id]) ?>'><?= ClientesTipo($reg->tipo) ?></td>
										<td> <a class='link' target='_blank' href='<?= $this->Url->build(["controller" => "Clientes", "action" => "edit", $reg->id]) ?>'><?= $reg->tipo == C_ClientesTipoFisica ? formatCnpjCpf($reg->cpf) : formatCnpjCpf($reg->cnpj) ?></td>
										<td> <a class='link' target='_blank' href='<?= $this->Url->build(["controller" => "Clientes", "action" => "edit", $reg->id]) ?>'><?= $reg->email ?></td>
										<td> <a class='link' target='_blank' href='<?= $this->Url->build(["controller" => "Clientes", "action" => "edit", $reg->id]) ?>'><?php if(!empty($reg->fone)) echo Mask("(###) ####-####",$reg->fone).'<br>'; if(!empty($reg->fone2))echo Mask("(###) #####-####",$reg->fone2) ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
			
			</div>
			<div class="clearfix"></div>
		</div>
	</div>
</div>
<script>
	$(document).ready(function() {
		var $window = $(window);
		table = $('#tableAtivos, #tableInativos')
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
				}
			},
			"drawCallback": function( settings ) {
				if ($('body').hasClass('dark-mode') ) $('td').each(function(){$(this).addClass('dark-mode');});
				else $('td').each(function(){$(this).removeClass('dark-mode');});
			},
		});
		table.search(filters).draw();
	});
</script>
