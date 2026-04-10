<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Configuração fiscal', ['action' => 'index']);
$this->Breadcrumbs->add('Naturezas de operação');
echo $this->element('Fiscal/styles');
?>
<div class="fpm-wrap">
    <div class="fpm-topbar">
        <h1 class="fpm-h1">Naturezas de operação</h1>
        <div class="fpm-actions">
            <?= $this->Html->link('Nova', ['action' => 'naturezaAdd'], ['class' => 'btn btn-pgm btn-pgm-salvar btn-sm']) ?>
            <?= $this->Html->link('Voltar', ['action' => 'index'], ['class' => 'btn btn-default btn-sm']) ?>
        </div>
    </div>
    <?= $this->element('Fiscal/regime_context') ?>
    <div class="fpm-table-wrap">
        <table class="fpm-table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Descrição</th>
                    <th>Tipo</th>
                    <th>CFOP padrão</th>
                    <th>Ativo</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($naturezas as $n): ?>
                <tr>
                    <td><?= h($n->codigo) ?></td>
                    <td><?= h($n->descricao) ?></td>
                    <td><?= h($n->tipo) ?></td>
                    <td><?= h($n->cfop_padrao ?: '—') ?></td>
                    <td><?= $n->ativo ? 'Sim' : 'Não' ?></td>
                    <td>
                        <?= $this->Html->link('Editar', ['action' => 'naturezaEdit', $n->id], ['class' => 'btn btn-xs btn-pgm btn-pgm-situacao']) ?>
                        <?= $this->Form->postLink('Excluir', ['action' => 'naturezaDelete', $n->id], [
                            'class' => 'btn btn-xs btn-outline-danger', 'confirm' => 'Excluir?',
                        ]) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
