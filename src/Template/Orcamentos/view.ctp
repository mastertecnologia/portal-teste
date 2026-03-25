<?php
    use Cake\Routing\Router;
    $this->append('css', $this->Html->css('/css/orcamentos-premium', ['timestamp' => true]));
    // Breadcumbs
    $this->Breadcrumbs->add('Orçamentos', ['controller' => 'Orcamentos', 'action' => 'index'], ['class' => 'breadcrumb-item']);
    $this->Breadcrumbs->add('Visualizar Orçamento', [], ['class' => 'breadcrumb-item active']);

    $dval = date_format(date_create($orcamento['validoate']), "d/m/Y");
    $orcamento['validoate'] = $dval;

?>
<script>
    tinymce.init({
        selector: '.editor',  // change this value according to your HTML
        plugins : 'advlist autolink link image imagetools lists advlist charmap media preview autoresize hr jbimages textcolor fullscreen table help paste',
        height: 300,
        language: 'pt_BR',
        entity_encoding : "raw",
        menubar: false,
        readonly: 1,
        toolbar: ['undo redo | bold italic underline strikethrough | bullist numlist | alinhamento | forecolor backcolor | table | link | fontselect fontsizeselect | image media | preview | hr | fullscreen',
        ],
        audio_template_callback: function(data) {
            return '<audio controls>' + '\n<source src="' + data.source1 + '"' + (data.source1mime ? ' type="' + data.source1mime + '"' : '') + ' />\n' + '</audio>';
        },
        setup: function(editor) {
            editor.addButton('alinhamento', {
                type: 'listbox',
                text: 'Alinhar',
                icon: false,
                onselect: function(e) {
                    tinyMCE.execCommand(this.value());
                },
                values: [
                    {icon: 'alignleft', value: 'JustifyLeft'},
                    {icon: 'alignright', value: 'JustifyRight'},
                    {icon: 'aligncenter', value: 'JustifyCenter'},
                    {icon: 'alignjustify', value: 'JustifyFull'},
                    {icon: 'outdent', value: 'outdent'},
                    {icon: 'indent', value: 'indent'},
                ],
                onPostRender: function() {
                    // Select the firts item by default
                    this.value('JustifyLeft');
                }
            });
        },
        browser_spellcheck: true,
        contextmenu: false,
        table_default_styles: {
            width: '75%'
        }
    });
