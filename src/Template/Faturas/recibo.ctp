<?php
$pessoaFisica   = $fatura->cliente->tipo == C_ClientesTipoFisica;
$pessoaJuridica = $fatura->cliente->tipo == C_ClientesTipoJuridica;

$nomeCliente   = $pessoaJuridica ? $fatura->cliente->razaosocial : $fatura->cliente->nome;
$fantasia      = $pessoaJuridica ? ($fatura->cliente->nomefantasia ?? '') : '';
$docCliente    = $pessoaJuridica ? $fatura->cliente->cnpj : $fatura->cliente->cpf;
$endCliente    = strtoupper(trim($fatura->cliente->endereco . ' ' . ($fatura->cliente->nroendereco ?? '') . ' ' . ($fatura->cliente->complemento ?? '')));
$bairroCliente = strtoupper($fatura->cliente->bairro ?? '');
$cepCliente    = $fatura->cliente->cep ?? '';
$munCliente    = strtoupper($fatura->cliente->cidade->nome ?? '');
$ufCliente     = strtoupper($fatura->cliente->cidade->estado->sigla ?? '');
$foneCliente   = $fatura->cliente->fone ?? '';

$dataRecebimento = !empty($recibo->datarecebimento) ? date_format($recibo->datarecebimento, 'd/m/Y') : date('d/m/Y');
$dataVencto      = !empty($fatura->vencimento) ? date_format($fatura->vencimento, 'd/m/Y') : '';

$faturaRef = h($fatura->nro) . '-' . (int) $recibo->nro;
$valorPagoFmt    = 'R$ ' . number_format((float) $recibo->valorpago, 2, ',', '.');
$jurosRec        = (float) $recibo->juros;
$descontoRec     = (float) $recibo->desconto;
$valorServicos   = max(0, (float) $recibo->valorpago + $descontoRec - $jurosRec);
$valorServFmt    = 'R$ ' . number_format($valorServicos, 2, ',', '.');
$descontoRecFmt  = 'R$ ' . number_format($descontoRec, 2, ',', '.');
$totalReciboFmt  = $valorPagoFmt;

$valorIbptRecibo = (float) $recibo->valorpago;

$obsRecibo = [];
$ref = trim((string) ($fatura->referente ?? ''));
if ($ref !== '') {
	$obsRecibo[] = $ref;
}
if ($jurosRec > 0) {
	$obsRecibo[] = 'Juros neste recebimento: R$ ' . number_format($jurosRec, 2, ',', '.');
}
if ($descontoRec > 0) {
	$obsRecibo[] = 'Desconto neste recebimento: R$ ' . number_format($descontoRec, 2, ',', '.');
}
$extenso = function_exists('convert_number_to_words') ? convert_number_to_words($recibo->valorpago) : '';
if ($extenso !== '') {
	$obsRecibo[] = 'Valor por extenso: ' . $extenso . '.';
}
$obsRecibo[] = 'Parcela ' . (int) $recibo->nro . ' da locação ' . (string) $fatura->nro . '.';
?>
<link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet">
<?= $this->Html->css('/css/faturas-locacao-doc.css') ?>
<style>
* { box-sizing: border-box; }
body, #printable { font-family: 'Open Sans', Arial, sans-serif; font-size: 11px; color: #000; }

@media print {
    body *, .main-wrapper { visibility: hidden; }
    #printable, #printable * { visibility: visible; }
    .hidden-print { display: none !important; }
    .page-wrapper { padding: 0; }
    #printable { position: relative; font-size: 10px; }
    .footer-print { position: fixed; bottom: 0; width: 100%; }
}

#printable { background: #fff; padding: 10px; max-width: 780px; margin: 0 auto; }

