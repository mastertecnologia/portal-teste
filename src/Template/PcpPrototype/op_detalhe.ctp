<?php
/** @var array<string,mixed> $pcpOrdem */
$o = (array)($pcpOrdem ?? []);
$ap = (array)($o['apontamentos'] ?? []);
?>
<div style="margin-bottom:14px;">
	<h1 style="font-size:22px;font-weight:600;margin:0;"><?= h(__('OP')) ?> <span style="color:var(--teal);"><?= h($o['numero'] ?? '') ?></span></h1>
	<div style="font-size:12px;color:var(--text-muted);"><?= h($o['produto'] ?? '') ?> <?= $o['codigo'] !== '' ? '· ' . h($o['codigo']) : '' ?></div>
</div>
<div class="summary-grid" style="margin-bottom:14px;">
	<div class="summary-card"><div class="lbl"><?= h(__('Quantidade')) ?></div><div class="val"><?= h(number_format((float)($o['quantidade'] ?? 0), 2, ',', '.')) ?></div></div>
	<div class="summary-card"><div class="lbl"><?= h(__('Produzida')) ?></div><div class="val" style="color:var(--teal-dark);"><?= h(number_format((float)($o['produzida'] ?? 0), 2, ',', '.')) ?></div></div>
	<div class="summary-card"><div class="lbl"><?= h(__('Status')) ?></div><div class="val"><?= h($o['status'] ?? '') ?></div></div>
</div>
<div class="card">
	<div class="sec-title"><?= h(__('Apontamentos')) ?></div>
	<?php if ($ap === []) : ?>
		<p style="color:var(--text-muted);font-size:13px;"><?= h(__('Nenhum apontamento registrado.')) ?></p>
	<?php else : ?>
	<div class="tbl-wrap">
		<table class="tbl">
			<thead><tr><th><?= h(__('Operação')) ?></th><th><?= h(__('Centro')) ?></th><th><?= h(__('Operador')) ?></th><th><?= h(__('Início')) ?></th><th class="r"><?= h(__('Boa')) ?></th><th class="r"><?= h(__('Refugo')) ?></th></tr></thead>
			<tbody>
			<?php foreach ($ap as $a) : ?>
				<tr>
					<td><?= h($a['operacao']) ?></td>
					<td><?= h($a['centro']) ?></td>
					<td><?= h($a['operador']) ?></td>
					<td><?= $a['inicio'] instanceof \DateTimeInterface ? h($a['inicio']->format('d/m/Y H:i')) : '—' ?></td>
					<td class="r"><?= h(number_format((float)$a['boa'], 2, ',', '.')) ?></td>
					<td class="r"><?= h(number_format((float)$a['refugo'], 2, ',', '.')) ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php endif; ?>
</div>
<?= $this->Html->link('← ' . __('Lista de OPs'), ['controller' => 'PcpPrototype', 'action' => 'view', 'op-lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
