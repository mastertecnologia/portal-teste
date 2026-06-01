<?php
/** @var array<int,array<string,mixed>> $licDispositivos @var array<int,string> $licClientes @var array<string,mixed> $licFilters */
$items = (array)($licDispositivos ?? []);
?>
<div style="display:flex;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<h1 style="font-size:22px;font-weight:600;margin:0;">💻 <?= h(__('Dispositivos')) ?></h1>
	<?= $this->Html->link('+ ' . __('Novo'), ['action' => 'view', 'dispositivo-novo'], ['class' => 'btn btn-primary btn-sm']) ?>
</div>
<div class="card" style="margin-bottom:14px;">
	<form method="get" class="g2">
		<div class="field" style="margin:0;"><label><?= h(__('Cliente')) ?></label>
			<select name="cliente" onchange="this.form.submit()">
				<option value=""><?= h(__('Todos')) ?></option>
				<?php foreach ((array)($licClientes ?? []) as $cid => $cn) : ?>
				<option value="<?= (int)$cid ?>"<?= (string)($licFilters['cliente'] ?? '') === (string)$cid ? ' selected' : '' ?>><?= h($cn) ?></option>
				<?php endforeach; ?>
			</select>
		</div>
	</form>
</div>
<div class="card" style="padding:0;">
	<table class="tbl">
		<thead><tr><th><?= h(__('Cliente')) ?></th><th><?= h(__('Hostname')) ?></th><th><?= h(__('Serial')) ?></th><th><?= h(__('SO')) ?></th><th></th></tr></thead>
		<tbody>
		<?php if ($items === []) : ?>
		<tr><td colspan="5" style="text-align:center;padding:24px;"><?= h(__('Nenhum dispositivo.')) ?></td></tr>
		<?php else : foreach ($items as $d) : ?>
		<tr>
			<td><?= h($d['cliente']) ?></td>
			<td><?= h($d['hostname'] ?: '—') ?></td>
			<td><?= h($d['serial'] ?: '—') ?></td>
			<td><?= h($d['so'] ?: '—') ?></td>
			<td><?= $this->Html->link(__('Ver'), ['action' => 'view', 'dispositivo-detalhe', '?' => ['id' => (int)$d['id']]], ['class' => 'btn btn-ghost btn-xs']) ?></td>
		</tr>
		<?php endforeach; endif; ?>
		</tbody>
	</table>
</div>
