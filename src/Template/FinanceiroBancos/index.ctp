<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Financeiro', ['controller' => 'Financeiro', 'action' => 'index']);
$this->Breadcrumbs->add('Bancos');

$totais = $totais ?? [
    'bancos' => 0,
    'ativos' => 0,
    'inativos' => 0,
    'receber' => 0,
    'pagar' => 0,
];
$bancos = $bancos ?? [];
?>
<style>
.fb-root { font-family:'DM Sans',sans-serif; }
.fb-topbar { display:flex; align-items:center; justify-content:space-between; padding:18px 24px 14px; border-bottom:1px solid rgba(255,255,255,.07); gap:12px; flex-wrap:wrap; }
.fb-h1 { font-size:20px; font-weight:600; color:#e6edf3; margin:0; }
.fb-h1-ico { color:#5cdbc0; margin-right:8px; }
.fb-actions { display:flex; gap:8px; flex-wrap:wrap; }

.fb-kpis { display:grid; grid-template-columns:repeat(auto-fit,minmax(210px,1fr)); gap:16px; padding:20px 24px 0; }
.fb-kpi { background:#161b22; border:1px solid rgba(255,255,255,.07); border-radius:12px; padding:18px; }
.fb-kpi-label { font-size:11px; text-transform:uppercase; letter-spacing:.05em; color:#7d8590; font-weight:700; margin-bottom:8px; }
.fb-kpi-value { font-size:28px; font-weight:700; color:#e6edf3; line-height:1.1; }
.fb-kpi-value--money { font-size:24px; }
.fb-kpi-help { margin-top:8px; color:#8b949e; font-size:12px; }

.fb-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(270px,1fr)); gap:16px; padding:20px 24px; }
.fb-card { background:#161b22; border:1px solid rgba(255,255,255,.07); border-radius:12px; padding:20px; text-decoration:none !important; color:inherit; transition:.18s ease; }
.fb-card:hover { border-color:rgba(92,219,192,.35); transform:translateY(-1px); }
.fb-card-ico { display:inline-flex; width:48px; height:48px; align-items:center; justify-content:center; border-radius:10px; background:rgba(29,158,117,.12); color:#5cdbc0; font-size:20px; margin-bottom:14px; }
.fb-card-title { font-size:16px; font-weight:700; color:#e6edf3; margin-bottom:6px; }
.fb-card-desc { color:#8b949e; font-size:13px; line-height:1.55; min-height:60px; }
.fb-card-meta { margin-top:12px; color:#5cdbc0; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; }

.fb-section { padding:0 24px 24px; }
.fb-panel { background:#161b22; border:1px solid rgba(255,255,255,.07); border-radius:12px; overflow:hidden; }
.fb-panel-head { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:16px 18px; border-bottom:1px solid rgba(255,255,255,.07); flex-wrap:wrap; }
.fb-panel-title { font-size:15px; font-weight:700; color:#e6edf3; margin:0; }
.fb-panel-sub { color:#7d8590; font-size:12px; }
.fb-table-wrap { overflow:auto; }
.fb-table { width:100%; border-collapse:collapse; }
.fb-table th { text-align:left; font-size:11px; text-transform:uppercase; letter-spacing:.05em; color:#7d8590; font-weight:700; padding:10px 14px; border-bottom:1px solid rgba(255,255,255,.07); }
.fb-table td { padding:12px 14px; color:#c9d1d9; border-bottom:1px solid rgba(255,255,255,.04); font-size:13px; vertical-align:middle; }
.fb-table tr:hover td { background:rgba(255,255,255,.02); }
.fb-badge { display:inline-block; padding:3px 8px; border-radius:999px; font-size:11px; font-weight:700; }
.fb-badge--ok { background:rgba(63,185,80,.14); color:#3fb950; }
.fb-badge--off { background:rgba(248,81,73,.14); color:#f85149; }
.fb-badge--warn { background:rgba(255,193,7,.14); color:#ffd166; }
.fb-code { color:#5cdbc0; font-weight:700; }
.fb-empty { padding:30px 20px; text-align:center; color:#8b949e; }
</style>

<div class="fb-root">
    <div class="fb-topbar">
        <h1 class="fb-h1"><i class="fas fa-university fb-h1-ico"></i>Módulo de Bancos</h1>
        <div class="fb-actions">
            <?= $this->Html->link('<i class="fas fa-plus"></i> Cadastrar banco', ['action' => 'add'], ['class' => 'btn btn-pgm btn-pgm-salvar btn-sm', 'escape' => false]) ?>
            <?= $this->Html->link('<i class="fas fa-list"></i> Ver cadastros', ['action' => 'cadastrar'], ['class' => 'btn btn-pgm btn-pgm-situacao btn-sm', 'escape' => false]) ?>
            <?= $this->Html->link('<i class="fas fa-arrow-left"></i> Financeiro', ['controller' => 'Financeiro', 'action' => 'index'], ['class' => 'btn btn-default btn-sm', 'escape' => false]) ?>
        </div>
    </div>

    <div class="fb-kpis">
        <div class="fb-kpi">
            <div class="fb-kpi-label">Bancos cadastrados</div>
            <div class="fb-kpi-value"><?= (int)$totais['bancos'] ?></div>
            <div class="fb-kpi-help">Total de cadastros bancários disponíveis no financeiro.</div>
        </div>
        <div class="fb-kpi">
            <div class="fb-kpi-label">Bancos ativos</div>
            <div class="fb-kpi-value"><?= (int)$totais['ativos'] ?></div>
            <div class="fb-kpi-help">Cadastros aptos para uso em lançamentos, remessas e relatórios.</div>
        </div>
        <div class="fb-kpi">
            <div class="fb-kpi-label">Previsto a receber</div>
            <div class="fb-kpi-value fb-kpi-value--money">R$ <?= number_format((float)$totais['receber'], 2, ',', '.') ?></div>
            <div class="fb-kpi-help">Receitas em aberto vinculadas aos bancos cadastrados.</div>
        </div>
        <div class="fb-kpi">
            <div class="fb-kpi-label">Previsto a pagar</div>
            <div class="fb-kpi-value fb-kpi-value--money">R$ <?= number_format((float)$totais['pagar'], 2, ',', '.') ?></div>
            <div class="fb-kpi-help">Despesas em aberto vinculadas aos bancos cadastrados.</div>
        </div>
    </div>

    <div class="fb-grid">
        <a href="<?= $this->Url->build(['action' => 'cadastrar']) ?>" class="fb-card">
            <span class="fb-card-ico"><i class="fas fa-folder-open"></i></span>
            <div class="fb-card-title">Cadastrar bancos</div>
            <div class="fb-card-desc">Gerencie os bancos do financeiro, pesquise por código bancário, preencha agência, conta, CNAB e demais configurações.</div>
            <div class="fb-card-meta">Financeiro → Bancos → Cadastro</div>
        </a>

        <a href="<?= $this->Url->build(['action' => 'remessa']) ?>" class="fb-card">
            <span class="fb-card-ico"><i class="fas fa-file-export"></i></span>
            <div class="fb-card-title">Criação de remessa bancária</div>
            <div class="fb-card-desc">Consulte títulos em aberto por banco e organize a geração operacional das remessas bancárias do financeiro.</div>
            <div class="fb-card-meta">Remessa</div>
        </a>

        <a href="<?= $this->Url->build(['action' => 'remessaMultiempresas']) ?>" class="fb-card">
            <span class="fb-card-ico"><i class="fas fa-layer-group"></i></span>
            <div class="fb-card-title">Remessa bancária multiempresas</div>
            <div class="fb-card-desc">Visualize a consolidação por banco para apoiar rotinas multiempresa e futuras expansões do processo bancário.</div>
            <div class="fb-card-meta">Remessa multiempresas</div>
        </a>

        <a href="<?= $this->Url->build(['action' => 'retorno']) ?>" class="fb-card">
            <span class="fb-card-ico"><i class="fas fa-file-import"></i></span>
            <div class="fb-card-title">Retornos bancários</div>
            <div class="fb-card-desc">Acompanhe o espaço do módulo destinado aos retornos bancários e à futura consolidação dos arquivos processados.</div>
            <div class="fb-card-meta">Retorno</div>
        </a>

        <a href="<?= $this->Url->build(['action' => 'relatorios']) ?>" class="fb-card">
            <span class="fb-card-ico"><i class="fas fa-chart-bar"></i></span>
            <div class="fb-card-title">Relatórios bancários</div>
            <div class="fb-card-desc">Acesse relação de bancos, relação de remessas, histórico de retorno e previsões financeiras agrupadas por banco.</div>
            <div class="fb-card-meta">Relatórios</div>
        </a>
    </div>

    <div class="fb-section">
        <div class="fb-panel">
            <div class="fb-panel-head">
                <div>
                    <h2 class="fb-panel-title">Últimos bancos cadastrados</h2>
                    <div class="fb-panel-sub">Visão rápida para alinhamento do módulo de bancos ao financeiro.</div>
                </div>
                <?= $this->Html->link('Abrir cadastro completo', ['action' => 'cadastrar'], ['class' => 'btn btn-default btn-sm']) ?>
            </div>

            <div class="fb-table-wrap">
                <?php if (empty($bancos)): ?>
                    <div class="fb-empty">
                        Nenhum banco cadastrado ainda. Use o botão <strong>Cadastrar banco</strong> para iniciar o módulo.
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
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($bancos, 0, 8) as $banco): ?>
                                <tr>
                                    <td><span class="fb-code"><?= h($banco->codigo_banco ?: '—') ?></span></td>
                                    <td><?= h($banco->nome ?: '—') ?></td>
                                    <td><?= h($banco->cnab ?: '—') ?></td>
                                    <td>
                                        <?= h($banco->numero_agencia ?: '—') ?><?= !empty($banco->digito_agencia) ? '-' . h($banco->digito_agencia) : '' ?>
                                        <?php if (empty($banco->numero_agencia)): ?>
                                            <div class="fb-muted">Agência não informada</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= h($banco->numero_conta ?: '—') ?><?= !empty($banco->digito_conta) ? '-' . h($banco->digito_conta) : '' ?>
                                        <?php if (empty($banco->numero_conta)): ?>
                                            <div class="fb-muted">Conta não informada</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($banco->ativo)): ?>
                                            <span class="fb-badge fb-badge--ok">Ativo</span>
                                        <?php else: ?>
                                            <span class="fb-badge fb-badge--off">Inativo</span>
                                        <?php endif; ?>
                                        <?php if (empty($banco->numero_agencia) || empty($banco->numero_conta)): ?>
                                            <div style="margin-top:6px;">
                                                <span class="fb-badge fb-badge--warn">Cadastro incompleto</span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
