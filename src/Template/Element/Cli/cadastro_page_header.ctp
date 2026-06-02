<?php
/**
 * Cabeçalho cadastro cliente — paridade pg-cliente-novo (pgm_erp_completo.html).
 *
 * @var string $pageTitle
 * @var string $pageSubtitle
 * @var string $crumbCurrent
 * @var array|string $cancelUrl
 * @var array|string|null $clientesListaUrl
 */
$pageTitle = (string)($pageTitle ?? __('Novo cadastro de cliente'));
$pageSubtitle = (string)($pageSubtitle ?? __('Preencha as informações principais · Você pode complementar depois'));
$crumbCurrent = (string)($crumbCurrent ?? __('Novo cliente'));
$crumbParentLabel = (string)($crumbParentLabel ?? __('Clientes'));
$saveLabel = (string)($saveLabel ?? __('Salvar cliente'));
$cancelUrl = $cancelUrl ?? ['action' => 'index'];
$clientesListaUrl = $clientesListaUrl ?? ['controller' => 'ClientesPrototype', 'action' => 'lista'];
?>
<div class="cli-cadastro-page-head" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;">
			<?= $this->Html->link('← ' . h($crumbParentLabel), $clientesListaUrl, ['style' => 'color:var(--teal);', 'data-turbo' => 'false', 'escape' => false]) ?>
			› <span style="color:var(--teal);"><?= h($crumbCurrent) ?></span>
		</div>
		<h1 style="font-size:22px;font-weight:600;margin:0 0 4px;"><?= h($pageTitle) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);"><?= h($pageSubtitle) ?></div>
	</div>
	<div class="cli-cadastro-page-actions" style="display:flex;gap:8px;flex-wrap:wrap;">
		<?= $this->Html->link(__('Cancelar'), $cancelUrl, ['class' => 'btn btn-ghost btn-sm', 'data-turbo' => 'false']) ?>
		<?php if (!empty($showHeaderDraft)) : ?>
		<button type="button" class="btn btn-ghost btn-sm" disabled title="<?= h(__('Em breve')) ?>">💾 <?= h(__('Rascunho')) ?></button>
		<?php endif; ?>
		<?php if (!empty($showHeaderSave)) : ?>
		<button type="submit" form="cli-add-form" class="btn btn-primary btn-sm">✓ <?= h($saveLabel) ?></button>
		<?php endif; ?>
	</div>
</div>
