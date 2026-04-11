<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Financeiro', ['controller' => 'Financeiro', 'action' => 'index']);
$this->Breadcrumbs->add('DRE');
?>
<style>
.dre-root { font-family:'DM Sans',sans-serif; }
.dre-topbar { display:flex; align-items:center; justify-content:space-between; padding:16px 24px 12px; border-bottom:1px solid rgba(255,255,255,.07); flex-wrap:wrap; gap:10px; }
.dre-h1 { font-size:18px; font-weight:600; color:#e6edf3; }
.dre-h1-ico { color:#5cdbc0; margin-right:8px; }
.dre-filters { display:flex; gap:10px; padding:12px 20px; align-items:center; border-bottom:1px solid rgba(255,255,255,.07); flex-wrap:wrap; }
.dre-filters input, .dre-filters select { background:#161b22; border:1px solid rgba(255,255,255,.10); border-radius:7px; color:#c9d1d9; padding:6px 10px; font-size:12.5px; height:34px; }
.dre-body { padding:20px 24px; max-width:700px; }
.dre-card { background:#161b22; border:1px solid rgba(255,255,255,.07); border-radius:10px; padding:18px 20px; margin-bottom:16px; }
.dre-section-title { font-size:12px; text-transform:uppercase; letter-spacing:.08em; color:#7d8590; font-weight:700; margin-bottom:10px; padding-bottom:4px; border-bottom:1px solid rgba(255,255,255,.06); }
.dre-row { display:flex; justify-content:space-between; padding:5px 0; font-size:13px; color:#c9d1d9; }
.dre-row-label { flex:1; }
.dre-row-value { font-weight:600; text-align:right; min-width:140px; }
.dre-subtotal { border-top:1px solid rgba(255,255,255,.08); margin-top:6px; padding-top:6px; font-weight:700; font-size:13.5px; }
.dre-resultado { background:rgba(29,158,117,.08); border:1px solid rgba(29,158,117,.2); border-radius:10px; padding:16px 20px; display:flex; justify-content:space-between; align-items:center; }
.dre-resultado-label { font-size:14px; font-weight:700; color:#e6edf3; }
.dre-resultado-valor { font-size:22px; font-weight:800; }
.dre-pos { color:#3fb950; }
.dre-neg { color:#f85149; }
.dre-receita-val { color:#3fb950; }
.dre-despesa-val { color:#f85149; }
.dre-empty { color:#484f58; font-size:12.5px; font-style:italic; padding:4px 0; }
</style>

<div class="dre-root">
    <div class="dre-topbar">
        <div class="dre-h1"><i class="fas fa-chart-pie dre-h1-ico"></i>DRE — Demonstrativo de Resultado</div>
        <div>
            <?= $this->Html->link('<i class="fas fa-arrow-left"></i> Dashboard', ['action' => 'index'], ['class' => 'btn btn-default btn-sm', 'escape' => false]) ?>
        </div>
    </div>

    <div class="dre-filters">
        <?= $this->Form->create(null, ['type' => 'get']) ?>
        <label style="color:#7d8590; font-size:12px; font-weight:600;">Período:</label>
        <input type="month" name="periodo" value="<?= h($periodo) ?>" onchange="this.form.submit()" />
        <span style="color:#484f58; font-size:12px;">ou</span>
        <select name="periodo" onchange="this.form.submit()">
            <option value="">Anual...</option>
            <?php for ($y = (int)date('Y'); $y >= (int)date('Y') - 3; $y--): ?>
            <option value="<?= $y ?>" <?= $periodo === (string)$y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
        <?= $this->Form->end() ?>
        <span style="color:#5cdbc0; font-size:13px; font-weight:600; margin-left:10px;"><?= h($labelPeriodo) ?></span>
    </div>

    <div class="dre-body">
        <!-- Receitas -->
        <div class="dre-card">
            <div class="dre-section-title">Receitas operacionais</div>
            <?php if (empty($receitas)): ?>
                <div class="dre-empty">Nenhuma receita no período.</div>
            <?php else: ?>
                <?php foreach ($receitas as $r): ?>
                <div class="dre-row">
                    <span class="dre-row-label"><?= h($r['label']) ?></span>
                    <span class="dre-row-value dre-receita-val">R$ <?= number_format($r['valor'], 2, ',', '.') ?></span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            <div class="dre-row dre-subtotal">
                <span class="dre-row-label">Total receitas</span>
                <span class="dre-row-value dre-receita-val">R$ <?= number_format($totalReceitas, 2, ',', '.') ?></span>
            </div>
        </div>

        <!-- Despesas -->
        <div class="dre-card">
            <div class="dre-section-title">Despesas operacionais</div>
            <?php if (empty($despesas)): ?>
                <div class="dre-empty">Nenhuma despesa no período.</div>
            <?php else: ?>
                <?php foreach ($despesas as $d): ?>
                <div class="dre-row">
                    <span class="dre-row-label"><?= h($d['label']) ?></span>
                    <span class="dre-row-value dre-despesa-val">R$ <?= number_format($d['valor'], 2, ',', '.') ?></span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            <div class="dre-row dre-subtotal">
                <span class="dre-row-label">Total despesas</span>
                <span class="dre-row-value dre-despesa-val">R$ <?= number_format($totalDespesas, 2, ',', '.') ?></span>
            </div>
        </div>

        <!-- Resultado -->
        <div class="dre-resultado">
            <span class="dre-resultado-label">Resultado do exercício</span>
            <span class="dre-resultado-valor <?= $resultado >= 0 ? 'dre-pos' : 'dre-neg' ?>">
                R$ <?= number_format($resultado, 2, ',', '.') ?>
            </span>
        </div>
    </div>
</div>
