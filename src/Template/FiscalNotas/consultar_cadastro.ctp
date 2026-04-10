<?php
$fpmCtrl = $this->request->getParam('controller');
$isEntrada = ($fpmCtrl === 'FiscalNotasEntrada');
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Módulo Fiscal', ['controller' => 'Fiscal', 'action' => 'index']);
$this->Breadcrumbs->add($isEntrada ? 'Notas de entrada' : 'Notas de saída', ['controller' => $fpmCtrl, 'action' => 'index']);
$this->Breadcrumbs->add('Consulta cadastral');
echo $this->element('Fiscal/styles');
$resultado = $resultado ?? null;
$docInput = $docInput ?? '';
$ufInput = $ufInput ?? '';
$tipoInput = $tipoInput ?? 'cnpj';
$ufs = $ufs ?? [];
?>
<div class="fpm-wrap">
    <div class="fpm-topbar">
        <h1 class="fpm-h1"><i class="fas fa-building"></i> Consulta Cadastral (SEFAZ)</h1>
        <div class="fpm-actions">
            <?= $this->Html->link('Voltar', ['controller' => $fpmCtrl, 'action' => 'index'], ['class' => 'btn btn-default btn-sm']) ?>
        </div>
    </div>

    <?= $this->element('Fiscal/regime_context') ?>

    <div class="fpm-card mx-3">
        <?= $this->Form->create(null, ['url' => ['controller' => $fpmCtrl, 'action' => 'consultarCadastro']]) ?>
        <div class="fpm-row">
            <div class="fpm-field">
                <label>Tipo</label>
                <select name="tipo" class="form-control">
                    <option value="cnpj" <?= $tipoInput === 'cnpj' ? 'selected' : '' ?>>CNPJ</option>
                    <option value="ie" <?= $tipoInput === 'ie' ? 'selected' : '' ?>>Inscrição Estadual</option>
                </select>
            </div>
            <div class="fpm-field" style="flex:1;">
                <label>Documento</label>
                <input type="text" name="documento" class="form-control" value="<?= h($docInput) ?>"
                       placeholder="CNPJ ou IE" required />
            </div>
            <div class="fpm-field">
                <label>UF</label>
                <select name="uf" class="form-control" required>
                    <option value="">-- UF --</option>
                    <?php foreach ($ufs as $u): ?>
                    <option value="<?= h($u) ?>" <?= $ufInput === $u ? 'selected' : '' ?>><?= h($u) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="mt-2">
            <?= $this->Form->button('<i class="fas fa-search"></i> Consultar cadastro', [
                'class' => 'btn btn-pgm btn-pgm-salvar btn-sm', 'escape' => false, 'type' => 'submit',
            ]) ?>
        </div>
        <?= $this->Form->end() ?>
    </div>

    <?php if ($resultado !== null): ?>
    <div class="fpm-card mx-3 mt-2">
        <div class="fpm-card-title"><i class="fas fa-address-card"></i> Resultado</div>
        <?php if (!empty($resultado['success'])): ?>
            <div class="fpm-alert fpm-alert-info mb-2">
                <strong>Código <?= h($resultado['codigo'] ?? '') ?>:</strong> <?= h($resultado['mensagem'] ?? '') ?>
            </div>
        <?php else: ?>
            <div class="fpm-alert fpm-alert-danger mb-2">
                <strong>Código <?= h($resultado['codigo'] ?? '') ?>:</strong> <?= h($resultado['mensagem'] ?? '') ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($resultado['cadastro'])): ?>
        <div class="fpm-table-wrap">
            <table class="fpm-table">
                <thead>
                    <tr>
                        <th>CNPJ</th>
                        <th>IE</th>
                        <th>Razão Social</th>
                        <th>Fantasia</th>
                        <th>UF</th>
                        <th>Município</th>
                        <th>CNAE</th>
                        <th>Situação</th>
                        <th>Início Atividade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resultado['cadastro'] as $cad): ?>
                    <tr>
                        <td><?= h($cad['CNPJ'] ?? '—') ?></td>
                        <td><?= h($cad['IE'] ?? '—') ?></td>
                        <td><?= h($cad['xNome'] ?? '—') ?></td>
                        <td><?= h($cad['xFant'] ?? '—') ?></td>
                        <td><?= h($cad['UF'] ?? '—') ?></td>
                        <td><?= h($cad['xMun'] ?? '—') ?></td>
                        <td><?= h($cad['CNAE'] ?? '—') ?></td>
                        <td><?= h($cad['cSit'] ?? '—') ?></td>
                        <td><?= h($cad['dIniAtiv'] ?? '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php elseif (!empty($resultado['success'])): ?>
            <p class="fpm-muted">Nenhum registro de cadastro retornado.</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
