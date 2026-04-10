<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Configuração fiscal', ['action' => 'index']);
$this->Breadcrumbs->add('Alíquotas');
echo $this->element('Fiscal/styles');
?>
<div class="fpm-wrap">
    <div class="fpm-topbar">
        <h1 class="fpm-h1">Alíquotas por UF / NCM</h1>
        <div class="fpm-actions">
            <?= $this->Html->link('Nova', ['action' => 'aliquotaAdd'], ['class' => 'btn btn-pgm btn-pgm-salvar btn-sm']) ?>
            <?= $this->Html->link('Voltar', ['action' => 'index'], ['class' => 'btn btn-default btn-sm']) ?>
        </div>
    </div>
    <?= $this->element('Fiscal/regime_context') ?>
    <div class="fpm-table-wrap">
        <table class="fpm-table">
            <thead>
                <tr>
                    <th>UF origem</th>
                    <th>UF destino</th>
                    <th>NCM</th>
                    <th>ICMS %</th>
                    <th>PIS</th>
                    <th>COFINS</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($aliquotas as $a): ?>
                <tr>
                    <td><?= h($a->uf_origem) ?></td>
                    <td><?= h($a->uf_destino) ?></td>
                    <td><?= h($a->ncm_codigo ?: '—') ?></td>
                    <td><?= h($a->icms_aliquota ?? '—') ?></td>
                    <td><?= h($a->pis_aliquota ?? '—') ?></td>
                    <td><?= h($a->cofins_aliquota ?? '—') ?></td>
                    <td>
                        <?= $this->Html->link('Editar', ['action' => 'aliquotaEdit', $a->id], ['class' => 'btn btn-xs btn-pgm btn-pgm-situacao']) ?>
                        <?= $this->Form->postLink('Excluir', ['action' => 'aliquotaDelete', $a->id], [
                            'class' => 'btn btn-xs btn-outline-danger', 'confirm' => 'Excluir?',
                        ]) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
