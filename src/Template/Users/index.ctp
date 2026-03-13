<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<ul class="nav nav-tabs customtab" role="tablist">
                <li class="nav-item"> <a class="nav-link active" data-toggle="tab" href="#admins" role="tab" aria-selected="true"><span class="hidden-sm-up"><i class="fa fa-user"></i></span> <span class="hidden-xs-down">Usuários</span></a> </li>
                <li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#clients" role="tab" aria-selected="false"><span class="hidden-sm-up"><i class="fa fa-building"></i></span> <span class="hidden-xs-down">Clientes</span></a> </li>
            </ul>
			<div class="tab-content">
				<div class="tab-pane active" id="admins">
					<div class="table-responsive">	
						<table class="table table-hover table-row-clickable" id="tableAdmins">
							<thead class="text-primary">
								<th> Usuário </th>
								<th> E-mail </th>
								<th> Nome </th>
								<?php
									if($admin) {
										echo "<th> Ações </th>";
									}
								?>
							</thead>
							<tbody>
								<?php foreach ($admins as $adm): ?>
									<tr rel="popover" data-trigger="hover" data-content='<div class="popover-big"><h4>Desativar 2FA</h4><br>Desative a autenticação de dois fatores</div>' data-original-title="Autenticação 2FA <small style='font-size: 12px;'></small>" data-html="true" data-placement="top">										<td width="33%"><a class="link" target='_blank' href='<?= $this->Url->build(["controller" => "Users", "action" => "edit", $adm->id]) ?>'><?= $adm->username ?></a></td>
										<td width="33%"><a class="link" target='_blank' href='<?= $this->Url->build(["controller" => "Users", "action" => "edit", $adm->id]) ?>'><?= $adm->email ?></a></td>
										<td width="20%"><a class="link" target='_blank' href='<?= $this->Url->build(["controller" => "Users", "action" => "edit", $adm->id]) ?>'><?= $adm->name ?></a></td>
										<td class='td-actions'>
										<?php if($admin) { ?>
												<?php 
													if($admin && $adm->secret != null) { 
														echo $this->Html->link('<i class="far fa-id-card"></i>', ["action" => "desativaverificacaoqualqueruser", $adm->id, '0'], ['rel' => 'tooltip', 'title' => 'Desativar 2FA', 'class' => 'btn btn-info btn-secondary btn-xs', 'id' => $adm->id, 'escape' => false, 'confirm' => 'Tem certeza que deseja desativar a autenticação de dois fatores deste usuário?']);
													}
												?>
										<?php } ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<?php if($admin){
						echo $this->Html->link('Adicionar usuário', ['action' => 'add'], ['class' => 'btn btn-success  m-t-20 m-r-5', 'style' => 'margin-bottom: 20px;', 'target' => '_blank']);
						echo $this->Html->link('Voltar para as configurações', ["controller" => "config", "action" => "index"], ['class' => 'btn btn-primary  m-t-20', 'style' => 'margin-bottom: 20px;']);
					} ?>
				</div>
				<div class="tab-pane" id="clients">
					<div class="table-responsive">	
						<table class="table table-hover table-row-clickable" id="tableClients">
							<thead class="text-primary">
								<tr>
									<th> Usuário </th>
									<th> E-mail </th>
									<th> Empresa </th>
									<th> Situação </th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($clients as $client): 
                                    if ($client->inativo == 1) {
                                        $label = 'danger';
                                        $sit = 'Inativo';
                                    }
                                    else if ($client->inativo == 0) {
                                        $label = 'success';
                                        $sit = 'Ativo';
									}
								?>
									<tr>
										<td width="25%"><a class="link"target='_blank'  href='<?= $this->Url->build(["controller" => "Users", "action" => "editcliente", $client->id]) ?>'><?= $client->username ?></a></td>
										<td width="25%"><a class="link"target='_blank'  href='<?= $this->Url->build(["controller" => "Users", "action" => "editcliente", $client->id]) ?>'><?= $client->email ?></a></td>
										<td width="25%">
											<a class="link" target='_blank' href='<?= $this->Url->build(["controller" => "Users", "action" => "editcliente", $client->id]) ?>'>
												<?= empty($client->cliente->razaosocial) ? $client->cliente->nome : $client->cliente->razaosocial?>
											</a>
										</td>
										<td width="25%"><a class="link" href='<?= $this->Url->build(["controller" => "Users", "action" => "edit", $client->id]) ?>'><span class="label label-<?= $label ?>"><?= $sit ?></span></a></td>
									</td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					</div>	
					<?php if($admin){
						echo $this->Html->link('Adicionar cliente', ['action' => 'addcliente'], ['class' => 'btn btn-success m-t-20 m-r-5', 'style' => 'margin-bottom: 20px;', 'target' => '_blank']);
						echo $this->Html->link('Voltar para as configurações', ["controller" => "config", "action" => "index"], ['class' => 'btn btn-primary m-t-20', 'style' => 'margin-bottom: 20px;']); 
					} ?>
				</div>
			
		</div>
	</div>
</div>
</div>
<script>
	$('[rel=popover]').popover({offset: 0});

	$(document).ready(function() {
		var $window = $(window);

		table = $('#tableAdmins, #tableClients');
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
</script>
