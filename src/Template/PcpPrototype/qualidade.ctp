<?php $rows = (array)($pcpRows ?? []); ?>
<div class="card">
	<div class="sec-title"><?= h(__('Qualidade · refugo')) ?></div>
	<div class="tbl-wrap">
		<table class="tbl">
			<thead><tr><th><?= h(__('OP')) ?></th><th><?= h(__('Operação')) ?></th><th><?= h(__('Data')) ?></th><th class="r"><?= h(__('Boa')) ?></th><th class="r"><?= h(__('Refugo')) ?></th></tr></thead>
			<tbody>
			<?php if ($rows === []) : ?>
				<tr><td colspan="5" style="text-align:center;padding:20px;color:var(--text-muted);"><?= h(__('Sem registros de refugo.')) ?></td></tr>
			<?php else : foreach ($rows as $r) : ?>
				<tr>
					<td><?= h($r['ordem']) ?></td><td><?= h($r['operacao']) ?></td>
					<td><?= $r['inicio'] instanceof \DateTimeInterface ? h($r['inicio']->format('d/m/Y H:i')) : '—' ?></td>
					<td class="r"><?= h(number_format((float)$r['boa'], 2, ',', '.')) ?></td>
					<td class="r"><?= h(number_format((float)$r['refugo'], 2, ',', '.')) ?></td>
				</tr>
			<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
</div>
