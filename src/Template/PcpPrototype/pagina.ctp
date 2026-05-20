<?php
/**
 * Página individual de uma tela PCP — placeholder com roteiro detalhado.
 *
 * @var \App\View\AppView $this
 * @var array{icon:string,title:string,subtitle:string,roteiro:array<int,string>,key:string} $pageMeta
 */
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div style="display:flex;align-items:center;gap:14px;">
		<div style="font-size:42px;line-height:1;"><?= $pageMeta['icon'] ?></div>
		<div>
			<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Indústria · PCP')) ?></div>
			<h1 style="font-size:22px;font-weight:600;margin:0;"><?= h((string)$pageMeta['title']) ?></h1>
			<div style="font-size:12px;color:var(--text-muted);"><?= h((string)$pageMeta['subtitle']) ?></div>
		</div>
	</div>
	<?= $this->Html->link('← ' . __('Visão geral PCP'), ['controller' => 'PcpPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
</div>

<div class="alert-box alert-amber">
	<strong><?= h(__('Tela em planejamento.')) ?></strong>
	<?= h(__('Esta funcionalidade ainda não existe no portal — depende de modelagem de banco nova. O roteiro abaixo descreve o que entra na implementação.')) ?>
</div>

<div class="card">
	<div class="sec-title">📋 <?= h(__('Roteiro de implementação')) ?></div>
	<ol style="font-size:13px;line-height:1.9;margin-left:18px;">
		<?php foreach ((array)$pageMeta['roteiro'] as $linha) : ?>
			<li><?= h((string)$linha) ?></li>
		<?php endforeach; ?>
	</ol>
</div>

<div class="card">
	<div class="sec-title"><?= h(__('Dependências de outros módulos')) ?></div>
	<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;font-size:12px;">
		<div style="background:var(--bg-surface);border-radius:var(--radius);padding:12px;">
			<strong>📦 <?= h(__('Produtos')) ?></strong>
			<div style="color:var(--text-muted);font-size:11px;margin-top:4px;"><?= h(__('Cadastro de produtos manufaturados/matérias-primas')) ?></div>
		</div>
		<div style="background:var(--bg-surface);border-radius:var(--radius);padding:12px;">
			<strong>📦 <?= h(__('Estoque')) ?></strong>
			<div style="color:var(--text-muted);font-size:11px;margin-top:4px;"><?= h(__('Movimentações geradas por produção e expedição')) ?></div>
		</div>
		<div style="background:var(--bg-surface);border-radius:var(--radius);padding:12px;">
			<strong>🛍 <?= h(__('Pedidos de venda')) ?></strong>
			<div style="color:var(--text-muted);font-size:11px;margin-top:4px;"><?= h(__('Origem das ordens de produção')) ?></div>
		</div>
		<div style="background:var(--bg-surface);border-radius:var(--radius);padding:12px;">
			<strong>🏷 <?= h(__('Compras')) ?></strong>
			<div style="color:var(--text-muted);font-size:11px;margin-top:4px;"><?= h(__('MRP sugere pedidos de compra para repor materiais')) ?></div>
		</div>
	</div>
</div>

<div class="footer-bar">
	<?= $this->Html->link('← ' . __('Voltar'), ['controller' => 'PcpPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
	<?= $this->Html->link(__('💬 Sugerir prioridade'), ['controller' => 'ServicedeskPrototype', 'action' => 'view', 'portal-novo'], ['class' => 'btn btn-primary btn-sm']) ?>
</div>
