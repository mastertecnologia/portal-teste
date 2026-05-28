<?php $rows = (array)($pcpRows ?? []); ?>
<div class="card">
	<div class="sec-title"><?= h(__('Expedição')) ?></div>
	<div class="tbl-wrap">
		<table class="tbl">
			<thead><tr><th><?= h(__('OP')) ?></th><th><?= h(__('Produto')) ?></th><th class="r"><?= h(__('Qtd')) ?></th><th><?= h(__('Status')) ?></th></tr></thead>
			<tbody>
			<?php if ($rows === []) : ?>
				<tr><td colspan="4" style="text-align:center;padding:20px;color:var(--text-muted);"><?= h(__('Nenhuma OP pronta para expedição.')) ?></td></tr>
			<?php else : foreach ($rows as $r) : ?>
				<tr><td><?= h($r['numero']) ?></td><td><?= h($r['produto']) ?></td><td class="r"><?= h(number_format((float)$r['quantidade'], 2, ',', '.')) ?></td><td><?= h($r['status']) ?></td></tr>
			<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
</div>
