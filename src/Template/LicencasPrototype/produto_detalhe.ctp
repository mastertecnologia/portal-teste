<?php /** @var array<string,mixed> $licProduto */ $p = (array)($licProduto ?? []); ?>
<h1 style="font-size:22px;margin-bottom:8px;"><?= h($p['nome'] ?? '') ?></h1>
<p style="color:var(--text-muted);margin-bottom:14px;">SKU: <?= h($p['sku'] ?: '—') ?> · <?= h($p['categoria'] ?: '—') ?></p>
<?= $this->Html->link(__('Editar'), ['action' => 'view', 'produto-editar', '?' => ['id' => (int)$p['id']]], ['class' => 'btn btn-primary btn-sm']) ?>
<?= $this->Html->link(__('Catálogo'), ['action' => 'view', 'catalogo'], ['class' => 'btn btn-ghost btn-sm']) ?>
