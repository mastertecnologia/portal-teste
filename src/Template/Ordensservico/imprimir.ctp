<?php

use Cake\Routing\Router;

$this->Breadcrumbs->add('Ordens de Serviço', ['controller' => 'Ordensservico', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Editar', ['controller' => 'Ordensservico', 'action' => 'edit', $ordem->id], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Imprimir Ordem de Serviço', [], ['class' => 'breadcrumb-item active']);

$logo = 'pgm.png';

function Mask($mask, $str)
{
    $str = str_replace(" ", "", $str);
    for ($i = 0; $i < strlen($str); $i++) $mask[strpos($mask, "#")] = $str[$i];
    return $mask;
}
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    /* ── Reset / Base ─────────────────────────────────────── */
    body {
        font-family: 'Inter', 'Open Sans', sans-serif;
        background: #f0f2f5;
        color: #1a1f2e;
    }

    /* ── Action buttons bar ───────────────────────────────── */
    .os-actions {
        display: flex;
        gap: 8px;
        margin-bottom: 18px;
        flex-wrap: wrap;
    }
    .os-actions .btn {
        font-weight: 600;
        font-size: 13px;
        border-radius: 8px;
        padding: 8px 18px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .btn-os-pdf    { background: linear-gradient(135deg,#1d9e75,#0f6e56); border:none; color:#fff !important; box-shadow:0 2px 10px rgba(29,158,117,.35); }
    .btn-os-pdf:hover { filter:brightness(1.08); color:#fff !important; }
    .btn-os-print  { background: linear-gradient(135deg,#ff8800,#cc6600); border:none; color:#fff !important; box-shadow:0 2px 10px rgba(255,136,0,.30); }
    .btn-os-print:hover { filter:brightness(1.08); color:#fff !important; }
    .btn-os-back   { background:#fff; border:1px solid #d0d7de; color:#374151 !important; }
    .btn-os-back:hover { background:#f3f4f6; color:#374151 !important; }

    /* ── Document card ────────────────────────────────────── */
    #printable {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,.10);
        overflow: hidden;
    }

    /* ── Document top accent bar ──────────────────────────── */
    .os-doc-topbar {
        height: 5px;
        background: linear-gradient(90deg, #1d9e75 0%, #0f6e56 100%);
    }

    /* ── Document header (logo + title + info) ────────────── */
    .os-doc-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 28px 36px 20px;
        border-bottom: 1px solid #e5e7eb;
    }
    .os-doc-header .os-logo img {
        height: 52px;
        width: auto;
    }
    .os-doc-header .os-title-block {
        text-align: center;
    }
    .os-doc-header .os-title-block h1 {
        font-size: 22px;
        font-weight: 700;
        color: #1a1f2e;
        margin: 0 0 2px;
        letter-spacing: -.02em;
    }
    .os-doc-header .os-title-block .os-subtitle {
        font-size: 13px;
        color: #6b7280;
        font-weight: 500;
    }
    .os-doc-header .os-number-badge {
        background: linear-gradient(135deg,#1d9e75,#0f6e56);
        color: #fff;
        border-radius: 10px;
        padding: 10px 20px;
        text-align: center;
        min-width: 110px;
    }
    .os-doc-header .os-number-badge .os-num-label {
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .08em;
        opacity: .85;
    }
    .os-doc-header .os-number-badge .os-num-value {
        font-size: 20px;
        font-weight: 700;
        line-height: 1.2;
    }

    /* ── Client info grid ─────────────────────────────────── */
    .os-client-section {
        padding: 20px 36px;
        border-bottom: 1px solid #e5e7eb;
    }
    .os-section-label {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .1em;
        color: #00a876;
        margin-bottom: 12px;
    }
    .os-info-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        overflow: hidden;
    }
    .os-info-cell--span2 {
        grid-column: span 2;
    }
    .os-total-row.valortotalh5 {
        display: none;
    }
    .os-info-cell {
        padding: 10px 14px;
        border-right: 1px solid #e5e7eb;
        border-bottom: 1px solid #e5e7eb;
    }
    .os-info-cell:nth-child(3n) { border-right: none; }
    .os-info-cell:nth-last-child(-n+3) { border-bottom: none; }
    .os-info-cell .os-field-label {
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .07em;
        color: #6b7280;
        margin-bottom: 3px;
    }
    .os-info-cell .os-field-value {
        font-size: 12.5px;
        font-weight: 500;
        color: #1a1f2e;
        word-break: break-word;
    }

    /* ── Section headers (Relato / Produtos) ──────────────── */
    .os-section-header {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 14px 36px;
        background: #f8fafb;
        border-bottom: 1px solid #e5e7eb;
        border-top: 1px solid #e5e7eb;
    }
    .os-section-header .os-section-icon {
        width: 4px;
        height: 20px;
        background: linear-gradient(180deg,#1d9e75,#0f6e56);
        border-radius: 2px;
        flex-shrink: 0;
    }
    .os-section-header h3 {
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .09em;
        color: #374151;
        margin: 0;
    }

    /* ── Relato body ──────────────────────────────────────── */
    .os-relato-body {
        padding: 16px 36px;
        font-size: 13px;
        color: #374151;
        line-height: 1.65;
        border-bottom: 1px solid #e5e7eb;
    }
    .os-relato-body .os-obs {
        margin-top: 8px;
        padding: 10px 14px;
        background: #fffbeb;
        border-left: 3px solid #f59e0b;
        border-radius: 0 6px 6px 0;
        font-size: 12.5px;
        color: #92400e;
    }

    /* ── Products table ───────────────────────────────────── */
    .os-products-body {
        padding: 0 36px 20px;
    }
    .os-products-body .table {
        margin-top: 16px;
        margin-bottom: 0;
        font-size: 12px;
        border-collapse: separate;
        border-spacing: 0;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #e5e7eb;
        width: 100%;
    }
    .os-products-body .table thead tr th {
        background: #f3f4f6;
        color: #374151;
        font-weight: 700;
        font-size: 10.5px;
        text-transform: uppercase;
        letter-spacing: .07em;
        padding: 10px 12px;
        border-bottom: 2px solid #00a876;
        border-top: none;
        white-space: nowrap;
    }
    .os-products-body .table tbody tr td {
        padding: 9px 12px;
        border-top: 1px solid #e5e7eb;
        color: #374151;
        vertical-align: middle;
    }
    .os-products-body .table tbody tr:nth-child(even) td {
        background: #f9fafb;
    }
    .os-products-body .table tbody tr:hover td {
        background: #f0faf6;
    }

    /* ── Total row ────────────────────────────────────────── */
    .os-total-row {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        padding: 14px 36px;
        gap: 14px;
        border-top: 2px solid #e5e7eb;
    }
    .os-total-row .os-total-label {
        font-size: 13px;
        font-weight: 600;
        color: #6b7280;
    }
    .os-total-row .os-total-value {
        font-size: 20px;
        font-weight: 700;
        color: #00a876;
    }

    /* ── Footer / Signature ───────────────────────────────── */
    .os-doc-footer {
        padding: 20px 36px 28px;
        border-top: 1px solid #e5e7eb;
        background: #f8fafb;
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
    }
    .os-doc-footer .os-footer-brand {
        font-size: 11px;
        color: #9ca3af;
        font-weight: 500;
    }
    .os-doc-footer .os-footer-brand strong {
        color: #00a876;
        font-weight: 700;
    }
    .os-doc-footer .os-signature {
        text-align: right;
    }
    .os-doc-footer .os-signature p {
        margin: 0;
        font-size: 12px;
        color: #6b7280;
        line-height: 1.6;
    }
    .os-doc-footer .os-signature p.os-sig-name {
        font-weight: 600;
        color: #374151;
        font-size: 13px;
        margin-top: 4px;
    }

    /* ── PDF mode override ────────────────────────────────── */
    #printable.pdf-mode {
        width: 860px !important;
        max-width: 860px !important;
        margin: 0 !important;
        padding: 0 !important;
        background: white !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        position: relative !important;
        left: 0 !important;
        top: 0 !important;
    }
    #printable.pdf-mode .table {
        width: 100% !important;
        table-layout: auto;
    }
    #printable.pdf-mode .table th,
    #printable.pdf-mode .table td {
        font-size: 10.5px !important;
        padding: 7px 10px !important;
        word-wrap: break-word;
    }

    /* ── Print media ──────────────────────────────────────── */
    @media print {
        @page {
            size: A4 portrait;
            margin: 1cm;
        }

        .topbar, .left-sidebar, .sidebar, header, aside, footer,
        .page-titles, .navbar, .breadcrumb, .btn,
        #btn-imprimir, #btn-salvar-pdf, .os-actions {
            display: none !important;
        }

        body, .page-wrapper, .main-wrapper, .container-fluid, .col-md-12, .row {
            margin: 0 !important;
            padding: 0 !important;
            background-color: #fff !important;
        }

        #printable {
            position: relative !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            left: auto !important;
            top: auto !important;
            visibility: visible !important;
            border-radius: 0 !important;
            box-shadow: none !important;
        }

        body * { visibility: hidden; }
        #printable, #printable * { visibility: visible; }

        #printable .card { border: none !important; box-shadow: none !important; margin: 0 !important; padding: 0 !important; }
        #printable .card-body { padding: 0 !important; }
        .table-responsive { overflow: visible !important; }
        table { page-break-inside: auto; width: 100% !important; }
        tr { page-break-inside: avoid; page-break-after: auto; }
        thead { display: table-header-group; }

        .os-doc-topbar,
        .os-section-header .os-section-icon,
        .os-products-body .table thead tr th,
        .os-doc-header .os-number-badge {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }

    /* Tema escuro: só na tela (PDF captura com .pdf-mode permanece claro) */
    @media screen {
        html[data-pgm-theme="dark"] body {
            background: #0f1218 !important;
            color: #e8eaed;
        }
        html[data-pgm-theme="dark"] #printable:not(.pdf-mode) {
            background: #13161d !important;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.45);
        }
        html[data-pgm-theme="dark"] .btn-os-back {
            background: #1e2430 !important;
            border-color: #3d4554 !important;
            color: #e8eaed !important;
        }
        html[data-pgm-theme="dark"] .btn-os-back:hover {
            background: #252b38 !important;
            color: #f1f5f9 !important;
        }
        html[data-pgm-theme="dark"] #printable:not(.pdf-mode) .os-doc-header {
            border-bottom-color: #2d3544;
        }
        html[data-pgm-theme="dark"] #printable:not(.pdf-mode) .os-doc-header .os-title-block h1 {
            color: #e8eaed;
        }
        html[data-pgm-theme="dark"] #printable:not(.pdf-mode) .os-doc-header .os-title-block .os-subtitle {
            color: #9ca3af;
        }
        html[data-pgm-theme="dark"] #printable:not(.pdf-mode) .os-client-section {
            border-bottom-color: #2d3544;
        }
        html[data-pgm-theme="dark"] #printable:not(.pdf-mode) .os-info-grid {
            border-color: #3d4554;
        }
        html[data-pgm-theme="dark"] #printable:not(.pdf-mode) .os-info-cell {
            border-right-color: #3d4554;
            border-bottom-color: #3d4554;
        }
        html[data-pgm-theme="dark"] #printable:not(.pdf-mode) .os-info-cell .os-field-label {
            color: #8b93a0;
        }
        html[data-pgm-theme="dark"] #printable:not(.pdf-mode) .os-info-cell .os-field-value {
            color: #e8eaed;
        }
        html[data-pgm-theme="dark"] #printable:not(.pdf-mode) .os-section-header {
            background: #1a1f28;
            border-color: #2d3544;
        }
        html[data-pgm-theme="dark"] #printable:not(.pdf-mode) .os-section-header h3 {
            color: #c5cad3;
        }
        html[data-pgm-theme="dark"] #printable:not(.pdf-mode) .os-relato-body {
            color: #c5cad3;
            border-bottom-color: #2d3544;
        }
        html[data-pgm-theme="dark"] #printable:not(.pdf-mode) .os-relato-body .os-obs {
            background: rgba(245, 158, 11, 0.12);
            border-left-color: #f59e0b;
            color: #fcd34d;
        }
        html[data-pgm-theme="dark"] #printable:not(.pdf-mode) .os-products-body .table {
            border-color: #3d4554;
        }
        html[data-pgm-theme="dark"] #printable:not(.pdf-mode) .os-products-body .table thead tr th {
            background: #1e2430 !important;
            color: #c5cad3 !important;
            border-bottom-color: #1d9e75 !important;
        }
        html[data-pgm-theme="dark"] #printable:not(.pdf-mode) .os-products-body .table tbody tr td {
            border-top-color: #2d3544;
            color: #e8eaed;
        }
        html[data-pgm-theme="dark"] #printable:not(.pdf-mode) .os-products-body .table tbody tr:nth-child(even) td {
            background: #181c24 !important;
        }
        html[data-pgm-theme="dark"] #printable:not(.pdf-mode) .os-products-body .table tbody tr:nth-child(odd) td {
            background: #13161d !important;
        }
        html[data-pgm-theme="dark"] #printable:not(.pdf-mode) .os-products-body .table tbody tr:hover td {
            background: rgba(29, 158, 117, 0.12) !important;
        }
        html[data-pgm-theme="dark"] #printable:not(.pdf-mode) .os-total-row {
            border-top-color: #2d3544;
        }
        html[data-pgm-theme="dark"] #printable:not(.pdf-mode) .os-total-row .os-total-label {
            color: #8b93a0;
        }
        html[data-pgm-theme="dark"] #printable:not(.pdf-mode) .os-doc-footer {
            background: #1a1f28;
            border-top-color: #2d3544;
        }
        html[data-pgm-theme="dark"] #printable:not(.pdf-mode) .os-doc-footer .os-footer-brand {
            color: #6b7280;
        }
        html[data-pgm-theme="dark"] #printable:not(.pdf-mode) .os-doc-footer .os-signature p {
            color: #8b93a0;
        }
        html[data-pgm-theme="dark"] #printable:not(.pdf-mode) .os-doc-footer .os-signature p.os-sig-name {
            color: #e8eaed;
        }
    }
