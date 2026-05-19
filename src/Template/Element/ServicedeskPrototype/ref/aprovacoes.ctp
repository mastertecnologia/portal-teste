<?php
/**
 * Fila de aprovações (pg-sd-aprovacoes).
 *
 * @var \App\View\AppView $this
 * @var array<string,mixed> $screen
 */
$aprov = (array)($screen['aprovacoes'] ?? []);
$stats = (array)($aprov['stats'] ?? []);
$tabs = (array)($aprov['tabs'] ?? []);
$tab = (string)($aprov['tab'] ?? 'pendentes');
$items = (array)($aprov['items'] ?? []);
$title = (string)($screen['title'] ?? '');
$subtitle = (string)($screen['subtitle'] ?? '');
$links = (array)($screen['links'] ?? []);
$empty = (string)($screen['empty'] ?? '');
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

$tagClass = static function (string $style): string {
	$map = [
		'pink' => 'sdp-ap-tag-pink',
		'amber' => 'sdp-ap-tag-amber',
		'blue' => 'sdp-ap-tag-blue',
		'red' => 'sdp-ap-tag-red',
		'purple' => 'sdp-ap-tag-purple',
		'teal' => 'sdp-ap-tag-teal',
	];

	return $map[$style] ?? 'sdp-ap-tag-amber';
};

