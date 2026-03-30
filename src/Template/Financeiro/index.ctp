<?php
/**
 * Financeiro — Dashboard
 */
use Cake\Routing\Router;
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Financeiro');

$graficoLabels  = array_map(function($m) {
    $d = \DateTime::createFromFormat('Y-m', $m);
    return $d ? $d->format('M/y') : $m;
}, array_keys($grafico));
$graficoReceita = array_column($grafico, 'receita');
$graficoDespesa = array_column($grafico, 'despesa');
?>
<style>
.fin-root { font-family:'DM Sans',sans-serif; }
.fin-topbar { display:flex; align-items:center; justify-content:space-between; padding:18px 24px 14px; border-bottom:1px solid rgba(255,255,255,.07); }
.fin-h1 { font-size:20px; font-weight:600; color:#e6edf3; }
.fin-kpi-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:0; border-bottom:1px solid rgba(255,255,255,.07); }
.fin-kpi { padding:16px 20px; border-right:1px solid rgba(255,255,255,.07); }
.fin-kpi:last-child { border-right:none; }
.fin-kpi-label { font-size:10px; text-transform:uppercase; letter-spacing:.08em; color:#7d8590; font-weight:600; margin-bottom:6px; }
.fin-kpi-val { font-size:20px; font-weight:700; color:#e6edf3; }
.fin-kpi-val.green { color:#3fb950; }
.fin-kpi-val.teal { color:#5cdbc0; }
.fin-kpi-val.red { color:#f85149; }
.fin-kpi-val.orange { color:#ff8833; }
.fin-body { padding:20px 24px; display:grid; grid-template-columns:2fr 1fr; gap:20px; }
.fin-card { background:#161b22; border:1px solid rgba(255,255,255,.07); border-radius:10px; padding:18px 20px; }
.fin-card-title { font-size:11px; text-transform:uppercase; letter-spacing:.08em; color:#7d8590; font-weight:600; margin-bottom:14px; }
.fin-nav { display:flex; gap:10px; padding:12px 24px 0; }
.fin-nav a { padding:7px 14px; border-radius:20px; font-size:12.5px; font-weight:500; color:#7d8590; text-decoration:none; transition:all .15s; }
.fin-nav a:hover { color:#e6edf3; background:rgba(255,255,255,.06); }
.fin-nav a.active { background:rgba(0,192,139,.14); color:#5cdbc0; }
/* Tabela vencimentos */
.fin-tbl { width:100%; border-collapse:collapse; font-size:12.5px; }
.fin-tbl th { color:#7d8590; font-size:10.5px; text-transform:uppercase; letter-spacing:.05em; font-weight:600; padding:5px 8px; border-bottom:1px solid rgba(255,255,255,.07); text-align:left; }
.fin-tbl td { padding:8px 8px; border-bottom:1px solid rgba(255,255,255,.04); color:#c9d1d9; }
.fin-tbl tr:hover td { background:rgba(255,255,255,.02); }
.fin-badge-venc { display:inline-block; padding:1px 7px; border-radius:9px; font-size:11px; font-weight:600; background:rgba(248,81,73,.13); color:#f85149; }
.fin-empty { text-align:center; padding:32px; color:#484f58; font-size:13px; }
/* Modo claro */
body.pgm-theme-light .fin-h1 { color:#1a1f2e; }
body.pgm-theme-light .fin-kpi-label { color:#6b7280; }
body.pgm-theme-light .fin-kpi-val { color:#1a1f2e; }
body.pgm-theme-light .fin-topbar, body.pgm-theme-light .fin-kpi-grid,
body.pgm-theme-light .fin-kpi { border-color:#e1e4e8; }
body.pgm-theme-light .fin-card { background:#fff; border-color:#e1e4e8; }
body.pgm-theme-light .fin-card-title { color:#6b7280; }
body.pgm-theme-light .fin-nav a { color:#6b7280; }
body.pgm-theme-light .fin-nav a:hover { color:#1a1f2e; background:rgba(0,0,0,.04); }
body.pgm-theme-light .fin-nav a.active { background:rgba(0,168,118,.10); color:#00a876; }
body.pgm-theme-light .fin-tbl th { color:#6b7280; border-color:#e5e7eb; }
body.pgm-theme-light .fin-tbl td { color:#374151; border-color:#f3f4f6; }
body.pgm-theme-light .fin-empty { color:#9ca3af; }
@media(max-width:768px){ .fin-body { grid-template-columns:1fr; } }
</style>

<div class="fin-root">
    <!-- Topbar -->
    <div class="fin-topbar">
        <div class="fin-h1"><i class="fas fa-chart-line" style="color:#5cdbc0;margin-right:8px"></i>Financeiro</div>
        <div style="display:flex;gap:8px">
            <?= $this->Html->link('<i class="fas fa-list"></i> Contas a Receber', ['action' => 'contasReceber'], ['class' => 'btn btn-pgm btn-pgm-situacao btn-sm', 'escape' => false]) ?>
            <?= $this->Html->link('<i class="fas fa-plus"></i> Novo Lançamento', ['controller' => 'Faturamento', 'action' => 'add'], ['class' => 'btn btn-pgm btn-pgm-salvar btn-sm', 'escape' => false]) ?>
        </div>
    </div>

    <!-- KPIs -->
    <div class="fin-kpi-grid">
        <div class="fin-kpi">
            <div class="fin-kpi-label">Total Receitas</div>
            <div class="fin-kpi-val teal">R$ <?= number_format($kpi['total_receitas'], 2, ',', '.') ?></div>
        </div>
        <div class="fin-kpi">
            <div class="fin-kpi-label">A Receber</div>
            <div class="fin-kpi-val orange">R$ <?= number_format($kpi['a_receber'], 2, ',', '.') ?></div>
        </div>
        <div class="fin-kpi">
            <div class="fin-kpi-label">Recebido (mês)</div>
            <div class="fin-kpi-val green">R$ <?= number_format($kpi['recebido_mes'], 2, ',', '.') ?></div>
        </div>
        <div class="fin-kpi">
            <div class="fin-kpi-label">Vencidos</div>
            <div class="fin-kpi-val red">R$ <?= number_format($kpi['vencidos'], 2, ',', '.') ?></div>
        </div>
        <div class="fin-kpi">
            <div class="fin-kpi-label">Total Despesas</div>
            <div class="fin-kpi-val"><?= $kpi['total_despesas'] > 0 ? 'R$ ' . number_format($kpi['total_despesas'], 2, ',', '.') : '—' ?></div>
        </div>
    </div>

    <!-- Conteúdo -->
    <div class="fin-body">
        <!-- Gráfico receita x despesa -->
        <div class="fin-card">
            <div class="fin-card-title">Receitas vs Despesas — Últimos 6 Meses</div>
            <canvas id="chartFinanceiro" height="180"></canvas>
        </div>

        <!-- Próximos vencimentos -->
        <div class="fin-card">
            <div class="fin-card-title">Próximos Vencimentos (30 dias)</div>
            <?php if (empty($vencimentos)): ?>
                <div class="fin-empty">Nenhum vencimento nos próximos 30 dias.</div>
            <?php else: ?>
            <table class="fin-tbl">
                <thead>
                    <tr><th>Cliente</th><th>Valor</th><th>Vence</th></tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($vencimentos, 0, 10) as $v): ?>
                    <tr>
                        <td style="max-width:110px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                            <?php
                                $nomeCli = '—';
                                if (!empty($v->cliente)) {
                                    $nomeCli = ($v->cliente->tipo == 1) ? ($v->cliente->nome ?? '—') : ($v->cliente->razaosocial ?? '—');
                                }
                                echo h($nomeCli);
                            ?>
                        </td>
                        <td>R$ <?= number_format($v->valor, 2, ',', '.') ?></td>
                        <td>
                            <?php
                                $dv = $v->data_vencimento ? $v->data_vencimento->format('Y-m-d') : null;
                                $hoje = date('Y-m-d');
                                if ($dv && $dv < $hoje): ?>
                                <span class="fin-badge-venc">Vencido</span>
                            <?php else: ?>
                                <?= $dv ? date('d/m/Y', strtotime($dv)) : '—' ?>
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

<script>
$(function() {
    if (typeof Chart === 'undefined') return;
    var ctx = document.getElementById('chartFinanceiro');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($graficoLabels) ?>,
            datasets: [
                {
                    label: 'Receitas',
                    data: <?= json_encode(array_values($graficoReceita)) ?>,
                    backgroundColor: 'rgba(29,158,117,.55)',
                    borderColor: '#1d9e75',
                    borderWidth: 1,
                    borderRadius: 5
                },
                {
                    label: 'Despesas',
                    data: <?= json_encode(array_values($graficoDespesa)) ?>,
                    backgroundColor: 'rgba(248,81,73,.45)',
                    borderColor: '#f85149',
                    borderWidth: 1,
                    borderRadius: 5
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { labels: { color: '#7d8590', font: { size: 12 } } }
            },
            scales: {
                x: { ticks: { color: '#7d8590' }, grid: { color: 'rgba(255,255,255,.04)' } },
                y: { ticks: { color: '#7d8590' }, grid: { color: 'rgba(255,255,255,.04)' } }
            }
        }
    });
});
</script>
