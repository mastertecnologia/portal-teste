<?php /** @var array<string,mixed> $lic @var array<int,array<string,mixed>> $licHistorico */ ?>
<h1 style="font-size:22px;font-weight:600;margin:0 0 14px;">🕐 <?= h(__('Histórico da licença')) ?> · <?= h($lic['codigo'] ?? '') ?></h1>
<div class="card" style="padding:0;margin-bottom:14px;">
	<table class="tbl">
		<thead><tr><th><?= h(__('Quando')) ?></th><th><?= h(__('Ação')) ?></th><th><?= h(__('Detalhe')) ?></th><th><?= h(__('User')) ?></th></tr></thead>
		<tbody>
		<?php $items = (array)($licHistorico ?? []); ?>
		<?php if ($items === []) : ?>
		<tr><td colspan="4" style="text-align:center;padding:24px;"><?= h(__('Sem eventos de auditoria para esta licença.')) ?></td></tr>
		<?php else : foreach ($items as $ev) : ?>
		<tr>
			<td><?= h($ev['created']) ?></td>
			<td><?= h($ev['acao']) ?></td>
			<td><?= h($ev['detalhe'] ?: '—') ?></td>
			<td><?= (int)($ev['iduser'] ?? 0) > 0 ? (int)$ev['iduser'] : '—' ?></td>
		</tr>
		<?php endforeach; endif; ?>
		</tbody>
	</table>
</div>
<p>
	<?= $this->Html->link(__('Voltar ao detalhe'), ['action' => 'licencaDetalhe', (int)($lic['id'] ?? 0)], ['class' => 'btn btn-ghost btn-sm']) ?>
</p>
