<?php
/**
 * Documento “papel” da proposta (pré-visualização e PDF).
 * Variáveis: orcamento, carrinho, empresaPdf, orcVersaoLabel; opcional pdf (bool) para layout mPDF.
 */
use App\Utility\OrcamentoDescontoUtil;

$pdf = !empty($pdf);
$versaoLbl = isset($orcVersaoLabel) ? h((string)$orcVersaoLabel) : 'v1';
$emissao = $orcamento->created ? date_format($orcamento->created, 'd/m/Y') : '';
$validadeFmt = '';
if (!empty($orcamento->validoate)) {
	$validadeFmt = pgm_format_date_br($orcamento->validoate);
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

$formaPagamentoPaper = !empty($orcamento->formapagamento)
	? h($orcamento->formapagamento)
	: 'Conforme itens (único / mensal)';

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
$empNome = 'PGM Soluções';
if ($emp) {
	if (!empty($emp->nomefantasia)) {
		$empNome = h($emp->nomefantasia);
	} elseif (!empty($emp->razaosocial)) {
		$empNome = h($emp->razaosocial);
	}
}
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
$temDescItemImp = false;
if (!empty($carrinho[0]) && property_exists($carrinho[0], 'desconto_valor')) {
	$temDescItemImp = true;
}
foreach ($carrinho as $_row) {
	$liquido = OrcamentoDescontoUtil::linhaLiquido($_row, $temDescItemImp);
	if ((float)($_row->valormensal ?? 0) > 0) {
		$totMensal += $liquido;
	} else {
		$totUnico += $liquido;
	}
}
$totGeral = $totUnico + $totMensal;
$solicitacaoHtml = str_replace(['text-white', 'dark:text-[#EBEBEB]'], '', $orcamento->solicitacao ?? '');
?>

<div class="orc-paper">
	<?php if ($pdf) : ?>
	<table class="orc-pdf-head" width="100%" cellpadding="0" cellspacing="0">
		<tr>
			<td width="58%" valign="top" style="padding-bottom:14px;border-bottom:2.5px solid #00c08b;">
				<table cellpadding="0" cellspacing="0"><tr>
					<td valign="middle" style="padding-right:8px;">
						<div class="orc-paper-logo" aria-hidden="true">
							<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="#fff" stroke-width="2"><rect x="3" y="3" width="6" height="6" rx="1"/><rect x="11" y="3" width="6" height="6" rx="1"/><rect x="3" y="11" width="6" height="6" rx="1"/><rect x="11" y="11" width="6" height="6" rx="1"/></svg>
						</div>
					</td>
					<td valign="middle">
						<div class="orc-paper-co"><?= $empNome ?></div>
						<div class="orc-paper-co-sub">ERP Enterprise</div>
					</td>
				</tr></table>
				<div class="orc-paper-addr" style="margin-top:6px;">
					<?php if ($empLinha !== '') : ?><?= $empLinha ?><br><?php endif; ?>
					<?php if ($empContato !== '') : ?><?= $empContato ?><?php endif; ?>
				</div>
			</td>
			<td width="42%" valign="top" align="right" style="padding-bottom:14px;border-bottom:2.5px solid #00c08b;">
				<div class="orc-paper-doc-title">Proposta de Orçamento</div>
				<div class="orc-paper-meta">Nº <?= h((string)$orcamento->id) ?> <?= $versaoLbl ?> · <?= h($emissao) ?></div>
				<div style="margin-top:8px;font-size:10px;">
					<div class="orc-paper-badge orc-paper-badge--teal"><span class="dot"></span><?= h($statusPaper) ?></div>
					<?php if ($validadeFmt !== '') : ?>
						<div class="orc-paper-badge orc-paper-badge--amber" style="margin-top:4px;"><span class="dot"></span>Válido até <?= h($validadeFmt) ?></div>
					<?php endif; ?>
				</div>
			</td>
		</tr>
	</table>
	<?php else : ?>
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
				<?php if ($empLinha !== '') : ?><?= $empLinha ?><br><?php endif; ?>
				<?php if ($empContato !== '') : ?><?= $empContato ?><?php endif; ?>
			</div>
		</div>
		<div class="orc-paper-right">
			<div class="orc-paper-doc-title">Proposta de Orçamento</div>
			<div class="orc-paper-meta">Nº <?= h((string)$orcamento->id) ?> <?= $versaoLbl ?> · <?= h($emissao) ?></div>
			<div class="orc-paper-badges">
				<div class="orc-paper-badge orc-paper-badge--teal"><span class="dot"></span><?= h($statusPaper) ?></div>
				<?php if ($validadeFmt !== '') : ?>
					<div class="orc-paper-badge orc-paper-badge--amber"><span class="dot"></span>Válido até <?= h($validadeFmt) ?></div>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php endif; ?>

	<?php if ($pdf) : ?>
	<table class="orc-pdf-info" width="100%" cellpadding="0" cellspacing="0" style="margin:18px 0 20px;border:0.5px solid #e5e5e5;border-radius:8px;border-collapse:separate;">
		<tr>
			<td width="50%" valign="top" style="padding:11px 15px;border-right:0.5px solid #e5e5e5;border-bottom:0.5px solid #e5e5e5;">
				<div class="orc-paper-lbl">Cliente</div>
				<div class="orc-paper-val"><?= h($nomeCliente) ?></div>
				<?php if ($docCliente !== '') : ?><div class="orc-paper-val-sm"><?= $docCliente ?></div><?php endif; ?>
			</td>
			<td width="50%" valign="top" style="padding:11px 15px;border-bottom:0.5px solid #e5e5e5;">
				<div class="orc-paper-lbl">Responsável</div>
				<div class="orc-paper-val"><?= $autorNome ?></div>
				<?php if ($autorEmail !== '') : ?><div class="orc-paper-val-sm"><?= $autorEmail ?></div><?php endif; ?>
			</td>
		</tr>
		<tr>
			<td width="33%" valign="top" style="padding:11px 15px;border-right:0.5px solid #e5e5e5;">
				<div class="orc-paper-lbl">Pagamento</div>
				<div class="orc-paper-val"><?= $formaPagamentoPaper ?></div>
			</td>
			<td width="33%" valign="top" style="padding:11px 15px;border-right:0.5px solid #e5e5e5;">
				<div class="orc-paper-lbl">Emissão</div>
				<div class="orc-paper-val"><?= h($emissao) ?></div>
			</td>
			<td width="34%" valign="top" style="padding:11px 15px;">
				<div class="orc-paper-lbl">Validade</div>
				<div class="orc-paper-val orc-paper-val--amber"><?= h($validadeFmt ?: '—') ?></div>
			</td>
		</tr>
	</table>
	<?php else : ?>
	<div class="orc-paper-grid">
		<div class="orc-paper-cell">
			<div class="orc-paper-lbl">Cliente</div>
			<div class="orc-paper-val"><?= h($nomeCliente) ?></div>
			<?php if ($docCliente !== '') : ?><div class="orc-paper-val-sm"><?= $docCliente ?></div><?php endif; ?>
		</div>
		<div class="orc-paper-cell">
			<div class="orc-paper-lbl">Responsável</div>
			<div class="orc-paper-val"><?= $autorNome ?></div>
			<?php if ($autorEmail !== '') : ?><div class="orc-paper-val-sm"><?= $autorEmail ?></div><?php endif; ?>
		</div>
		<div class="orc-paper-cell-full">
			<div>
				<div class="orc-paper-lbl">Pagamento</div>
				<div class="orc-paper-val"><?= $formaPagamentoPaper ?></div>
			</div>
			<div>
				<div class="orc-paper-lbl">Emissão</div>
				<div class="orc-paper-val"><?= h($emissao) ?></div>
			</div>
			<div>
				<div class="orc-paper-lbl">Validade</div>
				<div class="orc-paper-val orc-paper-val--amber"><?= h($validadeFmt ?: '—') ?></div>
			</div>
		</div>
	</div>
	<?php endif; ?>

	<table class="orc-ptbl" id="tableCarrinho" width="100%" cellpadding="0" cellspacing="0">
		<thead>
			<tr>
				<th class="orc-ptbl-w60">Código</th>
				<th>Produto / Serviço</th>
				<th class="orc-ptbl-w56">Tipo</th>
				<th class="r orc-ptbl-w38">Qtd.</th>
				<th class="r orc-ptbl-w80">Vl. Unit.</th>
				<th class="r orc-ptbl-w90">Vl. Total</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($carrinho as $reg) :
				$tipoLbl = ((int)$reg->tipo === 1) ? 'Hora' : 'Unidade';
				$vlUnit = ($reg->valormensal <= 0 && (float)$reg->valoruni > 0)
					? 'R$ ' . number_format($reg->valoruni, 2, ',', '.')
					: '—';
				$vlTotLinha = OrcamentoDescontoUtil::linhaLiquido($reg, $temDescItemImp);
				$vlTot = 'R$ ' . number_format($vlTotLinha, 2, ',', '.');
				?>
				<tr>
					<td><?= h((string)$reg->idproduto) ?></td>
					<td class="b"><?= h($reg->servico) ?>
						<?php if (!empty($reg->observacao)) : ?>
							<div class="orc-ptbl-item-obs"><?= h($reg->observacao) ?></div>
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

	<?php if (trim(strip_tags($solicitacaoHtml)) !== '') : ?>
		<div class="orc-paper-obs area-observacao"><?= $solicitacaoHtml ?></div>
	<?php endif; ?>

	<div class="orc-paper-cond">
		<h4>Condições gerais</h4>
		<?php if ($pdf) : ?>
		<table width="100%" cellpadding="2" cellspacing="0" class="orc-pdf-cond">
			<tr>
				<td width="50%" valign="top"><span class="i"></span> Proposta válida pelo período indicado.</td>
				<td width="50%" valign="top"><span class="i"></span> Garantia de 12 meses contra defeitos de fabricação, quando aplicável.</td>
			</tr>
			<tr>
				<td valign="top"><span class="i"></span> NF emitida após confirmação do pagamento.</td>
				<td valign="top"><span class="i"></span> Suporte técnico conforme contrato.</td>
			</tr>
		</table>
		<?php else : ?>
		<div class="orc-paper-cond-grid">
			<div class="orc-paper-cond-item"><span class="i"></span> Proposta válida pelo período indicado.</div>
			<div class="orc-paper-cond-item"><span class="i"></span> Garantia de 12 meses contra defeitos de fabricação, quando aplicável.</div>
			<div class="orc-paper-cond-item"><span class="i"></span> NF emitida após confirmação do pagamento.</div>
			<div class="orc-paper-cond-item"><span class="i"></span> Suporte técnico conforme contrato.</div>
		</div>
		<?php endif; ?>
	</div>

	<?php if ($pdf) : ?>
	<table width="100%" cellpadding="0" cellspacing="0" style="margin-top:24px;">
		<tr>
			<td width="50%" align="center" valign="bottom">
				<div style="border-top:0.5px solid #bbb;padding-top:6px;margin-top:30px;">
					<div class="orc-paper-sig-name"><?= $empNome ?></div>
					<div class="orc-paper-sig-role">Fornecedor<?= ($emp && !empty($emp->cnpj)) ? ' · CNPJ: ' . h($emp->cnpj) : '' ?></div>
				</div>
			</td>
			<td width="50%" align="center" valign="bottom">
				<div style="border-top:0.5px solid #bbb;padding-top:6px;margin-top:30px;">
					<div class="orc-paper-sig-name">Cliente</div>
					<div class="orc-paper-sig-role">Contratante</div>
				</div>
			</td>
		</tr>
	</table>
	<?php else : ?>
	<div class="orc-paper-sig">
		<div class="orc-paper-sig-b">
			<div class="orc-paper-sig-spacer" aria-hidden="true"></div>
			<div class="orc-paper-sig-line"></div>
			<div class="orc-paper-sig-name"><?= $empNome ?></div>
			<div class="orc-paper-sig-role">Fornecedor<?= ($emp && !empty($emp->cnpj)) ? ' · CNPJ: ' . h($emp->cnpj) : '' ?></div>
		</div>
		<div class="orc-paper-sig-b">
			<div class="orc-paper-sig-spacer" aria-hidden="true"></div>
			<div class="orc-paper-sig-line"></div>
			<div class="orc-paper-sig-name">Cliente</div>
			<div class="orc-paper-sig-role">Contratante</div>
		</div>
	</div>
	<?php endif; ?>

	<div class="orc-paper-foot">
		<span class="orc-paper-foot-em"><?= $empNome ?></span>
		<?php if ($emp && !empty($emp->site)) : ?> · <?= h($emp->site) ?><?php endif; ?>
		<?php if ($emp && !empty($emp->email)) : ?> · <?= h($emp->email) ?><?php endif; ?>
		<br>
		Documento gerado pelo ERP Enterprise · <?= h($emissao) ?>
	</div>
</div>
