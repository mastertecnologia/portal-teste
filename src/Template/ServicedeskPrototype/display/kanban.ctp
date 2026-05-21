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
$tecnicos = (array)($kanban['tecnicos'] ?? []);
$queueId = (int)($kanban['queue_id'] ?? 0);
$tecnicoId = (int)($kanban['tecnico_id'] ?? 0);
$readonly = !isset($kanban['readonly']) || !empty($kanban['readonly']);
$H = $this->ServicedeskPrototype;
$kanbanUrl = $H->sdpPage('kanban');
?>
<div class="pgm-sd-prototype" id="pg-sd-kanban">
	<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
		<div>
			<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Service Desk')) ?></div>
			<h1 style="font-size:22px;font-weight:600;margin:0 0 4px;"><?= h(__('Kanban de tickets')) ?></h1>
			<div style="font-size:12px;color:var(--text-muted);"><?= h(__('Arraste tickets entre etapas · WIP limits ativos')) ?><?= $readonly ? ' · ' . h(__('protótipo somente leitura')) : '' ?></div>
		</div>
		<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
			<?= $this->Html->link('📋 ' . __('Fila técnica'), $H->sdpPage('fila'), ['class' => 'btn btn-ghost btn-sm']) ?>
			<form method="get" action="<?= h($kanbanUrl) ?>" style="margin:0;display:flex;gap:8px;flex-wrap:wrap;">
				<?php if ($queueId > 0) : ?><input type="hidden" name="queue_id" value="<?= $queueId ?>" /><?php endif; ?>
				<select name="tecnico_id" class="sdp-select" style="padding:6px 10px;font-size:12px;min-width:160px;" onchange="this.form.submit()">
					<option value=""><?= h(__('Por técnico: Todos')) ?></option>
					<?php foreach ($tecnicos as $tec) : ?>
						<?php $tid = (int)($tec['id'] ?? 0); ?>
						<option value="<?= $tid ?>" <?= $tecnicoId === $tid ? 'selected' : '' ?>><?= h((string)($tec['name'] ?? '')) ?></option>
					<?php endforeach; ?>
				</select>
				<select name="queue_id" class="sdp-select" style="padding:6px 10px;font-size:12px;min-width:160px;" onchange="this.form.submit()">
					<option value=""><?= h(__('Por fila: Todas')) ?></option>
					<?php foreach ($queues as $q) : ?>
						<?php $qid = (int)($q['id'] ?? 0); ?>
						<option value="<?= $qid ?>" <?= $queueId === $qid ? 'selected' : '' ?>><?= h((string)($q['name'] ?? '')) ?></option>
					<?php endforeach; ?>
				</select>
			</form>
			<?= $this->Html->link('+ ' . __('Novo chamado'), ['controller' => 'Servicedesk', 'action' => 'add'], ['class' => 'btn btn-primary btn-sm']) ?>
		</div>
	</div>

	<?php if ($truncated && $hint !== '') : ?>
		<div class="alert-box alert-red" style="font-size:12px;margin-bottom:12px;"><?= h($hint) ?></div>
	<?php endif; ?>

	<?php if ($mode === 'empty' || $columns === []) : ?>
		<div class="card"><p style="margin:0;color:var(--text-muted);"><?= h($hint !== '' ? $hint : __('Sem colunas para exibir.')) ?></p></div>
	<?php else : ?>
		<div class="sdp-kanban-mockup-grid sdp-kanban-dnd-board" style="display:grid;grid-template-columns:repeat(5,minmax(220px,1fr));gap:12px;overflow-x:auto;" data-readonly="<?= $readonly ? '1' : '0' ?>">
			<?php foreach ($columns as $col) : ?>
				<?php
				$cards = (array)($col['cards'] ?? []);
				$total = (int)($col['total'] ?? count($cards));
				$sty = (array)($col['style'] ?? []);
				$colKey = (string)($col['key'] ?? '');
				$border = (string)($sty['border'] ?? 'var(--border)');
				$bg = (string)($sty['bg'] ?? '#fff');
				$countBg = (string)($sty['count_bg'] ?? $border);
				$countColor = (string)($sty['count_color'] ?? '#fff');
				$titleColor = (string)($col['title_color'] ?? 'inherit');
				$more = (int)($col['more'] ?? 0);
				?>
				<div class="sdp-kanban-col-drop" data-col="<?= h($colKey) ?>" style="background:<?= h($bg) ?>;border-radius:var(--radius);padding:12px;min-height:600px;">
					<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;padding-bottom:10px;border-bottom:2px solid <?= h($border) ?>;">
						<div>
							<strong style="font-size:13px;color:<?= h($titleColor) ?>;"><?= h((string)($col['title'] ?? '')) ?></strong>
							<?php if (!empty($col['sub'])) : ?>
								<div style="font-size:10px;color:var(--text-muted);"><?= h((string)$col['sub']) ?></div>
							<?php endif; ?>
						</div>
						<span style="background:<?= h($countBg) ?>;color:<?= h($countColor) ?>;padding:3px 8px;border-radius:10px;font-size:11px;font-weight:700;"><?= h((string)$total) ?></span>
					</div>
					<div class="sdp-kanban-col-body" style="display:flex;flex-direction:column;gap:8px;min-height:120px;">
						<?php foreach ($cards as $card) : ?>
							<?php
							$cid = (int)($card['id'] ?? 0);
							$url = $H->sdpTicketUrl($cid);
							$slaBad = !empty($card['sla_bad']);
							$closed = !empty($card['closed']);
							$prioLabel = (string)($card['prioridade_label'] ?? '');
							$pHigh = stripos($prioLabel, __('Alto')) !== false || stripos($prioLabel, __('Crítico')) !== false;
							$borderLeft = $slaBad ? 'var(--red)' : ($pHigh && !$closed ? 'var(--amber)' : $border);
							?>
							<div class="sdp-kanban-card-dnd" draggable="true" data-ticket-id="<?= $cid ?>" data-href="<?= h($url) ?>"
								style="padding:10px;background:#fff;border-radius:8px;border-left:3px solid <?= h($borderLeft) ?>;cursor:grab;<?= $closed ? 'opacity:.85;' : '' ?>">
								<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
									<span style="font-family:monospace;font-weight:700;color:<?= $closed ? 'var(--text-muted)' : 'var(--teal)' ?>;font-size:12px;">#<?= $cid ?></span>
									<span style="font-size:10px;color:<?= $slaBad ? '#7A1822' : ($pHigh ? '#8A4D02' : 'var(--text-muted)') ?>;"><?= h((string)($card['sla_label'] ?? $card['tempo'] ?? '')) ?></span>
								</div>
								<div style="font-size:12px;font-weight:600;margin-bottom:4px;"><?= h(\Cake\Utility\Text::truncate((string)($card['assunto'] ?? ''), 60, ['ellipsis' => '…'])) ?></div>
								<div style="font-size:10px;color:var(--text-muted);"><?= h((string)($card['cliente'] ?? '—')) ?></div>
								<?php $cardTags = (array)($card['tags'] ?? []); ?>
								<?php if ($cardTags !== []) : ?>
									<div style="display:flex;gap:4px;margin-top:6px;flex-wrap:wrap;">
										<?php foreach ($cardTags as $tag) : ?>
											<span style="padding:2px 6px;background:var(--bg-surface);border-radius:4px;font-size:9px;"><?= h((string)$tag) ?></span>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
								<?php if (!empty($card['hint'])) : ?>
									<div style="font-size:10px;color:#8A4D02;margin-top:4px;"><?= h((string)$card['hint']) ?></div>
								<?php endif; ?>
								<?php if ($prioLabel !== '' && $pHigh) : ?>
									<div style="display:flex;gap:4px;margin-top:6px;">
										<span style="padding:2px 6px;background:<?= $slaBad ? '#F8D8DA' : '#FAEEDA' ?>;color:<?= $slaBad ? '#7A1822' : '#8A4D02' ?>;border-radius:4px;font-size:9px;font-weight:700;"><?= h(strtoupper($prioLabel)) ?></span>
									</div>
								<?php elseif (!empty($card['tempo']) && (string)$card['tempo'] !== '—' && $colKey === 'execucao') : ?>
									<div style="font-size:10px;color:var(--text-muted);margin-top:4px;">⏱ <?= h((string)$card['tempo']) ?></div>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
					<?php if ($more > 0) : ?>
						<button type="button" class="btn btn-ghost btn-xs" style="margin-top:8px;" disabled><?= h(sprintf(__('Ver mais (%d)'), $more)) ?></button>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
