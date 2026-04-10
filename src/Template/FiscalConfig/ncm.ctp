<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Módulo Fiscal', ['controller' => 'Fiscal', 'action' => 'index']);
$this->Breadcrumbs->add('Configuração', ['action' => 'index']);
$this->Breadcrumbs->add('NCM');
echo $this->element('Fiscal/styles');
$this->Paginator->options(['url' => ['?' => $this->request->getQueryParams()]]);
?>
<div class="fpm-wrap">
    <div class="fpm-topbar">
        <h1 class="fpm-h1"><i class="fas fa-list"></i>Tabela NCM</h1>
        <div class="fpm-actions">
            <?= $this->Html->link('Novo NCM', ['action' => 'ncmAdd'], ['class' => 'btn btn-pgm btn-pgm-salvar btn-sm']) ?>
            <?= $this->Html->link('Voltar', ['action' => 'index'], ['class' => 'btn btn-default btn-sm']) ?>
        </div>
    </div>

    <?= $this->Form->create(null, ['type' => 'get', 'class' => 'fpm-filters']) ?>
    <div style="flex:1;min-width:240px;">
        <label>Busca</label>
        <input type="text" name="q" value="<?= h($busca ?? '') ?>" placeholder="Código ou descrição">
    </div>
    <div><button type="submit" class="btn btn-pgm btn-pgm-salvar btn-sm" style="margin-top:18px;">Filtrar</button></div>
    <?= $this->Form->end() ?>

    <div class="fpm-table-wrap">
        <table class="fpm-table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Descrição</th>
                    <th>IPI %</th>
                    <th>EX TIPI</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ncms as $n): ?>
                <tr>
                    <td><code><?= h($n->codigo) ?></code></td>
                    <td><?= h($n->descricao) ?></td>
                    <td><?= $n->aliquota_ipi !== null ? h($n->aliquota_ipi) : '—' ?></td>
                    <td><?= h($n->ex_tipi ?: '—') ?></td>
                    <td>
                        <?= $this->Html->link('Editar', ['action' => 'ncmEdit', $n->id], ['class' => 'btn btn-xs btn-pgm btn-pgm-situacao']) ?>
                        <?= $this->Form->postLink('Excluir', ['action' => 'ncmDelete', $n->id], [
                            'class' => 'btn btn-xs btn-outline-danger', 'confirm' => 'Excluir este NCM?',
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