</script>
<div class="col-md-12 orc-premium-page-root">
<div class="orc-premium-wrap orc-premium-form orc-premium-view">
    <!-- Cabeçalho -->
    <div class="orc-page-head" style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:18px;flex-wrap:wrap;">
        <div>
            <div class="orc-eyebrow" style="font-size:11px;color:#6b6a65;text-transform:uppercase;letter-spacing:.5px;margin:0 0 4px;">Módulo comercial</div>
            <h1 style="font-size:20px;font-weight:600;color:#1a1a18;margin:0;letter-spacing:-0.02em;">
                Orçamento <span style="color:#1d9e75;">#<?= $orcamento->id ?></span>
            </h1>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <?= $this->Html->link(
                '<i class="fa fa-arrow-left"></i> Voltar',
                ['action' => 'index'],
                ['class' => 'btn btn-sm btn-secondary', 'escape' => false]
            ) ?>
            <?php if (isset($role) && (int)$role === 0) : ?>
                <?= $this->Html->link(
                    '<i class="fa fa-pencil"></i> Editar',
                    ['action' => 'edit', $orcamento->id],
                    ['class' => 'btn btn-sm btn-pgm btn-pgm-situacao', 'escape' => false]
                ) ?>
                <?= $this->Html->link(
                    '<i class="fa fa-file-text-o"></i> Pré-visualizar PDF',
                    ['action' => 'imprimir', $orcamento->id],
                    ['class' => 'btn btn-sm btn-pgm btn-pgm-pdf', 'escape' => false]
                ) ?>
            <?php endif; ?>
            <?php if($orcamento->status == C_OrcamentoStatusEnviado): ?>
                <?= $this->Html->Link(
                    '<i class="fa fa-check"></i> Aprovar',
                    ['action' => 'aprovar', $orcamento->id],
                    ['class' => 'btn btn-sm btn-pgm btn-pgm-salvar btn-success', 'escape' => false]
                ) ?>
            <?php endif; ?>
        </div>
    </div>

    <?= $this->element('orcamentos_stepper') ?>

    <div class="card orc-premium-card-inner">
        <div class="card-body">

            <!-- Info do orçamento -->
            <div class="orc-sec-title">Informações</div>
            <div class="row m-t-10">
                <div class="col-md-3 col-xs-12">
                    <label class="control-label">Autor</label>
                    <?= $this->Form->text('idautor', ['class' => 'form-control', 'id' => 'idautor', 'value' => ($orcamento->user && !empty($orcamento->user->name)) ? $orcamento->user->name : '—', 'disabled']) ?>
                </div>
                <div class="col-md-3 col-xs-12">
                    <label class="control-label">Válido até</label>
                    <?= $this->Form->text('validoate', ['class' => 'form-control', 'id' => 'validoate', 'disabled' => true]) ?>
                </div>
                <div class="col-md-6 col-xs-12">
                    <label class="control-label">Status</label>
                    <div style="display:flex;align-items:center;gap:10px;padding:6px 0;">
                        <?= orcamentoStatus($orcamento->status) ?>
                        <?php if(isset($temordem) && $temordem != 'nao'): ?>
                            <?= $this->Html->link(
                                '<i class="fa fa-list-alt"></i> OS Nº ' . $temordem,
                                ['controller' => 'Ordensservico', 'action' => 'view', $temordem],
                                ['class' => 'btn btn-sm btn-pgm btn-pgm-situacao btn-info text-white', 'escape' => false]
                            ) ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Observação -->
            <div class="row m-t-10">
                <div class="col-12">
                    <label class="control-label">Observação / condições</label>
                    <?= $this->Form->control('solicitacao', ['value' => $orcamento->solicitacao, 'class' => 'editor form-control', 'label' => false, 'disabled']) ?>
                </div>
            </div>

            <!-- Itens do orçamento -->
            <div class="orc-sec-title" style="margin-top:20px;">Itens do orçamento</div>
            <div id="carrinho" class="m-t-10"></div>

            <!-- Workflow de aprovação (admin) -->
            <?php if(isset($role) && $role == 0): ?>
            <div class="orc-version-panel">
                <div class="orc-sec-title" style="margin-bottom:12px;">Fluxo de aprovação</div>
                <div class="orc-workflow-step">
                    <div class="orc-wf-dot orc-wf-ok"><i class="fa fa-check" style="font-size:11px;"></i></div>
                    <div class="orc-wf-body">
                        <div class="orc-wf-title">Orçamento criado</div>
                        <div class="orc-wf-sub">Proposta gerada pelo sistema</div>
                    </div>
                </div>
                <div class="orc-workflow-step">
                    <div class="orc-wf-dot <?= $orcamento->status >= C_OrcamentoStatusEnviado ? 'orc-wf-ok' : 'orc-wf-pend' ?>">
                        <i class="fa fa-<?= $orcamento->status >= C_OrcamentoStatusEnviado ? 'check' : 'clock-o' ?>" style="font-size:11px;"></i>
                    </div>
                    <div class="orc-wf-body">
                        <div class="orc-wf-title">Enviado ao cliente</div>
                        <div class="orc-wf-sub"><?= $orcamento->status >= C_OrcamentoStatusEnviado ? 'Enviado por e-mail' : 'Aguardando envio' ?></div>
                    </div>
                </div>
                <div class="orc-workflow-step">
                    <div class="orc-wf-dot <?= $orcamento->status == C_OrcamentoStatusAprovado ? 'orc-wf-ok' : ($orcamento->status == C_OrcamentoStatusRecusado ? 'orc-wf-pend' : '') ?>">
                        <i class="fa fa-<?= $orcamento->status == C_OrcamentoStatusAprovado ? 'check' : ($orcamento->status == C_OrcamentoStatusRecusado ? 'times' : 'circle-o') ?>" style="font-size:11px;"></i>
                    </div>
                    <div class="orc-wf-body">
                        <div class="orc-wf-title">Decisão do cliente</div>
                        <div class="orc-wf-sub">
                            <?php
                            if($orcamento->status == C_OrcamentoStatusAprovado) echo 'Aprovado';
                            elseif($orcamento->status == C_OrcamentoStatusRecusado) echo 'Recusado';
                            else echo 'Aguardando resposta';
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Footer -->
            <div class="orc-footer-bar">
                <span style="font-size:11px;color:#6b6a65;">
                    Orçamento #<?= $orcamento->id ?>
                </span>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <?php if($orcamento->status == C_OrcamentoStatusEnviado): ?>
                        <?= $this->Html->Link(
                            '<i class="fa fa-check"></i> Aprovar',
                            ['action' => 'aprovar', $orcamento->id],
                            ['class' => 'btn btn-pgm btn-pgm-salvar btn-success', 'escape' => false]
                        ) ?>
                    <?php endif; ?>
                </div>
            </div>

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
            success : function(data) {
                $("#carrinho").html(data);
            },
            error : function(error) { alert(error);}
        });
    }

    $(window).bind('beforeunload', function(){
        $.ajax({ url: "<?= Router::url(['controller'=>'Orcamentos','action'=>'limpasession']);?>", });
    });
</script>