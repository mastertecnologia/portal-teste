<?php
/**
 * Cabeçalho de página (mockup pgm_erp_completo / pg-lista).
 *
 * @var \App\View\AppView $this
 * @var string $eyebrow
 * @var string $title
 * @var string $subtitle
 * @var array<int,array{label:string,url?:array|string,class?:string}> $actions
 */
$eyebrow = (string)($eyebrow ?? '');
$title = (string)($title ?? '');
$subtitle = (string)($subtitle ?? '');
$actions = (array)($actions ?? []);
?>
<div class="pg-page-head">
	<div>
		<?php if ($eyebrow !== '') : ?>
			<div class="pg-page-eyebrow"><?= h($eyebrow) ?></div>
		<?php endif; ?>
		<h1 class="pg-page-title"><?= h($title) ?></h1>
		<?php if ($subtitle !== '') : ?>
			<div style="font-size:12px;color:var(--text-muted);margin-top:4px;"><?= h($subtitle) ?></div>
		<?php endif; ?>
	</div>
	<?php if ($actions !== []) : ?>
		<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
			<?php foreach ($actions as $act) :
				$label = (string)($act['label'] ?? '');
				$url = $act['url'] ?? null;
				if ($label === '' || $url === null) {
					continue;
				}
				$cls = (string)($act['class'] ?? 'btn btn-primary');
				?>
				<?= $this->Html->link($label, $url, ['class' => $cls, 'escape' => false]) ?>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
