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
.fin-nav a.active { background:rgba(29,158,117,.14); color:#5cdbc0; }
.fin-shortcuts { padding:16px 24px 0; }
.fin-shortcut-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:16px; }
.fin-shortcut-card { display:block; background:#161b22; border:1px solid rgba(255,255,255,.07); border-radius:10px; padding:18px 20px; text-decoration:none !important; color:inherit; transition:all .18s ease; }
.fin-shortcut-card:hover { border-color:rgba(92,219,192,.35); transform:translateY(-1px); }
.fin-shortcut-ico { display:inline-flex; width:42px; height:42px; align-items:center; justify-content:center; border-radius:10px; background:rgba(29,158,117,.12); color:#5cdbc0; font-size:18px; margin-bottom:12px; }
.fin-shortcut-title { font-size:15px; font-weight:700; color:#e6edf3; margin-bottom:6px; }
.fin-shortcut-desc { color:#8b949e; font-size:12.5px; line-height:1.55; }
.fin-shortcut-meta { margin-top:10px; color:#5cdbc0; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; }
/* Tabela vencimentos */
.fin-tbl { width:100%; border-collapse:collapse; font-size:12.5px; }
.fin-tbl th { color:#7d8590; font-size:10.5px; text-transform:uppercase; letter-spacing:.05em; font-weight:600; padding:5px 8px; border-bottom:1px solid rgba(255,255,255,.07); text-align:left; }
.fin-tbl td { padding:8px 8px; border-bottom:1px solid rgba(255,255,255,.04); color:#c9d1d9; }
.fin-tbl tr:hover td { background:rgba(255,255,255,.02); }
.fin-badge-venc { display:inline-block; padding:1px 7px; border-radius:9px; font-size:11px; font-weight:600; background:rgba(248,81,73,.13); color:#f85149; }
.fin-empty { text-align:center; padding:32px; color:#484f58; font-size:13px; }
@media(max-width:768px){ .fin-body { grid-template-columns:1fr; } }
.fin-h1-ico { color:#5cdbc0; margin-right:8px; }
.fin-topbar-actions { display:flex; gap:8px; }
.fin-td-ellipsis { max-width:110px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
</style>

<div class="fin-root">
    <!-- Topbar -->
    <div class="fin-topbar">
        <div class="fin-h1"><i class="fas fa-chart-line fin-h1-ico"></i>Financeiro</div>
        <div class="fin-topbar-actions">
            <?= $this->Html->link('<i class="fas fa-hand-holding-usd"></i> Contas a Receber', ['action' => 'contasReceber'], ['class' => 'btn btn-pgm btn-pgm-situacao btn-sm', 'escape' => false]) ?>
            <?= $this->Html->link('<i class="fas fa-file-invoice-dollar"></i> Contas a Pagar', ['action' => 'contasPagar'], ['class' => 'btn btn-pgm btn-pgm-situacao btn-sm', 'escape' => false]) ?>
            <?= $this->Html->link('<i class="fas fa-plus"></i> Nova Despesa', ['action' => 'addDespesa'], ['class' => 'btn btn-pgm btn-pgm-salvar btn-sm', 'escape' => false]) ?>
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
        <div class="fin-kpi">
            <div class="fin-kpi-label">A Pagar</div>
            <div class="fin-kpi-val orange">R$ <?= number_format($kpi['a_pagar'], 2, ',', '.') ?></div>
        </div>
        <div class="fin-kpi">
            <div class="fin-kpi-label">Pago (mês)</div>
            <div class="fin-kpi-val">R$ <?= number_format($kpi['pago_mes'], 2, ',', '.') ?></div>
        </div>
    </div>

    <div class="fin-shortcuts">
        <div class="fin-shortcut-grid">
            <a href="<?= $this->Url->build(['controller' => 'FinanceiroBancos', 'action' => 'index']) ?>" class="fin-shortcut-card">
                <span class="fin-shortcut-ico"><i class="fas fa-university"></i></span>
                <div class="fin-shortcut-title">Cadastrar Bancos</div>
                <div class="fin-shortcut-desc">Acesse cadastro de bancos, remessas, retornos e relatórios bancários do financeiro.</div>
                <div class="fin-shortcut-meta">Financeiro → Bancos</div>
            </a>
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
                        <td class="fin-td-ellipsis">
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
