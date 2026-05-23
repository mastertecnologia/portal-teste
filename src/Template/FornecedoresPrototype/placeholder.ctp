<?php
/**
 * Placeholder para módulo Fornecedores — não existe tabela dedicada no portal.
 *
 * @var \App\View\AppView $this
 * @var string $page
 */
$labels = [
	'lista' => __('Fornecedores'),
	'novo' => __('Novo fornecedor'),
	'360' => __('Visão 360º · fornecedor'),
];
$label = (string)($labels[$page] ?? ucfirst((string)$page));
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Cadastros')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">🏭 <?= h($label) ?></h1>
	</div>
	<?php if ($page !== 'lista') : ?>
		<?= $this->Html->link('← ' . __('Voltar'), ['controller' => 'FornecedoresPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
	<?php endif; ?>
</div>

<div class="alert-box alert-amber">
	<strong><?= h(__('Selecione um fornecedor na lista ou cadastre um cliente PJ.')) ?></strong><br>
	<?= h(__('Com ?id= na URL, esta tela redireciona automaticamente para a Visão 360° legada.')) ?>
</div>

<div class="card" style="text-align:center;padding:32px 22px;">
	<div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;">
		<?= $this->Html->link('🏭 ' . __('Lista de fornecedores'), ['controller' => 'FornecedoresPrototype', 'action' => 'lista'], ['class' => 'btn btn-primary btn-sm']) ?>
		<?= $this->Html->link('+ ' . __('Novo (PJ)'), ['controller' => 'Clientes', 'action' => 'add'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('👥 ' . __('Clientes'), ['controller' => 'ClientesPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
	</div>
</div>
