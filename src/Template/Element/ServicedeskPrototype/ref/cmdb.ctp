<?php
/**
 * CMDB · Configuration Items — mockup pg-sd-cmdb.
 *
 * @var \App\View\AppView $this
 * @var array<string,mixed> $screen
 */
$cmdb = (array)($screen['cmdb'] ?? []);
$kpis = (array)($cmdb['kpis'] ?? $screen['kpis'] ?? []);
$rows = (array)($cmdb['rows'] ?? $screen['rows'] ?? []);
$tabs = (array)($cmdb['tabs'] ?? []);
$filters = (array)($cmdb['filters'] ?? []);
$clientes = (array)($cmdb['clientes'] ?? []);
$statusOpts = (array)($cmdb['status_opts'] ?? []);
$critOpts = (array)($cmdb['crit_opts'] ?? []);
$dep = (array)($cmdb['dependency'] ?? []);
$pagination = (array)($cmdb['pagination'] ?? []);
$H = $this->ServicedeskPrototype;
$totalAll = (int)($cmdb['total'] ?? 0);
$totalFiltered = (int)($cmdb['filtered_total'] ?? count($rows));
$page = (int)($pagination['page'] ?? 1);
$pages = (int)($pagination['pages'] ?? 1);
$qVal = (string)($filters['q'] ?? '');
$catVal = (string)($filters['categoria'] ?? '');
$cliVal = (string)($filters['cliente_id'] ?? '');
$stVal = (string)($filters['status'] ?? '');
$critVal = (string)($filters['crit'] ?? '');

