<?php /** @var array<string,mixed> $licFornecedor */ $f = (array)($licFornecedor ?? []); $prods = (array)($f['produtos'] ?? []); ?>
<div style="display:flex;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<h1 style="font-size:22px;font-weight:600;margin:0;"><?= h($f['nome'] ?? '') ?></h1>
		<p style="font-size:12px;color:var(--text-muted);"><?= h($f['cnpj'] ?? '') ?></p>
	</div>
	<?= $this->Html->link(__('Cadastro fornecedor'), ['controller' => 'FornecedoresPrototype', 'action' => 'view', (int)($f['id'] ?? 0)], ['class' => 'btn btn-ghost btn-sm']) ?>
	<?= $this->Html->link('← ' . __('Fornecedores'), ['action' => 'view', 'fornecedores'], ['class' => 'btn btn-ghost btn-sm']) ?>
</div>
<div class="stats" style="margin-bottom:14px;">
	<div class="stat"><div class="stat-l"><?= h(__('Produtos no catálogo')) ?></div><div class="stat-n"><?= (int)($f['produtos_catalogo'] ?? 0) ?></div></div>
	<div class="stat"><div class="stat-l"><?= h(__('Licenças')) ?></div><div class="stat-n"><?= (int)($f['licencas'] ?? 0) ?></div></div>
</div>
<div class="card" style="padding:0;">
	<table class="tbl">
		<thead><tr><th><?= h(__('SKU')) ?></th><th><?= h(__('Produto')) ?></th></tr></thead>
		<tbody>
		<?php if ($prods === []) : ?>
		<tr><td colspan="2" style="text-align:center;padding:20px;"><?= h(__('Sem produtos no catálogo para este fornecedor.')) ?></td></tr>
		<?php else : foreach ($prods as $p) : ?>
		<tr><td><?= h($p['sku'] ?: '—') ?></td><td><?= h($p['nome']) ?></td></tr>
		<?php endforeach; endif; ?>
		</tbody>
	</table>
</div>
