<?php
/** @var array<string,mixed> $licCofreItem @var string|null $licCofreSegredoRevelado @var bool $licPodeRevelarSegredo */
$item = (array)($licCofreItem ?? []);
$id = (int)($item['id'] ?? 0);
?>
<div style="display:flex;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<h1 style="font-size:22px;font-weight:600;margin:0;">🔐 <?= h($item['titulo'] ?? '') ?></h1>
	<?= $this->Html->link(__('Editar'), ['action' => 'view', 'cofre-editar', '?' => ['id' => $id]], ['class' => 'btn btn-ghost btn-sm']) ?>
</div>
<div class="card g2">
	<div><strong><?= h(__('Cliente')) ?></strong><br><?= h($item['cliente'] ?: '—') ?></div>
	<div><strong><?= h(__('Nível')) ?></strong><br><?= h($item['nivel'] ?? '') ?></div>
	<div><strong><?= h(__('Licença')) ?></strong><br><?= h($item['licenca_codigo'] ?: '—') ?></div>
	<div><strong><?= h(__('Segredo')) ?></strong><br>
		<?php if (!empty($licCofreSegredoRevelado)) : ?>
		<code style="word-break:break-all;"><?= h($licCofreSegredoRevelado) ?></code>
		<?php elseif (!empty($item['tem_segredo'])) : ?>
		<span><?= h(__('Credencial protegida.')) ?></span>
		<?php if (!empty($licPodeRevelarSegredo)) : ?>
		<?= $this->Form->postLink(__('Revelar (auditado)'), ['action' => 'revelarCofreSegredo'], ['data' => ['id' => $id], 'class' => 'btn btn-warn btn-xs', 'style' => 'margin-left:8px;']) ?>
		<?php endif; ?>
		<?php else : ?>
		<span>—</span>
		<?php endif; ?>
	</div>
</div>
<p style="margin-top:12px;"><?= $this->Html->link('← ' . __('Cofre'), ['action' => 'view', 'cofre'], ['class' => 'btn btn-ghost btn-sm']) ?></p>
