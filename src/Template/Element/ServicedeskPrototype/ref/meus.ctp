<?php
/**
 * @var \App\View\AppView $this
 * @var array<string,mixed> $screen
 */
$title = (string)($screen['title'] ?? __('Meus tickets'));
$subtitle = (string)($screen['subtitle'] ?? '');
$kpis = (array)($screen['kpis'] ?? []);
$rows = (array)($screen['rows'] ?? []);
$H = $this->ServicedeskPrototype;
$toolbar = [
	['label' => '📋 ' . __('Fila completa'), 'url' => $H->sdpPage('fila')],
	['label' => '👥 ' . __('Meu grupo'), 'url' => $H->sdpPage('grupo')],
	['label' => '+ ' . __('Abrir chamado'), 'url' => ['controller' => 'Servicedesk', 'action' => 'add'], 'class' => 'btn btn-primary btn-sm'],
];
?>
<?= $this->element('ServicedeskPrototype/ref/header', compact('title', 'subtitle', 'toolbar') + ['eyebrow' => __('Service Desk')]) ?>

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
				<?php if (!empty($k['hint'])) : ?>
					<div style="font-size:11px;color:var(--text-muted);"><?= h((string)$k['hint']) ?></div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
<?php endif; ?>

<div class="card" style="margin-bottom:14px;padding:0;overflow:hidden;">
	<div style="display:flex;border-bottom:1px solid var(--border);">
		<div style="padding:12px 16px;border-bottom:3px solid var(--teal);"><strong style="font-size:13px;color:var(--teal-dark);">📌 <?= h(__('Ativos')) ?> (<?= h((string)count($rows)) ?>)</strong></div>
	</div>
	<div style="padding:14px;display:flex;flex-direction:column;gap:8px;">
		<?php foreach ($rows as $row) : ?>
			<?php
			$id = (int)($row['id'] ?? 0);
			$sla = (string)($row['sla_status'] ?? '');
			$viol = $sla === 'violado';
			$border = $viol ? 'var(--red)' : 'var(--border-light)';
			$bgCard = $viol ? '#FEF2F2' : 'var(--bg-surface)';
			$icon = $viol ? '🚨' : '🔵';
			$url = $H->sdpTicketUrl($id);
			?>
			<div style="display:flex;align-items:flex-start;gap:12px;padding:14px;border:1px solid <?= h($border) ?>;background:<?= h($bgCard) ?>;border-radius:var(--radius);cursor:pointer;" onclick="window.location.href='<?= h($url) ?>'">
				<div style="font-size:24px;flex-shrink:0;"><?= $icon ?></div>
				<div style="flex:1;">
					<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:6px;margin-bottom:4px;">
						<strong style="font-size:14px;<?= $viol ? 'color:#7A1822;' : '' ?>">#<?= $id ?> · <?= h(\Cake\Utility\Text::truncate((string)($row['assunto'] ?? ''), 48, ['ellipsis' => '…'])) ?></strong>
						<?php if ($viol) : ?>
							<span style="font-size:11px;color:#7A1822;font-weight:700;">⚠ <?= h(__('SLA ESTOURADO')) ?></span>
						<?php else : ?>
							<span style="font-size:11px;color:var(--teal-dark);"><?= h((string)($row['situacao_label'] ?? '')) ?></span>
						<?php endif; ?>
					</div>
					<div style="font-size:11px;color:var(--text-muted);"><?= h((string)($row['cliente'] ?? '')) ?> · <?= h((string)($row['tecnico'] ?? '')) ?></div>
				</div>
				<?= $this->Html->link(__('Abrir'), $url, ['class' => 'btn btn-ghost btn-sm', 'onclick' => 'event.stopPropagation();']) ?>
			</div>
		<?php endforeach; ?>
		<?php if ($rows === []) : ?>
			<p style="margin:0;color:var(--text-muted);font-size:13px;"><?= h((string)($screen['empty'] ?? __('Sem tickets.'))) ?></p>
		<?php endif; ?>
	</div>
</div>
