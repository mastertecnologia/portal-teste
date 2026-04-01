<?php
$label = $label ?? '';
$field = $field ?? '';
$selectOptions = isset($selectOptions) && is_array($selectOptions) ? $selectOptions : [];
$options = isset($options) && is_array($options) ? $options : [];
$colClass = $colClass ?? 'col-12';
$wrapClass = $wrapClass ?? 'form-group cli-cmp-field';
if (!isset($options['options'])) {
	$options['options'] = $selectOptions;
}
$options += ['label' => false];
$base = 'form-control cli-cmp-select';
if (!empty($options['class'])) {
	$options['class'] = trim($base . ' ' . $options['class']);
} else {
	$options['class'] = $base;
}
?>
<div class="<?= h($colClass) ?>">
	<div class="<?= h($wrapClass) ?>">
		<?php if ($label !== ''): ?>
		<label class="cli-cmp-label"><?= h($label) ?></label>
		<?php endif; ?>
		<?= $this->Form->control($field, $options) ?>
	</div>
</div>
