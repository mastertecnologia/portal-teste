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
	}
	
	.table td, .table th {
		padding: 0.2rem;
		vertical-align: top;
		border-top: 1px solid #dee2e6;
		color: black;
	}

	.header-empresa {
		text-align: center;
		font-weight: 500;
		font-size: 32px;
	}
	
	.info-recibo {
		text-align: left;
		font-weight: 500;
		margin-bottom: 3px;
		font-size: 20px;
	}

	.info-recibo-r {
		text-align: right;
		font-weight: 500;
		margin-bottom: 3px;
		font-size: 20px;
	}

	.info-recibo-w-300 {
		text-align: left;
		margin-top: 2px;
		margin-bottom: 2px;
		font-weight: 300;
	}

	.info-recibo-valor-total {
		font-size: 25px;
	}

	.infos-minor {
		font-size: 0.8rem;
		text-align: justify;
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
	<?= $this->Html->link('Imprimir', '#', ['id' => 'btn-imprimir', 'class' => 'btn btn-pgm btn-pgm-imprimir btn-orange m-l-5 m-b-5']) ?>
	<?php if($role == 0) echo $this->Html->link('Voltar para a locação', ["action" => "edit", $fatura->id], ['class' => 'm-b-5 btn btn-pgm btn-pgm-situacao btn-info']); ?>
</div>
<div class="col-md-12">
	<div id="printable">
		<div class="card">
			<div class="card-body">
				<!-- Header -->
				<div class="row">
					<div class="col-3">
						<img src="<?=$this->request->getAttribute('webroot') . 'arquivos/empresas/logotipos/' . $empresaObj->id . '/logo.png' ?>" alt="homepage" style='width: 180px' class='p-l-20 m-t-10'><br>
					</div>
					<div class="col-6">
						<p class='header-empresa'>
							RECIBO
						</p>
					</div>
					<div class="col-3">
					</div>
				</div>
				<!-- Infos -->
				<div class="row m-t-10">
					<div class="col-8">
						<p class='info-recibo'>
							Recibo:
						</p>
						<p class='info-recibo'>
							Data: <span class='info-recibo-w-300'> <?= date_format($recibo->datarecebimento, 'd/m/Y') ?> </span>
						</p>
						<p class='info-recibo'>
							Recebemos de: <span class='info-recibo-w-300'> <?= $pessoaJuridica ? $fatura->cliente->razaosocial : $fatura->cliente->nome ?> </span>
						</p>
						<p class='info-recibo'>
							CPF/CNPJ: <span class='info-recibo-w-300'> <?= $pessoaJuridica ? $fatura->cliente->cnpj : $fatura->cliente->cpf ?> </span>
						</p>
						<p class='info-recibo'>
							A Importância de: <span class='info-recibo-w-300'> R$<?= number_format($recibo->valorpago, 2, ",", ".") . ' (' . convert_number_to_words($recibo->valorpago) . ')' ?> </span>
						</p>
						<p class='info-recibo'>
							Referente: <span class='info-recibo-w-300'> <?= $fatura->nro ?> </span>
						</p>
					</div>
					<div class="col-4 info-recibo-r">
						<div class="row">
							<div class="col-6">
								Valor Bruto:
							</div>
							<div class="col-6">
								<span class='info-recibo-w-300'> <?= number_format($fatura->valor, 2, ",", ".") ?> </span>
							</div>
						</div>
						<div class="row">
							<div class="col-6">
								Juros:
							</div>
							<div class="col-6">
								<span class='info-recibo-w-300'> <?= number_format($recibo->juros, 2, ",", ".") ?> </span>
							</div>
						</div>
						<div class="row">
							<div class="col-6">
								Descontos:
							</div>
							<div class="col-6">
								<span class='info-recibo-w-300'> <?= number_format($recibo->desconto, 2, ",", ".") ?> </span>
							</div>
						</div>
						<hr>
						<div class="row">
							<div class="col-6">
								Valor Total:
							</div>
							<div class="col-6">
								<span class='info-recibo-valor-total'> <?= number_format($recibo->valorpago, 2, ",", ".") ?> </span>
							</div>
						</div>
					</div>
				</div>
				<!-- Footer -->
				<div class="row border-black footer-print">
					<div class="col-3">
						<p class='footer-text m-t-15'>
							Recibo de Locação: <br>
							<?= strtoupper($fatura->nro) . ' | ' . $recibo->nro ?> 
						</p>
					</div>
					<div class="col-9" style='border-left: 2px solid black'>
						<div class="row">
							<div class="col-12">
								<p class='footer-text'> Estamos de Acordo com a Emissão deste recibo: </p>
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
</script>