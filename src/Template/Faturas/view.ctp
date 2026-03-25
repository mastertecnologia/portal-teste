<?php
	use Cake\Routing\Router;
	$logo = 'pgm.png';

	$pessoaFisica = $fatura->cliente->tipo == C_ClientesTipoFisica;
	$pessoaJuridica = $fatura->cliente->tipo == C_ClientesTipoJuridica;
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
			overflow: visible;
			font-size: 85%;
		}

		.footer-print {
            position: fixed;
            bottom: 0;
            width: 95%;
        }
	}
	
	.table td, .table th {
		padding: 0.2rem;
		vertical-align: top;
		border-top: 1px solid #dee2e6;
		color: black;
	}

	.header-empresa {
		text-align: left;
		margin-top: 2px;
		margin-bottom: 2px;
		font-weight: 500;
	}
	
	.info-cliente {
		text-align: left;
		margin-top: 2px;
		margin-bottom: 2px;
		font-weight: 300;
	}

	.header-empresa-center {
		text-align: center;
		margin-top: 2px;
		margin-bottom: 2px;
		font-weight: 500;
	}

	.header-empresa-right {
		text-align: right;
		margin-top: 2px;
		margin-bottom: 2px;
		font-weight: 500;
	}

	.row-valores {
		background-color: #cbcaca;
	}

	.row-valores p {
		margin-top: 2px;
		margin-bottom: 2px;
		font-weight: 500;
		font-size: 0.8rem;
		text-align: center;
	}

	.infos-minor {
		font-size: 0.8rem;
		text-align: justify;
	}

	.valores {
		font-size: 0.6rem !important;
	}

	.footer-text {
		text-align: center;
		margin-top: 2px;
		margin-bottom: 2px;
		font-weight: 500;
	}

	.signature-field {
		margin-bottom: 15px;
		border: none;
		border-bottom: 1px solid #000;
	}
	
	.border-black {
		border: 1px solid black;
	}
</style>
<div class="row hidden-print">
	<?= $this->html->Link('Imprimir', [], ['id' => 'btn-imprimir', 'class' => 'btn btn-pgm btn-pgm-imprimir btn-orange m-l-5 m-r-5 m-b-5']) ?>
	<?php if($fatura->status == C_LocacaoStatusPendente) {
		echo $this->html->Link('Aprovar', ['action' => 'aprovarhash', $fatura->hash], ['id' => 'btn-aprovar', 'class' => 'btn btn-pgm btn-pgm-salvar btn-success m-b-5']);
		echo $this->html->Link('Rejeitar', ['action' => 'rejeitarhash', $fatura->hash], ['id' => 'btn-rejeitar', 'class' => 'btn btn-danger m-l-5 m-b-5']);
	} else {
		echo LocacaoStatus($fatura->status);
	}
	?>
