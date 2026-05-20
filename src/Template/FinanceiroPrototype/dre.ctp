<?php
/**
 * DRE Gerencial — mockup pg-dre, com gráfico Chart.js (últimos 6 meses).
 *
 * @var \App\View\AppView $this
 * @var array{labels:array,receita:array,despesa:array,resultado:array} $dreData
 */
$H = $this->ErpPrototype;
$totalReceita = array_sum($dreData['receita']);
$totalDespesa = array_sum($dreData['despesa']);
$totalResultado = $totalReceita - $totalDespesa;
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Financeiro')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">📊 <?= h(__('DRE Gerencial')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);"><?= h(__('Receita vs. despesa nos últimos 6 meses (faturas recebidas × lançamentos pagos)')) ?></div>
	</div>
	<?= $this->Html->link('← ' . __('Voltar'), ['controller' => 'FinanceiroPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
</div>

<div class="summary-grid" style="margin-bottom:14px;">
	<div class="summary-card" style="border-left:3px solid var(--teal);"><div class="lbl"><?= h(__('Receita 6m')) ?></div><div class="val" style="color:var(--teal-dark);"><?= h($H->brl($totalReceita)) ?></div></div>
	<div class="summary-card" style="border-left:3px solid var(--red);"><div class="lbl"><?= h(__('Despesa 6m')) ?></div><div class="val" style="color:#7A1822;"><?= h($H->brl($totalDespesa)) ?></div></div>
	<div class="summary-card" style="border-left:3px solid <?= $totalResultado >= 0 ? 'var(--teal)' : 'var(--red)' ?>;background:<?= $totalResultado >= 0 ? '#E1F5EE' : '#F8D8DA' ?>;">
		<div class="lbl"><?= h(__('Resultado 6m')) ?></div>
		<div class="val" style="color:<?= $totalResultado >= 0 ? 'var(--teal-dark)' : '#7A1822' ?>;"><?= h($H->brl($totalResultado)) ?></div>
	</div>
</div>

<div class="card">
	<div class="sec-title"><?= h(__('Evolução mensal')) ?></div>
	<div style="position:relative;height:360px;">
		<canvas id="dreChart"></canvas>
	</div>
</div>

<div class="card" style="padding:0;overflow:hidden;">
	<div style="overflow-x:auto;">
		<table class="tbl" style="margin:0;">
			<thead>
				<tr>
					<th><?= h(__('Mês')) ?></th>
					<th class="r"><?= h(__('Receita')) ?></th>
					<th class="r"><?= h(__('Despesa')) ?></th>
					<th class="r"><?= h(__('Resultado')) ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($dreData['labels'] as $i => $lbl) :
					$r = (float)$dreData['receita'][$i];
					$d = (float)$dreData['despesa'][$i];
					$x = $r - $d;
				?>
					<tr>
						<td><strong><?= h((string)$lbl) ?></strong></td>
						<td class="r" style="color:var(--teal-dark);"><?= h($H->brl($r)) ?></td>
						<td class="r" style="color:#7A1822;"><?= h($H->brl($d)) ?></td>
						<td class="r" style="font-weight:700;color:<?= $x >= 0 ? 'var(--teal-dark)' : '#7A1822' ?>;"><?= h($H->brl($x)) ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>

<?php $this->start('script'); ?>
<script>
(function () {
	function render () {
		if (typeof Chart === 'undefined') {
			setTimeout(render, 120);
			return;
		}
		var ctx = document.getElementById('dreChart');
		if (!ctx) return;
		new Chart(ctx.getContext('2d'), {
			type: 'bar',
			data: {
				labels: <?= json_encode($dreData['labels']) ?>,
				datasets: [
					{label: <?= json_encode(__('Receita')) ?>, data: <?= json_encode($dreData['receita']) ?>, backgroundColor: '#1D9E75'},
					{label: <?= json_encode(__('Despesa')) ?>, data: <?= json_encode($dreData['despesa']) ?>, backgroundColor: '#E24B4A'},
					{label: <?= json_encode(__('Resultado')) ?>, data: <?= json_encode($dreData['resultado']) ?>, backgroundColor: '#378ADD', type: 'line', borderColor: '#0C447C', tension: 0.25, fill: false}
				]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				scales: {y: {beginAtZero: true, ticks: {callback: function (v) { return 'R$ ' + Number(v).toLocaleString('pt-BR'); }}}}
			}
		});
	}
	render();
})();
</script>
<?php $this->end(); ?>
