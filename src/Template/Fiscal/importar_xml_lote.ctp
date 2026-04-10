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

    <div class="fpm-card mx-3">
        <p class="fpm-muted">
            Selecione arquivos XML de NF-e (nfeProc ou infNFe) para criar rascunhos de notas de entrada automaticamente.
        </p>
        <?= $this->Form->create(null, ['url' => ['action' => 'importarXmlLote'], 'type' => 'file']) ?>
        <div class="fpm-field">
            <label>Arquivos XML</label>
            <input type="file" name="xml_files[]" accept=".xml,application/xml,text/xml" multiple required class="form-control" />
            <small class="fpm-muted">Pode selecionar múltiplos arquivos de uma vez.</small>
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
