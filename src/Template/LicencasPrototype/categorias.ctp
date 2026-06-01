<?php /** @var array<int,array<string,mixed>> $licCategorias */ $cats = (array)($licCategorias ?? []); ?>
<div style="display:flex;justify-content:space-between;margin-bottom:14px;">
	<h1 style="font-size:22px;font-weight:600;margin:0;">📂 <?= h(__('Categorias')) ?></h1>
	<div style="display:flex;gap:8px;">
		<?= $this->Html->link('+ ' . __('Nova'), ['action' => 'view', 'categoria-editar'], ['class' => 'btn btn-primary btn-sm']) ?>
		<?= $this->Html->link(__('Catálogo'), ['action' => 'view', 'catalogo'], ['class' => 'btn btn-ghost btn-sm']) ?>
	</div>
</div>
<div class="card" style="padding:0;">
	<table class="tbl">
		<thead><tr><th><?= h(__('Código')) ?></th><th><?= h(__('Nome')) ?></th><th></th></tr></thead>
		<tbody>
		<?php foreach ($cats as $c) : ?>
		<tr>
			<td><?= h($c['codigo']) ?></td>
			<td><?= h($c['nome']) ?></td>
			<td><?= $this->Html->link(__('Editar'), ['action' => 'view', 'categoria-editar', '?' => ['id' => (int)$c['id']]], ['class' => 'btn btn-ghost btn-xs']) ?></td>
		</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>
