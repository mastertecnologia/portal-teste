<?php
/** @var array<int,array<string,mixed>> $licProdutos @var array<int,array<string,mixed>> $licCategorias */
$produtos = (array)($licProdutos ?? []);
$cats = (array)($licCategorias ?? []);
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;">PGM ERP › <?= $this->Html->link(__('Licenciamento'), ['action' => 'dashboard'], ['style' => 'color:var(--teal)']) ?> › <?= h(__('Catálogo')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">📚 <?= h(__('Catálogo de Produtos')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);"><?= h(__('{0} produtos em {1} categorias', count($produtos), count($cats))) ?></div>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;">
		<?= $this->Html->link('← ' . __('Painel'), ['action' => 'dashboard'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link(__('Categorias'), ['action' => 'view', 'categorias'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('+ ' . __('Novo produto'), ['action' => 'view', 'produto-novo'], ['class' => 'btn btn-primary btn-sm']) ?>
	</div>
</div>

<?php if ($cats !== []) : ?>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;margin-bottom:14px;">
	<?php foreach ($cats as $c) : ?>
	<a href="<?= h($this->Url->build(['action' => 'view', 'produto-novo', '?' => ['idcategoria' => (int)$c['id']]])) ?>" class="card" style="text-align:center;padding:12px;text-decoration:none;color:inherit;">
		<div style="font-size:26px;"><?= h($c['icon'] ?? '📦') ?></div>
		<strong style="font-size:12px;"><?= h($c['nome']) ?></strong>
		<div style="font-size:10px;color:var(--text-muted);"><?= (int)($c['produtos'] ?? 0) ?> <?= h(__('produtos')) ?></div>
	</a>
	<?php endforeach; ?>
</div>
<?php endif; ?>

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
