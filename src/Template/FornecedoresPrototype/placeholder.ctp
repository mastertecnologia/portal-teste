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
	<strong><?= h(__('Módulo dedicado ainda não existe no portal.')) ?></strong><br>
	<?= h(__('Atualmente os fornecedores são gerenciados via Clientes (tipo PJ) ou pelo módulo Fiscal. A tela premium dedicada faz parte do roadmap.')) ?>
</div>

<div class="card" style="text-align:center;padding:32px 22px;">
	<div style="font-size:42px;margin-bottom:10px;">🚧</div>
	<h2 style="font-size:16px;margin-bottom:8px;"><?= h(__('Tela em construção')) ?></h2>
	<p style="color:var(--text-muted);margin-bottom:14px;">
		<?= h(__('Quando o módulo de fornecedores for criado, esta interface vai mostrar a lista premium do mockup.')) ?>
	</p>
	<div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;">
		<?= $this->Html->link('👥 ' . __('Clientes (PJ)'), ['controller' => 'ClientesPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('🧾 ' . __('Notas fiscais'), ['controller' => 'FiscalNotas', 'action' => 'index'], ['class' => 'btn btn-primary btn-sm']) ?>
	</div>
</div>
