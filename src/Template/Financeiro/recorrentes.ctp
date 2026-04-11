<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Financeiro', ['controller' => 'Financeiro', 'action' => 'index']);
$this->Breadcrumbs->add('Lançamentos Recorrentes');

$freqMap = ['semanal' => 'Semanal', 'quinzenal' => 'Quinzenal', 'mensal' => 'Mensal', 'trimestral' => 'Trimestral', 'anual' => 'Anual'];
?>
<style>
.rc-root { font-family:'DM Sans',sans-serif; }
.rc-topbar { display:flex; align-items:center; justify-content:space-between; padding:16px 24px 12px; border-bottom:1px solid rgba(255,255,255,.07); flex-wrap:wrap; gap:10px; }
.rc-h1 { font-size:18px; font-weight:600; color:#e6edf3; }
.rc-h1-ico { color:#5cdbc0; margin-right:8px; }
.rc-table-wrap { padding:20px 24px; }
table.rc-table { width:100%; border-collapse:collapse; font-size:13px; }
.rc-table th { background:#161b22; color:#7d8590; font-size:11px; text-transform:uppercase; letter-spacing:.05em; font-weight:600; padding:8px 12px; border-bottom:1px solid rgba(255,255,255,.07); text-align:left; }
.rc-table td { padding:10px 12px; border-bottom:1px solid rgba(255,255,255,.04); color:#c9d1d9; vertical-align:middle; }
.rc-table tr:hover td { background:rgba(255,255,255,.03); }
.rc-badge { display:inline-block; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:600; }
.rc-badge-active { background:rgba(63,185,80,.13); color:#3fb950; }
.rc-badge-inactive { background:rgba(255,255,255,.08); color:#9ca3af; }
.rc-badge-receita { background:rgba(29,158,117,.14); color:#5cdbc0; }
.rc-badge-despesa { background:rgba(248,81,73,.13); color:#f85149; }
.rc-zero { text-align:center; padding:48px; color:#484f58; }
.rc-zero-ico { font-size:32px; display:block; margin-bottom:10px; opacity:.3; }
.rc-conta-muted { font-size:11px; color:#7d8590; }
</style>

<div class="rc-root">
    <div class="rc-topbar">
        <div class="rc-h1"><i class="fas fa-redo rc-h1-ico"></i>Lançamentos Recorrentes</div>
        <div>
            <?= $this->Html->link('<i class="fas fa-plus"></i> Novo recorrente', ['action' => 'addRecorrente'], ['class' => 'btn btn-pgm btn-pgm-salvar btn-sm', 'escape' => false]) ?>
            <?= $this->Html->link('<i class="fas fa-arrow-left"></i> Dashboard', ['action' => 'index'], ['class' => 'btn btn-default btn-sm', 'escape' => false]) ?>
        </div>
    </div>

    <div class="rc-table-wrap">
        <?php if (empty($templates)): ?>
            <div class="rc-zero">
                <i class="fas fa-redo rc-zero-ico"></i>
                Nenhum lançamento recorrente cadastrado.
            </div>
        <?php else: ?>
        <table class="rc-table" id="tabRecorrentes">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Descrição</th>
                    <th>Tipo</th>
                    <th>Valor</th>
                    <th>Frequência</th>
                    <th>Dia venc.</th>
                    <th>Início</th>
                    <th>Fim</th>
                    <th>Última geração</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($templates as $t): ?>
                <tr>
                    <td><small style="color:#7d8590;">#<?= $t->id ?></small></td>
                    <td>
                        <?= h($t->descricao) ?>
                        <?php if (!empty($t->cliente)): ?>
                            <br><small class="rc-conta-muted"><?= h($t->cliente->razaosocial ?? $t->cliente->nome ?? '') ?></small>
                        <?php endif; ?>
                    </td>
                    <td><span class="rc-badge rc-badge-<?= $t->tipo ?>"><?= ucfirst($t->tipo) ?></span></td>
                    <td><strong>R$ <?= number_format((float)$t->valor, 2, ',', '.') ?></strong></td>
                    <td><?= $freqMap[$t->frequencia] ?? $t->frequencia ?></td>
                    <td><?= (int)$t->dia_vencimento ?></td>
                    <td><?= $t->data_inicio ? $t->data_inicio->format('d/m/Y') : '—' ?></td>
                    <td><?= $t->data_fim ? $t->data_fim->format('d/m/Y') : 'Indeterminado' ?></td>
                    <td><?= $t->ultima_geracao ? $t->ultima_geracao->format('d/m/Y') : 'Nunca' ?></td>
                    <td>
                        <span class="rc-badge <?= $t->ativo ? 'rc-badge-active' : 'rc-badge-inactive' ?>">
                            <?= $t->ativo ? 'Ativo' : 'Inativo' ?>
                        </span>
                    </td>
                    <td style="white-space:nowrap;">
                        <?= $this->Html->link('<i class="fas fa-edit"></i>', ['action' => 'editRecorrente', $t->id], ['class' => 'btn btn-default btn-xs m-r-5', 'escape' => false, 'title' => 'Editar']) ?>
                        <?= $this->Form->postLink('<i class="fas fa-trash"></i>', ['action' => 'deleteRecorrente', $t->id], ['class' => 'btn btn-danger btn-xs', 'escape' => false, 'confirm' => 'Remover este lançamento recorrente?', 'title' => 'Excluir']) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<script>
$(function() {
    if ($.fn.DataTable) {
        $('#tabRecorrentes').DataTable({
            order: [[0, 'desc']],
            pageLength: 25,
            language: { url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Portuguese-Brasil.json' }
        });
    }
});
</script>
