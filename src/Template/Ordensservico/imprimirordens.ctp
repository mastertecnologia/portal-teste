<?php
	use Cake\Routing\Router;
	$this->Breadcrumbs->add('Ordens de Serviço', ['controller' => 'Ordensservico', 'action' => 'index'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Imprimir Ordens de Serviço', [], ['class' => 'breadcrumb-item active']);
	
	$logo = 'pgm.png';

	function Mask($mask,$str){
		$str = str_replace(" ","",$str);
		for($i=0;$i<strlen($str);$i++) $mask[strpos($mask,"#")] = $str[$i];
		return $mask;
	}
?>
<link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet">
<style>
	@media print {
		body * , .main-wrapper{ visibility: hidden; }
		#printable, #printable * { visibility: visible; }
		#printable, button  * { visibility: hidden; }
		.topbar, .page-titles, .navbar{
			visibility: hidden;
			overflow: hidden;
		}
		.page-wrapper{ padding: 0; }
		#printable {
			position: relative;
			margin-top: -165px;
			margin-left: -100px;
			overflow: visible;
			height: 105%;
			width: 112%;
			font-size: 85%;
		}
		#printable.printMini {
			position: relative;
			margin-top: -165px;
			margin-left: -40px;
			overflow: visible;
			height: 105%;
			width: 106%;
			font-size: 85%;
		}
		
		table { page-break-inside:auto }
		tr    { page-break-inside:avoid; page-break-after:auto }
		thead { /* margin-top:-200; */ display:table-header-group }
		.quebrapaginas {page-break-after: always !important; }
	}

	.table td, .table th {
		padding: 0.7rem;
		vertical-align: top;
		border-top: 1px solid #dee2e6;
	}
	
</style>

