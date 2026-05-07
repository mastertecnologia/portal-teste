<?php
/**
 * Relatório de SLA — filtros, KPIs e tabelas auxiliares.
 *
 * @var array<string, mixed> $slaFiltros
 * @var array<string, mixed> $slaReport
 * @var array<int, string> $slaClientesList
 * @var array<int, string> $slaTecnicosList
 * @var array<int, string> $slaQueuesList
 * @var array<int, string> $slaProblemasList
 * @var array<int, string> $slaClicontratosList
 * @var array<int, string> $slaContratosHorasList
 * @var array<int, string> $slaSitLabels
 * @var string[] $slaSchemaCols
 */
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Service Desk', ['controller' => 'Servicedesk', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Relatório de SLA', [], ['class' => 'breadcrumb-item active']);

$f = $slaFiltros ?? [];
$r = $slaReport ?? [];
?>
<?= $this->Html->css('dist/css/dashboard-erp.css') ?>

<div class="col-12 p-0">
	<div class="dash-erp dash-erp-relatorios">
		<div class="dash-erp-header">
			<div>
				<h2 class="dash-erp-title">Relatório de SLA</h2>
				<p class="dash-erp-subtitle">
					Tickets filtrados pela <strong>data de abertura</strong> (criado no período). Escopo ABAC e empresa da sessão.
					Consumo de horas soma lançamentos em <strong>Horas cadastradas</strong> cuja <strong>data</strong> está no período e pertence aos tickets do conjunto (máx. 6000 tickets).
				</p>
			</div>
			<div class="d-flex flex-wrap align-items-center pgm-gap-8">
				<a class="btn btn-outline-secondary" href="<?= $this->Url->build(['controller' => 'Servicedesk', 'action' => 'operacional']) ?>" data-turbo="false">Painel operacional</a>
				<button type="button" class="btn btn-outline-secondary" onclick="window.print()">Imprimir</button>
			</div>
		</div>

		<div class="card card-body mb-3" style="background:var(--erp-card,#1a1e28);border:1px solid var(--erp-border,rgba(255,255,255,.10));">
			<h3 class="h6 text-uppercase text-muted mb-3">Filtros</h3>
			<form method="get" action="<?= h($this->Url->build(['controller' => 'Servicedesk', 'action' => 'slaRelatorio'])) ?>" class="row" data-turbo="false">
				<div class="col-md-3 mb-2">
					<label class="small text-muted">Cliente</label>
					<select name="idcliente" class="form-control form-control-sm">
						<option value="">—</option>
						<?php foreach (($slaClientesList ?? []) as $cid => $cn) : ?>
							<option value="<?= (int)$cid ?>" <?= (isset($f['idcliente']) && (int)$f['idcliente'] === (int)$cid) ? 'selected' : '' ?>><?= h($cn) ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="col-md-3 mb-2">
					<label class="small text-muted">Contrato (cadastro comercial)</label>
					<select name="idclicontrato" class="form-control form-control-sm">
						<option value="">—</option>
						<?php foreach (($slaClicontratosList ?? []) as $cid => $label) : ?>
							<option value="<?= (int)$cid ?>" <?= (isset($f['idclicontrato']) && (int)$f['idclicontrato'] === (int)$cid) ? 'selected' : '' ?>><?= h($label) ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="col-md-3 mb-2">
					<label class="small text-muted">Contrato horas (Service Desk)</label>
					<select name="id_contrato_horas" class="form-control form-control-sm">
						<option value="">—</option>
						<?php foreach (($slaContratosHorasList ?? []) as $cid => $label) : ?>
							<option value="<?= (int)$cid ?>" <?= (isset($f['id_contrato_horas']) && (int)$f['id_contrato_horas'] === (int)$cid) ? 'selected' : '' ?>><?= h($label) ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<?php if (in_array('contract_id', $slaSchemaCols ?? [], true)) : ?>
				<div class="col-md-3 mb-2">
					<label class="small text-muted">Contract ID (ticket avançado)</label>
					<input type="number" name="ticket_contract_id" class="form-control form-control-sm" min="1" value="<?= isset($f['ticket_contract_id']) && $f['ticket_contract_id'] ? (int)$f['ticket_contract_id'] : '' ?>" placeholder="ID contrato no ticket">
				</div>
				<?php endif; ?>
				<div class="col-md-2 mb-2">
					<label class="small text-muted">Ticket #</label>
					<input type="number" name="idticket" class="form-control form-control-sm" min="1" value="<?= isset($f['idticket']) && $f['idticket'] ? (int)$f['idticket'] : '' ?>">
				</div>
				<div class="col-md-2 mb-2">
					<label class="small text-muted">Fila</label>
					<select name="queue_id" class="form-control form-control-sm">
						<option value="">—</option>
						<?php foreach (($slaQueuesList ?? []) as $qid => $qn) : ?>
							<option value="<?= (int)$qid ?>" <?= (isset($f['queue_id']) && (int)$f['queue_id'] === (int)$qid) ? 'selected' : '' ?>><?= h($qn) ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="col-md-3 mb-2">
					<label class="small text-muted">Técnico</label>
					<select name="idtecnico" class="form-control form-control-sm">
						<option value="">—</option>
						<?php foreach (($slaTecnicosList ?? []) as $tid => $tn) : ?>
							<option value="<?= (int)$tid ?>" <?= (isset($f['idtecnico']) && (int)$f['idtecnico'] === (int)$tid) ? 'selected' : '' ?>><?= h($tn) ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="col-md-3 mb-2">
					<label class="small text-muted">Problema</label>
					<select name="problema_id" class="form-control form-control-sm">
						<option value="">—</option>
						<?php foreach (($slaProblemasList ?? []) as $pid => $pl) : ?>
							<option value="<?= (int)$pid ?>" <?= (isset($f['problema_id']) && (int)$f['problema_id'] === (int)$pid) ? 'selected' : '' ?>><?= h($pl) ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="col-md-2 mb-2">
					<label class="small text-muted">Mês (opcional)</label>
					<input type="month" name="mes_ref" class="form-control form-control-sm" value="<?= h((string)($f['mes_ref'] ?? '')) ?>">
				</div>
				<div class="col-md-2 mb-2">
					<label class="small text-muted">Período — início</label>
					<input type="date" name="periodo_ini" class="form-control form-control-sm" value="<?= h(substr((string)($f['created_start'] ?? ''), 0, 10)) ?>">
				</div>
				<div class="col-md-2 mb-2">
					<label class="small text-muted">Período — fim</label>
					<input type="date" name="periodo_fim" class="form-control form-control-sm" value="<?= h(substr((string)($f['created_end'] ?? ''), 0, 10)) ?>">
				</div>
				<div class="col-md-12 mb-2">
					<label class="small text-muted d-block">Status (situação)</label>
					<div class="d-flex flex-wrap gap-2">
						<?php
						$selSit = isset($f['situacao_in']) && is_array($f['situacao_in']) ? array_map('intval', $f['situacao_in']) : [];
						foreach (($slaSitLabels ?? []) as $sid => $slabel) :
							$chk = in_array((int)$sid, $selSit, true);
							?>
							<label class="mb-0 small"><input type="checkbox" name="situacao[]" value="<?= (int)$sid ?>" <?= $chk ? 'checked' : '' ?>> <?= h($slabel) ?></label>
						<?php endforeach; ?>
					</div>
				</div>
				<div class="col-md-12 mb-2 d-flex flex-wrap align-items-center gap-3">
					<label class="mb-0 small"><input type="checkbox" name="sla_estourado" value="1" <?= !empty($f['sla_estourado_only']) ? 'checked' : '' ?>> Somente SLA estourado (<code>sla_status = violado</code>)</label>
					<label class="mb-0 small"><input type="checkbox" name="sla_pausado" value="1" <?= !empty($f['sla_pausado_only']) ? 'checked' : '' ?>> Somente SLA pausado</label>
				</div>
				<div class="col-12">
					<button type="submit" class="btn btn-pgm btn-sm">Aplicar</button>
				</div>
			</form>
			<p class="small text-muted mt-2 mb-0">Período atual: <strong><?= h((string)($f['periodo_label'] ?? '')) ?></strong><?= !empty($f['periodo_padrao']) ? ' (padrão: últimos 60 dias)' : '' ?></p>
			<?php if (!empty($r['horas_warn'])) : ?>
				<p class="small text-warning mb-0 mt-1"><?= h($r['horas_warn']) ?></p>
			<?php endif; ?>
		</div>

		<p class="rel-section-label">Indicadores</p>
		<div class="dash-erp-kpis" aria-label="KPIs SLA">
			<div class="dash-erp-kpi">
				<div class="dash-erp-kpi-icon" aria-hidden="true"><i class="fas fa-ticket-alt"></i></div>
				<div class="dash-erp-kpi-meta">
					<p class="dash-erp-kpi-label">Tickets no período</p>
					<p class="dash-erp-kpi-value"><?= (int)($r['total_tickets'] ?? 0) ?></p>
				</div>
			</div>
			<div class="dash-erp-kpi">
				<div class="dash-erp-kpi-icon" aria-hidden="true"><i class="fas fa-check-circle"></i></div>
				<div class="dash-erp-kpi-meta">
					<p class="dash-erp-kpi-label">SLA cumprido (com status)</p>
					<p class="dash-erp-kpi-value"><?= (int)($r['sla_cumprido'] ?? 0) ?></p>
				</div>
			</div>
			<div class="dash-erp-kpi">
				<div class="dash-erp-kpi-icon" aria-hidden="true"><i class="fas fa-exclamation-triangle"></i></div>
				<div class="dash-erp-kpi-meta">
					<p class="dash-erp-kpi-label">SLA estourado</p>
					<p class="dash-erp-kpi-value"><?= (int)($r['sla_estourado'] ?? 0) ?></p>
				</div>
			</div>
			<div class="dash-erp-kpi">
				<div class="dash-erp-kpi-icon" aria-hidden="true"><i class="fas fa-reply"></i></div>
				<div class="dash-erp-kpi-meta">
					<p class="dash-erp-kpi-label">Tempo médio 1ª resposta</p>
					<p class="dash-erp-kpi-value"><?= isset($r['avg_resposta_minutes']) && $r['avg_resposta_minutes'] !== null ? h(number_format((float)$r['avg_resposta_minutes'], 1, ',', '.')) . ' min' : '—' ?></p>
				</div>
			</div>
			<div class="dash-erp-kpi">
				<div class="dash-erp-kpi-icon" aria-hidden="true"><i class="fas fa-flag-checkered"></i></div>
				<div class="dash-erp-kpi-meta">
					<p class="dash-erp-kpi-label">Tempo médio resolução</p>
					<p class="dash-erp-kpi-value"><?= isset($r['avg_resolucao_minutes']) && $r['avg_resolucao_minutes'] !== null ? h(number_format((float)$r['avg_resolucao_minutes'], 1, ',', '.')) . ' min' : '—' ?></p>
				</div>
			</div>
			<div class="dash-erp-kpi">
				<div class="dash-erp-kpi-icon" aria-hidden="true"><i class="fas fa-pause-circle"></i></div>
				<div class="dash-erp-kpi-meta">
					<p class="dash-erp-kpi-label">Tempo médio pausado</p>
					<p class="dash-erp-kpi-value"><?= isset($r['avg_pausado_minutes']) && $r['avg_pausado_minutes'] !== null ? h(number_format((float)$r['avg_pausado_minutes'], 1, ',', '.')) . ' min' : '—' ?></p>
				</div>
			</div>
		</div>

		<?php if (empty($r['has_sla_status_column'])) : ?>
			<p class="alert alert-warning small">Coluna <code>sla_status</code> ausente no banco: KPIs de cumprimento/estouro usam apenas o que puder ser inferido; considere migrar tickets enterprise.</p>
		<?php endif; ?>

		<div class="row mt-3">
			<div class="col-lg-6 mb-3">
				<p class="rel-section-label">Tempo por fila</p>
				<div class="table-responsive">
					<table class="table table-sm table-dark">
						<thead><tr><th>Fila</th><th>Tickets</th><th>Média resolução (min)</th><th>Média atend. (h)</th></tr></thead>
						<tbody>
							<?php foreach (($r['by_queue'] ?? []) as $row) : ?>
								<tr>
									<td><?= h($row['queue_name'] ?? '') ?></td>
									<td><?= (int)($row['ticket_count'] ?? 0) ?></td>
									<td><?= isset($row['avg_resolucao_minutes']) && $row['avg_resolucao_minutes'] !== null ? h((string)$row['avg_resolucao_minutes']) : '—' ?></td>
									<td><?= isset($row['avg_atendimento_hours']) && $row['avg_atendimento_hours'] !== null ? h((string)$row['avg_atendimento_hours']) : '—' ?></td>
								</tr>
							<?php endforeach; ?>
							<?php if (empty($r['by_queue'])) : ?>
								<tr><td colspan="4" class="text-muted">Sem dados ou filas não configuradas.</td></tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
			<div class="col-lg-6 mb-3">
				<p class="rel-section-label">Tempo por técnico</p>
				<div class="table-responsive">
					<table class="table table-sm table-dark">
						<thead><tr><th>Técnico</th><th>Tickets</th><th>Média resolução (min)</th><th>Média atend. (h)</th></tr></thead>
						<tbody>
							<?php foreach (($r['by_technician'] ?? []) as $row) : ?>
								<tr>
									<td><?= h($row['user_name'] ?? '') ?></td>
									<td><?= (int)($row['ticket_count'] ?? 0) ?></td>
									<td><?= isset($row['avg_resolucao_minutes']) && $row['avg_resolucao_minutes'] !== null ? h((string)$row['avg_resolucao_minutes']) : '—' ?></td>
									<td><?= isset($row['avg_atendimento_hours']) && $row['avg_atendimento_hours'] !== null ? h((string)$row['avg_atendimento_hours']) : '—' ?></td>
								</tr>
							<?php endforeach; ?>
							<?php if (empty($r['by_technician'])) : ?>
								<tr><td colspan="4" class="text-muted">Sem dados ou técnico não vinculado.</td></tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>

		<p class="rel-section-label">Consumo de horas por contrato (horas / cliente)</p>
		<div class="table-responsive mb-3">
			<table class="table table-sm table-dark">
				<thead><tr><th>Contrato horas / rótulo</th><th>Cliente</th><th>Minutos</th><th>Horas</th></tr></thead>
				<tbody>
					<?php foreach (($r['contract_hours_consumption'] ?? []) as $row) : ?>
						<tr>
							<td><?= h($row['label'] ?? '') ?></td>
							<td><?= (int)($row['cliente_id'] ?? 0) ?></td>
							<td><?= (int)($row['minutes'] ?? 0) ?></td>
							<td><?= h(number_format((float)($row['hours'] ?? 0), 2, ',', '.')) ?></td>
						</tr>
					<?php endforeach; ?>
					<?php if (empty($r['contract_hours_consumption'])) : ?>
						<tr><td colspan="4" class="text-muted">Sem lançamentos de horas no período para o conjunto filtrado.</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

		<p class="rel-section-label">Amostra de tickets (últimos)</p>
		<div class="table-responsive">
			<table class="table table-sm table-dark">
				<thead><tr><th>#</th><th>Cliente</th><th>Status</th><th>SLA</th><th>Fila</th><th>Técnico</th><th>Aberto</th><th>Limite resol.</th></tr></thead>
				<tbody>
					<?php foreach (($r['sample_tickets'] ?? []) as $row) : ?>
						<tr>
							<td><?= $this->Html->link((string)(int)$row['id'], ['controller' => 'Servicedesk', 'action' => 'edit', (int)$row['id'], '?' => ['sd' => '1']], ['data-turbo' => 'false']) ?></td>
							<td><?= h((string)($row['cliente'] ?? '')) ?></td>
							<td><?= h((string)($row['situacao'] ?? '')) ?></td>
							<td><?= h((string)($row['sla_status'] ?? '')) ?></td>
							<td><?= h((string)($row['queue'] ?? '')) ?></td>
							<td><?= h((string)($row['tecnico'] ?? '')) ?></td>
							<td><?= h((string)($row['created'] ?? '')) ?></td>
							<td><?= h((string)($row['data_limite_resolucao'] ?? '')) ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
