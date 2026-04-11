<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Financeiro', ['controller' => 'Financeiro', 'action' => 'index']);
$this->Breadcrumbs->add('Relatórios');
?>
<style>
.fr-root { font-family:'DM Sans',sans-serif; }
.fr-topbar { display:flex; align-items:center; justify-content:space-between; padding:18px 24px 14px; border-bottom:1px solid rgba(255,255,255,.07); }
.fr-h1 { font-size:20px; font-weight:600; color:#e6edf3; }
.fr-h1-ico { color:#5cdbc0; margin-right:8px; }
.fr-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:16px; padding:20px 24px; }
.fr-card { background:#161b22; border:1px solid rgba(255,255,255,.07); border-radius:10px; padding:20px; transition:border-color .15s; }
.fr-card:hover { border-color:rgba(92,219,192,.3); }
.fr-card-title { font-size:15px; font-weight:600; color:#e6edf3; margin-bottom:6px; }
.fr-card-desc { font-size:12.5px; color:#7d8590; line-height:1.5; margin-bottom:12px; }
.fr-card-ico { font-size:28px; color:#5cdbc0; margin-bottom:12px; display:block; }
</style>

<div class="fr-root">
    <div class="fr-topbar">
        <div class="fr-h1"><i class="fas fa-chart-bar fr-h1-ico"></i>Relatórios Financeiros</div>
        <div>
            <?= $this->Html->link('<i class="fas fa-arrow-left"></i> Dashboard', ['controller' => 'Financeiro', 'action' => 'index'], ['class' => 'btn btn-default btn-sm', 'escape' => false]) ?>
        </div>
    </div>

    <div class="fr-grid">
        <a href="<?= $this->Url->build(['action' => 'aging']) ?>" class="fr-card" style="text-decoration:none;">
            <i class="fas fa-hourglass-half fr-card-ico"></i>
            <div class="fr-card-title">Aging</div>
            <div class="fr-card-desc">Títulos em aberto por faixa de atraso (a vencer, 1-30, 31-60, 61-90, >90 dias).</div>
        </a>

        <a href="<?= $this->Url->build(['action' => 'inadimplencia']) ?>" class="fr-card" style="text-decoration:none;">
            <i class="fas fa-exclamation-triangle fr-card-ico"></i>
            <div class="fr-card-title">Inadimplência</div>
            <div class="fr-card-desc">Ranking de clientes por valor em atraso e dias de inadimplência.</div>
        </a>

        <a href="<?= $this->Url->build(['action' => 'porCentroCusto']) ?>" class="fr-card" style="text-decoration:none;">
            <i class="fas fa-sitemap fr-card-ico"></i>
            <div class="fr-card-title">Por Centro de Custo</div>
            <div class="fr-card-desc">Receitas e despesas agrupadas por centro de custo no período.</div>
        </a>

        <a href="<?= $this->Url->build(['controller' => 'Financeiro', 'action' => 'dre']) ?>" class="fr-card" style="text-decoration:none;">
            <i class="fas fa-chart-pie fr-card-ico"></i>
            <div class="fr-card-title">DRE</div>
            <div class="fr-card-desc">Demonstrativo de Resultado do Exercício por plano de contas.</div>
        </a>
    </div>
</div>
