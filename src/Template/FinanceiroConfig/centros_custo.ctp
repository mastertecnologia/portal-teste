<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Financeiro', ['controller' => 'Financeiro', 'action' => 'index']);
$this->Breadcrumbs->add('Centros de Custo');
$centros = $centros ?? [];
?>
<style>
.fin-root { font-family:'DM Sans',sans-serif; }
.fin-topbar { display:flex; align-items:center; justify-content:space-between; padding:18px 24px 14px; border-bottom:1px solid rgba(255,255,255,.07); }
.fin-h1 { font-size:20px; font-weight:600; color:#e6edf3; }
.fin-h1-ico { color:#5cdbc0; margin-right:8px; }
.fin-card { background:#161b22; border:1px solid rgba(255,255,255,.07); border-radius:10px; padding:18px 20px; margin:16px 24px; }
.fin-tbl { width:100%; border-collapse:collapse; font-size:12.5px; }
.fin-tbl th { color:#7d8590; font-size:10.5px; text-transform:uppercase; letter-spacing:.05em; font-weight:600; padding:5px 8px; border-bottom:1px solid rgba(255,255,255,.07); text-align:left; }
.fin-tbl td { padding:8px 8px; border-bottom:1px solid rgba(255,255,255,.04); color:#c9d1d9; }
.fin-tbl tr:hover td { background:rgba(255,255,255,.02); }
</style>

<div class="fin-root">
    <div class="fin-topbar">
        <h1 class="fin-h1"><i class="fas fa-project-diagram fin-h1-ico"></i>Centros de Custo</h1>
        <div>
            <?= $this->Html->link('Novo centro', ['action' => 'centroCustoAdd'], ['class' => 'btn btn-pgm btn-pgm-salvar btn-sm']) ?>
            <?= $this->Html->link('Plano de contas', ['action' => 'planoContas'], ['class' => 'btn btn-pgm btn-pgm-situacao btn-sm']) ?>
            <?= $this->Html->link('Financeiro', ['controller' => 'Financeiro', 'action' => 'index'], ['class' => 'btn btn-default btn-sm']) ?>
        </div>
    </div>

    <div class="fin-card">
        <?php if (empty($centros)): ?>
            <p style="color:#484f58; text-align:center; padding:24px;">Nenhum centro de custo cadastrado.</p>
        <?php else: ?>
        <table class="fin-tbl">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Descrição</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($centros as $cc): ?>
                <tr>
                    <td><code><?= h($cc->codigo) ?></code></td>
                    <td><?= h($cc->descricao) ?></td>
                    <td><?= $cc->ativo ? '<span style="color:#3fb950;">Ativo</span>' : '<span style="color:#8b949e;">Inativo</span>' ?></td>
                    <td>
                        <?= $this->Html->link('Editar', ['action' => 'centroCustoEdit', $cc->id], ['class' => 'btn btn-xs btn-pgm btn-pgm-situacao']) ?>
                        <?= $this->Form->postLink('Excluir', ['action' => 'centroCustoDelete', $cc->id], [
                            'class' => 'btn btn-xs btn-outline-danger', 'confirm' => 'Excluir este centro de custo?',
                        ]) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
