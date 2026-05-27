<?php

$this->append('css', $this->element('pgm_premium_css', ['name' => 'orcamentos-premium']));

if ($role == 0) {
	$this->Breadcrumbs->add('Orçamentos', ['controller' => 'Orcamentos', 'action' => 'index'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Dados da proposta', ['controller' => 'Orcamentos', 'action' => 'dados', $orcamento->id], ['class' => 'breadcrumb-item']);
}
if ($role == 1) {
	$this->Breadcrumbs->add('Orçamentos', ['controller' => 'Financeiro', 'action' => 'orcamentos'], ['class' => 'breadcrumb-item']);
}
$this->Breadcrumbs->add('Pré-visualização PDF', [], ['class' => 'breadcrumb-item active']);
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<div class="col-md-12 orc-premium-page-root">
<div class="orc-premium-wrap orc-premium-form orc-premium-print">
	<div class="orc-print-toolbar orc-print-no-print">
		<div>
			<div class="orc-print-toolbar-back">
				<?php if ($role == 0) : ?>
					← <?= $this->Html->link('Revisão', ['action' => 'view', $orcamento->id], ['data-turbo' => 'false']) ?>
				<?php else : ?>
					← <?= $this->Html->link('Voltar', ['controller' => 'Financeiro', 'action' => 'orcamentos']) ?>
				<?php endif; ?>
			</div>
			<h1>Pré-visualização PDF</h1>
		</div>
		<div class="orc-print-actions">
			<?= $this->Html->link(
				'<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13" aria-hidden="true"><path d="M2 11v2a1 1 0 001 1h10a1 1 0 001-1v-2"/><polyline points="8 2 8 10"/><polyline points="5 7 8 10 11 7"/></svg> Exportar PDF',
				'#',
				['id' => 'btn-salvar-pdf', 'class' => 'btn btn-orc-outline-teal btn-orc-compact', 'escape' => false]
			) ?>
			<?php if ($role == 0) : ?>
				<?= $this->Html->link(
					'Enviar & Assinar',
					['action' => 'envioassinatura', $orcamento->id],
					['class' => 'btn btn-orc-outline-purple btn-orc-compact']
				) ?>
				<?= $this->Html->link(
					'Confirmar e salvar',
					['action' => 'dados', $orcamento->id],
					['class' => 'btn btn-orc-premium-primary btn-orc-compact', 'data-turbo' => 'false']
				) ?>
			<?php endif; ?>
			<?= $this->Html->link(
				'Imprimir',
				'#',
				['id' => 'btn-imprimir', 'class' => 'btn btn-orc-form-secondary btn-orc-compact']
			) ?>
			<?= $this->Html->link(
				'Baixar PDF (servidor)',
				['action' => 'imprimirPdf', $orcamento->id],
				['class' => 'btn btn-orc-form-secondary btn-orc-compact']
			) ?>
		</div>
	</div>

	<?= $this->element('orcamentos_stepper') ?>

	<div id="printable">
		<?= $this->element('orcamentos_imprimir_paper') ?>
	</div>
</div>
</div>
<script>
	$('#btn-imprimir').click(function(e) {
		e.preventDefault();
		window.print();
	});

	function gerarPDF() {
		const elemento = document.getElementById('printable');
		const tituloOriginal = document.title;
		document.title = "Orcamento_<?= (int)$orcamento->id ?>";
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
			#printable.pdf-mode .orc-ptbl td,
			#printable.pdf-mode .orc-ptbl th {
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

			pdf.save(`Orcamento_<?= (int)$orcamento->id ?>.pdf`);
			document.title = tituloOriginal;
		}).catch(error => {
			console.error('Erro:', error);
			alert('Erro ao gerar PDF. Tente novamente.');
			elemento.classList.remove('pdf-mode');
			const tempStyle = document.getElementById('temp-pdf-style');
			if (tempStyle) tempStyle.remove();
		});
	}

	$('#btn-salvar-pdf').click(function(e) {
		e.preventDefault();
		gerarPDF();
	});
</script>
