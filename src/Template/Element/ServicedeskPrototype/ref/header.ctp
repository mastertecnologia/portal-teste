<?php
/**
 * Cabeçalho padrão (mockup pg-sd-*).
 *
 * @var \App\View\AppView $this
 * @var string $eyebrow
 * @var string $title
 * @var string $subtitle
 * @var array<int,array<string,mixed>> $toolbar
 */
$eyebrow = $eyebrow ?? __('Service Desk');
$eyebrowHtml = $eyebrowHtml ?? null;
$subtitle = $subtitle ?? '';
$toolbar = $toolbar ?? [];
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= $eyebrowHtml !== null ? $eyebrowHtml : h($eyebrow) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;"><?= h($title) ?></h1>
		<?php if ($subtitle !== '') : ?>
			<div style="font-size:12px;color:var(--text-muted);"><?= h($subtitle) ?></div>
		<?php endif; ?>
	</div>
	<?php if ($toolbar !== []) : ?>
		<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
			<?php foreach ($toolbar as $btn) : ?>
				<?php
				$cls = (string)($btn['class'] ?? 'btn btn-ghost btn-sm');
				$label = (string)($btn['label'] ?? '');
				$url = $btn['url'] ?? null;
				if ($label === '' || $url === null) {
					continue;
				}
				?>
				<?= $this->Html->link($label, $url, ['class' => $cls, 'escape' => false]) ?>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
