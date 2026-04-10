<?php
/**
 * E-mail: NF-e autorizada — DANFE + XML em anexo.
 *
 * Variáveis: $empresaNome, $numero, $serie, $modelo, $valorTotal,
 *            $chaveAcesso, $dataEmissao, $clienteNome, $protocolo
 */
$empresaNome = $empresaNome ?? 'Empresa';
$numero = $numero ?? '-';
$serie = $serie ?? '-';
$modelo = $modelo ?? '55';
$valorTotal = $valorTotal ?? '0,00';
$chaveAcesso = $chaveAcesso ?? '';
$dataEmissao = $dataEmissao ?? '-';
$clienteNome = $clienteNome ?? '-';
$protocolo = $protocolo ?? '';
?>
<div style="font-family:Arial,Helvetica,sans-serif;max-width:600px;margin:0 auto;color:#333;">
    <div style="background:#1D9E75;padding:16px 20px;border-radius:6px 6px 0 0;">
        <h2 style="margin:0;color:#fff;font-size:18px;">
            <?= h($empresaNome) ?> &mdash; Nota Fiscal Eletr&ocirc;nica
        </h2>
    </div>

    <div style="border:1px solid #ddd;border-top:none;padding:20px;border-radius:0 0 6px 6px;">
        <p style="margin:0 0 14px;">Prezado(a) <strong><?= h($clienteNome) ?></strong>,</p>

        <p style="margin:0 0 14px;">
            Segue em anexo a <strong>DANFE</strong> (PDF) e o <strong>XML</strong> da nota fiscal eletr&ocirc;nica autorizada:
        </p>

        <table style="width:100%;border-collapse:collapse;margin-bottom:16px;font-size:14px;">
            <tr>
                <td style="padding:6px 10px;border:1px solid #eee;background:#f9f9f9;width:140px;"><strong>N&uacute;mero</strong></td>
                <td style="padding:6px 10px;border:1px solid #eee;"><?= h($numero) ?></td>
            </tr>
            <tr>
                <td style="padding:6px 10px;border:1px solid #eee;background:#f9f9f9;"><strong>S&eacute;rie</strong></td>
                <td style="padding:6px 10px;border:1px solid #eee;"><?= h($serie) ?></td>
            </tr>
            <tr>
                <td style="padding:6px 10px;border:1px solid #eee;background:#f9f9f9;"><strong>Modelo</strong></td>
                <td style="padding:6px 10px;border:1px solid #eee;"><?= h($modelo) ?></td>
            </tr>
            <tr>
                <td style="padding:6px 10px;border:1px solid #eee;background:#f9f9f9;"><strong>Valor Total</strong></td>
                <td style="padding:6px 10px;border:1px solid #eee;">R$ <?= h($valorTotal) ?></td>
            </tr>
            <tr>
                <td style="padding:6px 10px;border:1px solid #eee;background:#f9f9f9;"><strong>Emiss&atilde;o</strong></td>
                <td style="padding:6px 10px;border:1px solid #eee;"><?= h($dataEmissao) ?></td>
            </tr>
            <?php if ($protocolo !== ''): ?>
            <tr>
                <td style="padding:6px 10px;border:1px solid #eee;background:#f9f9f9;"><strong>Protocolo</strong></td>
                <td style="padding:6px 10px;border:1px solid #eee;"><?= h($protocolo) ?></td>
            </tr>
            <?php endif; ?>
            <?php if ($chaveAcesso !== ''): ?>
            <tr>
                <td style="padding:6px 10px;border:1px solid #eee;background:#f9f9f9;"><strong>Chave de Acesso</strong></td>
                <td style="padding:6px 10px;border:1px solid #eee;word-break:break-all;font-size:12px;"><?= h($chaveAcesso) ?></td>
            </tr>
            <?php endif; ?>
        </table>

        <p style="margin:0 0 10px;font-size:13px;color:#666;">
            Guarde o arquivo XML pelo prazo m&iacute;nimo de 5 anos conforme legisla&ccedil;&atilde;o fiscal vigente.
        </p>

        <p style="margin:0;font-size:13px;color:#999;">
            Este e-mail foi gerado automaticamente. Em caso de d&uacute;vidas, entre em contato com <?= h($empresaNome) ?>.
        </p>
    </div>
</div>
