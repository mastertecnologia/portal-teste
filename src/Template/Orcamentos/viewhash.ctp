<?php
  	use Cake\Routing\Router;
	$this->Breadcrumbs->add('Orçamentos', ['controller' => 'Orcamentos', 'action' => 'index'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Visualizar Orçamento', [], ['class' => 'breadcrumb-item active']);

	$dval = date_format(date_create($orcamento['validoate']), "d/m/Y");
	$orcamento['validoate'] = $dval;
?>
<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<h3 class='text-center'>Proposta de Orçamento</h3>
			<div class="row m-t-20">
				<div class="col-md-3 col-xs-12">
					<p class="h4"> <b> Autor </b> </p>
					<p class="h4"><?= $orcamento->user->name ?></p>
				</div>
				<div class="col-md-3 col-xs-12">
					<p class="h4"> <b> Cliente </b> </p>
					<p class="h4"><?= $orcamento->cliente->tipo == C_ClientesTipoJuridica ? $orcamento->cliente->razaosocial : $orcamento->cliente->nome ?></p>
				</div>
				<div class="col-md-3 col-xs-12">
					<p class="h4"> <b> Validade </b> </p>
					<p class="h4"><?= $orcamento->validoate ?></p>
				</div>
				<div class="col-md-3 col-xs-12">
					<p class="h4"> <b> Status </b> </p>
					<h5> <?= orcamentoStatus($orcamento->status) ?> </h5>
				</div>
			</div>
			<div class="row m-t-10">
				<div class="col-12">
					<p class="h4"> <b> Observação </b> </p>
					<p class="h4"><?= $orcamento->solicitacao ?></p>
				</div>
			</div>
			<div class="row m-t-10">
                <div class="col-12">
                    <h3 class='titulo bg-dark text-white text-center p-2'> Produtos e Serviços </h3><br>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table" id="tableCarrinho">
                    <thead class="text-primary">
                        <th width="6%">Código</th>
                        <th width="20%">Produto/Serviço</th>
                        <th width="23%">Descrição</th>
                        <th width="10%" class="text-right">Pagamento</th>
                        <th width="10%" class="text-right">Qtde.</th>
                        <th width="10%" class="text-right">Vl. Mensal</th>
                        <th width="10%" class="text-right">Vl. Unit.</th>
                        <th width="10%" class="text-right">Valor Total</th>
                    </thead>
                    <tbody>
                        <!-- Serviços -->
                        <?php if(isset($carrinho)){ foreach($carrinho as $reg){ ?>
							<?php 
								if ($reg->valoruni > 0) $mensal = $reg->valoruni;
								else $mensal = (float) $reg->valormensal / $reg->quantidade;
							?>
                            <tr id='<?= $reg->id ?>'>
                                <td><?= $reg->idproduto ?></td>
                                <td><?= $reg->servico ?></td>
                                <td><?= $reg->observacao ?></td>
                                <td class="text-right"> <?= $reg->valormensal > 0 ? 'Mensal' : 'Único' ?> </td>
                                <td class="text-right"><?= $reg->quantidade ?></td>
                                <td class="text-right valormensal"><?= 'R$ ' . number_format($reg->valormensal, 2, ",", ".") ?></td>
                                <td class="text-right valorunit"><?= 'R$ ' . number_format($mensal, 2, ",", ".") ?></td>
                                <td class="text-right valordoservico"><?= 'R$ ' . number_format($reg->valordoservico, 2, ",", ".") ?></td>
                            </tr>
                        <?php } } ?>
                        <!-- Fim Serviços -->
                        <!-- Outros -->
                        <tr>
                            <th class="text-right">  </th>
                            <th class="text-right">  </th>
                            <th class="text-right">  </th>
                            <th class="text-right">  </th>
                            <th class="text-right"> Pagamento Mensal: </th>
                            <th class="text-right valormensaltotal"> </p></th>
                            <th class="text-right"> Pagamento Único: </th>
                            <th class="text-right valortotal"> </p></th>
                        </tr>
                        <!-- Fim Outros -->
                    </tbody>
                </table>
				<?php if($orcamento->status == C_OrcamentoStatusEnviado) echo $this->Html->Link('Aprovar', ['action' => 'aprovarhash', $orcamento->hash], ['class' => 'btn btn-aprovar btn-pgm btn-pgm-salvar btn-success float-right m-t-20']); ?>
            </div>
		</div>
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

	$('.btn-aprovar').click(function(e) {
		e.preventDefault();
		href = $(this).attr('href');
		bootbox.confirm({
			message: "Você confirma a aprovação do orçamento?",
			buttons: {
				confirm: { label: 'Sim', className: 'btn btn-pgm btn-pgm-salvar btn-success' },
				cancel: { label: 'Cancelar', className: 'btn-danger' }
			},
			callback: function (result) {
				if(result === true) window.location = href;
			}
		});
	})

	$('.tempoestimado').each(function() {
		var body = $( this ).html(); 
		var h = body.search("h");
		var min = body.search("min");
		if(h < 0 && min < 0) $( this ).text( $( this ).text() + 'h');
	})

	function valortotal(){
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
	}

	valortotal();
</script>