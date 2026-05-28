<?php $rows = (array)($pcpRows ?? []); ?>
<div class="card">
	<div class="sec-title"><?= h(__('Pedidos de compra')) ?></div>
	<div class="tbl-wrap">
		<table class="tbl">
			<thead><tr><th><?= h(__('Número')) ?></th><th><?= h(__('Descrição')) ?></th><th class="r"><?= h(__('Qtd')) ?></th><th><?= h(__('Status')) ?></th><th><?= h(__('Data')) ?></th></tr></thead>
			<tbody>
			<?php if ($rows === []) : ?>
				<tr><td colspan="5" style="text-align:center;padding:20px;color:var(--text-muted);"><?= h(__('Nenhum registro.')) ?></td></tr>
			<?php else : foreach ($rows as $r) : ?>
				<tr>
					<td><?= h($r['numero']) ?></td>
					<td><?= h($r['descricao']) ?></td>
					<td class="r"><?= h(number_format((float)$r['quantidade'], 4, ',', '.')) ?></td>
					<td><span class="badge b-env"><?= h($r['status']) ?></span></td>
					<td><?= !empty($r['created']) && $r['created'] instanceof \DateTimeInterface ? h($r['created']->format('d/m/Y')) : '—' ?></td>
				</tr>
			<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
</div>
