<?php
/**
 * Dashboard executivo — dados reais ($proto).
 *
 * @var \App\View\AppView $this
 * @var string $uFila
 * @var string $uClientes
 * @var array<string,mixed> $proto
 */
$snap = (array)($proto['snapshot'] ?? []);
$backlogAbac = (int)($proto['backlog_abac'] ?? 0);
$ticketsHoje = (int)($proto['tickets_hoje'] ?? 0);
$ticketsOntem = (int)($proto['tickets_ontem'] ?? 0);
$slaViol = (int)($proto['sla_violados_total'] ?? 0);
$near = (int)($snap['sla_por_etapa']['near_due'] ?? 0);
$paused = (int)($snap['sla_por_etapa']['paused'] ?? 0);
$kpis = (array)($snap['sla_operational_kpis'] ?? []);
$aguardaCli = (int)($kpis['aguardando_cliente'] ?? 0);
$semTec = (int)($kpis['sem_tecnico'] ?? 0);

$vol = (array)($proto['vol_diario_14'] ?? []);
$volMax = 1;
foreach ($vol as $v) {
	$volMax = max($volMax, (int)($v['abertos'] ?? 0) + (int)($v['fechados'] ?? 0));
}
$deltaTxt = '—';
if ($ticketsOntem > 0) {
	$d = (int)round(100 * ($ticketsHoje - $ticketsOntem) / $ticketsOntem);
	$deltaTxt = ($d >= 0 ? '↑ ' : '↓ ') . abs($d) . '% ' . __('vs ontem');
} elseif ($ticketsHoje > 0 && $ticketsOntem === 0) {
	$deltaTxt = __('↑ vs ontem (0)');
}

$sitRows = (array)($proto['por_situacao_aberto'] ?? []);

