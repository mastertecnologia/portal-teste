<?php $rows = (array)($pcpRows ?? []); ?>
<div class="card">
	<div class="sec-title"><?= h(__('Cronograma de produção')) ?></div>
	<div class="tbl-wrap">
		<table class="tbl">
			<thead><tr><th><?= h(__('OP')) ?></th><th><?= h(__('Produto')) ?></th><th><?= h(__('Início prev.')) ?></th><th><?= h(__('Fim prev.')) ?></th><th><?= h(__('Status')) ?></th></tr></thead>
			<tbody>
			<?php if ($rows === []) : ?>
				<tr><td colspan="5" style="text-align:center;padding:20px;color:var(--text-muted);"><?= h(__('Sem ordens para exibir.')) ?></td></tr>
			<?php else : foreach ($rows as $r) : ?>
				<tr>
					<td><span class="titulo-num"><?= h($r['numero']) ?></span></td>
					<td><?= h($r['produto']) ?></td>
					<td><?= $r['inicio'] instanceof \DateTimeInterface ? h($r['inicio']->format('d/m/Y')) : '—' ?></td>
					<td><?= $r['fim'] instanceof \DateTimeInterface ? h($r['fim']->format('d/m/Y')) : '—' ?></td>
					<td><span class="badge b-env"><?= h($r['status']) ?></span></td>
				</tr>
			<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
</div>
