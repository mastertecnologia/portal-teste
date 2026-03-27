<?php
$this->Html->css('/dist/css/pages/ordensservico-index-shell.css', ['block' => true]);
$this->assign('title', h($tituloRelatorio ?? 'Relatório'));
$nf = function ($v) {
	return number_format((float)$v, 2, ',', '.');
};
$qRelFiltros = [];
foreach (['cliente', 'situacao', 'problema', 'locacao'] as $k) {
	$v = $filtros[$k] ?? null;
	if ($v === null || $v === '') {
		continue;
	}
	if ($k === 'locacao' && ((string)$v === '-1' || (int)$v === -1)) {
		continue;
	}
	$qRelFiltros[$k] = $v;
}
?>
<style>
@media print {
	body * { visibility: hidden; }
	#os-rel-printable, #os-rel-printable * { visibility: visible; }
	#os-rel-printable { position: absolute; left: 0; top: 0; width: 100%; }
	.no-print { display: none !important; }
}
.os-rel-doc { max-width: 1100px; margin: 0 auto; padding: 16px; font-size: 13px; }
.os-rel-doc h1 { font-size: 1.25rem; margin: 0 0 4px; }
.os-rel-doc .meta { color: #555; font-size: 12px; margin-bottom: 16px; }
.os-rel-doc table { width: 100%; border-collapse: collapse; }
.os-rel-doc th, .os-rel-doc td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
.os-rel-doc th { background: #f0f0f0; font-size: 11px; text-transform: uppercase; }
.os-rel-doc .num { text-align: right; }
.os-rel-doc .tag { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; background: #eee; }
</style>

<div class="os-index-shell no-print" style="margin:0 0 12px;border-radius:12px;padding:12px 16px;">
	<div class="os-page-head-actions" style="flex-wrap:wrap;gap:10px;">
		<button type="button" class="os-btn-ghost" style="border:none;cursor:pointer;font-family:inherit;" onclick="window.print()">Imprimir</button>
		<?= $this->Html->link('PDF', ['action' => 'relatorioPdf', $modeloRelatorio, '?' => $qRelFiltros], ['class' => 'btn btn-success', 'style' => 'border-radius:8px;padding:6px 14px;font-size:12.5px;']) ?>
		<?= $this->Html->link(
			'<svg width="14" height="14" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M10 4L6 8l4 4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg><span>Relatórios</span>',
			['action' => 'relatorios', '?' => $qRelFiltros],
			['class' => 'os-page-head-link', 'escape' => false]
		) ?>
	</div>
</div>

<div id="os-rel-printable" class="os-rel-doc">
	<h1><?= h($tituloRelatorio) ?></h1>
	<div class="meta">
		Empresa: <?= h($nomeempresa ?? '') ?> · Gerado em <?= h(date('d/m/Y H:i')) ?><br>
		Filtros: situação <?= h($filtrosRotulo['situacao']) ?>, cliente <?= h($filtrosRotulo['cliente']) ?>,
		problema <?= h($filtrosRotulo['problema']) ?>, tipo <?= h($filtrosRotulo['locacao']) ?>.
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
	<?php if (empty($ordens)) : ?>
	<p><em>Nenhuma ordem encontrada com os filtros informados.</em></p>
	<?php endif; ?>

	<?php elseif ($modeloRelatorio === 'resumo_situacao') : ?>
	<table>
		<thead>
			<tr>
				<th>Situação</th>
				<th class="num">Quantidade</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($resumoSituacao as $row) : ?>
			<tr>
				<td><span class="tag"><?= h($row['label']) ?></span></td>
				<td class="num"><?= (int)$row['total'] ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php if (empty($resumoSituacao)) : ?>
	<p><em>Nenhum dado para o resumo.</em></p>
	<?php endif; ?>
	<?php endif; ?>
</div>
