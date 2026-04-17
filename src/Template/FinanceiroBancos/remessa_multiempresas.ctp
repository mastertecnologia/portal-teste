<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Financeiro', ['controller' => 'Financeiro', 'action' => 'index']);
$this->Breadcrumbs->add('Bancos', ['controller' => 'FinanceiroBancos', 'action' => 'index']);
$this->Breadcrumbs->add('Remessa bancária multiempresas');

$resumo = $resumo ?? [];
$totalGeral = 0.0;
$totalTitulos = 0;

foreach ($resumo as $item) {
    $totalGeral += (float)($item['total'] ?? 0);
    $totalTitulos += (int)($item['quantidade'] ?? 0);
}
?>
<style>
.fb-root { font-family:'DM Sans',sans-serif; }
.fb-topbar { display:flex; align-items:center; justify-content:space-between; padding:18px 24px 14px; border-bottom:1px solid rgba(255,255,255,.07); gap:12px; flex-wrap:wrap; }
.fb-h1 { font-size:20px; font-weight:600; color:#e6edf3; margin:0; }
.fb-h1-ico { color:#5cdbc0; margin-right:8px; }
.fb-sub { color:#7d8590; font-size:12.5px; margin-top:4px; }
.fb-wrap { padding:20px 24px; }
.fb-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:14px; margin-bottom:18px; }
.fb-kpi { background:#161b22; border:1px solid rgba(255,255,255,.07); border-radius:10px; padding:16px 18px; }
.fb-kpi-label { color:#7d8590; font-size:11px; text-transform:uppercase; letter-spacing:.06em; font-weight:600; margin-bottom:6px; }
.fb-kpi-value { color:#e6edf3; font-size:24px; font-weight:700; line-height:1.1; }
.fb-card { background:#161b22; border:1px solid rgba(255,255,255,.07); border-radius:10px; overflow:hidden; }
.fb-card-head { display:flex; align-items:center; justify-content:space-between; padding:14px 18px; border-bottom:1px solid rgba(255,255,255,.06); gap:12px; flex-wrap:wrap; }
.fb-card-title { color:#e6edf3; font-size:15px; font-weight:600; }
.fb-card-desc { color:#7d8590; font-size:12.5px; }
.fb-table-wrap { overflow:auto; }
.fb-table { width:100%; border-collapse:collapse; font-size:13px; }
.fb-table th { background:#0f141a; color:#7d8590; font-size:11px; text-transform:uppercase; letter-spacing:.05em; font-weight:600; padding:10px 12px; border-bottom:1px solid rgba(255,255,255,.07); text-align:left; white-space:nowrap; }
.fb-table td { padding:11px 12px; border-bottom:1px solid rgba(255,255,255,.05); color:#c9d1d9; vertical-align:middle; }
.fb-table tr:hover td { background:rgba(255,255,255,.02); }
.fb-code { display:inline-block; font-size:12px; color:#5cdbc0; background:rgba(92,219,192,.08); border:1px solid rgba(92,219,192,.15); border-radius:999px; padding:3px 8px; }
.fb-badge { display:inline-block; padding:3px 8px; border-radius:999px; font-size:11px; font-weight:700; }
.fb-badge-ok { color:#3fb950; background:rgba(63,185,80,.12); }
.fb-badge-off { color:#8b949e; background:rgba(139,148,158,.16); }
.fb-empty { padding:36px 20px; text-align:center; color:#7d8590; }
.fb-empty i { display:block; font-size:30px; margin-bottom:10px; opacity:.35; }
.fb-total-row td { color:#5cdbc0; font-weight:700; background:rgba(29,158,117,.06); }
.fb-actions .btn { margin-left:6px; }
.fb-muted { color:#7d8590; font-size:12px; }
</style>

<div class="fb-root">
    <div class="fb-topbar">
        <div>
            <h1 class="fb-h1"><i class="fas fa-layer-group fb-h1-ico"></i>Criação de Remessa Bancária Multiempresas</h1>
            <div class="fb-sub">Visão consolidada dos bancos ativos e dos títulos em aberto vinculados para geração de remessa.</div>
        </div>
        <div class="fb-actions">
            <?= $this->Html->link('<i class="fas fa-university"></i> Bancos', ['controller' => 'FinanceiroBancos', 'action' => 'cadastrar'], ['class' => 'btn btn-default btn-sm', 'escape' => false]) ?>
            <?= $this->Html->link('<i class="fas fa-file-export"></i> Remessa simples', ['controller' => 'FinanceiroBancos', 'action' => 'remessa'], ['class' => 'btn btn-pgm btn-pgm-situacao btn-sm', 'escape' => false]) ?>
            <?= $this->Html->link('<i class="fas fa-arrow-left"></i> Voltar ao módulo', ['controller' => 'FinanceiroBancos', 'action' => 'index'], ['class' => 'btn btn-default btn-sm', 'escape' => false]) ?>
        </div>
    </div>

    <div class="fb-wrap">
        <div class="fb-grid">
            <div class="fb-kpi">
                <div class="fb-kpi-label">Bancos consolidados</div>
                <div class="fb-kpi-value"><?= count($resumo) ?></div>
            </div>
            <div class="fb-kpi">
                <div class="fb-kpi-label">Títulos em aberto</div>
                <div class="fb-kpi-value"><?= $totalTitulos ?></div>
            </div>
            <div class="fb-kpi">
                <div class="fb-kpi-label">Valor total previsto</div>
                <div class="fb-kpi-value">R$ <?= number_format($totalGeral, 2, ',', '.') ?></div>
            </div>
        </div>

        <div class="fb-card">
            <div class="fb-card-head">
                <div>
                    <div class="fb-card-title">Resumo consolidado por banco</div>
                    <div class="fb-card-desc">Use esta visão para identificar rapidamente quais bancos possuem carteira pronta para futura geração de remessa multiempresas.</div>
                </div>
            </div>

            <div class="fb-table-wrap">
                <?php if (empty($resumo)): ?>
                    <div class="fb-empty">
                        <i class="fas fa-folder-open"></i>
                        Nenhum banco ativo encontrado para consolidação.
                    </div>
                <?php else: ?>
                    <table class="fb-table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Banco</th>
                                <th>CNAB</th>
                                <th>Agência</th>
                                <th>Conta</th>
                                <th>Status</th>
                                <th>Qtd. títulos</th>
                                <th>Valor previsto</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resumo as $item): ?>
                                <?php
                                $banco = $item['banco'] ?? null;
                                if (!$banco) {
                                    continue;
                                }

                                $agencia = trim((string)($banco->numero_agencia ?? ''));
                                $digAgencia = trim((string)($banco->digito_agencia ?? ''));
                                $conta = trim((string)($banco->numero_conta ?? ''));
                                $digConta = trim((string)($banco->digito_conta ?? ''));
                                ?>
                                <tr>
                                    <td><span class="fb-code"><?= h($banco->codigo_banco ?: '—') ?></span></td>
                                    <td>
                                        <strong><?= h($banco->nome) ?></strong>
                                        <?php if (!empty($banco->codigo_banco_interno)): ?>
                                            <div class="fb-muted">Interno: <?= h($banco->codigo_banco_interno) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= h($banco->cnab ?: '—') ?></td>
                                    <td><?= h($agencia !== '' ? $agencia . ($digAgencia !== '' ? '-' . $digAgencia : '') : '—') ?></td>
                                    <td><?= h($conta !== '' ? $conta . ($digConta !== '' ? '-' . $digConta : '') : '—') ?></td>
                                    <td>
                                        <?php if (!empty($banco->ativo)): ?>
                                            <span class="fb-badge fb-badge-ok">Ativo</span>
                                        <?php else: ?>
                                            <span class="fb-badge fb-badge-off">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= (int)($item['quantidade'] ?? 0) ?></td>
                                    <td><strong>R$ <?= number_format((float)($item['total'] ?? 0), 2, ',', '.') ?></strong></td>
                                    <td>
                                        <?= $this->Html->link('Ver remessa', ['controller' => 'FinanceiroBancos', 'action' => 'remessa', '?' => ['banco_id' => $banco->id]], ['class' => 'btn btn-xs btn-pgm btn-pgm-salvar']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="fb-total-row">
                                <td colspan="6">Total consolidado</td>
                                <td><?= $totalTitulos ?></td>
                                <td>R$ <?= number_format($totalGeral, 2, ',', '.') ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
