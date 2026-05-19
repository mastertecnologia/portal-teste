<?php
/**
 * @var \App\View\AppView $this
 * @var array<string,mixed> $screen
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
$queues = (array)($screen['queues'] ?? []);
$queueId = $screen['queue_id'] ?? null;
$layout = (string)($screen['layout'] ?? '');
$charts = (array)($screen['charts'] ?? []);
$refPage = (string)($screen['ref_page'] ?? '');
?>
<?php if ($refPage !== '') : ?>
<div class="pgm-sd-prototype">
	<?php
	$refElement = 'ServicedeskPrototype/ref/' . $refPage;
	if (!$this->elementExists($refElement)) {
		$refElement = 'ServicedeskPrototype/ref/generic';
	}
	echo $this->element($refElement, ['screen' => $screen, 'charts' => $charts]);
	?>
</div>
<?php else : ?>
<div class="row">
	<div class="col-12 pgm-sd-prototype">
		<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
			<div>
				<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Service Desk')) ?></div>
				<h1 style="font-size:20px;font-weight:600;margin:0;"><?= h($title) ?></h1>
				<?php if ($subtitle !== '') : ?>
					<div style="font-size:12px;color:var(--text-muted);"><?= h($subtitle) ?></div>
				<?php endif; ?>
			</div>
			<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
				<?php if ($queues !== []) : ?>
					<form method="get" style="margin:0;">
						<select name="queue_id" class="form-control input-sm" onchange="this.form.submit()" style="font-size:12px;">
							<option value=""><?= h(__('Todas as filas')) ?></option>
							<?php foreach ($queues as $qid => $qname) : ?>
								<option value="<?= (int)$qid ?>" <?= $queueId !== null && (int)$queueId === (int)$qid ? 'selected' : '' ?>><?= h((string)$qname) ?></option>
							<?php endforeach; ?>
						</select>
					</form>
				<?php endif; ?>
				<?php foreach ($links as $lnk) : ?>
					<?php if (!empty($lnk['label']) && !empty($lnk['url'])) : ?>
						<?= $this->Html->link((string)$lnk['label'], $lnk['url'], ['class' => 'btn btn-default btn-sm']) ?>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		</div>

		<?php if ($kpis !== []) : ?>
			<div class="summary-grid" style="margin-bottom:14px;">
				<?php foreach ($kpis as $k) : ?>
					<?php
					$alert = !empty($k['alert']);
					$style = $alert ? 'background:#F8D8DA;border-left:3px solid var(--red);' : 'border-left:3px solid var(--teal);';
					?>
					<div class="summary-card" style="<?= h($style) ?>">
						<div class="lbl"><?= h((string)($k['lbl'] ?? '')) ?></div>
						<div class="val" style="color:var(--teal-dark);"><?= h((string)($k['val'] ?? '')) ?></div>
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
			<div class="sdp-card" style="padding:0;overflow-x:auto;">
				<table class="table table-striped table-condensed" style="margin:0;font-size:12px;">
					<thead>
						<tr>
							<th><?= h(__('ID')) ?></th>
							<th><?= h(__('Cliente')) ?></th>
							<th><?= h(__('Assunto')) ?></th>
							<th><?= h(__('Situação')) ?></th>
							<th><?= h(__('Prioridade')) ?></th>
							<th><?= h(__('Técnico')) ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($rows as $row) : ?>
							<tr>
								<td><strong>#<?= (int)($row['id'] ?? 0) ?></strong></td>
								<td><?= h((string)($row['cliente'] ?? '')) ?></td>
								<td><?= h(\Cake\Utility\Text::truncate((string)($row['assunto'] ?? ''), 80, ['ellipsis' => '…'])) ?></td>
								<td><?= h((string)($row['situacao_label'] ?? '')) ?></td>
								<td><?= h((string)($row['prioridade'] ?? '—')) ?></td>
								<td><?= h((string)($row['tecnico'] ?? '')) ?></td>
								<td><?= $this->Html->link(__('Abrir'), ['controller' => 'ServicedeskPrototype', 'action' => 'ticket', (int)($row['id'] ?? 0)], ['class' => 'btn btn-xs btn-primary']) ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php elseif ($mode === 'items' && $items !== []) : ?>
			<div class="sdp-card" style="padding:0;overflow-x:auto;">
				<table class="table table-striped table-condensed" style="margin:0;font-size:12px;">
					<thead>
						<tr>
							<?php foreach ($headers as $hcol) : ?>
								<th><?= h((string)$hcol) ?></th>
							<?php endforeach; ?>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($items as $it) : ?>
							<tr>
								<td><?= h((string)($it['col1'] ?? '')) ?></td>
								<td><?= h((string)($it['col2'] ?? '')) ?></td>
								<td><?= h((string)($it['col3'] ?? '')) ?></td>
								<td><?= h((string)($it['col4'] ?? '')) ?></td>
								<td>
									<?php if (!empty($it['link'])) : ?>
										<?= $this->Html->link(__('Ver'), $it['link'], ['class' => 'btn btn-xs btn-default']) ?>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php elseif ($mode === 'portal' || $mode === 'info') : ?>
			<?php if ($empty !== '') : ?>
				<p class="text-muted"><?= h($empty) ?></p>
			<?php endif; ?>
		<?php else : ?>
			<div class="sdp-card"><p class="text-muted" style="margin:0;"><?= h($empty !== '' ? $empty : __('Sem registos.')) ?></p></div>
		<?php endif; ?>
	</div>
</div>
<?php endif; ?>
