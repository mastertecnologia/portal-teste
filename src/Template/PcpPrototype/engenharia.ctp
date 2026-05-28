<?php
/** @var array<int,array<string,mixed>> $pcpRows */
$rows = (array)($pcpRows ?? []);
?>
<div class="card">
	<div class="sec-title"><?= h(__('Fichas técnicas')) ?></div>
	<div class="tbl-wrap">
		<table class="tbl">
			<thead><tr><th><?= h(__('Código')) ?></th><th><?= h(__('Rev.')) ?></th><th><?= h(__('Produto')) ?></th><th><?= h(__('Status')) ?></th></tr></thead>
			<tbody>
			<?php if ($rows === []) : ?>
				<tr><td colspan="4" style="text-align:center;padding:20px;color:var(--text-muted);"><?= h(__('Nenhuma ficha cadastrada.')) ?></td></tr>
			<?php else : ?>
				<?php foreach ($rows as $r) : ?>
				<tr><td><?= h($r['codigo']) ?></td><td><?= h($r['revisao']) ?></td><td><?= h($r['produto']) ?></td><td><span class="badge b-env"><?= h($r['status']) ?></span></td></tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>
