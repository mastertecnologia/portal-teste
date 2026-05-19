<?php
/**
 * Plantões & disponibilidade (pg-sd-calendar).
 *
 * @var \App\View\AppView $this
 * @var array<string,mixed> $screen
 */
$p = (array)($screen['plantao'] ?? []);
$now = (array)($p['now'] ?? []);
$days = (array)($p['days'] ?? []);
$shifts = (array)($p['shifts'] ?? []);
$absences = (array)($p['absences'] ?? []);
$phones = (array)($p['phones'] ?? []);
$nav = (array)($p['nav'] ?? []);
$monthOptions = (array)($p['month_options'] ?? []);
$title = (string)($screen['title'] ?? '');
$subtitle = (string)($screen['subtitle'] ?? '');
$links = (array)($screen['links'] ?? []);
$emptyHint = (string)($screen['empty'] ?? '');
$H = $this->ServicedeskPrototype;

$toolbar = [];
foreach ($links as $lnk) {
	if (!empty($lnk['label']) && !empty($lnk['url'])) {
		$toolbar[] = [
			'label' => (string)$lnk['label'],
			'url' => $lnk['url'],
			'class' => (string)($lnk['class'] ?? 'btn btn-ghost btn-sm'),
		];
	}
}

$cellClass = static function (string $style): string {
	$map = [
		'teal' => 'sdp-cal-cell-teal',
		'blue' => 'sdp-cal-cell-blue',
		'purple' => 'sdp-cal-cell-purple',
		'pink' => 'sdp-cal-cell-pink',
		'muted' => 'sdp-cal-cell-muted',
		'amber' => 'sdp-cal-cell-amber',
	];

	return $map[$style] ?? 'sdp-cal-cell-muted';
};

