<?php
/**
 * Fluxo de Caixa — mockup pg-fluxo-caixa, com gráfico Chart.js (30d).
 *
 * @var \App\View\AppView $this
 * @var array{labels:array,entradas:array,saidas:array,saldo:array} $fluxoData
 */
$H = $this->ErpPrototype;
$totalEntradas = array_sum($fluxoData['entradas']);
$totalSaidas = array_sum($fluxoData['saidas']);
$saldoFinal = end($fluxoData['saldo']) ?: 0;
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Bancos')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">📈 <?= h(__('Fluxo de Caixa')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);"><?= h(__('Entradas × saídas dos últimos 30 dias e saldo acumulado')) ?></div>
	</div>
	<?= $this->Html->link('← ' . __('Voltar'), ['controller' => 'BancosPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
</div>

<div class="summary-grid" style="margin-bottom:14px;">
	<div class="summary-card" style="border-left:3px solid var(--teal);"><div class="lbl"><?= h(__('Entradas 30d')) ?></div><div class="val" style="color:var(--teal-dark);"><?= h($H->brl($totalEntradas)) ?></div></div>
	<div class="summary-card" style="border-left:3px solid var(--red);"><div class="lbl"><?= h(__('Saídas 30d')) ?></div><div class="val" style="color:#7A1822;"><?= h($H->brl($totalSaidas)) ?></div></div>
	<div class="summary-card" style="border-left:3px solid <?= (float)$saldoFinal >= 0 ? 'var(--teal)' : 'var(--red)' ?>;background:<?= (float)$saldoFinal >= 0 ? '#E1F5EE' : '#F8D8DA' ?>;">
		<div class="lbl"><?= h(__('Saldo acumulado')) ?></div>
		<div class="val" style="color:<?= (float)$saldoFinal >= 0 ? 'var(--teal-dark)' : '#7A1822' ?>;"><?= h($H->brl((float)$saldoFinal)) ?></div>
	</div>
</div>

<div class="card">
	<div class="sec-title"><?= h(__('Curva diária')) ?></div>
	<div style="position:relative;height:360px;">
		<canvas id="fluxoChart"></canvas>
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
		var ctx = document.getElementById('fluxoChart');
		if (!ctx) return;
		new Chart(ctx.getContext('2d'), {
			type: 'line',
			data: {
				labels: <?= json_encode($fluxoData['labels']) ?>,
				datasets: [
					{label: <?= json_encode(__('Entradas')) ?>, data: <?= json_encode($fluxoData['entradas']) ?>, borderColor: '#1D9E75', backgroundColor: 'rgba(29,158,117,0.15)', tension: 0.3, fill: true},
					{label: <?= json_encode(__('Saídas')) ?>, data: <?= json_encode($fluxoData['saidas']) ?>, borderColor: '#E24B4A', backgroundColor: 'rgba(226,75,74,0.15)', tension: 0.3, fill: true},
					{label: <?= json_encode(__('Saldo acumulado')) ?>, data: <?= json_encode($fluxoData['saldo']) ?>, borderColor: '#378ADD', borderDash: [5,4], tension: 0.25, fill: false}
				]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				interaction: {mode: 'index', intersect: false},
				scales: {y: {ticks: {callback: function (v) { return 'R$ ' + Number(v).toLocaleString('pt-BR'); }}}}
			}
		});
	}
	render();
})();
</script>
<?php $this->end(); ?>
