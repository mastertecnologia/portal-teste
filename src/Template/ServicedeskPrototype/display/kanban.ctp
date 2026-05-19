<?php

/**

 * @var \App\View\AppView $this

 * @var string $title

 * @var array<string,mixed> $kanban

 */

$this->assign('title', $title);

$mode = (string)($kanban['mode'] ?? '');

$columns = (array)($kanban['columns'] ?? []);

$truncated = !empty($kanban['truncated']);

$hint = (string)($kanban['hint'] ?? '');

$queues = (array)($kanban['queues'] ?? []);

$queueId = (int)($kanban['queue_id'] ?? 0);

$H = $this->ServicedeskPrototype;

$colStyles = [

	['bg' => '#F0FDF4', 'border' => '#7DD3C0'],

	['bg' => '#ECFEFF', 'border' => '#06B6D4'],

	['bg' => '#FFFBEB', 'border' => '#F59E0B'],

	['bg' => '#F3F4F6', 'border' => '#9CA3AF'],

];

$kanbanUrl = $H->sdpPage('kanban');

?>

<div class="row">

	<div class="col-12 pgm-sd-prototype" id="pg-sd-kanban">

		<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">

			<div>

				<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;">Service Desk</div>

				<h1 style="font-size:22px;font-weight:600;margin:0 0 4px;"><?= h(__('Kanban de tickets')) ?></h1>

				<div style="font-size:12px;color:var(--text-muted);"><?= h(__('Situação operacional e fila · dados reais (somente leitura no protótipo)')) ?></div>

			</div>

			<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">

				<?= $this->Html->link('📋 ' . __('Fila técnica'), $H->sdpPage('fila'), ['class' => 'btn btn-ghost btn-sm']) ?>

				<?php if ($queues !== []) : ?>

					<form method="get" action="<?= h($kanbanUrl) ?>" style="margin:0;">

						<select name="queue_id" class="sdp-select" style="padding:6px 10px;font-size:12px;min-width:180px;" onchange="this.form.submit()">

							<option value=""><?= h(__('Por fila: Todas')) ?></option>

							<?php foreach ($queues as $q) : ?>

								<?php $qid = (int)($q['id'] ?? 0); ?>

								<option value="<?= $qid ?>" <?= $queueId === $qid ? 'selected' : '' ?>><?= h((string)($q['name'] ?? '')) ?></option>

							<?php endforeach; ?>

						</select>

					</form>

				<?php endif; ?>

				<?= $this->Html->link('+ ' . __('Novo chamado'), ['controller' => 'Servicedesk', 'action' => 'add'], ['class' => 'btn btn-primary btn-sm']) ?>

			</div>

		</div>



		<?php if ($truncated && $hint !== '') : ?>

			<div class="alert-box alert-red" style="font-size:12px;margin-bottom:12px;"><?= h($hint) ?></div>

		<?php endif; ?>



		<?php if ($mode === 'empty' || $columns === []) : ?>

			<div class="card"><p style="margin:0;color:var(--text-muted);"><?= h($hint !== '' ? $hint : __('Sem colunas para exibir.')) ?></p></div>

		<?php else : ?>

			<div class="sdp-kanban-mockup-grid">

				<?php foreach ($columns as $i => $col) : ?>

					<?php

					$cards = (array)($col['cards'] ?? []);

					$total = (int)($col['total'] ?? count($cards));

					$styCol = (array)($col['style'] ?? []);

					$sty = [

						'bg' => (string)($styCol['bg'] ?? ($colStyles[$i % count($colStyles)]['bg'] ?? '#fff')),

						'border' => (string)($styCol['border'] ?? ($colStyles[$i % count($colStyles)]['border'] ?? 'var(--border)')),

					];

					?>

					<div style="background:<?= h($sty['bg']) ?>;border-radius:var(--radius);padding:12px;min-height:400px;">

						<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;padding-bottom:10px;border-bottom:2px solid <?= h($sty['border']) ?>;">

							<div>

								<strong style="font-size:13px;"><?= h((string)($col['title'] ?? '')) ?></strong>

								<?php if (!empty($col['sub'])) : ?>

									<div style="font-size:10px;color:var(--text-muted);"><?= h((string)$col['sub']) ?></div>

								<?php endif; ?>

							</div>

							<span style="background:<?= h($sty['border']) ?>;color:#fff;padding:3px 8px;border-radius:10px;font-size:11px;font-weight:700;"><?= h((string)$total) ?></span>

						</div>

						<div style="display:flex;flex-direction:column;gap:8px;">

							<?php foreach ($cards as $card) : ?>

								<?php

								$cid = (int)($card['id'] ?? 0);

								$url = $H->sdpTicketUrl($cid);

								$slaBad = !empty($card['sla_status']) && (string)$card['sla_status'] === 'violado';

								?>

								<div style="padding:10px;background:#fff;border-radius:8px;border-left:3px solid <?= $slaBad ? 'var(--red)' : h($sty['border']) ?>;cursor:pointer;" onclick="window.location.href='<?= h($url) ?>'">

									<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">

										<span style="font-family:monospace;font-weight:700;color:var(--teal);font-size:12px;">#<?= $cid ?></span>

										<span style="font-size:10px;color:<?= $slaBad ? '#7A1822' : 'var(--text-muted)' ?>;"><?= $slaBad ? '⚠ SLA' : h((string)($card['tempo'] ?? '')) ?></span>

									</div>

									<div style="font-size:12px;font-weight:600;margin-bottom:4px;"><?= h(\Cake\Utility\Text::truncate((string)($card['assunto'] ?? ''), 60, ['ellipsis' => '…'])) ?></div>

									<div style="font-size:10px;color:var(--text-muted);"><?= h((string)($card['cliente'] ?? '—')) ?></div>

									<?php if (!empty($card['fila_label']) && (string)$card['fila_label'] !== '—') : ?>

										<div style="font-size:10px;color:var(--teal-dark);margin-top:4px;"><?= h((string)$card['fila_label']) ?></div>

									<?php endif; ?>

								</div>

							<?php endforeach; ?>

						</div>

					</div>

				<?php endforeach; ?>

			</div>

		<?php endif; ?>

	</div>

</div>

