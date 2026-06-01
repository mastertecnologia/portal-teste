<?php /** @var array<int,array<string,mixed>> $licAuditoria */ $items = (array)($licAuditoria ?? []); ?>
<h1 style="font-size:22px;font-weight:600;margin:0 0 14px;">🛡 <?= h(__('Auditoria do módulo')) ?></h1>
<div class="card" style="padding:0;">
	<table class="tbl">
		<thead><tr><th><?= h(__('Quando')) ?></th><th><?= h(__('Ação')) ?></th><th><?= h(__('Entidade')) ?></th><th><?= h(__('Detalhe')) ?></th><th><?= h(__('User')) ?></th><th>IP</th></tr></thead>
		<tbody>
		<?php if ($items === []) : ?>
		<tr><td colspan="6" style="text-align:center;padding:24px;"><?= h(__('Sem eventos registrados.')) ?></td></tr>
		<?php else : foreach ($items as $ev) : ?>
		<tr>
			<td><?= h($ev['created']) ?></td>
			<td><?= h($ev['acao']) ?></td>
			<td><?= h($ev['entidade']) ?><?= (int)$ev['entidade_id'] > 0 ? ' #' . (int)$ev['entidade_id'] : '' ?></td>
			<td><?= h($ev['detalhe'] ?: '—') ?></td>
			<td><?= (int)$ev['iduser'] > 0 ? (int)$ev['iduser'] : '—' ?></td>
			<td><?= h($ev['ip'] ?: '—') ?></td>
		</tr>
		<?php endforeach; endif; ?>
		</tbody>
	</table>
</div>
