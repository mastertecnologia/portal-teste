<?php
/**
 * Pedidos de Compra — tabela `pedidos_compra` ainda não existe; placeholder
 * informativo com instruções e link para o roteiro.
 *
 * @var \App\View\AppView $this
 * @var string $page
 */
$novo = $page === 'pc-novo';
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Compras · Pedidos')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;"><?= $novo ? h('📝 ' . __('Novo pedido de compra')) : h('📋 ' . __('Pedidos de compra')) ?></h1>
	</div>
	<?= $this->Html->link('← ' . __('Estoque'), ['controller' => 'ProdutosPrototype', 'action' => 'estoque'], ['class' => 'btn btn-ghost btn-sm']) ?>
</div>

<div class="alert-box alert-amber">
	<strong><?= h(__('Módulo ainda não implementado.')) ?></strong>
	<?= h(__('A tabela `pedidos_compra` precisa ser criada com migration dedicada. Roadmap inclui: requisição interna → cotação → pedido → recebimento → conferência fiscal.')) ?>
</div>

<div class="card">
	<div class="sec-title"><?= h(__('Fluxo proposto')) ?></div>
	<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;">
		<?php foreach ([
			'1️⃣ Requisição',
			'2️⃣ Cotação 3 fornecedores',
			'3️⃣ Pedido aprovado',
			'4️⃣ Recebimento físico',
			'5️⃣ Conferência fiscal',
			'6️⃣ Pagamento',
		] as $step) : ?>
			<div style="background:var(--bg-surface);border:1px dashed var(--border);border-radius:var(--radius);padding:12px;text-align:center;font-size:12px;color:var(--text-muted);">
				<?= h($step) ?>
			</div>
		<?php endforeach; ?>
	</div>
</div>

<div class="footer-bar">
	<?= $this->Html->link(__('Sugerir migration'), ['controller' => 'ProdutosPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
	<?= $this->Html->link(__('Voltar a Produtos'), ['controller' => 'ProdutosPrototype', 'action' => 'lista'], ['class' => 'btn btn-primary btn-sm']) ?>
</div>
