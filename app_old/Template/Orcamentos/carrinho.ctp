<?php 
	use Cake\Routing\Router;
	error_reporting(E_ERROR | E_PARSE);
?>
<style>
	.titulosessao{ padding: 0.5rem !important; }
	.table td, .table th{ padding: 0.7rem !important; }
	.btn-actions { white-space: nowrap; }
</style>
<div class="row">
	<div class="col-lg-12" >
		<div class="table-responsive">
			<table class="table table-hover table-row-clickable" id="tableCarrinho">
				<thead class="text-primary">
					<th width="6%">Código</th>
					<th width="20%">Produto/Serviço</th>
					<th width="18%">Descrição</th>
					<th width="10%" class="text-right">Pagamento</th>
					<th width="10%" class="text-right">Qtde.</th>
					<th width="10%" class="text-right">Vl. Mensal</th>
					<th width="10%" class="text-right">Vl. Unit.</th>
					<th width="10%" class="text-right">Valor Total</th>
                    <th width="5%"	class="text-center">Ações</th>
				</thead>
				<tbody>
					<!-- Serviços -->
					<?php foreach ($carrinho as $reg): ?>
						<tr id='<?= $reg->id ?>'>
							<td><?= $reg->idproduto ?></td>
							<td><?= $reg->servico ?></td>
							<td><?= $reg->observacao ?></td>
							<td class="text-right"> <?= $reg->valormensal > 0 ? 'Mensal' : 'Único'; ?> </td>
							<td class="text-right"><?= $reg->quantidade ?></td>
							<td class="text-right valormensal"><?php echo 'R$ ' . number_format($reg->valormensal, 2, ",", ".") ?></td>
							<td class="text-right valorunit"><?php echo 'R$ ' . number_format($reg->valoruni, 2, ",", ".") ?></td>
							<td class="text-right valordoservico"><?php echo 'R$ ' . number_format($reg->valordoservico, 2, ",", ".") ?></td>
							<td class="text-center btn-actions"> 
							<?= $this->Html->link('<i class="fa fa-edit"></i>', [], [
								'rel' => 'tooltip', 
								'title' => 'Editar', 
								'data-id' => $reg->id,
								'data-servico' => $reg->servico,
								'data-quantidade' => $reg->quantidade,
								'data-valoruni' => $reg->valoruni,
								'data-observacao' => $reg->observacao,
								'data-valormensal' => $reg->valormensal,
								'data-idproduto' => $reg->idproduto,
								'data-tipo' => $reg->valormensal > 0 ? 1 : 0,
								'class' => 'editaitemcarrinho btn btn-warning btn-simple btn-xs', 
								'escape' => false
							]) ?>
								<?= $this->Html->link('<i class="fa fa-times"></i>', [], [
									'rel' => 'tooltip', 
									'title' => 'Excluir', 
									'id' => $reg->id, 
									'class' => 'excluiitemcarrinho btn btn-danger btn-simple btn-xs', 
									'escape' => false
								]) ?>
							</td>
						</tr>
					<?php endforeach; ?>
					<!-- Fim Serviços -->
					<!-- Outros -->
					<tr>
						<th class="text-right">  </th>
						<th class="text-right">  </th>
						<th class="text-right">  </th>
						<th class="text-right">  </th>
						<th class="text-right"> Valor Mensal: </th>
						<th class="text-right valormensaltotal"> </p></th>
						<th class="text-right"> Valor Total: </th>
						<th class="text-right valortotal"> </p></th>
						<th class="text-right"> </th>
					</tr>
					<!-- Fim Outros -->
				</tbody>
			</table>
		</div>
		<button class="btn btn-danger float-right m-b-10 btn-limpacarrinho">Limpar Carrinho</button>
	</div>
</div>
<script>
	valortotal();

    function numberToReal(numero) {
		if(!isNaN(numero)){
			// var numero = numero.toFixed(2).split('.');
			var numero = numero.toFixed(2).split('.');
			numero[0] = numero[0].split(/(?=(?:...)*$)/).join('.');
			return numero.join(',');
		}
	}

/* 	function valortotal(){
        valortotal = 0;
        $('.valordoservico').each(function() {
			valor = $(this).text();
			valor = valor.split('R$').join('');
			valor = valor.replace(".", "");
			valor = valor.replace(",", ".");

			valortotal = valortotal + parseFloat( valor ) ;
        });
        $(".valortotal").html( 'R$ ' + numberToReal(valortotal) );

        valormensaltotal = 0;
        $('.valormensal').each(function() {
			valor = $(this).text();
			valor = valor.split('R$').join('');
			valor = valor.replace(".", "");
			valor = valor.replace(",", ".");

			valormensaltotal = valormensaltotal + parseFloat( valor ) ;
        });
        $(".valormensaltotal").html( 'R$ ' + numberToReal(valormensaltotal) );
	} */

	function valortotal(){
        var valortotal = 0;
        var valormensaltotal = 0;
        $('.valormensal').each(function() {
            var linha = $(this).closest('tr');            
            var strQtde = $(this).prev().text().trim();
            var qtde = 0;            
            if(strQtde.indexOf(':') > -1) {
                var arr = strQtde.split(':');
                qtde = parseFloat(arr[0]) + (parseFloat(arr[1]) / 6 / 10);
            } else {
                qtde = parseFloat(strQtde.replace(/\./g, "").replace(",", ".")) || 0;
            }

            var strMensal = $(this).text().split('R$').join('');
            var vMensal = parseFloat(strMensal.replace(/\./g, "").replace(",", ".")) || 0;
            var strUnit = linha.find('.valorunit').text().split('R$').join('');
            var vUnit = parseFloat(strUnit.replace(/\./g, "").replace(",", ".")) || 0;
            if(vMensal > 0) {
                valormensaltotal += (vMensal * qtde);
            } else {
                valortotal += (vUnit * qtde);
            }
        });

        $(".valortotal").html( 'R$ ' + numberToReal(valortotal) );
        $(".valormensaltotal").html( 'R$ ' + numberToReal(valormensaltotal) );
    }

    $('.excluiitemcarrinho').click(function(e) {
		e.preventDefault();
		id =  $(this).attr('id');
		$.ajax({
			type: "POST",
			url: "<?= Router::url(array('controller'=>'Orcamentos','action'=>'excluiitemcarrinho'));?>/"+id,
			dataType: "html",
			error : function(error) { alert(error); },
			complete: function(data) { carrinho(e); }
		});
    });

	$('.btn-limpacarrinho').click(function(e) {	
		e.preventDefault();
		$.ajax({
			type: "POST",
			url: "<?= Router::url(array('controller'=>'Orcamentos','action'=>'limpacarrinho'));?>",
			dataType: "html",
			error : function(error) { alert(error); },
			complete: function(data) { carrinho(e); }
		});
	});
</script>