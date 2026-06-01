<?php
/** @var array<int,array<string,mixed>> $licCofreItens @var array<int,string> $licClientes @var array<string,mixed> $licFilters @var bool $licPodeRevelarSegredo */
$items = (array)($licCofreItens ?? []);
?>
<div style="display:flex;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<h1 style="font-size:22px;font-weight:600;margin:0;">🔐 <?= h(__('Cofre de credenciais')) ?></h1>
	<?= $this->Html->link('+ ' . __('Novo item'), ['action' => 'view', 'cofre-novo'], ['class' => 'btn btn-primary btn-sm']) ?>
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
		<thead><tr><th><?= h(__('Título')) ?></th><th><?= h(__('Cliente')) ?></th><th><?= h(__('Nível')) ?></th><th><?= h(__('Licença')) ?></th><th></th></tr></thead>
		<tbody>
		<?php if ($items === []) : ?>
		<tr><td colspan="5" style="text-align:center;padding:24px;"><?= h(__('Nenhum item no cofre.')) ?></td></tr>
		<?php else : foreach ($items as $row) : ?>
		<tr>
			<td><?= h($row['titulo']) ?></td>
			<td><?= h($row['cliente'] ?: '—') ?></td>
			<td><?= h($row['nivel']) ?></td>
			<td><?= h($row['licenca_codigo'] ?: '—') ?></td>
			<td><?= $this->Html->link(__('Abrir'), ['action' => 'view', 'cofre-item', '?' => ['id' => (int)$row['id']]], ['class' => 'btn btn-ghost btn-xs']) ?></td>
		</tr>
		<?php endforeach; endif; ?>
		</tbody>
	</table>
</div>
<?php if (empty($licPodeRevelarSegredo)) : ?>
<p style="font-size:12px;color:var(--text-muted);margin-top:10px;"><?= h(__('Revelar segredos exige permissão licencas.cofre.secret.')) ?></p>
<?php endif; ?>
