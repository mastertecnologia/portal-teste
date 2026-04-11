<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Financeiro', ['controller' => 'Financeiro', 'action' => 'index']);
$this->Breadcrumbs->add('Plano de Contas');
$contas = $contas ?? [];
$tipo = $tipo ?? '';
?>
<style>
.fin-root { font-family:'DM Sans',sans-serif; }
.fin-topbar { display:flex; align-items:center; justify-content:space-between; padding:18px 24px 14px; border-bottom:1px solid rgba(255,255,255,.07); }
.fin-h1 { font-size:20px; font-weight:600; color:#e6edf3; }
.fin-h1-ico { color:#5cdbc0; margin-right:8px; }
.fin-card { background:#161b22; border:1px solid rgba(255,255,255,.07); border-radius:10px; padding:18px 20px; margin:16px 24px; }
.fin-card-title { font-size:11px; text-transform:uppercase; letter-spacing:.08em; color:#7d8590; font-weight:600; margin-bottom:14px; }
.fin-tbl { width:100%; border-collapse:collapse; font-size:12.5px; }
.fin-tbl th { color:#7d8590; font-size:10.5px; text-transform:uppercase; letter-spacing:.05em; font-weight:600; padding:5px 8px; border-bottom:1px solid rgba(255,255,255,.07); text-align:left; }
.fin-tbl td { padding:8px 8px; border-bottom:1px solid rgba(255,255,255,.04); color:#c9d1d9; }
.fin-tbl tr:hover td { background:rgba(255,255,255,.02); }
.fin-filters { display:flex; gap:10px; padding:12px 24px 0; align-items:flex-end; flex-wrap:wrap; }
.fin-badge { display:inline-block; padding:1px 7px; border-radius:9px; font-size:11px; font-weight:600; }
.fin-badge-rec { background:rgba(63,185,80,.13); color:#3fb950; }
.fin-badge-desp { background:rgba(248,81,73,.13); color:#f85149; }
.fin-badge-sint { background:rgba(136,149,167,.13); color:#8b949e; }
.fin-badge-ana { background:rgba(92,219,192,.13); color:#5cdbc0; }
.fin-indent { padding-left:20px; color:#8b949e; }
</style>

<div class="fin-root">
    <div class="fin-topbar">
        <h1 class="fin-h1"><i class="fas fa-sitemap fin-h1-ico"></i>Plano de Contas</h1>
        <div>
            <?= $this->Html->link('Nova conta', ['action' => 'planoContasAdd'], ['class' => 'btn btn-pgm btn-pgm-salvar btn-sm']) ?>
            <?= $this->Html->link('Centros de custo', ['action' => 'centrosCusto'], ['class' => 'btn btn-pgm btn-pgm-situacao btn-sm']) ?>
            <?= $this->Html->link('Financeiro', ['controller' => 'Financeiro', 'action' => 'index'], ['class' => 'btn btn-default btn-sm']) ?>
        </div>
    </div>

    <div class="fin-filters">
        <?= $this->Html->link('Todas', ['action' => 'planoContas'], ['class' => 'btn btn-sm ' . ($tipo === '' ? 'btn-pgm btn-pgm-salvar' : 'btn-default')]) ?>
        <?= $this->Html->link('Receitas', ['action' => 'planoContas', '?' => ['tipo' => 'receita']], ['class' => 'btn btn-sm ' . ($tipo === 'receita' ? 'btn-pgm btn-pgm-salvar' : 'btn-default')]) ?>
        <?= $this->Html->link('Despesas', ['action' => 'planoContas', '?' => ['tipo' => 'despesa']], ['class' => 'btn btn-sm ' . ($tipo === 'despesa' ? 'btn-pgm btn-pgm-salvar' : 'btn-default')]) ?>
    </div>

    <div class="fin-card">
        <?php if (empty($contas)): ?>
            <p style="color:#484f58; text-align:center; padding:24px;">Nenhuma conta cadastrada.</p>
        <?php else: ?>
        <table class="fin-tbl">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Descrição</th>
                    <th>Tipo</th>
                    <th>Natureza</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($contas as $c): ?>
                <tr>
                    <td><code><?= h($c->codigo) ?></code></td>
                    <td>
                        <?php if ($c->parent_id): ?><span class="fin-indent">└ </span><?php endif; ?>
                        <?= h($c->descricao) ?>
                    </td>
                    <td><span class="fin-badge <?= $c->tipo === 'receita' ? 'fin-badge-rec' : 'fin-badge-desp' ?>"><?= h($c->tipo) ?></span></td>
                    <td><span class="fin-badge <?= $c->natureza === 'sintetica' ? 'fin-badge-sint' : 'fin-badge-ana' ?>"><?= h($c->natureza) ?></span></td>
                    <td><?= $c->ativo ? '<span style="color:#3fb950;">Ativo</span>' : '<span style="color:#8b949e;">Inativo</span>' ?></td>
                    <td>
                        <?= $this->Html->link('Editar', ['action' => 'planoContasEdit', $c->id], ['class' => 'btn btn-xs btn-pgm btn-pgm-situacao']) ?>
                        <?= $this->Form->postLink('Excluir', ['action' => 'planoContasDelete', $c->id], [
                            'class' => 'btn btn-xs btn-outline-danger', 'confirm' => 'Excluir esta conta?',
                        ]) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
