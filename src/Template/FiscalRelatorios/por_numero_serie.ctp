<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Módulo Fiscal', ['controller' => 'Fiscal', 'action' => 'index']);
$this->Breadcrumbs->add('Relatórios', ['controller' => 'FiscalRelatorios', 'action' => 'index']);
$this->Breadcrumbs->add('Por número de série');
echo $this->element('Fiscal/styles');
?>
<div class="fpm-wrap">
    <div class="fpm-topbar">
        <h1 class="fpm-h1"><i class="fas fa-barcode"></i>Relatório por número de série</h1>
        <?= $this->Html->link('Voltar', ['action' => 'index'], ['class' => 'btn btn-default btn-sm']) ?>
    </div>

    <?= $this->Form->create(null, ['type' => 'get', 'class' => 'fpm-filters']) ?>
    <div style="flex:1;min-width:220px;">
        <label>Número de série (obrigatório)</label>
        <input type="text" name="numero_serie" value="<?= h($this->request->getQuery('numero_serie')) ?>" required placeholder="Ex.: SN123456">
    </div>
    <div>
        <label>Tipo nota</label>
        <select name="tipo_operacao">
            <option value="">Todos</option>
            <option value="0" <?= $this->request->getQuery('tipo_operacao') === '0' ? 'selected' : '' ?>>Entrada</option>
            <option value="1" <?= $this->request->getQuery('tipo_operacao') === '1' ? 'selected' : '' ?>>Saída</option>
        </select>
    </div>
    <div>
        <label>Cód. produto</label>
        <input type="text" name="codigo_produto" value="<?= h($this->request->getQuery('codigo_produto')) ?>">
    </div>
    <div>
        <label>De</label>
        <input type="date" name="data_inicio" value="<?= h($this->request->getQuery('data_inicio')) ?>">
    </div>
    <div>
        <label>Até</label>
        <input type="date" name="data_fim" value="<?= h($this->request->getQuery('data_fim')) ?>">
    </div>
    <div>
        <button type="submit" class="btn btn-pgm btn-pgm-salvar btn-sm" style="margin-top:18px;">Gerar</button>
    </div>
    <?= $this->Form->end() ?>

    <div class="fpm-table-wrap">
        <?php if ($numeroSerie === ''): ?>
            <div class="fpm-empty">Informe o número de série e clique em Gerar.</div>
        <?php elseif (empty($linhas)): ?>
            <div class="fpm-empty">Nenhum registro encontrado para <strong><?= h($numeroSerie) ?></strong>.</div>
        <?php else: ?>
        <p class="fpm-muted px-3">Resultados para <code><?= h($numeroSerie) ?></code> — <?= count($linhas) ?> linha(s)</p>
        <table class="fpm-table">
            <thead>
                <tr>
                    <th>Série</th>
                    <th>Produto</th>
                    <th>Descrição</th>
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
                            <?= $this->Html->link('Abrir', ['controller' => $vc, 'action' => 'view', $nota->id], ['class' => 'btn btn-xs btn-pgm btn-pgm-situacao']) ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
