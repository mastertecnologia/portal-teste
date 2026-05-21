<?php
/**
 * Relatórios SD — KPIs + gráficos + performance por técnico.
 *
 * @var \App\View\AppView $this
 * @var array<string,mixed> $screen
 * @var array<string,mixed> $charts
 */
$kpis = (array)($screen['kpis'] ?? []);
$volume = (array)($charts['volume_30d'] ?? []);
$categorias = (array)($charts['categorias'] ?? []);
$tecnicos = (array)($charts['tecnicos'] ?? []);
$periodDays = (int)($charts['period_days'] ?? 30);
$H = $this->ServicedeskPrototype;
$volMax = 1;
foreach ($volume as $v) {
	$volMax = max($volMax, (int)($v['abertos'] ?? 0), (int)($v['fechados'] ?? 0));
}
$catColors = ['var(--teal)', 'var(--blue)', '#D946A0', '#6B5B95', 'var(--amber)', 'var(--text-muted)'];
$volFirst = $volume !== [] ? (string)($volume[0]['day'] ?? '') : '';
$volMid = $volume !== [] ? (string)($volume[(int)floor(count($volume) / 2)]['day'] ?? '') : '';
$volLast = $volume !== [] ? (string)($volume[count($volume) - 1]['day'] ?? '') : '';
$periodOptions = [
	30 => __('Período: últimos 30 dias'),
	1 => __('Hoje'),
	7 => __('Esta semana'),
	90 => __('Trimestre'),
	365 => __('Último ano'),
];
$volumeTitle = $periodDays === 30
	? __('Volume de tickets por dia · últimos 30 dias')
	: sprintf(__('Volume de tickets por dia · últimos %d dias'), $periodDays);
