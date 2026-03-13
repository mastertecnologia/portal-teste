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
<link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet">
<style>
    body {
        font-family: 'Open Sans', sans-serif;
        background: white;
    }

    .table td,
    .table th {
        padding: 0.7rem;
        vertical-align: top;
        border-top: 1px solid #dee2e6;
        font-size: 12px;
    }

    .titulo {
        background-color: #343a40 !important;
        color: white !important;
        text-align: center;
        padding: 8px;
        margin: 10px 0;
    }

    #printable.pdf-mode {
        width: 850px !important;
        max-width: 850px !important;
        margin: 0 !important;
        padding: 15px !important;
        background: white !important;
        /* Garante que o elemento não herde deslocamentos da tela */
        position: relative !important;
        left: 0 !important;
        top: 0 !important;
    }

    #printable.pdf-mode .table {
        width: 100% !important;
        table-layout: fixed;
        /* Evita que a tabela "estique" para fora */
    }

    #printable.pdf-mode .table th,
    #printable.pdf-mode .table td {
        font-size: 11px !important;
        padding: 5px !important;
        word-wrap: break-word;
    }

    @media print {
        @page {
            size: A4 portrait;
            margin: 1cm;
        }

        .topbar,
        .left-sidebar,
        .sidebar,
        header,
        aside,
        footer,
        .page-titles,
        .navbar,
        .breadcrumb,
        .btn,
        #btn-imprimir,
        #btn-salvar-pdf {
            display: none !important;
        }

        body,
        .page-wrapper,
        .main-wrapper,
        .container-fluid,
        .col-md-12,
        .row {
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
        }

        body * {
            visibility: hidden;
        }

        #printable,
        #printable * {
            visibility: visible;
        }

        #printable .card {
            border: none !important;
            box-shadow: none !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        #printable .card-body {
            padding: 0 !important;
        }

        .table-responsive {
            overflow: visible !important;
        }

        table {
            page-break-inside: auto;
            width: 100% !important;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        thead {
            display: table-header-group;
        }

        .titulo {
            background-color: #343a40 !important;
            color: white !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }
</style>

<div class="col-md-12">
    <?= $this->html->Link('Salvar PDF', [], ['id' => 'btn-salvar-pdf', 'class' => 'btn btn-success m-l-5 m-b-5']) ?>
    <?= $this->html->Link('Imprimir', [], ['id' => 'btn-imprimir', 'class' => 'btn btn-orange m-l-5 m-b-5']) ?>
    <?= $this->Html->link('Voltar para a Ordem', ["action" => "edit", $ordem->id], ['class' => 'm-b-5 btn btn-info']); ?>

    <div id="printable">
        <div class="card">
            <div class="card-body">
                <h2 class='titulo bg-dark text-white text-center p-2'> Ordem de Serviço </h2><br>
                <div class="row">
                    <div class="col-2 text-center">
                        <img src="<?= $this->request->getAttribute('webroot') . 'assets/images/' . $logo ?>" alt="homepage" style='width: 100px' class='m-t-10'>
                    </div>

                    <div class="col-4">
                        <table class="table m-t-10">
                            <tbody>
                                <tr>
                                    <th width='30%' class='text-left'>Cliente</th>
                                    <td class='text-left' style="word-break: break-word;">
                                        <?= $ordem->cliente->tipo == C_ClientesTipoJuridica ? $ordem->cliente->razaosocial : $ordem->cliente->nome ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th width='30%' class='text-left'>Nº da Ordem</th>
                                    <td class='text-left'> <?= $ordem->id ?></td>
                                </tr>
                                <tr>
                                    <th width='30%' class='text-left'>Previsão</th>
                                    <td class='text-left'> <?= date_format($ordem->dataprevisao, "d/m/Y"); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-3 text-center">
                        <table class="table m-t-10">
                            <tbody>
                                <tr>
                                    <th width='30%' class='text-left'><?= $ordem->cliente->tipo == C_ClientesTipoJuridica ? 'CNPJ' : 'CPF' ?></th>
                                    <td class='text-left'> <?= $ordem->cliente->tipo == C_ClientesTipoJuridica ? formatCnpjCpf($ordem->cliente->cnpj) : formatCnpjCpf($ordem->cliente->cpf) ?></td>
                                </tr>
                                <tr>
                                    <th width='30%' class='text-left'>Endereço</th>
                                    <td class='text-left'> <?= $ordem->cliente->endereco . ' - ' . $ordem->cliente->nroendereco . ' Bairro: ' . $ordem->cliente->bairro ?></td>
                                </tr>
                                <tr>
                                    <th width='30%' class='text-left'>CEP</th>
                                    <td class='text-left'> <?= Mask("#####-###", $ordem->cliente->cep) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-3 text-center">
                        <table class="table m-t-10">
                            <tbody>
                                <tr>
                                    <th width='30%' class='text-left'>Telefone</th>
                                    <td class='text-left'><?php if (!empty($ordem->cliente->fone)) echo Mask("(###) ####-####", $ordem->cliente->fone) . '<br>';
                                                            if (!empty($ordem->cliente->fone2)) echo Mask("(###) #####-####", $ordem->cliente->fone2) ?></td>
                                </tr>
                                <tr>
                                    <th width='30%' class='text-left'>Cidade</th>
                                    <td class='text-left'> <?= $cidade ?></td>
                                </tr>
                                <tr>
                                    <th width='30%' class='text-left'>Estado</th>
                                    <td class='text-left'> <?= $estado ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <br>
                <div class="row">
                    <div class="col-12">
                        <h3 class='titulo bg-dark text-white text-center p-2'> Relato </h3>
                        <h5 class='m-l-40 m-r-40'><?= $ordem->relato ?></h5>
                        <?php if ($ordem->observacao != null) { ?>
                            <h5 class='m-l-40 m-r-40'><?= 'Observação: ' . $ordem->observacao ?></h5>
                        <?php } ?>
                    </div>
                </div>
                <br>
                <div class="row">
                    <div class="col-12">
                        <h3 class='titulo bg-dark text-white text-center p-2'> Produtos e Serviços </h3><br>
                    </div>
                </div>
                <!-- Tabela -->
                <div class="table-responsive">
                    <table class="table" id="tableCarrinho">
                        <thead class="text-primary">
                            <th>Tipo</th>
                            <th>Código</th>
                            <th>Descrição</th>
                            <th>Observação</th>
                            <th>Unidade</th>
                            <th width="7%" class="text-right">Qtde.</th>
                            <th width="7%" class="text-right">Vl. Unitário</th>
                            <th width="7%" class="text-right">Vl. Desconto</th>
                            <th width="7%" class="text-right">Valor Total</th>
                            <th>Serial Number</th>
                        </thead>
                        <tbody>
                            <!-- Serviços -->
                            <?php if (isset($carrinho)) {
                                foreach ($carrinho as $reg) { ?>
                                    <tr id='<?= $reg->id ?>'>
                                        <td><?= ProdutosTipo($reg->tipo) ?></td>
                                        <td><?= $reg->codproduto ?></td>
                                        <td><?= $reg->descricao ?></td>
                                        <td><?= $reg->observacao ?></td>
                                        <td><?= $reg->unidade ?></td>
                                        <td class="text-right"><?= $reg->quantidade ?></td>
                                        <td class="text-right"><?= 'R$ ' . number_format($reg->valorunitario, 2, ",", ".") ?></td>
                                        <td class="text-right"><?= 'R$ ' . number_format($reg->valordesconto, 2, ",", ".") ?></td>
                                        <td class="text-right valordoservico"><?= 'R$ ' . number_format($reg->valortotal, 2, ",", ".") ?></td>
                                        <td><?= $reg->serialnumber ?></td>
                                    </tr>
                            <?php }
                            } ?>
                            <!-- Fim Serviços -->
                        </tbody>
                    </table>
                </div>
                <!-- valortotal que é exibido -->
                <h5 class="text-right font-weight-bold m-r-15 hide valortotalh5"> Total Geral: <span class="text-success valortotal"> </span></h5>
            </div>
            <p style="width:1000px"></p>
            <br>
            <div class="float-right">
                <p class='m-b-0 text-right'>Bento Gonçalves, <?= @date_format($ordem->dataabertura, 'd') . ' de ' . descricaoMes($ordem->dataabertura, 1) . ' de ' . @date_format($ordem->dataabertura, 'Y') ?></p>
                <p class='m-b-0 text-right'>Obrigado pela sua atenção,</p>
                <p class='m-b-0 text-right'><?= $ordem->user->name ?></p>
            </div>
        </div>
    </div>
</div>
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
        valortotal = 0;
        $('.valordoservico').each(function() {
            valor = $(this).text();
            valor = valor.split('R$').join('');
            valor = valor.replaceAll(".", "").replaceAll(",", ".");

            valortotal = valortotal + parseFloat(valor);
            $('.valortotalh5').show();
        });
        $(".valortotal").html('R$ ' + numberToReal(valortotal));
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
        // Prepara o layout
        $('.titulo').removeClass('bg-dark').removeClass('text-white');
        $("#printable").removeClass('printMini');

        setTimeout(function() {
            window.print();
            $('.titulo').addClass('bg-dark').addClass('text-white');
        }, 500);
    });

    // Novo botão para salvar PDF
    $('#btn-salvar-pdf').click(function(e) {
        e.preventDefault();
        gerarPDF();
    });
</script>