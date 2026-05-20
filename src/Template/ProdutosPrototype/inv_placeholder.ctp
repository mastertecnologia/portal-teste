<?php
/**
 * Inventário — tabela `inventarios` ainda não existe; placeholder informativo.
 *
 * @var \App\View\AppView $this
 * @var string $page
 */
$historico = $page === 'inv-historico';
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Estoque · Inventário')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;"><?= $historico ? h('📚 ' . __('Inventários anteriores')) : h('📋 ' . __('Inventário de estoque')) ?></h1>
	</div>
	<?= $this->Html->link('← ' . __('Estoque'), ['controller' => 'ProdutosPrototype', 'action' => 'estoque'], ['class' => 'btn btn-ghost btn-sm']) ?>
</div>

<div class="alert-box alert-amber">
	<strong><?= h(__('Módulo ainda não implementado.')) ?></strong>
	<?= h(__('A tabela `inventarios` precisa ser criada para registrar contagens cíclicas com saldo apurado × saldo do sistema. Roadmap: planejamento → contagem mobile/papel → divergências → ajustes.')) ?>
</div>

<div class="card">
	<div class="sec-title"><?= h(__('Roteiro de implantação sugerido')) ?></div>
	<ol style="font-size:12px;line-height:1.8;margin-left:18px;">
		<li><?= h(__('Migration `CreateInventarios` com cabeçalho (data início/fim, responsável, status)')) ?></li>
		<li><?= h(__('Migration `CreateInventarioItens` com saldo sistema/apurado/divergência por produto')) ?></li>
		<li><?= h(__('App para contagem em campo (PWA) com leitura de código de barras')) ?></li>
		<li><?= h(__('Geração de ajustes em `estoque_movimentos` ao fechar inventário')) ?></li>
	</ol>
</div>

<div class="footer-bar">
	<?= $this->Html->link(__('Voltar ao Estoque'), ['controller' => 'ProdutosPrototype', 'action' => 'estoque'], ['class' => 'btn btn-primary btn-sm']) ?>
</div>
