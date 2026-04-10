<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Módulo Fiscal', ['controller' => 'Fiscal', 'action' => 'index']);
$this->Breadcrumbs->add('Certificados digitais');
echo $this->element('Fiscal/styles');
?>
<div class="fpm-wrap">
    <div class="fpm-topbar">
        <h1 class="fpm-h1"><i class="fas fa-certificate"></i>Certificados digitais</h1>
        <div class="fpm-actions">
            <?= $this->Html->link('Novo certificado', ['action' => 'add'], ['class' => 'btn btn-pgm btn-pgm-salvar btn-sm']) ?>
            <?= $this->Html->link('Voltar', ['controller' => 'Fiscal', 'action' => 'index'], ['class' => 'btn btn-default btn-sm']) ?>
        </div>
    </div>
    <?= $this->element('Fiscal/regime_context') ?>
    <div class="fpm-table-wrap">
        <?php if (empty($certificados)): ?>
            <div class="fpm-empty">Nenhum certificado cadastrado.</div>
        <?php else: ?>
        <table class="fpm-table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Tipo</th>
                    <th>CNPJ</th>
                    <th>Validade</th>
                    <th>Ativo</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($certificados as $c): ?>
                <tr>
                    <td><?= h($c->nome) ?></td>
                    <td><?= h($c->tipo) ?></td>
                    <td><?= h($c->cnpj_certificado ?: '—') ?></td>
                    <td><?= $c->validade_fim ? h($c->validade_fim->format('d/m/Y')) : '—' ?></td>
                    <td><?= $c->ativo ? '<span class="fpm-badge ok">Sim</span>' : '<span class="fpm-badge muted">Não</span>' ?></td>
                    <td>
                        <?= $this->Html->link('Ver', ['action' => 'view', $c->id], ['class' => 'btn btn-xs btn-pgm btn-pgm-situacao']) ?>
                        <?= $this->Form->postLink($c->ativo ? 'Desativar' : 'Ativar', ['action' => 'toggleAtivo', $c->id], ['class' => 'btn btn-xs btn-default']) ?>
                        <?= $this->Form->postLink('Excluir', ['action' => 'delete', $c->id], [
                            'class' => 'btn btn-xs btn-outline-danger', 'confirm' => 'Excluir certificado?',
                        ]) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
