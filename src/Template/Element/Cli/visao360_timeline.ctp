<?php
/**
 * Timeline Visão 360°.
 *
 * @var \App\View\AppView $this
 * @var array<int,array<string,mixed>> $items
 * @var string $empty
 */
if (empty($items)) : ?>
<p class="cli-360-empty"><?= h($empty) ?></p>
<?php return; endif; ?>
<ul class="cli-360-timeline">
<?php foreach ($items as $t) :
	$tone = (string)($t['tone'] ?? 'teal');
	$icon = (string)($t['icon'] ?? 'fa-circle');
?>
	<li class="cli-360-tl-item">
		<div class="cli-360-tl-icon cli-360-tl-icon--<?= h($tone) ?>"><i class="fas <?= h($icon) ?>" aria-hidden="true"></i></div>
		<div class="cli-360-tl-body">
			<div class="cli-360-tl-title">
				<?php if (!empty($t['url'])) : ?>
				<?= $this->Html->link((string)$t['label'], $t['url'], ['data-turbo' => 'false']) ?>
				<?php else : ?>
				<?= h((string)$t['label']) ?>
				<?php endif; ?>
			</div>
			<div class="cli-360-tl-sub">
				<?php if ($t['data'] instanceof \DateTimeInterface) : ?>
				<?= h($t['data']->i18nFormat('dd/MM/yyyy')) ?>
				<?php endif; ?>
				<?php if (!empty($t['sub'])) : ?> · <?= h((string)$t['sub']) ?><?php endif; ?>
			</div>
		</div>
	</li>
<?php endforeach; ?>
</ul>
