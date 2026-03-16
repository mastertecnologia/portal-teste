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
					<ul class="nav nav-pills m-b-10" role="tablist">
						<li class="nav-item">
							<a class="nav-link active" data-toggle="tab" href="#ativos_pj" role="tab" aria-selected="true">
								Pessoa Jurídica <span class="badge badge-pill badge-info m-l-5"><?= count($clientesAtivosPJ) ?></span>
							</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" data-toggle="tab" href="#ativos_pf" role="tab" aria-selected="false">
								Pessoa Física <span class="badge badge-pill badge-info m-l-5"><?= count($clientesAtivosPF) ?></span>
							</a>
						</li>
					</ul>
					<div class="tab-content">
						<div class="tab-pane active" id="ativos_pj">
							<div class="table-responsive">
								<table class="table table-hover table-row-clickable" id="tableAtivosPJ">
									<thead class="text-primary">
										<th style="width:40%">Razão Social</th>
										<th style="width:20%">CNPJ</th>
										<th style="width:20%">E-mail</th>
										<th style="width:20%">Telefone</th>
									</thead>
									<tbody>
										<?php foreach ($clientesAtivosPJ as $reg): ?>
											<tr>
												<td><a class='link' target='_blank' href='<?= $this->Url->build(["controller" => "Clientes", "action" => "edit", $reg->id]) ?>'><?= $reg->razaosocial ?></a></td>
												<td><a class='link' target='_blank' href='<?= $this->Url->build(["controller" => "Clientes", "action" => "edit", $reg->id]) ?>'><?= formatCnpjCpf($reg->cnpj) ?></a></td>
												<td><a class='link' target='_blank' href='<?= $this->Url->build(["controller" => "Clientes", "action" => "edit", $reg->id]) ?>'><?= $reg->email ?></a></td>
												<td><a class='link' target='_blank' href='<?= $this->Url->build(["controller" => "Clientes", "action" => "edit", $reg->id]) ?>'><?php if(!empty($reg->fone)) echo Mask("(###) ####-####",$reg->fone).'<br>'; if(!empty($reg->fone2))echo Mask("(###) #####-####",$reg->fone2) ?></a></td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						</div>
						<div class="tab-pane" id="ativos_pf">
							<div class="table-responsive">
								<table class="table table-hover table-row-clickable" id="tableAtivosPF">
									<thead class="text-primary">
										<th style="width:40%">Nome</th>
										<th style="width:20%">CPF</th>
										<th style="width:20%">E-mail</th>
										<th style="width:20%">Telefone</th>
									</thead>
									<tbody>
										<?php foreach ($clientesAtivosPF as $reg): ?>
											<tr>
												<td><a class='link' target='_blank' href='<?= $this->Url->build(["controller" => "Clientes", "action" => "edit", $reg->id]) ?>'><?= $reg->nome ?></a></td>
												<td><a class='link' target='_blank' href='<?= $this->Url->build(["controller" => "Clientes", "action" => "edit", $reg->id]) ?>'><?= formatCnpjCpf($reg->cpf) ?></a></td>
												<td><a class='link' target='_blank' href='<?= $this->Url->build(["controller" => "Clientes", "action" => "edit", $reg->id]) ?>'><?= $reg->email ?></a></td>
												<td><a class='link' target='_blank' href='<?= $this->Url->build(["controller" => "Clientes", "action" => "edit", $reg->id]) ?>'><?php if(!empty($reg->fone)) echo Mask("(###) ####-####",$reg->fone).'<br>'; if(!empty($reg->fone2))echo Mask("(###) #####-####",$reg->fone2) ?></a></td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
				<div class="tab-pane" id="inativos">
					<ul class="nav nav-pills m-b-10" role="tablist">
						<li class="nav-item">
							<a class="nav-link active" data-toggle="tab" href="#inativos_pj" role="tab" aria-selected="true">
								Pessoa Jurídica <span class="badge badge-pill badge-secondary m-l-5"><?= count($clientesInativosPJ) ?></span>
							</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" data-toggle="tab" href="#inativos_pf" role="tab" aria-selected="false">
								Pessoa Física <span class="badge badge-pill badge-secondary m-l-5"><?= count($clientesInativosPF) ?></span>
							</a>
						</li>
					</ul>
					<div class="tab-content">
						<div class="tab-pane active" id="inativos_pj">
							<div class="table-responsive">
								<table class="table table-hover table-row-clickable" id="tableInativosPJ">
									<thead class="text-primary">
										<th style="width:30%">Razão Social</th>
										<th style="width:15%">CNPJ</th>
										<th style="width:20%">E-mail</th>
										<th style="width:15%">Telefone</th>
										<th style="width:10%">Ações</th>
									</thead>
									<tbody>
										<?php foreach ($clientesInativosPJ as $reg): ?>
											<tr>
												<td><a class='link' target='_blank' href='<?= $this->Url->build(["controller" => "Clientes", "action" => "edit", $reg->id]) ?>'><?= $reg->razaosocial ?></a></td>
												<td><a class='link' target='_blank' href='<?= $this->Url->build(["controller" => "Clientes", "action" => "edit", $reg->id]) ?>'><?= formatCnpjCpf($reg->cnpj) ?></a></td>
												<td><a class='link' target='_blank' href='<?= $this->Url->build(["controller" => "Clientes", "action" => "edit", $reg->id]) ?>'><?= $reg->email ?></a></td>
												<td><a class='link' target='_blank' href='<?= $this->Url->build(["controller" => "Clientes", "action" => "edit", $reg->id]) ?>'><?php if(!empty($reg->fone)) echo Mask("(###) ####-####",$reg->fone).'<br>'; if(!empty($reg->fone2))echo Mask("(###) #####-####",$reg->fone2) ?></a></td>
												<td>
													<?= $this->Html->link('Reativar', ['controller' => 'Clientes', 'action' => 'reativar', $reg->id], ['class' => 'btn btn-success btn-sm', 'confirm' => 'Você confirma a reativação deste cliente?']) ?>
												</td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						</div>
						<div class="tab-pane" id="inativos_pf">
							<div class="table-responsive">
								<table class="table table-hover table-row-clickable" id="tableInativosPF">
									<thead class="text-primary">
										<th style="width:30%">Nome</th>
										<th style="width:15%">CPF</th>
										<th style="width:20%">E-mail</th>
										<th style="width:15%">Telefone</th>
										<th style="width:10%">Ações</th>
									</thead>
									<tbody>
										<?php foreach ($clientesInativosPF as $reg): ?>
											<tr>
												<td><a class='link' target='_blank' href='<?= $this->Url->build(["controller" => "Clientes", "action" => "edit", $reg->id]) ?>'><?= $reg->nome ?></a></td>
												<td><a class='link' target='_blank' href='<?= $this->Url->build(["controller" => "Clientes", "action" => "edit", $reg->id]) ?>'><?= formatCnpjCpf($reg->cpf) ?></a></td>
												<td><a class='link' target='_blank' href='<?= $this->Url->build(["controller" => "Clientes", "action" => "edit", $reg->id]) ?>'><?= $reg->email ?></a></td>
												<td><a class='link' target='_blank' href='<?= $this->Url->build(["controller" => "Clientes", "action" => "edit", $reg->id]) ?>'><?php if(!empty($reg->fone)) echo Mask("(###) ####-####",$reg->fone).'<br>'; if(!empty($reg->fone2))echo Mask("(###) #####-####",$reg->fone2) ?></a></td>
												<td>
													<?= $this->Html->link('Reativar', ['controller' => 'Clientes', 'action' => 'reativar', $reg->id], ['class' => 'btn btn-success btn-sm', 'confirm' => 'Você confirma a reativação deste cliente?']) ?>
												</td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						</div>
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
		table = $('#tableAtivosPJ, #tableAtivosPF, #tableInativosPJ, #tableInativosPF')
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
		if (typeof filters !== 'undefined') table.search(filters).draw();
	});
</script>
