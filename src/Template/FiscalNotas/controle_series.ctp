<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Módulo Fiscal', ['controller' => 'Fiscal', 'action' => 'index']);
$this->Breadcrumbs->add('Controle de séries');
echo $this->element('Fiscal/styles');
$this->Paginator->options(['url' => ['?' => $this->request->getQueryParams()]]);
?>
<div class="fpm-wrap">
    <div class="fpm-topbar">
        <h1 class="fpm-h1"><i class="fas fa-barcode"></i>Controle e busca — números de série</h1>
        <div class="fpm-actions">
            <?= $this->Html->link('Voltar ao fiscal', ['controller' => 'Fiscal', 'action' => 'index'], ['class' => 'btn btn-default btn-sm']) ?>
            <?= $this->Html->link('Notas de saída', ['controller' => 'FiscalNotas', 'action' => 'index'], ['class' => 'btn btn-pgm btn-pgm-situacao btn-sm']) ?>
        </div>
    </div>

    <?= $this->element('Fiscal/regime_context') ?>

    <?= $this->Form->create(null, ['type' => 'get', 'class' => 'fpm-filters']) ?>
    <div>
        <label>Número de série</label>
        <input type="text" name="numero_serie" value="<?= h($filters['numero_serie'] ?? '') ?>" placeholder="Contém…">
    </div>
    <div>
        <label>Tipo nota</label>
        <select name="tipo_operacao">
            <option value="">Todos</option>
            <option value="0" <?= (($filters['tipo_operacao'] ?? '') === '0' || ($filters['tipo_operacao'] ?? '') === 0) ? 'selected' : '' ?>>Entrada</option>
            <option value="1" <?= (($filters['tipo_operacao'] ?? '') === '1' || ($filters['tipo_operacao'] ?? '') === 1) ? 'selected' : '' ?>>Saída</option>
        </select>
    </div>
    <div>
        <label>Cód. produto</label>
        <input type="text" name="codigo_produto" value="<?= h($filters['codigo_produto'] ?? '') ?>">
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
        <button type="submit" class="btn btn-pgm btn-pgm-salvar btn-sm">Buscar</button>
    </div>
    <?= $this->Form->end() ?>

    <div class="fpm-table-wrap">
        <?php if (count($linhas) === 0): ?>
            <div class="fpm-empty">Nenhuma linha encontrada. Informe filtros e busque novamente.</div>
        <?php else: ?>
        <table class="fpm-table">
            <thead>
                <tr>
                    <th>Série</th>
                    <th>Produto</th>
                    <th>Descrição item</th>
                    <th>Nota</th>
                    <th>Cliente</th>
                    <th>Emissão</th>
                    <th>Tipo</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($linhas as $row):
                    $item = $row->fiscal_nota_item;
                    $nota = $item && $item->fiscal_nota ? $item->fiscal_nota : null;
                ?>
                <tr>
                    <td><code><?= h($row->numero_serie) ?></code></td>
                    <td><?= h($item->codigo_produto ?? '—') ?></td>
                    <td><?= h($item->descricao ?? '—') ?></td>
                    <td>
                        <?php if ($nota): ?>
                            <?= h($nota->modelo) ?> <?= h((string)$nota->serie) ?> / <?= h((string)$nota->numero) ?>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td><?= h($nota && $nota->cliente ? ($nota->cliente->razaosocial ?: $nota->cliente->nome) : '—') ?></td>
                    <td><?= $nota && $nota->data_emissao ? h($nota->data_emissao->format('d/m/Y')) : '—' ?></td>
                    <td><?= $nota && (int)$nota->tipo_operacao === 0 ? 'Entrada' : 'Saída' ?></td>
                    <td><?= $nota ? h($statusList[$nota->status] ?? $nota->status) : '—' ?></td>
                    <td>
                        <?php if ($nota): ?>
                            <?php $vc = (int)$nota->tipo_operacao === 0 ? 'FiscalNotasEntrada' : 'FiscalNotas'; ?>
                            <?= $this->Html->link('Nota', ['controller' => $vc, 'action' => 'view', $nota->id], ['class' => 'btn btn-xs btn-pgm btn-pgm-situacao']) ?>
                        <?php endif; ?>
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
