<?php
/**
 * Financeiro — Fluxo de Caixa
 */
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Financeiro', ['controller' => 'Financeiro', 'action' => 'index']);
$this->Breadcrumbs->add('Fluxo de Caixa');

$labels = array_map(function($m) {
    $d = \DateTime::createFromFormat('Y-m', $m);
    return $d ? $d->format('M/y') : $m;
}, array_keys($buckets));

$recPrev  = array_column($buckets, 'receita_prevista');
$despPrev = array_column($buckets, 'despesa_prevista');
$recReal  = array_column($buckets, 'receita_realizada');
$despReal = array_column($buckets, 'despesa_realizada');
$saldos   = array_values($saldoAcumulado);
?>
<style>
.fc-root { font-family:'DM Sans',sans-serif; }
.fc-topbar { display:flex; align-items:center; justify-content:space-between; padding:16px 24px 12px; border-bottom:1px solid rgba(255,255,255,.07); flex-wrap:wrap; gap:10px; }
.fc-h1 { font-size:18px; font-weight:600; color:#e6edf3; }
.fc-h1-ico { color:#5cdbc0; margin-right:8px; }
.fc-filters { display:flex; gap:10px; padding:12px 20px; align-items:center; border-bottom:1px solid rgba(255,255,255,.07); }
.fc-filters select { background:#161b22; border:1px solid rgba(255,255,255,.10); border-radius:7px; color:#c9d1d9; padding:6px 10px; font-size:12.5px; height:34px; }
.fc-body { padding:20px 24px; display:grid; grid-template-columns:1fr; gap:20px; }
.fc-card { background:#161b22; border:1px solid rgba(255,255,255,.07); border-radius:10px; padding:18px 20px; }
.fc-card-title { font-size:11px; text-transform:uppercase; letter-spacing:.08em; color:#7d8590; font-weight:600; margin-bottom:14px; }
.fc-tbl { width:100%; border-collapse:collapse; font-size:12.5px; }
.fc-tbl th { color:#7d8590; font-size:10.5px; text-transform:uppercase; letter-spacing:.05em; font-weight:600; padding:6px 10px; border-bottom:1px solid rgba(255,255,255,.07); text-align:right; }
.fc-tbl th:first-child { text-align:left; }
.fc-tbl td { padding:8px 10px; border-bottom:1px solid rgba(255,255,255,.04); color:#c9d1d9; text-align:right; }
.fc-tbl td:first-child { text-align:left; font-weight:600; }
.fc-tbl tr:hover td { background:rgba(255,255,255,.02); }
.fc-pos { color:#3fb950; }
.fc-neg { color:#f85149; }
.fc-total-row td { font-weight:700; border-top:1px solid rgba(29,158,117,.2); color:#5cdbc0; }
</style>

<div class="fc-root">
    <div class="fc-topbar">
        <div class="fc-h1"><i class="fas fa-chart-area fc-h1-ico"></i>Fluxo de Caixa</div>
        <div>
            <?= $this->Html->link('<i class="fas fa-arrow-left"></i> Dashboard', ['action' => 'index'], ['class' => 'btn btn-default btn-sm', 'escape' => false]) ?>
        </div>
    </div>

    <div class="fc-filters">
        <?= $this->Form->create(null, ['type' => 'get']) ?>
        <select name="meses" onchange="this.form.submit()">
            <?php foreach ([3 => '3 meses', 6 => '6 meses', 12 => '12 meses', 24 => '24 meses'] as $v => $lbl): ?>
            <option value="<?= $v ?>" <?= (int)$meses === $v ? 'selected' : '' ?>><?= $lbl ?></option>
            <?php endforeach; ?>
        </select>
        <?= $this->Form->end() ?>
    </div>

    <div class="fc-body">
        <!-- Gráfico -->
        <div class="fc-card">
            <div class="fc-card-title">Fluxo de Caixa — Previsto vs Realizado</div>
            <canvas id="chartFluxo" height="200"></canvas>
        </div>

        <!-- Tabela detalhada -->
        <div class="fc-card">
            <div class="fc-card-title">Detalhamento mensal</div>
            <div style="overflow-x:auto;">
            <table class="fc-tbl">
                <thead>
                    <tr>
                        <th>Mês</th>
                        <th>Receita prevista</th>
                        <th>Receita realizada</th>
                        <th>Despesa prevista</th>
                        <th>Despesa realizada</th>
                        <th>Saldo previsto</th>
                        <th>Saldo realizado</th>
                        <th>Saldo acumulado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $saldoAcumIdx = 0;
                    $saldoValues = array_values($saldoAcumulado);
                    foreach ($buckets as $mes => $b):
                        $saldoPrev = $b['receita_prevista'] - $b['despesa_prevista'];
                        $saldoReal = $b['receita_realizada'] - $b['despesa_realizada'];
                        $saldoAcum = $saldoValues[$saldoAcumIdx] ?? 0;
                        $saldoAcumIdx++;
                        $dMes = \DateTime::createFromFormat('Y-m', $mes);
                        $labelMes = $dMes ? $dMes->format('M/Y') : $mes;
                    ?>
                    <tr>
                        <td><?= $labelMes ?></td>
                        <td>R$ <?= number_format($b['receita_prevista'], 2, ',', '.') ?></td>
                        <td>R$ <?= number_format($b['receita_realizada'], 2, ',', '.') ?></td>
                        <td>R$ <?= number_format($b['despesa_prevista'], 2, ',', '.') ?></td>
                        <td>R$ <?= number_format($b['despesa_realizada'], 2, ',', '.') ?></td>
                        <td class="<?= $saldoPrev >= 0 ? 'fc-pos' : 'fc-neg' ?>">
                            R$ <?= number_format($saldoPrev, 2, ',', '.') ?>
                        </td>
                        <td class="<?= $saldoReal >= 0 ? 'fc-pos' : 'fc-neg' ?>">
                            R$ <?= number_format($saldoReal, 2, ',', '.') ?>
                        </td>
                        <td class="<?= $saldoAcum >= 0 ? 'fc-pos' : 'fc-neg' ?>" style="font-weight:700;">
                            R$ <?= number_format($saldoAcum, 2, ',', '.') ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    if (typeof Chart === 'undefined') return;
    var ctx = document.getElementById('chartFluxo');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [
                {
                    label: 'Receita prevista',
                    data: <?= json_encode(array_values($recPrev)) ?>,
                    backgroundColor: 'rgba(29,158,117,.3)',
                    borderColor: '#1d9e75',
                    borderWidth: 1,
                    borderRadius: 4
                },
                {
                    label: 'Receita realizada',
                    data: <?= json_encode(array_values($recReal)) ?>,
                    backgroundColor: 'rgba(63,185,80,.55)',
                    borderColor: '#3fb950',
                    borderWidth: 1,
                    borderRadius: 4
                },
                {
                    label: 'Despesa prevista',
                    data: <?= json_encode(array_values($despPrev)) ?>,
                    backgroundColor: 'rgba(248,81,73,.25)',
                    borderColor: '#f85149',
                    borderWidth: 1,
                    borderRadius: 4
                },
                {
                    label: 'Despesa realizada',
                    data: <?= json_encode(array_values($despReal)) ?>,
                    backgroundColor: 'rgba(248,81,73,.55)',
                    borderColor: '#f85149',
                    borderWidth: 1,
                    borderRadius: 4
                },
                {
                    label: 'Saldo acumulado',
                    type: 'line',
                    data: <?= json_encode($saldos) ?>,
                    borderColor: '#5cdbc0',
                    backgroundColor: 'rgba(92,219,192,.1)',
                    borderWidth: 2,
                    pointRadius: 4,
                    fill: true,
                    tension: 0.3
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { labels: { color: '#7d8590', font: { size: 11 } } }
            },
            scales: {
                x: { ticks: { color: '#7d8590' }, grid: { color: 'rgba(255,255,255,.04)' } },
                y: { ticks: { color: '#7d8590' }, grid: { color: 'rgba(255,255,255,.04)' } }
            }
        }
    });
});
</script>