$dueClass = static function (array $due): string {
	$st = (string)($due['style'] ?? 'amber');
	if ($st === 'red') {
		return 'sdp-ap-due-red';
	}
	if ($st === 'pending') {
		return 'sdp-ap-due-pending';
	}

	return 'sdp-ap-due-amber';
};
?>
<div id="pg-sd-aprovacoes" class="sdp-ap-page">
	<?= $this->element('ServicedeskPrototype/ref/header', compact('title', 'subtitle', 'toolbar') + ['eyebrow' => __('Service Desk')]) ?>

	<div class="summary-grid sdp-ap-kpis">
		<div class="summary-card sdp-ap-kpi-pend">
			<div class="lbl"><?= h(__('Pendentes')) ?></div>
			<div class="val"><?= h((string)($stats['pendentes'] ?? '0')) ?></div>
			<div class="sdp-ap-kpi-hint"><?= h(__('requerem ação')) ?></div>
		</div>
		<div class="summary-card sdp-ap-kpi-ok">
			<div class="lbl"><?= h(__('Aprovadas mês')) ?></div>
			<div class="val"><?= h((string)($stats['aprovadas_mes'] ?? '0')) ?></div>
			<div class="sdp-ap-kpi-hint"><?= h((string)($stats['trend'] ?? '—')) ?></div>
		</div>
		<div class="summary-card sdp-ap-kpi-no">
			<div class="lbl"><?= h(__('Reprovadas mês')) ?></div>
			<div class="val"><?= h((string)($stats['reprovadas_mes'] ?? '0')) ?></div>
			<div class="sdp-ap-kpi-hint"><?= h((string)($stats['reprovacao_pct'] ?? '0%')) ?> <?= h(__('reprovação')) ?></div>
		</div>
		<div class="summary-card sdp-ap-kpi-time">
			<div class="lbl"><?= h(__('T. médio aprovação')) ?></div>
			<div class="val"><?= h((string)($stats['tempo_medio'] ?? '—')) ?></div>
			<div class="sdp-ap-kpi-hint"><?= h((string)($stats['sla_label'] ?? '')) ?></div>
		</div>
	</div>

	<div class="card sdp-ap-panel">
		<div class="sdp-ap-tabs">
			<?php foreach ($tabs as $t) : ?>
				<?php
				$tid = (string)($t['id'] ?? '');
				$active = $tid === $tab;
				$url = $H->sdpPage('aprovacoes', ['tab' => $tid]);
				?>
				<?= $this->Html->link(
					'<strong>' . h((string)($t['icon'] ?? '')) . ' ' . h((string)($t['label'] ?? '')) . ' (' . h((string)($t['count'] ?? 0)) . ')</strong>',
					$url,
					['class' => 'sdp-ap-tab' . ($active ? ' sdp-ap-tab-active' : ''), 'escape' => false]
				) ?>
			<?php endforeach; ?>
		</div>

		<div class="sdp-ap-list">
			<?php if ($items === []) : ?>
				<p class="sdp-ap-empty"><?= h($empty !== '' ? $empty : __('Sem registos.')) ?></p>
			<?php else : ?>
				<?php foreach ($items as $it) : ?>
					<?php
					$due = (array)($it['due_badge'] ?? []);
					$bodyMode = (string)($it['body_mode'] ?? 'text');
					$finance = (array)($it['finance'] ?? []);
					$bullets = (array)($it['bullets'] ?? []);
					$actions = (array)($it['actions'] ?? []);
					?>
					<div class="sdp-ap-card">
						<div class="sdp-ap-card-head">
							<div>
								<div class="sdp-ap-card-tags">
									<span class="sdp-ap-tag <?= h($tagClass((string)($it['tag_style'] ?? 'amber'))) ?>"><?= h((string)($it['tag'] ?? '')) ?></span>
									<strong class="sdp-ap-card-title"><?= h((string)($it['title'] ?? '')) ?></strong>
								</div>
								<div class="sdp-ap-card-meta"><?= h((string)($it['meta'] ?? '')) ?></div>
							</div>
							<?php if ($due !== []) : ?>
								<span class="sdp-ap-due <?= h($dueClass($due)) ?>"><?= h((string)($due['text'] ?? '')) ?></span>
							<?php endif; ?>
						</div>

						<?php if ($bodyMode === 'finance' && $finance !== []) : ?>
							<div class="sdp-ap-body-box">
								<div class="sdp-ap-fin-row"><span><?= h(__('Valor original')) ?></span><strong><?= h((string)($finance['original'] ?? '—')) ?></strong></div>
								<?php if (($finance['discount'] ?? '—') !== '—') : ?>
									<div class="sdp-ap-fin-row"><span><?= h(__('Desconto solicitado')) ?></span><strong class="sdp-ap-fin-disc"><?= h((string)$finance['discount']) ?></strong></div>
								<?php endif; ?>
								<div class="sdp-ap-fin-row sdp-ap-fin-final"><span><?= h(__('Valor final')) ?></span><strong><?= h((string)($finance['final'] ?? '—')) ?></strong></div>
							</div>
							<?php if (!empty($it['body_text'])) : ?>
								<p class="sdp-ap-motivo"><strong><?= h(__('Motivo')) ?>:</strong> <?= h((string)$it['body_text']) ?></p>
							<?php endif; ?>
						<?php elseif ($bodyMode === 'bullets' && $bullets !== []) : ?>
							<div class="sdp-ap-body-box sdp-ap-bullets">
								<?php foreach ($bullets as $b) : ?>
									<div><strong><?= h((string)($b['label'] ?? '')) ?>:</strong> <?= h((string)($b['text'] ?? '')) ?></div>
								<?php endforeach; ?>
							</div>
						<?php elseif ((string)($it['body_text'] ?? '') !== '') : ?>
							<div class="sdp-ap-body-box">
								<?php if ($bodyMode === 'text' && strpos((string)$it['body_text'], '"') === 0) : ?>
									<div class="sdp-ap-body-label"><?= h(__('Justificativa')) ?></div>
								<?php endif; ?>
								<div class="sdp-ap-body-text"><?= h((string)$it['body_text']) ?></div>
							</div>
						<?php endif; ?>

						<?php if ($actions !== []) : ?>
							<div class="sdp-ap-actions">
								<?php foreach ($actions as $act) : ?>
									<?= $this->Html->link(
										(string)($act['label'] ?? ''),
										$act['url'] ?? '#',
										['class' => (string)($act['class'] ?? 'btn btn-ghost btn-sm'), 'escape' => false]
									) ?>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>
</div>

