<?php
/** @var array<int,array<string,mixed>> $pcpRows */
$rows = (array)($pcpRows ?? []);
?>
<div class="card">
	<div class="sec-title"><?= h(__('Estrutura BOM')) ?></div>
	<div class="tbl-wrap">
		<table class="tbl">
			<thead><tr><th><?= h(__('Produto pai')) ?></th><th><?= h(__('Componente')) ?></th><th class="r"><?= h(__('Qtd')) ?></th><th class="r"><?= h(__('Scrap %')) ?></th></tr></thead>
			<tbody>
			<?php if ($rows === []) : ?>
				<tr><td colspan="4" style="text-align:center;padding:20px;color:var(--text-muted);"><?= h(__('Nenhum item BOM.')) ?></td></tr>
			<?php else : ?>
				<?php foreach ($rows as $r) : ?>
				<tr><td><?= h($r['parent']) ?></td><td><?= h($r['child']) ?></td><td class="r"><?= h(number_format((float)$r['qtd'], 4, ',', '.')) ?></td><td class="r"><?= h(number_format((float)$r['scrap'], 2, ',', '.')) ?></td></tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>
