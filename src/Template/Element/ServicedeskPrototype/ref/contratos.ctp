<?php
/**
 * Contratos & SLA por cliente — mockup pg-sd-contratos.
 *
 * @var \App\View\AppView $this
 * @var array<string,mixed> $screen
 */
$contr = (array)($screen['contratos'] ?? []);
$kpis = (array)($contr['kpis'] ?? $screen['kpis'] ?? []);
$rows = (array)($contr['rows'] ?? $screen['rows'] ?? []);
$filters = (array)($contr['filters'] ?? []);
$planos = (array)($contr['planos'] ?? []);
$statusOpts = (array)($contr['status_opts'] ?? []);
$H = $this->ServicedeskPrototype;
$qVal = (string)($filters['q'] ?? '');
$planoVal = (string)($filters['plano'] ?? '');
$statusVal = (string)($filters['status'] ?? '');
?>
<div id="pg-sd-contratos" class="pgm-sd-prototype">
	<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
		<div>
			<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Service Desk')) ?></div>
			<h1 style="font-size:22px;font-weight:600;margin:0;">📄 <?= h((string)($screen['title'] ?? __('Contratos & SLA por cliente'))) ?></h1>
			<div style="font-size:12px;color:var(--text-muted);"><?= h((string)($screen['subtitle'] ?? '')) ?></div>
		</div>
		<div style="display:flex;gap:8px;flex-wrap:wrap;">
			<a class="btn btn-ghost btn-sm" href="<?= h($H->sdpPage('fila')) ?>">← <?= h(__('Voltar')) ?></a>
			<button type="button" class="btn btn-primary btn-sm" disabled>+ <?= h(__('Novo contrato')) ?></button>
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
					<?php if (!empty($k['hint'])) : ?><div style="font-size:11px;color:<?= h(strpos($valColor, 'var(') === 0 ? $valColor : (strpos($valColor, '#') === 0 && $bg !== '' ? $valColor : 'var(--text-muted)')) ?>;"><?= h((string)$k['hint']) ?></div><?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="card" style="padding:0;overflow:hidden;">
		<div style="padding:12px 14px;background:var(--bg-surface);border-bottom:1px solid var(--border-light);">
			<form method="get" action="<?= h($H->sdpPage('contratos')) ?>" style="display:flex;gap:8px;flex-wrap:wrap;margin:0;">
				<input type="text" name="q" value="<?= h($qVal) ?>" placeholder="🔍 <?= h(__('Buscar cliente, contrato...')) ?>" style="flex:1;min-width:240px;padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:13px;" />
				<select name="plano" style="padding:8px 10px;border:1px solid var(--border);border-radius:var(--radius);font-size:12px;background:#fff;" onchange="this.form.submit()">
					<?php foreach ($planos as $pk => $pl) : ?>
						<option value="<?= h((string)$pk) ?>"<?= $planoVal === (string)$pk ? ' selected' : '' ?>><?= h((string)$pl) ?></option>
					<?php endforeach; ?>
				</select>
				<select name="status" style="padding:8px 10px;border:1px solid var(--border);border-radius:var(--radius);font-size:12px;background:#fff;" onchange="this.form.submit()">
					<?php foreach ($statusOpts as $sk => $sl) : ?>
						<option value="<?= h((string)$sk) ?>"<?= $statusVal === (string)$sk ? ' selected' : '' ?>><?= h((string)$sl) ?></option>
					<?php endforeach; ?>
				</select>
				<button type="submit" class="btn btn-ghost btn-sm" style="display:none;"><?= h(__('Filtrar')) ?></button>
			</form>
		</div>
		<div style="overflow-x:auto;">
			<table style="width:100%;border-collapse:collapse;font-size:12px;">
				<thead>
					<tr style="background:var(--bg-surface);border-bottom:1px solid var(--border);">
						<th style="padding:10px 12px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Cliente')) ?></th>
						<th style="padding:10px 12px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Contrato')) ?></th>
						<th style="padding:10px 12px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Plano · SLA')) ?></th>
						<th style="padding:10px 12px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Horas · mês')) ?></th>
						<th style="padding:10px 12px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Usadas')) ?></th>
						<th style="padding:10px 12px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Valor mensal')) ?></th>
						<th style="padding:10px 12px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Vigência')) ?></th>
						<th style="padding:10px 12px;text-align:center;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Status')) ?></th>
						<th style="padding:10px 12px;text-align:center;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Ações')) ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ($rows === []) : ?>
						<tr><td colspan="9" style="padding:24px;text-align:center;color:var(--text-muted);"><?= h((string)($screen['empty'] ?? __('Nenhum contrato encontrado.'))) ?></td></tr>
					<?php else : foreach ($rows as $row) :
						$sla = (array)($row['sla_detail'] ?? []);
						$st = (array)($row['status'] ?? []);
						$exced = (float)($row['excedente_h'] ?? 0);
						$usadas = (float)($row['horas_usadas'] ?? 0);
						$horasPlan = (int)($row['horas_mes'] ?? 0);
						$pct = (int)($row['horas_pct'] ?? 0);
						$usadasColor = $exced > 0 ? '#8A4D02' : ($pct >= 70 ? 'var(--teal-dark)' : 'inherit');
						$link = (array)($row['link'] ?? []);
						$rowUrl = $link !== [] ? $this->Url->build($link) : '';
						$stKey = (string)($st['key'] ?? '');
						$vigenciaExtra = $stKey === 'vencido' ? ' <span style="color:#7A1822;">(' . h(__('vencido')) . ')</span>' : '';
					?>
						<tr style="border-bottom:1px solid var(--border-light);<?= $rowUrl !== '' ? 'cursor:pointer;' : '' ?>"<?php if ($rowUrl !== '') : ?> onclick="window.location.href='<?= h($rowUrl) ?>'"<?php endif; ?>>
							<td style="padding:10px 12px;font-weight:600;"><?= h((string)($row['cliente'] ?? '—')) ?></td>
							<td style="padding:10px 12px;"><span class="titulo-cod"><?= h((string)($row['code'] ?? '')) ?></span></td>
							<td style="padding:10px 12px;">
								<span class="badge <?= h((string)($sla['badge_class'] ?? 'b-paga')) ?>" style="font-size:10px;"><?= h((string)($sla['label'] ?? '')) ?></span>
								<?php if (!empty($sla['sla_line'])) : ?><div style="font-size:10px;color:var(--text-muted);margin-top:2px;"><?= h((string)$sla['sla_line']) ?></div><?php endif; ?>
							</td>
							<td style="padding:10px 12px;text-align:right;"><?= (int)$horasPlan ?>h</td>
							<td style="padding:10px 12px;text-align:right;">
								<strong style="color:<?= h($usadasColor) ?>;"><?= h(rtrim(rtrim(number_format($usadas, 1, ',', '.'), '0'), ',')) ?>h</strong>
								<?php if ($exced > 0) : ?>
									<div style="font-size:10px;color:#8A4D02;">+<?= h(rtrim(rtrim(number_format($exced, 1, ',', '.'), '0'), ',')) ?>h <?= h(__('excedente')) ?></div>
								<?php elseif ($horasPlan > 0) : ?>
									<div style="font-size:10px;color:var(--text-muted);"><?= (int)$pct ?>%</div>
								<?php endif; ?>
							</td>
							<td style="padding:10px 12px;text-align:right;font-weight:700;color:var(--teal-dark);"><?= h((string)($row['valor_mensal_fmt'] ?? '')) ?></td>
							<td style="padding:10px 12px;font-size:11px;">
								<?= h((string)($row['vigencia_inicio'] ?? '—')) ?> →
								<?php if ($stKey !== 'vencido') : ?><strong><?= h((string)($row['vigencia_fim'] ?? '—')) ?></strong><?php else : ?><?= h((string)($row['vigencia_fim'] ?? '—')) ?><?= $vigenciaExtra ?><?php endif; ?>
							</td>
							<td style="padding:10px 12px;text-align:center;">
								<?php if (!empty($st['badge_class'])) : ?>
									<span class="badge <?= h((string)$st['badge_class']) ?>" style="font-size:10px;"><?= h((string)($st['label'] ?? '')) ?></span>
								<?php else : ?>
									<span class="badge" style="font-size:10px;<?= h((string)($st['badge_style'] ?? '')) ?>"><?= h((string)($st['label'] ?? '')) ?></span>
								<?php endif; ?>
							</td>
							<td style="padding:10px 12px;text-align:center;" onclick="event.stopPropagation();">
								<?php
								$action = (string)($st['action'] ?? 'menu');
								$actionClass = (string)($st['action_class'] ?? 'btn-ghost');
								if ($action === 'menu') :
								?>
									<button type="button" class="btn btn-ghost btn-xs" disabled>⋮</button>
								<?php else : ?>
									<button type="button" class="btn <?= h($actionClass) ?> btn-xs" disabled><?= h($action) ?></button>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
