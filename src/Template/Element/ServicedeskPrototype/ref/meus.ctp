<?php
/**
 * Meus tickets (pg-sd-meus).
 *
 * @var \App\View\AppView $this
 * @var array<string,mixed> $screen
 */
$title = (string)($screen['title'] ?? __('Meus tickets'));
$subtitle = (string)($screen['subtitle'] ?? '');
$kpis = (array)($screen['kpis'] ?? []);
$tabCounts = (array)($screen['tab_counts'] ?? []);
$tabRows = (array)($screen['tab_rows'] ?? []);
$notificacoes = (array)($screen['notificacoes'] ?? []);
$compromissos = (array)($screen['compromissos'] ?? []);
$activeTab = (string)($screen['active_tab'] ?? 'ativos');
$H = $this->ServicedeskPrototype;
$toolbar = [
	['label' => '📋 ' . __('Fila completa'), 'url' => $H->sdpPage('fila')],
	['label' => '👥 ' . __('Meu grupo'), 'url' => $H->sdpPage('grupo')],
	['label' => '+ ' . __('Abrir chamado'), 'url' => ['controller' => 'Servicedesk', 'action' => 'add'], 'class' => 'btn btn-primary btn-sm'],
];
$tabs = [
	'ativos' => ['icon' => '📌', 'label' => __('Ativos')],
	'aguarda' => ['icon' => '⏰', 'label' => __('Aguardando cliente')],
	'resolvidos_hoje' => ['icon' => '✓', 'label' => __('Resolvidos hoje')],
	'observados' => ['icon' => '👁', 'label' => __('Sendo observado')],
	'favoritos' => ['icon' => '⭐', 'label' => __('Favoritos')],
];
?>
<div id="pg-sd-meus" class="pgm-sd-prototype">
<?= $this->element('ServicedeskPrototype/ref/header', compact('title', 'subtitle', 'toolbar') + ['eyebrow' => __('Service Desk')]) ?>

<?php if ($kpis !== []) : ?>
	<div class="summary-grid" style="margin-bottom:14px;">
		<?php foreach ($kpis as $k) : ?>
			<?php
			$border = (string)($k['border'] ?? 'var(--teal)');
			$bg = (string)($k['bg'] ?? '');
			$valColor = (string)($k['val_color'] ?? 'var(--teal-dark)');
			$hintColor = (string)($k['hint_color'] ?? 'var(--text-muted)');
			$style = 'border-left:3px solid ' . $border . ';';
			if ($bg !== '') {
				$style .= 'background:' . $bg . ';';
			}
			?>
			<div class="summary-card" style="<?= h($style) ?>">
				<div class="lbl"><?= h((string)($k['lbl'] ?? '')) ?></div>
				<div class="val" style="color:<?= h($valColor) ?>;"><?= h((string)($k['val'] ?? '')) ?></div>
				<?php if (!empty($k['hint'])) : ?>
					<div style="font-size:11px;color:<?= h($hintColor) ?>;"><?= h((string)$k['hint']) ?></div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
<?php endif; ?>

