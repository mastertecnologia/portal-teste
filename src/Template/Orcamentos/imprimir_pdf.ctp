<?php
$logo = 'pgm.png';
$logoFile = WWW_ROOT . 'assets' . DS . 'images' . DS . $logo;
$logoSrc = '';
if (is_file($logoFile)) {
	$logoSrc = 'data:image/png;base64,' . base64_encode(file_get_contents($logoFile));
}

$totalMensal = 0;
$totalUnico = 0;
if (!empty($carrinho)) {
	foreach ($carrinho as $reg) {
		$q = (float)$reg->quantidade;
		$vm = (float)($reg->valormensal ?? 0);
		$vu = (float)($reg->valoruni ?? 0);
		if ($vm > 0) {
			$totalMensal += $vm * $q;
		} else {
			$totalUnico += $vu * $q;
		}
	}
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<style>
		body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
		h2, h3 { background: #343a40; color: #fff; text-align: center; padding: 6px; margin: 10px 0 8px; font-size: 14px; }
		table { width: 100%; border-collapse: collapse; }
		.table-dados th, .table-dados td { border: 1px solid #ccc; padding: 5px 8px; text-align: left; }
		.table-items th, .table-items td { border: 1px solid #ccc; padding: 4px 6px; }
		.table-items th { background: #f0f0f0; font-size: 10px; }
		.text-right { text-align: right; }
		.area-observacao { padding: 8px 12px; font-size: 11px; }
		.rodape-data { text-align: right; margin-top: 20px; }
	</style>
</head>
<body>
	<h2>Proposta de Orçamento</h2>
	<table class="table-dados" style="margin-bottom: 12px;">
		<tr>
			<td style="width: 22%; vertical-align: top;">
				<?php if ($logoSrc) : ?>
					<img src="<?= $logoSrc ?>" style="width: 110px;" alt="">
				<?php endif; ?>
			</td>
			<td style="vertical-align: top;">
				<table class="table-dados" style="margin: 0;">
					<tr>
						<th style="width: 30%;">Nº do Orçamento</th>
						<td><?= h($orcamento->id) ?></td>
					</tr>
					<tr>
						<th>Cliente</th>
						<td><?= h($orcamento->cliente->tipo == C_ClientesTipoJuridica ? $orcamento->cliente->razaosocial : $orcamento->cliente->nome) ?></td>
					</tr>
					<tr>
						<th>Cidade</th>
						<td><?= !empty($orcamento->cliente->cidade) ? h($orcamento->cliente->cidade->nome) : 'Não informada' ?></td>
					</tr>
					<tr>
						<th>Validade</th>
						<td><?= date_format(date_create($orcamento->validoate), 'd/m/Y') ?></td>
					</tr>
				</table>
			</td>
		</tr>
	</table>

	<h3>Observação</h3>
	<div class="area-observacao">
		<?= str_replace(['text-white', 'dark:text-[#EBEBEB]'], '', $orcamento->solicitacao) ?>
	</div>

	<h3>Produtos e Serviços</h3>
	<table class="table-items">
		<thead>
			<tr>
				<th width="7%">Cód.</th>
				<th width="18%">Produto/Serviço</th>
				<th width="22%">Descrição</th>
				<th width="10%" class="text-right">Pagamento</th>
				<th width="8%" class="text-right">Qtde.</th>
				<th width="11%" class="text-right">Vl. Mensal</th>
				<th width="11%" class="text-right">Vl. Unit.</th>
				<th width="13%" class="text-right">Valor Total</th>
			</tr>
		</thead>
		<tbody>
			<?php if (!empty($carrinho)) : ?>
				<?php foreach ($carrinho as $reg) : ?>
					<tr>
						<td><?= h($reg->idproduto) ?></td>
						<td><?= h($reg->servico) ?></td>
						<td><?= h($reg->observacao) ?></td>
						<td class="text-right"><?= ($reg->valormensal > 0) ? 'Mensal' : 'Único' ?></td>
						<td class="text-right"><?= h($reg->quantidade) ?></td>
						<td class="text-right"><?= $reg->valormensal > 0 ? 'R$ ' . number_format($reg->valormensal, 2, ',', '.') : 'R$ 0,00' ?></td>
						<td class="text-right"><?= ($reg->valormensal <= 0 && $reg->valoruni > 0) ? 'R$ ' . number_format($reg->valoruni, 2, ',', '.') : 'R$ 0,00' ?></td>
						<td class="text-right"><?= ($reg->valormensal <= 0 && $reg->valordoservico > 0) ? 'R$ ' . number_format($reg->valordoservico, 2, ',', '.') : 'R$ 0,00' ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			<tr>
				<td colspan="4"></td>
				<td class="text-right"><strong>Pagamento Mensal:</strong></td>
				<td class="text-right"><strong>R$ <?= number_format($totalMensal, 2, ',', '.') ?></strong></td>
				<td class="text-right"><strong>Pagamento Único:</strong></td>
				<td class="text-right"><strong>R$ <?= number_format($totalUnico, 2, ',', '.') ?></strong></td>
			</tr>
		</tbody>
	</table>

	<div class="rodape-data">
		<p style="margin: 2px 0;">Bento Gonçalves, <?= @date_format($orcamento->created, 'd') . ' de ' . descricaoMes($orcamento->created, 1) . ' de ' . @date_format($orcamento->created, 'Y') ?></p>
		<p style="margin: 2px 0;">Obrigado pela sua atenção,</p>
		<p style="margin: 2px 0;"><?= h($orcamento->user->name) ?></p>
	</div>
</body>
</html>