?>
<div id="pg-sd-relatorios" class="pgm-sd-prototype">
	<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
		<div>
			<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Service Desk')) ?></div>
			<h1 style="font-size:22px;font-weight:600;">📊 <?= h((string)($screen['title'] ?? __('Relatórios & Métricas'))) ?></h1>
			<div style="font-size:12px;color:var(--text-muted);"><?= h((string)($screen['subtitle'] ?? '')) ?></div>
		</div>
		<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
			<a class="btn btn-ghost btn-sm" href="<?= h($H->sdpPage('fila')) ?>">← <?= h(__('Voltar')) ?></a>
			<form method="get" action="<?= h($H->sdpPage('relatorios')) ?>" style="margin:0;">
				<select name="period" style="padding:6px 10px;border:1px solid var(--border);border-radius:6px;font-size:12px;background:#fff;" onchange="this.form.submit()" aria-label="<?= h(__('Período')) ?>">
					<?php foreach ($periodOptions as $val => $label) : ?>
						<option value="<?= (int)$val ?>" <?= $periodDays === (int)$val ? 'selected' : '' ?>><?= h($label) ?></option>
					<?php endforeach; ?>
				</select>
			</form>
			<button type="button" class="btn btn-primary btn-sm" disabled title="<?= h(__('Protótipo somente leitura')) ?>">📥 <?= h(__('Exportar')) ?></button>
		</div>
	</div>

	<?php if ($kpis !== []) : ?>
		<div class="summary-grid" style="margin-bottom:14px;">
			<?php foreach ($kpis as $k) : ?>
				<?php
				$alert = !empty($k['alert']);
				$border = (string)($k['border'] ?? ($alert ? 'var(--red)' : 'var(--teal)'));
				$bg = (string)($k['bg'] ?? ($alert ? '#F8D8DA' : ''));
				$valColor = (string)($k['val_color'] ?? ($alert ? '#7A1822' : 'var(--teal-dark)'));
				$style = 'border-left:3px solid ' . $border . ';';
				if ($bg !== '') {
					$style .= 'background:' . $bg . ';';
				}
				?>
				<div class="summary-card" style="<?= h($style) ?>">
					<div class="lbl"><?= h((string)($k['lbl'] ?? '')) ?></div>
					<div class="val" style="color:<?= h($valColor) ?>;"><?= h((string)($k['val'] ?? '')) ?></div>
					<?php if (!empty($k['hint'])) : ?>
						<div style="font-size:11px;color:var(--text-muted);"><?= h((string)$k['hint']) ?></div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="g2" style="margin-bottom:14px;">
		<div class="card">
			<div class="sec-title">📈 <?= h($volumeTitle) ?></div>
			<div style="height:200px;background:var(--bg-surface);border-radius:var(--radius);display:flex;align-items:flex-end;gap:3px;padding:14px;overflow-x:auto;">
				<?php foreach ($volume as $v) : ?>
					<?php
					$a = (int)($v['abertos'] ?? 0);
					$hPct = $volMax > 0 ? (int)max(8, round(100 * $a / $volMax)) : 8;
					$isWeekend = !empty($v['weekend']);
					?>
					<div style="flex:1;min-width:6px;background:<?= $isWeekend ? 'var(--teal-mid)' : 'var(--teal)' ?>;border-radius:4px 4px 0 0;height:<?= $hPct ?>%;" title="<?= h((string)($v['day'] ?? '')) ?>: <?= $a ?>"></div>
				<?php endforeach; ?>
				<?php if ($volume === []) : ?>
					<div style="align-self:center;width:100%;text-align:center;color:var(--text-muted);font-size:12px;"><?= h(__('Sem dados de volume.')) ?></div>
				<?php endif; ?>
			</div>
			<?php if ($volume !== []) : ?>
				<div style="display:flex;justify-content:space-between;font-size:11px;color:var(--text-muted);margin-top:6px;">
					<span><?= h($volFirst) ?></span><span><?= h($volMid) ?></span><span><?= h($volLast) ?></span>
				</div>
			<?php endif; ?>
		</div>

		<div class="card">
			<div class="sec-title">🎯 <?= h(__('Distribuição por categoria')) ?></div>
			<div style="display:flex;flex-direction:column;gap:8px;">
				<?php foreach (array_slice($categorias, 0, 6) as $i => $c) : ?>
					<?php
					$pct = (float)($c['pct'] ?? 0);
					$color = $catColors[$i % count($catColors)];
					?>
					<div>
						<div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:3px;">
							<span><?= h(\Cake\Utility\Text::truncate((string)($c['label'] ?? ''), 36, ['ellipsis' => '…'])) ?></span>
							<strong><?= (int)($c['count'] ?? 0) ?> (<?= h(number_format($pct, 0, ',', '.')) ?>%)</strong>
						</div>
						<div style="height:8px;background:var(--bg-surface);border-radius:4px;overflow:hidden;">
							<div style="height:100%;width:<?= h((string)min(100, $pct)) ?>%;background:<?= h($color) ?>;"></div>
						</div>
					</div>
				<?php endforeach; ?>
				<?php if ($categorias === []) : ?>
					<p style="margin:0;font-size:12px;color:var(--text-muted);"><?= h(__('Sem categorias no período.')) ?></p>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<div class="card">
		<div class="sec-title">🏆 <?= h(__('Performance por técnico')) ?></div>
		<div style="overflow-x:auto;">
			<table style="width:100%;border-collapse:collapse;font-size:12px;">
				<thead>
					<tr style="background:var(--bg-surface);border-bottom:1px solid var(--border);">
						<th style="padding:10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Técnico')) ?></th>
						<th style="padding:10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Nível')) ?></th>
						<th style="padding:10px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Atribuídos')) ?></th>
						<th style="padding:10px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Resolvidos')) ?></th>
						<th style="padding:10px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Taxa')) ?></th>
						<th style="padding:10px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('T. médio')) ?></th>
						<th style="padding:10px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('SLA cumprido')) ?></th>
						<th style="padding:10px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('CSAT')) ?></th>
						<th style="padding:10px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Horas faturáveis')) ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($tecnicos as $t) : ?>
						<?php
						$taxa = (int)($t['taxa'] ?? 0);
						$sla = (int)($t['sla_cumprido'] ?? 0);
						$taxaColor = $taxa >= 90 ? 'var(--teal-dark)' : 'inherit';
						$slaColor = $sla >= 90 ? 'var(--teal-dark)' : 'inherit';
						?>
						<tr style="border-bottom:1px solid var(--border-light);">
							<td style="padding:10px;font-weight:600;"><?= h((string)($t['nome'] ?? '')) ?></td>
							<td style="padding:10px;"><?= h((string)($t['nivel'] ?? '—')) ?></td>
							<td style="padding:10px;text-align:right;"><?= (int)($t['atribuidos'] ?? 0) ?></td>
							<td style="padding:10px;text-align:right;font-weight:600;color:var(--teal-dark);"><?= (int)($t['resolvidos'] ?? 0) ?></td>
							<td style="padding:10px;text-align:right;color:<?= h($taxaColor) ?>;"><?= $taxa ?>%</td>
							<td style="padding:10px;text-align:right;"><?= h((string)($t['tempo_medio'] ?? '—')) ?></td>
							<td style="padding:10px;text-align:right;font-weight:600;color:<?= h($slaColor) ?>;"><?= $sla ?>%</td>
							<td style="padding:10px;text-align:right;"><?= h((string)($t['csat'] ?? '—')) ?></td>
							<td style="padding:10px;text-align:right;font-weight:600;"><?= h((string)($t['horas_faturaveis'] ?? '—')) ?></td>
						</tr>
					<?php endforeach; ?>
					<?php if ($tecnicos === []) : ?>
						<tr><td colspan="9" style="padding:12px;color:var(--text-muted);"><?= h(__('Sem dados de técnicos no período.')) ?></td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