<?php
$this->Html->scriptBlock(
	'(function(){' .
	'var board=document.querySelector(".sdp-kanban-dnd-board");if(!board)return;' .
	'var readonly=board.getAttribute("data-readonly")==="1";' .
	'var dragEl=null,fromCol=null;' .
	'board.querySelectorAll(".sdp-kanban-card-dnd").forEach(function(card){' .
	'card.addEventListener("dragstart",function(ev){dragEl=card;fromCol=card.closest(".sdp-kanban-col-drop");card.style.opacity="0.5";ev.dataTransfer.effectAllowed="move";});' .
	'card.addEventListener("dragend",function(){card.style.opacity="";dragEl=null;fromCol=null;board.querySelectorAll(".sdp-kanban-col-drop").forEach(function(c){c.classList.remove("sdp-kanban-drop-over");});});' .
	'card.addEventListener("click",function(ev){if(ev.defaultPrevented)return;var href=card.getAttribute("data-href");if(href)window.location.href=href;});' .
	'});' .
	'board.querySelectorAll(".sdp-kanban-col-drop").forEach(function(col){' .
	'col.addEventListener("dragover",function(ev){ev.preventDefault();col.classList.add("sdp-kanban-drop-over");});' .
	'col.addEventListener("dragleave",function(){col.classList.remove("sdp-kanban-drop-over");});' .
	'col.addEventListener("drop",function(ev){ev.preventDefault();col.classList.remove("sdp-kanban-drop-over");' .
	'if(!dragEl)return;if(readonly){alert(' . json_encode(__('Protótipo somente leitura — movimentação não persiste.')) . ');if(fromCol){var rb=fromCol.querySelector(".sdp-kanban-col-body");if(rb)rb.appendChild(dragEl);}return;}' .
	'var body=col.querySelector(".sdp-kanban-col-body");if(body&&dragEl.parentNode!==body){body.appendChild(dragEl);}' .
	'});});' .
	'})();',
	['block' => 'script']
);
