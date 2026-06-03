<?php
/**
 * Detalhe do produto — fluxo para preços e precificação.
 *
 * @var array<string,mixed>|null $produto
 * @var int $produtoId
 */
$H = $this->ErpPrototype;
$nav = $H->navLinkOpts();
$p = $produto ?? null;
$qPrec = $p !== null ? ['produto_id' => (int)$p['id']] : [];
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;">
			← <?= $this->Html->link(__('Produtos'), ['controller' => 'ProdutosPrototype', 'action' => 'lista'], array_merge($nav, ['style' => 'color:var(--teal);'])) ?>
			› <?= h(__('Detalhe')) ?>
		</div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">📦 <?= $p !== null ? h((string)$p['codigo']) . ' · ' . h((string)$p['descricao']) : h(__('Produto')) ?></h1>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;">
		<?= $this->Html->link('← ' . __('Lista'), ['controller' => 'ProdutosPrototype', 'action' => 'lista'], array_merge($nav, ['class' => 'btn btn-ghost btn-sm'])) ?>
		<?php if ($p !== null) : ?>
			<?= $this->Html->link('📋 ' . __('Tabela de preços'), ['controller' => 'ProdutosPrototype', 'action' => 'view', 'precos'], array_merge($nav, ['class' => 'btn btn-ghost btn-sm'])) ?>
			<?= $this->Html->link('🧮 ' . __('Centro de cálculo'), ['controller' => 'ProdutosPrototype', 'action' => 'view', 'precificacao', '?' => $qPrec], array_merge($nav, ['class' => 'btn btn-primary btn-sm'])) ?>
			<?= $this->Html->link('📈 ' . __('Ajustar preço'), ['controller' => 'ProdutosPrototype', 'action' => 'view', 'preco-ajuste', '?' => ['id' => (int)$p['id']]], array_merge($nav, ['class' => 'btn btn-ghost btn-sm'])) ?>
		<?php endif; ?>
	</div>
</div>

<?php if ($p === null) : ?>
	<div class="alert-box alert-amber"><?= h(__('Produto não encontrado (id={0}).', $produtoId)) ?></div>
<?php else : ?>
	<?php if (!empty($this->request->getQuery('novo'))) : ?>
		<div class="alert-box alert-teal" style="margin-bottom:14px;"><?= h(__('Produto cadastrado. Defina o preço na tabela ou abra o Centro de Cálculo.')) ?></div>
	<?php endif; ?>
	<div class="card" style="padding:16px;">
		<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;">
			<div><div class="mu" style="font-size:10px;text-transform:uppercase;"><?= h(__('Código')) ?></div><strong style="font-family:monospace;"><?= h((string)$p['codigo']) ?></strong></div>
			<div><div class="mu" style="font-size:10px;text-transform:uppercase;"><?= h(__('Tipo')) ?></div><?= h((string)$p['tipo']) ?></div>
			<div><div class="mu" style="font-size:10px;text-transform:uppercase;"><?= h(__('Preço (cadastro)')) ?></div><strong><?= h($H->brl((float)$p['preco'])) ?></strong></div>
			<div><div class="mu" style="font-size:10px;text-transform:uppercase;"><?= h(__('Estoque')) ?></div><?= number_format((float)$p['estoque'], 2, ',', '.') ?></div>
			<div><div class="mu" style="font-size:10px;text-transform:uppercase;"><?= h(__('Status')) ?></div><?= $H->badge($p['ativo'] ? __('Ativo') : __('Inativo'), $p['ativo'] ? 'paga' : 'arq') ?></div>
		</div>
	</div>
<?php endif; ?>
