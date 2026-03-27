<?php
$geradoEm = date('d/m/Y H:i');
$escopo = (string)($escopo ?? '');
$codigosSelecionados = (array)($codigosSelecionados ?? []);
$escopoLabel = 'Listagem atual';
if ($escopo === 'selecionados') {
	$escopoLabel = 'Produtos selecionados';
} elseif ($escopo === 'item') {
	$escopoLabel = 'Produto unico';
}
?>
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
h1 { margin: 0 0 4px 0; font-size: 18px; }
.meta { margin-bottom: 12px; color: #444; font-size: 10px; }
table { width: 100%; border-collapse: collapse; }
th, td { border: 1px solid #d5d5d5; padding: 6px 7px; }
th { background: #f2f2f2; text-transform: uppercase; font-size: 9px; letter-spacing: .04em; }
td.num { text-align: right; }
.foot { margin-top: 10px; color: #444; font-size: 10px; }
</style>

<h1>Relatório de Estoque</h1>
<div class="meta">
	<strong>Gerado em:</strong> <?= h($geradoEm) ?><br>
	<strong>Escopo:</strong> <?= h($escopoLabel) ?><br>
	<strong>Filtro:</strong> <?= !empty($bApenasComSaldo) ? 'Apenas produtos com estoque' : 'Todos os produtos' ?><br>
	<strong>Código:</strong> <?= $sCodProduto !== null && $sCodProduto !== '' ? h($sCodProduto) : 'Todos' ?> |
	<strong>Descrição:</strong> <?= !empty($sDescricao) ? h($sDescricao) : 'Todas' ?>
	<?php if (!empty($codigosSelecionados)) : ?><br>
	<strong>Códigos selecionados:</strong> <?= h(implode(', ', $codigosSelecionados)) ?>
	<?php endif; ?>
</div>

<table>
	<thead>
		<tr>
			<th style="width: 11%;">Código</th>
			<th>Descrição</th>
			<th style="width: 12%;">Quantidade Atual</th>
			<th style="width: 12%;">Preço Custo</th>
			<th style="width: 12%;">Preço Venda</th>
		</tr>
	</thead>
	<tbody>
		<?php if (!empty($produtos)) : ?>
			<?php foreach ($produtos as $reg) : ?>
				<tr>
					<td><?= h($reg->sCodProduto) ?></td>
					<td><?= h($reg->sDescProduto) ?></td>
					<td class="num"><?= h($reg->nQtdeAtual) ?></td>
					<td class="num"><?= number_format((float)$reg->nPrecoCusto, 2, ',', '.') ?></td>
					<td class="num"><?= number_format((float)$reg->nPrecoVenda, 2, ',', '.') ?></td>
				</tr>
			<?php endforeach; ?>
		<?php else : ?>
			<tr>
				<td colspan="5" style="text-align:center;">Nenhum produto encontrado para os filtros informados.</td>
			</tr>
		<?php endif; ?>
	</tbody>
</table>

<div class="foot">
	Total de itens listados: <strong><?= (int)count($produtos ?? []) ?></strong>
</div>
