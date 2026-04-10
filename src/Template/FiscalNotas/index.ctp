<?php
use Cake\Core\Configure;
$fpmCtrl = $this->request->getParam('controller');
$isEntrada = ($fpmCtrl === 'FiscalNotasEntrada');
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Módulo Fiscal', ['controller' => 'Fiscal', 'action' => 'index']);
$this->Breadcrumbs->add($isEntrada ? 'Notas de entrada' : 'Notas de saída');
echo $this->element('Fiscal/styles');

$this->Paginator->options(['url' => ['?' => $this->request->getQueryParams()]]);
?>
<div class="fpm-wrap">
    <div class="fpm-topbar">
        <h1 class="fpm-h1">
            <i class="fas fa-file-invoice"></i><?= $isEntrada ? 'Notas fiscais de entrada' : 'Notas fiscais de saída' ?>
        </h1>
        <div class="fpm-actions">
            <?= $this->Html->link('<i class="fas fa-barcode"></i> Séries', ['controller' => $fpmCtrl, 'action' => 'controleSeries'], ['class' => 'btn btn-pgm btn-pgm-situacao btn-sm', 'escape' => false]) ?>
            <?php if (!$isEntrada) : ?>
            <?= $this->Html->link('<i class="fas fa-ban"></i> Inutilizar', ['controller' => 'FiscalNotas', 'action' => 'inutilizarNumeracao'], ['class' => 'btn btn-default btn-sm', 'escape' => false]) ?>
            <?php else : ?>
            <?= $this->Html->link('<i class="fas fa-ban"></i> Inutilizar', ['controller' => 'FiscalNotasEntrada', 'action' => 'inutilizarNumeracao'], ['class' => 'btn btn-default btn-sm', 'escape' => false]) ?>
            <?php endif; ?>
            <?= $this->Html->link('<i class="fas fa-plus"></i> Nova', ['controller' => $fpmCtrl, 'action' => 'add'], ['class' => 'btn btn-pgm btn-pgm-salvar btn-sm', 'escape' => false]) ?>
        </div>
    </div>

    <?= $this->Form->create(null, ['type' => 'get', 'class' => 'fpm-filters']) ?>
    <?php if (!$isEntrada): ?>
    <div>
        <label>Tipo operação</label>
        <select name="tipo_operacao" onchange="this.form.submit()">
            <option value="1" <?= (($filters['tipo_operacao'] ?? 1) == 1) ? 'selected' : '' ?>>Saída</option>
            <option value="0" <?= (($filters['tipo_operacao'] ?? '') === 0 || ($filters['tipo_operacao'] ?? '') === '0') ? 'selected' : '' ?>>Entrada</option>
        </select>
    </div>
    <?php endif; ?>
    <div>
        <label>Status</label>
        <select name="status" onchange="this.form.submit()">
            <option value="">Todos</option>
            <?php foreach ($statusList as $k => $lbl): ?>
            <option value="<?= h($k) ?>" <?= (($filters['status'] ?? '') === (string)$k) ? 'selected' : '' ?>><?= h($lbl) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label>Modelo</label>
        <select name="modelo" onchange="this.form.submit()">
            <option value="">Todos</option>
            <?php foreach ($modelos as $k => $lbl): ?>
            <option value="<?= h($k) ?>" <?= (($filters['modelo'] ?? '') === (string)$k) ? 'selected' : '' ?>><?= h($lbl) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label>Cliente</label>
        <select name="cliente" onchange="this.form.submit()">
            <option value="">Todos</option>
            <?php foreach ($clientes as $cid => $cnome): ?>
            <option value="<?= (int)$cid ?>" <?= (($filters['idcliente'] ?? '') == $cid) ? 'selected' : '' ?>><?= h($cnome) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label>Nº série contém</label>
        <input type="text" name="numero_serie" value="<?= h($filters['numero_serie'] ?? '') ?>" placeholder="Buscar…">
    </div>
    <div>
        <label>De</label>
        <input type="date" name="data_inicio" value="<?= h($filters['data_inicio'] ?? '') ?>">
    </div>
    <div>
        <label>Até</label>
        <input type="date" name="data_fim" value="<?= h($filters['data_fim'] ?? '') ?>">
    </div>
    <div>
        <label>Número NF</label>
        <input type="number" name="numero" value="<?= h($filters['numero'] ?? '') ?>" min="0" style="max-width:120px;">
    </div>
    <div>
        <button type="submit" class="btn btn-pgm btn-pgm-salvar btn-sm">Filtrar</button>
    </div>
    <?= $this->Form->end() ?>

    <div class="fpm-table-wrap">
        <?php if (count($notas) === 0): ?>
            <div class="fpm-empty">Nenhuma nota encontrada.</div>
        <?php else: ?>
        <table class="fpm-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Número</th>
                    <th>Modelo</th>
                    <th>Tipo</th>
                    <th>Cliente</th>
                    <th>Emissão</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($notas as $n): ?>
                <tr>
                    <td><?= (int)$n->id ?></td>
                    <td><?= $n->numero !== null ? h((string)$n->numero) : '—' ?></td>
                    <td><?= h($n->modelo) ?></td>
                    <td><?= (int)$n->tipo_operacao === 0 ? 'Entrada' : 'Saída' ?></td>
                    <td><?= h($n->cliente ? ($n->cliente->razaosocial ?: $n->cliente->nome) : '—') ?></td>
                    <td><?= $n->data_emissao ? h($n->data_emissao->format('d/m/Y')) : '—' ?></td>
                    <td>R$ <?= number_format((float)$n->valor_total, 2, ',', '.') ?></td>
                    <td><span class="fpm-badge muted"><?= h($statusList[$n->status] ?? $n->status) ?></span></td>
                    <td>
                        <?= $this->Html->link('Ver', ['controller' => $fpmCtrl, 'action' => 'view', $n->id], ['class' => 'btn btn-xs btn-pgm btn-pgm-situacao']) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <nav class="p-3">
            <ul class="pagination pagination-sm mb-0">
                <?= $this->Paginator->prev('<') ?>
                <?= $this->Paginator->numbers() ?>
                <?= $this->Paginator->next('>') ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>