<div class="col-md-12">
	<?= $this->Html->Link('Imprimir', [], ['id' => 'btn-imprimir', 'class' => 'btn btn-pgm btn-pgm-imprimir btn-orange m-l-5 m-b-5']) ?>
	<?= $this->Html->link('Voltar', ["action" => "index"], ['class' => 'm-b-5 btn btn-pgm btn-pgm-situacao btn-info']); ?>
	<div id="printable">
		<?php foreach($ordens as $ordem) { ?>
			<div class="card">
				<div class="card-body">
					<h2 class='titulo bg-dark text-white text-center p-2'> Ordem de Serviço </h2><br>
					<div class="row">
						<div class="col-3 text-center">
							<img src="<?=$this->request->getAttribute('webroot') . 'assets/images/' . $logo ?>" alt="homepage" style='width: 140px' class='p-l-20 m-t-10'><br>
						</div>
						<div class="col-3 text-center">
							<table class="table m-t-10">
								<tbody>
									<tr>
										<th width='30%' class='text-left'>Cliente</th>
										<td class='text-left'> <?= $ordem->cliente->tipo == C_ClientesTipoJuridica ? $ordem->cliente->razaosocial : $ordem->cliente->nome ?></td>            
									</tr>
									<tr>
										<th width='30%' class='text-left'>Nº da Ordem</th>
										<td class='text-left'> <?= $ordem->id ?></td> 
									</tr>
									<tr>
										<th width='30%' class='text-left'>Previsão</th>
										<td class='text-left'> <?= date_format($ordem->dataprevisao, "d/m/Y"); ?></td> 
									</tr>
								</tbody>
							</table>
						</div>
						<div class="col-3 text-center">
							<table class="table m-t-10">
								<tbody>
									<tr>
										<th width='30%' class='text-left'><?= $ordem->cliente->tipo == C_ClientesTipoJuridica ? 'CNPJ' : 'CPF' ?></th>
										<td class='text-left'> <?= $ordem->cliente->tipo == C_ClientesTipoJuridica ? formatCnpjCpf($ordem->cliente->cnpj) : formatCnpjCpf($ordem->cliente->cpf) ?></td>            
									</tr>
									<tr>
										<th width='30%' class='text-left'>Endereço</th>
										<td class='text-left'> <?= $ordem->cliente->endereco . ' - ' . $ordem->cliente->nroendereco . ' Bairro: ' . $ordem->cliente->bairro ?></td> 
									</tr>
									<tr>
										<th width='30%' class='text-left'>CEP</th>
										<td class='text-left'> <?= Mask("#####-###",$ordem->cliente->cep) ?></td> 
									</tr>
								</tbody>
							</table>
						</div>
						<div class="col-3 text-center">
							<table class="table m-t-10">
								<tbody>
									<tr>
										<th width='30%' class='text-left'>Telefone</th>
										<td class='text-left'><?php if(!empty($ordem->cliente->fone)) echo Mask("(###) ####-####",$ordem->cliente->fone).'<br>'; if(!empty($ordem->cliente->fone2))echo Mask("(###) #####-####",$ordem->cliente->fone2) ?></td> 
									</tr>
									<tr>
										<th width='30%' class='text-left'>Cidade</th>
										<td class='text-left'> <?= $cidades[$ordem->id]->nome ?></td> 
									</tr>
									<tr>
										<th width='30%' class='text-left'>Estado</th>
										<td class='text-left'> <?= $estados[$ordem->id]->nome ?></td> 
									</tr>
								</tbody>
							</table>
						</div>
					</div>
					<br>
					<div class="row">
						<div class="col-12">
							<h3 class='titulo bg-dark text-white text-center p-2'> Relato </h3>
							<h5 class='m-l-40 m-r-40'><?= $ordem->relato ?></h5>
							<?php if($ordem->observacao != null){ ?>
							<h5 class='m-l-40 m-r-40'><?= 'Observação: '.$ordem->observacao ?></h5>
							<?php } ?>
						</div>
					</div>
					<br>
					<div class="row">
						<div class="col-12">
							<h3 class='titulo bg-dark text-white text-center p-2'> Produtos e Serviços </h3><br>
						</div>
					</div>
					<!-- Tabela -->
					<div class="table-responsive">
						<table class="table" id="tableCarrinho">
							<thead class="text-primary">
								<th>Tipo</th>
								<th>Código</th>
								<th>Descrição</th>
								<th>Observação</th>
								<th>Unidade</th>
								<th width="7%" class="text-right">Qtde.</th>
								<th width="7%" class="text-right">Vl. Unitário</th>
								<th width="7%" class="text-right">Vl. Desconto</th>
								<th width="7%" class="text-right">Valor Total</th>
								<th>Serial Number</th>
							</thead>
							<tbody>
								<!-- Serviços -->
								<?php if(isset($carrinhos[$ordem->id])){ foreach($carrinhos[$ordem->id] as $reg){ ?>
									<tr id='<?= $reg->id ?>'>
										<td><?= ProdutosTipo($reg->tipo) ?></td>
										<td><?= $reg->codproduto ?></td>
										<td><?= $reg->descricao ?></td>
										<td><?= $reg->observacao ?></td>
										<td><?= $reg->unidade ?></td>
										<td class="text-right"><?= $reg->quantidade ?></td>
										<td class="text-right"><?= 'R$ ' . number_format($reg->valorunitario, 2, ",", ".") ?></td>
										<td class="text-right"><?= 'R$ ' . number_format($reg->valordesconto, 2, ",", ".") ?></td>
										<td class="text-right valordoservico" data-id='<?= $ordem->id ?>'><?= 'R$ ' . number_format($reg->valortotal, 2, ",", ".") ?></td>
										<td><?= $reg->serialnumber ?></td>
									</tr>
								<?php } } ?>
								<!-- Fim Serviços -->
							</tbody>
						</table>
					</div>
					<!-- valortotal que é exibido -->
					<h5 class="text-right font-weight-bold m-r-15 hide valortotalh5"> Total Geral: <span class="text-success valortotal<?= $ordem->id ?>"> </span></h5>
					<br>
					<div class="float-right">
						<p class='m-b-0 text-right'>Bento Gonçalves, <?=  @date_format($ordem->dataabertura, 'd') . ' de ' . descricaoMes($ordem->dataabertura, 1) . ' de ' . @date_format($ordem->dataabertura, 'Y') ?></p>
						<p class='m-b-0 text-right'>Obrigado pela sua atenção,</p>
						<p class='m-b-0 text-right'><?= $ordem->user->name ?></p>
					</div>
				</div>
			</div>
			<div class="quebrapaginas"></div>
		<?php } ?>
	</div>
</div>
<script>
	function numberToReal(numero) {
		if(!isNaN(numero)){
			// var numero = numero.toFixed(2).split('.');
			var numero = numero.toFixed(2).split('.');
			numero[0] = numero[0].split(/(?=(?:...)*$)/).join('.');
			return numero.join(',');
		}
	}

	function valortotal(){
		valores = [];
		$('.valordoservico').each(function() {
			ordem = $(this).attr('data-id');
			console.log(ordem);
			if(typeof valores[ordem] === 'undefined') valores[ordem] = 0;
			valor = $(this).text();
			valor = valor.split('R$').join('');
			valor = valor.replaceAll(".", "").replaceAll(",", ".");
			valores[ordem] += parseFloat( valor );
			$('.valortotalh5').show();
		});
		$.each(valores, function(key, value) {
			classe = '.valortotal'+key;
			$(classe).html( 'R$ ' + numberToReal(value) );
		})
	}

	valortotal();
	imprimir();

	$('#btn-imprimir').click(function(e) {
		e.preventDefault();
		imprimir();
	});

	function imprimir() {
		$('.titulo').removeClass('bg-dark').removeClass('text-white');
		if ($("body").hasClass("mini-sidebar")) $("#printable").removeClass('printMini');
		else $("#printable").addClass('printMini');
		var $print = $('#printable')
			.clone()
			.addClass('print')
		window.print();
		$('.titulo').addClass('bg-dark').addClass('text-white');
		$print.remove();
	}
</script>