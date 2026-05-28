<?php $rows = (array)($pcpRows ?? []); ?>
<div class="card">
	<div class="sec-title"><?= h(__('Roteiros de produção')) ?></div>
	<div class="tbl-wrap">
		<table class="tbl">
			<thead><tr><th><?= h(__('Produto')) ?></th><th class="r"><?= h(__('Seq.')) ?></th><th><?= h(__('Operação')) ?></th><th><?= h(__('Centro')) ?></th><th class="r"><?= h(__('Setup min')) ?></th><th class="r"><?= h(__('Run min')) ?></th></tr></thead>
			<tbody>
			<?php if ($rows === []) : ?>
				<tr><td colspan="6" style="text-align:center;padding:20px;color:var(--text-muted);"><?= h(__('Nenhuma operação de roteiro.')) ?></td></tr>
			<?php else : foreach ($rows as $r) : ?>
				<tr><td><?= h($r['produto']) ?></td><td class="r"><?= (int)$r['sequencia'] ?></td><td><?= h($r['operacao']) ?></td><td><?= h($r['centro']) ?></td><td class="r"><?= $r['setup'] > 0 ? h(number_format((float)$r['setup'], 2, ',', '.')) : '—' ?></td><td class="r"><?= $r['run'] > 0 ? h(number_format((float)$r['run'], 2, ',', '.')) : '—' ?></td></tr>
			<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
</div>
