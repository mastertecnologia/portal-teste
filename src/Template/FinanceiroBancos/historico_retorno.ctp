<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Financeiro', ['controller' => 'Financeiro', 'action' => 'index']);
$this->Breadcrumbs->add('Bancos', ['controller' => 'FinanceiroBancos', 'action' => 'index']);
$this->Breadcrumbs->add('Histórico de Retorno Bancário');

$historico = $historico ?? [];
?>
<style>
.fb-root { font-family:'DM Sans',sans-serif; }
.fb-topbar { display:flex; align-items:center; justify-content:space-between; padding:18px 24px 14px; border-bottom:1px solid rgba(255,255,255,.07); gap:12px; flex-wrap:wrap; }
.fb-h1 { font-size:20px; font-weight:600; color:#e6edf3; margin:0; }
.fb-h1-ico { color:#5cdbc0; margin-right:8px; }
.fb-actions { display:flex; gap:8px; flex-wrap:wrap; }
.fb-card { background:#161b22; border:1px solid rgba(255,255,255,.07); border-radius:10px; padding:18px 20px; margin:16px 24px; }
.fb-table-wrap { overflow:auto; }
.fb-table { width:100%; border-collapse:collapse; font-size:12.5px; min-width:860px; }
.fb-table th { color:#7d8590; font-size:10.5px; text-transform:uppercase; letter-spacing:.05em; font-weight:600; padding:8px 10px; border-bottom:1px solid rgba(255,255,255,.07); text-align:left; }
.fb-table td { padding:10px; border-bottom:1px solid rgba(255,255,255,.04); color:#c9d1d9; vertical-align:top; }
.fb-table tr:hover td { background:rgba(255,255,255,.02); }
.fb-status { display:inline-block; padding:3px 9px; border-radius:999px; font-size:11px; font-weight:700; }
.fb-status--implantacao { background:rgba(255,193,7,.16); color:#ffd166; }
.fb-status--sucesso { background:rgba(63,185,80,.16); color:#3fb950; }
.fb-status--erro { background:rgba(248,81,73,.16); color:#f85149; }
.fb-muted { color:#8b949e; }
.fb-empty { text-align:center; padding:28px 12px; color:#8b949e; }
.fb-note { margin:0 24px 20px; color:#8b949e; font-size:12px; }
</style>

<div class="fb-root">
    <div class="fb-topbar">
        <h1 class="fb-h1"><i class="fas fa-history fb-h1-ico"></i>Histórico de Retorno Bancário</h1>
        <div class="fb-actions">
            <?= $this->Html->link('Relatórios bancários', ['controller' => 'FinanceiroBancos', 'action' => 'relatorios'], ['class' => 'btn btn-pgm btn-pgm-situacao btn-sm']) ?>
            <?= $this->Html->link('Bancos', ['controller' => 'FinanceiroBancos', 'action' => 'index'], ['class' => 'btn btn-default btn-sm']) ?>
        </div>
    </div>

    <div class="fb-card">
        <div class="fb-table-wrap">
            <table class="fb-table">
                <thead>
                    <tr>
                        <th>Banco</th>
                        <th>Código</th>
                        <th>CNAB</th>
                        <th>Status</th>
                        <th>Descrição</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($historico)): ?>
                        <tr>
                            <td colspan="5" class="fb-empty">Nenhum histórico de retorno bancário disponível.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($historico as $item): ?>
                            <?php
                                $banco = $item['banco'] ?? null;
                                $status = trim((string)($item['status'] ?? 'Em implantação'));
                                $descricao = trim((string)($item['descricao'] ?? ''));
                                $statusClass = 'fb-status--implantacao';

                                if (mb_strtolower($status) === 'sucesso') {
                                    $statusClass = 'fb-status--sucesso';
                                } elseif (mb_strtolower($status) === 'erro') {
                                    $statusClass = 'fb-status--erro';
                                }
                            ?>
                            <tr>
                                <td><?= h($banco->nome ?? '—') ?></td>
                                <td>
                                    <?php if (!empty($banco->codigo_banco)): ?>
                                        <code><?= h($banco->codigo_banco) ?></code>
                                    <?php else: ?>
                                        <span class="fb-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($banco->cnab)): ?>
                                        <code><?= h($banco->cnab) ?></code>
                                    <?php else: ?>
                                        <span class="fb-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="fb-status <?= h($statusClass) ?>"><?= h($status) ?></span>
                                </td>
                                <td><?= h($descricao !== '' ? $descricao : 'Sem detalhes informados.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <p class="fb-note">
        Este relatório já está alinhado ao módulo financeiro e preparado para receber,
        futuramente, os eventos processados a partir de arquivos de retorno bancário.
    </p>
</div>
