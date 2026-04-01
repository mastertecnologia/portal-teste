<?php
/** @var array<string,string> $kpis */
/** @var string[] $rotulos */
$k = $kpis ?? [];
$r = $rotulos ?? [];
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<style>
		body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
		h1 { font-size: 16px; margin: 0 0 8px; }
		.meta { margin: 0 0 14px; color: #444; line-height: 1.4; }
		table { width: 100%; border-collapse: collapse; margin-top: 8px; }
		th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
		th { background: #f0f0f0; font-weight: bold; }
	</style>
</head>
<body>
	<h1>Relatório — Indicadores</h1>
	<div class="meta">
		<?php foreach ($r as $line) { ?>
			<div><?= h($line) ?></div>
		<?php } ?>
	</div>
	<table>
		<thead>
			<tr><th>Indicador</th><th>Valor</th></tr>
		</thead>
		<tbody>
			<tr><td>Tickets no período</td><td><?= h($k['tickets'] ?? '') ?></td></tr>
			<tr><td>SLA</td><td><?= h($k['sla'] ?? '') ?></td></tr>
			<tr><td>Receita no período</td><td><?= h($k['receita'] ?? '') ?></td></tr>
			<tr><td>Inadimplência (vencido em aberto)</td><td><?= h($k['inadimplencia'] ?? '') ?></td></tr>
		</tbody>
	</table>
</body>
</html>
