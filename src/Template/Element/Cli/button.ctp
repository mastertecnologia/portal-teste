<?php
$text = $text ?? '';
$iconHtml = $iconHtml ?? '';
$type = $type ?? 'button';
$class = $class ?? 'btn-secondary';
$attrs = isset($attrs) && is_array($attrs) ? $attrs : [];
$content = $iconHtml . ($iconHtml !== '' && $text !== '' ? ' ' : '') . h($text);
echo $this->Html->tag('button', $content, array_merge([
	'type' => $type,
	'class' => trim('btn cli-cmp-btn ' . $class),
	'escape' => false,
], $attrs));