</style>

<div class="col-md-12">

    <!-- ── Barra de ações ───────────────────────────────────── -->
    <div class="os-actions">
        <a href="#" id="btn-salvar-pdf" class="btn btn-os-pdf">
            <i class="fas fa-file-pdf"></i> Salvar PDF
        </a>
        <a href="#" id="btn-imprimir" class="btn btn-os-print">
            <i class="fas fa-print"></i> Imprimir
        </a>
        <?= $this->Html->link(
            '<i class="fas fa-arrow-left"></i> Voltar para a Ordem',
            ['action' => 'edit', $ordem->id],
            ['class' => 'btn btn-os-back', 'escape' => false]
        ) ?>
    </div>

    <!-- ── Documento ─────────────────────────────────────────── -->
    <div id="printable">

        <!-- Barra accent topo -->
        <div class="os-doc-topbar"></div>

        <!-- Cabeçalho -->
        <div class="os-doc-header">
            <div class="os-logo">
                <img src="<?= $this->request->getAttribute('webroot') . 'assets/images/' . $logo ?>" alt="PGM Soluções em TI">
            </div>
            <div class="os-title-block">
                <h1>Ordem de Serviço</h1>
                <span class="os-subtitle">PGM Soluções em TI — ERP Enterprise</span>
            </div>
            <div class="os-number-badge">
                <div class="os-num-label">Nº da Ordem</div>
                <div class="os-num-value"><?= h($ordem->id) ?></div>
            </div>
        </div>

        <!-- Dados do cliente -->
        <div class="os-client-section">
            <div class="os-section-label">Dados do Cliente</div>
            <div class="os-info-grid">
                <!-- linha 1 -->
                <div class="os-info-cell os-info-cell--span2">
                    <div class="os-field-label">Cliente</div>
                    <div class="os-field-value">
                        <?= h($ordem->cliente->tipo == C_ClientesTipoJuridica ? $ordem->cliente->razaosocial : $ordem->cliente->nome) ?>
                    </div>
                </div>
                <div class="os-info-cell">
                    <div class="os-field-label"><?= $ordem->cliente->tipo == C_ClientesTipoJuridica ? 'CNPJ' : 'CPF' ?></div>
                    <div class="os-field-value">
                        <?= h($ordem->cliente->tipo == C_ClientesTipoJuridica ? formatCnpjCpf($ordem->cliente->cnpj) : formatCnpjCpf($ordem->cliente->cpf)) ?>
                    </div>
                </div>
                <!-- linha 2 -->
                <div class="os-info-cell os-info-cell--span2">
                    <div class="os-field-label">Endereço</div>
                    <div class="os-field-value">
                        <?= h($ordem->cliente->endereco . ', ' . $ordem->cliente->nroendereco . ' — Bairro ' . $ordem->cliente->bairro) ?>
                    </div>
                </div>
                <div class="os-info-cell">
                    <div class="os-field-label">CEP</div>
                    <div class="os-field-value"><?= h(Mask("#####-###", $ordem->cliente->cep)) ?></div>
                </div>
                <!-- linha 3 -->
                <div class="os-info-cell">
                    <div class="os-field-label">Telefone</div>
                    <div class="os-field-value">
                        <?php if (!empty($ordem->cliente->fone))  echo h(Mask("(###) ####-####",  $ordem->cliente->fone))  . '<br>'; ?>
                        <?php if (!empty($ordem->cliente->fone2)) echo h(Mask("(###) #####-####", $ordem->cliente->fone2)); ?>
                    </div>
                </div>
                <div class="os-info-cell">
                    <div class="os-field-label">Cidade</div>
                    <div class="os-field-value"><?= h($cidade) ?></div>
                </div>
                <div class="os-info-cell">
                    <div class="os-field-label">Previsão</div>
                    <div class="os-field-value"><?= date_format($ordem->dataprevisao, 'd/m/Y') ?></div>
                </div>
            </div>
        </div>

        <!-- Seção Relato -->
        <div class="os-section-header">
            <div class="os-section-icon"></div>
            <h3>Relato</h3>
        </div>
        <div class="os-relato-body">
            <?= nl2br(h($ordem->relato)) ?>
            <?php if (!empty($ordem->observacao)): ?>
                <div class="os-obs">
                    <strong>Observação:</strong> <?= h($ordem->observacao) ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Seção Produtos e Serviços -->
        <div class="os-section-header">
            <div class="os-section-icon"></div>
            <h3>Produtos e Serviços</h3>
        </div>
        <div class="os-products-body">
            <div class="table-responsive">
                <table class="table" id="tableCarrinho">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Código</th>
                            <th>Descrição</th>
                            <th>Observação</th>
                            <th>Unidade</th>
                            <th class="text-right">Qtde.</th>
                            <th class="text-right">Vl. Unit.</th>
                            <th class="text-right">Desconto</th>
                            <th class="text-right">Total</th>
                            <th>Serial</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($carrinho)): foreach ($carrinho as $reg): ?>
                        <tr id="<?= $reg->id ?>">
                            <td><?= ProdutosTipo($reg->tipo) ?></td>
                            <td><?= h($reg->codproduto) ?></td>
                            <td><?= h($reg->descricao) ?></td>
                            <td><?= h($reg->observacao) ?></td>
                            <td><?= h($reg->unidade) ?></td>
                            <td class="text-right"><?= h($reg->quantidade) ?></td>
                            <td class="text-right">R$&nbsp;<?= number_format($reg->valorunitario, 2, ',', '.') ?></td>
                            <td class="text-right">R$&nbsp;<?= number_format($reg->valordesconto,  2, ',', '.') ?></td>
                            <td class="text-right valordoservico">R$&nbsp;<?= number_format($reg->valortotal, 2, ',', '.') ?></td>
                            <td><?= h($reg->serialnumber) ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Total -->
        <div class="os-total-row valortotalh5">
            <span class="os-total-label">Total Geral</span>
            <span class="os-total-value valortotal"></span>
        </div>

        <!-- Rodapé / Assinatura -->
        <div class="os-doc-footer">
            <div class="os-footer-brand">
                Emitido por <strong>PGM Soluções em TI</strong><br>
                ERP Enterprise — <?= date('d/m/Y H:i') ?>
            </div>
            <div class="os-signature">
                <p>Bento Gonçalves, <?= @date_format($ordem->dataabertura, 'd') . ' de ' . descricaoMes($ordem->dataabertura, 1) . ' de ' . @date_format($ordem->dataabertura, 'Y') ?></p>
                <p>Atenciosamente,</p>
                <p class="os-sig-name"><?= h($ordem->user->name) ?></p>
            </div>
        </div>

    </div><!-- /#printable -->
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>


