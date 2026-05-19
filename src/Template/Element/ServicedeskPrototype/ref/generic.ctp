<?php
/**
 * Layout genérico alinhado ao mockup (KPIs + tabela/lista).
 *
 * @var \App\View\AppView $this
 * @var array<string,mixed> $screen
 * @var array<string,mixed> $charts
 */
$title = (string)($screen['title'] ?? '');
$subtitle = (string)($screen['subtitle'] ?? '');
$kpis = (array)($screen['kpis'] ?? []);
$rows = (array)($screen['rows'] ?? []);
$items = (array)($screen['items'] ?? []);
$mode = (string)($screen['mode'] ?? 'tickets');
$empty = (string)($screen['empty'] ?? '');
$links = (array)($screen['links'] ?? []);
$headers = (array)($screen['item_headers'] ?? []);
$layout = (string)($screen['layout'] ?? '');
$charts = $charts ?? [];
$H = $this->ServicedeskPrototype;
$toolbar = [];
foreach ($links as $lnk) {
	if (!empty($lnk['label']) && !empty($lnk['url'])) {
		$toolbar[] = ['label' => (string)$lnk['label'], 'url' => $lnk['url'], 'class' => 'btn btn-ghost btn-sm'];
	}
}
?>
<?= $this->element('ServicedeskPrototype/ref/header', compact('title', 'subtitle', 'toolbar') + ['eyebrow' => __('Service Desk')]) ?>

<?php if ($kpis !== []) : ?>
	<div class="summary-grid" style="margin-bottom:14px;">
		<?php foreach ($kpis as $k) : ?>
			<?php
			$alert = !empty($k['alert']);
			$border = (string)($k['border'] ?? ($alert ? 'var(--red)' : 'var(--teal)'));
			$bg = (string)($k['bg'] ?? ($alert ? '#F8D8DA' : ''));
			$valColor = (string)($k['val_color'] ?? ($alert ? '#7A1822' : 'var(--teal-dark)'));
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

<?php if ($layout === 'cmdb') : ?>
	<?= $this->element('ServicedeskPrototype/screen_cmdb', ['screen' => $screen]) ?>
<?php elseif ($layout === 'relatorios') : ?>
	<?= $this->element('ServicedeskPrototype/screen_relatorios', ['screen' => $screen, 'charts' => $charts]) ?>
<?php elseif ($mode === 'tickets' && $rows !== []) : ?>
	<div class="card" style="padding:0;overflow:hidden;">
		<div style="overflow-x:auto;">
			<table style="width:100%;border-collapse:collapse;font-size:12px;">
				<thead>
					<tr style="border-bottom:1px solid var(--border);">
						<?php foreach ([__('Ticket'), __('Cliente'), __('Assunto'), __('Situação'), __('Prioridade'), __('Técnico'), __('Ações')] as $th) : ?>
							<th style="padding:12px;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h($th) ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($rows as $row) : ?>
						<tr style="border-bottom:1px solid var(--border-light);">
							<td style="padding:12px;"><?= $this->Html->link('#' . (int)($row['id'] ?? 0), $H->sdpTicketUrl((int)($row['id'] ?? 0)), ['style' => 'color:var(--teal);font-weight:700;font-family:monospace;']) ?></td>
							<td style="padding:12px;"><?= h((string)($row['cliente'] ?? '')) ?></td>
							<td style="padding:12px;"><?= h(\Cake\Utility\Text::truncate((string)($row['assunto'] ?? ''), 80, ['ellipsis' => '…'])) ?></td>
							<td style="padding:12px;"><?= h((string)($row['situacao_label'] ?? '')) ?></td>
							<td style="padding:12px;"><?= h((string)($row['prioridade'] ?? '—')) ?></td>
							<td style="padding:12px;"><?= h((string)($row['tecnico'] ?? '')) ?></td>
							<td style="padding:12px;"><?= $this->Html->link(__('Abrir'), $H->sdpTicketUrl((int)($row['id'] ?? 0)), ['class' => 'btn btn-ghost btn-xs']) ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
<?php elseif ($mode === 'items' && $items !== []) : ?>
	<div class="card" style="padding:0;overflow:hidden;">
		<table style="width:100%;border-collapse:collapse;font-size:12px;">
			<thead><tr><?php foreach ($headers as $hcol) : ?><th style="padding:12px;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h((string)$hcol) ?></th><?php endforeach; ?><th></th></tr></thead>
			<tbody>
				<?php foreach ($items as $it) : ?>
					<tr style="border-bottom:1px solid var(--border-light);">
						<td style="padding:12px;"><?= h((string)($it['col1'] ?? '')) ?></td>
						<td style="padding:12px;"><?= h((string)($it['col2'] ?? '')) ?></td>
						<td style="padding:12px;"><?= h((string)($it['col3'] ?? '')) ?></td>
						<td style="padding:12px;"><?= h((string)($it['col4'] ?? '')) ?></td>
						<td style="padding:12px;"><?php if (!empty($it['link'])) : ?><?= $this->Html->link(__('Ver'), $it['link'], ['class' => 'btn btn-ghost btn-xs']) ?><?php endif; ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
<?php else : ?>
	<div class="card"><p style="margin:0;color:var(--text-muted);"><?= h($empty !== '' ? $empty : __('Sem registos.')) ?></p></div>
<?php endif; ?>
