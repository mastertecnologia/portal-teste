<?php
    use Cake\Routing\Router;
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
<div class="col-md-12">
    <div class="card">
        <div class="card-body">
            <h3 class='text-center'>Proposta de Orçamento</h3>
            <div class="row m-t-10">
                <div class="col-md-3 col-xs-12">
                    <label class="control-label">Autor</label> <br>
                    <?= $this->Form->text('idautor', ['class' => 'form-control ', 'id' => 'idautor', 'value' => $orcamento->user->name, 'disabled']) ?>
                </div>
                <div class="col-md-3 col-xs-12">
                    <label class="control-label text-muted">Válido até</label>
                    <?= $this->Form->text('validoate', ['class' => 'form-control datepicker ', 'id' => 'validoate', 'default' => date('d/m/Y'), 'placeholder' => 'Insira a data', 'required' => true, 'data-mask' => '99/99/9999', 'disabled' => true]) ?>
                </div>
            </div>
            <div class="row m-t-10">
                <div class="col-12">
                    <label class="control-label">Observação</label>
                    <?= $this->Form->control('solicitacao', ['value' => $orcamento->solicitacao, 'class' => 'editor form-control', 'label' => false, 'disabled']) ?>
                </div>
            </div>
            <div class="row m-t-10">
                <div class="col-12">
                    <label class="control-label text-muted">Status do orçamento</label>
                    <h5> <?= orcamentoStatus($orcamento->status) ?> </h5>
                    
                    <?php if (isset($temordem) && $temordem != 'nao'): ?>
                        <div class="m-t-10">
                            <?= $this->Html->link(
                                '<i class="fa fa-list-alt"></i> Ordem de serviço gerada: Nº ' . $temordem,
                                ['controller' => 'Ordensservico', 'action' => 'view', $temordem],
                                ['class' => 'btn btn-sm btn-info text-white', 'escape' => false]
                            ) ?>
                        </div>
                    <?php endif; ?>
                    </div>
            </div>
            <div id="carrinho" class="m-t-10"></div>
            <?php if($orcamento->status == C_OrcamentoStatusEnviado) echo $this->Html->Link('Aprovar', ['action' => 'aprovar', $orcamento->id], ['class' => 'btn btn-aprovar btn-success float-right m-t-20']); ?>
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