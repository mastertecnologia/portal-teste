<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Módulo Fiscal', ['controller' => 'Fiscal', 'action' => 'index']);
$this->Breadcrumbs->add('Importar XMLs em lote');
echo $this->element('Fiscal/styles');
$resultados = $resultados ?? [];
?>
<div class="fpm-wrap">
    <div class="fpm-topbar">
        <h1 class="fpm-h1"><i class="fas fa-file-upload"></i> Importar XMLs em Lote</h1>
        <div class="fpm-actions">
            <?= $this->Html->link('Painel', ['action' => 'index'], ['class' => 'btn btn-default btn-sm']) ?>
        </div>
    </div>

    <?= $this->element('Fiscal/regime_context') ?>

    <div class="fpm-card mx-3">
        <p class="fpm-muted">
            Selecione arquivos XML de NF-e (nfeProc ou infNFe) ou um arquivo .zip contendo XMLs.
            Notas de entrada serão criadas automaticamente.
        </p>
        <?= $this->Form->create(null, ['url' => ['action' => 'importarXmlLote'], 'type' => 'file']) ?>
        <div class="fpm-field">
            <label>Arquivos XML ou ZIP</label>
            <input type="file" name="xml_files[]" accept=".xml,.zip,application/xml,text/xml,application/zip" multiple required class="form-control" />
            <small class="fpm-muted">Pode selecionar múltiplos XMLs ou um arquivo .zip com todos os XMLs dentro.</small>
        </div>
        <div class="fpm-field mt-2">
            <label class="mb-0" style="font-weight:normal;">
                <input type="checkbox" name="como_autorizada" value="1" />
                Marcar como <strong>autorizada</strong> (notas já autorizadas na SEFAZ por outro sistema)
            </label>
            <small class="fpm-muted">Se desmarcado, as notas entram como rascunho para revisão.</small>
        </div>
        <div class="mt-2">
            <?= $this->Form->button('<i class="fas fa-upload"></i> Importar', [
                'class' => 'btn btn-pgm btn-pgm-salvar btn-sm', 'escape' => false, 'type' => 'submit',
            ]) ?>
        </div>
        <?= $this->Form->end() ?>
    </div>

    <?php if (!empty($resultados)): ?>
    <div class="fpm-card mx-3 mt-2">
        <div class="fpm-card-title">Resultado da Importação</div>
        <table class="fpm-table">
            <thead>
                <tr>
                    <th>Arquivo</th>
                    <th>Status</th>
                    <th>Mensagem</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($resultados as $r): ?>
                <tr>
                    <td><?= h($r['filename'] ?? '—') ?></td>
                    <td>
                        <?php if (!empty($r['ok'])): ?>
                            <span class="fpm-badge ok">OK</span>
                        <?php else: ?>
                            <span class="fpm-badge muted">Erro</span>
                        <?php endif; ?>
                    </td>
                    <td><?= h($r['message'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