$buildUrl = static function (array $extra = []) use ($H, $filters): string {
	$params = array_filter([
		'q' => (string)($filters['q'] ?? ''),
		'categoria' => (string)($filters['categoria'] ?? ''),
		'cliente_id' => (string)($filters['cliente_id'] ?? ''),
		'status' => (string)($filters['status'] ?? ''),
		'crit' => (string)($filters['crit'] ?? ''),
		'page' => (string)($filters['page'] ?? ''),
	], static function ($v): bool {
		return $v !== '';
	});
	foreach ($extra as $k => $v) {
		if ($v === '' || $v === null) {
			unset($params[$k]);
		} else {
			$params[$k] = (string)$v;
		}
	}

	return $H->sdpPage('cmdb', $params);
};
?>
<div id="pg-sd-cmdb" class="pgm-sd-prototype">
	<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
		<div>
			<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Service Desk · ITIL')) ?></div>
			<h1 style="font-size:22px;font-weight:600;margin:0;">🗄 <?= h((string)($screen['title'] ?? __('CMDB · Configuration Items'))) ?></h1>
			<div style="font-size:12px;color:var(--text-muted);"><?= h((string)($screen['subtitle'] ?? '')) ?></div>
		</div>
		<div style="display:flex;gap:8px;flex-wrap:wrap;">
			<a class="btn btn-ghost btn-sm" href="<?= h($H->sdpPage('fila')) ?>">← <?= h(__('Service Desk')) ?></a>
			<button type="button" class="btn btn-ghost btn-sm" disabled>📊 <?= h(__('Mapa de dependências')) ?></button>
			<button type="button" class="btn btn-ghost btn-sm" disabled>🔄 <?= h(__('Sincronizar agentes')) ?></button>
			<button type="button" class="btn btn-primary btn-sm" disabled>+ <?= h(__('Novo CI')) ?></button>
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
					<?php if (!empty($k['hint'])) : ?><div style="font-size:11px;color:<?= h(strpos($valColor, 'var(') === 0 && $bg === '' ? 'var(--text-muted)' : $valColor) ?>;"><?= h((string)$k['hint']) ?></div><?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="card" style="margin-bottom:14px;padding:0;overflow:hidden;">
		<?php if ($tabs !== []) : ?>
			<div style="display:flex;border-bottom:1px solid var(--border);flex-wrap:wrap;overflow-x:auto;">
				<?php foreach ($tabs as $tab) :
					$key = (string)($tab['key'] ?? '');
					$active = !empty($tab['active']);
					$count = (int)($tab['count'] ?? 0);
					$label = (string)($tab['label'] ?? '');
					$tabUrl = $buildUrl(['categoria' => $key, 'page' => '1']);
				?>
					<a href="<?= h($tabUrl) ?>" style="padding:12px 16px;text-decoration:none;white-space:nowrap;<?= $active ? 'border-bottom:3px solid var(--teal);' : 'color:var(--text-muted);' ?>">
						<?php if ($active) : ?><strong style="font-size:13px;color:var(--teal-dark);"><?= h($label) ?> (<?= $count ?>)</strong><?php else : ?><?= h($label) ?> (<?= $count ?>)<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<div style="padding:12px 14px;border-bottom:1px solid var(--border-light);background:var(--bg-surface);">
			<form method="get" action="<?= h($H->sdpPage('cmdb')) ?>" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:0;">
				<?php if ($catVal !== '') : ?><input type="hidden" name="categoria" value="<?= h($catVal) ?>" /><?php endif; ?>
				<input type="text" name="q" value="<?= h($qVal) ?>" placeholder="🔍 <?= h(__('Buscar por nome, tag, serial, IP, MAC...')) ?>" style="flex:1;min-width:280px;padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:13px;" />
				<select name="cliente_id" style="padding:8px 10px;border:1px solid var(--border);border-radius:var(--radius);font-size:12px;background:#fff;" onchange="this.form.submit()">
					<?php foreach ($clientes as $cid => $cn) : ?>
						<option value="<?= h((string)$cid) ?>"<?= $cliVal === (string)$cid ? ' selected' : '' ?>><?= h((string)$cn) ?></option>
					<?php endforeach; ?>
				</select>
				<select name="status" style="padding:8px 10px;border:1px solid var(--border);border-radius:var(--radius);font-size:12px;background:#fff;" onchange="this.form.submit()">
					<?php foreach ($statusOpts as $sk => $sl) : ?>
						<option value="<?= h((string)$sk) ?>"<?= $stVal === (string)$sk ? ' selected' : '' ?>><?= h((string)$sl) ?></option>
					<?php endforeach; ?>
				</select>
				<select name="crit" style="padding:8px 10px;border:1px solid var(--border);border-radius:var(--radius);font-size:12px;background:#fff;" onchange="this.form.submit()">
					<?php foreach ($critOpts as $ck => $cl) : ?>
						<option value="<?= h((string)$ck) ?>"<?= $critVal === (string)$ck ? ' selected' : '' ?>><?= h((string)$cl) ?></option>
					<?php endforeach; ?>
				</select>
			</form>
		</div>

		<div style="overflow-x:auto;">
			<table style="width:100%;border-collapse:collapse;font-size:12px;">
				<thead>
					<tr style="background:var(--bg-surface);border-bottom:1px solid var(--border);">
						<th style="padding:10px 12px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Tag CI')) ?></th>
						<th style="padding:10px 12px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Nome / Tipo')) ?></th>
						<th style="padding:10px 12px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Cliente')) ?></th>
						<th style="padding:10px 12px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Localização')) ?></th>
						<th style="padding:10px 12px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('IP / Serial')) ?></th>
						<th style="padding:10px 12px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Status')) ?></th>
						<th style="padding:10px 12px;text-align:center;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Tickets')) ?></th>
						<th style="padding:10px 12px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Garantia')) ?></th>
						<th style="padding:10px 12px;text-align:center;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Ações')) ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ($rows === []) : ?>
						<tr><td colspan="9" style="padding:24px;text-align:center;color:var(--text-muted);"><?= h((string)($screen['empty'] ?? __('Nenhum ativo encontrado.'))) ?></td></tr>
					<?php else : foreach ($rows as $row) :
						$st = (array)($row['status'] ?? []);
						$tickets = (int)($row['tickets'] ?? 0);
						$rowBg = (string)($row['row_bg'] ?? '');
						$link = (array)($row['link'] ?? []);
						$rowUrl = $link !== [] ? $this->Url->build($link) : '';
						$garColor = !empty($row['garantia_warn']) ? '#8A4D02' : 'var(--teal-dark)';
						$rowStyle = 'border-bottom:1px solid var(--border-light);';
						if ($rowBg !== '') {
							$rowStyle .= 'background:' . $rowBg . ';';
						}
						if ($rowUrl !== '') {
							$rowStyle .= 'cursor:pointer;';
						}
					?>
						<tr style="<?= h($rowStyle) ?>"<?php if ($rowUrl !== '') : ?> onclick="window.location.href='<?= h($rowUrl) ?>'"<?php endif; ?>>
							<td style="padding:10px 12px;"><span class="titulo-cod"><?= h((string)($row['tag'] ?? '')) ?></span></td>
							<td style="padding:10px 12px;">
								<div style="font-weight:600;"><?= h((string)($row['nome'] ?? '')) ?></div>
								<?php if (!empty($row['tipo_sub'])) : ?><div style="font-size:11px;color:var(--text-muted);"><?= h((string)$row['tipo_sub']) ?></div><?php endif; ?>
							</td>
							<td style="padding:10px 12px;"><?= h((string)($row['cliente'] ?? '—')) ?></td>
							<td style="padding:10px 12px;font-size:11px;"><?= h((string)($row['localizacao'] ?? '—')) ?></td>
							<td style="padding:10px 12px;font-family:monospace;font-size:11px;">
								<?= h((string)($row['ip'] ?? '—')) ?>
								<?php if (!empty($row['serial'])) : ?><br><span style="color:var(--text-muted);"><?= h((string)$row['serial']) ?></span><?php endif; ?>
							</td>
							<td style="padding:10px 12px;">
								<?php if (!empty($st['badge_class'])) : ?>
									<span class="badge <?= h((string)$st['badge_class']) ?>" style="font-size:10px;"><?= h((string)($st['label'] ?? '')) ?></span>
								<?php else : ?>
									<span class="badge" style="font-size:10px;<?= h((string)($st['badge_style'] ?? '')) ?>"><?= h((string)($st['label'] ?? '')) ?></span>
								<?php endif; ?>
							</td>
							<td style="padding:10px 12px;text-align:center;">
								<?php if ($tickets > 0) : ?><strong style="color:#7A1822;"><?= $tickets ?> <?= h(__('ativos')) ?></strong><?php else : ?>0<?php endif; ?>
							</td>
							<td style="padding:10px 12px;font-size:11px;color:<?= h($garColor) ?>;"><?= h((string)($row['garantia_label'] ?? '—')) ?></td>
							<td style="padding:10px 12px;text-align:center;" onclick="event.stopPropagation();"><button type="button" class="btn btn-ghost btn-xs" disabled>⋮</button></td>
						</tr>
					<?php endforeach; endif; ?>
				</tbody>
			</table>
		</div>

		<div style="padding:10px 14px;background:var(--bg-surface);display:flex;justify-content:space-between;align-items:center;font-size:12px;border-top:1px solid var(--border-light);flex-wrap:wrap;gap:8px;">
			<span style="color:var(--text-muted);"><?= sprintf(h(__('Mostrando %d de %d CIs')), count($rows), $totalFiltered) ?><?php if ($totalAll !== $totalFiltered) : ?> · <?= sprintf(h(__('%d no inventário')), $totalAll) ?><?php endif; ?></span>
			<?php if ($pages > 1) : ?>
				<div style="display:flex;gap:4px;align-items:center;flex-wrap:wrap;">
					<?php if ($page > 1) : ?>
						<a class="btn btn-ghost btn-xs" href="<?= h($buildUrl(['page' => (string)($page - 1)])) ?>">‹</a>
					<?php else : ?>
						<button type="button" class="btn btn-ghost btn-xs" disabled>‹</button>
					<?php endif; ?>
					<?php
					$showPages = array_unique(array_filter([1, $page, $pages]));
					sort($showPages);
					$prev = 0;
					foreach ($showPages as $p) :
						if ($prev > 0 && $p - $prev > 1) :
					?><span style="color:var(--text-muted);">…</span><?php
						endif;
						$prev = $p;
					?>
						<a class="btn btn-ghost btn-xs" href="<?= h($buildUrl(['page' => (string)$p])) ?>" style="<?= $p === $page ? 'background:var(--teal-light);color:var(--teal-dark);' : '' ?>"><?= (int)$p ?></a>
					<?php endforeach; ?>
					<?php if ($page < $pages) : ?>
						<a class="btn btn-ghost btn-xs" href="<?= h($buildUrl(['page' => (string)($page + 1)])) ?>">›</a>
					<?php else : ?>
						<button type="button" class="btn btn-ghost btn-xs" disabled>›</button>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<?php if ($dep !== []) : ?>
		<div class="card">
			<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:8px;">
				<div class="sec-title" style="margin:0;border:none;">🗺 <?= h(__('Mapa de dependências')) ?> · <?= h((string)($dep['nome'] ?? '')) ?> (<?= h((string)($dep['tag'] ?? '')) ?>)</div>
				<?php if (!empty($dep['link'])) : ?>
					<a class="btn btn-ghost btn-xs" href="<?= h($this->Url->build((array)$dep['link'])) ?>">🔍 <?= h(__('Ver completo')) ?></a>
				<?php else : ?>
					<button type="button" class="btn btn-ghost btn-xs" disabled>🔍 <?= h(__('Ver completo')) ?></button>
				<?php endif; ?>
			</div>
			<div style="background:var(--bg-surface);border-radius:var(--radius);padding:24px;display:flex;flex-direction:column;align-items:center;gap:14px;">
				<div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:center;">
					<?php foreach ((array)($dep['upstream'] ?? []) as $up) : ?>
						<div style="padding:8px 12px;background:var(--blue-light);border:1px solid var(--blue);border-radius:8px;font-size:11px;"><?= h((string)$up) ?></div>
					<?php endforeach; ?>
				</div>
				<div style="font-size:18px;color:var(--text-muted);">↓ <?= h(__('depende de')) ?> ↓</div>
				<div style="padding:14px 24px;background:linear-gradient(135deg,#FEF2F2,#fff);border:2px solid var(--red);border-radius:var(--radius);text-align:center;">
					<div style="font-size:32px;margin-bottom:4px;"><?= h((string)($dep['icon'] ?? '📦')) ?></div>
					<strong style="font-size:13px;"><?= h((string)($dep['nome'] ?? '')) ?></strong>
					<div style="font-size:10px;color:var(--text-muted);"><?= h((string)($dep['sub'] ?? '')) ?> · <?= h((string)($dep['status_label'] ?? '')) ?></div>
				</div>
				<div style="font-size:18px;color:var(--text-muted);">↓ <?= h(__('suporta')) ?> ↓</div>
				<div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:center;">
					<?php foreach ((array)($dep['downstream'] ?? []) as $dn) : ?>
						<div style="padding:8px 12px;background:#FAEEDA;border:1px solid var(--amber);border-radius:8px;font-size:11px;"><?= h((string)$dn) ?></div>
					<?php endforeach; ?>
				</div>
				<div class="alert-box alert-red" style="margin:0;font-size:12px;"><?= h((string)($dep['impact'] ?? '')) ?></div>
			</div>
		</div>
	<?php endif; ?>
</div>
