<?php
/**
 * Gestão de Problemas ITIL — mockup pg-sd-problemas.
 *
 * @var \App\View\AppView $this
 * @var array<string,mixed> $screen
 */
$prob = (array)($screen['problemas'] ?? []);
$kpis = (array)($prob['kpis'] ?? $screen['kpis'] ?? []);
$rows = (array)($prob['rows'] ?? $screen['rows'] ?? []);
$H = $this->ServicedeskPrototype;

$prioStyles = [
	'critico' => ['bg' => '#F8D8DA', 'color' => '#7A1822', 'label' => '🔴 ' . __('CRÍTICO')],
	'alto' => ['bg' => '#FAEEDA', 'color' => '#8A4D02', 'label' => '🟡 ' . __('ALTO')],
	'medio' => ['bg' => 'var(--blue-light)', 'color' => '#0C447C', 'label' => '🟢 ' . __('MÉDIO')],
];
$statusStyles = [
	'investigacao' => ['bg' => '#FAEEDA', 'color' => '#8A4D02', 'label' => '🔍 ' . __('Investigação')],
	'correcao' => ['bg' => 'var(--blue-light)', 'color' => '#0C447C', 'label' => '⚙ ' . __('Em correção')],
	'workaround' => ['bg' => '#FAEEDA', 'color' => '#8A4D02', 'label' => '🔍 ' . __('Investigação')],
	'resolvido' => ['bg' => '', 'color' => '', 'label' => '✓ ' . __('Resolvido'), 'class' => 'b-paga'],
];
?>
<div id="pg-sd-problemas" class="pgm-sd-prototype">
	<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
		<div>
			<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Service Desk · ITIL')) ?></div>
			<h1 style="font-size:22px;font-weight:600;margin:0;">🔍 <?= h((string)($screen['title'] ?? __('Gestão de Problemas'))) ?></h1>
			<div style="font-size:12px;color:var(--text-muted);"><?= h((string)($screen['subtitle'] ?? '')) ?></div>
		</div>
		<div style="display:flex;gap:8px;flex-wrap:wrap;">
			<a class="btn btn-ghost btn-sm" href="<?= h($H->sdpPage('fila')) ?>">← <?= h(__('Voltar')) ?></a>
			<button type="button" class="btn btn-primary btn-sm" disabled>+ <?= h(__('Novo problema')) ?></button>
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
					<?php if (!empty($k['hint'])) : ?><div style="font-size:11px;color:<?= h($valColor !== 'var(--teal-dark)' ? $valColor : 'var(--text-muted)') ?>;"><?= h((string)$k['hint']) ?></div><?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="card" style="padding:0;overflow:hidden;">
		<?php if ($rows === []) : ?>
			<div style="padding:14px;color:var(--text-muted);"><?= h((string)($screen['empty'] ?? __('Nenhum problema registrado.'))) ?></div>
		<?php else : ?>
			<div style="overflow-x:auto;">
				<table style="width:100%;border-collapse:collapse;font-size:12px;">
					<thead>
						<tr style="background:var(--bg-surface);border-bottom:1px solid var(--border);">
							<th style="padding:10px 12px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Problema')) ?></th>
							<th style="padding:10px 12px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Título')) ?></th>
							<th style="padding:10px 12px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Prioridade')) ?></th>
							<th style="padding:10px 12px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Status')) ?></th>
							<th style="padding:10px 12px;text-align:center;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Incidentes vinculados')) ?></th>
							<th style="padding:10px 12px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Workaround')) ?></th>
							<th style="padding:10px 12px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Owner')) ?></th>
							<th style="padding:10px 12px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Aberto há')) ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($rows as $row) :
							$prio = (string)($row['priority'] ?? 'medio');
							$st = (string)($row['status'] ?? 'investigacao');
							$pStyle = $prioStyles[$prio] ?? $prioStyles['medio'];
							$sStyle = $statusStyles[$st] ?? $statusStyles['investigacao'];
							$rowBg = (string)($row['row_bg'] ?? '');
							$incColor = $prio === 'critico' ? '#7A1822' : ($prio === 'alto' ? '#8A4D02' : 'inherit');
							$daysColor = $prio === 'critico' ? '#7A1822' : 'inherit';
							$ticketId = (int)($row['ticket_id'] ?? 0);
							$rowStyle = 'border-bottom:1px solid var(--border-light);cursor:pointer;';
							if ($rowBg !== '') {
								$rowStyle .= 'background:' . $rowBg . ';';
							}
						?>
							<tr style="<?= h($rowStyle) ?>"<?php if ($ticketId > 0) : ?> onclick="window.location.href='<?= h($H->sdpTicketUrl($ticketId)) ?>'"<?php endif; ?>>
								<td style="padding:10px 12px;"><span class="titulo-cod"><?= h((string)($row['code'] ?? '')) ?></span></td>
								<td style="padding:10px 12px;">
									<div style="font-weight:600;"><?= h((string)($row['title'] ?? '')) ?></div>
									<?php if (!empty($row['subtitle'])) : ?><div style="font-size:11px;color:var(--text-muted);"><?= h((string)$row['subtitle']) ?></div><?php endif; ?>
								</td>
								<td style="padding:10px 12px;"><span class="badge" style="background:<?= h($pStyle['bg']) ?>;color:<?= h($pStyle['color']) ?>;font-size:10px;"><?= h($pStyle['label']) ?></span></td>
								<td style="padding:10px 12px;">
									<?php if (!empty($sStyle['class'])) : ?>
										<span class="badge <?= h($sStyle['class']) ?>" style="font-size:10px;"><?= h($sStyle['label']) ?></span>
									<?php else : ?>
										<span class="badge" style="background:<?= h($sStyle['bg']) ?>;color:<?= h($sStyle['color']) ?>;font-size:10px;"><?= h($sStyle['label']) ?></span>
									<?php endif; ?>
								</td>
								<td style="padding:10px 12px;text-align:center;"><strong style="color:<?= h($incColor) ?>;"><?= (int)($row['incidents'] ?? 0) ?></strong></td>
								<td style="padding:10px 12px;font-size:11px;"><?= h((string)($row['workaround'] ?? '—')) ?></td>
								<td style="padding:10px 12px;"><?= h((string)($row['owner'] ?? '—')) ?></td>
								<td style="padding:10px 12px;font-size:11px;color:<?= h($daysColor) ?>;"><?= (int)($row['days_open'] ?? 0) ?> <?= h(__('dias')) ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</div>
</div>
