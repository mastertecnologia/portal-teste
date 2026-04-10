<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Módulo Fiscal', ['controller' => 'Fiscal', 'action' => 'index']);
$this->Breadcrumbs->add('Contingência');
echo $this->element('Fiscal/styles');
$modoAtual = $modoAtual ?? null;
$modos = $modos ?? [];
$configFiscal = $configFiscal ?? null;
?>
<div class="fpm-wrap">
    <div class="fpm-topbar">
        <h1 class="fpm-h1"><i class="fas fa-exclamation-triangle"></i> Contingência Fiscal</h1>
        <div class="fpm-actions">
            <?= $this->Html->link('Painel', ['action' => 'index'], ['class' => 'btn btn-default btn-sm']) ?>
        </div>
    </div>

    <?= $this->element('Fiscal/regime_context') ?>

    <div class="fpm-card mx-3">
        <div class="fpm-card-title">Modo Atual</div>
        <?php if ($modoAtual === null): ?>
            <div class="fpm-alert fpm-alert-info">
                <strong>Normal</strong> — emissão pelo autorizador padrão (SVRS).
            </div>
        <?php else: ?>
            <div class="fpm-alert fpm-alert-danger">
                <strong>Contingência ativa:</strong> <?= h($modos[$modoAtual] ?? $modoAtual) ?>
                <?php if ($configFiscal && $configFiscal->contingencia_inicio): ?>
                    <br><small>Ativada em: <?= h($configFiscal->contingencia_inicio->format('d/m/Y H:i')) ?></small>
                <?php endif; ?>
                <?php if ($configFiscal && $configFiscal->contingencia_justificativa): ?>
                    <br><small>Motivo: <?= h($configFiscal->contingencia_justificativa) ?></small>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="fpm-card mx-3 mt-2">
        <div class="fpm-card-title">Alterar Modo</div>
        <?= $this->Form->create(null, ['url' => ['action' => 'contingencia']]) ?>
        <div class="fpm-row">
            <div class="fpm-field" style="flex:1;">
                <label>Modo de contingência</label>
                <select name="modo" class="form-control" id="fpmContModo">
                    <?php foreach ($modos as $val => $label): ?>
                    <option value="<?= h($val) ?>" <?= ($val === ($modoAtual ?? '')) ? 'selected' : '' ?>><?= h($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="fpm-row mt-2" id="fpmContJustWrap">
            <div class="fpm-field" style="flex:1;">
                <label>Justificativa (mín. 15 caracteres)</label>
                <textarea name="justificativa" class="form-control" rows="3" placeholder="Motivo da ativação da contingência"><?= h($configFiscal->contingencia_justificativa ?? '') ?></textarea>
            </div>
        </div>
        <div class="mt-2">
            <?= $this->Form->button('<i class="fas fa-save"></i> Aplicar', [
                'class' => 'btn btn-pgm btn-pgm-salvar btn-sm', 'escape' => false, 'type' => 'submit',
            ]) ?>
        </div>
        <?= $this->Form->end() ?>
    </div>

    <div class="fpm-card mx-3 mt-2">
        <div class="fpm-card-title">Sobre os modos</div>
        <table class="fpm-table" style="max-width:700px;">
            <tbody>
                <tr><td><strong>Normal</strong></td><td>Autorizador padrão (SVRS). Modo recomendado.</td></tr>
                <tr><td><strong>SVC-RS</strong></td><td>SEFAZ Virtual de Contingência (RS). Quando o autorizador estadual está fora do ar. Autoriza normalmente.</td></tr>
                <tr><td><strong>SVC-AN</strong></td><td>SEFAZ Virtual de Contingência (AN). Alternativa ao SVC-RS.</td></tr>
                <tr><td><strong>EPEC</strong></td><td>Evento Prévio de Emissão em Contingência. Registra dados mínimos na Receita; a NF-e deve ser transmitida quando o serviço voltar.</td></tr>
            </tbody>
        </table>
    </div>
</div>
<script>
(function() {
    var sel = document.getElementById('fpmContModo');
    var wrap = document.getElementById('fpmContJustWrap');
    if (!sel || !wrap) return;
    function sync() { wrap.style.display = sel.value === '' ? 'none' : ''; }
    sel.addEventListener('change', sync);
    sync();
})();
</script>
