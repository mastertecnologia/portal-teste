<?php

use Cake\Routing\Router;

$this->append('css', $this->Html->css('/css/orcamentos-premium', ['timestamp' => true]));

if ($role == 0) {
	$this->Breadcrumbs->add('Orçamentos', ['controller' => 'Orcamentos', 'action' => 'index'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Editar', ['controller' => 'Orcamentos', 'action' => 'edit', $orcamento->id], ['class' => 'breadcrumb-item']);
}
if ($role == 1) {
	$this->Breadcrumbs->add('Orçamentos', ['controller' => 'Financeiro', 'action' => 'orcamentos'], ['class' => 'breadcrumb-item']);
}
$this->Breadcrumbs->add('Pré-visualização PDF', [], ['class' => 'breadcrumb-item active']);

$versaoLbl = isset($orcVersaoLabel) ? h($orcVersaoLabel) : 'v1';
$emissao = $orcamento->created ? date_format($orcamento->created, 'd/m/Y') : '';
$validadeFmt = '';
if (!empty($orcamento->validoate)) {
	$validadeFmt = date_format(date_create($orcamento->validoate), 'd/m/Y');
}

$nomeCliente = $orcamento->cliente->tipo == C_ClientesTipoJuridica
	? ($orcamento->cliente->razaosocial ?? '')
	: ($orcamento->cliente->nome ?? '');
$docCliente = '';
if ($orcamento->cliente->tipo == C_ClientesTipoJuridica && !empty($orcamento->cliente->cnpj)) {
	$docCliente = 'CNPJ: ' . h($orcamento->cliente->cnpj);
} elseif (!empty($orcamento->cliente->cpf)) {
	$docCliente = 'CPF: ' . h($orcamento->cliente->cpf);
}

$autorNome = ($orcamento->user && !empty($orcamento->user->name)) ? h($orcamento->user->name) : '—';
$autorEmail = '';
if ($orcamento->user) {
	$u = $orcamento->user;
	$autorEmail = (isset($u->email) && (string)$u->email !== '') ? h($u->email) : ((isset($u->username) && (string)$u->username !== '') ? h($u->username) : '');
}

$st = (int)$orcamento->status;
$statusPaper = 'Em andamento';
if ($st === C_OrcamentoStatusEnviado) {
	$statusPaper = 'Enviado ao cliente';
} elseif ($st === C_OrcamentoStatusAprovado) {
	$statusPaper = 'Aprovado';
} elseif ($st === C_OrcamentoStatusRecusado) {
	$statusPaper = 'Recusado';
} elseif ($st === C_OrcamentoStatusArquivado) {
	$statusPaper = 'Arquivado';
}

$emp = $empresaPdf ?? null;
$empNome = $emp && !empty($emp->razaosocial) ? h($emp->razaosocial) : 'PGM Soluções';
$empLinha = '';
if ($emp) {
	$parts = array_filter([
		!empty($emp->cnpj) ? 'CNPJ: ' . h($emp->cnpj) : '',
		!empty($emp->cidade->nome) ? h($emp->cidade->nome) . (!empty($emp->cidade->estado->sigla) ? ', ' . h($emp->cidade->estado->sigla) : '') : '',
	]);
	$empLinha = implode(' · ', $parts);
}
$empContato = '';
if ($emp) {
	$empContato = implode(' · ', array_filter([
		!empty($emp->email) ? h($emp->email) : '',
		!empty($emp->fone) ? h($emp->fone) : '',
	]));
}

$carrinho = $carrinho ?? [];
$totUnico = 0.0;
$totMensal = 0.0;
foreach ($carrinho as $_row) {
	if ((float)($_row->valormensal ?? 0) > 0) {
		$totMensal += (float)$_row->valormensal;
	} else {
		$totUnico += (float)($_row->valordoservico ?? 0);
	}
}
$totGeral = $totUnico + $totMensal;
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<div class="col-md-12 orc-premium-wrap orc-premium-form orc-premium-print">
	<div class="orc-print-toolbar orc-print-no-print">
		<div>
			<div class="orc-print-toolbar-back">
				<?php if ($role == 0) : ?>
					← <?= $this->Html->link('Revisão', ['action' => 'edit', $orcamento->id]) ?>
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
				['id' => 'btn-salvar-pdf', 'class' => 'btn btn-pgm btn-pgm-pdf', 'escape' => false]
			) ?>
			<?php if ($role == 0) : ?>
				<?= $this->Html->link(
					'Enviar & Assinar',
					['action' => 'envioassinatura', $orcamento->id],
					['class' => 'btn btn-pgm btn-pgm-email']
				) ?>
				<?= $this->Html->link(
					'Confirmar e salvar',
					['action' => 'edit', $orcamento->id],
					['class' => 'btn btn-pgm btn-pgm-salvar']
				) ?>
			<?php endif; ?>
			<?= $this->Html->link(
				'Imprimir',
				'#',
				['id' => 'btn-imprimir', 'class' => 'btn btn-pgm btn-pgm-imprimir']
			) ?>
			<?= $this->Html->link(
				'Baixar PDF (servidor)',
				['action' => 'imprimirPdf', $orcamento->id],
				['class' => 'btn btn-secondary btn-sm']
			) ?>
		</div>
	</div>

	<?= $this->element('orcamentos_stepper') ?>

	<div id="printable">
		<div class="orc-paper">
			<div class="orc-paper-head">
				<div>
					<div class="orc-paper-brand">
						<div class="orc-paper-logo" aria-hidden="true">
							<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="#fff" stroke-width="2"><rect x="3" y="3" width="6" height="6" rx="1"/><rect x="11" y="3" width="6" height="6" rx="1"/><rect x="3" y="11" width="6" height="6" rx="1"/><rect x="11" y="11" width="6" height="6" rx="1"/></svg>
						</div>
						<div>
							<div class="orc-paper-co"><?= $empNome ?></div>
							<div class="orc-paper-co-sub">ERP Enterprise</div>
						</div>
					</div>
					<div class="orc-paper-addr">
						<?php if ($empLinha !== '') : ?>
							<?= $empLinha ?><br>
						<?php endif; ?>
						<?php if ($empContato !== '') : ?>
							<?= $empContato ?>
						<?php endif; ?>
					</div>
				</div>
				<div class="orc-paper-right">
					<div class="orc-paper-doc-title">Proposta de Orçamento</div>
					<div class="orc-paper-meta">
						Nº <?= h((string)$orcamento->id) ?> <?= $versaoLbl ?> · <?= h($emissao) ?>
					</div>
					<div class="orc-paper-badges">
						<div class="orc-paper-badge" style="color:#00c08b;">
							<span class="dot" style="background:#00c08b;"></span>
							<?= h($statusPaper) ?>
						</div>
						<?php if ($validadeFmt !== '') : ?>
							<div class="orc-paper-badge" style="color:#ffc107;">
								<span class="dot" style="background:#ffc107;"></span>
								Válido até <?= h($validadeFmt) ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<div class="orc-paper-grid">
				<div class="orc-paper-cell">
					<div class="orc-paper-lbl">Cliente</div>
					<div class="orc-paper-val"><?= h($nomeCliente) ?></div>
					<?php if ($docCliente !== '') : ?>
						<div class="orc-paper-val-sm"><?= $docCliente ?></div>
					<?php endif; ?>
				</div>
				<div class="orc-paper-cell">
					<div class="orc-paper-lbl">Responsável</div>
					<div class="orc-paper-val"><?= $autorNome ?></div>
					<?php if ($autorEmail !== '') : ?>
						<div class="orc-paper-val-sm"><?= $autorEmail ?></div>
					<?php endif; ?>
				</div>
				<div class="orc-paper-cell-full">
					<div>
						<div class="orc-paper-lbl">Pagamento</div>
						<div class="orc-paper-val">Conforme itens (único / mensal)</div>
					</div>
					<div>
						<div class="orc-paper-lbl">Emissão</div>
						<div class="orc-paper-val"><?= h($emissao) ?></div>
					</div>
					<div>
						<div class="orc-paper-lbl">Validade</div>
						<div class="orc-paper-val" style="color:#ffc107;"><?= h($validadeFmt ?: '—') ?></div>
					</div>
				</div>
			</div>

			<table class="orc-ptbl" id="tableCarrinho">
				<thead>
					<tr>
						<th style="width:60px;">Código</th>
						<th>Produto / Serviço</th>
						<th style="width:56px;">Tipo</th>
						<th class="r" style="width:38px;">Qtd.</th>
						<th class="r" style="width:80px;">Vl. Unit.</th>
						<th class="r" style="width:90px;">Vl. Total</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($carrinho as $reg) :
						$tipoLbl = ((int)$reg->tipo === 1) ? 'Hora' : 'Unidade';
						$vlUnit = ($reg->valormensal <= 0 && (float)$reg->valoruni > 0)
							? 'R$ ' . number_format($reg->valoruni, 2, ',', '.')
							: '—';
						$vlTot = ($reg->valormensal > 0)
							? 'R$ ' . number_format($reg->valormensal, 2, ',', '.')
							: (($reg->valordoservico > 0) ? 'R$ ' . number_format($reg->valordoservico, 2, ',', '.') : 'R$ 0,00');
						?>
						<tr id="<?= h((string)$reg->id) ?>">
							<td><?= h((string)$reg->idproduto) ?></td>
							<td class="b"><?= h($reg->servico) ?>
								<?php if (!empty($reg->observacao)) : ?>
									<div style="font-size:9px;color:#666;font-weight:400;margin-top:2px;"><?= h($reg->observacao) ?></div>
								<?php endif; ?>
							</td>
							<td><?= h($tipoLbl) ?></td>
							<td class="r"><?= h((string)$reg->quantidade) ?></td>
							<td class="r"><?= $vlUnit ?></td>
							<td class="r"><?= $vlTot ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<div class="orc-paper-totals">
				<div class="orc-paper-totals-inner">
					<div class="orc-paper-tot-row"><span>Subtotal (pagamento único)</span><span>R$ <?= number_format($totUnico, 2, ',', '.') ?></span></div>
					<div class="orc-paper-tot-row"><span>Total mensal (linhas)</span><span>R$ <?= number_format($totMensal, 2, ',', '.') ?></span></div>
					<div class="orc-paper-tot-row disc"><span>Desconto</span><span>—</span></div>
					<div class="orc-paper-tot-row grand"><span>Total geral</span><span>R$ <?= number_format($totGeral, 2, ',', '.') ?></span></div>
				</div>
			</div>

			<div class="orc-paper-obs area-observacao">
				<?= str_replace(['text-white', 'dark:text-[#EBEBEB]'], '', $orcamento->solicitacao ?? '') ?>
			</div>

			<div class="orc-paper-cond">
				<h4>Condições gerais</h4>
				<div class="orc-paper-cond-grid">
					<div class="orc-paper-cond-item"><span class="i"></span> Proposta válida pelo período indicado.</div>
					<div class="orc-paper-cond-item"><span class="i"></span> Garantia de 12 meses contra defeitos de fabricação, quando aplicável.</div>
					<div class="orc-paper-cond-item"><span class="i"></span> NF emitida após confirmação do pagamento.</div>
					<div class="orc-paper-cond-item"><span class="i"></span> Suporte técnico conforme contrato.</div>
				</div>
			</div>

			<div class="orc-paper-sig">
				<div class="orc-paper-sig-b">
					<div style="height:28px;"></div>
					<div class="orc-paper-sig-line"></div>
					<div style="font-size:11px;font-weight:700;color:#111;"><?= $empNome ?></div>
					<div style="font-size:9px;color:#999;">Fornecedor<?= ($emp && !empty($emp->cnpj)) ? ' · CNPJ: ' . h($emp->cnpj) : '' ?></div>
				</div>
				<div class="orc-paper-sig-b">
					<div style="height:28px;"></div>
					<div class="orc-paper-sig-line"></div>
					<div style="font-size:11px;font-weight:700;color:#111;">Cliente</div>
					<div style="font-size:9px;color:#999;">Contratante</div>
				</div>
			</div>

			<div class="orc-paper-foot">
				<span style="color:#00c08b;font-weight:600;"><?= $empNome ?></span>
				<?php if ($emp && !empty($emp->site)) : ?>
					· <?= h($emp->site) ?>
				<?php endif; ?>
				<?php if ($emp && !empty($emp->email)) : ?>
					· <?= h($emp->email) ?>
				<?php endif; ?>
				<br>
				Documento gerado pelo ERP Enterprise · <?= h($emissao) ?>
			</div>
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
