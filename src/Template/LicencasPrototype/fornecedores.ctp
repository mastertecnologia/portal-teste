<?php /** @var array<int,array<string,mixed>> $licFornecedores */ $items = (array)($licFornecedores ?? []); ?>
<div style="display:flex;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<h1 style="font-size:22px;font-weight:600;margin:0;">🏭 <?= h(__('Fornecedores')) ?></h1>
	<?= $this->Html->link('+ ' . __('Novo fornecedor'), ['action' => 'view', 'fornecedor-novo'], ['class' => 'btn btn-primary btn-sm']) ?>
	<?= $this->Html->link(__('Lista completa'), ['controller' => 'FornecedoresPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
</div>
<p style="font-size:12px;color:var(--text-muted);margin-bottom:12px;"><?= h(__('Clientes PJ vinculados ao catálogo de licenciamento.')) ?></p>
<div class="card" style="padding:0;">
	<table class="tbl">
		<thead><tr><th><?= h(__('Fornecedor')) ?></th><th><?= h(__('CNPJ')) ?></th><th><?= h(__('Produtos catálogo')) ?></th><th><?= h(__('Licenças')) ?></th><th></th></tr></thead>
		<tbody>
		<?php if ($items === []) : ?>
		<tr><td colspan="5" style="text-align:center;padding:24px;"><?= h(__('Nenhum fornecedor PJ ativo.')) ?></td></tr>
		<?php else : foreach ($items as $f) : ?>
		<tr>
			<td><?= h($f['nome']) ?></td>
			<td><?= h($f['cnpj'] ?: '—') ?></td>
			<td><?= (int)$f['produtos_catalogo'] ?></td>
			<td><?= (int)$f['licencas'] ?></td>
			<td><?= $this->Html->link(__('Ver'), ['action' => 'view', 'fornecedor-detalhe', '?' => ['id' => (int)$f['id']]], ['class' => 'btn btn-ghost btn-xs']) ?></td>
		</tr>
		<?php endforeach; endif; ?>
		</tbody>
	</table>
</div>
