<?php
/**
 * Badge de contagem nas abas da ficha (somente se count > 0).
 *
 * @var int|null $count
 */
$count = isset($count) ? (int)$count : 0;
if ($count <= 0) {
	return;
}
?>
<span class="cli-tab-count badge badge-secondary"><?= $count ?></span>
