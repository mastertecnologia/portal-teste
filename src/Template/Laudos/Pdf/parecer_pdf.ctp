<?php
/**
 * Template HTML para geração de PDF via mPDF.
 * Variáveis: $parecer (entity), $totais (array), $qrUrl (string)
 */

$empresa = $parecer->laudos_empresa ?? null;
$produtos = $parecer->laudos_produtos ?? [];
$totalPecas = (float)($totais['total_pecas'] ?? 0);
$totalServicos = (float)($totais['total_servicos'] ?? 0);
$totalGeral = (float)($totais['total_geral'] ?? 0);
$totalNovo = (float)($totais['total_novo'] ?? $parecer->estimated_new_equipment ?? 0);
$percentual = $totais['percentual_reparo'] ?? null;

$fmtBrl = function($v) {
    return 'R$ ' . number_format((float)$v, 2, ',', '.');
};

$fmtDate = function($d) {
    if (!$d) return '—';
    $s = is_object($d) ? $d->format('d/m/Y') : date('d/m/Y', strtotime((string)$d));
    return $s;
};

/** Caminho relativo seguro sob uploads/laudos/ (evita .. e prefixos externos). */
$laudosUploadsFs = function (?string $relative): ?string {
    if ($relative === null || $relative === '') {
        return null;
    }
    $relative = str_replace('\\', '/', trim($relative));
    if ($relative === '' || strpos($relative, '..') !== false) {
        return null;
    }
    $prefix = 'uploads/laudos/';
    if (strpos($relative, $prefix) !== 0) {
        return null;
    }
    $fs = WWW_ROOT . str_replace('/', DS, $relative);

    return is_file($fs) ? $fs : null;
};
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10pt; color: #333; }
  h1 { font-size: 16pt; color: #1D9E75; margin-bottom: 4px; }
  h2 { font-size: 12pt; color: #1D9E75; border-bottom: 2px solid #1D9E75; padding-bottom: 4px; margin: 16px 0 8px; }
  h3 { font-size: 10pt; font-weight: bold; margin: 10px 0 4px; }

  .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 3px solid #1D9E75; }
  .header-empresa { font-size: 9pt; color: #555; }
  .header-empresa .nome { font-size: 13pt; font-weight: bold; color: #1D9E75; }

  .numero-box { text-align: right; }
  .numero { font-size: 22pt; font-weight: bold; color: #1D9E75; }
  .status-badge { display: inline-block; padding: 3px 10px; border-radius: 4px; font-size: 9pt; font-weight: bold; margin-top: 4px; }
  .status-rascunho { background: #e9ecef; color: #6c757d; }
  .status-em_analise { background: #fff3cd; color: #856404; }
  .status-aprovado { background: #cff4fc; color: #055160; }
  .status-concluido { background: #d1e7dd; color: #0a3622; }
  .status-enviado { background: #cfe2ff; color: #084298; }

  table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
  th { background: #f8f9fa; font-weight: bold; padding: 5px 8px; text-align: left; font-size: 9pt; border: 1px solid #dee2e6; }
  td { padding: 5px 8px; font-size: 9pt; border: 1px solid #dee2e6; vertical-align: top; }
  tr:nth-child(even) td { background: #f8f9fa; }

  .info-grid { display: table; width: 100%; }
  .info-row { display: table-row; }
  .info-label { display: table-cell; width: 35%; font-weight: bold; padding: 3px 0; font-size: 9pt; color: #555; }
  .info-value { display: table-cell; padding: 3px 0 3px 8px; font-size: 9pt; }

  .produto-card { border: 1px solid #dee2e6; border-radius: 6px; margin-bottom: 12px; page-break-inside: avoid; }
  .produto-header { background: #1D9E75; color: #fff; padding: 6px 10px; border-radius: 5px 5px 0 0; font-weight: bold; font-size: 10pt; }
  .produto-body { padding: 10px; }

  .total-box { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 6px; padding: 10px 14px; margin-top: 10px; }
  .total-line { display: flex; justify-content: space-between; padding: 3px 0; font-size: 9pt; border-bottom: 1px solid #e9ecef; }
  .total-line:last-child { border-bottom: none; font-weight: bold; font-size: 11pt; color: #1D9E75; }

  .text-section { text-align: justify; line-height: 1.5; font-size: 9.5pt; white-space: pre-wrap; }

  .assinatura-area { text-align: center; margin-top: 30px; page-break-inside: avoid; }
  .assinatura-linha { border-top: 1px solid #333; width: 280px; margin: 0 auto 6px; }

  .qr-section { float: right; text-align: center; margin-left: 20px; }
  .qr-section p { font-size: 8pt; color: #777; margin-top: 4px; }

  .footer-page { position: fixed; bottom: 0; left: 0; right: 0; font-size: 8pt; color: #999; text-align: center; border-top: 1px solid #e9ecef; padding: 6px; }

  img.produto-img { width: 120px; height: 90px; object-fit: cover; border-radius: 4px; margin: 3px; }
</style>
</head>
<body>

<!-- Cabeçalho -->
<?php
$logoFs = ($empresa && !empty($empresa->logo_path)) ? $laudosUploadsFs($empresa->logo_path) : null;
$logoOk = $logoFs !== null && $logoFs !== '';
$carimboFs = ($empresa && !empty($empresa->carimbo_path)) ? $laudosUploadsFs($empresa->carimbo_path) : null;
$carimboOk = $carimboFs !== null && $carimboFs !== '';
?>
<div class="header">
  <div style="display:flex;gap:12px;align-items:flex-start;flex:1">
    <?php if ($logoOk): ?>
    <div style="flex-shrink:0">
      <img src="<?= h($logoFs) ?>" alt="Logo" style="max-height:52px;max-width:140px;object-fit:contain">
    </div>
    <?php endif; ?>
  <div class="header-empresa">
    <?php if ($empresa): ?>
      <div class="nome"><?= h($empresa->razao_social) ?></div>
      <div><?= h($empresa->cnpj) ?></div>
      <div><?= h($empresa->endereco) ?></div>
      <div><?= h($empresa->telefone) ?> | <?= h($empresa->email) ?></div>
    <?php endif; ?>
  </div>
  </div>
  <div class="numero-box">
    <div class="numero"><?= h($parecer->numero) ?></div>
    <div style="font-size:9pt;color:#777">Parecer Técnico</div>
    <div class="status-badge status-<?= h($parecer->status) ?>">
      <?= h($parecer->status_label) ?>
    </div>
  </div>
</div>

<h1>Parecer Técnico</h1>
<p style="font-size:9pt;color:#777;margin-bottom:16px"><?= h($parecer->titulo) ?></p>

<!-- Dados de emissão e requerente -->
<h2>Requerente</h2>
<div class="info-grid">
  <div class="info-row">
    <div class="info-label">Empresa / Nome</div>
    <div class="info-value"><?= h($parecer->requester_company_name ?: '—') ?></div>
  </div>
  <div class="info-row">
    <div class="info-label">CNPJ / CPF</div>
    <div class="info-value"><?= h($parecer->requester_cnpj ?: '—') ?></div>
  </div>
  <?php if ($parecer->requester_attention_to): ?>
  <div class="info-row">
    <div class="info-label">A/C</div>
    <div class="info-value"><?= h($parecer->requester_attention_to) ?></div>
  </div>
  <?php endif; ?>
  <div class="info-row">
    <div class="info-label">Endereço</div>
    <div class="info-value"><?= h($parecer->requester_address ?: '—') ?></div>
  </div>
  <div class="info-row">
    <div class="info-label">Telefone / E-mail</div>
    <div class="info-value"><?= h(trim(($parecer->requester_phone ? $parecer->requester_phone . ' | ' : '') . ($parecer->requester_email ?: ''))) ?: '—' ?></div>
  </div>
</div>

<!-- Emissão -->
<h2>Dados de emissão</h2>
<div class="info-grid">
  <div class="info-row">
    <div class="info-label">Número</div>
    <div class="info-value"><?= h($parecer->numero) ?></div>
  </div>
  <div class="info-row">
    <div class="info-label">Data de emissão</div>
    <div class="info-value"><?= $fmtDate($parecer->data_emissao) ?></div>
  </div>
  <div class="info-row">
    <div class="info-label">Técnico responsável</div>
    <div class="info-value"><?= h($parecer->tecnico_nome ?: '—') ?><?= $parecer->tecnico_registro ? ' — ' . h($parecer->tecnico_registro) : '' ?></div>
  </div>
  <div class="info-row">
    <div class="info-label">Local</div>
    <div class="info-value"><?= h($parecer->cidade ?: '—') ?></div>
  </div>
</div>

<!-- Objetivo -->
<?php if ($parecer->objetivo): ?>
<h2>Objetivo</h2>
<div class="text-section"><?= h($parecer->objetivo) ?></div>
<?php endif; ?>

<!-- Equipamentos -->
<h2>Equipamentos avaliados</h2>

<?php foreach ($produtos as $i => $produto): ?>
<div class="produto-card">
  <div class="produto-header">
    <?= ($i + 1) ?>. <?= h($produto->nome ?: 'Equipamento ' . ($i + 1)) ?>
    <?php if ($produto->tipo): ?> — <span style="font-weight:normal;font-size:9pt"><?= h($produto->tipo) ?></span><?php endif; ?>
    <?php if ($produto->serial_number): ?> — S/N: <?= h($produto->serial_number) ?><?php endif; ?>
  </div>
  <div class="produto-body">

    <!-- Diagnóstico -->
    <?php if ($produto->diagnostico): ?>
    <h3>Diagnóstico</h3>
    <div class="text-section"><?= h($produto->diagnostico) ?></div>
    <?php endif; ?>

    <!-- Imagens -->
    <?php if (!empty($produto->laudos_produto_imagens)): ?>
    <div style="margin-top:8px">
      <?php foreach ($produto->laudos_produto_imagens as $img): ?>
        <?php
          $imgPath = $laudosUploadsFs($img->file_path ?? '');
          if ($imgPath !== null):
        ?>
        <img class="produto-img" src="<?= h($imgPath) ?>" alt="Foto do equipamento">
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Peças -->
    <?php if (!empty($produto->laudos_produto_pecas)): ?>
    <h3>Peças / Componentes</h3>
    <table>
      <tr>
        <th>Descrição</th>
        <th style="width:60px;text-align:center">Qtd</th>
        <th style="width:90px;text-align:right">Unitário</th>
        <th style="width:90px;text-align:right">Subtotal</th>
      </tr>
      <?php foreach ($produto->laudos_produto_pecas as $peca): ?>
      <tr>
        <td><?= h($peca->nome) ?></td>
        <td style="text-align:center"><?= h($peca->quantidade) ?></td>
        <td style="text-align:right"><?= $fmtBrl($peca->preco_unitario) ?></td>
        <td style="text-align:right"><?= $fmtBrl((float)$peca->quantidade * (float)$peca->preco_unitario) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>

    <!-- Serviços -->
    <?php if (!empty($produto->laudos_produto_servicos)): ?>
    <h3>Serviços</h3>
    <table>
      <tr>
        <th>Descrição</th>
        <th style="width:60px;text-align:center">Horas</th>
        <th style="width:90px;text-align:right">Valor/hora</th>
        <th style="width:90px;text-align:right">Subtotal</th>
      </tr>
      <?php foreach ($produto->laudos_produto_servicos as $srv): ?>
      <tr>
        <td><?= h($srv->descricao) ?></td>
        <td style="text-align:center"><?= h($srv->horas) ?></td>
        <td style="text-align:right"><?= $fmtBrl($srv->valor_hora) ?></td>
        <td style="text-align:right"><?= $fmtBrl((float)$srv->horas * (float)$srv->valor_hora) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>

    <!-- Recomendação -->
    <?php
    $recLabels = ['repair' => 'Reparar', 'replace' => 'Substituir', 'partial' => 'Parcial'];
    $recLabel = $recLabels[$produto->recomendacao] ?? $produto->recomendacao;
    ?>
    <p style="margin-top:6px;font-size:9pt">
      <strong>Recomendação:</strong> <?= h($recLabel) ?>
    </p>

  </div>
</div>
<?php endforeach; ?>

<!-- Resumo financeiro -->
<h2>Resumo financeiro</h2>
<div class="total-box">
  <div class="total-line"><span>Total em peças</span><span><?= $fmtBrl($totalPecas) ?></span></div>
  <div class="total-line"><span>Total em serviços</span><span><?= $fmtBrl($totalServicos) ?></span></div>
  <div class="total-line"><span>Total estimado do reparo</span><span><?= $fmtBrl($totalGeral) ?></span></div>
  <?php if ($parecer->show_comparison && $totalNovo > 0): ?>
  <div class="total-line" style="font-size:9pt;font-weight:normal;color:#333">
    <span>Valor equipamento novo equivalente</span><span><?= $fmtBrl($totalNovo) ?></span>
  </div>
  <?php if ($percentual !== null): ?>
  <div class="total-line" style="font-size:9pt;font-weight:normal;color:#333">
    <span>Reparo representa</span><span><?= number_format((float)$percentual, 1, ',', '.') ?>% do novo</span>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<!-- Conclusão -->
<?php if ($parecer->conclusao): ?>
<h2>Conclusão</h2>
<div class="text-section"><?= h($parecer->conclusao) ?></div>
<?php endif; ?>

<!-- Documentação considerada -->
<?php if ($parecer->documentacao): ?>
<h2>Documentação considerada</h2>
<div class="text-section"><?= h($parecer->documentacao) ?></div>
<?php endif; ?>

<!-- Assinatura e QR Code -->
<div style="margin-top:30px;overflow:hidden">

  <?php if (!empty($qrUrl)): ?>
  <div class="qr-section">
    <!-- QR Code gerado inline via URL do Google Charts (fallback simples) -->
    <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?= urlencode($qrUrl) ?>"
         width="100" height="100" alt="QR Code de validação">
    <p>Validar em:<br><?= h($qrUrl) ?></p>
  </div>
  <?php endif; ?>

  <div class="assinatura-area" style="float:left">
    <?php if ($carimboOk): ?>
    <div style="margin-bottom:10px">
      <img src="<?= h($carimboFs) ?>" alt="Carimbo" style="max-height:72px;max-width:200px;object-fit:contain">
    </div>
    <?php endif; ?>
    <?php if ($parecer->assinatura_path): ?>
      <?php
      $sigRaw = (string)$parecer->assinatura_path;
      if (strpos($sigRaw, 'data:image/') === 0): ?>
      <img src="<?= h($sigRaw) ?>" style="height:60px;margin-bottom:8px" alt="Assinatura">
      <?php else:
      $sigFs = $laudosUploadsFs($sigRaw);
      if ($sigFs !== null): ?>
      <img src="<?= h($sigFs) ?>" style="height:60px;margin-bottom:8px" alt="Assinatura">
      <?php endif;
      endif; ?>
    <?php endif; ?>
    <div class="assinatura-linha"></div>
    <div style="font-size:9.5pt"><?= h($parecer->tecnico_nome ?: 'Técnico Responsável') ?></div>
    <?php if ($parecer->tecnico_registro): ?>
    <div style="font-size:8.5pt;color:#777"><?= h($parecer->tecnico_registro) ?></div>
    <?php endif; ?>
    <div style="font-size:8.5pt;color:#777;margin-top:6px">
      <?= h($parecer->cidade ?: '') ?><?= $parecer->data_emissao ? ', ' . $fmtDate($parecer->data_emissao) : '' ?>
    </div>
  </div>

  <div style="clear:both"></div>
</div>

<div class="footer-page">
  Parecer Técnico nº <?= h($parecer->numero) ?> — Código de verificação: <?= h($parecer->public_hash) ?> —
  <?= $empresa ? h($empresa->razao_social) : '' ?>
</div>

</body>
</html>
