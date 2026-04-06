<?php
/**
 * Observações fiscais + tributos aproximados (IBPT), com detalhamento federal/estadual/municipal quando disponível.
 *
 * @var array|null $ibptBreakdown Retorno de IbptTributosService::breakdownForCarrinho (opcionalmente scaleBreakdown).
 * @var float|null $valorBaseIbpt Fallback quando não há linhas no breakdown.
 * @var float|null $ibptAliquota Fallback único (fração, ex. 0.3145).
 */
$aliqFallback = isset($ibptAliquota) && $ibptAliquota !== null ? (float) $ibptAliquota : 0.3145;
$baseFallback = isset($valorBaseIbpt) ? (float) $valorBaseIbpt : 0.0;
$ibptFmtFallback = 'R$ ' . number_format($baseFallback * $aliqFallback, 2, ',', '.');

$useTable = is_array($ibptBreakdown ?? null)
	&& !empty($ibptBreakdown['lines'])
	&& isset($ibptBreakdown['totais']['geral_valor']);
?>
<style>
.ibpt-secao-mt { margin-top: 6px; }
.ibpt-box-obs {
	border: 1px solid #000;
	border-top: none;
	padding: 5px 6px;
	font-size: 9.5px;
	text-align: justify;
	line-height: 1.4;
}
.ibpt-box-ibpt {
	border: 1px solid #000;
	border-top: none;
	padding: 4px 4px;
	font-size: 9px;
}
.ibpt-intro-p { margin: 0 0 4px 0; }
.ibpt-tbl { margin: 0; font-size: 9px; }
.ibpt-th-w9 { width: 9%; }
.ibpt-th-w10 { width: 10%; }
.ibpt-th-w22 { width: 22%; }
.ibpt-th-w11 { width: 11%; }
.ibpt-th-w8 { width: 8%; }
.ibpt-th-w12 { width: 12%; }
.ibpt-td-r { text-align: right; }
.ibpt-totals {
	display: flex;
	flex-wrap: wrap;
	border-top: 1px solid #000;
	margin-top: 4px;
	padding-top: 4px;
	gap: 8px;
	font-size: 10px;
	font-weight: 700;
}
.ibpt-totals-end {
	flex: 1;
	text-align: right;
}
.ibpt-fallback-box {
	border: 1px solid #000;
	border-top: none;
	padding: 5px 6px;
	font-size: 10px;
	margin-top: 0;
}
.ibpt-fallback-hint {
	font-size: 9px;
	color: #333;
}
</style>
<div class="secao-titulo ibpt-secao-mt">OBSERVAÇÕES FISCAIS</div>
<div class="ibpt-box-obs">
	DOCUMENTO EMITIDO POR ME OU EPP OPTANTE PELO SIMPLES NACIONAL. NÃO GERA DIREITO A CRÉDITO FISCAL DE IPI. LOCAÇÃO DE BENS MÓVEIS –
	ATIVIDADE IMPOSSIBILIDADE DE EMISSÃO DE NOTA FISCAL. ESTE RECIBO DE LOCAÇÃO É VÁLIDO COMO DOCUMENTO EQUIVALENTE, NOS TERMOS DO
	ARTIGO 1º DA LEI 8.846/94 E § 1º DESTE ARTIGO. NÃO INCIDÊNCIA DE ISS CONFORME SÚMULA VINCULANTE Nº 31 DO STF: "É INCONSTITUCIONAL A
	INCIDÊNCIA DO IMPOSTO SOBRE SERVIÇOS DE QUALQUER NATUREZA – ISS SOBRE OPERAÇÕES DE LOCAÇÃO DE BENS MÓVEIS."
</div>

<?php if ($useTable) :
	$t = $ibptBreakdown['totais'];
	$geral = (float) $t['geral_valor'];
	$ufIbpt = h($ibptBreakdown['uf'] ?? '');
	$ver = h($ibptBreakdown['versao'] ?? '');
	$fonte = h($ibptBreakdown['fonte'] ?? 'IBPT');
	$fb = !empty($ibptBreakdown['fallback']);
	?>
<div class="secao-titulo ibpt-secao-mt">TRIBUTOS APROXIMADOS (LEI 12.741/2012 — FONTE IBPT)</div>
<div class="ibpt-box-ibpt">
	<p class="ibpt-intro-p">
		UF: <strong><?= $ufIbpt ?></strong>
		<?php if ($ver !== '') : ?> · Versão tabela: <strong><?= $ver ?></strong><?php endif; ?>
		· Fonte: <strong><?= $fonte ?></strong>
		<?php if ($fb) : ?> · <em>Inclui estimativa onde a consulta não retornou alíquotas.</em><?php endif; ?>
		<?php if (!empty($ibptBreakdown['scaled_by']) && (float)$ibptBreakdown['scaled_by'] < 0.999) : ?>
		 · <em>Valores proporcionais ao valor pago neste recibo.</em>
		<?php endif; ?>
	</p>
	<table class="tbl-produtos ibpt-tbl">
		<thead>
			<tr>
				<th class="ibpt-th-w9">Cód.</th>
				<th class="ibpt-th-w10">NCM</th>
				<th class="ibpt-th-w22">Descrição</th>
				<th class="ibpt-th-w11 ibpt-td-r">Valor R$</th>
				<th class="ibpt-th-w8 ibpt-td-r">Fed.%</th>
				<th class="ibpt-th-w8 ibpt-td-r">UF.%</th>
				<th class="ibpt-th-w8 ibpt-td-r">Mun.%</th>
				<th class="ibpt-th-w12 ibpt-td-r">Aprox. R$</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($ibptBreakdown['lines'] as $ln) :
				$pn = $ln['pct_nacional'];
				$pe = $ln['pct_estadual'];
				$pm = $ln['pct_municipal'];
				?>
			<tr>
				<td><?= h($ln['codigo']) ?></td>
				<td><?= h($ln['ncm']) ?></td>
				<td><?= h(function_exists('mb_substr') ? mb_substr($ln['descricao'], 0, 42) : substr($ln['descricao'], 0, 42)) ?></td>
				<td class="ibpt-td-r"><?= number_format((float)$ln['valor'], 2, ',', '.') ?></td>
				<td class="ibpt-td-r"><?= $pn === null ? '—' : number_format((float)$pn, 2, ',', '.') ?></td>
				<td class="ibpt-td-r"><?= $pe === null ? '—' : number_format((float)$pe, 2, ',', '.') ?></td>
				<td class="ibpt-td-r"><?= $pm === null ? '—' : number_format((float)$pm, 2, ',', '.') ?></td>
				<td class="ibpt-td-r"><?= number_format((float)$ln['aprox'], 2, ',', '.') ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<div class="ibpt-totals">
		<span>Federal (aprox.): R$ <?= number_format((float)$t['nacional_valor'], 2, ',', '.') ?></span>
		<span>Estadual (aprox.): R$ <?= number_format((float)$t['estadual_valor'], 2, ',', '.') ?></span>
		<span>Municipal (aprox.): R$ <?= number_format((float)$t['municipal_valor'], 2, ',', '.') ?></span>
		<span class="ibpt-totals-end">Total aprox. tributos: R$ <?= number_format($geral, 2, ',', '.') ?> — Fonte IBPT</span>
	</div>
</div>
<?php else : ?>
<div class="ibpt-fallback-box">
	<strong>Valor Aprox. Tributos: <?= $ibptFmtFallback ?> Fonte IBPT</strong>
	<span class="ibpt-fallback-hint"> (sem itens para detalhar ou configure NCM nos produtos e conectividade com a tabela IBPT.)</span>
</div>
<?php endif; ?>
