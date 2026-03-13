<?php 
	use Cake\Routing\Router;
	error_reporting(E_ERROR | E_PARSE);
?>
<style>
	.titulosessao{ padding: 0.5rem !important; }
	.table td, .table th{ padding: 0.7rem !important; }
</style>
<div class="row">
	<div class="col-lg-12" >
		<div class="table-responsive">
			<table class="table table-hover table-row-clickable" id="tableCarrinho">
				<thead class="text-primary">
					<th width="10%"> Código </th>
					<th width="40%"> Descrição da Locação </th>
					<th width="10%" class="text-right"> Quantidade </th>
					<th width="10%" class="text-right"> Valor Item </th>
					<th width="10%" class="text-right"> Valor Total Item</th>
					<th width="10%"	class="text-center"> Ações </th>
				</thead>
				<tbody>
					<!-- Itens -->
					<?php foreach ($carrinho as $reg): ?>
						<tr id='<?= $reg->id ?>'>
							<td> <?= $reg->codigo ?> </td>
							<td> <?= $reg->descricao ?> </td>
							<td class="text-right"> <?= $reg->quantidade ?> </td>
							<td class="text-right"> <?= 'R$ ' . number_format($reg->valoritem, 2, ",", ".") ?> </td>
							<td class="text-right"> <?= 'R$ ' . number_format($reg->valortotal, 2, ",", ".") ?> </td>
							<td class="text-center"> 
								<?= $this->Html->link('<i class="fa fa-times"></i>', [], ['rel' => 'tooltip', 'title' => 'Excluir', 'id' => $reg->id, 'class' => 'excluiitemcarrinho btn btn-danger btn-xs', 'escape' => false]) ?>
							</td>
						</tr>
					<?php endforeach; ?>
					<!-- Fim Itens -->
					<!-- Outros -->
					<tr>
						<th class="text-right"> </th>
						<th class="text-right"> </th>
						<th class="text-right"> </th>
						<th class="text-right"> Valor Total: </th>
						<th class="text-right valortotal"> </th>
						<th class="text-right"> </th>
					</tr>
					<!-- Fim Outros -->
				</tbody>
			</table>
		</div>
		<button class="btn btn-danger float-right m-b-10 btn-limpacarrinho"> Limpar Carrinho </button>
	</div>
</div>
<script>
	function numberToReal(numero) {
		if(!isNaN(numero)){
			var numero = numero.toFixed(2).split('.');
			numero[0] = numero[0].split(/(?=(?:...)*$)/).join('.');
			return numero.join(',');
		}
	}

	function valorTotal() {
		var valorTotal = <?= $valorTotal ?>;
		var desconto = $('#desconto').val().replace('.', '').replace(',', '.');
		var outrosgastos = $('#outrosgastos').val().replace('.', '').replace(',', '.');
		if(desconto == '') desconto = 0;
		if(outrosgastos == '') outrosgastos = 0;
		valorTotal = parseFloat(valorTotal) - parseFloat(desconto) + parseFloat(outrosgastos);
		if(valorTotal < 0 || isNaN(valorTotal)) valorTotal = 0;
		$('.valortotal').text('R$ ' + numberToReal(valorTotal));
	}

	valorTotal();

	$("#desconto, #outrosgastos").change(function() {
		valorTotal();
	});

	$('.excluiitemcarrinho').click(function(e) {
		e.preventDefault();
		id = $(this).attr('id');
		$.ajax({
			type: "POST",
			url: "<?= Router::url(['controller' => 'Faturas', 'action' => 'deleteitem']);?>/"+id,
			dataType: "html",
			error : function(error) { alert(error); },
			complete: function(data) { carrinho(e); }
		});
	});

	$('.btn-limpacarrinho').click(function(e) {	
		e.preventDefault();
		$.ajax({
			type: "POST",
			url: "<?= Router::url(['controller' => 'Faturas', 'action' => 'limpacarrinho', $idcarrinho]);?>",
			dataType: "html",
			error : function(error) { alert(error); },
			complete: function(data) { carrinho(e); }
		});
	});
</script>