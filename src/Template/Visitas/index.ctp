<div class="col-md-12">
	<div class="card" >
		<div class="card-body">
			<div class="row">
				<div class="col-md-12">
					<div class="table-responsive">	
						<table class="table table-hover table-row-clickable" id="table-visitas">
							<thead class="text-primary">
								<th> Id </th>
								<th> Empresa </th>
								<th> Membros </th>
								<th> Situação </th>
								<th> Data </th>
								<th> Hora  </th>
							</thead>
							<tbody>
								<?php foreach ($visitas as $reg): ?>
									<tr rel="popover" data-trigger="hover" data-content='<div class="popover-big"><?= nl2br(str_replace("'", "", $reg->motivo)) ?></div>' data-original-title="Visita <small class='pgm-popover-title-date'><i>(<?= date_format($reg->data, 'd/m/Y') ?>)</i></small>" data-html="true" data-placement="top">
										<td><a class='link' href='<?= $this->Url->build(["controller" => "Agenda", "action" => "edit", $reg->id]) ?>'><?= $reg->id ?></td>
										<td><a class='link' href='<?= $this->Url->build(["controller" => "Agenda", "action" => "edit", $reg->id]) ?>'>
										<?php 
										if($reg->cliente != null){
											if(!empty($reg->cliente->razaosocial)) echo $reg->cliente->razaosocial; else echo $reg->cliente->nome;
										} 
										else echo $reg->nomecliente;
										?>
										</td>
										<td>   
											<?php
										 	foreach ($reg->users as $atendente){
												echo $atendente->name . '<br>'; 
											}		
											?>			
										</td>
										<td><?= h($this->Visitas->descricaoSituacaoVisitas($reg->situacao)) ?></td>
										<td data-order="<?= date_format($reg->data, 'Ymd') ?>"><?= date_format($reg->data, 'd/m/Y') ?></td>
										<td><?= date_format($reg->horaini, 'H:i') . " - " . date_format($reg->horafim, 'H:i') ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
               		</div>
                    <?= $this->Html->link('Cadastrar nova visita', ['action' => 'add'], ['class' => 'btn btn-pgm btn-pgm-salvar btn-success m-t-10 m-b-10']) ?>
                    <?= $this->Html->link('Calendário', ['action' => 'calendario'], ['class' => 'btn btn-secondary m-t-10 m-b-10']) ?>
                </div>
            </div>
			<div class="clearfix"></div>
		</div>
	</div>
</div>



<script>
	$(document).ready(function() {
        $('#situacao').on('change', function() {
            this.form.submit();
        });

		table = $('#table-visitas');
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
					"sFirst":    "Primeiro",
					"sLast":    "Último",
					"sNext":    "Seguinte",
					"sPrevious": "Anterior"
				},
				"oAria": {
					"sSortAscending":  ": Ordem Ascendente",
					"sSortDescending": ": Ordem descendente"
				}
			},
			"drawCallback": function( settings ) {
				$('td').removeClass('dark-mode');
			},
		});
    	table.search(filters).draw();
		// Popover
		$('[rel=popover]').popover({offset: 0});
	
    });
</script>