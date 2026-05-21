<?php
/**
 * @var \App\View\AppView $this
 * @var array<string,mixed> $screen
 */
$tpl = (array)($screen['templates'] ?? []);
$catalog = (array)($tpl['catalog'] ?? []);
$editor = (array)($tpl['editor'] ?? []);
$preview = (array)($editor['preview'] ?? []);
$variables = (array)($editor['variables'] ?? []);
$kpis = (array)($screen['kpis'] ?? []);
$stats = (array)($tpl['stats'] ?? []);
$H = $this->ServicedeskPrototype;
$uTpl = $H->sdpPage('templates');
$formCount = (int)($stats['formularios'] ?? 8);
?>
<div id="pg-sd-templates" class="pgm-sd-prototype">
	<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
		<div>
			<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Service Desk · Configurações')) ?></div>
			<h1 style="font-size:22px;font-weight:600;margin:0;">📝 <?= h((string)($screen['title'] ?? __('Templates & Formulários'))) ?></h1>
			<div style="font-size:12px;color:var(--text-muted);"><?= h((string)($screen['subtitle'] ?? '')) ?></div>
		</div>
		<div style="display:flex;gap:8px;flex-wrap:wrap;">
			<a class="btn btn-ghost btn-sm" href="<?= h($H->sdpPage('config')) ?>">← <?= h(__('Configurações')) ?></a>
			<button type="button" class="btn btn-primary btn-sm" disabled title="<?= h(__('Protótipo somente leitura')) ?>">+ <?= h(__('Novo template')) ?></button>
		</div>
	</div>

	<?php if ($kpis !== []) : ?>
		<div class="summary-grid" style="margin-bottom:14px;">
			<?php foreach ($kpis as $k) : ?>
				<?php
				$border = (string)($k['border'] ?? 'var(--teal)');
				$valColor = (string)($k['val_color'] ?? 'var(--teal-dark)');
				?>
				<div class="summary-card" style="border-left:3px solid <?= h($border) ?>;">
					<div class="lbl"><?= h((string)($k['lbl'] ?? '')) ?></div>
					<div class="val" style="color:<?= h($valColor) ?>;<?= !empty($k['val_size']) ? 'font-size:' . h((string)$k['val_size']) . ';' : '' ?>"><?= h((string)($k['val'] ?? '')) ?></div>
					<?php if (!empty($k['hint'])) : ?><div style="font-size:11px;color:var(--text-muted);"><?= h((string)$k['hint']) ?></div><?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="card" style="padding:0;overflow:hidden;">
		<div style="display:flex;border-bottom:1px solid var(--border);overflow-x:auto;">
			<div style="padding:12px 16px;border-bottom:3px solid var(--teal);flex-shrink:0;"><strong style="font-size:13px;color:var(--teal-dark);">📝 <?= h(__('Templates de resposta')) ?> (<?= count($catalog) ?>)</strong></div>
			<div style="padding:12px 16px;color:var(--text-muted);flex-shrink:0;">📋 <?= h(__('Formulários')) ?> (<?= $formCount ?>)</div>
			<div style="padding:12px 16px;color:var(--text-muted);flex-shrink:0;">🔠 <?= h(__('Variáveis disponíveis')) ?></div>
		</div>

		<div style="padding:14px;">
			<div style="display:grid;grid-template-columns:1fr 1.5fr;gap:14px;">
				<div>
					<input type="text" disabled placeholder="🔍 <?= h(__('Buscar template...')) ?>" style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:13px;margin-bottom:10px;" />
					<div style="display:flex;flex-direction:column;gap:6px;">
						<?php foreach ($catalog as $item) : ?>
							<?php
							$active = !empty($item['active']);
							$cmd = (string)($item['cmd'] ?? '');
							$itemUrl = $H->sdpPage('templates', ['tpl' => $cmd]);
							$style = $active
								? 'padding:10px;background:var(--teal-light);border:2px solid var(--teal);border-radius:var(--radius);display:block;text-decoration:none;color:inherit;'
								: 'padding:10px;border:1px solid var(--border);border-radius:var(--radius);display:block;text-decoration:none;color:inherit;';
							?>
							<?= $this->Html->link(
								'<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:3px;">'
								. '<strong style="font-size:13px;' . ($active ? 'color:var(--teal-dark);' : '') . '">' . h((string)($item['nome'] ?? '')) . '</strong>'
								. '<span style="font-size:10px;color:var(--text-muted);font-family:monospace;">' . h($cmd) . '</span></div>'
								. '<div style="font-size:11px;color:var(--text-muted);">"' . h(\Cake\Utility\Text::truncate((string)($item['preview'] ?? ''), 48, ['ellipsis' => '…'])) . '"</div>'
								. '<div style="font-size:10px;color:' . ($active ? 'var(--teal-dark)' : 'var(--text-muted)') . ';margin-top:4px;">'
								. ($active ? '⭐ ' : '') . (int)($item['usos'] ?? 0) . ' ' . h(__('usos')) . '</div>',
								$itemUrl,
								['escape' => false, 'style' => $style]
							) ?>
						<?php endforeach; ?>
					</div>
				</div>

				<div>
					<div class="g2" style="margin-bottom:10px;">
						<div class="field"><label><?= h(__('Nome do template')) ?></label><input type="text" disabled value="<?= h((string)($editor['nome'] ?? '')) ?>" /></div>
						<div class="field"><label><?= h(__('Comando rápido')) ?></label><input type="text" disabled value="<?= h((string)($editor['cmd'] ?? '')) ?>" style="font-family:monospace;" /></div>
					</div>
					<div class="g2" style="margin-bottom:10px;">
						<div class="field"><label><?= h(__('Categoria')) ?></label><select disabled><option><?= h((string)($editor['categoria'] ?? __('Saudações'))) ?></option></select></div>
						<div class="field"><label><?= h(__('Visibilidade')) ?></label><select disabled><option><?= h((string)($editor['visibilidade'] ?? __('Todos os técnicos'))) ?></option></select></div>
					</div>
					<div class="field" style="margin-bottom:10px;">
						<label><?= h(__('Conteúdo · use variáveis para personalizar automaticamente')) ?></label>
						<textarea rows="10" disabled style="font-family:Consolas,Monaco,monospace;font-size:12px;line-height:1.6;width:100%;"><?= h((string)($editor['body'] ?? '')) ?></textarea>
					</div>

					<div style="padding:14px;background:var(--bg-surface);border-radius:var(--radius);margin-bottom:10px;">
						<div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;font-weight:600;margin-bottom:8px;">👁 <?= h(__('Preview · ticket')) ?> #<?= (int)($preview['ticket_id'] ?? 0) ?></div>
						<div style="font-size:12px;line-height:1.6;background:#fff;padding:12px;border-radius:8px;"><?= (string)($preview['html'] ?? '') ?></div>
					</div>

					<div class="g2" style="margin-bottom:10px;">
						<div class="field">
							<label><?= h(__('Anexar arquivos automaticamente')) ?></label>
							<select disabled><option><?= h(__('Nenhum')) ?></option></select>
						</div>
						<div class="field">
							<label><?= h(__('Idioma')) ?></label>
							<select disabled><option><?= h(__('Português (BR)')) ?></option></select>
						</div>
					</div>

					<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
						<div style="font-size:11px;color:var(--text-muted);">
							<strong><?= h(__('Variáveis usadas')) ?>:</strong>
							<?= h(implode(' ', $variables)) ?>
						</div>
						<div style="display:flex;gap:6px;">
							<button type="button" class="btn btn-red btn-sm" disabled>🗑 <?= h(__('Excluir')) ?></button>
							<button type="button" class="btn btn-ghost btn-sm" disabled>📋 <?= h(__('Duplicar')) ?></button>
							<button type="button" class="btn btn-primary btn-sm" disabled>💾 <?= h(__('Salvar template')) ?></button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