$topCli = (array)($proto['top_clientes'] ?? []);
$topAss = (array)($proto['top_assuntos'] ?? []);
$quentes = (array)($proto['assuntos_quentes'] ?? []);
$equipe = (array)($proto['equipe'] ?? []);
$abertosPreview = (array)($proto['tickets_abertos_preview'] ?? []);
$backlogEmpresa = (int)($proto['backlog_empresa'] ?? $backlogAbac);
$heatmap = (array)($proto['heatmap'] ?? []);
$hmRows = (array)($heatmap['rows'] ?? []);
$hmHours = (array)($heatmap['hours'] ?? range(8, 18));
$hmMax = max(1, (int)($heatmap['max'] ?? 1));
$hmDays = (array)($heatmap['day_labels'] ?? ['Seg', 'Ter', 'Qua', 'Qui', 'Sex']);
$H = $this->ServicedeskPrototype;
?>
<div id="pg-sd-dashboard">
	<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
		<div>
			<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Service Desk · Visão executiva')) ?></div>
			<h1 style="font-size:22px;font-weight:600;"><?= h(__('Dashboard executivo')) ?></h1>
			<div style="font-size:12px;color:var(--text-muted);"><?= h(sprintf(__('Atualizado em %s (cache operacional pode aplicar alguns segundos).'), (string)($proto['gerado_em'] ?? ''))) ?></div>
		</div>
		<div style="display:flex;gap:8px;flex-wrap:wrap;">
			<a class="btn btn-ghost btn-sm" href="<?= h($uFila) ?>"><?= h(__('Fila técnica')) ?></a>
			<?= $this->Html->link(__('Painel operacional'), ['controller' => 'Servicedesk', 'action' => 'operacional'], ['class' => 'btn btn-default btn-sm']) ?>
		</div>
	</div>

	<?php if ($slaViol > 0) : ?>
	<div class="alert-box alert-red" style="margin-bottom:14px;">
		🚨 <strong><?= h(sprintf(__('%d tickets com SLA estourado'), $slaViol)) ?></strong> <?= h(__('requerem ação imediata')) ?> ·
		<a href="<?= h($uFila) ?>" style="color:var(--teal-dark);font-weight:700;text-decoration:underline;margin-left:4px;"><?= h(__('ver agora →')) ?></a>
	</div>
	<?php endif; ?>

	<div class="summary-grid" style="margin-bottom:14px;">
		<div class="summary-card" style="border-left:3px solid var(--teal);"><div class="lbl"><?= h(__('Volume hoje')) ?></div><div class="val" style="color:var(--teal-dark);"><?= (int)$ticketsHoje ?></div><div style="font-size:11px;color:var(--text-muted);"><?= h($deltaTxt) ?></div></div>
		<div class="summary-card" style="border-left:3px solid var(--blue);"><div class="lbl"><?= h(__('Em aberto')) ?></div><div class="val" style="color:#0C447C;"><?= (int)$backlogEmpresa ?></div><div style="font-size:11px;color:var(--text-muted);"><?= h(__('total empresa')) ?><?php if ($backlogAbac !== $backlogEmpresa) : ?> · <?= h(sprintf(__('seu escopo: %d'), $backlogAbac)) ?><?php endif; ?></div></div>
		<div class="summary-card" style="<?= $slaViol > 0 ? 'background:#F8D8DA;border-left:3px solid var(--red);' : 'border-left:3px solid var(--gray-400);' ?>"><div class="lbl"><?= h(__('SLA crítico / violado')) ?></div><div class="val" style="<?= $slaViol > 0 ? 'color:#7A1822;' : '' ?>"><?= (int)$slaViol ?></div><div style="font-size:11px;color:var(--text-muted);"><?= h(__('Lista no painel operacional')) ?></div></div>
		<div class="summary-card" style="background:#FAEEDA;border-left:3px solid var(--amber);"><div class="lbl"><?= h(__('Perto do limite (30 min)')) ?></div><div class="val" style="color:#8A4D02;"><?= (int)$near ?></div><div style="font-size:11px;color:var(--text-muted);"><?= h(__('Empresa')) ?></div></div>
		<div class="summary-card" style="border-left:3px solid #6B5B95;"><div class="lbl"><?= h(__('P1 abertos (empresa)')) ?></div><div class="val" style="color:#3D2D63;"><?= (int)($snap['p1_abertos'] ?? 0) ?></div><div style="font-size:11px;color:var(--text-muted);"><?= h(__('Prioridade máxima')) ?></div></div>
		<div class="summary-card" style="border-left:3px solid #D946A0;"><div class="lbl"><?= h(__('SLA pausado')) ?></div><div class="val" style="color:#7A1B5C;"><?= (int)$paused ?></div><div style="font-size:11px;color:var(--text-muted);"><?= h(__('Empresa')) ?></div></div>
	</div>

	<div class="summary-grid" style="margin-bottom:14px;">
		<div class="summary-card" style="border-left:3px solid var(--teal-mid);"><div class="lbl"><?= h(__('Sem técnico')) ?></div><div class="val" style="color:var(--teal-dark);"><?= (int)$semTec ?></div><div style="font-size:11px;color:var(--text-muted);"><?= h(__('Abertos')) ?></div></div>
		<div class="summary-card" style="border-left:3px solid var(--blue);"><div class="lbl"><?= h(__('Aguarda cliente')) ?></div><div class="val" style="color:#0C447C;"><?= (int)$aguardaCli ?></div><div style="font-size:11px;color:var(--text-muted);"><?= h(__('Respondido')) ?></div></div>
		<div class="summary-card" style="border-left:3px solid var(--amber);"><div class="lbl"><?= h(__('Resolvidos hoje')) ?></div><div class="val" style="color:#8A4D02;"><?= (int)($snap['resolvidos_hoje'] ?? 0) ?></div><div style="font-size:11px;color:var(--text-muted);"><?= h(__('Empresa')) ?></div></div>
		<div class="summary-card" style="opacity:.85;border-left:3px solid var(--border);"><div class="lbl"><?= h(__('FCR / CSAT / NPS')) ?></div><div class="val" style="color:var(--text-muted);">—</div><div style="font-size:11px;color:var(--text-muted);"><?= h(__('Não medido neste protótipo')) ?></div></div>
		<div class="summary-card" style="opacity:.85;border-left:3px solid var(--border);"><div class="lbl"><?= h(__('Receita SD (mockup)')) ?></div><div class="val" style="color:var(--text-muted);">—</div><div style="font-size:11px;color:var(--text-muted);"><?= h(__('Use financeiro / OS')) ?></div></div>
		<div class="summary-card" style="opacity:.85;border-left:3px solid var(--border);"><div class="lbl"><?= h(__('Horas técnicas')) ?></div><div class="val" style="color:var(--text-muted);">—</div><div style="font-size:11px;color:var(--text-muted);"><?= h(__('Ver apontamentos / OS')) ?></div></div>
	</div>

	<div class="g2" style="margin-bottom:14px;">
		<div class="card">
			<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
				<div class="sec-title sdp-sec-no-line" style="margin:0;border:none;"><?= h(__('Volume diário · últimos 14 dias')) ?></div>
				<div style="font-size:11px;color:var(--text-muted);"><span style="color:var(--teal-dark);">●</span> <?= h(__('Criados')) ?> · <span style="color:#D946A0;">●</span> <?= h(__('Resolvidos')) ?></div>
			</div>
			<div style="height:200px;background:var(--bg-surface);border-radius:var(--radius);padding:14px;display:flex;align-items:flex-end;gap:4px;">
				<?php foreach ($vol as $v) : ?>
					<?php
					$a = (int)($v['abertos'] ?? 0);
					$f = (int)($v['fechados'] ?? 0);
					$ha = $volMax > 0 ? (int)max(4, round(160 * $a / $volMax)) : 0;
					$hf = $volMax > 0 ? (int)max(2, round(160 * $f / $volMax)) : 0;
					?>
					<div style="flex:1;display:flex;flex-direction:column;gap:1px;align-items:center;justify-content:flex-end;min-width:0;height:160px;">
						<div style="width:100%;background:var(--teal);height:<?= $ha ?>px;border-radius:2px 2px 0 0;min-height:<?= $a > 0 ? 4 : 0 ?>px;"></div>
						<div style="width:100%;background:#D946A0;height:<?= $hf ?>px;min-height:<?= $f > 0 ? 2 : 0 ?>px;"></div>
						<span style="font-size:9px;color:var(--text-muted);margin-top:4px;"><?= h((string)($v['day'] ?? '')) ?></span>
					</div>
				<?php endforeach; ?>
				<?php if ($vol === []) : ?>
					<div class="text-muted" style="align-self:center;width:100%;text-align:center;"><?= h(__('Sem coluna created nos tickets.')) ?></div>
				<?php endif; ?>
			</div>
		</div>

		<div class="card">
			<div class="sec-title"><?= h(__('Distribuição por situação (abertos no seu escopo)')) ?></div>
			<?php if ($sitRows === []) : ?>
				<p class="text-muted"><?= h(__('Sem dados.')) ?></p>
			<?php else : ?>
				<div style="display:flex;flex-direction:column;gap:10px;">
					<?php foreach ($sitRows as $r) : ?>
						<div>
							<div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px;">
								<span><?= h((string)($r['label'] ?? '')) ?></span>
								<strong><?= (int)($r['count'] ?? 0) ?> (<?= h((string)($r['pct'] ?? '0')) ?>%)</strong>
							</div>
							<div style="height:10px;background:var(--bg-surface);border-radius:5px;overflow:hidden;">
								<div style="height:100%;width:<?= h((string)min(100, (float)($r['pct'] ?? 0))) ?>%;background:var(--teal);"></div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
				<div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border-light);">
					<div class="sec-title" style="margin-bottom:8px;"><?= h(__('Top assuntos (90 dias)')) ?></div>
					<div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;font-size:11px;">
						<?php foreach ($topAss as $a) : ?>
							<div><?= h(\Cake\Utility\Text::truncate((string)($a['label'] ?? ''), 42, ['ellipsis' => '…'])) ?> · <strong><?= (int)($a['count'] ?? 0) ?></strong></div>
						<?php endforeach; ?>
						<?php if ($topAss === []) : ?>
							<div class="text-muted"><?= h(__('Sem assuntos no período.')) ?></div>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<div class="sdp-grid-3" style="margin-bottom:14px;">
		<div class="card">
			<div class="sec-title"><?= h(__('Top clientes (90 dias)')) ?></div>
			<div style="display:flex;flex-direction:column;gap:8px;">
				<?php foreach ($topCli as $c) : ?>
					<div style="display:flex;justify-content:space-between;align-items:center;padding:8px;background:var(--bg-surface);border-radius:6px;">
						<div><strong style="font-size:12px;"><?= h((string)($c['name'] ?? '')) ?></strong></div>
						<strong style="color:var(--teal-dark);"><?= (int)($c['count'] ?? 0) ?></strong>
					</div>
				<?php endforeach; ?>
				<?php if ($topCli === []) : ?>
					<p class="text-muted small"><?= h(__('Nenhum ticket com cliente no período.')) ?></p>
				<?php endif; ?>
				<?= $this->Html->link(__('Ver clientes'), $uClientes, ['class' => 'btn btn-link btn-xs', 'style' => 'padding-left:0;']) ?>
			</div>
		</div>

		<div class="card">
			<div class="sec-title"><?= h(__('Assuntos com pico (24h)')) ?></div>
			<div style="display:flex;flex-direction:column;gap:8px;">
				<?php if ($quentes === []) : ?>
					<p class="text-muted small"><?= h(__('Nenhum assunto com tickets nas últimas 24h.')) ?></p>
				<?php else : ?>
					<?php foreach ($quentes as $q) : ?>
						<div style="padding:10px;background:var(--blue-light);border-radius:8px;border-left:3px solid var(--blue);">
							<div style="font-size:12px;font-weight:600;color:#0C447C;"><?= h((string)($q['label'] ?? '')) ?></div>
							<div style="font-size:11px;color:var(--text-muted);"><?= h(sprintf(__('%d tickets'), (int)($q['count'] ?? 0))) ?></div>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>

		<div class="card">
			<div class="sec-title"><?= h(__('Técnicos com mais chamados abertos')) ?></div>
			<div style="display:flex;flex-direction:column;gap:8px;">
				<?php if ($equipe === []) : ?>
					<p class="text-muted small"><?= h(__('Sem atribuições ou colunas indisponíveis.')) ?></p>
				<?php else : ?>
					<?php foreach ($equipe as $m) : ?>
						<div style="display:flex;align-items:center;gap:8px;padding:8px;background:var(--bg-surface);border-radius:6px;">
							<div style="width:32px;height:32px;border-radius:50%;background:var(--teal);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:11px;"><?= h((string)($m['initials'] ?? '?')) ?></div>
							<div style="flex:1;"><strong style="font-size:12px;"><?= h((string)($m['name'] ?? '')) ?></strong><div style="font-size:10px;color:var(--text-muted);"><?= h(sprintf(__('%d abertos'), (int)($m['abertos'] ?? 0))) ?></div></div>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<?php if ($abertosPreview !== []) : ?>
	<div class="card" style="margin-bottom:14px;">
		<div class="sec-title"><?= h(__('Tickets em aberto (seu escopo)')) ?></div>
		<div style="overflow-x:auto;">
			<table style="width:100%;border-collapse:collapse;font-size:12px;">
				<thead>
					<tr style="border-bottom:1px solid var(--border);">
						<?php foreach ([__('Ticket'), __('Assunto'), __('Status'), __('Técnico'), __('Cliente')] as $th) : ?>
							<th style="padding:10px 12px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h($th) ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($abertosPreview as $row) : ?>
						<tr style="border-bottom:1px solid var(--border-light);">
							<td style="padding:10px 12px;"><?= $this->Html->link('#' . (int)($row['id'] ?? 0), $H->sdpTicketUrl((int)($row['id'] ?? 0)), ['style' => 'font-weight:700;color:var(--teal);font-family:monospace;']) ?></td>
							<td style="padding:10px 12px;"><?= h((string)($row['assunto_titulo'] ?? '')) ?></td>
							<td style="padding:10px 12px;"><?= h((string)($row['situacao_label'] ?? '')) ?></td>
							<td style="padding:10px 12px;"><?= h((string)($row['tecnico_short'] ?? '—')) ?></td>
							<td style="padding:10px 12px;"><?= h((string)($row['cliente_short'] ?? '—')) ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<div style="margin-top:10px;"><?= $this->Html->link(__('Ver fila técnica completa →'), $uFila, ['style' => 'color:var(--teal);font-weight:600;font-size:13px;']) ?></div>
	</div>
	<?php endif; ?>

	<div class="card">
		<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
			<div class="sec-title sdp-sec-no-line" style="margin:0;border:none;">🗺 <?= h(__('Heatmap · volume por dia da semana × horário')) ?></div>
			<div style="font-size:11px;color:var(--text-muted);"><?= h(__('últimos 90 dias · seu escopo ABAC')) ?></div>
		</div>
		<div style="overflow-x:auto;">
			<table style="width:100%;border-collapse:separate;border-spacing:2px;font-size:11px;min-width:600px;">
				<thead>
					<tr>
						<th></th>
						<?php foreach ($hmHours as $h) : ?>
							<th style="padding:4px;color:var(--text-muted);font-weight:600;"><?= h((string)$h) ?>h</th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($hmDays as $day) : ?>
						<tr>
							<td style="padding:4px;color:var(--text-muted);font-weight:600;"><?= h($day) ?></td>
							<?php foreach ($hmHours as $h) : ?>
								<?php $cnt = (int)($hmRows[$day][$h] ?? 0); ?>
								<td style="<?= h($H->heatmapCellStyle($cnt, $hmMax)) ?> padding:8px;text-align:center;border-radius:3px;"><?= h((string)$cnt) ?></td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<div style="display:flex;align-items:center;gap:14px;margin-top:10px;font-size:11px;color:var(--text-muted);">
			<span><?= h(__('Intensidade:')) ?></span>
			<span><span style="display:inline-block;width:14px;height:14px;background:#C5F1D8;border-radius:2px;vertical-align:middle;"></span> 1–9</span>
			<span><span style="display:inline-block;width:14px;height:14px;background:#7DD3C0;border-radius:2px;vertical-align:middle;"></span> 10–15</span>
			<span><span style="display:inline-block;width:14px;height:14px;background:#1D9E75;border-radius:2px;vertical-align:middle;"></span> 16+</span>
		</div>
	</div>
</div>
