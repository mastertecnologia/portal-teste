<div class="est-table-wrap">
	<?php if (!empty($produtos)) : ?>
	<table class="est-table">
		<thead>
			<tr>
				<th class="est-col-check"><input type="checkbox" id="est-check-all" class="est-check" /></th>
				<th>Código</th>
				<th>Descrição</th>
				<th class="est-num">Quantidade Atual</th>
				<th class="est-num">Preço Custo</th>
				<th class="est-num">Preço Venda</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($produtos as $reg) : ?>
			<tr class="est-row" data-codigo="<?= h($reg->sCodProduto) ?>">
				<td class="est-col-check"><input type="checkbox" class="est-check est-check-item" data-codigo="<?= h($reg->sCodProduto) ?>" /></td>
				<td><?= h($reg->sCodProduto) ?></td>
				<td><?= h($reg->sDescProduto) ?></td>
				<td class="est-num"><?= h($reg->nQtdeAtual) ?></td>
				<td class="est-num"><?= number_format((float)$reg->nPrecoCusto, 2, ',', '.') ?></td>
				<td class="est-num"><?= number_format((float)$reg->nPrecoVenda, 2, ',', '.') ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php else : ?>
		<div class="est-empty">Nenhum produto encontrado com os filtros atuais.</div>
	<?php endif; ?>
</div>

<div class="est-footer">
	<div><?= $bApenasComSaldo ? 'Modo: apenas produtos com estoque.' : 'Modo: todos os produtos.' ?></div>
	<div class="est-actions-secondary">
		<span>Total listado: <strong><?= (int)count($produtos ?? []) ?></strong></span>
		<span>Selecionados: <strong id="est-selected-count">0</strong></span>
	</div>
</div>
