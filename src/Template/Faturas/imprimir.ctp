<?php
use Cake\Routing\Router;

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

$dataEmissao  = !empty($fatura->dtemissao)  ? date_format($fatura->dtemissao,  'd/m/Y') : date('d/m/Y');
$dataVencto   = !empty($fatura->vencimento) ? date_format($fatura->vencimento, 'd/m/Y') : '';
$valorFmt     = 'R$ ' . number_format($fatura->valor,    2, ',', '.');
$descontoFmt  = 'R$ ' . number_format($fatura->desconto, 2, ',', '.');
$valorTotal   = $fatura->valor - $fatura->desconto;
$totalFmt     = 'R$ ' . number_format($valorTotal, 2, ',', '.');
?>
<link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet">
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

/* Bordas */
.b  { border: 1px solid #000; }
.bt { border-top: 1px solid #000; }
.bb { border-bottom: 1px solid #000; }
.bl { border-left: 1px solid #000; }
.br { border-right: 1px solid #000; }

/* Células de campo (label + valor) */
.campo { padding: 2px 4px; }
.campo-label { font-size: 9px; font-weight: 700; text-transform: uppercase; color: #222; display: block; }
.campo-valor { font-size: 11px; font-weight: 400; display: block; min-height: 14px; }

/* Cabeçalho de seção */
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

/* Tabela de produtos */
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

/* Totais */
.totais-row { background: #d0d0d0; }
.totais-row .campo-label { font-size: 9px; }
.totais-row .campo-valor { font-size: 13px; font-weight: 700; }

/* Footer */
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

/* Linha de campos (usa flex) */
.campo-row { display: flex; border: 1px solid #000; border-top: none; }
.campo-row .campo { flex: 1; border-right: 1px solid #ccc; }
.campo-row .campo:last-child { border-right: none; }
.campo-row-first { border-top: 1px solid #000; }
</style>

<!-- Botões (escondidos na impressão) -->
<div class="row hidden-print" style="margin-bottom:10px;">
    <?= $this->Html->link('🖨 Imprimir', '#', ['id' => 'btn-imprimir', 'class' => 'btn btn-warning m-r-5']) ?>
    <?php if ($role == 0): ?>
    <?= $this->Html->link('← Voltar', ['action' => 'edit', $fatura->id], ['class' => 'btn btn-info']) ?>
    <?php endif; ?>
</div>

<div class="col-md-12">
<div id="printable">

    <!-- ══ CABEÇALHO ══════════════════════════════════════════════════ -->
    <div class="b" style="display:flex;align-items:stretch;margin-bottom:0;">
        <!-- Logo -->
        <div style="width:180px;min-width:150px;padding:8px;display:flex;align-items:center;justify-content:center;border-right:1px solid #000;">
            <img src="<?= $this->request->getAttribute('webroot') . 'arquivos/empresas/logotipos/' . $empresaObj->id . '/logo.png' ?>"
                 alt="Logo" style="max-width:160px;max-height:70px;">
        </div>
        <!-- Dados empresa -->
        <div style="flex:1;padding:5px 8px;text-align:center;border-right:1px solid #000;">
            <div style="font-weight:700;font-size:13px;text-transform:uppercase;"><?= h($empresaObj->razaosocial) ?></div>
            <div><?= h(strtoupper($empresaObj->endereco)) ?>, <?= h($empresaObj->nroendereco) ?> - <?= h(strtoupper($empresaObj->bairro)) ?> - Cep: <?= h($empresaObj->cep) ?></div>
            <div><?= h(strtoupper($empresaObj->cidade->nome)) ?> / <?= h($empresaObj->cidade->estado->sigla) ?> - SAO FRANCISCO</div>
            <div>Fone/Fax: <?= h($empresaObj->fone) ?></div>
            <div>Cnpj: <?= h($empresaObj->cnpj) ?> - I.e: <?= h($empresaObj->inscricaoestadual) ?></div>
            <div>Email: <?= h($empresaObj->email) ?></div>
        </div>
    </div>

    <!-- ══ TÍTULO FATURA ══════════════════════════════════════════════ -->
    <div style="display:flex;align-items:baseline;justify-content:space-between;border:1px solid #000;border-top:none;padding:4px 8px;">
        <div style="font-weight:700;font-size:14px;text-transform:uppercase;letter-spacing:.5px;">
            FATURA DE LOCAÇÃO Nº:&nbsp;<?= h($fatura->nro) ?>
        </div>
        <div style="font-weight:700;font-size:12px;">
            Data: <?= $dataEmissao ?>
        </div>
    </div>

    <!-- ══ DADOS DO CLIENTE ═══════════════════════════════════════════ -->
    <div class="secao-titulo">DADOS DO CLIENTE</div>

    <div class="campo-row campo-row-first">
        <div class="campo" style="flex:3;">
            <span class="campo-label">Nome/Razão Social</span>
            <span class="campo-valor"><?= h($nomeCliente) ?></span>
        </div>
        <div class="campo" style="flex:2;">
            <span class="campo-label">Nome Fantasia</span>
            <span class="campo-valor"><?= h($fantasia) ?></span>
        </div>
        <div class="campo" style="flex:2;">
            <span class="campo-label">Contato</span>
            <span class="campo-valor"><?= h($fatura->cliente->contato ?? '') ?></span>
        </div>
        <div class="campo" style="flex:2;">
            <span class="campo-label">Cnpj/Cpf</span>
            <span class="campo-valor"><?= h($docCliente) ?></span>
        </div>
    </div>

    <div class="campo-row">
        <div class="campo" style="flex:4;">
            <span class="campo-label">Endereço</span>
            <span class="campo-valor"><?= h($endCliente) ?></span>
        </div>
        <div class="campo" style="flex:2;">
            <span class="campo-label">Bairro</span>
            <span class="campo-valor"><?= h($bairroCliente) ?></span>
        </div>
        <div class="campo" style="flex:1;">
            <span class="campo-label">Cep</span>
            <span class="campo-valor"><?= h($cepCliente) ?></span>
        </div>
    </div>

    <div class="campo-row">
        <div class="campo" style="flex:3;">
            <span class="campo-label">Município</span>
            <span class="campo-valor"><?= h($munCliente) ?></span>
        </div>
        <div class="campo" style="flex:1;">
            <span class="campo-label">UF</span>
            <span class="campo-valor"><?= h($ufCliente) ?></span>
        </div>
        <div class="campo" style="flex:2;">
            <span class="campo-label">Fone/Fax</span>
            <span class="campo-valor"><?= h($foneCliente) ?></span>
        </div>
        <div class="campo" style="flex:2;">
            <span class="campo-label">I.M.</span>
            <span class="campo-valor"><?= h($fatura->cliente->inscricaomunicipal ?? '') ?></span>
        </div>
    </div>

    <!-- ══ DADOS DA FATURA ════════════════════════════════════════════ -->
    <div class="secao-titulo" style="margin-top:6px;">DADOS DA FATURA</div>

    <div class="campo-row campo-row-first">
        <?php foreach ([1, 2, 3] as $col): ?>
        <div class="campo" style="flex:1;<?= $col < 3 ? 'border-right:1px solid #000;' : '' ?>">
            <div style="display:flex;gap:0;">
                <div style="flex:2;border-right:1px solid #ccc;padding:2px 4px;">
                    <span class="campo-label">Fatura</span>
                    <span class="campo-valor"><?= $col === 1 ? h($fatura->nro) : '' ?></span>
                </div>
                <div style="flex:1;border-right:1px solid #ccc;padding:2px 4px;">
                    <span class="campo-label">Vencto.</span>
                    <span class="campo-valor"><?= $col === 1 ? $dataVencto : '' ?></span>
                </div>
                <div style="flex:1;padding:2px 4px;">
                    <span class="campo-label">Valor</span>
                    <span class="campo-valor"><?= $col === 1 ? $valorFmt : '' ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ══ DADOS DOS PRODUTOS ═════════════════════════════════════════ -->
    <div class="secao-titulo" style="margin-top:6px;">DADOS DOS PRODUTOS</div>

    <table class="tbl-produtos" style="margin-top:0;">
        <thead>
            <tr>
                <th style="width:8%;">Código</th>
                <th style="width:38%;">Descrição do Produto</th>
                <th style="width:6%;">Unid.</th>
                <th style="width:10%;text-align:right;">Quantidade</th>
                <th style="width:13%;text-align:right;">Valor Unitário</th>
                <th style="width:12%;text-align:right;">Desconto</th>
                <th style="width:13%;text-align:right;">Valor Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($carrinho as $reg): ?>
            <tr>
                <td><?= h($reg->codigo) ?></td>
                <td><?= h($reg->descricao) ?></td>
                <td><?= h($reg->unidade ?? 'UN') ?></td>
                <td style="text-align:right;"><?= number_format($reg->quantidade, 2, ',', '.') ?></td>
                <td style="text-align:right;">R$ <?= number_format($reg->valoritem, 2, ',', '.') ?></td>
                <td style="text-align:right;">R$ 0,00</td>
                <td style="text-align:right;">R$ <?= number_format($reg->valortotal, 2, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
            <?php
            // Preencher linhas em branco até pelo menos 5 linhas
            $linhas = count($carrinho);
            for ($i = $linhas; $i < 5; $i++):
            ?>
            <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
            <?php endfor; ?>
        </tbody>
    </table>

    <!-- ══ TOTAIS DO RECIBO ═══════════════════════════════════════════ -->
    <div class="secao-titulo" style="margin-top:6px;">TOTAIS DO RECIBO</div>

    <div class="campo-row totais-row campo-row-first">
        <div class="campo" style="flex:1;border-right:1px solid #000;">
            <span class="campo-label">Valor dos Serviços</span>
            <span class="campo-valor"><?= $valorFmt ?></span>
        </div>
        <div class="campo" style="flex:1;border-right:1px solid #000;">
            <span class="campo-label">Valor do Desconto</span>
            <span class="campo-valor"><?= $descontoFmt ?></span>
        </div>
        <div class="campo" style="flex:1;">
            <span class="campo-label">Valor Total do Recibo</span>
            <span class="campo-valor"><?= $totalFmt ?></span>
        </div>
    </div>

    <!-- ══ FORMA PAGAMENTO / VALIDADE / ENTREGA / VENDEDOR ═══════════ -->
    <div class="campo-row" style="margin-top:4px;border-top:1px solid #000;">
        <div class="campo" style="flex:3;border-right:1px solid #ccc;">
            <span class="campo-label">Forma de Pagamento</span>
            <span class="campo-valor"><?= h(OrdensPagamento($fatura->pagamento)) ?></span>
        </div>
        <div class="campo" style="flex:2;border-right:1px solid #ccc;">
            <span class="campo-label">Validade</span>
            <span class="campo-valor"><?= $dataVencto ?></span>
        </div>
        <div class="campo" style="flex:2;border-right:1px solid #ccc;">
            <span class="campo-label">Previsão de Entrega</span>
            <span class="campo-valor"><?= !empty($fatura->dtretorno) ? date_format($fatura->dtretorno, 'd/m/Y') : '' ?></span>
        </div>
        <div class="campo" style="flex:2;">
            <span class="campo-label">Vendedor</span>
            <span class="campo-valor"><?= h($fatura->vendedor ?? '') ?></span>
        </div>
    </div>

    <!-- ══ OBSERVAÇÕES DO RECIBO ══════════════════════════════════════ -->
    <div class="secao-titulo" style="margin-top:6px;">OBSERVAÇÕES DO RECIBO</div>
    <div style="border:1px solid #000;border-top:none;padding:4px 6px;min-height:40px;font-size:11px;">
        <?= nl2br(h($fatura->referente ?? '')) ?>
    </div>

    <?= $this->element('Faturas/observacoes_fiscais_ibpt', ['ibptBreakdown' => $ibptBreakdown ?? null, 'valorBaseIbpt' => $valorTotal]) ?>

    <!-- ══ RODAPÉ / ASSINATURA ════════════════════════════════════════ -->
    <div class="footer-wrap footer-print" style="margin-top:8px;">
        <div class="footer-left">
            FATURA DE LOCAÇÃO<br>
            Nº: <?= h($fatura->nro) ?><br><br>
            Data: <?= $dataEmissao ?>
        </div>
        <div class="footer-right">
            <div style="font-weight:700;margin-bottom:8px;">Estamos de Acordo com a Emissão dessa Fatura:</div>
            <div style="display:flex;align-items:flex-end;gap:20px;flex-wrap:wrap;">
                <div>
                    <?= h(strtoupper($empresaObj->cidade->nome ?? 'BENTO GONÇALVES')) ?>,&nbsp;
                    <span class="sig-line" style="width:120px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                </div>
                <div>
                    Assinatura:&nbsp;<span class="sig-line" style="width:200px;"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Crédito rodapé -->
    <div class="credito-rodape">
        DB9 Sistemas - Software de Gestão Empresarial Completo - www.db9sistemas.com.br - (51) 98419-5964
    </div>

</div><!-- #printable -->
</div>

<script>
$('#btn-imprimir').click(function (e) {
    e.preventDefault();
    $('.hidden-print').hide();
    window.print();
    $('.hidden-print').show();
});
</script>
