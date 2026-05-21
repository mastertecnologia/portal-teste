<?php
/**
 * @var \App\View\AppView $this
 * @var array<string,mixed> $screen
 */
$fat = (array)($screen['fat'] ?? []);
$kpis = (array)($fat['kpis'] ?? $screen['kpis'] ?? []);
$rows = (array)($fat['rows'] ?? $screen['rows'] ?? []);
$H = $this->ServicedeskPrototype;
?>
<div id="pg-sd-fat" class="pgm-sd-prototype">
	<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
		<div>
			<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Service Desk')) ?></div>
			<h1 style="font-size:22px;font-weight:600;margin:0;">💰 <?= h((string)($screen['title'] ?? __('Faturamento de tickets'))) ?></h1>
			<div style="font-size:12px;color:var(--text-muted);"><?= h((string)($screen['subtitle'] ?? '')) ?></div>
		</div>
		<div style="display:flex;gap:8px;flex-wrap:wrap;">
			<a class="btn btn-ghost btn-sm" href="<?= h($H->sdpPage('fila')) ?>">← <?= h(__('Voltar')) ?></a>
			<button type="button" class="btn btn-primary btn-sm" disabled>📤 <?= h(__('Gerar faturas em lote')) ?></button>
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
					<div class="val" style="color:<?= h($valColor) ?>;<?= !empty($k['val_size']) ? 'font-size:' . h((string)$k['val_size']) . ';' : '' ?>"><?= h((string)($k['val'] ?? '')) ?></div>
					<?php if (!empty($k['hint'])) : ?><div style="font-size:11px;color:var(--text-muted);"><?= h((string)$k['hint']) ?></div><?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="card" style="padding:0;overflow:hidden;">
		<div style="padding:14px 16px;border-bottom:1px solid var(--border-light);background:var(--bg-surface);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
			<strong style="font-size:13px;"><?= h(__('Tickets fechados aguardando faturamento')) ?></strong>
			<div style="display:flex;gap:6px;">
				<select disabled style="padding:6px 10px;border:1px solid var(--border);border-radius:6px;font-size:12px;background:#fff;"><option><?= h(__('Período: mês atual')) ?></option></select>
				<select disabled style="padding:6px 10px;border:1px solid var(--border);border-radius:6px;font-size:12px;background:#fff;"><option><?= h(__('Todos clientes')) ?></option></select>
			</div>
		</div>
		<div style="overflow-x:auto;">
			<table style="width:100%;border-collapse:collapse;font-size:12px;">
				<thead>
					<tr style="background:var(--bg-surface);border-bottom:1px solid var(--border);">
						<th style="padding:10px;text-align:center;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><input type="checkbox" disabled checked /></th>
						<th style="padding:10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h(__('Ticket')) ?></th>
						<th style="padding:10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h(__('Cliente')) ?></th>
						<th style="padding:10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h(__('Contrato')) ?></th>
						<th style="padding:10px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h(__('Horas')) ?></th>
						<th style="padding:10px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h(__('Cobertas')) ?></th>
						<th style="padding:10px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h(__('A faturar')) ?></th>
						<th style="padding:10px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h(__('Valor')) ?></th>
						<th style="padding:10px;text-align:center;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h(__('Status')) ?></th>
						<th style="padding:10px;text-align:center;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h(__('Ações')) ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($rows as $r) : ?>
						<?php
						$id = (int)($r['id'] ?? 0);
						$sc = (string)($r['status_class'] ?? 'coberto');
						$badgeStyle = $sc === 'avulso' ? 'background:#F8D8DA;color:#7A1822;' : ($sc === 'excedente' ? 'background:#FAEEDA;color:#8A4D02;' : '');
						$badgeClass = $badgeStyle === '' ? 'b-aprov' : '';
						?>
						<tr style="border-bottom:1px solid var(--border-light);">
							<td style="padding:10px;text-align:center;"><input type="checkbox" disabled checked /></td>
							<td style="padding:10px;">
								<?= $this->Html->link('#' . $id, $H->sdpTicketUrl($id), ['style' => 'color:var(--teal);font-family:monospace;font-weight:700;text-decoration:none;']) ?>
								<div style="font-size:11px;color:var(--text-muted);"><?= h(\Cake\Utility\Text::truncate((string)($r['assunto'] ?? ''), 36, ['ellipsis' => '…'])) ?></div>
							</td>
							<td style="padding:10px;font-weight:600;"><?= h((string)($r['cliente'] ?? '')) ?></td>
							<td style="padding:10px;font-size:11px;"><span class="badge <?= (string)($r['contrato_badge'] ?? '') === 'premium' ? 'b-paga' : 'b-pendente' ?>" style="font-size:10px;"><?= h((string)($r['contrato'] ?? '')) ?></span></td>
							<td style="padding:10px;text-align:right;"><?= h((string)($r['horas_fmt'] ?? '')) ?></td>
							<td style="padding:10px;text-align:right;color:var(--teal-dark);"><?= h((string)($r['cobertas_fmt'] ?? '')) ?></td>
							<td style="padding:10px;text-align:right;color:<?= $sc !== 'coberto' ? '#8A4D02' : 'var(--text-muted)' ?>;font-weight:<?= $sc !== 'coberto' ? '600' : '400' ?>;"><?= h((string)($r['a_faturar_fmt'] ?? '')) ?></td>
							<td style="padding:10px;text-align:right;font-weight:600;color:<?= $sc === 'avulso' ? '#7A1822' : 'var(--teal-dark)' ?>;"><?= h((string)($r['valor_fmt'] ?? '')) ?></td>
							<td style="padding:10px;text-align:center;"><span class="badge <?= h($badgeClass) ?>" style="font-size:10px;<?= h($badgeStyle) ?>"><?= h((string)($r['status'] ?? '')) ?></span></td>
							<td style="padding:10px;text-align:center;">
								<?= $this->Html->link('👁', $H->sdpPage('detalhe-fatura', ['ticket_id' => $id]), ['class' => 'btn btn-ghost btn-xs', 'title' => __('Ver fatura')]) ?>
							</td>
						</tr>
					<?php endforeach; ?>
					<?php if ($rows === []) : ?>
						<tr><td colspan="10" style="padding:16px;color:var(--text-muted);"><?= h((string)($screen['empty'] ?? __('Sem tickets.'))) ?></td></tr>
					<?php endif; ?>
				</tbody>
				<?php if ($rows !== []) : ?>
					<tfoot>
						<tr style="background:var(--teal-light);">
							<td colspan="6" style="padding:12px;text-align:right;font-weight:700;color:var(--teal-dark);"><?= h(__('TOTAL A FATURAR')) ?>:</td>
							<td style="padding:12px;text-align:right;font-weight:700;color:var(--teal-dark);"><?= h((string)($fat['total_horas_fmt'] ?? '')) ?></td>
							<td style="padding:12px;text-align:right;font-weight:700;font-size:14px;color:var(--teal-dark);"><?= h((string)($fat['total_valor_fmt'] ?? '')) ?></td>
							<td colspan="2"></td>
						</tr>
					</tfoot>
				<?php endif; ?>
			</table>
		</div>
		<?php if ($rows !== []) : ?>
			<div style="padding:14px 16px;background:var(--bg-surface);display:flex;justify-content:space-between;align-items:center;border-top:1px solid var(--border-light);flex-wrap:wrap;gap:8px;">
				<div style="font-size:12px;color:var(--text-muted);"><?= h(sprintf(__('%d tickets · consolidar por cliente/contrato'), count($rows))) ?></div>
				<button type="button" class="btn btn-primary btn-sm" disabled>📤 <?= h(__('Gerar faturas → financeiro')) ?></button>
			</div>
		<?php endif; ?>
	</div>
</div>
