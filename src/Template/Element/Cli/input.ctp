<?php
$label = $label ?? '';
$field = $field ?? '';
$options = isset($options) && is_array($options) ? $options : [];
$colClass = $colClass ?? 'col-12';
$wrapClass = $wrapClass ?? 'form-group cli-cmp-field';
$labelHtml = $labelHtml ?? null;
$beforeControlHtml = $beforeControlHtml ?? '';
$options += ['label' => false];
$base = 'form-control cli-cmp-input';
if (!empty($options['class'])) {
	$options['class'] = trim($base . ' ' . $options['class']);
} else {
	$options['class'] = $base;
}
?>
<div class="<?= h($colClass) ?>">
	<div class="<?= h($wrapClass) ?>">
		<?php if ($labelHtml !== null && $labelHtml !== ''): ?>
		<?= $labelHtml ?>
		<?php elseif ($label !== ''): ?>
		<label class="cli-cmp-label"><?= h($label) ?></label>
		<?php endif; ?>
		<?= $beforeControlHtml ?>
		<?= $this->Form->control($field, $options) ?>
	</div>
</div>
