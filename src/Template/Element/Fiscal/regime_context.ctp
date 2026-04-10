<?php
/**
 * Contexto de regime tributário da empresa (add/edit nota).
 *
 * @var string $fiscalRegimeLabel
 * @var bool $fiscalConfigRegimeIncomplete
 * @var string|null $fiscalRegimeNormalEnquadLabel
 */
$fiscalRegimeLabel = $fiscalRegimeLabel ?? '';
$fiscalConfigRegimeIncomplete = !empty($fiscalConfigRegimeIncomplete);
$fiscalRegimeNormalEnquadLabel = $fiscalRegimeNormalEnquadLabel ?? null;
if ($fiscalRegimeLabel === '') {
    return;
}
?>
<div class="px-3 mb-2">
    <?php if ($fiscalConfigRegimeIncomplete) : ?>
        <div class="alert alert-warning mb-0 py-2">
            Regime <strong>Normal (CRT 3)</strong> sem enquadramento Lucro presumido / Lucro real.
            <?= $this->Html->link('Abrir configuração fiscal', ['controller' => 'FiscalConfig', 'action' => 'index'], ['class' => 'alert-link']) ?>
            para o motor aplicar PIS/COFINS de referência corretos.
        </div>
    <?php else : ?>
        <p class="text-muted small mb-0">
            <i class="fas fa-info-circle"></i>
            Empresa: <?= h($fiscalRegimeLabel) ?>
            <?php if ($fiscalRegimeNormalEnquadLabel !== null && $fiscalRegimeNormalEnquadLabel !== '') : ?>
                — <?= h($fiscalRegimeNormalEnquadLabel) ?>
            <?php endif; ?>
        </p>
    <?php endif; ?>
</div>
