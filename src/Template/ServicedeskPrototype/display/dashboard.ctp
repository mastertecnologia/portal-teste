<?php
/**
 * @var \App\View\AppView $this
 * @var string $title
 * @var array<string,mixed> $proto
 */
$this->assign('title', $title);
$this->Breadcrumbs->add(__('Gestão de incidentes'), ['controller' => 'Servicedesk', 'action' => 'index']);
$this->Breadcrumbs->add(__('SD protótipo'), ['controller' => 'ServicedeskPrototype', 'action' => 'index'], ['class' => 'breadcrumb-item active']);
$volDiario = (array)($proto['vol_diario_14'] ?? []);
?>
<div class="row">
	<div class="col-12 pgm-sd-prototype">
		<?php
		$uFila = $this->Url->build(['controller' => 'ServicedeskPrototype', 'action' => 'fila']);
		$uClientes = $this->Url->build(['controller' => 'Clientes', 'action' => 'index']);
		echo $this->element('ServicedeskPrototype/dashboard_markup', ['uFila' => $uFila, 'uClientes' => $uClientes, 'proto' => $proto]);
		?>

		<?php if ($volDiario !== []) :
			$evolLabels = [];
			$evolValores = [];
			foreach ($volDiario as $entry) {
				$label = (string)($entry['label'] ?? $entry['data'] ?? '');
				if ($label === '' && isset($entry['data'])) {
					$ts = strtotime((string)$entry['data']);
					if ($ts !== false) {
						$label = date('d/m', $ts);
					}
				}
				$evolLabels[] = $label;
				$evolValores[] = (int)($entry['total'] ?? $entry['valor'] ?? $entry['count'] ?? 0);
			}
		?>
			<div class="sdp-card" style="margin-top:14px;padding:18px;background:#fff;border:1px solid #e5e4e0;border-radius:12px;">
				<div style="font-size:10px;font-weight:600;color:#6b6a65;text-transform:uppercase;letter-spacing:.7px;margin-bottom:10px;">
					📈 <?= h(__('Volume de tickets · últimos 14 dias')) ?>
				</div>
				<div style="position:relative;height:240px;">
					<canvas id="sdpEvolChart"></canvas>
				</div>
			</div>

			<?php $this->start('script'); ?>
			<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" defer></script>
			<script>
			(function () {
				function render () {
					if (typeof Chart === 'undefined') {
						setTimeout(render, 120);
						return;
					}
					var ctx = document.getElementById('sdpEvolChart');
					if (!ctx) return;
					new Chart(ctx.getContext('2d'), {
						type: 'line',
						data: {
							labels: <?= json_encode($evolLabels) ?>,
							datasets: [{
								label: <?= json_encode(__('Tickets abertos')) ?>,
								data: <?= json_encode($evolValores) ?>,
								borderColor: '#1D9E75',
								backgroundColor: 'rgba(29,158,117,0.18)',
								tension: 0.3,
								fill: true
							}]
						},
						options: {
							responsive: true,
							maintainAspectRatio: false,
							plugins: {legend: {display: false}},
							scales: {y: {beginAtZero: true, ticks: {precision: 0}}}
						}
					});
				}
				render();
			})();
			</script>
			<?php $this->end(); ?>
		<?php endif; ?>
	</div>
</div>
