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
						<tr <?= $reg->qtddevolvida ? 'style="background-color: #85eaff"' : '' ?> id='<?= $reg->id ?>'>
							<td id="codigo<?= $reg->id ?>"> <?= $reg->codigo ?> </td>
							<td id="descricao<?= $reg->id ?>"> <?= $reg->descricao ?> </td>
							<td id="quantidade<?= $reg->id ?>" class="text-right"> <?= $reg->quantidade ?> </td>
							<td id="valoritem<?= $reg->id ?>" class="text-right"> <?= 'R$ ' . number_format($reg->valoritem, 2, ",", ".") ?> </td>
							<td id="valortotal<?= $reg->id ?>" class="text-right"> <?= 'R$ ' . number_format($reg->valortotal, 2, ",", ".") ?> </td>
							<td class="text-center"> 
								<?php 
									if($fatura->status == C_LocacaoStatusPendente) {
										echo $this->Html->link('<i class="fa fa-edit"></i>', [], ['rel' => 'tooltip', 'title' => 'Editar', 'id' => $reg->id, 'class' => 'btn-edit-item btn btn-warning btn-xs', 'escape' => false]);
										echo $this->Html->link('<i class="fa fa-times"></i>', [], ['rel' => 'tooltip', 'title' => 'Excluir', 'id' => $reg->id, 'class' => 'btn-del-item btn btn-danger btn-xs', 'escape' => false]);
									}
									if($fatura->status == C_LocacaoStatusAprovado) {
										if(!$reg->qtddevolvida) echo $this->Html->link('<i class="fas fa-level-up-alt"></i>', ['action' => 'devolveritem', $reg->id], ['rel' => 'tooltip', 'title' => 'Devolver', 'id' => $reg->id, 'class' => 'btn-devolver btn btn-queequaseinfo btn-xs', 'escape' => false]);
										else echo $this->Html->link('<i class="fa fa-eye"></i>', ['action' => 'viewitem', $reg->id], ['rel' => 'tooltip', 'title' => 'Visualizar', 'id' => $reg->id, 'class' => 'btn-view btn btn-queequaseinfo btn-xs', 'escape' => false]);
									}
								?>
							</td>
						</tr>
					<?php endforeach; ?>
					<!-- Outros -->
					<tr>
						<th class="text-right"> </th>
						<th class="text-right"> </th>
						<th class="text-right"> </th>
						<th class="text-right"> Valor Total: </th>
						<th class="text-right valortotal">  </th>
						<th class="text-right"> </th>
					</tr>
					<!-- Fim Outros -->
				</tbody>
			</table>
		</div>
	</div>
</div>
<!-- Modal Edit Item -->
<div class="modal fade none-border" id="modal-edit-item">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="row m-20">
				<div class="col-12">
					<?= $this->Form->create(null, ['url' => ['controller' => 'Faturas', 'action' => 'carrinhoedititem'], 'class' => 'form-material']); ?> 
						<div class="row m-t-10">
							<div class="col-lg-2 col-md-12">
								<label class="control-label text-muted"> Código </label>
								<?= $this->Form->control('codigo-modal', ['class' => 'form-control', 'label' => false, 'readonly']) ?>
							</div>
							<div class="col-lg-8 col-md-12">
								<label class="control-label text-muted"> Descrição da Locação </label>
								<?= $this->Form->control('descricao-modal', ['class' => 'descricao form-control', 'label' => false]) ?>
							</div>
						</div>
						<div class="row">
							<div class="col-lg-2 col-md-6">
								<div class="form-group ">
									<label class="control-label text-muted"> Quantidade </label>
									<?= $this->Form->control('quantidade-modal', ['onkeypress' => 'return SomenteNumero(event, "#quantidade-modal")', 'class' => 'quantidade form-control', 'label' => false]) ?>
								</div>
							</div>
							<div class="col-lg-3 col-md-6">
								<div class="form-group ">
									<label class="control-label text-muted"> Valor Item </label>
									<?= $this->Form->control('valoritem-modal', ['onkeypress' => 'return SomenteNumero(event, "#valoritem-modal")', 'class' => 'form-control mascaramonetaria', 'label' => false]) ?>
								</div>
							</div>
							<div class="col-lg-3 col-md-12">
								<div class="form-group ">
									<label class="control-label text-muted"> Valor Total Item </label>
									<?= $this->Form->control('valortotal-modal', ['onkeypress' => 'return SomenteNumero(event, "#valortotal-modal")', 'class' => 'form-control mascaramonetaria', 'label' => false]) ?>
								</div>
							</div>
						</div>
						<?= $this->Form->control('iditem', ['id' => 'iditem-modal', 'label' => false, 'type' => 'hidden']) ?>
						<button type="button" class="btn btn-danger waves-effect float-right" data-dismiss="modal"> Fechar </button>
						<?= $this->Form->button('Salvar', ['class' => 'btn btn-pgm btn-pgm-salvar btn-info text-white btn-save-item m-r-5 float-right' ]) ?>
					<?= $this->Form->end(); ?>
				</div>
			</div>
		</div>
	</div>
