<?php
/**
 * @var \App\View\AppView $this
 * @var array<int,array<string,mixed>> $licItems
 * @var array<int,string> $licClientes
 * @var array<string,mixed> $licFilters
 * @var bool $licMigrationHint
 */
$H = $this->ErpPrototype;
$items = (array)($licItems ?? []);
$statusMap = [
	'ativa' => ['label' => __('Ativa'), 'class' => 'b-paga'],
	'rascunho' => ['label' => __('Rascunho'), 'class' => 'b-pend'],
	'suspensa' => ['label' => __('Suspensa'), 'class' => 'b-canc'],
	'vencida' => ['label' => __('Vencida'), 'class' => 'b-canc'],
	'cancelada' => ['label' => __('Cancelada'), 'class' => 'b-canc'],
];
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;">PGM ERP › <?= h(__('Licenciamento')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">📦 <?= h(__('Licenças')) ?></h1>
	</div>
	<div style="display:flex;gap:8px;">
		<?= $this->Html->link('+ ' . __('Nova licença'), ['action' => 'view', 'nova'], ['class' => 'btn btn-primary btn-sm']) ?>
		<?= $this->Html->link('← ' . __('Painel'), ['action' => 'dashboard'], ['class' => 'btn btn-ghost btn-sm']) ?>
	</div>
</div>

<?php if (!empty($licMigrationHint)) : ?>
<div class="alert alert-warn" style="margin-bottom:14px;"><?= h(__('Execute bin/cake migrations migrate para criar as tabelas lic_*.')) ?></div>
<?php endif; ?>

<div class="card" style="margin-bottom:14px;">
	<form method="get" class="g3" style="align-items:end;">
		<div class="field" style="margin:0;">
			<label><?= h(__('Status')) ?></label>
			<select name="status">
				<option value=""><?= h(__('Todos')) ?></option>
				<?php foreach ($statusMap as $k => $meta) : ?>
				<option value="<?= h($k) ?>"<?= ($licFilters['status'] ?? '') === $k ? ' selected' : '' ?>><?= h($meta['label']) ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="field" style="margin:0;">
			<label><?= h(__('Cliente')) ?></label>
			<select name="cliente">
				<option value=""><?= h(__('Todos')) ?></option>
				<?php foreach ((array)($licClientes ?? []) as $cid => $cnome) : ?>
				<option value="<?= (int)$cid ?>"<?= (string)($licFilters['cliente'] ?? '') === (string)$cid ? ' selected' : '' ?>><?= h($cnome) ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div><button type="submit" class="btn btn-ghost btn-sm"><?= h(__('Filtrar')) ?></button></div>
	</form>
</div>

<div class="card" style="padding:0;overflow:hidden;">
	<table class="tbl">
		<thead>
			<tr>
				<th><?= h(__('Código')) ?></th>
				<th><?= h(__('Cliente')) ?></th>
				<th><?= h(__('Produto')) ?></th>
				<th><?= h(__('Assentos')) ?></th>
				<th><?= h(__('Vigência')) ?></th>
				<th><?= h(__('Status')) ?></th>
				<th></th>
			</tr>
		</thead>
		<tbody>
		<?php if ($items === []) : ?>
			<tr><td colspan="7" style="text-align:center;padding:28px;color:var(--text-muted);"><?= h(__('Nenhuma licença. Inicie pelo wizard.')) ?></td></tr>
		<?php else : ?>
			<?php foreach ($items as $row) :
				$st = (string)$row['status'];
				$badge = $statusMap[$st] ?? ['label' => ucfirst($st), 'class' => 'b-pend'];
				$ini = $row['inicio'] ? (string)$row['inicio'] : '—';
				$fim = $row['fim'] ? (string)$row['fim'] : '—';
			?>
			<tr>
				<td><strong><?= h($row['codigo']) ?></strong></td>
				<td><?= h($row['cliente']) ?></td>
				<td><?= h($row['produto'] ?: '—') ?></td>
				<td><?= (int)$row['assentos'] ?></td>
				<td style="font-size:11px;"><?= h($ini) ?> → <?= h($fim) ?></td>
				<td><span class="badge <?= h($badge['class']) ?>"><?= h($badge['label']) ?></span></td>
				<td><?= $this->Html->link(__('Abrir'), ['action' => 'licencaDetalhe', (int)$row['id']], ['class' => 'btn btn-ghost btn-xs']) ?></td>
			</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>
</div>
