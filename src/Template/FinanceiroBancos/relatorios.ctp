<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Financeiro', ['controller' => 'Financeiro', 'action' => 'index']);
$this->Breadcrumbs->add('Bancos', ['controller' => 'FinanceiroBancos', 'action' => 'index']);
$this->Breadcrumbs->add('Relatórios');
?>
<style>
.fb-rel-root { font-family:'DM Sans',sans-serif; }
.fb-rel-topbar {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    padding:18px 24px 14px;
    border-bottom:1px solid rgba(255,255,255,.07);
    flex-wrap:wrap;
}
.fb-rel-title {
    font-size:20px;
    font-weight:600;
    color:#e6edf3;
}
.fb-rel-title i {
    color:#5cdbc0;
    margin-right:8px;
}
.fb-rel-subtitle {
    color:#7d8590;
    font-size:12.5px;
    margin-top:4px;
}
.fb-rel-kpis {
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));
    gap:14px;
    padding:20px 24px 0;
}
.fb-rel-kpi {
    background:#161b22;
    border:1px solid rgba(255,255,255,.07);
    border-radius:10px;
    padding:16px 18px;
}
.fb-rel-kpi-label {
    color:#7d8590;
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:.05em;
    font-weight:600;
    margin-bottom:6px;
}
.fb-rel-kpi-value {
    color:#e6edf3;
    font-size:24px;
    font-weight:700;
    line-height:1.1;
}
.fb-rel-kpi-value--money {
    font-size:20px;
}
.fb-rel-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));
    gap:16px;
    padding:20px 24px;
}
.fb-rel-card {
    display:block;
    text-decoration:none !important;
    background:#161b22;
    border:1px solid rgba(255,255,255,.07);
    border-radius:10px;
    padding:20px;
    transition:.18s ease;
}
.fb-rel-card:hover {
    border-color:rgba(92,219,192,.35);
    transform:translateY(-2px);
    box-shadow:0 10px 24px rgba(0,0,0,.18);
}
.fb-rel-card-ico {
    font-size:28px;
    color:#5cdbc0;
    margin-bottom:12px;
    display:block;
}
.fb-rel-card-title {
    font-size:15px;
    font-weight:600;
    color:#e6edf3;
    margin-bottom:6px;
}
.fb-rel-card-desc {
    font-size:12.5px;
    color:#7d8590;
    line-height:1.5;
}
.fb-rel-help {
    margin:0 24px 24px;
    background:rgba(29,158,117,.08);
    border:1px solid rgba(29,158,117,.16);
    border-radius:10px;
    padding:14px 16px;
    color:#c9d1d9;
    font-size:12.5px;
    line-height:1.6;
}
.fb-rel-help strong {
    color:#5cdbc0;
}
</style>