</div>
<div class="col-md-12">
	<div id="printable">
		<div class="card">
			<div class="card-body">
				<!-- Empresa -->
				<div class="row border-black">
					<div class="col-3 text-center">
						<img src="<?=$this->request->getAttribute('webroot') . 'arquivos/empresas/logotipos/' . $empresaObj->id . '/logo.png' ?>" alt="homepage" style='width: 180px' class='p-l-20 m-t-10'><br>
					</div>
					<div class="col-7 text-center">
						<p class='header-empresa'>
							<?= strtoupper($empresaObj->razaosocial) ?>
						</p>
						<p class='header-empresa'>
							CPNJ: <?= $empresaObj->cnpj ?>
						</p>
						<p class='header-empresa'>
							INSCRIÇÃO ESTADUAL: <?= $empresaObj->inscricaoestadual ?>
						</p>
						<p class='header-empresa'>
							<?= strtoupper($empresaObj->endereco) ?>, <?= strtoupper($empresaObj->nroendereco) ?> - <?= strtoupper($empresaObj->bairro) ?> 
						</p>
						<p class='header-empresa'>
							<?= strtoupper($empresaObj->cidade->nome) ?> - <?= strtoupper($empresaObj->cidade->estado->sigla) ?> - CEP: <?= strtoupper($empresaObj->cep) ?> 
						</p>
						<p class='header-empresa'>
							FONE: <?= $empresaObj->fone ?> SITE: <?= $empresaObj->site ?>
						</p>
						<p class='header-empresa'>
							E-MAIL: <?= $empresaObj->email ?>
						</p>
					</div>
					<div class="col-2 text-center">
						<p class='border-black header-empresa-center'>
							Contrato: <br>
							<?= strtoupper($fatura->nro) ?> 
						</p>
						<br>
						<br>
						<br>
						<br>
						<p class='header-empresa-right'>
							Emissão:
							<?= date_format($fatura->created, 'd/m/Y') ?> 
						</p>
					</div>
				</div>
				<!-- Cliente -->
				<p class='header-empresa'> DESTINATÁRIO DA LOCAÇÃO </p>
				<div class="row border-black">
					<div class="col-12">
						<div class="row">
							<div class="col-6">
								<p class='header-empresa'>
									Nome/Razão Social do Cliente: <br>
									<span class='info-cliente'> <?= $pessoaJuridica ? $fatura->cliente->razaosocial : $fatura->cliente->nome ?> </span>
								</p>
							</div>
							<div class="col-3">
								<p class='header-empresa'>
									CPF/CNPJ do Cliente: <br>
									<span class='info-cliente'> <?= $pessoaJuridica ? $fatura->cliente->cnpj : $fatura->cliente->cpf ?> </span>
								</p>
							</div>
							<div class="col-3">
								<p class='header-empresa'>
									Inscrição Estadual: <br>
									<span class='info-cliente'> <?= $fatura->cliente->inscricaoestadual ?> </span>
								</p>
							</div>
						</div>
						<div class="row">
							<div class="col-4">
								<p class='header-empresa'>
									Endereço: <br>
									<span class='info-cliente'> <?= strtoupper($fatura->cliente->endereco) ?> </span>
								</p>
							</div>
							<div class="col-2">
								<p class='header-empresa'>
									Número: <br>
									<span class='info-cliente'> <?= strtoupper($fatura->cliente->nroendereco) ?> </span>
								</p>
							</div>
							<div class="col-2">
								<p class='header-empresa'>
									Complemento: <br>
									<span class='info-cliente'> <?= strtoupper($fatura->cliente->complemento) ?> </span>
								</p>
							</div>
							<div class="col-2">
								<p class='header-empresa'>
									Bairro: <br>
									<span class='info-cliente'> <?= strtoupper($fatura->cliente->bairro) ?> </span>
								</p>
							</div>
							<div class="col-2">
								<p class='header-empresa'>
									CEP: <br>
									<span class='info-cliente'> <?= strtoupper($fatura->cliente->cep) ?> </span>
								</p>
							</div>
						</div>
						<div class="row">
							<div class="col-4">
								<p class='header-empresa'>
									Município: <br>
									<span class='info-cliente'> <?= strtoupper($fatura->cliente->cidade->nome) ?> </span>
								</p>
							</div>
							<div class="col-3">
								<p class='header-empresa'>
									Telefone: <br>
									<span class='info-cliente'> <?= strtoupper($fatura->cliente->fone) ?> </span>
								</p>
							</div>
							<div class="col-2">
								<p class='header-empresa'>
									UF: <br>
									<span class='info-cliente'> <?= strtoupper($fatura->cliente->cidade->estado->sigla) ?> </span>
								</p>
							</div>
							<div class="col-3">
								<p class='header-empresa'>
									Inscrição Municipal: <br>
									<span class='info-cliente'> <?= strtoupper($fatura->cliente->inscricaomunicipal) ?> </span>
								</p>
							</div>
						</div>
						<div class="row">
							<div class="col-6">
								<p class='header-empresa'>
									E-mail: <br>
									<span class='info-cliente'> <?= strtoupper($fatura->cliente->email) ?> </span>
								</p>
							</div>
							<div class="col-6">
								<p class='header-empresa'>
									Site: <br>
									<span class='info-cliente'> <?= strtoupper($fatura->cliente->site) ?> </span>
								</p>
							</div>
						</div>
						<div class="row">
							<div class="col-12">
								<p class='header-empresa'>
									Classificação do Contrato: <br>
									<?= '' ?>
								</p>
							</div>
						</div>
					</div>
				</div>
				<!-- Datas -->
				<div class="row">
					<div class="col-12">
						<div class="row">
							<div class="col-2">
								<p class='header-empresa'>
									Emissão <br>
									<span class='info-cliente'> <?= date_format($fatura->dtemissao, 'd/m/Y') ?> </span>
								</p>
							</div>
							<div class="col-2">
								<p class='header-empresa'>
									Previsão Retorno <br>
									<span class='info-cliente'> <?= date_format($fatura->dtretorno, 'd/m/Y') ?> </span>
								</p>
							</div>
							<div class="col-2">
								<p class='header-empresa'>
									Data Devolução <br>
									<span class='info-cliente'> <?= date_format($fatura->dtdevolucao, 'd/m/Y') ?> </span>
								</p>
							</div>
							<div class="col-2">
								<p class='header-empresa'>
									Vencimento <br>
									<span class='info-cliente'> <?= date_format($fatura->vencimento, 'd/m/Y') ?> </span>
								</p>
							</div>
						</div>
					</div>
				</div>
				<!-- <p class='header-empresa'> FATURA </p>
				<div class="row">
					<div class="col-12">
						<div class="row">
							<div class="col-2">
								<p class='header-empresa'>
									Nº Fatura <br>
									<span class='info-cliente'> <?= $fatura->nro ?> </span>
								</p>
							</div>
							<div class="col-2">
								<p class='header-empresa'>
									Venc. <br>
									<span class='info-cliente'> <?= date_format($fatura->vencimento, 'd/m/Y') ?> </span>
								</p>
							</div>
							<div class="col-2">
								<p class='header-empresa'>
									Valor <br>
									<span class='info-cliente'> <?= number_format($fatura->valor, 2, ",", ".") ?> </span>
								</p>
							</div>
						</div>
					</div>
				</div> -->
				<!-- Itens -->
				<div class="row">
					<div class="col-12">
						<div class="table-responsive" style='border-top: 3px solid black'>
							<table class="table table-hover table-row-clickable" id="tableCarrinho">
								<thead class="text-primary">
									<th width="10%"> Código </th>
									<th width="40%"> Descrição da Locação </th>
									<th width="10%" class="text-right"> Quantidade </th>
									<th width="10%" class="text-right"> Valor Unitário </th>
									<th width="10%" class="text-right"> Valor Total</th>
								</thead>
								<tbody>
									<!-- Itens -->
									<?php foreach ($carrinho as $reg): ?>
										<tr id='<?= $reg->id ?>'>
											<td id="codigo<?= $reg->id ?>"> <?= $reg->codigo ?> </td>
											<td id="descricao<?= $reg->id ?>"> <?= $reg->descricao ?> </td>
											<td id="quantidade<?= $reg->id ?>" class="text-right"> <?= $reg->quantidade ?> </td>
											<td id="valoritem<?= $reg->id ?>" class="text-right"> <?= 'R$ ' . number_format($reg->valoritem, 2, ",", ".") ?> </td>
											<td id="valortotal<?= $reg->id ?>" class="text-right"> <?= 'R$ ' . number_format($reg->valortotal, 2, ",", ".") ?> </td>
										</tr>
									<?php endforeach; ?>
									<!-- Outros -->
									<tr>
										<th class="text-right"> </th>
										<th class="text-right"> </th>
										<th class="text-right"> </th>
										<th class="text-right"> Descontos: </th>
										<th class="text-right valortotal"> <?= 'R$ ' . number_format($fatura->desconto, 2, ",", ".") ?> </th>
									</tr>
									<tr>
										<th class="text-right"> </th>
										<th class="text-right"> </th>
										<th class="text-right" colspan='2'> Outros Gastos/Frete: </th>
										<th class="text-right valortotal"> <?= 'R$ ' . number_format($fatura->outrosgastos, 2, ",", ".") ?> </th>
									</tr>
									<tr>
										<th class="text-right"> </th>
										<th class="text-right"> </th>
										<th class="text-right"> </th>
										<th class="text-right"> Valor Total: </th>
										<th class="text-right valortotal"> <?= 'R$ ' . number_format($fatura->valor, 2, ",", ".") ?> </th>
									</tr>
									<!-- Fim Outros -->
								</tbody>
							</table>
						</div>
					</div>
				</div>
				<!-- Valores -->
				<div class="row border-black row-valores">
					<div class="col-12">
						<div class="row">
							<div class="col-3">
								<p class='valores'>
									Valor PIS Retido: <br>
									0,00
								</p>
							</div>
							<div class="col-3">
								<p class='valores'>
									Valor CSLL Retido: <br>
									0,00
								</p>
							</div>
							<div class="col-3">
								<p class='valores'>
									Valor Caução: <br>
									0,00
								</p>
							</div>
							<div class="col-3">
								<p class='valores'>
									Total da Fatura: <br>
									<?= number_format($fatura->valor, 2, ",", ".") ?>
								</p>
							</div>
						</div>
						<div class="row">
							<div class="col-3">
								<p class='valores'>
									Valor COFINS Retido: <br>
									0,00
								</p>
							</div>
							<div class="col-3">
								<p class='valores'>
									Valor IR Retido: <br>
									0,00
								</p>
							</div>
							<div class="col-3">
							</div>
							<div class="col-3">
								<p class='valores'>
									Total da Fatura - Retenções - Descontos: <br>
									<span style='font-size:1rem'> <?= number_format($fatura->valor, 2, ",", ".") ?> </span>
								</p>
							</div>
						</div>
					</div>
				</div>
				<!-- <p class='header-empresa'> Val Aprox Tributos R$78,63 (31,45%) Fonte:IBPT </p> -->
				<!-- Outras infos -->
				<p class='infos-minor'>
					EQUIPAMENTO(S) INSTALADO(S) EM: <?= $fatura->local ?>, <br>
					CONTRATO: <?= $fatura->contrato ?> <br>
					REFERENTE: <?= $fatura->referente ?> <br>
					PAGAMENTO: <?= OrdensPagamento($fatura->pagamento) ?> <br>
					TIPO: <?= LocacaoTipo($fatura->tipo) ?> <br>
					DOCUMENTO EMITIDO POR ME OU EPP OPTANTE PELO SIMPLES NACIONAL. NÃO GERA DIREITO A CRÉDITO FISCAL DE IPI. LOCAÇÃO DE BENS MÓVEIS –
					ATIVIDADE IMPOSSIBILIDADE DE EMISSÃO DE NOTA FISCAL. ESTE RECIBO DE LOCAÇÃO É VALIDO COMO DOCUMENTO EQUIVALENTE, NOS TERMOS DO
					ARTIGO 1º DA LEI 8.846/94 E § 1º DESTE ARTIGO. NÃO INCIDÊNCIA DE ISS CONFORME SÚMULA VINCULANTE Nº 31 DO STF: “É INCONSTITUCIONAL A
					INCIDÊNCIA DO IMPOSTO SOBRE SERVIÇOS DE QUALQUER NATUREZA – ISS SOBRE OPERAÇÕES DE LOCAÇÃO DE BENS MÓVEIS.
				</p>
				<!-- Footer -->
				<div class="row border-black footer-print">
					<div class="col-3">
						<p class='footer-text m-t-15'>
							Fatura de Locação: <br>
							<?= strtoupper($fatura->nro) ?> 
						</p>
					</div>
					<div class="col-9" style='border-left: 2px solid black'>
						<div class="row">
							<div class="col-12">
								<p class='footer-text'> Estamos de Acordo com a Emissão desta Fatura: </p>
							</div>
						</div>
						<div class="row">
							<div class="col-6 float-left">
								<span class='footer-text'> BENTO GONÇALVES, </span>
								<input type="text" class="signature-field" style="width: 40%;" value="&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp/&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp/ ">
							</div>
							<div class="col-6 float-left">
								<span class='footer-text'> Assinatura: </span>
								<input type="text" class="signature-field" style="width: 70%;" value="">
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
	$('#btn-imprimir').click(function(e) {
		e.preventDefault();
		$('.hidden-print').hide();
		var $print = $('#printable')
			.clone()
			.addClass('print')
		window.print();
		$('.hidden-print').show();
		$print.remove();
	});

	$('#btn-aprovar, #btn-rejeitar').click(function(e) {
		e.preventDefault();
		var href = $(this).attr('href');
		bootbox.confirm({
			message: 'Confirmar a ação?',
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
</script>