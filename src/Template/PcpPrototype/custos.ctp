<?php $rows = (array)($pcpRows ?? []); ?>
<div class="card">
	<div class="sec-title"><?= h(__('Custos de produção')) ?></div>
	<div class="tbl-wrap">
		<table class="tbl">
			<thead><tr><th><?= h(__('OP')) ?></th><th><?= h(__('Centro')) ?></th><th class="r"><?= h(__('Horas')) ?></th><th class="r"><?= h(__('Custo est.')) ?></th></tr></thead>
			<tbody>
			<?php if ($rows === []) : ?>
				<tr><td colspan="4" style="text-align:center;padding:20px;color:var(--text-muted);"><?= h(__('Sem apontamentos para custeio.')) ?></td></tr>
			<?php else : foreach ($rows as $r) : ?>
				<tr><td><?= h($r['ordem']) ?></td><td><?= h($r['centro']) ?></td><td class="r"><?= h(number_format((float)$r['horas'], 2, ',', '.')) ?></td><td class="r"><?= h('R$ ' . number_format((float)$r['custo'], 2, ',', '.')) ?></td></tr>
			<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
</div>
