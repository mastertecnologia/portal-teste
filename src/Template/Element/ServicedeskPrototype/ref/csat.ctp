<?php
/**
 * CSAT & NPS — mockup pg-sd-csat.
 *
 * @var \App\View\AppView $this
 * @var array<string,mixed> $screen
 */
$csat = (array)($screen['csat'] ?? []);
$dist = (array)($csat['distribuicao'] ?? []);
$breakdown = (array)($csat['breakdown'] ?? []);
$comentarios = (array)($csat['comentarios'] ?? []);
$tendencia = (array)($csat['tendencia'] ?? []);
$periodOpts = (array)($csat['period_opts'] ?? []);
$periodVal = (string)($csat['period'] ?? '30');
$H = $this->ServicedeskPrototype;
$csatMedia = $csat['csat_media'] ?? null;
$nps = $csat['nps'] ?? null;
$csatDelta = $csat['csat_delta'] ?? null;
$npsDelta = $csat['nps_delta'] ?? null;
?>
<div id="pg-sd-csat" class="pgm-sd-prototype">
	<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
		<div>
			<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Service Desk')) ?></div>
			<h1 style="font-size:22px;font-weight:600;margin:0;">⭐ <?= h((string)($screen['title'] ?? __('CSAT & NPS · Satisfação'))) ?></h1>
			<div style="font-size:12px;color:var(--text-muted);"><?= h((string)($screen['subtitle'] ?? '')) ?></div>
		</div>
		<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
			<?php foreach ((array)($screen['links'] ?? []) as $lnk) :
				if (empty($lnk['label']) || empty($lnk['url'])) {
					continue;
				}
				$cls = (string)($lnk['class'] ?? 'btn btn-ghost btn-sm');
				echo $this->Html->link((string)$lnk['label'], $lnk['url'], ['class' => $cls, 'escape' => false]);
			endforeach; ?>
			<form method="get" action="<?= h($H->sdpPage('csat')) ?>" style="margin:0;">
				<select name="period" style="padding:6px 10px;border:1px solid var(--border);border-radius:6px;font-size:12px;background:#fff;" onchange="this.form.submit()">
					<?php foreach ($periodOpts as $pk => $pl) : ?>
						<option value="<?= h((string)$pk) ?>"<?= $periodVal === (string)$pk ? ' selected' : '' ?>><?= h((string)$pl) ?></option>
					<?php endforeach; ?>
				</select>
			</form>
			<button type="button" class="btn btn-ghost btn-sm" disabled>📥 <?= h(__('Exportar')) ?></button>
			<button type="button" class="btn btn-primary btn-sm" disabled>⚙ <?= h(__('Editar pesquisa')) ?></button>
		</div>
	</div>

	<div class="g2" style="margin-bottom:14px;">
		<div class="card" style="background:linear-gradient(135deg,#0a3d2c 0%,#1D9E75 100%);color:#fff;border:none;">
			<div style="text-align:center;padding:14px;">
				<div style="font-size:14px;opacity:.85;text-transform:uppercase;font-weight:600;letter-spacing:.4px;margin-bottom:8px;"><?= h(__('CSAT MÉDIO · %d DIAS', (int)($csat['period_days'] ?? 30))) ?></div>
				<div style="font-size:72px;font-weight:700;line-height:1;"><?= $csatMedia !== null ? h(number_format((float)$csatMedia, 1, ',', '.')) : '—' ?></div>
				<div style="font-size:24px;margin-top:6px;"><?= h((string)($csat['csat_stars_display'] ?? '—')) ?></div>
				<?php if ($csatDelta !== null) : ?>
					<div style="font-size:13px;opacity:.85;margin-top:8px;"><?= ($csatDelta >= 0 ? '↑ ' : '↓ ') . h(abs((float)$csatDelta)) ?> <?= h(__('vs período anterior')) ?></div>
				<?php endif; ?>
			</div>
			<?php if ($dist !== []) : ?>
				<div style="background:rgba(255,255,255,.1);border-radius:var(--radius);padding:14px;margin-top:10px;">
					<div style="font-size:11px;text-transform:uppercase;font-weight:600;letter-spacing:.4px;margin-bottom:10px;"><?= h(__('Distribuição')) ?></div>
					<div style="display:flex;flex-direction:column;gap:6px;">
						<?php foreach ($dist as $d) : ?>
							<div style="display:flex;align-items:center;gap:8px;font-size:12px;">
								<span style="width:60px;"><?= h((string)($d['stars_label'] ?? '')) ?></span>
								<div style="flex:1;height:10px;background:rgba(255,255,255,.2);border-radius:5px;overflow:hidden;">
									<div style="height:100%;width:<?= (int)($d['pct'] ?? 0) ?>%;background:<?= (int)($d['stars'] ?? 0) <= 2 ? '#F8D8DA' : '#fff' ?>;"></div>
								</div>
								<span style="width:70px;text-align:right;"><?= (int)($d['count'] ?? 0) ?> · <?= (int)($d['pct'] ?? 0) ?>%</span>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
		</div>

		<div class="card" style="background:linear-gradient(135deg,#3D2D63 0%,#6B5B95 100%);color:#fff;border:none;">
			<div style="text-align:center;padding:14px;">
				<div style="font-size:14px;opacity:.85;text-transform:uppercase;font-weight:600;letter-spacing:.4px;margin-bottom:8px;"><?= h(__('NPS · NET PROMOTER SCORE')) ?></div>
				<div style="font-size:72px;font-weight:700;line-height:1;"><?= $nps !== null ? (($nps >= 0 ? '+' : '') . (int)$nps) : '—' ?></div>
				<div style="font-size:14px;margin-top:6px;opacity:.85;"><?= h((string)($csat['nps_zone'] ?? '—')) ?></div>
				<?php if ($npsDelta !== null) : ?>
					<div style="font-size:13px;opacity:.85;margin-top:8px;"><?= ($npsDelta >= 0 ? '↑ ' : '↓ ') . abs((int)$npsDelta) ?> <?= h(__('pontos vs período anterior')) ?></div>
				<?php endif; ?>
			</div>
			<?php if ((int)($breakdown['total_nps'] ?? 0) > 0) : ?>
				<div style="background:rgba(255,255,255,.1);border-radius:var(--radius);padding:14px;margin-top:10px;">
					<div style="font-size:11px;text-transform:uppercase;font-weight:600;letter-spacing:.4px;margin-bottom:10px;"><?= h(__('Composição')) ?></div>
					<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;text-align:center;">
						<div style="padding:10px;background:rgba(16,185,129,.3);border-radius:8px;">
							<div style="font-size:24px;font-weight:700;"><?= (int)($breakdown['promotores_pct'] ?? 0) ?>%</div>
							<div style="font-size:11px;opacity:.85;"><?= h(__('Promotores (9-10)')) ?></div>
							<div style="font-size:10px;opacity:.7;margin-top:2px;"><?= (int)($breakdown['promotores'] ?? 0) ?> <?= h(__('pessoas')) ?></div>
						</div>
						<div style="padding:10px;background:rgba(245,158,11,.3);border-radius:8px;">
							<div style="font-size:24px;font-weight:700;"><?= (int)($breakdown['neutros_pct'] ?? 0) ?>%</div>
							<div style="font-size:11px;opacity:.85;"><?= h(__('Neutros (7-8)')) ?></div>
							<div style="font-size:10px;opacity:.7;margin-top:2px;"><?= (int)($breakdown['neutros'] ?? 0) ?> <?= h(__('pessoas')) ?></div>
						</div>
						<div style="padding:10px;background:rgba(248,113,113,.3);border-radius:8px;">
							<div style="font-size:24px;font-weight:700;"><?= (int)($breakdown['detratores_pct'] ?? 0) ?>%</div>
							<div style="font-size:11px;opacity:.85;"><?= h(__('Detratores (0-6)')) ?></div>
							<div style="font-size:10px;opacity:.7;margin-top:2px;"><?= (int)($breakdown['detratores'] ?? 0) ?> <?= h(__('pessoas')) ?></div>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<div class="card" style="margin-bottom:14px;">
		<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:8px;">
			<div class="sec-title" style="margin:0;border:none;">💬 <?= h(__('Comentários recentes')) ?></div>
			<div style="display:flex;gap:6px;flex-wrap:wrap;">
				<button type="button" class="btn btn-ghost btn-xs" style="background:var(--teal-light);color:var(--teal-dark);" disabled><?= h(__('Todos')) ?></button>
				<button type="button" class="btn btn-ghost btn-xs" disabled>⭐⭐⭐⭐⭐ <?= h(__('Promotores')) ?></button>
				<button type="button" class="btn btn-ghost btn-xs" disabled>⭐ <?= h(__('Detratores')) ?></button>
			</div>
		</div>
		<?php if ($comentarios === []) : ?>
			<p style="margin:0;font-size:12px;color:var(--text-muted);"><?= h((string)($screen['empty'] ?? __('Nenhuma resposta no período.'))) ?></p>
		<?php else : ?>
			<div style="display:flex;flex-direction:column;gap:10px;">
				<?php foreach ($comentarios as $c) :
					$st = (array)($c['style'] ?? []);
					$tone = (string)($c['tone'] ?? 'promotor');
				?>
					<div style="padding:12px;background:<?= h((string)($st['bg'] ?? '#F0FDF4')) ?>;border-left:3px solid <?= h((string)($st['border'] ?? 'var(--teal)')) ?>;border-radius:var(--radius);">
						<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:6px;margin-bottom:6px;">
							<strong style="font-size:12px;color:<?= h((string)($st['title_color'] ?? 'var(--teal-dark)')) ?>;"><?= h((string)($c['title'] ?? '')) ?></strong>
							<span style="font-size:11px;color:var(--text-muted);"><?= h((string)($c['meta'] ?? '')) ?></span>
						</div>
						<?php if (!empty($c['comentario'])) : ?>
							<div style="font-size:12px;line-height:1.5;">"<?= h((string)$c['comentario']) ?>"</div>
						<?php endif; ?>
						<?php if (!empty($c['tags'])) : ?>
							<div style="display:flex;gap:4px;margin-top:6px;flex-wrap:wrap;">
								<?php foreach ((array)$c['tags'] as $tag) : ?>
									<span style="padding:2px 6px;background:#fff;color:var(--teal-dark);border-radius:4px;font-size:10px;"><?= h((string)$tag) ?></span>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
						<?php if (!empty($st['actions'])) : ?>
							<div style="display:flex;gap:6px;margin-top:8px;justify-content:flex-end;flex-wrap:wrap;">
								<button type="button" class="btn btn-ghost btn-xs" disabled>📞 <?= h(__('Ligar para cliente')) ?></button>
								<?php if ($tone === 'detrator') : ?>
									<button type="button" class="btn btn-red btn-xs" disabled>⚠ <?= h(__('Escalonar diretor')) ?></button>
								<?php else : ?>
									<button type="button" class="btn btn-amber btn-xs" disabled>📝 <?= h(__('Responder')) ?></button>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>

	<?php if ($tendencia !== []) : ?>
		<div class="card">
			<div class="sec-title">📈 <?= h(__('Tendência CSAT · últimos 6 meses')) ?></div>
			<div style="height:180px;background:var(--bg-surface);border-radius:var(--radius);padding:14px;display:flex;align-items:end;gap:14px;">
				<?php foreach ($tendencia as $t) : ?>
					<div style="flex:1;display:flex;flex-direction:column;align-items:center;">
						<div style="width:60%;background:<?= !empty($t['current']) ? 'var(--teal)' : 'var(--teal-mid)' ?>;height:<?= max(8, (int)($t['height_pct'] ?? 10)) ?>%;border-radius:4px 4px 0 0;min-height:8px;"></div>
						<span style="font-size:11px;margin-top:6px;<?= !empty($t['current']) ? 'font-weight:700;color:var(--teal-dark);' : '' ?>"><?= h($t['avg'] !== null ? number_format((float)$t['avg'], 1, ',', '.') : '—') ?></span>
						<span style="font-size:10px;color:var(--text-muted);"><?= h((string)($t['label'] ?? '')) ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>
</div>
