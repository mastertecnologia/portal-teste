<?php
$fpmCtrl = $this->request->getParam('controller');
$isEntrada = ($fpmCtrl === 'FiscalNotasEntrada');
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Módulo Fiscal', ['controller' => 'Fiscal', 'action' => 'index']);
$this->Breadcrumbs->add($isEntrada ? 'Notas de entrada' : 'Notas de saída', ['controller' => $fpmCtrl, 'action' => 'index']);
$this->Breadcrumbs->add('Inutilizar numeração');
echo $this->element('Fiscal/styles');

$fiscalAmbienteProducao = $fiscalAmbienteProducao ?? false;
$modelos = $modelos ?? [];
?>
<div class="fpm-wrap">
    <?php if ($fiscalAmbienteProducao) : ?>
    <div class="fpm-homolog-wrap" style="padding-top:12px;">
        <div class="fpm-alert fpm-alert-danger">
            <strong>Ambiente de produção SEFAZ.</strong> A inutilização vale fiscalmente para a numeração indicada.
        </div>
    </div>
    <?php endif; ?>

    <div class="fpm-topbar">
        <h1 class="fpm-h1"><i class="fas fa-ban"></i>Inutilizar numeração</h1>
        <div class="fpm-actions">
            <?= $this->Html->link('Lista', ['controller' => $fpmCtrl, 'action' => 'index'], ['class' => 'btn btn-default btn-sm']) ?>
        </div>
    </div>

    <?= $this->element('Fiscal/regime_context') ?>

    <div class="fpm-card mx-3">
        <?php if (!empty($empresa)) : ?>
        <p class="mb-2"><span class="fpm-muted">Emitente</span> <?= h($empresa->razaosocial ?? $empresa->nomefantasia ?? '—') ?></p>
        <?php endif; ?>
        <p class="fpm-muted">Pedido de inutilização de faixa na SEFAZ (NF-e 55 ou NFC-e 65). Os XMLs de envio/retorno são gravados em <code>storage/fiscal/xml/inutilizacao</code> quando configurado.</p>

        <?= $this->Form->create(null, ['class' => 'fpm-inut-form']) ?>
        <div class="fpm-row">
            <div class="fpm-field">
                <label>Modelo</label>
                <?= $this->Form->select('modelo', [
                    '55' => ($modelos['55'] ?? '55') . ' — NF-e',
                    '65' => ($modelos['65'] ?? '65') . ' — NFC-e',
                ], ['class' => 'form-control', 'default' => '55', 'required' => true]) ?>
            </div>
            <div class="fpm-field">
                <label>Série</label>
                <?= $this->Form->control('serie', [
                    'type' => 'number', 'class' => 'form-control', 'min' => 0, 'max' => 999,
                    'value' => 1, 'required' => true, 'label' => false,
                ]) ?>
            </div>
            <div class="fpm-field">
                <label>Ano referência (AA ou AAAA)</label>
                <?= $this->Form->control('ano_referencia', [
                    'type' => 'text', 'class' => 'form-control', 'placeholder' => date('Y'),
                    'label' => false, 'maxlength' => 4,
                ]) ?>
                <small class="fpm-muted">Vazio = ano corrente (últimos 2 dígitos no XML).</small>
            </div>
        </div>
        <div class="fpm-row">
            <div class="fpm-field">
                <label>Número inicial</label>
                <?= $this->Form->control('numero_inicial', [
                    'type' => 'number', 'class' => 'form-control', 'min' => 1, 'required' => true, 'label' => false,
                ]) ?>
            </div>
            <div class="fpm-field">
                <label>Número final</label>
                <?= $this->Form->control('numero_final', [
                    'type' => 'number', 'class' => 'form-control', 'min' => 1, 'required' => true, 'label' => false,
                ]) ?>
            </div>
        </div>
        <div class="fpm-field">
            <label>Justificativa (mín. 15 caracteres)</label>
            <?= $this->Form->textarea('justificativa', ['class' => 'form-control', 'rows' => 3, 'required' => true, 'minlength' => 15]) ?>
        </div>
        <?php if ($fiscalAmbienteProducao) : ?>
        <div class="fpm-field mb-3">
            <label class="fpm-check-prod mb-0">
                <?= $this->Form->checkbox('confirmar_producao', ['value' => '1', 'required' => true, 'id' => 'fpmConfirmInut']) ?>
                <span>Confirmo o pedido de <strong>inutilização</strong> em produção SEFAZ.</span>
            </label>
        </div>
        <?php endif; ?>
        <?= $this->Form->button('<i class="fas fa-paper-plane"></i> Enviar à SEFAZ', [
            'class' => 'btn btn-pgm btn-pgm-salvar', 'escape' => false, 'type' => 'submit',
        ]) ?>
        <?= $this->Form->end() ?>
    </div>
</div>
