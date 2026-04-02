<?php
$chart = $chart ?? ['mode' => 'week', 'points' => []];
$points = $chart['points'] ?? [];
$mode = $chart['mode'] ?? 'week';
$sub = $mode === 'month'
	? 'Volume de chamados abertos no período, agrupado por mês.'
	: 'Volume de chamados abertos no período, por semana (início na segunda-feira).';
?>
<?php if (empty($points)) : ?>
<div class="relcli-chart-empty" role="status">
	<p class="relcli-chart-empty__title">Sem dados para o gráfico</p>
	<p class="relcli-chart-empty__hint">Ajuste o período ou os filtros, ou aguarde novos chamados no intervalo.</p>
</div>
<?php else :
	$max = max(1, max(array_column($points, 'count')));
	$n = count($points);
	$w = 640;
	$h = 232;
	$padL = 40;
	$padR = 20;
	$padT = 16;
	$padB = 52;
	$plotW = $w - $padL - $padR;
	$plotH = $h - $padT - $padB;
	$slotW = $plotW / $n;
	$barW = max(6, $slotW * 0.62);
	?>
<figure class="relcli-chart-figure">
	<figcaption class="relcli-sr-only">Gráfico de barras: <?= h($sub) ?></figcaption>
	<svg class="relcli-chart-svg" viewBox="0 0 <?= (int)$w ?> <?= (int)$h ?>" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
		<line x1="<?= (int)$padL ?>" y1="<?= (int)($padT + $plotH) ?>" x2="<?= (int)($w - $padR) ?>" y2="<?= (int)($padT + $plotH) ?>" class="relcli-chart-axis"/>
		<?php
		$baselineY = $padT + $plotH;
		foreach ($points as $i => $pt) :
			$c = (int)$pt['count'];
			$bh = $plotH * ($c / $max);
			$x = $padL + $i * $slotW + ($slotW - $barW) / 2;
			$y = $baselineY - $bh;
			$label = (string)($pt['label'] ?? '');
			?>
		<rect x="<?= sprintf('%.2f', $x) ?>" y="<?= sprintf('%.2f', $y) ?>" width="<?= sprintf('%.2f', $barW) ?>" height="<?= sprintf('%.2f', max(1, $bh)) ?>" class="relcli-chart-bar" rx="3"/>
		<text x="<?= sprintf('%.2f', $x + $barW / 2) ?>" y="<?= (int)($baselineY + 16) ?>" text-anchor="middle" class="relcli-chart-label"><?= h($label) ?></text>
		<?php if ($c > 0) : ?>
		<text x="<?= sprintf('%.2f', $x + $barW / 2) ?>" y="<?= sprintf('%.2f', $y - 6) ?>" text-anchor="middle" class="relcli-chart-value"><?= h((string)$c) ?></text>
		<?php endif; ?>
		<?php endforeach; ?>
	</svg>
	<p class="relcli-chart-caption"><?= h($sub) ?></p>
</figure>
<?php endif; ?>
