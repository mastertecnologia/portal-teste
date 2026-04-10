<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Relatórios', ['action' => 'index']);
$this->Breadcrumbs->add('Livro de saídas');
echo $this->element('Fiscal/styles');
?>
<div class="fpm-wrap">
    <div class="fpm-topbar">
        <h1 class="fpm-h1">Livro de saídas</h1>
        <?= $this->Html->link('Voltar', ['action' => 'index'], ['class' => 'btn btn-default btn-sm']) ?>
    </div>
    <?= $this->element('Fiscal/regime_context') ?>
    <?= $this->Form->create(null, ['type' => 'get', 'class' => 'fpm-filters']) ?>
    <div>
        <label>Mês</label>
        <input type="month" name="mes_ano" value="<?= h($mesAno) ?>">
    </div>
    <div><button type="submit" class="btn btn-pgm btn-pgm-salvar btn-sm" style="margin-top:18px;">Atualizar</button></div>
    <?= $this->Form->end() ?>

    <div class="fpm-card mx-3">
        <div class="fpm-row">
            <?php foreach ($totais as $k => $v): ?>
            <div class="fpm-field" style="min-width:120px;">
                <span class="fpm-muted"><?= h($k) ?></span>
                <div>R$ <?= number_format($v, 2, ',', '.') ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="fpm-table-wrap">
        <table class="fpm-table">
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Cliente</th>
                    <th>Emissão</th>
                    <th>Status</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($notas as $n): ?>
                <tr>
                    <td><?= h((string)$n->numero) ?></td>
                    <td><?= h($n->cliente ? $n->cliente->razaosocial : '—') ?></td>
                    <td><?= $n->data_emissao ? h($n->data_emissao->format('d/m/Y')) : '—' ?></td>
                    <td><?= h($n->status) ?></td>
                    <td>R$ <?= number_format((float)$n->valor_total, 2, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