.b  { border: 1px solid #000; }

.campo { padding: 2px 4px; }
.campo-label { font-size: 9px; font-weight: 700; text-transform: uppercase; color: #222; display: block; }
.campo-valor { font-size: 11px; font-weight: 400; display: block; min-height: 14px; }

.secao-titulo {
    background: #f0f0f0;
    font-weight: 700;
    font-size: 10px;
    text-transform: uppercase;
    padding: 2px 5px;
    border-top: 1px solid #000;
    border-bottom: 1px solid #000;
    letter-spacing: .5px;
}

.tbl-produtos { width: 100%; border-collapse: collapse; }
.tbl-produtos th {
    background: #e8e8e8;
    border: 1px solid #000;
    padding: 3px 4px;
    font-size: 10px;
    text-align: left;
}
.tbl-produtos td {
    border: 1px solid #ccc;
    padding: 3px 4px;
    font-size: 11px;
    vertical-align: top;
}
.tbl-produtos tr:nth-child(even) td { background: #fafafa; }

.totais-row { background: #d0d0d0; }
.totais-row .campo-label { font-size: 9px; }
.totais-row .campo-valor { font-size: 13px; font-weight: 700; }

.footer-wrap {
    border: 1px solid #000;
    display: flex;
    margin-top: 6px;
}
.footer-left {
    width: 25%;
    padding: 6px;
    border-right: 2px solid #000;
    font-size: 10px;
    font-weight: 700;
    text-align: center;
}
.footer-right {
    flex: 1;
    padding: 6px;
    font-size: 10px;
}
.sig-line {
    display: inline-block;
    border-bottom: 1px solid #000;
    min-width: 100px;
}
.credito-rodape { font-size: 9px; text-align: center; margin-top: 4px; color: #555; }

.campo-row { display: flex; border: 1px solid #000; border-top: none; }
.campo-row .campo { flex: 1; border-right: 1px solid #ccc; }
.campo-row .campo:last-child { border-right: none; }
.campo-row-first { border-top: 1px solid #000; }
</style>

<div class="row hidden-print fat-loc-actions-bar">
	<?= $this->Html->link('🖨 Imprimir', '#', ['id' => 'btn-imprimir', 'class' => 'btn btn-pgm btn-pgm-imprimir btn-orange m-r-5']) ?>
	<?php if ($role == 0): ?>
	<?= $this->Html->link('← Voltar para a locação', ['action' => 'edit', $fatura->id, 2], ['class' => 'btn btn-pgm btn-pgm-situacao btn-info']) ?>
	<?php endif; ?>
</div>

<div class="col-md-12">
<div id="printable">

	<div class="b fat-loc-header-b">
		<div class="fat-loc-logo-cell">
			<img src="<?= $this->request->getAttribute('webroot') . 'arquivos/empresas/logotipos/' . $empresaObj->id . '/logo.png' ?>"
				 alt="Logo" class="fat-loc-logo-img">
		</div>
		<div class="fat-loc-empresa-cell">
			<div class="fat-loc-empresa-nome"><?= h($empresaObj->razaosocial) ?></div>
			<div><?= h(strtoupper($empresaObj->endereco)) ?>, <?= h($empresaObj->nroendereco) ?> - <?= h(strtoupper($empresaObj->bairro)) ?> - Cep: <?= h($empresaObj->cep) ?></div>
			<div><?= h(strtoupper($empresaObj->cidade->nome)) ?> / <?= h($empresaObj->cidade->estado->sigla) ?> - SAO FRANCISCO</div>
			<div>Fone/Fax: <?= h($empresaObj->fone) ?></div>
			<div>Cnpj: <?= h($empresaObj->cnpj) ?> - I.e: <?= h($empresaObj->inscricaoestadual) ?></div>
			<div>Email: <?= h($empresaObj->email) ?></div>
		</div>
	</div>

	<div class="fat-loc-title-bar fat-loc-title-bar--wrap">
		<div class="fat-loc-doc-title">
			RECIBO DE LOCAÇÃO — Ref.&nbsp;<?= $faturaRef ?>
		</div>
		<div class="fat-loc-doc-meta">
			Data recebimento: <?= h($dataRecebimento) ?> &nbsp;|&nbsp; Fatura: <?= h($fatura->nro) ?>
		</div>
	</div>

	<div class="secao-titulo">DADOS DO CLIENTE</div>

	<div class="campo-row campo-row-first">
		<div class="campo fat-loc-f3">
			<span class="campo-label">Nome/Razão Social</span>
			<span class="campo-valor"><?= h($nomeCliente) ?></span>
		</div>
		<div class="campo fat-loc-f2">
			<span class="campo-label">Nome Fantasia</span>
			<span class="campo-valor"><?= h($fantasia) ?></span>
		</div>
		<div class="campo fat-loc-f2">
			<span class="campo-label">Contato</span>
			<span class="campo-valor"><?= h($fatura->cliente->contato ?? '') ?></span>
		</div>
		<div class="campo fat-loc-f2">
			<span class="campo-label">Cnpj/Cpf</span>
			<span class="campo-valor"><?= h($docCliente) ?></span>
		</div>
	</div>

	<div class="campo-row">
		<div class="campo fat-loc-f4">
			<span class="campo-label">Endereço</span>
			<span class="campo-valor"><?= h($endCliente) ?></span>
		</div>
		<div class="campo fat-loc-f2">
			<span class="campo-label">Bairro</span>
			<span class="campo-valor"><?= h($bairroCliente) ?></span>
		</div>
		<div class="campo fat-loc-f1">
			<span class="campo-label">Cep</span>
			<span class="campo-valor"><?= h($cepCliente) ?></span>
		</div>
	</div>

	<div class="campo-row">
		<div class="campo fat-loc-f3">
			<span class="campo-label">Município</span>
			<span class="campo-valor"><?= h($munCliente) ?></span>
		</div>
		<div class="campo fat-loc-f1">
			<span class="campo-label">UF</span>
			<span class="campo-valor"><?= h($ufCliente) ?></span>
		</div>
		<div class="campo fat-loc-f2">
			<span class="campo-label">Fone/Fax</span>
			<span class="campo-valor"><?= h($foneCliente) ?></span>
		</div>
		<div class="campo fat-loc-f2">
			<span class="campo-label">I.M.</span>
			<span class="campo-valor"><?= h($fatura->cliente->inscricaomunicipal ?? '') ?></span>
		</div>
	</div>

	<div class="secao-titulo fat-loc-sec-mt">DADOS DA FATURA</div>

	<div class="campo-row campo-row-first">
		<?php foreach ([1, 2, 3] as $col): ?>
		<div class="campo fat-loc-f1<?= $col < 3 ? ' fat-loc-fatura-col--sep' : '' ?>">
			<div class="fat-loc-fatura-inner">
				<div class="fat-loc-fic-2">
					<span class="campo-label">Fatura</span>
					<span class="campo-valor"><?= $col === 1 ? $faturaRef : '' ?></span>
				</div>
				<div class="fat-loc-fic-1">
					<span class="campo-label">Vencto.</span>
					<span class="campo-valor"><?= $col === 1 ? h($dataVencto) : '' ?></span>
				</div>
				<div class="fat-loc-fic-1-end">
					<span class="campo-label">Valor</span>
					<span class="campo-valor"><?= $col === 1 ? $valorPagoFmt : '' ?></span>
				</div>
			</div>
		</div>
		<?php endforeach; ?>
	</div>

	<div class="secao-titulo fat-loc-sec-mt">DADOS DOS PRODUTOS</div>

	<table class="tbl-produtos fat-loc-tbl-mt0">
		<thead>
			<tr>
				<th class="fat-loc-w8">Código</th>
				<th class="fat-loc-w38">Descrição do Produto</th>
				<th class="fat-loc-w6">Unid.</th>
				<th class="fat-loc-w10 fat-loc-tar">Quantidade</th>
				<th class="fat-loc-w13 fat-loc-tar">Valor Unitário</th>
				<th class="fat-loc-w12 fat-loc-tar">Desconto</th>
				<th class="fat-loc-w13 fat-loc-tar">Valor Total</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($carrinho as $reg): ?>
			<tr>
				<td><?= h($reg->codigo) ?></td>
				<td><?= h($reg->descricao) ?></td>
				<td><?= h($reg->unidade ?? 'UN') ?></td>
				<td class="fat-loc-tar"><?= number_format($reg->quantidade, 2, ',', '.') ?></td>
				<td class="fat-loc-tar">R$ <?= number_format($reg->valoritem, 2, ',', '.') ?></td>
				<td class="fat-loc-tar">R$ 0,00</td>
				<td class="fat-loc-tar">R$ <?= number_format($reg->valortotal, 2, ',', '.') ?></td>
			</tr>
			<?php endforeach; ?>
			<?php
			$linhas = count($carrinho);
			for ($i = $linhas; $i < 5; $i++):
			?>
			<tr><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
			<?php endfor; ?>
		</tbody>
	</table>

	<div class="secao-titulo fat-loc-sec-mt">TOTAIS DO RECIBO</div>

	<div class="campo-row totais-row campo-row-first">
		<div class="campo fat-loc-f1 fat-loc-br-000">
			<span class="campo-label">Valor dos Serviços</span>
			<span class="campo-valor"><?= $valorServFmt ?></span>
		</div>
		<div class="campo fat-loc-f1 fat-loc-br-000">
			<span class="campo-label">Valor do Desconto</span>
			<span class="campo-valor"><?= $descontoRecFmt ?></span>
		</div>
		<div class="campo fat-loc-f1">
			<span class="campo-label">Valor Total do Recibo</span>
			<span class="campo-valor"><?= $totalReciboFmt ?></span>
		</div>
	</div>

	<div class="campo-row fat-loc-meta-row">
		<div class="campo fat-loc-f3 fat-loc-br-ccc">
			<span class="campo-label">Forma de Pagamento</span>
			<span class="campo-valor"><?= h(OrdensPagamento($recibo->pagamento)) ?></span>
		</div>
		<div class="campo fat-loc-f2 fat-loc-br-ccc">
			<span class="campo-label">Validade</span>
			<span class="campo-valor"><?= h($dataVencto) ?></span>
		</div>
		<div class="campo fat-loc-f2 fat-loc-br-ccc">
			<span class="campo-label">Previsão de Entrega</span>
			<span class="campo-valor"><?= !empty($fatura->dtretorno) ? date_format($fatura->dtretorno, 'd/m/Y') : '' ?></span>
		</div>
		<div class="campo fat-loc-f2">
			<span class="campo-label">Vendedor</span>
			<span class="campo-valor"><?= h($fatura->vendedor ?? '') ?></span>
		</div>
	</div>

	<div class="secao-titulo fat-loc-sec-mt">OBSERVAÇÕES DO RECIBO</div>
	<div class="fat-loc-obs-box">
		<?= nl2br(h(implode("\n", $obsRecibo))) ?>
	</div>

	<?= $this->element('Faturas/observacoes_fiscais_ibpt', ['ibptBreakdown' => $ibptBreakdown ?? null, 'valorBaseIbpt' => $valorIbptRecibo]) ?>

	<div class="footer-wrap footer-print fat-loc-footer-mt">
		<div class="footer-left">
			RECIBO DE LOCAÇÃO<br>
			Ref.: <?= $faturaRef ?><br><br>
			Data: <?= h($dataRecebimento) ?>
		</div>
		<div class="footer-right">
			<div class="fat-loc-sign-title">Estamos de Acordo com a Emissão dessa Fatura:</div>
			<div class="fat-loc-sign-row">
				<div>
					<?= h(strtoupper($empresaObj->cidade->nome ?? 'BENTO GONÇALVES')) ?>,&nbsp;
					<span class="sig-line fat-loc-sig-date">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
				</div>
				<div>
					Assinatura:&nbsp;<span class="sig-line fat-loc-sig-full"></span>
				</div>
			</div>
		</div>
	</div>

	<div class="credito-rodape">
		DB9 Sistemas - Software de Gestão Empresarial Completo - www.db9sistemas.com.br - (51) 98419-5964
	</div>

</div>
</div>

<script>
$('#btn-imprimir').click(function (e) {
	e.preventDefault();
	$('.hidden-print').hide();
	window.print();
	$('.hidden-print').show();
});
</script>
