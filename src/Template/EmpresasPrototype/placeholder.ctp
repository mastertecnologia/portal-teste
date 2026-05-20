<?php
/**
 * Placeholder das telas de empresa.
 *
 * @var \App\View\AppView $this
 * @var string $page
 */
$labels = [
	'nova' => __('Nova empresa'),
	'editar' => __('Editar empresa'),
	'detalhe' => __('Detalhe da empresa'),
];
$label = (string)($labels[$page] ?? ucfirst((string)$page));
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Empresas · Protótipo')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;"><?= h($label) ?></h1>
	</div>
	<?= $this->Html->link('← ' . __('Voltar'), ['controller' => 'EmpresasPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
</div>

<div class="card" style="text-align:center;padding:48px 22px;">
	<div style="font-size:48px;margin-bottom:14px;">🚧</div>
	<h2 style="font-size:18px;margin-bottom:8px;"><?= h(__('Tela em construção')) ?></h2>
	<p style="color:var(--text-muted);margin-bottom:18px;"><?= h(__('Por enquanto, o fluxo continua disponível no módulo clássico.')) ?></p>
	<?= $this->Html->link(__('Ir ao módulo clássico'), ['controller' => 'Empresas', 'action' => 'index'], ['class' => 'btn btn-primary btn-sm']) ?>
</div>
