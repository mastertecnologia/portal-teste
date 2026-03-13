<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<?= $this->Html->link('Cadastrar um novo produto/serviço/contrato', ['action' => 'add'], ['class' => 'btn btn-success  m-t-20 m-r-5', 'style' => 'margin-bottom: 20px;', 'target' => '_blank']) ?>
			<?= $this->Html->link('Estoque', ['action' => 'estoque'], ['class' => 'btn btn-info  m-t-20 m-r-5', 'style' => 'margin-bottom: 20px;', 'target' => '_blank']) ?>
			<ul class="nav nav-tabs customtab" role="tablist">
                <li class="nav-item"> <a class="nav-link active" data-toggle="tab" href="#produtos" role="tab" aria-selected="true"><span class="hidden-sm-up"><i class="fa fa-box-open"></i></span> <span class="hidden-xs-down">Produtos</span></a> </li>
                <li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#servicos" role="tab" aria-selected="false"><span class="hidden-sm-up"><i class="fa fa-laptop"></i></span> <span class="hidden-xs-down">Serviços</span></a> </li>
                <li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#contratos" role="tab" aria-selected="false"><span class="hidden-sm-up"><i class="fa fa-book"></i></span> <span class="hidden-xs-down">Contratos</span></a> </li>
            </ul>
			<div class="tab-content">
				<div class="tab-pane active" id="produtos">
					<div class="table-responsive">	
						<table class="table table-hover table-row-clickable" id="tableProdutos">
							<thead class="text-primary">
								<th>Código</th>
								<th>Descrição</th>
								<th>Valor Unitário</th>
								<th class="text-right">Quantidade Atual</th>
								<th class="text-right">Preço Custo</th>
								<th class="text-right">Preço Venda</th>
								<th>Ativo</th>
							</thead>
							<tbody>
								<?php foreach ($produtos as $reg): ?>
									<tr>
										<td><a target='_blank' class="link" href='<?= $this->Url->build(["action" => "edit", $reg->id]) ?>'><?= $reg->codigo ?></a></td>
										<td><a target='_blank' class="link" href='<?= $this->Url->build(["action" => "edit", $reg->id]) ?>'><?= $reg->descricao ?></a></td>
										<td><?= number_format( $reg->vlunitario   , 2, ",", ".") ?></td>
										<td class="text-right"><?= $reg->nQtdeAtual ?></td>
										<td class="text-right"><?= number_format($reg->nPrecoCusto, 2, ',', '.') ?></td>
										<td class="text-right"><?= number_format($reg->nPrecoVenda, 2, ',', '.') ?></td>
										<td><?= ProdutosAtivo($reg->ativo) ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
				<div class="tab-pane " id="servicos">
					<div class="table-responsive">	
						<table class="table table-hover table-row-clickable" id="tableServicos">
						<thead class="text-primary">
								<th>Código</th>
								<th>Descrição</th>
								<th>Vl. Hora</th>
								<th>Ativo</th>
							</thead>
							<tbody>
								<?php foreach ($servicos as $reg): ?>
									<tr>
										<td><a target='_blank' class="link" href='<?= $this->Url->build(["action" => "edit", $reg->id]) ?>'><?= $reg->codigo ?></a></td>
										<td><a target='_blank' class="link" href='<?= $this->Url->build(["action" => "edit", $reg->id]) ?>'><?= $reg->descricao ?></a></td>
										<td><?= number_format( $reg->vlunitario   , 2, ",", ".") ?></td>
										<td><?= ProdutosAtivo($reg->ativo) ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
				<div class="tab-pane " id="contratos">
					<div class="table-responsive">	
						<table class="table table-hover table-row-clickable" id="tableServicos">
						<thead class="text-primary">
								<th>Código</th>
								<th>Descrição</th>
								<th>Vl. Mensal</th>
								<th>Ativo</th>
							</thead>
							<tbody>
								<?php foreach ($contratos as $reg): ?>
									<tr>
										<td><a target='_blank' class="link" href='<?= $this->Url->build(["action" => "edit", $reg->id]) ?>'><?= $reg->codigo ?></a></td>
										<td><a target='_blank' class="link" href='<?= $this->Url->build(["action" => "edit", $reg->id]) ?>'><?= $reg->descricao ?></a></td>
										<td><?= number_format( $reg->vlunitario   , 2, ",", ".") ?></td>
										<td><?= ProdutosAtivo($reg->ativo) ?></td>
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
		var $window = $(window);
		table = $('#tableProdutos, #tableServicos');
		table.on( 'length.dt', function ( e, settings, len ) {
			pagelength(len);
		});
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
			}
		});
		table.search(filters).draw();
	});

	window.onload = function() {
		$('[type="search"]').focus();
	}
</script>
