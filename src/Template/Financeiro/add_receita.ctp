<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Financeiro', ['controller' => 'Financeiro', 'action' => 'index']);
$this->Breadcrumbs->add('Contas a Receber', ['action' => 'contasReceber']);
$this->Breadcrumbs->add('Nova receita');
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
.fin-field-full { min-width:100%; }
</style>

<div class="fin-root">
    <div class="fin-topbar">
        <h1 class="fin-h1"><i class="fas fa-plus fin-h1-ico"></i>Nova receita</h1>
        <div><?= $this->Html->link('Voltar', ['action' => 'contasReceber'], ['class' => 'btn btn-default btn-sm']) ?></div>
    </div>

    <div class="fin-card">
        <?= $this->Form->create($lancamento) ?>
        <div class="fin-row">
            <div class="fin-field">
                <label>Descrição</label>
                <?= $this->Form->control('descricao', ['label' => false, 'class' => 'form-control', 'placeholder' => 'Ex: Mensalidade contrato']) ?>
            </div>
            <div class="fin-field" style="max-width:200px;">
                <label>Valor (R$)</label>
                <?= $this->Form->control('valor', ['label' => false, 'class' => 'form-control', 'type' => 'number', 'step' => '0.01', 'min' => '0.01', 'placeholder' => '0,00']) ?>
            </div>
        </div>
        <div class="fin-row">
            <div class="fin-field">
                <label>Cliente</label>
                <?= $this->Form->select('idcliente', $clientes, ['empty' => '— Selecione (opcional) —', 'class' => 'form-control']) ?>
            </div>
            <div class="fin-field">
                <label>Data de vencimento</label>
                <?= $this->Form->control('data_vencimento', ['label' => false, 'type' => 'date', 'class' => 'form-control']) ?>
            </div>
        </div>
        <div class="fin-row">
            <div class="fin-field">
                <label>Plano de contas</label>
                <?= $this->Form->select('plano_conta_id', $planoContas, ['empty' => '— Selecione (opcional) —', 'class' => 'form-control']) ?>
            </div>
            <div class="fin-field">
                <label>Centro de custo</label>
                <?= $this->Form->select('centro_custo_id', $centrosCusto, ['empty' => '— Selecione (opcional) —', 'class' => 'form-control']) ?>
            </div>
        </div>
        <div class="fin-row">
            <div class="fin-field fin-field-full">
                <label>Observações</label>
                <?= $this->Form->textarea('observacoes', ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Observações internas (opcional)']) ?>
            </div>
        </div>
        <div style="margin-top:16px;">
            <?= $this->Form->button('<i class="fas fa-save"></i> Salvar', ['class' => 'btn btn-pgm btn-pgm-salvar', 'escape' => false]) ?>
        </div>
        <?= $this->Form->end() ?>
    </div>
</div>
