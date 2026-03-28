<?php
    use Cake\Routing\Router;
    $this->append('css', $this->element('pgm_premium_css', ['name' => 'orcamentos-premium']));

    $dval = date_format(date_create($orcamento['validoate']), "d/m/Y");
    $orcamento['validoate'] = $dval;
?>
<style>
.orc-premium-wrap.orc-premium-view {
  --orc-teal-light: #e6faf4;
  --orc-teal-mid: #5cdbc0;
  --orc-border: #e5e4e0;
  --orc-border-light: #f0efec;
  --orc-text: #1a1a18;
  --orc-text-muted: #6b6a65;
  --orc-bg-surface: #f9f9f8;
}
.orc-info-row { display:flex; gap:0; border:1px solid #e5e4e0; border-radius:8px; overflow:hidden; margin-bottom:14px; flex-wrap:wrap; }
.orc-info-cell { flex:1; min-width:140px; padding:12px 16px; border-right:1px solid #e5e4e0; }
.orc-info-cell:last-child { border-right:none; }
.orc-info-label { font-size:10px; text-transform:uppercase; letter-spacing:.5px; color:#6b6a65; font-weight:600; margin-bottom:4px; }
.orc-info-value { font-size:13px; font-weight:600; color:#1a1a18; }
.orc-obs-box { background:#f9f9f8; border-radius:8px; padding:14px 16px; font-size:13px; color:#1a1a18; line-height:1.8; border-left:3px solid #00c08b; }
</style>
<div class="col-md-12 orc-premium-page-root">
<div class="orc-premium-wrap orc-premium-form orc-premium-view">

    <!-- Cabeçalho -->
    <div class="orc-page-head">
        <div>
            <div class="orc-eyebrow">Módulo comercial</div>
            <div style="font-size:11px;color:#6b6a65;margin-bottom:3px;">
                <?= $this->Html->link('Orçamentos', ['action' => 'index'], ['escape' => false]) ?> › <span style="color:#00c08b;">Orçamento #<?= $orcamento->id ?></span>
            </div>
            <h1 class="orc-h1">
                Orçamento <span style="color:#00c08b;">#<?= $orcamento->id ?></span>
                <?php if(!empty($orcamento->versao)): ?>
                    <span class="badge" style="background:#00c08b;color:#fff;font-family:monospace;font-size:10px;padding:3px 8px;border-radius:99px;letter-spacing:.3px;font-weight:700;">v<?= $orcamento->versao ?></span>
                <?php endif; ?>
                <span><?= orcamentoStatus($orcamento->status) ?></span>
            </h1>
        </div>
        <div class="orc-page-head-actions">
            <?= $this->Html->link('<i class="fa fa-arrow-left"></i> Voltar', ['action' => 'index'], ['class' => 'btn btn-orc-form-secondary btn-orc-compact', 'escape' => false]) ?>
            <?php if (isset($role) && (int)$role === 0) : ?>
                <?= $this->Html->link('<i class="fa fa-pencil"></i> Editar', ['action' => 'edit', $orcamento->id], ['class' => 'btn btn-orc-outline-teal btn-orc-compact', 'escape' => false]) ?>
                <?= $this->Html->link('<i class="fa fa-file-text-o"></i> PDF', ['action' => 'imprimir', $orcamento->id], ['class' => 'btn btn-orc-outline-teal btn-orc-compact', 'escape' => false]) ?>
            <?php endif; ?>
            <?php if($orcamento->status == C_OrcamentoStatusEnviado): ?>
                <?= $this->Html->Link('<i class="fa fa-check"></i> Aprovar', ['action' => 'aprovar', $orcamento->id], ['class' => 'btn btn-orc-premium-primary btn-orc-compact', 'escape' => false]) ?>
            <?php endif; ?>
        </div>
    </div>

    <?= $this->element('orcamentos_stepper') ?>

    <!-- Card: Dados da proposta -->
    <div class="card orc-premium-card-inner" style="margin-bottom:14px;">
        <div class="card-body">
            <div class="orc-sec-title">Dados da proposta</div>
            <div class="orc-info-row">
                <div class="orc-info-cell">
                    <div class="orc-info-label">Cliente</div>
                    <div class="orc-info-value"><?= empty($orcamento->cliente->razaosocial) ? $orcamento->cliente->nome : $orcamento->cliente->razaosocial ?></div>
                </div>
                <div class="orc-info-cell">
                    <div class="orc-info-label">Emitido por</div>
                    <div class="orc-info-value"><?= ($orcamento->user && !empty($orcamento->user->name)) ? $orcamento->user->name : '—' ?></div>
                </div>
                <div class="orc-info-cell">
                    <div class="orc-info-label">Pagamento</div>
                    <div class="orc-info-value"><?= !empty($orcamento->formapagamento) ? $orcamento->formapagamento : '—' ?></div>
                </div>
                <div class="orc-info-cell">
                    <div class="orc-info-label">Válido até</div>
                    <div class="orc-info-value" style="color:#FFC107;"><?= $orcamento->validoate ?></div>
                </div>
                <div class="orc-info-cell">
                    <div class="orc-info-label">Status</div>
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                        <?= orcamentoStatus($orcamento->status) ?>
                        <?php if(isset($temordem) && $temordem != 'nao'): ?>
                            <?= $this->Html->link('<i class="fa fa-list-alt"></i> OS Nº ' . $temordem, ['controller' => 'Ordensservico', 'action' => 'view', $temordem], ['class' => 'btn btn-orc-outline-teal btn-orc-compact', 'escape' => false]) ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php if(!empty($orcamento->solicitacao)): ?>
                <div class="orc-sec-title" style="margin-top:10px;">Observações / condições</div>
                <div class="orc-obs-box"><?= nl2br(htmlspecialchars($orcamento->solicitacao)) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Card: Itens -->
    <div class="card orc-premium-card-inner" style="margin-bottom:14px;">
        <div class="card-body">
            <div class="orc-sec-title">Itens do orçamento</div>
            <div id="carrinho" class="m-t-10"></div>
        </div>
    </div>

    <!-- Card: Fluxo de aprovação (admin) -->
    <?php if(isset($role) && $role == 0): ?>
    <div class="card orc-premium-card-inner" style="margin-bottom:14px;">
        <div class="card-body">
            <div class="orc-sec-title">Fluxo de aprovação</div>
            <div class="orc-wf-list">
                <div class="orc-workflow-step">
                    <div class="orc-wf-dot orc-wf-ok"><i class="fa fa-check"></i></div>
                    <div class="orc-wf-body">
                        <div class="orc-wf-title">Orçamento criado</div>
                        <div class="orc-wf-sub">Proposta gerada no sistema em <?= $orcamento->created->format('d/m/Y') ?></div>
                    </div>
                </div>
                <div class="orc-workflow-step">
                    <div class="orc-wf-dot <?= $orcamento->status >= C_OrcamentoStatusEnviado ? 'orc-wf-ok' : 'orc-wf-pend' ?>">
                        <i class="fa fa-<?= $orcamento->status >= C_OrcamentoStatusEnviado ? 'check' : 'clock-o' ?>"></i>
                    </div>
                    <div class="orc-wf-body">
                        <div class="orc-wf-title" <?= $orcamento->status < C_OrcamentoStatusEnviado ? 'style="color:#9a9890;"' : '' ?>>Enviado ao cliente</div>
                        <div class="orc-wf-sub"><?= $orcamento->status >= C_OrcamentoStatusEnviado ? 'Proposta enviada por e-mail' : 'Aguardando envio' ?></div>
                    </div>
                </div>
                <div class="orc-workflow-step">
                    <div class="orc-wf-dot <?= $orcamento->status == C_OrcamentoStatusAprovado ? 'orc-wf-ok' : ($orcamento->status == C_OrcamentoStatusRecusado ? 'orc-wf-pend' : '') ?>">
                        <?php if($orcamento->status == C_OrcamentoStatusAprovado): ?><i class="fa fa-check"></i>
                        <?php elseif($orcamento->status == C_OrcamentoStatusRecusado): ?><i class="fa fa-times"></i>
                        <?php else: ?><div style="width:7px;height:7px;border-radius:50%;background:#e5e4e0;"></div><?php endif; ?>
                    </div>
                    <div class="orc-wf-body">
                        <div class="orc-wf-title" <?= !in_array($orcamento->status, [C_OrcamentoStatusAprovado, C_OrcamentoStatusRecusado]) ? 'style="color:#9a9890;"' : '' ?>>Decisão do cliente</div>
                        <div class="orc-wf-sub">
                            <?php
                            if($orcamento->status == C_OrcamentoStatusAprovado) echo 'Aprovado' . (!empty($orcamento->ipaprovacao) ? ' · IP: ' . $orcamento->ipaprovacao : '');
                            elseif($orcamento->status == C_OrcamentoStatusRecusado) echo 'Recusado pelo cliente';
                            else echo 'Aguardando resposta';
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Footer -->
    <div class="orc-footer-bar">
        <span style="font-size:11px;color:#6b6a65;">Orçamento #<?= $orcamento->id ?></span>
        <div class="orc-footer-bar-actions">
            <?php if($orcamento->status == C_OrcamentoStatusEnviado): ?>
                <?= $this->Html->Link('<i class="fa fa-check"></i> Aprovar', ['action' => 'aprovar', $orcamento->id], ['class' => 'btn btn-orc-premium-primary btn-orc-compact', 'escape' => false]) ?>
            <?php endif; ?>
        </div>
    </div>

</div>
</div>
<script>
    carrinho();
    function carrinho(){
        $.ajax({
            type: "POST",
            url: "<?= Router::url(['controller'=>'Orcamentos','action'=>'carrinhoedit']);?>/" + <?= $orcamento->id ?>,
            dataType: "html",
            success : function(data) { $("#carrinho").html(data); },
            error : function(error) { alert(error); }
        });
    }
    $(window).bind('beforeunload', function(){
        $.ajax({ url: "<?= Router::url(['controller'=>'Orcamentos','action'=>'limpasession']);?>", });
    });
</script>
