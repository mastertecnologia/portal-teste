<?php
/**
 * @var \App\View\AppView $this
 * @var array<string,mixed> $screen
 */
$items = (array)($screen['items'] ?? []);
?>
<?php if ($items === []) : ?>
	<div class="sdp-card"><p class="text-muted" style="margin:0;"><?= h((string)($screen['empty'] ?? __('Sem ativos.'))) ?></p></div>
<?php else : ?>
	<div class="sdp-cmdb-grid">
		<?php foreach ($items as $ci) : ?>
			<?php
			$ticketsOpen = (int)($ci['tickets'] ?? 0);
			$alert = $ticketsOpen > 0;
			?>
			<article class="sdp-cmdb-card<?= $alert ? ' sdp-cmdb-card-alert' : '' ?>">
				<div class="sdp-cmdb-card-tag"><?= h((string)($ci['tag'] ?? '')) ?></div>
				<div class="sdp-cmdb-card-name"><?= h((string)($ci['nome'] ?? '')) ?></div>
				<div class="sdp-cmdb-card-sub"><?= h((string)($ci['tipo'] ?? '')) ?></div>
				<div class="sdp-cmdb-card-meta">
					<span><?= h((string)($ci['cliente'] ?? '—')) ?></span>
					<span class="sdp-cmdb-host"><?= h((string)($ci['host'] ?? '—')) ?></span>
				</div>
				<div class="sdp-cmdb-card-foot">
					<?php if ($ticketsOpen > 0) : ?>
						<span class="sdp-cmdb-incident"><?= h(__('{0} ticket(s) aberto(s)', $ticketsOpen)) ?></span>
					<?php else : ?>
						<span class="text-muted"><?= h(__('Sem tickets abertos')) ?></span>
					<?php endif; ?>
					<?php if (!empty($ci['link'])) : ?>
						<?= $this->Html->link(__('Ver CI'), $ci['link'], ['class' => 'btn btn-xs btn-default']) ?>
					<?php endif; ?>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
	<div class="sdp-card" style="margin-top:14px;padding:0;overflow-x:auto;">
		<table class="table table-striped table-condensed" style="margin:0;font-size:12px;">
			<thead>
				<tr>
					<th><?= h(__('Tag')) ?></th>
					<th><?= h(__('Nome')) ?></th>
					<th><?= h(__('Cliente')) ?></th>
					<th><?= h(__('Tipo')) ?></th>
					<th><?= h(__('Host')) ?></th>
					<th><?= h(__('Tickets')) ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($items as $ci) : ?>
					<tr<?= (int)($ci['tickets'] ?? 0) > 0 ? ' class="sdp-row-alert"' : '' ?>>
						<td><code><?= h((string)($ci['tag'] ?? '')) ?></code></td>
						<td><?= h((string)($ci['nome'] ?? '')) ?></td>
						<td><?= h((string)($ci['cliente'] ?? '')) ?></td>
						<td><?= h((string)($ci['tipo'] ?? '')) ?></td>
						<td><?= h((string)($ci['host'] ?? '')) ?></td>
						<td><?= (int)($ci['tickets'] ?? 0) ?></td>
						<td>
							<?php if (!empty($ci['link'])) : ?>
								<?= $this->Html->link(__('Ver'), $ci['link'], ['class' => 'btn btn-xs btn-default']) ?>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
<?php endif; ?>
