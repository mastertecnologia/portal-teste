<?php
/**
 * @var \App\View\AppView $this
 * @var array<string,mixed> $screen
 */
$integ = (array)($screen['integracoes'] ?? []);
$kpis = (array)($integ['kpis'] ?? $screen['kpis'] ?? []);
$categories = (array)($integ['categories'] ?? []);
$api = (array)($integ['api'] ?? []);
$H = $this->ServicedeskPrototype;
?>
<div id="pg-sd-integracoes" class="pgm-sd-prototype">
	<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
		<div>
			<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Service Desk · Configurações')) ?></div>
			<h1 style="font-size:22px;font-weight:600;margin:0;">🔌 <?= h((string)($screen['title'] ?? __('Integrações'))) ?></h1>
			<div style="font-size:12px;color:var(--text-muted);"><?= h((string)($screen['subtitle'] ?? '')) ?></div>
		</div>
		<div style="display:flex;gap:8px;flex-wrap:wrap;">
			<a class="btn btn-ghost btn-sm" href="<?= h($H->sdpPage('config')) ?>">← <?= h(__('Configurações')) ?></a>
			<button type="button" class="btn btn-ghost btn-sm" disabled>📚 <?= h(__('Documentação API')) ?></button>
			<button type="button" class="btn btn-primary btn-sm" disabled>+ <?= h(__('Nova integração')) ?></button>
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
					<?php if (!empty($k['hint'])) : ?><div style="font-size:11px;color:<?= h($valColor) ?>;"><?= h((string)$k['hint']) ?></div><?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php foreach ($categories as $cat) : ?>
		<div class="card" style="margin-bottom:14px;">
			<div class="sec-title"><?= h((string)($cat['title'] ?? '')) ?></div>
			<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px;">
				<?php foreach ((array)($cat['items'] ?? []) as $item) : ?>
					<?php
					$st = (string)($item['status'] ?? '');
					$warn = !empty($item['warn_card']);
					$muted = !empty($item['muted']);
					$badgeClass = $st === 'available' ? 'b-pendente' : ($st === 'warning' ? '' : 'b-paga');
					$badgeStyle = $st === 'warning' ? 'background:#FAEEDA;color:#8A4D02;' : '';
					$cardStyle = $warn ? 'border:1px solid #FAEEDA;background:#FFFBF0;' : 'border:1px solid var(--border);';
					if ($muted) {
						$cardStyle .= 'opacity:.7;';
					}
					$descColor = $warn ? '#8A4D02' : 'var(--text-muted)';
					$action = (string)($item['action'] ?? 'configure');
					?>
					<div style="padding:14px;<?= h($cardStyle) ?>border-radius:var(--radius);">
						<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;">
							<div style="display:flex;align-items:center;gap:10px;">
								<div style="width:40px;height:40px;background:<?= h((string)($item['icon_bg'] ?? 'var(--teal)')) ?>;color:#fff;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:20px;"><?= h((string)($item['icon'] ?? '🔌')) ?></div>
								<div>
									<strong><?= h((string)($item['nome'] ?? '')) ?></strong>
									<?php if (!empty($item['sub'])) : ?><div style="font-size:11px;color:var(--text-muted);"><?= h((string)$item['sub']) ?></div><?php endif; ?>
								</div>
							</div>
							<span class="badge <?= h($badgeClass) ?>" style="font-size:10px;<?= h($badgeStyle) ?>"><?= h((string)($item['status_label'] ?? '')) ?></span>
						</div>
						<div style="font-size:11px;color:<?= h($descColor) ?>;margin-bottom:8px;"><?= h((string)($item['desc'] ?? '')) ?></div>
						<?php if ($action === 'connect') : ?>
							<div style="display:flex;gap:6px;justify-content:flex-end;"><button type="button" class="btn btn-primary btn-xs" disabled>+ <?= h(__('Conectar')) ?></button></div>
						<?php elseif ($action === 'configure' || $st === 'connected' || $st === 'native') : ?>
							<div style="display:flex;gap:6px;justify-content:flex-end;"><button type="button" class="btn btn-ghost btn-xs" disabled>⚙ <?= h(__('Configurar')) ?></button></div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endforeach; ?>

	<div class="card">
		<div class="sec-title">🔑 <?= h(__('API & Webhooks')) ?></div>
		<div class="g2" style="margin-bottom:10px;">
			<div class="field">
				<label><?= h(__('Endpoint base')) ?></label>
				<input type="text" readonly value="<?= h((string)($api['endpoint'] ?? '')) ?>" style="font-family:monospace;font-size:11px;" />
			</div>
			<div class="field">
				<label><?= h(__('API Key')) ?></label>
				<input type="password" readonly value="<?= h((string)($api['key_masked'] ?? '')) ?>" style="font-family:monospace;font-size:11px;" />
			</div>
		</div>
		<div style="display:flex;gap:6px;justify-content:flex-end;">
			<button type="button" class="btn btn-ghost btn-sm" disabled>📋 <?= h(__('Copiar')) ?></button>
			<button type="button" class="btn btn-ghost btn-sm" disabled>🔄 <?= h(__('Rotacionar')) ?></button>
			<button type="button" class="btn btn-primary btn-sm" disabled>📚 <?= h(__('Ver docs')) ?></button>
		</div>
	</div>
</div>
