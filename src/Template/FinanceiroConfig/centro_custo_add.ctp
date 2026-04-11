<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Financeiro', ['controller' => 'Financeiro', 'action' => 'index']);
$this->Breadcrumbs->add('Centros de Custo', ['action' => 'centrosCusto']);
$this->Breadcrumbs->add('Novo centro');
?>
<style>
.fin-root { font-family:'DM Sans',sans-serif; }
.fin-topbar { display:flex; align-items:center; justify-content:space-between; padding:18px 24px 14px; border-bottom:1px solid rgba(255,255,255,.07); }
.fin-h1 { font-size:20px; font-weight:600; color:#e6edf3; }
.fin-h1-ico { color:#5cdbc0; margin-right:8px; }
.fin-card { background:#161b22; border:1px solid rgba(255,255,255,.07); border-radius:10px; padding:18px 20px; margin:16px 24px; }
.fin-row { display:flex; gap:16px; flex-wrap:wrap; margin-bottom:12px; }
.fin-field { flex:1; min-width:200px; }
.fin-field label { display:block; font-size:11px; color:#7d8590; text-transform:uppercase; letter-spacing:.05em; font-weight:600; margin-bottom:4px; }
</style>

<div class="fin-root">
    <div class="fin-topbar">
        <h1 class="fin-h1"><i class="fas fa-plus fin-h1-ico"></i>Novo centro de custo</h1>
        <div><?= $this->Html->link('Voltar', ['action' => 'centrosCusto'], ['class' => 'btn btn-default btn-sm']) ?></div>
    </div>

    <div class="fin-card">
        <?= $this->Form->create($centro) ?>
        <div class="fin-row">
            <div class="fin-field">
                <label>Código</label>
                <?= $this->Form->control('codigo', ['label' => false, 'class' => 'form-control', 'placeholder' => 'Ex: ADM, TI, COM']) ?>
            </div>
            <div class="fin-field">
                <label>Descrição</label>
                <?= $this->Form->control('descricao', ['label' => false, 'class' => 'form-control', 'placeholder' => 'Ex: Administrativo']) ?>
            </div>
        </div>
        <div style="margin-top:16px;">
            <?= $this->Form->button('<i class="fas fa-save"></i> Salvar', ['class' => 'btn btn-pgm btn-pgm-salvar', 'escape' => false]) ?>
        </div>
        <?= $this->Form->end() ?>
    </div>
</div>