<script>
    function numberToReal(numero) {
        if (!isNaN(numero)) {
            var numero = numero.toFixed(2).split('.');
            numero[0] = numero[0].split(/(?=(?:...)*$)/).join('.');
            return numero.join(',');
        }
    }

    function valortotal() {
        var total = 0;
        $('.valordoservico').each(function() {
            var valor = $(this).text().replace('R$', '').trim().replaceAll('.', '').replaceAll(',', '.');
            total += parseFloat(valor) || 0;
        });
        if ($('.valordoservico').length > 0) {
            $('.valortotalh5').show();
            $('.valortotal').text('R$ ' + numberToReal(total));
        }
    }

    valortotal();

    // Função para gerar e baixar PDF

    function gerarPDF() {
        const elemento = document.getElementById('printable');
        const tituloOriginal = document.title;
        document.title = "Ordem_Servico_<?= $ordem->id ?>";
        elemento.classList.add('pdf-mode');

        html2canvas(elemento, {
            scale: 2,
            backgroundColor: '#ffffff',
            allowTaint: false,
            useCORS: true,
            windowWidth: 850,
            logging: false,
            onclone: function(clonedDoc) {
                const clonedElement = clonedDoc.getElementById('printable');
                clonedElement.style.margin = '0';
                clonedElement.style.padding = '15px';
                clonedElement.style.position = 'relative';
                clonedElement.style.top = '0';
                clonedElement.style.left = '0';
            }
        }).then(canvas => {
            elemento.classList.remove('pdf-mode');

            const imgData = canvas.toDataURL('image/png');

            const pdf = new jspdf.jsPDF({
                orientation: 'portrait',
                unit: 'px',
                format: 'a4',
                hotfixes: ["px_scaling"] 
            });

            const pdfWidth = pdf.internal.pageSize.getWidth();
            const pdfHeight = pdf.internal.pageSize.getHeight();
            const imgWidth = canvas.width;
            const imgHeight = canvas.height;
            const scale = pdfWidth / imgWidth;
            const height = imgHeight * scale;

            if (height > pdfHeight) {
                const scaleHeight = pdfHeight / imgHeight;
                const width = imgWidth * scaleHeight;
                const x = (pdfWidth - width) / 2;

                pdf.addImage(imgData, 'PNG', x, 0, width, pdfHeight);
            } else {
                pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, height);
            }

            pdf.save(`Ordem_Servico_<?= $ordem->id ?>.pdf`);
            document.title = tituloOriginal;
        }).catch(error => {
            console.error('Erro:', error);
            alert('Erro ao gerar PDF. Tente novamente.');
            elemento.classList.remove('pdf-mode');
        });
    }


    $('#btn-imprimir').click(function(e) {
        e.preventDefault();
        setTimeout(function() { window.print(); }, 300);
    });

    // Novo botão para salvar PDF
    $('#btn-salvar-pdf').click(function(e) {
        e.preventDefault();
        gerarPDF();
    });
</script>