<?php
/**
 * Linha da tabela da fila técnica (mockup).
 *
 * @var \App\View\AppView $this
 * @var array<string,mixed> $row
 * @var array<string,mixed> $assignment
 */
$pill = (array)($row['situacao_pill'] ?? []);
$prio = (array)($row['prioridade_meta'] ?? []);
$slaViol = !empty($row['sla_violado']);
$semTec = !empty($row['sem_tecnico']);
$ticketId = (int)($row['id'] ?? 0);
$H = $this->ServicedeskPrototype;
$ticketUrl = $H->sdpTicketUrl($ticketId);
$ticketClassicUrl = $this->Url->build(['controller' => 'Tickets', 'action' => 'view', $ticketId]);
$assignment = $assignment ?? [];
$canAssign = !empty($assignment['can_assign']);
$queuesRel = !empty($assignment['queues_relacional']);
$queues = (array)($assignment['queues'] ?? []);
$tecnicos = (array)($assignment['tecnicos'] ?? []);
$queueId = (int)($row['queue_id'] ?? 0);
$tecId = (int)($row['tecnico_id'] ?? 0);
$nivelLabel = (string)($row['nivel'] ?? '—');
$prioCur = is_numeric($row['prioridade'] ?? null) ? (int)$row['prioridade'] : 0;
if ($prioCur < 1 || $prioCur > 4) {
	$prioCur = 1;
}
$prioOptions = [
	1 => __('Baixo'),
	2 => __('Médio'),
	3 => __('Alto'),
	4 => __('Crítico'),
];
$sitCur = (int)($row['situacao'] ?? 0);
$sitInconsistente = !empty($row['situacao_inconsistente']);
$sitDb = (int)($row['situacao_db'] ?? $sitCur);
$statusOptions = [];
if (defined('C_TicketSituacaoPendente')) {
	$statusOptions[(int)C_TicketSituacaoPendente] = 'Aberto';
}
if (defined('C_TicketSituacaoEmandamento')) {
	$statusOptions[(int)C_TicketSituacaoEmandamento] = 'Em execução';
}
if (defined('C_TicketSituacaoResolvido')) {
	$statusOptions[(int)C_TicketSituacaoResolvido] = 'Resolvido';
}
if (defined('C_TicketSituacaoFechado')) {
	$statusOptions[(int)C_TicketSituacaoFechado] = 'Fechado';
}
?>
<tr class="sdp-fila-row" style="border-bottom:1px solid var(--border-light);vertical-align:middle;background:#fff;" data-sdp-ticket-id="<?= $ticketId ?>" data-sdp-nivel="<?= h($nivelLabel) ?>" data-pgm-row-href="<?= h($ticketUrl) ?>" tabindex="0"<?= $sitInconsistente ? ' data-sdp-situacao-inconsistente="1"' : '' ?>>
	<td style="padding:14px 12px;">
		<?= $this->Html->link('#' . $ticketId, $ticketUrl, [
			'style' => 'color:var(--teal);font-weight:700;font-family:monospace;text-decoration:none;',
		]) ?>
	</td>
	<td style="padding:14px 12px;color:var(--text-muted);"><?= h((string)($row['autor_short'] ?? '—')) ?></td>
	<td style="padding:14px 12px;color:var(--text-muted);font-family:monospace;font-size:11px;"><?= h((string)($row['created_fmt'] ?? '—')) ?></td>
	<td style="padding:14px 12px;">
		<div style="font-weight:600;"><?= h((string)($row['assunto_titulo'] ?? '')) ?></div>
		<div style="font-size:11px;color:var(--text-muted);"><?= h((string)($row['excerpt'] ?? '')) ?></div>
	</td>
	<td style="padding:14px 12px;">
		<select class="sdp-select sdp-fila-prioridade" style="width:90px;border-color:<?= h((string)($prio['border'] ?? 'var(--border)')) ?>;background:<?= h((string)($prio['bg'] ?? '#fff')) ?>;" data-sdp-field="prioridade" title="<?= h(__('Alterar prioridade')) ?>">
			<?php foreach ($prioOptions as $pv => $pl) : ?>
				<option value="<?= (int)$pv ?>" <?= $prioCur === (int)$pv ? 'selected' : '' ?>><?= h($pl) ?></option>
			<?php endforeach; ?>
		</select>
	</td>
	<td style="padding:14px 12px;min-width:240px;">
		<div style="display:inline-block;background:<?= h((string)($pill['bg'] ?? '#7DD3C0')) ?>;color:<?= h((string)($pill['color'] ?? '#0a3d2c')) ?>;padding:3px 14px;border-radius:14px;font-size:11px;font-weight:700;margin-bottom:6px;" class="sdp-fila-status-pill"><?= h((string)($pill['label'] ?? '')) ?></div>
		<select class="sdp-select sdp-fila-status" style="width:100%;margin-bottom:8px;" data-sdp-field="status" title="<?= h(__('Alterar status')) ?>">
			<?php foreach ($statusOptions as $sv => $sl) : ?>
				<option value="<?= (int)$sv ?>" data-status-api="<?= h($sl) ?>" <?= $sitCur === (int)$sv ? 'selected' : '' ?>><?= h($sl) ?></option>
			<?php endforeach; ?>
		</select>
		<?php if ($sitInconsistente) : ?>
			<div style="font-size:10px;color:#8A4D02;font-weight:600;margin-bottom:4px;" title="<?= h(__('Valor legado em tickets.situacao não reflete encerramento real (sem data_resolucao).')) ?>">
				<?= h(sprintf(__('BD: situacao=%d (ajuste recomendado)'), $sitDb)) ?>
			</div>
		<?php endif; ?>
		<?php if ($semTec) : ?>
			<div style="display:inline-block;background:#FAEEDA;color:#8A4D02;padding:3px 8px;border-radius:6px;font-size:10px;font-weight:700;">⏰ <?= h(__('Aguardando atribuição')) ?></div>
		<?php elseif ($slaViol) : ?>
			<div style="display:inline-block;background:#FEE2E2;color:#7A1822;padding:3px 8px;border-radius:6px;font-size:10px;font-weight:700;margin-bottom:4px;"><?= h(__('EM EXECUÇÃO (SLA)')) ?></div>
			<div style="font-size:11px;color:#7A1822;font-weight:600;"><?= h(__('SLA estourado')) ?><?php if (!empty($row['sla_limite_fmt']) && $row['sla_limite_fmt'] !== '—') : ?> · <?= h(__('limite')) ?> <?= h((string)$row['sla_limite_fmt']) ?><?php endif; ?></div>
		<?php else : ?>
			<div style="display:inline-block;background:#DCFCE7;color:var(--teal-dark);padding:3px 8px;border-radius:6px;font-size:10px;font-weight:700;margin-bottom:4px;"><?= h(__('SLA ok')) ?></div>
		<?php endif; ?>
	</td>
	<td style="padding:14px 12px;">
		<?php if ($canAssign && $queuesRel && $queues !== []) : ?>
			<select class="sdp-select sdp-fila-queue" style="width:140px;" data-sdp-field="queue" title="<?= h(__('Escalonar fila')) ?>">
				<option value=""><?= h(__('— Fila —')) ?></option>
				<?php foreach ($queues as $q) : ?>
					<?php $qid = (int)($q['id'] ?? 0); ?>
					<option value="<?= $qid ?>" data-nivel="<?= h((string)($q['nivel'] ?? '')) ?>" <?= $queueId === $qid ? 'selected' : '' ?>><?= h((string)($q['name'] ?? '')) ?></option>
				<?php endforeach; ?>
			</select>
		<?php else : ?>
			<select class="sdp-select" style="width:140px;" disabled><option selected><?= h((string)($row['fila_label'] ?? '—')) ?></option></select>
		<?php endif; ?>
	</td>
	<td style="padding:14px 12px;color:var(--text-muted);font-weight:600;" class="sdp-fila-nivel-cell"><?= h($nivelLabel) ?></td>
	<td style="padding:14px 12px;">
		<?php if ($canAssign && $tecnicos !== []) : ?>
			<select class="sdp-select sdp-fila-tecnico" style="width:160px;<?= $semTec ? 'color:#8A4D02;font-style:italic;' : '' ?>" data-sdp-field="tecnico" title="<?= h(__('Atribuir técnico')) ?>">
				<option value=""><?= h(__('Sem atribuição')) ?></option>
				<?php foreach ($tecnicos as $tec) : ?>
					<?php
					$uid = (int)($tec['id'] ?? 0);
					$qids = (array)($tec['queue_ids'] ?? []);
					?>
					<option value="<?= $uid ?>" data-queue-ids="<?= h(implode(',', $qids)) ?>" <?= $tecId === $uid ? 'selected' : '' ?>><?= h((string)($tec['name'] ?? '')) ?></option>
				<?php endforeach; ?>
			</select>
		<?php else : ?>
			<span style="color:<?= $semTec ? '#8A4D02;font-style:italic;' : 'var(--text-muted);' ?>"><?= h($semTec ? __('Sem atribuição') : (string)($row['tecnico_short'] ?? '—')) ?></span>
		<?php endif; ?>
	</td>
	<td style="padding:14px 12px;color:var(--text-muted);font-family:monospace;font-size:11px;" class="sdp-fila-tempo-cell" title="<?= h(__('Aberto: tempo desde a última alteração. Encerrado: duração total do chamado.')) ?>"><?= h((string)($row['tempo'] ?? '—')) ?></td>
	<td style="padding:14px 12px;color:var(--text-muted);"><?= h((string)($row['cliente_short'] ?? '—')) ?></td>
	<td style="padding:14px 12px;text-align:right;white-space:nowrap;">
		<div class="sdp-fila-actions-wrap" style="position:relative;display:inline-block;">
			<button type="button" class="btn btn-ghost btn-xs sdp-fila-actions-btn" style="font-size:11px;" aria-haspopup="true" aria-expanded="false">⋮ <?= h(__('Ações')) ?> ▾</button>
			<div class="sdp-fila-actions-menu" style="display:none;position:absolute;right:0;top:100%;z-index:40;min-width:200px;margin-top:4px;background:#fff;border:1px solid var(--border);border-radius:var(--radius);box-shadow:0 8px 24px rgba(0,0,0,.12);padding:6px 0;">
				<?= $this->Html->link(__('Ver ticket (protótipo)'), $ticketUrl, ['style' => 'display:block;padding:8px 14px;font-size:12px;color:var(--text);text-decoration:none;']) ?>
				<?= $this->Html->link(__('Abrir no Service Desk'), $ticketClassicUrl, ['style' => 'display:block;padding:8px 14px;font-size:12px;color:var(--text);text-decoration:none;', 'target' => '_blank', 'rel' => 'noopener']) ?>
				<?= $this->Html->link(__('Imprimir'), ['controller' => 'Tickets', 'action' => 'imprimir', $ticketId], ['style' => 'display:block;padding:8px 14px;font-size:12px;color:var(--text);text-decoration:none;', 'target' => '_blank', 'rel' => 'noopener']) ?>
			</div>
		</div>
		<span class="sdp-fila-save-msg" style="display:none;font-size:10px;margin-left:6px;"></span>
	</td>
</tr>
