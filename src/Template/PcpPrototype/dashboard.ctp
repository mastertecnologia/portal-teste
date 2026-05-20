<?php
/**
 * Dashboard PCP — demo com gráficos Chart.js.
 *
 * @var \App\View\AppView $this
 * @var array<string,mixed> $pcpChart
 */
$c = (array)$pcpChart;
$op = (array)($c['opsKpi'] ?? []);
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Indústria · PCP')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">🏭 <?= h(__('Dashboard PCP')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);"><?= h(__('OEE, disponibilidade e produção · dados sintéticos (módulo em planejamento)')) ?></div>
	</div>
	<?= $this->Html->link('← ' . __('Visão geral'), ['controller' => 'PcpPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
</div>

<div class="summary-grid" style="margin-bottom:14px;">
	<div class="summary-card" style="border-left:3px solid var(--teal);"><div class="lbl"><?= h(__('OEE hoje')) ?></div><div class="val" style="color:var(--teal-dark);"><?= end($c['oee']) ?>%</div></div>
	<div class="summary-card" style="border-left:3px solid var(--blue);"><div class="lbl"><?= h(__('OPs em aberto')) ?></div><div class="val" style="color:#0C447C;"><?= (int)($op['abertas'] ?? 0) ?></div></div>
	<div class="summary-card" style="border-left:3px solid var(--amber);"><div class="lbl"><?= h(__('Em execução')) ?></div><div class="val" style="color:#8A4D02;"><?= (int)($op['em_execucao'] ?? 0) ?></div></div>
	<div class="summary-card" style="background:#F8D8DA;border-left:3px solid var(--red);"><div class="lbl"><?= h(__('Aguardando material')) ?></div><div class="val" style="color:#7A1822;"><?= (int)($op['aguardando_mat'] ?? 0) ?></div></div>
	<div class="summary-card" style="border-left:3px solid var(--teal-dark);"><div class="lbl"><?= h(__('Concluídas (30d)')) ?></div><div class="val" style="color:var(--teal-dark);"><?= (int)($op['concluidas'] ?? 0) ?></div></div>
</div>

<div class="card">
	<div class="sec-title">📈 <?= h(__('OEE diário · últimos 14 dias')) ?></div>
	<div style="position:relative;height:280px;"><canvas id="pcpOeeChart"></canvas></div>
</div>

<div class="g2">
	<div class="card">
		<div class="sec-title">🎯 <?= h(__('Componentes OEE')) ?></div>
		<div style="position:relative;height:240px;"><canvas id="pcpComponentesChart"></canvas></div>
	</div>
	<div class="card">
		<div class="sec-title">📦 <?= h(__('OPs por status')) ?></div>
		<div style="position:relative;height:240px;"><canvas id="pcpOpsChart"></canvas></div>
	</div>
</div>

<div class="alert-box alert-amber">
	<strong><?= h(__('Aviso:')) ?></strong>
	<?= h(__('estes números são simulação. Quando o módulo PCP for implementado (BOM + OPs + apontamento), o dashboard passa a calcular OEE real a partir de paradas, ciclos e refugo.')) ?>
</div>

<?php $this->start('script'); ?>
<script>
(function () {
	function render () {
		if (typeof Chart === 'undefined') { setTimeout(render, 120); return; }

		new Chart(document.getElementById('pcpOeeChart').getContext('2d'), {
			type: 'line',
			data: {
				labels: <?= json_encode($c['labels']) ?>,
				datasets: [{
					label: 'OEE',
					data: <?= json_encode($c['oee']) ?>,
					borderColor: '#1D9E75',
					backgroundColor: 'rgba(29,158,117,0.2)',
					tension: 0.3, fill: true
				}, {
					label: 'Meta',
					data: <?= json_encode(array_fill(0, count($c['labels']), 85)) ?>,
					borderColor: '#E24B4A',
					borderDash: [4, 4],
					tension: 0, fill: false, pointRadius: 0
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				scales: {y: {min: 0, max: 100, ticks: {callback: function (v) { return v + '%'; }}}}
			}
		});

		new Chart(document.getElementById('pcpComponentesChart').getContext('2d'), {
			type: 'bar',
			data: {
				labels: <?= json_encode($c['labels']) ?>,
				datasets: [
					{label: 'Disponibilidade', data: <?= json_encode($c['disp']) ?>, backgroundColor: '#378ADD'},
					{label: 'Performance', data: <?= json_encode($c['perf']) ?>, backgroundColor: '#E9A025'},
					{label: 'Qualidade', data: <?= json_encode($c['qual']) ?>, backgroundColor: '#1D9E75'}
				]
			},
			options: {
				responsive: true, maintainAspectRatio: false,
				scales: {y: {beginAtZero: true, max: 100, ticks: {callback: function (v) { return v + '%'; }}}}
			}
		});

		new Chart(document.getElementById('pcpOpsChart').getContext('2d'), {
			type: 'doughnut',
			data: {
				labels: ['Abertas', 'Em execução', 'Aguardando material', 'Concluídas (30d)'],
				datasets: [{
					data: [<?= (int)($op['abertas'] ?? 0) ?>, <?= (int)($op['em_execucao'] ?? 0) ?>, <?= (int)($op['aguardando_mat'] ?? 0) ?>, <?= (int)($op['concluidas'] ?? 0) ?>],
					backgroundColor: ['#378ADD', '#E9A025', '#E24B4A', '#1D9E75']
				}]
			},
			options: {responsive: true, maintainAspectRatio: false}
		});
	}
	render();
})();
</script>
<?php $this->end(); ?>
