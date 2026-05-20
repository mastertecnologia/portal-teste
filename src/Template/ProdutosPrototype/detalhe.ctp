<?php
/**
 * Detalhe resumido do produto (protótipo).
 *
 * @var \App\View\AppView $this
 * @var array<string,mixed>|null $produto
 * @var int $produtoId
 */
$H = $this->ErpPrototype;
$p = $produto ?? null;
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Cadastros')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">📦 <?= h(__('Produto')) ?></h1>
	</div>
	<div style="display:flex;gap:8px;">
		<?= $this->Html->link('← ' . __('Lista'), ['controller' => 'ProdutosPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?php if ($p !== null) : ?>
			<?= $this->Html->link(__('Legado'), ['controller' => 'Produtos', 'action' => 'view', (int)$p['id']], ['class' => 'btn btn-ghost btn-sm', 'target' => '_blank']) ?>
		<?php endif; ?>
	</div>
</div>

<?php if ($p === null) : ?>
	<div class="alert-box alert-amber"><?= h(__('Produto não encontrado no escopo da empresa (id={0}).', $produtoId)) ?></div>
<?php else : ?>
	<div class="card" style="padding:16px;">
		<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;">
			<div><div class="mu" style="font-size:10px;text-transform:uppercase;"><?= h(__('Código')) ?></div><strong style="font-family:monospace;"><?= h((string)$p['codigo']) ?></strong></div>
			<div><div class="mu" style="font-size:10px;text-transform:uppercase;"><?= h(__('Tipo')) ?></div><?= h((string)$p['tipo']) ?></div>
			<div><div class="mu" style="font-size:10px;text-transform:uppercase;"><?= h(__('Preço')) ?></div><strong><?= h($H->brl((float)$p['preco'])) ?></strong></div>
			<div><div class="mu" style="font-size:10px;text-transform:uppercase;"><?= h(__('Estoque')) ?></div><?= number_format((float)$p['estoque'], 2, ',', '.') ?></div>
			<div><div class="mu" style="font-size:10px;text-transform:uppercase;"><?= h(__('Status')) ?></div><?= $H->badge($p['ativo'] ? __('Ativo') : __('Inativo'), $p['ativo'] ? 'paga' : 'arq') ?></div>
		</div>
		<div style="margin-top:14px;">
			<div class="mu" style="font-size:10px;text-transform:uppercase;margin-bottom:4px;"><?= h(__('Descrição')) ?></div>
			<div><?= h((string)$p['descricao']) ?></div>
		</div>
	</div>
	<div class="alert-box alert-blue" style="margin-top:14px;">
		<?= h(__('Tela completa de edição fiscal/estoque permanece no módulo Produtos clássico até a migração total do mockup.')) ?>
	</div>
<?php endif; ?>
