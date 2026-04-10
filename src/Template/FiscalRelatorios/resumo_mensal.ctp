<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Relatórios', ['action' => 'index']);
$this->Breadcrumbs->add('Resumo mensal');
echo $this->element('Fiscal/styles');
?>
<div class="fpm-wrap">
    <div class="fpm-topbar">
        <h1 class="fpm-h1">Resumo mensal por modelo</h1>
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
                    <th>Modelo</th>
                    <th>Qtd</th>
                    <th>Produtos</th>
                    <th>ICMS</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($resumo as $r): ?>
                <tr>
                    <td><?= h($r['modelo']) ?></td>
                    <td><?= (int)$r['qtd'] ?></td>
                    <td>R$ <?= number_format((float)($r['valor_produtos'] ?? 0), 2, ',', '.') ?></td>
                    <td>R$ <?= number_format((float)($r['valor_icms'] ?? 0), 2, ',', '.') ?></td>
                    <td>R$ <?= number_format((float)($r['valor_total'] ?? 0), 2, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
