<?php
/** HTML para mPDF — OrdensservicoController::relatorioPdf / e-mail */
$nf = function ($v) {
	return number_format((float)$v, 2, ',', '.');
};
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8"/>
	<style>
		body {
			font-family: DejaVu Sans, sans-serif;
			font-size: 10pt;
			color: #111;
			font-weight: normal;
			-webkit-print-color-adjust: exact;
			print-color-adjust: exact;
		}
		h1 {
			font-size: 15pt;
			margin: 0 0 8px;
			color: #000;
			font-weight: bold;
		}
		.meta {
			font-size: 9.5pt;
			color: #222;
			margin-bottom: 12px;
			font-weight: 500;
			line-height: 1.35;
		}
		table { width: 100%; border-collapse: collapse; margin-top: 8px; background: #fff; }
		th, td {
			border: 1px solid #444;
			padding: 5px 7px;
			text-align: left;
			color: #111;
		}
		th {
			background: #d8d8d8;
			font-size: 8.5pt;
			text-transform: uppercase;
			font-weight: bold;
			color: #000;
		}
		td { font-weight: 500; }
		tr:nth-child(even) td { background: #f7f7f7; }
		.num { text-align: right; font-variant-numeric: tabular-nums; }
	</style>
</head>
<body>
	<h1><?= h($tituloRelatorio) ?></h1>
	<div class="meta">
		<?= h($nomeempresa ?? '') ?> — <?= h(date('d/m/Y H:i')) ?><br/>
		Filtros: <?= h($filtrosRotulo['situacao']) ?> | <?= h($filtrosRotulo['cliente']) ?> | <?= h($filtrosRotulo['problema']) ?> | <?= h($filtrosRotulo['locacao']) ?> | Técnico responsável: <?= h($filtrosRotulo['solicitante'] ?? 'Todos') ?> | <?= h($filtrosRotulo['periodo'] ?? 'Todos') ?>
	</div>

	<?php if ($modeloRelatorio === 'lista_filtrada') : ?>
	<table>
		<thead>
			<tr>
				<th>Nº</th>
				<th>Abertura</th>
				<th>Previsão</th>
				<th>Cliente</th>
				<th>Problema</th>
				<th>Técnico</th>
				<th class="num">Valor</th>
				<th>Situação</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($ordens as $reg) :
				$cliNome = $reg->cliente->tipo == C_ClientesTipoFisica ? $reg->cliente->nome : $reg->cliente->razaosocial;
				$prob = $problemas[$reg->idproblema] ?? '—';
				$sitTxt = trim(strip_tags((string)SituacaoOrdem($reg->situacao)));
				?>
			<tr>
				<td><?= h($reg->id) ?></td>
				<td><?= h($reg->dataabertura ? date_format($reg->dataabertura, 'd/m/Y') : '') ?></td>
				<td><?= h($reg->dataprevisao ? date_format($reg->dataprevisao, 'd/m/Y') : '') ?></td>
				<td><?= h($cliNome) ?></td>
				<td><?= h($prob) ?></td>
				<td><?= h($reg->user ? ($reg->user->name ?? '') : '') ?></td>
				<td class="num"><?= h($nf($reg->valortotal)) ?></td>
				<td><?= h($sitTxt) ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php elseif ($modeloRelatorio === 'resumo_situacao') : ?>
	<table>
		<thead>
			<tr>
				<th>Situação</th>
				<th class="num">Qtd</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($resumoSituacao as $row) : ?>
			<tr>
				<td><?= h($row['label']) ?></td>
				<td class="num"><?= (int)$row['total'] ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>
</body>
</html>