<div class="fb-rel-root">
    <div class="fb-rel-topbar">
        <div>
            <div class="fb-rel-title">
                <i class="fas fa-chart-bar"></i>Relatórios Bancários
            </div>
            <div class="fb-rel-subtitle">
                Consultas e visões consolidadas do módulo Financeiro &gt; Bancos, com foco em remessa, retorno e previsões financeiras.
            </div>
        </div>
        <div>
            <?= $this->Html->link(
                '<i class="fas fa-university"></i> Bancos',
                ['controller' => 'FinanceiroBancos', 'action' => 'index'],
                ['class' => 'btn btn-default btn-sm', 'escape' => false]
            ) ?>
        </div>
    </div>

    <?php
    $resumoRelatorios = $resumoRelatorios ?? [
        'bancos' => 0,
        'ativos' => 0,
        'incompletos' => 0,
        'com_movimento' => 0,
        'total_receber' => 0,
        'total_recebido' => 0,
        'total_pagar' => 0,
        'total_pago' => 0,
    ];
    ?>

    <div class="fb-rel-kpis">
        <div class="fb-rel-kpi">
            <div class="fb-rel-kpi-label">Bancos cadastrados</div>
            <div class="fb-rel-kpi-value"><?= number_format((int)$resumoRelatorios['bancos'], 0, ',', '.') ?></div>
        </div>
        <div class="fb-rel-kpi">
            <div class="fb-rel-kpi-label">Bancos ativos</div>
            <div class="fb-rel-kpi-value"><?= number_format((int)$resumoRelatorios['ativos'], 0, ',', '.') ?></div>
        </div>
        <div class="fb-rel-kpi">
            <div class="fb-rel-kpi-label">Cadastros incompletos</div>
            <div class="fb-rel-kpi-value"><?= number_format((int)$resumoRelatorios['incompletos'], 0, ',', '.') ?></div>
        </div>
        <div class="fb-rel-kpi">
            <div class="fb-rel-kpi-label">Bancos com movimento</div>
            <div class="fb-rel-kpi-value"><?= number_format((int)$resumoRelatorios['com_movimento'], 0, ',', '.') ?></div>
        </div>
        <div class="fb-rel-kpi">
            <div class="fb-rel-kpi-label">Total a receber</div>
            <div class="fb-rel-kpi-value fb-rel-kpi-value--money">R$ <?= number_format((float)$resumoRelatorios['total_receber'], 2, ',', '.') ?></div>
        </div>
        <div class="fb-rel-kpi">
            <div class="fb-rel-kpi-label">Total recebido</div>
            <div class="fb-rel-kpi-value fb-rel-kpi-value--money">R$ <?= number_format((float)$resumoRelatorios['total_recebido'], 2, ',', '.') ?></div>
        </div>
        <div class="fb-rel-kpi">
            <div class="fb-rel-kpi-label">Total a pagar</div>
            <div class="fb-rel-kpi-value fb-rel-kpi-value--money">R$ <?= number_format((float)$resumoRelatorios['total_pagar'], 2, ',', '.') ?></div>
        </div>
        <div class="fb-rel-kpi">
            <div class="fb-rel-kpi-label">Total pago</div>
            <div class="fb-rel-kpi-value fb-rel-kpi-value--money">R$ <?= number_format((float)$resumoRelatorios['total_pago'], 2, ',', '.') ?></div>
        </div>
    </div>

    <div class="fb-rel-grid">
        <a href="<?= $this->Url->build(['controller' => 'FinanceiroBancos', 'action' => 'relacaoBancos']) ?>" class="fb-rel-card">
            <i class="fas fa-list fb-rel-card-ico"></i>
            <div class="fb-rel-card-title">Relação de Bancos</div>
            <div class="fb-rel-card-desc">
                Lista completa dos bancos cadastrados no financeiro, com código bancário, CNAB, agência, conta e status.
            </div>
        </a>

        <a href="<?= $this->Url->build(['controller' => 'FinanceiroBancos', 'action' => 'relacaoRemessas']) ?>" class="fb-rel-card">
            <i class="fas fa-file-export fb-rel-card-ico"></i>
            <div class="fb-rel-card-title">Relação de Remessas Bancárias</div>
            <div class="fb-rel-card-desc">
                Visão resumida das remessas previstas por banco com quantidade de títulos e total financeiro em aberto.
            </div>
        </a>

        <a href="<?= $this->Url->build(['controller' => 'FinanceiroBancos', 'action' => 'historicoRetorno']) ?>" class="fb-rel-card">
            <i class="fas fa-history fb-rel-card-ico"></i>
            <div class="fb-rel-card-title">Histórico de Retorno Bancário</div>
            <div class="fb-rel-card-desc">
                Painel preparado para acompanhamento de retornos bancários e evolução futura das integrações do módulo.
            </div>
        </a>

        <a href="<?= $this->Url->build(['controller' => 'FinanceiroBancos', 'action' => 'previsaoRecebimentosPorBanco']) ?>" class="fb-rel-card">
            <i class="fas fa-hand-holding-usd fb-rel-card-ico"></i>
            <div class="fb-rel-card-title">Previsão de Recebimentos por Banco</div>
            <div class="fb-rel-card-desc">
                Apura os títulos a receber agrupados por banco, com totais e próximo vencimento previsto.
            </div>
        </a>

        <a href="<?= $this->Url->build(['controller' => 'FinanceiroBancos', 'action' => 'previsaoPorBancos']) ?>" class="fb-rel-card">
            <i class="fas fa-chart-line fb-rel-card-ico"></i>
            <div class="fb-rel-card-title">Previsão por Bancos</div>
            <div class="fb-rel-card-desc">
                Consolida valores a receber, recebidos, a pagar e pagos por banco vinculado aos lançamentos financeiros.
            </div>
        </a>
    </div>

    <div class="fb-rel-help">
        <strong>Leitura operacional:</strong> use <strong>Relação de Bancos</strong> para revisar cadastros e identificar contas incompletas,
        <strong>Relação de Remessas Bancárias</strong> para acompanhar títulos em aberto por banco,
        <strong>Histórico de Retorno Bancário</strong> para verificar extratos importados e pendências de conciliação,
        <strong>Previsão de Recebimentos por Banco</strong> para priorizar cobranças por vencimento
        e <strong>Previsão por Bancos</strong> para enxergar o saldo previsto consolidado entre receber e pagar.
        <br><br>
        <strong>Dica:</strong> para que os relatórios reflitam corretamente o financeiro, vincule os lançamentos de
        contas a receber e contas a pagar ao banco correspondente no cadastro do módulo e mantenha agência/conta preenchidas no cadastro bancário.
    </div>
</div>
