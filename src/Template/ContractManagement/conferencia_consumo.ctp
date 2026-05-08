<?php
$this->assign('title', $title ?? 'Conferência de consumo');
$c = $contract;
$totals = (array)($conference['totals'] ?? []);
$rows = (array)($conference['rows'] ?? []);
$unlinked = (array)($conference['unlinked_rows'] ?? []);
$status = (string)($conference['status'] ?? '');
$fmtMoney = static function ($v) {
	return 'R$ ' . number_format((float)$v, 2, ',', '.');
};
?>
<div class="col-12 pgm-adv-page">
	<div class="pgm-adv-panel card mb-3">
		<div class="card-body">
			<h4 class="card-title mb-1"><?= h($title) ?> — <span class="text-muted"><?= h($c->code ?? '') ?></span></h4>
			<p class="small text-muted mb-3">Somente auditoria. Esta tela não gera cobrança/fatura.</p>
			<p class="small text-muted mb-3">
				Regra de franquia: ordem cronológica dos apontamentos. Os primeiros consumos da competência abatem a franquia; consumos posteriores são considerados excedentes conforme o período.
			</p>
			<div class="mb-2">
				<?= $this->Html->link('Voltar para ficha', ['action' => 'view', (int)$c->id], ['class' => 'btn btn-sm btn-default']) ?>
				<?= $this->Html->link('Serviços do contrato', ['action' => 'addServicos', (int)$c->id], ['class' => 'btn btn-sm btn-default']) ?>
			</div>
			<?= $this->Form->create(null, ['type' => 'get', 'class' => 'form-inline mb-3']) ?>
			<label class="control-label mr-2">Competência</label>
			<?= $this->Form->text('reference_month', ['value' => $referenceMonth, 'class' => 'form-control input-sm', 'placeholder' => 'YYYY-MM']) ?>
			<?= $this->Form->button('Filtrar', ['class' => 'btn btn-sm btn-primary']) ?>
			<?= $this->Form->end() ?>

			<div class="row mb-2 small">
				<div class="col-md-3"><strong>Total consumido:</strong> <?= h(number_format((float)($totals['total_consumed'] ?? 0), 4, ',', '.')) ?></div>
				<div class="col-md-3"><strong>Total abatido da franquia:</strong> <?= h(number_format((float)($totals['total_included'] ?? 0), 4, ',', '.')) ?></div>
				<div class="col-md-3"><strong>Total excedente:</strong> <?= h(number_format((float)($totals['total_overage'] ?? 0), 4, ',', '.')) ?></div>
				<div class="col-md-3"><strong>Valor excedente:</strong> <?= h($fmtMoney((float)($totals['total_overage_amount'] ?? 0))) ?></div>
			</div>
			<p class="small text-muted">Status do cálculo: <strong><?= h($status) ?></strong></p>

			<div class="table-responsive">
				<table class="table table-sm table-striped table-bordered">
					<thead>
						<tr>
							<th>Serviço</th>
							<th>Unidade</th>
							<th class="text-right">Franquia</th>
							<th class="text-right">Consumido</th>
							<th class="text-right">Abatido franquia</th>
							<th class="text-right">Excedente</th>
							<th>Período</th>
							<th class="text-right">Tarifa</th>
							<th class="text-right">Valor excedente</th>
							<th>Origem</th>
							<th>Status</th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ($rows as $r): ?>
						<tr>
							<td><?= h($r['service_name'] ?? '') ?></td>
							<td><?= h($r['unidade'] ?? '') ?></td>
							<td class="text-right"><?= h(number_format((float)($r['franquia'] ?? 0), 4, ',', '.')) ?></td>
							<td class="text-right"><?= h(number_format((float)($r['consumed'] ?? 0), 4, ',', '.')) ?></td>
							<td class="text-right"><?= h(number_format((float)($r['included_used'] ?? 0), 4, ',', '.')) ?></td>
							<td class="text-right"><?= h(number_format((float)($r['overage'] ?? 0), 4, ',', '.')) ?></td>
							<td><?= h($r['period_type'] ?? 'business') ?></td>
							<td class="text-right"><?= h($fmtMoney((float)($r['rate'] ?? 0))) ?></td>
							<td class="text-right"><?= h($fmtMoney((float)($r['overage_amount'] ?? 0))) ?></td>
							<td>
								<?php $src = (array)($r['sources'][0] ?? []); ?>
								<small>
									<?= h((string)($src['source_type'] ?? 'legacy')) ?>
									<?php if (!empty($src['source_id'])): ?> / #<?= h((string)$src['source_id']) ?><?php endif; ?>
									<?php if (!empty($src['ticket_id'])): ?><br>ticket: <?= h((string)$src['ticket_id']) ?><?php endif; ?>
								</small>
							</td>
							<td><?= h($r['status'] ?? '') ?></td>
						</tr>
					<?php endforeach; ?>
					<?php if ($rows === []): ?>
						<tr><td colspan="11" class="text-muted">Sem consumos itemizados na competência.</td></tr>
					<?php endif; ?>
					</tbody>
				</table>
			</div>

			<?php if ($unlinked !== []): ?>
			<hr>
			<h5>Sem vínculo de item / fallback legado</h5>
			<div class="table-responsive">
				<table class="table table-sm table-bordered">
					<thead>
						<tr>
							<th>Serviço</th>
							<th>Período</th>
							<th class="text-right">Consumido</th>
							<th>Origem</th>
							<th>ticket_id</th>
							<th>service_order_id</th>
							<th>Status</th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ($unlinked as $u): ?>
						<tr>
							<td><?= h($u['service_name'] ?? 'Sem vínculo') ?></td>
							<td><?= h($u['period_type'] ?? 'business') ?></td>
							<td class="text-right"><?= h(number_format((float)($u['consumed'] ?? 0), 4, ',', '.')) ?></td>
							<td><?= h($u['source_type'] ?? 'legacy') ?><?= !empty($u['source_id']) ? ' / #' . h((string)$u['source_id']) : '' ?></td>
							<td><?= h((string)($u['ticket_id'] ?? '')) ?></td>
							<td><?= h((string)($u['service_order_id'] ?? '')) ?></td>
							<td><?= h($u['status'] ?? 'fallback_legado') ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php endif; ?>
		</div>
	</div>
</div>