$absenceClass = static function (string $style): string {
	$map = [
		'amber' => 'sdp-cal-abs-amber',
		'blue' => 'sdp-cal-abs-blue',
		'purple' => 'sdp-cal-abs-purple',
	];

	return $map[$style] ?? 'sdp-cal-abs-amber';
};
?>
<div id="pg-sd-calendar" class="sdp-cal-page">
	<?= $this->element('ServicedeskPrototype/ref/header', compact('title', 'subtitle', 'toolbar') + ['eyebrow' => __('Service Desk')]) ?>

	<form method="get" action="<?= h($H->sdpPage('calendar')) ?>" class="sdp-cal-month-form" style="margin:-8px 0 14px;display:flex;justify-content:flex-end;">
		<select name="month" class="sdp-cal-month-select" onchange="this.form.submit()" aria-label="<?= h(__('Mês')) ?>">
			<?php foreach ($monthOptions as $mo) : ?>
				<option value="<?= h((string)($mo['value'] ?? '')) ?>" <?= !empty($mo['selected']) ? 'selected' : '' ?>><?= h((string)($mo['label'] ?? '')) ?></option>
			<?php endforeach; ?>
		</select>
	</form>

	<div class="card sdp-cal-now-banner">
		<div class="sdp-cal-now-inner">
			<div>
				<div class="sdp-cal-now-eyebrow"><?= h(__('PLANTÃO AGORA')) ?> · <?= h((string)($now['timestamp'] ?? '')) ?></div>
				<div class="sdp-cal-now-title">🟢 <?= h(__('Cobertura ativa')) ?> · <?= h(sprintf(__('%d técnicos com atividade'), (int)($now['online_count'] ?? 0))) ?></div>
			</div>
			<div class="sdp-cal-now-pills">
				<div class="sdp-cal-now-pill">
					<div class="sdp-cal-now-pill-lbl"><?= h(__('N1 hoje')) ?></div>
					<strong><?= h((string)($now['n1_label'] ?? '—')) ?></strong>
				</div>
				<div class="sdp-cal-now-pill">
					<div class="sdp-cal-now-pill-lbl"><?= h(__('N2/N3 hoje')) ?></div>
					<strong><?= h((string)($now['n2_label'] ?? '—')) ?></strong>
				</div>
				<div class="sdp-cal-now-pill">
					<div class="sdp-cal-now-pill-lbl"><?= h(__('Plantão noturno')) ?></div>
					<strong><?= h((string)($now['noite_label'] ?? '—')) ?></strong>
				</div>
			</div>
		</div>
	</div>

	<div class="card sdp-cal-schedule-card">
		<div class="sdp-cal-schedule-head">
			<div class="sec-title sdp-sec-no-line" style="margin:0;border:none;">📅 <?= h(__('Escala')) ?> · <?= h(__('semana')) ?> <?= h((string)($p['week_label'] ?? '')) ?></div>
			<div class="sdp-cal-week-nav">
				<?= $this->Html->link('‹ ' . __('Anterior'), $H->sdpPage('calendar', ['week' => (string)($nav['prev'] ?? '')]), ['class' => 'btn btn-ghost btn-xs']) ?>
				<?= $this->Html->link(__('Hoje'), $H->sdpPage('calendar', ['week' => (string)($nav['today'] ?? '')]), [
					'class' => 'btn btn-ghost btn-xs sdp-cal-nav-today',
				]) ?>
				<?= $this->Html->link(__('Próxima') . ' ›', $H->sdpPage('calendar', ['week' => (string)($nav['next'] ?? '')]), ['class' => 'btn btn-ghost btn-xs']) ?>
			</div>
		</div>

		<?php if ($emptyHint !== '') : ?>
			<p class="sdp-cal-hint"><?= h($emptyHint) ?></p>
		<?php endif; ?>

		<div class="sdp-cal-table-wrap">
			<table class="sdp-cal-table">
				<thead>
					<tr>
						<th class="sdp-cal-th-shift"></th>
						<?php foreach ($days as $day) : ?>
							<th class="sdp-cal-th-day<?= !empty($day['is_today']) ? ' sdp-cal-th-today' : '' ?>">
								<strong><?= h((string)($day['label'] ?? '')) ?></strong>
								<?php if (!empty($day['is_today'])) : ?>
									<div class="sdp-cal-today-tag"><?= h(__('hoje')) ?></div>
								<?php endif; ?>
							</th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($shifts as $row) : ?>
						<tr>
							<td class="sdp-cal-shift-label">
								<?= h((string)($row['icon'] ?? '')) ?> <?= h((string)($row['label'] ?? '')) ?>
								<br><span><?= h((string)($row['hours'] ?? '')) ?></span>
							</td>
							<?php foreach ((array)($row['cells'] ?? []) as $cell) : ?>
								<?php $st = (string)($cell['style'] ?? 'muted'); ?>
								<td class="sdp-cal-cell <?= h($cellClass($st)) ?>"><?= h((string)($cell['text'] ?? '—')) ?></td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>

	<div class="g2 sdp-cal-bottom">
		<div class="card">
			<div class="sec-title">🏖 <?= h(__('Ausências programadas')) ?></div>
			<?php if ($absences === []) : ?>
				<p style="margin:0;font-size:12px;color:var(--text-muted);"><?= h(__('Nenhuma ausência registrada na agenda para o período.')) ?></p>
			<?php else : ?>
				<div class="sdp-cal-abs-list">
					<?php foreach ($absences as $abs) : ?>
						<div class="sdp-cal-abs-item <?= h($absenceClass((string)($abs['style'] ?? 'amber'))) ?>">
							<div class="sdp-cal-abs-name"><?= h((string)($abs['name'] ?? '')) ?> · <?= h((string)($abs['type'] ?? '')) ?></div>
							<div class="sdp-cal-abs-period"><?= h((string)($abs['period'] ?? '')) ?></div>
							<div class="sdp-cal-abs-coverage">📝 <?= h((string)($abs['coverage'] ?? '')) ?></div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<div class="card">
			<div class="sec-title">📞 <?= h(__('Telefones de plantão')) ?></div>
			<div class="sdp-cal-phones">
				<?php foreach ($phones as $ph) : ?>
					<div class="sdp-cal-phone-item">
						<div class="sdp-cal-phone-title"><?= h((string)($ph['title'] ?? '')) ?></div>
						<div class="sdp-cal-phone-num"><?= h((string)($ph['phone'] ?? '—')) ?></div>
						<div class="sdp-cal-phone-meta"><?= h((string)($ph['meta'] ?? '')) ?></div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</div>
