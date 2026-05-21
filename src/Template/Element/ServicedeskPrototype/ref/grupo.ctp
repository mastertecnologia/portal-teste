<?php
/**
 * @var \App\View\AppView $this
 * @var array<string,mixed> $screen
 */
$grupo = (array)($screen['grupo'] ?? []);
$stats = (array)($grupo['stats'] ?? []);
$members = (array)($grupo['members'] ?? []);
$rows = (array)($grupo['rows'] ?? $screen['rows'] ?? []);
$queues = (array)($screen['queues'] ?? []);
$queueId = $screen['queue_id'] ?? null;
$H = $this->ServicedeskPrototype;
?>
<div id="pg-sd-grupo" class="pgm-sd-prototype">
	<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
		<div>
			<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Service Desk')) ?></div>
			<h1 style="font-size:22px;font-weight:600;margin:0;"><?= h((string)($screen['title'] ?? __('Tickets do meu grupo'))) ?></h1>
			<div style="font-size:12px;color:var(--text-muted);"><?= h((string)($screen['subtitle'] ?? '')) ?></div>
		</div>
		<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
			<?php if ($queues !== []) : ?>
				<form method="get" style="margin:0;">
					<select name="queue_id" onchange="this.form.submit()" style="padding:6px 10px;border:1px solid var(--border);border-radius:6px;font-size:12px;background:#fff;">
						<option value=""><?= h(__('Todas as filas')) ?></option>
						<?php foreach ($queues as $qid => $qname) : ?>
							<option value="<?= (int)$qid ?>" <?= $queueId !== null && (int)$queueId === (int)$qid ? 'selected' : '' ?>><?= h((string)$qname) ?></option>
						<?php endforeach; ?>
					</select>
				</form>
			<?php endif; ?>
			<a class="btn btn-ghost btn-sm" href="<?= h($H->sdpPage('meus')) ?>">🎯 <?= h(__('Meus tickets')) ?></a>
			<?= $this->Html->link('+ ' . __('Abrir chamado'), ['controller' => 'Servicedesk', 'action' => 'add'], ['class' => 'btn btn-primary btn-sm']) ?>
		</div>
	</div>

	<div class="card" style="margin-bottom:14px;padding:16px;background:linear-gradient(135deg,#EDE9F8,#fff);">
		<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:14px;">
			<div>
				<div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;font-weight:600;"><?= h(__('Total no grupo')) ?></div>
				<div style="font-size:28px;font-weight:700;color:#3D2D63;"><?= (int)($stats['total'] ?? 0) ?></div>
			</div>
			<div>
				<div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;font-weight:600;"><?= h(__('Sem atribuição')) ?></div>
				<div style="font-size:28px;font-weight:700;color:#8A4D02;"><?= (int)($stats['sem_tec'] ?? 0) ?></div>
			</div>
			<div>
				<div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;font-weight:600;"><?= h(__('SLA crítico')) ?></div>
				<div style="font-size:28px;font-weight:700;color:#7A1822;"><?= (int)($stats['sla_critico'] ?? 0) ?></div>
			</div>
			<div>
				<div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;font-weight:600;"><?= h(__('Tempo médio resolução')) ?></div>
				<div style="font-size:18px;font-weight:700;color:#3D2D63;"><?= h((string)($stats['tempo_medio'] ?? '—')) ?></div>
			</div>
			<div>
				<div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;font-weight:600;"><?= h(__('CSAT grupo')) ?></div>
				<div style="font-size:28px;font-weight:700;color:#3D2D63;"><?= ($stats['csat'] ?? null) !== null ? '⭐ ' . h((string)$stats['csat']) : '—' ?></div>
			</div>
		</div>
	</div>

	<?php if ($members !== []) : ?>
		<div class="card" style="margin-bottom:14px;">
			<div class="sec-title">👥 <?= h(__('Membros do grupo · carga atual')) ?></div>
			<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px;">
				<?php foreach ($members as $m) : ?>
					<div style="padding:12px;background:var(--bg-surface);border-radius:var(--radius);border-left:3px solid var(--teal);">
						<div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
							<div style="width:36px;height:36px;border-radius:50%;background:<?= h((string)($m['avatar_bg'] ?? 'var(--teal)')) ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;"><?= h((string)($m['initials'] ?? '?')) ?></div>
							<div>
								<strong style="font-size:13px;"><?= h((string)($m['nome'] ?? '')) ?></strong>
								<div style="font-size:10px;color:var(--teal-dark);">🟢 <?= h(__('Online')) ?></div>
							</div>
						</div>
						<div style="font-size:11px;display:flex;justify-content:space-between;"><span style="color:var(--text-muted);"><?= h(__('Tickets ativos')) ?></span><strong><?= (int)($m['ativos'] ?? 0) ?></strong></div>
						<div style="font-size:11px;display:flex;justify-content:space-between;"><span style="color:var(--text-muted);"><?= h(__('Hoje resolvidos')) ?></span><strong><?= (int)($m['resolvidos_hoje'] ?? 0) ?></strong></div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

	<div class="card" style="padding:0;overflow:hidden;">
		<div style="padding:12px 14px;border-bottom:1px solid var(--border-light);background:var(--bg-surface);display:flex;justify-content:space-between;align-items:center;">
			<strong style="font-size:13px;">📋 <?= h(__('Tickets do grupo')) ?></strong>
			<button type="button" class="btn btn-primary btn-xs" disabled>⚡ <?= h(__('Pegar próximo da fila')) ?></button>
		</div>
		<div style="overflow-x:auto;">
			<table style="width:100%;border-collapse:collapse;font-size:12px;">
				<thead>
					<tr style="background:var(--bg-surface);border-bottom:1px solid var(--border);">
						<th style="padding:10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h(__('Ticket')) ?></th>
						<th style="padding:10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h(__('Cliente')) ?></th>
						<th style="padding:10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h(__('Assunto')) ?></th>
						<th style="padding:10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h(__('Status')) ?></th>
						<th style="padding:10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);">SLA</th>
						<th style="padding:10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h(__('Técnico')) ?></th>
						<th style="padding:10px;text-align:center;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h(__('Ações')) ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($rows as $r) : ?>
						<?php $id = (int)($r['id'] ?? 0); ?>
						<tr style="border-bottom:1px solid var(--border-light);<?= !empty($r['row_bg']) ? 'background:' . h((string)$r['row_bg']) . ';' : '' ?>">
							<td style="padding:10px;"><?= $this->Html->link('#' . $id, $H->sdpTicketUrl($id), ['style' => 'color:var(--teal);font-family:monospace;font-weight:700;text-decoration:none;']) ?></td>
							<td style="padding:10px;"><?= h((string)($r['cliente'] ?? '')) ?></td>
							<td style="padding:10px;font-weight:600;"><?= h(\Cake\Utility\Text::truncate((string)($r['assunto'] ?? ''), 40, ['ellipsis' => '…'])) ?></td>
							<td style="padding:10px;"><span class="badge" style="font-size:10px;background:<?= h((string)($r['pill_bg'] ?? '#eee')) ?>;color:<?= h((string)($r['pill_color'] ?? '#333')) ?>;"><?= h((string)($r['situacao_label'] ?? '')) ?></span></td>
							<td style="padding:10px;"><span style="color:<?= h((string)($r['sla_color'] ?? 'var(--teal-dark)')) ?>;font-weight:600;font-size:11px;"><?= h((string)($r['sla_label'] ?? '')) ?></span></td>
							<td style="padding:10px;<?= !empty($r['unassigned']) ? 'font-style:italic;color:#8A4D02;' : '' ?>"><?= h((string)($r['tecnico'] ?? '')) ?></td>
							<td style="padding:10px;text-align:center;">
								<?php if (!empty($r['unassigned'])) : ?>
									<button type="button" class="btn btn-amber btn-xs" disabled><?= h(__('Pegar')) ?></button>
								<?php else : ?>
									<?= $this->Html->link(__('Ver'), $H->sdpTicketUrl($id), ['class' => 'btn btn-ghost btn-xs']) ?>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					<?php if ($rows === []) : ?>
						<tr><td colspan="7" style="padding:16px;color:var(--text-muted);"><?= h((string)($screen['empty'] ?? __('Sem tickets.'))) ?></td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
