<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<?= $this->Html->link('Cadastrar um novo status', ['action' => 'add'], ['class' => 'btn btn-pgm btn-pgm-salvar btn-success m-t-20 m-r-5 m-b-20', 'target' => '_blank']); ?>
            <div class="table-responsive">	
                <table class="table table-hover table-row-clickable" id="tableAreas">
                    <thead class="text-primary">
                        <th width='10%'>Código</th>
                        <th width='80%'>Descrição</th>
						<th></th>
                    </thead>
                    <tbody>
                        <?php foreach ($areas as $reg): ?>
                            <tr>
                                <td><a class="link" target='_blank' href='<?= $this->Url->build(["action" => "edit", $reg->id]) ?>'><?= $reg->id ?></a></td>
                                <td><a class="link" target='_blank' href='<?= $this->Url->build(["action" => "edit", $reg->id]) ?>'><?= $reg->descricao ?></a></td>
								<td><?= $this->Html->link('Excluir', ["controller" => "Areas", "action" => "delete", $reg->id], ['class' => 'label label-danger']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
		</div>
    </div>
</div>
<script>
	$(document).ready(function() {
		var $window = $(window);
		table = $('#tableAreas');
		table.DataTable({
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
</script>
