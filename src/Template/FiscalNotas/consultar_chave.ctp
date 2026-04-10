<?php
$fpmCtrl = $this->request->getParam('controller');
$isEntrada = ($fpmCtrl === 'FiscalNotasEntrada');
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Módulo Fiscal', ['controller' => 'Fiscal', 'action' => 'index']);
$this->Breadcrumbs->add($isEntrada ? 'Notas de entrada' : 'Notas de saída', ['controller' => $fpmCtrl, 'action' => 'index']);
$this->Breadcrumbs->add('Consultar chave');
echo $this->element('Fiscal/styles');
$resultado = $resultado ?? null;
$chaveInput = $chaveInput ?? '';
?>
<div class="fpm-wrap">
    <div class="fpm-topbar">
        <h1 class="fpm-h1"><i class="fas fa-search"></i> Consultar NF-e por Chave de Acesso</h1>
        <div class="fpm-actions">
            <?= $this->Html->link('Voltar', ['controller' => $fpmCtrl, 'action' => 'index'], ['class' => 'btn btn-default btn-sm']) ?>
        </div>
    </div>

    <div class="fpm-card mx-3">
        <?= $this->Form->create(null, ['url' => ['controller' => $fpmCtrl, 'action' => 'consultarChave']]) ?>
        <div class="fpm-row">
            <div class="fpm-field" style="flex:1;">
                <label>Chave de acesso (44 dígitos)</label>
                <input type="text" name="chave_acesso" class="form-control" maxlength="50"
                       value="<?= h($chaveInput) ?>" placeholder="Ex.: 35210512345678000190550010000012341000012349"
                       required pattern="\d{44}" title="44 dígitos numéricos" />
            </div>
        </div>
        <div class="mt-2">
            <?= $this->Form->button('<i class="fas fa-search"></i> Consultar na SEFAZ', [
                'class' => 'btn btn-pgm btn-pgm-salvar btn-sm', 'escape' => false, 'type' => 'submit',
            ]) ?>
        </div>
        <?= $this->Form->end() ?>
    </div>

    <?php if ($resultado !== null): ?>
    <div class="fpm-card mx-3 mt-2">
        <div class="fpm-card-title"><i class="fas fa-file-alt"></i> Resultado da Consulta</div>
        <?php if (!empty($resultado['success'])): ?>
            <div class="fpm-alert fpm-alert-info mb-2">
                <strong>Código <?= h($resultado['codigo'] ?? '') ?>:</strong> <?= h($resultado['mensagem'] ?? '') ?>
            </div>
        <?php else: ?>
            <div class="fpm-alert fpm-alert-danger mb-2">
                <strong>Código <?= h($resultado['codigo'] ?? '') ?>:</strong> <?= h($resultado['mensagem'] ?? '') ?>
            </div>
        <?php endif; ?>
        <table class="fpm-table" style="max-width:600px;">
            <tbody>
                <tr><td class="fpm-muted" style="width:160px;">Código retorno</td><td><?= h($resultado['codigo'] ?? '—') ?></td></tr>
                <tr><td class="fpm-muted">Mensagem</td><td><?= h($resultado['mensagem'] ?? '—') ?></td></tr>
                <tr><td class="fpm-muted">Protocolo</td><td><?= h($resultado['protocolo'] ?? '—') ?></td></tr>
                <tr><td class="fpm-muted">Chave retornada</td><td style="word-break:break-all;font-size:12px;"><?= h($resultado['chave_acesso'] ?? '—') ?></td></tr>
                <tr><td class="fpm-muted">Data recebimento</td><td><?= h($resultado['data_recebimento'] ?? '—') ?></td></tr>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
