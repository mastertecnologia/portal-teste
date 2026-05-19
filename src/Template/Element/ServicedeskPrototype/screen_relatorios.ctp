<?php
/**
 * @var \App\View\AppView $this
 * @var array<string,mixed> $screen
 * @var array<string,mixed> $charts
 */
$volume = (array)($charts['volume_30d'] ?? []);
$categorias = (array)($charts['categorias'] ?? []);
$tecnicos = (array)($charts['tecnicos'] ?? []);
$maxVol = 1;
foreach ($volume as $v) {
	$maxVol = max($maxVol, (int)($v['abertos'] ?? 0));
}
$barColors = ['#1D9E75', '#0C447C', '#D946A0', '#6B5B95', '#e9a025', '#888'];
?>
<div class="sdp-relatorios-grid">
	<div class="sdp-card">
		<div class="sdp-sec-title"><?= h(__('Volume por dia · 30 dias')) ?></div>
		<?php if ($volume === []) : ?>
			<p class="text-muted" style="margin:0;font-size:12px;"><?= h(__('Sem dados de volume.')) ?></p>
		<?php else : ?>
			<div class="sdp-bar-chart" role="img" aria-label="<?= h(__('Gráfico de volume')) ?>">
				<?php foreach ($volume as $v) : ?>
					<?php
					$n = (int)($v['abertos'] ?? 0);
					$h = $maxVol > 0 ? max(4, (int)round(100 * $n / $maxVol)) : 4;
					?>
					<div class="sdp-bar-col" title="<?= h((string)($v['day'] ?? '') . ': ' . $n) ?>">
						<div class="sdp-bar-fill" style="height:<?= (int)$h ?>%;"></div>
						<span class="sdp-bar-lbl"><?= h((string)($v['day'] ?? '')) ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>

	<div class="sdp-card">
		<div class="sdp-sec-title"><?= h(__('Distribuição por assunto/categoria')) ?></div>
		<?php if ($categorias === []) : ?>
			<p class="text-muted" style="margin:0;font-size:12px;"><?= h(__('Sem categorias.')) ?></p>
		<?php else : ?>
			<div class="sdp-cat-bars">
				<?php foreach ($categorias as $i => $cat) : ?>
					<?php
					$pct = (float)($cat['pct'] ?? 0);
					$color = $barColors[$i % count($barColors)];
					?>
					<div class="sdp-cat-row">
						<div class="sdp-cat-head">
							<span><?= h(\Cake\Utility\Text::truncate((string)($cat['label'] ?? ''), 42, ['ellipsis' => '…'])) ?></span>
							<strong><?= (int)($cat['count'] ?? 0) ?> (<?= h((string)$pct) ?>%)</strong>
						</div>
						<div class="sdp-cat-track"><div class="sdp-cat-fill" style="width:<?= h((string)max(2, $pct)) ?>%;background:<?= h($color) ?>;"></div></div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</div>

<?php if ($tecnicos !== []) : ?>
	<div class="sdp-card" style="margin-top:14px;padding:0;overflow-x:auto;">
		<div class="sdp-sec-title" style="padding:12px 14px 0;"><?= h(__('Performance por técnico · 30 dias')) ?></div>
		<table class="table table-striped table-condensed" style="margin:0;font-size:12px;">
			<thead>
				<tr>
					<th><?= h(__('Técnico')) ?></th>
					<th class="text-right"><?= h(__('Atribuídos')) ?></th>
					<th class="text-right"><?= h(__('Resolvidos')) ?></th>
					<th class="text-right"><?= h(__('Taxa')) ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($tecnicos as $tec) : ?>
					<tr>
						<td><strong><?= h((string)($tec['nome'] ?? '')) ?></strong></td>
						<td class="text-right"><?= (int)($tec['atribuidos'] ?? 0) ?></td>
						<td class="text-right"><?= (int)($tec['resolvidos'] ?? 0) ?></td>
						<td class="text-right"><?= (int)($tec['taxa'] ?? 0) ?>%</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
<?php endif; ?>
