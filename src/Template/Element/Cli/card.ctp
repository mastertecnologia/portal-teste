<?php
$title = $title ?? '';
$headHtml = $headHtml ?? null;
$extraClass = trim($extraClass ?? '');
?>
<div class="cli-subcard cli-cmp-card<?= $extraClass !== '' ? ' ' . h($extraClass) : '' ?>">
<div class="cli-subcard-head cli-cmp-card__head"><?php
if ($headHtml !== null && $headHtml !== '') {
	echo $headHtml;
} else {
	echo h($title);
}
?></div>
<div class="cli-subcard-body cli-cmp-card__body">
