<?php
/** @var array<int,array<string,mixed>> $licProdutos @var array<int,array<string,mixed>> $licCategorias */
$produtos = (array)($licProdutos ?? []);
?>
<div style="display:flex;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">📚 <?= h(__('Catálogo de produtos')) ?></h1>
	</div>
	<div style="display:flex;gap:8px;">
		<?= $this->Html->link('+ ' . __('Produto'), ['action' => 'view', 'produto-novo'], ['class' => 'btn btn-primary btn-sm']) ?>
		<?= $this->Html->link(__('Categorias'), ['action' => 'view', 'categorias'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('← ' . __('Painel'), ['action' => 'dashboard'], ['class' => 'btn btn-ghost btn-sm']) ?>
	</div>
</div>
<div class="card" style="padding:0;overflow:hidden;">
	<table class="tbl">
		<thead><tr><th><?= h(__('SKU')) ?></th><th><?= h(__('Nome')) ?></th><th><?= h(__('Categoria')) ?></th><th><?= h(__('Ativo')) ?></th><th></th></tr></thead>
		<tbody>
		<?php if ($produtos === []) : ?>
		<tr><td colspan="5" style="text-align:center;padding:24px;color:var(--text-muted);"><?= h(__('Nenhum produto no catálogo.')) ?></td></tr>
		<?php else : foreach ($produtos as $p) : ?>
		<tr>
			<td><?= h($p['sku'] ?: '—') ?></td>
			<td><strong><?= h($p['nome']) ?></strong></td>
			<td><?= h($p['categoria'] ?: '—') ?></td>
			<td><?= !empty($p['ativo']) ? '✓' : '—' ?></td>
			<td>
				<?= $this->Html->link(__('Ver'), ['action' => 'view', 'produto-detalhe', '?' => ['id' => (int)$p['id']]], ['class' => 'btn btn-ghost btn-xs']) ?>
				<?= $this->Html->link(__('Editar'), ['action' => 'view', 'produto-editar', '?' => ['id' => (int)$p['id']]], ['class' => 'btn btn-ghost btn-xs']) ?>
			</td>
		</tr>
		<?php endforeach; endif; ?>
		</tbody>
	</table>
</div>
