<?php
$this->assign('title', $title ?? 'Franquia');
$rows = $rows ?? [];
$mes = $mes ?? date('Y-m');
?>
<div class="col-12 pgm-adv-page">
	<div class="pgm-adv-panel card mb-3">
		<div class="card-body">
			<h4 class="card-title"><?= h($title) ?></h4>
			<p class="text-muted small">Horas consumidas no mês de referência (tabela <code>contract_consumptions</code>).</p>
			<form method="get" action="" class="form-inline mb-3">
				<label class="mr-2 small">Mês (AAAA-MM)</label>
				<input type="text" name="mes" class="form-control form-control-sm mr-2" value="<?= h($mes) ?>" pattern="\d{4}-\d{2}" placeholder="2026-04">
				<button type="submit" class="btn btn-sm btn-primary">Atualizar</button>
			</form>
			<div class="table-responsive">
				<table class="table table-sm table-striped mb-0">
					<thead>
						<tr>
							<th>Contrato</th>
							<th>Código</th>
							<th>Horas incluídas</th>
							<th>Consumidas (mês)</th>
							<th>Saldo</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($rows as $r): ?>
						<?php
							$c = $r['contract'];
							$inc = (float)($r['included_hours'] ?? 0);
							$con = (float)($r['consumed_hours'] ?? 0);
							$saldo = $inc - $con;
						?>
						<tr>
							<td><?= h($c->name) ?></td>
							<td><?= h($c->code) ?></td>
							<td><?= h(number_format($inc, 2, ',', '.')) ?></td>
							<td><?= h(number_format($con, 2, ',', '.')) ?></td>
							<td><?= h(number_format($saldo, 2, ',', '.')) ?></td>
						</tr>
						<?php endforeach; ?>
						<?php if (count($rows) === 0): ?>
						<tr><td colspan="5" class="text-muted">Sem contratos ou sem dados para este mês.</td></tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
			<p class="mb-0 mt-2"><?= $this->Html->link('← Contratos', ['action' => 'index'], ['class' => 'btn btn-sm btn-secondary']) ?></p>
		</div>
	</div>
</div>
