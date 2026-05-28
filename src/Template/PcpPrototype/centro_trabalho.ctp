<?php
/** @var array<int,array<string,mixed>> $pcpRows */
$rows = (array)($pcpRows ?? []);
?>
<div class="card">
	<div class="sec-title"><?= h(__('Centros de trabalho')) ?></div>
	<div class="tbl-wrap">
		<table class="tbl">
			<thead><tr><th><?= h(__('Código')) ?></th><th><?= h(__('Nome')) ?></th><th class="r"><?= h(__('Capacidade h/dia')) ?></th><th class="r"><?= h(__('Custo/h')) ?></th><th><?= h(__('Ativo')) ?></th></tr></thead>
			<tbody>
			<?php if ($rows === []) : ?>
				<tr><td colspan="5" style="text-align:center;padding:20px;color:var(--text-muted);"><?= h(__('Nenhum centro cadastrado.')) ?></td></tr>
			<?php else : ?>
				<?php foreach ($rows as $r) : ?>
				<tr>
					<td><?= h($r['codigo']) ?></td>
					<td><?= h($r['nome']) ?></td>
					<td class="r"><?= $r['capacidade'] > 0 ? h(number_format((float)$r['capacidade'], 2, ',', '.')) : '—' ?></td>
					<td class="r"><?= $r['custo_h'] > 0 ? h('R$ ' . number_format((float)$r['custo_h'], 2, ',', '.')) : '—' ?></td>
					<td><?= !empty($r['ativo']) ? '✓' : '—' ?></td>
				</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>
