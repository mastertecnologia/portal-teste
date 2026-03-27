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
		body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #222; }
		h1 { font-size: 14pt; margin: 0 0 6px; }
		.meta { font-size: 9pt; color: #444; margin-bottom: 12px; }
		table { width: 100%; border-collapse: collapse; margin-top: 8px; }
		th, td { border: 1px solid #999; padding: 4px 6px; text-align: left; }
		th { background: #eee; font-size: 8pt; text-transform: uppercase; }
		.num { text-align: right; }
	</style>
</head>
<body>
	<h1><?= h($tituloRelatorio) ?></h1>
	<div class="meta">
		<?= h($nomeempresa ?? '') ?> — <?= h(date('d/m/Y H:i')) ?><br/>
		Filtros: <?= h($filtrosRotulo['situacao']) ?> | <?= h($filtrosRotulo['cliente']) ?> | <?= h($filtrosRotulo['problema']) ?> | <?= h($filtrosRotulo['locacao']) ?>
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
