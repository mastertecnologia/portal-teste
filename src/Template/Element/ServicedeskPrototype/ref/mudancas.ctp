<?php
/**
 * Gestão de Mudanças (CAB) — mockup pg-sd-mudancas.
 *
 * @var \App\View\AppView $this
 * @var array<string,mixed> $screen
 */
$mud = (array)($screen['mudancas'] ?? []);
$kpis = (array)($mud['kpis'] ?? $screen['kpis'] ?? []);
$tabs = (array)($mud['tabs'] ?? []);
$cards = (array)($mud['cards'] ?? []);
$calendar = (array)($mud['calendar'] ?? []);
$H = $this->ServicedeskPrototype;

$riskBadge = static function (string $risk): array {
	if ($risk === 'high') {
		return ['bg' => '#F8D8DA', 'color' => '#7A1822', 'label' => '⚠ ' . __('ALTO RISCO')];
	}

	return ['bg' => '#FAEEDA', 'color' => '#8A4D02', 'label' => '🟡 ' . __('MÉDIO')];
};
?>
<div id="pg-sd-mudancas" class="pgm-sd-prototype">
	<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
		<div>
			<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Service Desk · ITIL')) ?></div>
			<h1 style="font-size:22px;font-weight:600;margin:0;">⚙ <?= h((string)($screen['title'] ?? __('Gestão de Mudanças (Change Management)'))) ?></h1>
			<div style="font-size:12px;color:var(--text-muted);"><?= h((string)($screen['subtitle'] ?? '')) ?></div>
		</div>
		<div style="display:flex;gap:8px;flex-wrap:wrap;">
			<a class="btn btn-ghost btn-sm" href="<?= h($H->sdpPage('fila')) ?>">← <?= h(__('Voltar')) ?></a>
			<button type="button" class="btn btn-ghost btn-sm" disabled>📅 <?= h(__('Janelas de mudança')) ?></button>
			<button type="button" class="btn btn-primary btn-sm" disabled>+ <?= h(__('Nova mudança')) ?></button>
		</div>
	</div>

	<?php if ($kpis !== []) : ?>
		<div class="summary-grid" style="margin-bottom:14px;">
			<?php foreach ($kpis as $k) : ?>
				<?php
				$border = (string)($k['border'] ?? 'var(--teal)');
				$bg = (string)($k['bg'] ?? '');
				$valColor = (string)($k['val_color'] ?? 'var(--teal-dark)');
				$style = 'border-left:3px solid ' . $border . ';';
				if ($bg !== '') {
					$style .= 'background:' . $bg . ';';
				}
				?>
				<div class="summary-card" style="<?= h($style) ?>">
					<div class="lbl"><?= h((string)($k['lbl'] ?? '')) ?></div>
					<div class="val" style="color:<?= h($valColor) ?>;"><?= h((string)($k['val'] ?? '')) ?></div>
					<?php if (!empty($k['hint'])) : ?><div style="font-size:11px;color:<?= h(strpos($valColor, 'var(') === 0 ? 'var(--text-muted)' : $valColor) ?>;"><?= h((string)$k['hint']) ?></div><?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="card" style="margin-bottom:14px;padding:0;overflow:hidden;">
		<?php if ($tabs !== []) : ?>
			<div style="display:flex;border-bottom:1px solid var(--border);overflow-x:auto;">
				<?php foreach ($tabs as $tab) :
					$active = !empty($tab['active']);
					$count = (int)($tab['count'] ?? 0);
					$label = (string)($tab['label'] ?? '');
				?>
					<div style="padding:12px 16px;cursor:pointer;white-space:nowrap;<?= $active ? 'border-bottom:3px solid var(--teal);' : 'color:var(--text-muted);' ?>">
						<?php if ($active) : ?><strong style="font-size:13px;color:var(--teal-dark);"><?= h($label) ?> (<?= $count ?>)</strong><?php else : ?><?= h($label) ?> (<?= $count ?>)<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<div style="padding:14px;display:flex;flex-direction:column;gap:10px;">
			<?php if ($cards === []) : ?>
				<p style="margin:0;color:var(--text-muted);"><?= h((string)($screen['empty'] ?? __('Nenhuma mudança em curso.'))) ?></p>
			<?php else : ?>
				<?php foreach ($cards as $card) :
					$risk = (string)($card['risk'] ?? 'medium');
					$rb = $riskBadge($risk);
					$ticketId = (int)($card['ticket_id'] ?? 0);
					$ticketUrl = $ticketId > 0 ? $H->sdpTicketUrl($ticketId) : '';
				?>
					<div style="padding:14px;border:1px solid var(--border);border-radius:var(--radius);<?= $ticketUrl !== '' ? 'cursor:pointer;' : '' ?>"<?php if ($ticketUrl !== '') : ?> onclick="window.location.href='<?= h($ticketUrl) ?>'"<?php endif; ?>>
						<div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px;margin-bottom:8px;">
							<div>
								<div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
									<span class="titulo-cod"><?= h((string)($card['code'] ?? '')) ?></span>
									<strong style="font-size:14px;"><?= h((string)($card['title'] ?? '')) ?></strong>
									<span class="badge" style="background:<?= h($rb['bg']) ?>;color:<?= h($rb['color']) ?>;font-size:10px;"><?= h($rb['label']) ?></span>
								</div>
								<?php if (!empty($card['meta'])) : ?><div style="font-size:11px;color:var(--text-muted);margin-top:4px;"><?= h((string)$card['meta']) ?></div><?php endif; ?>
							</div>
							<span class="badge b-paga" style="font-size:10px;"><?= h((string)($card['status_label'] ?? '')) ?></span>
						</div>
						<div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:8px;font-size:11px;">
							<div><div style="color:var(--text-muted);"><?= h(__('Tipo')) ?></div><strong><?= h((string)($card['tipo'] ?? '—')) ?></strong></div>
							<div><div style="color:var(--text-muted);"><?= h(__('CI afetado')) ?></div><strong><?= h((string)($card['ci'] ?? '—')) ?></strong></div>
							<div><div style="color:var(--text-muted);"><?= h(__('Downtime previsto')) ?></div><strong><?= h((string)($card['downtime'] ?? '—')) ?></strong></div>
							<div><div style="color:var(--text-muted);"><?= h(__('CAB · aprovação')) ?></div><strong style="color:var(--teal-dark);"><?= h((string)($card['cab'] ?? '—')) ?></strong></div>
						</div>
						<?php if (!empty($card['show_actions'])) : ?>
							<div style="display:flex;gap:6px;margin-top:10px;justify-content:flex-end;">
								<button type="button" class="btn btn-ghost btn-xs" disabled>📋 <?= h(__('Plano completo')) ?></button>
								<button type="button" class="btn btn-ghost btn-xs" disabled>🔄 <?= h(__('Rollback plan')) ?></button>
								<button type="button" class="btn btn-primary btn-xs" disabled>🚀 <?= h(__('Executar (em 3 dias)')) ?></button>
							</div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>

	<?php if ($calendar !== []) : ?>
		<div class="card">
			<div class="sec-title">📅 <?= h(__('Calendário · próximas 4 semanas')) ?></div>
			<div style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px;font-size:11px;">
				<?php foreach ([__('Seg'), __('Ter'), __('Qua'), __('Qui'), __('Sex'), __('Sáb'), __('Dom')] as $wd) : ?>
					<div style="padding:6px;text-align:center;color:var(--text-muted);font-weight:600;"><?= h($wd) ?></div>
				<?php endforeach; ?>
				<?php foreach ($calendar as $cell) :
					$risk = (string)($cell['risk'] ?? '');
					$bg = 'var(--bg-surface)';
					$border = '';
					$dayColor = 'inherit';
					if ($risk === 'high') {
						$bg = '#F8D8DA';
						$border = 'border:1px solid var(--red);';
						$dayColor = '#7A1822';
					} elseif ($risk === 'medium') {
						$bg = '#FAEEDA';
						$dayColor = '#8A4D02';
					}
					$labels = (array)($cell['labels'] ?? []);
				?>
					<div style="padding:6px;background:<?= h($bg) ?>;<?= h($border) ?>border-radius:4px;min-height:54px;">
						<strong style="color:<?= h($dayColor) ?>;"><?= (int)($cell['day'] ?? 0) ?></strong>
						<?php foreach ($labels as $lb) : ?>
							<?php if (!empty($lb['code'])) : ?><div style="font-size:9px;color:<?= h($dayColor) ?>;font-weight:600;margin-top:2px;"><?= h((string)$lb['code']) ?></div><?php endif; ?>
							<?php if (!empty($lb['note'])) : ?><div style="font-size:9px;color:<?= h($dayColor) ?>;"><?= h((string)$lb['note']) ?></div><?php endif; ?>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>
			</div>
			<div style="display:flex;gap:14px;margin-top:10px;font-size:11px;color:var(--text-muted);flex-wrap:wrap;">
				<span style="display:flex;align-items:center;gap:4px;"><span style="width:12px;height:12px;background:#F8D8DA;border-radius:3px;"></span><?= h(__('Alto risco')) ?></span>
				<span style="display:flex;align-items:center;gap:4px;"><span style="width:12px;height:12px;background:#FAEEDA;border-radius:3px;"></span><?= h(__('Médio risco')) ?></span>
				<span style="display:flex;align-items:center;gap:4px;"><span style="width:12px;height:12px;background:var(--bg-surface);border-radius:3px;border:1px solid var(--border);"></span><?= h(__('Sem mudança')) ?></span>
			</div>
		</div>
	<?php endif; ?>
</div>
