<?php
/**
 * @var array<int,array<string,mixed>> $pcpOrdens
 * @var array<string,int> $pcpKpi
 * @var bool $pcpMigrationHint
 */
$ordens = (array)($pcpOrdens ?? []);
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<h1 style="font-size:22px;font-weight:600;margin:0;"><?= h(__('Ordens de Produção')) ?></h1>
	</div>
	<?= $this->Html->link('← ' . __('PCP'), ['controller' => 'PcpPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
</div>
<?php if (!empty($pcpMigrationHint)) : ?>
<div class="alert-box alert-amber"><?= h(__('Execute a migration PCP para persistir ordens.')) ?></div>
<?php endif; ?>
<div class="card">
	<div class="tbl-wrap">
		<table class="tbl">
			<thead><tr><th><?= h(__('OP')) ?></th><th><?= h(__('Produto')) ?></th><th class="r"><?= h(__('Planejado')) ?></th><th class="r"><?= h(__('Produzido')) ?></th><th><?= h(__('Status')) ?></th><th></th></tr></thead>
			<tbody>
			<?php if ($ordens === []) : ?>
				<tr><td colspan="6" style="text-align:center;padding:24px;color:var(--text-muted);"><?= h(__('Sem ordens de produção.')) ?></td></tr>
			<?php else : ?>
				<?php foreach ($ordens as $o) : ?>
				<tr>
					<td><span class="titulo-num"><?= h($o['numero']) ?></span></td>
					<td><?= h($o['produto']) ?></td>
					<td class="r"><?= h(number_format((float)$o['quantidade'], 2, ',', '.')) ?></td>
					<td class="r"><?= h(number_format((float)$o['produzida'], 2, ',', '.')) ?></td>
					<td><span class="badge b-env"><?= h($o['status']) ?></span></td>
					<td><?= $this->Html->link(__('Detalhe'), ['controller' => 'PcpPrototype', 'action' => 'opDetalhe', $o['id']], ['class' => 'btn btn-ghost btn-xs']) ?></td>
				</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>
