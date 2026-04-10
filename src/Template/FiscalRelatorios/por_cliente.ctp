<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Relatórios', ['action' => 'index']);
$this->Breadcrumbs->add('Por cliente');
echo $this->element('Fiscal/styles');
?>
<div class="fpm-wrap">
    <div class="fpm-topbar">
        <h1 class="fpm-h1">Notas por cliente</h1>
        <?= $this->Html->link('Voltar', ['action' => 'index'], ['class' => 'btn btn-default btn-sm']) ?>
    </div>
    <?= $this->Form->create(null, ['type' => 'get', 'class' => 'fpm-filters']) ?>
    <div>
        <label>Mês</label>
        <input type="month" name="mes_ano" value="<?= h($mesAno) ?>">
    </div>
    <div><button type="submit" class="btn btn-pgm btn-pgm-salvar btn-sm" style="margin-top:18px;">Atualizar</button></div>
    <?= $this->Form->end() ?>

    <div class="fpm-table-wrap">
        <table class="fpm-table">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Qtd notas</th>
                    <th>Valor total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dados as $r): ?>
                <tr>
                    <td><?= h($r['razaosocial'] ?? '—') ?></td>
                    <td><?= (int)$r['qtd'] ?></td>
                    <td>R$ <?= number_format((float)($r['valor_total'] ?? 0), 2, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
