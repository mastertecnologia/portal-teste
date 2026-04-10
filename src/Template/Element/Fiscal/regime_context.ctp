<?php
/**
 * Contexto de regime tributário da empresa (módulo fiscal).
 *
 * @var string $fiscalRegimeLabel
 * @var bool $fiscalConfigRegimeIncomplete
 * @var string|null $fiscalRegimeNormalEnquadLabel
 */
$fiscalRegimeLabel = $fiscalRegimeLabel ?? '';
$fiscalConfigRegimeIncomplete = !empty($fiscalConfigRegimeIncomplete);
$fiscalRegimeNormalEnquadLabel = $fiscalRegimeNormalEnquadLabel ?? null;
$showReforma = \App\Utility\Fiscal\FiscalRegimeHelper::reformaTributariaEstudoIbscbsAtivo();
if ($fiscalRegimeLabel === '' && !$showReforma) {
    return;
}
$reformaExtraClass = ($fiscalRegimeLabel !== '') ? ' mt-2' : '';
?>
<div class="px-3 mb-2">
    <?php if ($fiscalRegimeLabel !== '') : ?>
        <?php if ($fiscalConfigRegimeIncomplete) : ?>
            <div class="fpm-alert fpm-alert-warn mb-0 py-2" role="alert">
                Regime <strong>Normal (CRT 3)</strong> sem enquadramento Lucro presumido / Lucro real.
                <?= $this->Html->link('Abrir configuração fiscal', ['controller' => 'FiscalConfig', 'action' => 'index'], ['style' => 'color:inherit;font-weight:600;text-decoration:underline;']) ?>
                para o motor aplicar PIS/COFINS de referência corretos.
            </div>
        <?php else : ?>
            <p class="fpm-muted small mb-0">
                <i class="fas fa-info-circle"></i>
                Empresa: <?= h($fiscalRegimeLabel) ?>
                <?php if ($fiscalRegimeNormalEnquadLabel !== null && $fiscalRegimeNormalEnquadLabel !== '') : ?>
                    — <?= h($fiscalRegimeNormalEnquadLabel) ?>
                <?php endif; ?>
            </p>
        <?php endif; ?>
    <?php endif; ?>
    <?php if ($showReforma) : ?>
        <p class="fpm-muted small mb-0<?= $reformaExtraClass ?>">
            <i class="fas fa-flask"></i>
            Modo estudo reforma tributária (IBS/CBS): XML da NF-e segue apenas o leiaute vigente até NT oficial.
        </p>
    <?php endif; ?>
</div>