</div>
<!-- Modal View Item -->
<div class="modal fade none-border" id="modal-view">
	<div class="modal-dialog modal-lg">
		<div class="modal-content modal-content-view">
			
		</div>
	</div>
</div>
<script>
	// Edit 
		$('.btn-edit-item').click(function(e){
			e.preventDefault();
			var id = $(this).attr('id');
			$('#codigo-modal').val($('#codigo'+id).html().trim());
			$('#descricao-modal').val($('#descricao'+id).html().trim());
			$('#quantidade-modal').val($('#quantidade'+id).html().trim());
			$('#valoritem-modal').val($('#valoritem'+id).html().replaceAll('R$ ', '').trim());
			$('#valortotal-modal').val($('#valortotal'+id).html().replaceAll('R$ ', '').trim());
			$('#iditem-modal').val(id);
			$('#modal-edit-item').modal('toggle');
		});

		$('#quantidade-modal, #valoritem-modal').change(function(e){
			valoritemEdit = $('#valoritem-modal').val().replaceAll('.', '').replaceAll(',', '.');
			quantidadeEdit = $('#quantidade-modal').val();
			if(quantidadeEdit > 0 && valoritemEdit){
				valortotalEdit = quantidadeEdit * valoritemEdit;
				$('#valortotal-modal').val(numberToReal(valortotalEdit));
			}
			else $('#valortotal-modal').val('');
		});

		$('.btn-save-item').click(function(e){
			e.preventDefault();
			codigo =       	$('#codigo-modal').val();
			descricao = 	$('#descricao-modal').val();
			quantidade =	$('#quantidade-modal').val();
			valoritem = 	$('#valoritem-modal').val();
			valortotal = 	$('#valortotal-modal').val();
			id = 			$('#iditem-modal').val();

			if(descricao == ''){
				bootbox.alert('Preencha o campo "Descrição".');
				return false;
			}

			if(quantidade == '' || (valoritem == '')){
				bootbox.alert('Preencha o campo "Quantidade" e o campo "Valor Unitário".');
				return false;
			}

			$.ajax({
				url: "<?= Router::url(['controller' => 'Faturas', 'action' => 'edititem']);?>",
				dataType: "html",
				type: 'POST',
				data: { id: id, codigo: codigo, descricao: descricao, quantidade: quantidade, valoritem: valoritem, valortotal: valortotal},
				success : function(data) {
					carrinho();
					$('#modal-edit-item').modal('toggle');
				},
				error : function(error) {
					alert(error.msg);
				}
			});
		});
	// Delete 
		$('.btn-del-item').click(function(e) {
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
	// Misc 
		$(function(){
			$(".mascaramonetaria").maskMoney({
				allowNegative: true,
				thousands: '.',
				decimal: ','
			});
		});

		function numberToReal(numero) {
			if(!isNaN(numero)){
				if(numero != null){
					var numero = numero.toFixed(2).split('.');
					numero[0] = numero[0].split(/(?=(?:...)*$)/).join('.');
					return numero.join(',');
				} else return numero;
			}
		}
	// Produto 
		$('#codigo-modal').change(function(e){
			if( $(this).val() != 0){
				$.ajax({
					type:"post",
					url: "<?= Router::url(['controller' => 'Produtos', 'action' => 'produto']);?>" + '/' + $(this).val(),
					dataType: "json",
					success: function(data){
						$('#quantidade-modal').val('');
						$('#descricao-modal').val(data.descricao.trim());
						if($('#tipo').val() == <?= C_LocacaoTipoDiaria ?>) $('#valoritem-modal').val(numberToReal(data.vllocdiario));
						if($('#tipo').val() == <?= C_LocacaoTipoSemanal ?>) $('#valoritem-modal').val(numberToReal(data.vllocsemanal));
						if($('#tipo').val() == <?= C_LocacaoTipoQuinzenal ?>) $('#valoritem-modal').val(numberToReal(data.vllocquinzenal));
						if($('#tipo').val() == <?= C_LocacaoTipoMensal ?>) $('#valoritem-modal').val(numberToReal(data.vllocmensal));
					},
					error: function (error) { console.log(error); }
				});
			} else {
				$('#quantidade').val('');
				$('#descricao').val('');
				$('#valoritem').val('');
				$('#valortotal').val('');
			}
		});
	// Valor total 
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

		$("#desconto, #outrosgastos").change(valorTotal);
	// Devolver 
		$('.btn-devolver').click(function(e) {
			e.preventDefault();
			var href = $(this).attr('href');
			bootbox.confirm({
				message: 'Confirmar a devolução?',
				buttons: {
					confirm: {
						label: 'Sim',
						className: 'btn btn-pgm btn-pgm-salvar btn-success'
					},
					cancel: {
						label: 'Não',
						className: 'btn-danger'
					}
				},
				callback: function (result) {
					if(result == true) {
						window.location = href;
					}
				}
			});
		});
	// View item  
		$('.btn-view').click(function(e) {
			e.preventDefault();
			var href = $(this).attr('href');
			$.ajax({
				type: "POST",
				url: href,
				dataType: "html",
				success: function(data) { 
					$('.modal-content-view').html(data);
					$('#modal-view').modal('toggle')
				},
				error: function(error) { alert(error); },
			});
		});
	// 
</script>