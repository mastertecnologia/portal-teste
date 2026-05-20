<?php
/**
 * CSAT & NPS — placeholder pro com KPIs + roteiro de integração.
 *
 * @var \App\View\AppView $this
 * @var array<string,mixed> $screen
 */
$kpis = (array)($screen['kpis'] ?? []);
$ultimos = (array)($screen['csat_ultimos'] ?? []);
$breakdown = (array)($screen['csat_breakdown'] ?? []);
?>
<div class="pgm-erp-shell" style="background:transparent;min-height:0;">
	<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
		<div>
			<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Service Desk · Satisfação')) ?></div>
			<h1 style="font-size:22px;font-weight:600;margin:0;">⭐ <?= h(__('CSAT & NPS')) ?></h1>
			<div style="font-size:12px;color:var(--text-muted);"><?= h(__('Pesquisa de satisfação pós-atendimento e Net Promoter Score')) ?></div>
		</div>
		<?= $this->Html->link('← ' . __('Service Desk'), ['controller' => 'ServicedeskPrototype', 'action' => 'fila'], ['class' => 'btn btn-ghost btn-sm']) ?>
	</div>

	<?php if ($kpis !== []) : ?>
		<div class="summary-grid" style="margin-bottom:14px;">
			<?php foreach ($kpis as $k) : ?>
				<div class="summary-card" style="border-left:3px solid var(--teal);">
					<div class="lbl"><?= h((string)($k['lbl'] ?? '')) ?></div>
					<div class="val" style="color:var(--teal-dark);"><?= h((string)($k['val'] ?? '')) ?></div>
					<?php if (!empty($k['hint'])) : ?><div style="font-size:11px;color:var(--text-muted);"><?= h((string)$k['hint']) ?></div><?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php if ((int)($breakdown['total_nps'] ?? 0) > 0) : ?>
		<div class="card">
			<div class="sec-title">📊 <?= h(__('NPS · distribuição')) ?></div>
			<?php
			$total = (int)$breakdown['total_nps'];
			$pPct = $total > 0 ? round(((int)$breakdown['promotores'] / $total) * 100) : 0;
			$nPct = $total > 0 ? round(((int)$breakdown['neutros'] / $total) * 100) : 0;
			$dPct = $total > 0 ? round(((int)$breakdown['detratores'] / $total) * 100) : 0;
			?>
			<div style="display:flex;height:24px;border-radius:6px;overflow:hidden;background:#eee;font-size:11px;color:#fff;">
				<div style="width:<?= $dPct ?>%;background:#E24B4A;display:flex;align-items:center;justify-content:center;"><?= $dPct ?>%</div>
				<div style="width:<?= $nPct ?>%;background:#E9A025;display:flex;align-items:center;justify-content:center;color:#333;"><?= $nPct ?>%</div>
				<div style="width:<?= $pPct ?>%;background:#1D9E75;display:flex;align-items:center;justify-content:center;"><?= $pPct ?>%</div>
			</div>
			<div style="display:flex;justify-content:space-between;font-size:11px;color:var(--text-muted);margin-top:6px;">
				<span><?= sprintf(h(__('🔴 Detratores: %d')), (int)$breakdown['detratores']) ?></span>
				<span><?= sprintf(h(__('🟡 Neutros: %d')), (int)$breakdown['neutros']) ?></span>
				<span><?= sprintf(h(__('🟢 Promotores: %d')), (int)$breakdown['promotores']) ?></span>
			</div>
		</div>
	<?php endif; ?>

	<?php if ($ultimos !== []) : ?>
		<div class="card">
			<div class="sec-title">💬 <?= h(__('Últimas respostas')) ?></div>
			<?php foreach ($ultimos as $u) : ?>
				<div style="padding:10px 0;border-bottom:1px solid var(--border-light,#f0efec);font-size:12px;">
					<div style="display:flex;justify-content:space-between;gap:10px;align-items:center;">
						<div>
							<strong>Ticket #<?= (int)$u['ticket_id'] ?></strong>
							<span style="color:#E9A025;font-size:14px;margin-left:6px;"><?= str_repeat('★', (int)$u['csat']) ?><span style="color:#ddd;"><?= str_repeat('★', 5 - (int)$u['csat']) ?></span></span>
							<?php if ($u['nps'] !== null) : ?>
								<span class="badge <?= (int)$u['nps'] >= 9 ? 'b-paga' : ((int)$u['nps'] <= 6 ? 'b-recus' : 'b-pendente') ?>" style="margin-left:6px;font-size:9px;">NPS <?= (int)$u['nps'] ?></span>
							<?php endif; ?>
						</div>
						<span style="font-size:10px;color:var(--text-muted);"><?= h($u['data'] instanceof \DateTimeInterface ? $u['data']->format('d/m H:i') : '') ?></span>
					</div>
					<?php if (!empty($u['comentario'])) : ?>
						<div style="font-size:11px;color:var(--text-muted);margin-top:4px;font-style:italic;">"<?= h(\Cake\Utility\Text::truncate((string)$u['comentario'], 140, ['ellipsis' => '…'])) ?>"</div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="alert-box alert-blue">
		<strong><?= h(__('Como coletar respostas?')) ?></strong>
		<?= h(__('Após fechar um ticket, envie ao cliente o link público:')) ?>
		<code style="background:#fff;padding:2px 6px;border-radius:4px;font-size:11px;">/csat/csat-{TICKET_ID}-{TOKEN}</code>
		— <?= h(__('o token é gerado por TicketCsatController::tokenForTicket($id, $idempresa).')) ?>
	</div>
</div>
