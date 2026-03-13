<?php

use Cake\Routing\Router;
// Breadcumbs
if ($role == 0) $this->Breadcrumbs->add('Orçamentos', ['controller' => 'Orcamentos', 'action' => 'index'], ['class' => 'breadcrumb-item']);
if ($role == 0) $this->Breadcrumbs->add('Editar', ['controller' => 'Orcamentos', 'action' => 'edit', $orcamento->id], ['class' => 'breadcrumb-item']);
if ($role == 1) $this->Breadcrumbs->add('Orçamentos', ['controller' => 'Financeiro', 'action' => 'orcamentos'], ['class' => 'breadcrumb-item']);

$this->Breadcrumbs->add('Imprimir Orçamento', [], ['class' => 'breadcrumb-item active']);

$logo = 'pgm.png';
$logoClaro = 'pgm2.png';
$logoEscuro = 'pgm3.png';
error_reporting(0);
?>
<link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet">

<style>
	body {
		font-family: 'Open Sans', sans-serif;
		background: white;
	}

	.table td,
	.table th {
		padding: 0.8rem;
		vertical-align: top;
		border-top: 1px solid #dee2e6;
		font-size: 12px;
	}

	.titulo {
		background-color: #343a40 !important;
		color: white !important;
		text-align: center;
		padding: 8px;
		margin: 10px 0;
	}

	/* Estilos para Impressão Física (Ctrl+P / A4) */
	@media print {
		@page {
			size: A4 portrait;
			margin: 1cm;
			/* Margem segura controlada via código */
		}

		/* 1. Ocultamos fisicamente os menus em vez de apenas deixá-los invisíveis */
		.topbar,
		.left-sidebar,
		.sidebar,
		header,
		aside,
		footer,
		.page-titles,
		.navbar,
		.breadcrumb,
		.btn,
		#btn-imprimir,
		#btn-salvar-pdf {
			display: none !important;
		}

		/* 2. Zeramos paddings e margins das caixas principais */
		body,
		.page-wrapper,
		.main-wrapper,
		.container-fluid,
		.col-md-12,
		.row {
			margin: 0 !important;
			padding: 0 !important;
			background-color: #fff !important;
		}

		/* 3. Posicionamento relativo impede que a tabela fuja da folha com as margens */
		#printable {
			position: relative !important;
			width: 100% !important;
			margin: 0 !important;
			padding: 0 !important;
			left: auto !important;
			top: auto !important;
			visibility: visible !important;
		}

		body * {
			visibility: hidden;
		}

		#printable,
		#printable * {
			visibility: visible;
		}

		#printable .card {
			border: none !important;
			box-shadow: none !important;
			margin: 0 !important;
			padding: 0 !important;
		}

		#printable .card-body {
			padding: 0 !important;
		}

		.table-responsive {
			overflow: visible !important;
		}

		table {
			page-break-inside: auto;
			width: 100% !important;
		}

		tr {
			page-break-inside: avoid;
			page-break-after: auto;
		}

		thead {
			display: table-header-group;
		}

		.titulo {
			background-color: #343a40 !important;
			color: white !important;
			-webkit-print-color-adjust: exact;
			print-color-adjust: exact;
		}

		.area-observacao, 
		.area-observacao * {
			color: #000000 !important;
			background-color: transparent !important;
			-webkit-text-fill-color: #000000 !important;
		}
		
	}
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<div class="col-md-12">
	<?= $this->html->Link('Imprimir', [], ['id' => 'btn-imprimir', 'class' => 'btn btn-orange m-l-5 m-b-5']) ?>
	<?= $this->html->Link('Salvar PDF', [], ['id' => 'btn-salvar-pdf', 'class' => 'btn btn-success m-l-5 m-b-5']) ?>
	<?php if ($role == 0) echo $this->Html->link('Voltar para o Orçamento', ["action" => "edit", $orcamento->id], ['class' => 'm-b-5 btn btn-info']); ?>
	<?php if ($role == 1) echo $this->Html->link('Voltar', ['controller' => 'Financeiro', 'action' => 'orcamentos'], ['class' => 'm-b-5 btn btn-info']); ?>
	<div id="printable">
		<div class="card">
			<div class="card-body">
				<h2 class='titulo bg-dark text-white text-center p-2'> Proposta de Orçamento </h2><br>
				<div class="row">
					<div class="col-3 text-center">
						<img src="<?= $this->request->getAttribute('webroot') . 'assets/images/' . $logo ?>" alt="homepage" style='width: 140px' class='p-l-20 m-t-10'><br>
					</div>
					<div class="col-9 text-center">
						<table class="table table-dados m-t-10" id='table-imprimir'>
							<tbody>
								<tr>
									<th width='30%' class='text-left'>Nº do Orçamento</th>
									<td class='text-left'> <?= $orcamento->id ?></td>
								</tr>
								<tr>
									<th width='30%' class='text-left'>Cliente</th>
									<td class='text-left'> <?= $orcamento->cliente->tipo == C_ClientesTipoJuridica ? $orcamento->cliente->razaosocial : $orcamento->cliente->nome ?></td>
								</tr>

								<tr>
									<th width='30%' class='text-left'>Cidade</th>
									<td class='text-left'>
										<?= !empty($orcamento->cliente->cidade) ? $orcamento->cliente->cidade->nome : 'Não informada' ?>
									</td>
								</tr>
								<tr>
									<th width='30%' class='text-left'>Validade</th>
									<td class='cnpj text-left'> <?= date_format(date_create($orcamento->validoate), "d/m/Y"); ?></td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
				<div class="row m-t-10">
					<div class="col-12">
						<h3 class='titulo bg-dark text-center p-2'> Observação </h3><br>
						<div class='m-l-40 m-r-40 area-observacao' style="font-size: 14px;">
							<?= str_replace(['text-white', 'dark:text-[#EBEBEB]'], '', $orcamento->solicitacao) ?>
						</div>
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
							<?php if (isset($carrinho)) {
								foreach ($carrinho as $reg) { ?>
									<tr id='<?= $reg->id ?>'>
										<td><?= $reg->idproduto ?></td>
										<td><?= $reg->servico ?></td>
										<td><?= $reg->observacao ?></td>

										<td class="text-right">
											<?= ($reg->valormensal > 0) ? 'Mensal' : 'Único' ?>
										</td>

										<td class="text-right"><?= $reg->quantidade ?></td>

										<td class="text-right valormensal">
											<?= $reg->valormensal > 0 ? 'R$ ' . number_format($reg->valormensal, 2, ",", ".") : 'R$ 0,00' ?>
										</td>

										<td class="text-right valorunit">
											<?= ($reg->valormensal <= 0 && $reg->valoruni > 0) ? 'R$ ' . number_format($reg->valoruni, 2, ",", ".") : 'R$ 0,00' ?>
										</td>

										<td class="text-right valordoservico">
											<?= ($reg->valormensal <= 0 && $reg->valordoservico > 0) ? 'R$ ' . number_format($reg->valordoservico, 2, ",", ".") : 'R$ 0,00' ?>
										</td>
									</tr>
							<?php }
							} ?>
							<!-- Fim Serviços -->
							<!-- Outros -->
							<tr>
								<th class="text-right"> </th>
								<th class="text-right"> </th>
								<th class="text-right"> </th>
								<th class="text-right"> </th>
								<th class="text-right"> Pagamento Mensal: </th>
								<th class="text-right valormensaltotal">
									</p>
								</th>
								<th class="text-right"> Pagamento Único: </th>
								<th class="text-right valortotal">
									</p>
								</th>
							</tr>
							<!-- Fim Outros -->
						</tbody>
					</table>
				</div>
				<p style="width:1000px"></p>
				<br>
				<div class="float-right">
					<p class='m-b-0 text-right'>Bento Gonçalves, <?= @date_format($orcamento->created, 'd') . ' de ' . descricaoMes($orcamento->created, 1) . ' de ' . @date_format($orcamento->created, 'Y') ?></p>
					<p class='m-b-0 text-right'>Obrigado pela sua atenção,</p>
					<p class='m-b-0 text-right'><?= $orcamento->user->name ?></p>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
	function numberToReal(numero) {
		if (!isNaN(numero)) {
			var numero = numero.toFixed(2).split('.');
			numero[0] = numero[0].split(/(?=(?:...)*$)/).join('.');
			return numero.join(',');
		}
	}


	function valortotal() {
		var valortotal = 0;
		var valormensaltotal = 0;

		$('.valormensal').each(function() {
			var linha = $(this).closest('tr');
			var strQtde = $(this).prev().text().trim();
			var qtde = 0;
			if (strQtde.indexOf(':') > -1) {
				var arr = strQtde.split(':');
				qtde = parseFloat(arr[0]) + (parseFloat(arr[1]) / 6 / 10);
			} else {
				qtde = parseFloat(strQtde.replace(/\./g, "").replace(",", ".")) || 0;
			}
			var strMensal = $(this).text().split('R$').join('');
			var vMensal = parseFloat(strMensal.replace(/\./g, "").replace(",", ".")) || 0;
			var strUnit = linha.find('.valorunit').text().split('R$').join('');
			var vUnit = parseFloat(strUnit.replace(/\./g, "").replace(",", ".")) || 0;
			if (vMensal > 0) {
				valormensaltotal += (vMensal * qtde);
			} else {
				valortotal += (vUnit * qtde);
			}
		});

		$(".valortotal").html('R$ ' + numberToReal(valortotal));
		$(".valormensaltotal").html('R$ ' + numberToReal(valormensaltotal));
	}

	valortotal();

	$('#btn-imprimir').click(function(e) {
		e.preventDefault();
		$('.titulo').removeClass('bg-dark').removeClass('text-white');
		if ($("body").hasClass("mini-sidebar")) $("#printable").removeClass('printMini');
		else $("#printable").addClass('printMini');
		var $print = $('#printable')
			.clone()
			.addClass('print')
		window.print();
		$('.titulo').addClass('bg-dark').addClass('text-white');
		$print.remove();
	});

	$('.tempoestimado').each(function() {
		var body = $(this).html();
		var h = body.search("h");
		var min = body.search("min");
		if (h < 0 && min < 0) $(this).text($(this).text() + 'h');
	})


	function gerarPDF() {
		const elemento = document.getElementById('printable');
		const tituloOriginal = document.title;
		document.title = "Orcamento_<?= $orcamento->id ?>";
		elemento.classList.add('pdf-mode');
		const style = document.createElement('style');
		style.id = 'temp-pdf-style';
		style.innerHTML = `
			#printable.pdf-mode {
				width: 850px !important;
				max-width: 850px !important;
				margin: 0 !important;
				padding: 15px !important;
				background: white !important;
				position: relative !important;
				left: 0 !important;
				top: 0 !important;
			}
			#printable.pdf-mode .table td,
			#printable.pdf-mode .table th {
				font-size: 11px !important;
				padding: 5px !important;
			}
			#printable.pdf-mode .area-observacao,
            #printable.pdf-mode .area-observacao * {
                color: #000000 !important;
            }
		`;
		document.head.appendChild(style);

		html2canvas(elemento, {
			scale: 2,
			backgroundColor: '#ffffff',
			allowTaint: false,
			useCORS: true,
			windowWidth: 850,
			logging: false,
			onclone: function(clonedDoc) {
				const clonedElement = clonedDoc.getElementById('printable');
				if (clonedElement) {
					clonedElement.style.margin = '0';
					clonedElement.style.padding = '15px';
					clonedElement.style.position = 'relative';
					clonedElement.style.top = '0';
					clonedElement.style.left = '0';
					clonedElement.style.width = '850px';
				}
			}
		}).then(canvas => {
			elemento.classList.remove('pdf-mode');
			const tempStyle = document.getElementById('temp-pdf-style');
			if (tempStyle) tempStyle.remove();

			const imgData = canvas.toDataURL('image/png');

			const pdf = new jspdf.jsPDF({
				orientation: 'portrait',
				unit: 'px',
				format: 'a4',
				hotfixes: ["px_scaling"]
			});

			const pdfWidth = pdf.internal.pageSize.getWidth();
			const pdfHeight = pdf.internal.pageSize.getHeight();
			const imgWidth = canvas.width;
			const imgHeight = canvas.height;

			const scale = pdfWidth / imgWidth;
			const height = imgHeight * scale;

			if (height > pdfHeight) {
				const scaleHeight = pdfHeight / imgHeight;
				const width = imgWidth * scaleHeight;
				const x = (pdfWidth - width) / 2;

				pdf.addImage(imgData, 'PNG', x, 0, width, pdfHeight);
			} else {
				pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, height);
			}

			pdf.save(`Orcamento_<?= $orcamento->id ?>.pdf`);
			document.title = tituloOriginal;
		}).catch(error => {
			console.error('Erro:', error);
			alert('Erro ao gerar PDF. Tente novamente.');
			elemento.classList.remove('pdf-mode');
			const tempStyle = document.getElementById('temp-pdf-style');
			if (tempStyle) tempStyle.remove();
		});
	}

	// Evento do botão Salvar PDF
	$('#btn-salvar-pdf').click(function(e) {
		e.preventDefault();
		gerarPDF();
	});
</script>