<div class="card" style="margin-bottom:14px;padding:0;overflow:hidden;">
	<div style="display:flex;border-bottom:1px solid var(--border);overflow-x:auto;">
		<?php foreach ($tabs as $key => $tab) : ?>
			<?php
			$cnt = (int)($tabCounts[$key] ?? 0);
			$isActive = $key === $activeTab;
			$tabUrl = $H->sdpPage('meus', ['tab' => $key]);
			$tabStyle = 'padding:12px 16px;flex-shrink:0;text-decoration:none;display:block;';
			$tabStyle .= $isActive ? 'border-bottom:3px solid var(--teal);' : 'color:var(--text-muted);';
			$strongStyle = 'font-size:13px;';
			if ($isActive) {
				$strongStyle .= 'color:var(--teal-dark);';
			}
			?>
			<?= $this->Html->link(
				'<strong style="' . h($strongStyle) . '">' . h($tab['icon'] . ' ' . $tab['label']) . ' (' . (int)$cnt . ')</strong>',
				$tabUrl,
				['escape' => false, 'style' => $tabStyle]
			) ?>
		<?php endforeach; ?>
	</div>
	<?php foreach ($tabs as $key => $tab) : ?>
		<?php if ($key !== $activeTab) {
			continue;
		} ?>
		<?php $rows = (array)($tabRows[$key] ?? []); ?>
		<div style="padding:14px;display:flex;flex-direction:column;gap:8px;">
			<?php foreach ($rows as $row) : ?>
				<?php
				$id = (int)($row['id'] ?? 0);
				$url = $H->sdpTicketUrl($id);
				$border = (string)($row['border'] ?? 'var(--border-light)');
				$bgCard = (string)($row['bg'] ?? 'var(--bg-surface)');
				$icon = (string)($row['icon'] ?? '🔵');
				$titleColor = (string)($row['title_color'] ?? '');
				$badge = (string)($row['badge'] ?? '');
				$badgeColor = (string)($row['badge_color'] ?? 'var(--teal-dark)');
				$meta = (string)($row['meta'] ?? '');
				$tags = (array)($row['tags'] ?? []);
				$actionLabel = (string)($row['action_label'] ?? '');
				$actionClass = (string)($row['action_class'] ?? 'btn btn-ghost btn-sm');
				?>
				<div style="display:flex;align-items:flex-start;gap:12px;padding:14px;border:1px solid <?= h($border) ?>;background:<?= h($bgCard) ?>;border-radius:var(--radius);cursor:pointer;" onclick="window.location.href='<?= h($url) ?>'">
					<div style="font-size:24px;flex-shrink:0;"><?= $icon ?></div>
					<div style="flex:1;">
						<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:6px;margin-bottom:4px;">
							<strong style="font-size:14px;<?= $titleColor !== '' ? 'color:' . h($titleColor) . ';' : '' ?>">#<?= $id ?> · <?= h(\Cake\Utility\Text::truncate((string)($row['assunto'] ?? ''), 48, ['ellipsis' => '…'])) ?></strong>
							<?php if ($badge !== '') : ?>
								<span style="font-size:11px;color:<?= h($badgeColor) ?>;font-weight:700;"><?= h($badge) ?></span>
							<?php endif; ?>
						</div>
						<?php if ($meta !== '') : ?>
							<div style="font-size:11px;color:var(--text-muted);margin-bottom:6px;"><?= h($meta) ?></div>
						<?php endif; ?>
						<?php if ($tags !== []) : ?>
							<div style="display:flex;gap:4px;flex-wrap:wrap;">
								<?php foreach ($tags as $tag) : ?>
									<span style="padding:2px 6px;background:#fff;color:var(--text-muted);border-radius:4px;font-size:10px;"><?= h((string)$tag) ?></span>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
					<?php if ($actionLabel !== '') : ?>
						<button type="button" class="<?= h($actionClass) ?>" onclick="event.stopPropagation();window.location.href='<?= h($url) ?>'"><?= h($actionLabel) ?></button>
					<?php else : ?>
						<?= $this->Html->link(__('Abrir'), $url, ['class' => 'btn btn-ghost btn-sm', 'onclick' => 'event.stopPropagation();']) ?>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
			<?php if ($rows === []) : ?>
				<p style="margin:0;color:var(--text-muted);font-size:13px;"><?= h(__('Sem tickets nesta visão.')) ?></p>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</div>

<div class="g2">
	<div class="card">
		<div class="sec-title">📅 <?= h(__('Próximas reuniões e visitas')) ?></div>
		<div style="display:flex;flex-direction:column;gap:8px;">
			<?php foreach ($compromissos as $c) : ?>
				<div style="padding:10px;background:var(--bg-surface);border-radius:8px;border-left:3px solid <?= h((string)($c['border'] ?? 'var(--teal)')) ?>;">
					<div style="display:flex;justify-content:space-between;font-size:12px;font-weight:600;">
						<span><?= h((string)($c['when'] ?? '')) ?></span>
						<?php if (!empty($c['hint'])) : ?><span style="color:var(--teal-dark);"><?= h((string)$c['hint']) ?></span><?php endif; ?>
					</div>
					<div style="font-size:11px;color:var(--text-muted);margin-top:2px;"><?= h((string)($c['title'] ?? '')) ?></div>
				</div>
			<?php endforeach; ?>
			<?php if ($compromissos === []) : ?>
				<p style="margin:0;font-size:12px;color:var(--text-muted);"><?= h(__('Nenhum compromisso agendado.')) ?></p>
			<?php endif; ?>
		</div>
	</div>

	<div class="card">
		<div class="sec-title">🔔 <?= h(__('Notificações')) ?></div>
		<div style="display:flex;flex-direction:column;gap:8px;">
			<?php foreach ($notificacoes as $n) : ?>
				<div style="padding:10px;background:<?= h((string)($n['bg'] ?? 'var(--bg-surface)')) ?>;border-radius:8px;display:flex;gap:10px;align-items:flex-start;">
					<div style="font-size:18px;"><?= h((string)($n['icon'] ?? '💬')) ?></div>
					<div style="flex:1;">
						<div style="font-size:12px;"><?= h((string)($n['title'] ?? '')) ?></div>
						<?php if (!empty($n['sub'])) : ?>
							<div style="font-size:11px;color:var(--text-muted);"><?= h((string)$n['sub']) ?></div>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
			<?php if ($notificacoes === []) : ?>
				<p style="margin:0;font-size:12px;color:var(--text-muted);"><?= h(__('Sem notificações recentes.')) ?></p>
			<?php endif; ?>
		</div>
	</div>
</div>
</div>
