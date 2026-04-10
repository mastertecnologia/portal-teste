<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Módulo Fiscal', ['controller' => 'Fiscal', 'action' => 'index']);
$this->Breadcrumbs->add('Configuração', ['action' => 'index']);
$this->Breadcrumbs->add('CFOP');
echo $this->element('Fiscal/styles');
$this->Paginator->options(['url' => ['?' => $this->request->getQueryParams()]]);
?>
<div class="fpm-wrap">
    <div class="fpm-topbar">
        <h1 class="fpm-h1"><i class="fas fa-list"></i>Tabela CFOP</h1>
        <div class="fpm-actions">
            <?= $this->Html->link('Novo CFOP', ['action' => 'cfopAdd'], ['class' => 'btn btn-pgm btn-pgm-salvar btn-sm']) ?>
            <?= $this->Html->link('Voltar', ['action' => 'index'], ['class' => 'btn btn-default btn-sm']) ?>
        </div>
    </div>

    <?= $this->Form->create(null, ['type' => 'get', 'class' => 'fpm-filters']) ?>
    <div>
        <label>Tipo</label>
        <select name="tipo" onchange="this.form.submit()">
            <option value="">Todos</option>
            <option value="entrada" <?= ($this->request->getQuery('tipo') === 'entrada') ? 'selected' : '' ?>>Entrada</option>
            <option value="saida" <?= ($this->request->getQuery('tipo') === 'saida') ? 'selected' : '' ?>>Saída</option>
        </select>
    </div>
    <?= $this->Form->end() ?>

    <div class="fpm-table-wrap">
        <table class="fpm-table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Descrição</th>
                    <th>Tipo</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cfops as $c): ?>
                <tr>
                    <td><code><?= h($c->codigo) ?></code></td>
                    <td><?= h($c->descricao) ?></td>
                    <td><?= h($c->tipo) ?></td>
                    <td>
                        <?= $this->Html->link('Editar', ['action' => 'cfopEdit', $c->id], ['class' => 'btn btn-xs btn-pgm btn-pgm-situacao']) ?>
                        <?= $this->Form->postLink('Excluir', ['action' => 'cfopDelete', $c->id], [
                            'class' => 'btn btn-xs btn-outline-danger', 'confirm' => 'Excluir este CFOP?',
                        ]) ?>
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
    </div>
</div>
