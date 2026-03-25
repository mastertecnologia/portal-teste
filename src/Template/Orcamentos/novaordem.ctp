<?php
	use Cake\Routing\Router;
	$this->append('css', $this->Html->css('/css/orcamentos-premium', ['timestamp' => true]));
    // Breadcumbs
    $this->Breadcrumbs->add('Orçamentos', ['controller' => 'Orcamentos', 'action' => 'index'], ['class' => 'breadcrumb-item']);
    $this->Breadcrumbs->add('Orçamento nº '.$idorcamento, ['controller' => 'Orcamentos', 'action' => 'edit', $idorcamento], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Nova Ordem de Serviço', [], ['class' => 'breadcrumb-item active']);

?>
<div class="col-md-12 orc-premium-page-root">
<div class="orc-premium-wrap orc-premium-form">
    <div class="card orc-premium-card-inner">
        <div class="card-body">
			<div class="row">
				<div class="col-12">
					<legend>Orçamento nº: <?= $idorcamento ?></legend>
				</div>
			</div>
            <?= $this->Form->create($novaordem, ['class' => 'form-material']) ?>
				<div class="row">
					<div class="col-lg-4 col-sm-12">
						<label class="control-label">Cliente</label>
						<?= $this->Form->control('idcliente', ['disabled', 'data-live-search' => true, 'options' => $clientes, 'title' => 'Selecione um cliente', 'class' => 'form-control selectpicker', 'label' => false, 'required' => true]) ?>
						<?= $this->Form->text('idcliente', ['type' => 'hidden', 'value' => $novaordem->idcliente, 'label' => false, 'required' => true]) ?>
					</div>
					<div class="col-lg-2 col-sm-12">
						<div class="form-group ">
							<label class="control-label">Data de Abertura</label>
							<?= $this->Form->text('dataabertura', ['readonly', 'value' => date('d/m/Y'), 'class' => 'form-control datepicker', 'label' => false, 'required' => true]) ?>
						</div>
					</div>
					<div class="col-lg-2 col-sm-12">
						<div class="form-group ">
							<label class="control-label">Data de Previsão</label>
							<?= $this->Form->text('dataprevisao', ['placeholder' => 'Data', 'class' => 'form-control datepicker', 'label' => false, 'required' => true]) ?>
						</div>
					</div>
					<div class="col-lg-2 col-sm-12">
						<label class="control-label">Prioridade</label>
						<?= $this->Form->control('prioridade', ['placeholder' => 'Data', 'options' => C_OrdensPrioridade,  'class' => 'form-control', 'label' => false, 'required' => true]) ?>
					</div>
					<div class="col-lg-2 col-sm-12">
						<label class="control-label">Contrato</label>
						<?= $this->Form->control('contrato', ['readonly', 'placeholder' => 'Data', 'options' => C_OrdensContrato,  'class' => 'form-control', 'label' => false, 'required' => true]) ?>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-6 col-sm-12">
						<label class="control-label">Status</label>
						<?= $this->Form->control('idarea', ['disabled', 'data-live-search' => true, 'options' => $areas, 'title' => 'Selecione um status', 'class' => 'form-control selectpicker', 'label' => false, 'required' => true]) ?>
						<?= $this->Form->text('idarea', ['type' => 'hidden', 'value' => $novaordem->idarea, 'label' => false, 'required' => true]) ?>
					</div>
					<div class="col-lg-6 col-sm-12">
						<label class="control-label">Tipo de OS</label>
						<?= $this->Form->control('idproblema', ['disabled', 'data-live-search' => true, 'options' => $problemas, 'title' => 'Selecione um Tipo de OS', 'class' => 'form-control selectpicker', 'label' => false, 'required' => true]) ?>
						<?= $this->Form->text('idproblema', ['type' => 'hidden', 'value' => $novaordem->idproblema, 'label' => false, 'required' => true]) ?>
					</div>
				</div>
				<br>
				<div class="row">
					<div class="col-lg-6 col-md-12">
						<label class="control-label">Descrição do Problema</label>
						<?= $this->Form->textarea('relato', ['placeholder' => 'Insira a descrição do problema da ordem', 'class' => 'form-control', 'label' => false, 'required' => false]) ?>
					</div>
					<div class="col-lg-6 col-md-12">
						<label class="control-label">Observação</label>
						<?= $this->Form->textarea('observacao', ['placeholder' => 'Observação', 'class' => 'form-control', 'label' => false, 'required' => false]) ?>
					</div>
				</div>
				<br>
				<div class="row">
					<div class="col-lg-3 col-sm-12">
						<label class="control-label">Solicitante</label>
						<?= $this->Form->control('idsolicitante', ['class' => 'selectpicker form-control', 'title' => 'Solicitante (opcional)', 'data-live-search' => true, 'options' => '', 'label' => false, 'required' => false]) ?>
					</div>
					<div class="col-lg-3 col-sm-12">
						<label class="control-label">Atendimento</label>
						<?= $this->Form->control('atendimento', ['placeholder' => 'Data', 'options' => C_OrdensAtendimento,  'class' => 'form-control', 'label' => false, 'required' => true]) ?>
					</div>
				</div>
				<hr>
				<!-- Campos pro mobile  -->
					<br><h4 class='text-center'>Adicionar Produtos/Serviços</h4><br>
					<?php if(isMobile()){ ?>
						<div class="row">
							<div class="col-2">
								<label class="form-group ">Tipo</label>
								<?= $this->Form->control('tipo', ['class' => 'form-control inputMobile', 'label' => false, 'readonly']) ?>
							</div>
							<div class="col-2">
								<label class="control-label text-muted">Código</label>
								<?= $this->Form->control('codproduto', ['data-live-search' => true, 'options' => $produtosMobile, 'title' => 'Código', 'class' => 'inputMobile form-control selectpicker p-0', 'label' => false]) ?>
							</div>
							<div class="col-8">
								<div class="form-group ">
									<label class="control-label text-muted">Descrição</label>
									<?= $this->Form->control('descricao', ['class' => 'form-control inputMobile', 'label' => false, 'readonly']) ?>
								</div>
							</div>
							<div class="col-2">
								<div class="form-group ">
									<label class="control-label text-muted">Unidade</label>
									<?= $this->Form->control('unidade', ['class' => 'form-control inputMobile', 'label' => false, 'readonly']) ?>
								</div>
							</div>
							<div class="col-5">
								<div class="form-group ">
									<label class="control-label text-muted">Quantidade</label>
									<?= $this->Form->control('quantidade', ['class' => 'aquisicao form-control inputMobile', 'label' => false]) ?>
								</div>
							</div>
							<div class="col-5">
								<div class="form-group ">
									<label class="control-label text-muted">Valor Unitário (R$)</label>
									<?= $this->Form->control('valorunitario', ['class' => 'aquisicao form-control inputMobile', 'label' => false,]) ?>
								</div>
							</div>
							<div class="col-6">
								<div class="form-group ">
									<label class="control-label text-muted">Valor Desconto (R$)</label>
									<?= $this->Form->control('valordesconto', ['class' => 'mensal form-control inputMobile', 'label' => false]) ?>
								</div>
							</div>
							<div class="col-6">
								<div class="form-group ">
									<label class="control-label text-muted">Valor Total (R$)</label>
									<?= $this->Form->text('valortotal', ['id' => 'valortotal', 'class' => 'mensal form-control inputMobile', 'label' => false, 'readonly']) ?>
								</div>
							</div>
							<div class="col-6">
								<div class="form-group ">
									<label class="control-label text-muted">Serial Number</label>
									<?= $this->Form->control('serialnumber', ['list' => 'listaSN', 'id' => 'serialnumber', 'class' => 'form-control inputMobile', 'label' => false]) ?>
									<datalist id="listaSN"> </datalist>
								</div>
							</div>
						</div>
						<?= $this->Html->link('Adicionar item', [], ['class' => 'btn btn-orc-outline-teal btn-orc-compact btn-additem m-b-20']) ?>
					<?php } ?>
				<!-- Tabela -->
				<div id="grid_table"></div>
				<!-- valortotal que é exibido e o input hidden dele -->
				<?= '<h5 class="text-right text-success font-weight-bold m-r-15 valortotalordem"> </h5>' ?>
				<?= $this->Form->control('valortotalordem', ['type' => 'hidden', 'label' => false, ]) ?>
				<?php
					if(isset($temalgumsemproduto)){
						$disabled = true;
				?>
				<p class='m-b-0 mensagem'>Todos os itens da ordem devem poussir um código! Vincule os itens que não possuem um código a um produto válido.</p>
				<?php
					}else $disabled = false;
					echo $this->Form->button('Abrir Ordem de Serviço', ['class' => 'btn btn-orc-premium-primary btn-orc-compact m-t-20 btn-enviar', 'disabled' => $disabled])
				?>

            <?= $this->Form->end() ?>
            <div class="clearfix"></div>
        </div>
    </div>
</div>
</div>
<!-- Modal observação -->
<div class="modal fade none-border" id="modal-observacao">
    <div class="modal-dialog orc-premium-wrap orc-premium-form">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Detalhes do Item</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row m-20 m-b-0 form-material">
                    <div class="col-12">
                        <div class="form-group">
                            <label class="control-label m-b-0">Modelo</label>
                            <?= $this->Form->control('modelomodal', ['placeholder' => 'Insira o modelo do item', 'id' => 'modelomodal', 'class' => 'form-control', 'label' => false]);?>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label class="control-label m-b-0">Serial Number (N/S)</label>
                            <?= $this->Form->text('serialnumbermodal', ['list' => 'listaSN', 'maxlength' => 100, 'placeholder' => 'Insira o serial number', 'id' => 'serialnumbermodal', 'class' => 'form-control', 'label' => false]);?>
                            <datalist id="listaSN"> </datalist>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label class="control-label m-b-0">Observação</label>
                            <?= $this->Form->textarea('observacaomodal', ['placeholder' => 'Insira a observação do item', 'id' => 'observacaomodal', 'class' => 'form-control', 'label' => false]);?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <?= $this->Html->link('Salvar Detalhes', ['#'], ['class' => 'btn btn-orc-premium-primary btn-orc-compact btn-observacao m-l-5']) ?>
                <button type="button" class="btn btn-orc-form-secondary btn-orc-compact" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>
<!-- Modal Serial Number -->
<div class="modal fade none-border" id="modal-serialnumber">
	<div class="modal-dialog modal-lg orc-premium-wrap orc-premium-form">
		<div class="modal-content">
			<div class="row m-20 m-b-0 form-material">
				<div class="col-12">
					<div class="form-group ">
						<label class="control-label">Serial Number</label>
						<?= $this->Form->text('serialnumbermodal', ['list' => 'listaSN', 'maxlength' => 100, 'placeholder' => 'Insira o serial number do item', 'id' => 'serialnumbermodal', 'class' => 'form-control', 'label' => false]);?>
						<datalist id="listaSN"> </datalist>
						<small>*Informe apenas um Serial Number. Para mais códigos, adicione o produto novamente.</small>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<?= $this->Html->link('Salvar serial number', ['#'], ['class' => 'btn btn-orc-premium-primary btn-orc-compact btn-serialnumber m-l-5']) ?>
				<button type="button" class="btn btn-orc-form-secondary btn-orc-compact" data-dismiss="modal">Fechar</button>
			</div>
		</div>
	</div>
</div>
<script>
    var idEmpresAtual = $("#empresaSidebar").val();
    $('#idEmpresaAtual').val(idEmpresAtual); 
        
    $(document).ready(function(){
        $('.clienteTelemail').hide();
        $('.clienteTelemail').prop("disabled", true);
    });

    function loadSolicitantes(idcliente) {
        $.ajax({
            dataType: "json",
            url: "<?= Router::url(['controller'=>'Clientes','action'=>'solicitantes']);?>/" + idcliente,
            success: function(data){
                $('#idsolicitante').find('option').remove().end();
                $('#idsolicitante').append($('<option>', { value: 0, text: 'Outros' }));
                $.each(data, function(key, array) {
                    $('#idsolicitante').append($('<option>', { value: key, text: array }));
                });
                $('#idsolicitante').selectpicker("refresh");
                $('#solicitante-outros-container').hide();
            },
        });
    }

    $("#idsolicitante").change(function() {
        if($(this).val() == 0) {
            $('#solicitante-outros-container').show();
            $('#solicitante-outros').focus();
        } else {
            $('#solicitante-outros-container').hide();
            $('#solicitante-outros').val('');
            loadSolTelemail($(this).val());
        }
    });

    function loadCliTelemail(idcliente) {
        $.ajax({
            dataType: "json",
            url: "<?= Router::url(['controller'=>'Clientes','action'=>'cliemail']);?>/" + idcliente,
            success: function(data){
                $('.clienteTelemail').show();
                $('.email').val(data.email);
                $('.telefone').val(data.fone);
                $('.celular').val(data.fone2);
            },
        });
    }

    function loadSolTelemail(idsolicitante) {
        if(idsolicitante == 0) {
            $('.clienteTelemail').show();
            $('.email, .telefone, .celular').val('');
            return;
        }
        $.ajax({
            dataType: "json",
            url: "<?= Router::url(['controller'=>'Clientes','action'=>'solemail']);?>/" + idsolicitante,
            success: function(data){
                $('.clienteTelemail').show();
                $('.email').val(data.email);
                $('.telefone').val(data.telefone);
                $('.celular').val(data.celular);
            },
        });
    }

    $("#idcliente").change(function() {
        var idcliente = $(this).val();
        loadSolicitantes(idcliente);
        loadCliTelemail(idcliente);
        $.ajax({
            url: "<?= Router::url(['controller'=>'Clientes','action'=>'contrato']);?>/" + $(this).val(),
            success:function(data){
                if(data == 1) $('#contrato').val(1); 
                else $('#contrato').val(0); 
            },
        });
    });

    var urlLoadData = "<?= Router::url(['controller'=>'Ordensservico','action'=>'carrinho']);?>";
    var urlAdd = "<?= Router::url(['controller'=>'Ordensservico','action'=>'carrinhoadd']);?>";
    var urlEdit = "<?= Router::url(['controller'=>'Ordensservico','action'=>'carrinhoedititem']);?>";
    var urlDelete = "<?= Router::url(['controller'=>'Ordensservico','action'=>'carrinhodelitem']);?>";

    var tiposOpt = <?= $tiposOpt ?>;
    var produtosOpt = <?= $produtosOpt ?>;
    produtosOpt.sort(function(a, b){
        if(a.descricao < b.descricao) return -1;
        if(a.descricao > b.descricao) return 1;
        return 0;
    });

    $('#grid_table').jsGrid({
            width: "100%",
            height: "600px",
            filtering: false,
            inserting: true,
            editing: true,
            sorting: true,
            paging: true,
            autoload: true,
            pageSize: 10,
            pageButtonCount: 5,
            deleteConfirm: "Tem certeza que deseja remover o item?",
            noDataContent: "Nenhum item adicionado",
            
            controller: {
                loadData: function(){
                    return $.ajax({
                        url: urlLoadData,
                        dataType: "json",
                        success: function(result){
                            var url = "<?= Router::url(['controller'=>'Ordensservico','action'=>'valortotal']);?>";
                            $.ajax({
                                url: url,
                                success:function(data){
                                    valortotal = parseFloat(data.valortotal);
                                    $('#valortotalordem').val(valortotal);
                                    $('.valortotalordem').html( '<font color="#212529"> Total geral:</font> R$ ' + numberToReal(valortotal));
                                    setTimeout(function(){ tdcommuitotexto(); }, 500);
                                    
                                    // Ajuste visual
                                    $('.jsgrid-cell').each(function() {
                                        if($(this).text().trim() === "") $(this).append('<small class="vazio">⠀⠀⠀</small>');
                                    });
                                },
                            });
                        }
                    });
                },
                insertItem: function(item){
                    item['idEmpresaAtual'] = $("#empresaSidebar option:selected").val();
                    return $.ajax({
                        type: "POST",
                        url: urlAdd+'/null/'+$('.inputCodproduto > select').val(),
                        data: item,
                        success: function(data){
                            $(".qtdEstoque, .vazio").remove();
                            $("#grid_table").jsGrid("loadData");
                            if(data == 'naopode') bootbox.alert('Este produto já foi adicionado à ordem de serviço.');
                        }, 
                        error: function(data) { location.reload(); }
                    });
                },
                updateItem: function(item){
                    item['idEmpresaAtual'] = $("#empresaSidebar option:selected").val();
                    return $.ajax({
                        type: "PUT",
                        url: urlEdit,
                        data: item,
                        success: function(data){
                            $(".qtdEstoque, .vazio").remove();
                            $("#grid_table").jsGrid("loadData");
                        },
                        error: function(data) { location.reload(); }
                    });
                },
                deleteItem: function(item){
                    item['idEmpresaAtual'] = $("#empresaSidebar option:selected").val();
                    return $.ajax({
                        type: "DELETE",
                        url: urlDelete,
                        data: item,
                        success: function(){
                            $(".qtdEstoque, .vazio").remove();
                            $("#grid_table").jsGrid("loadData");
                        },
                        error: function(data) { location.reload(); }
                    });
                },
            },
            fields: [
                { name: "codprodutosoocod", title: "cod", type: "text", css: 'hide td-codproduto-soocod', editing: false},
                { name: "id", title: "id", type: "text", css: 'hide', validade: 'required',  editing: false,},
                { name: "tipo", title: "Tipo", type: "select", items: tiposOpt, validade: 'required',  editing: false, insertcss: 'cellInput inputTipo', editcss: "editTipo",},
                {
                    name: "codproduto",
                    title: "Código do Produto",
                    type: "select",
                    items: produtosOpt,
                    valueField: "codigo",
                    textField: "descricao",
                    insertcss: 'cellInput inputCodproduto',
                    editcss: "editCodproduto",
                    validate: "required",
                    width: 300,
                    insertTemplate: function() {
                        var $select = $("<select>").addClass("jsgrid-select2").select2({
                            placeholder: "Selecione um produto",
                            allowClear: true,
                            data: produtosOpt.map(function(item) { return { id: item.codigo, text: item.descricao }; })
                        });
                        this.insertControl = $select;
                        return $select;
                    },
                    editTemplate: function(value) {
                        var $select = $("<select>").addClass("jsgrid-select2");
                        $select.select2({
                            placeholder: "Selecione um produto",
                            allowClear: true,
                            data: produtosOpt.map(function(item) { return { id: item.codigo, text: item.descricao }; })
                        });
                        if (produtosOpt.some(function(item) { return item.codigo == value; })) { $select.val(value).trigger("change"); }
                        this.editControl = $select;
                        return $select;
                    },
                    insertValue: function() { return this.insertControl.val(); },
                    editValue: function() { return this.editControl.val(); },
                },
                { name: "descricao",  title: "Descrição", type: "text",  validate: "required", editing: false, readOnly: true, insertcss: 'cellInput inputDescricao', editcss: "editDescricao", validade: 'required', },

                { 
                    name: "observacao", 
                    title: "Obs / Detalhes", 
                    type: "text",  
                    validate: "", 
                    insertcss: 'cellInput inputObservacao', 
                    editcss: "editObservacao",
                    itemTemplate: function(value) { return "📄 Detalhes/Obs"; },
                    insertTemplate: function() { return this.insertControl = $("<input>").prop("type", "text").val("").addClass('form-control btn-modal-obs').attr('placeholder', 'Clique para editar'); },
                    editTemplate: function(value) { return this.editControl = $("<input>").prop("type", "text").val(value).addClass('form-control btn-modal-obs'); },
                    insertValue: function() { return this.insertControl.val(); },
                    editValue: function() { return this.editControl.val(); }
                },
                
                { name: "unidade",  title: "Unidade", type: "text",  editing: false, readOnly: true, insertcss: 'cellInput inputUnidade', editcss: "editUnidade", validade: 'required', },
                { name: "quantidade",  title: "Qtde", type: "text",  insertcss: 'cellInput inputQuantidade', editcss: "editQuantidade", validate: { message: "A quantidade não pode ser igual ou inferior a 0!", validator: function(value) { return value.replace('.', '').replace(',', '.') > 0; }},},
                { name: "valorunitario",  title: "Vl. Unitário", type: "text",  insertcss: 'cellInput inputValorunitario', editcss: "editValorunitario", validate: { message: "O valor unitário não pode ser igual ou inferior a 0!", validator: function(value) { return value.replace('.', '').replace(',', '.') > 0; }},},
                { name: "valordesconto",  title: "Vl. Desconto", type: "text",  insertcss: 'cellInput inputValordesconto', editcss: "editValordesconto",},
                { name: "valortotal",  title: "Vl. Total", type: "text",  readOnly: true, insertcss: 'cellInput inputValortotal', editcss: "editValortotal", headercss: 'sai', css: 'fieldValortotal', validate: { message: "O valor total não pode ser igual ou inferior a 0!", validator: function(value) { return value.replace('.', '').replace(',', '.') > 0; }},},
                
                // --- CAMPOS OCULTOS PARA O MODAL ---
                { 
                    name: "modelo", type: "text", css: 'hide', width: 0,
                    insertTemplate: function() { return this.insertControl = $("<input>").addClass('classe-modelo-input'); },
                    editTemplate: function(value) { return this.editControl = $("<input>").val(value).addClass('classe-modelo-input'); },
                    insertValue: function() { return this.insertControl.val(); },
                    editValue: function() { return this.editControl.val(); }
                },
                { 
                    name: "serialnumber", type: "text", css: 'hide', width: 0,
                    insertTemplate: function() { return this.insertControl = $("<input>").addClass('classe-serial-input'); },
                    editTemplate: function(value) { return this.editControl = $("<input>").val(value).addClass('classe-serial-input'); },
                    insertValue: function() { return this.insertControl.val(); },
                    editValue: function() { return this.editControl.val(); }
                },
                
                { type: "control" }
            ], 
            onRefreshed: function(args) { $(".jsgrid-select2").select2(); }
    });

    
    window.currentRowContext = null;

    $(document).on("click", ".btn-modal-obs", function(e){
        e.stopPropagation(); 
        window.currentRowContext = $(this).closest('tr');

        var obs = $(this).val();
        var mod = window.currentRowContext.find('.classe-modelo-input').val();
        var sn  = window.currentRowContext.find('.classe-serial-input').val();

        $('#observacaomodal').val(obs);
        $('#modelomodal').val(mod);
        $('#serialnumbermodal').val(sn);
        
        var codProd = "";
        if (window.currentRowContext.hasClass('jsgrid-edit-row')) {
             codProd = window.currentRowContext.find('.td-codproduto-soocod').text();
        } else {
             codProd = $('.inputCodproduto > select').val();
        }
        if(codProd) serialnumbers(codProd);

        $('#modal-observacao').modal('show');
    });

    $(document).on("click", ".btn-observacao", function(e){ 
        e.preventDefault();
        
        if(window.currentRowContext){
            var obs = $('#observacaomodal').val();
            var mod = $('#modelomodal').val();
            var sn  = $('#serialnumbermodal').val();

            window.currentRowContext.find('.btn-modal-obs').val(obs).trigger('change');
            window.currentRowContext.find('.classe-modelo-input').val(mod).trigger('change');
            window.currentRowContext.find('.classe-serial-input').val(sn).trigger('change');
        }
        
        $('#modal-observacao').modal('hide');
    });

    function numberToReal(numero) {
        if(!isNaN(numero)){
            var numero = numero.toFixed(2).split('.');
            numero[0] = numero[0].split(/(?=(?:...)*$)/).join('.');
            return numero.join(',');
        }
    }
    
    function serialnumbers(codproduto) {
        $('#listaSN').html('');
        $.ajax({
            url: "<?= Router::url(['controller'=>'Produtos','action'=>'serialnumberproduto']);?>/" + codproduto,
            dataType: "json",
            success: function(data){
                $.each(data, function(key, reg) {
                    $('#listaSN').append('<option value="'+reg.sSerialNumber+'">');
                })
            }
        });
    }

    <?php if (!isMobile()){ ?>
        $(document).ready(function() {
            $('.inputTipo > select').append($('<option>', {value: 0,text: 'Tipo',class: 'hide',}));
            $('.inputTipo > select').val(0);
            $('.inputCodproduto > select').val(0);

            $(document).on('change', '.inputTipo > select', function(){
                 var url = "<?= Router::url(['controller'=>'Produtos','action'=>'produtostipo']);?>/" + $(this).val();
                 $.ajax({
                    url: url, dataType: "json",
                    success:function(data){
                        data.sort(function(a, b){ if(a.descricao < b.descricao) return -1; return 1; });
                        $('.inputCodproduto > select').empty();
                        $.each(data, function(key, array) {
                             $('.inputCodproduto > select').append($('<option>', { value: array.codigo, text: array.descricao }));
                        });
                    }
                 });
            });
            
            $(document).on('change', '.inputCodproduto > select', function(){
                 var url = "<?= Router::url(['controller'=>'Produtos','action'=>'produto']);?>/" + $(this).val();
                 $.ajax({
                    url: url, dataType: "json",
                    success:function(data){
                        if(data.tipo == <?= C_ProdutosTipoProduto ?>) {
                            serialnumbers(data.codigo);
                        }
                        $('.inputTipo > select').val(data.tipo);
                        $('.inputDescricao input').val(data.descricao);
                        $('.inputUnidade input').val(data.unidade);
                        $('.inputValorunitario input').val(numberToReal(data.vlunitario));
                        // Zera qtd e total
                        $(".inputQuantidade input").val("");
                        $(".inputValortotal input").val("");
                    }
                 });
            });
            
            // Calculos Add
            $(document).on('change', '.inputQuantidade input, .inputValordesconto input, .inputValorunitario input', function(){ calculoAdd(); });
            function calculoAdd(){
                var qtde = $('.inputQuantidade input').val() || 0;
                var vldesconto = ($('.inputValordesconto input').val() || "0").replace('.', '').replace(',', '.');
                var vlunidade = ($('.inputValorunitario input').val() || "0").replace('.', '').replace(',', '.');
                var valortotal = (qtde * vlunidade) - vldesconto;
                $('.inputValortotal input').val(numberToReal(valortotal));
            }
        });
    <?php } ?>

    // Calculos Edit
    $(document).on('change', '.editQuantidade input, .editValorunitario input, .editValordesconto input', function(){ calculoEdit(); });
    function calculoEdit(){
        var qtde = $('.editQuantidade input').val() || 0;
        var vldesconto = ($('.editValordesconto input').val() || "0").replace('.', '').replace(',', '.');
        var vlunidade = ($('.editValorunitario input').val() || "0").replace('.', '').replace(',', '.');
        var valortotal = (qtde * vlunidade) - vldesconto;
        $('.editValortotal input').val(numberToReal(valortotal));
    }

    // Máscaras e Texto
    $(document).ready(function() {
        $('body').on('focus', '.mascaramonetaria', function(){ $(this).maskMoney({showSymbol:false, decimal:",", thousands:"."}); });
    });
    
    function tdcommuitotexto () {
        var i = 0;
        $('.jsgrid-cell').each(function() {
            if(!$(this).hasClass('cellInput') && $(this).text().length > 50) {
                $(this).attr('data-textointeiro', $(this).text());
                $(this).html($(this).text().substr(0, 49) + '... <div class="btn btn-xs btn-orc-outline-teal btn-exapndemuitotexto btn-'+i+'" style="display:inline-flex;padding:2px 8px;"><i class="fa fa-search"></i></div>');
                i++;
            }
        });
    }
    $(document).on('click', '.btn-exapndemuitotexto', function(e) {
        e.preventDefault();
        bootbox.alert({ message: $(this).parent().attr('data-textointeiro'), size: 'xl' });
    });

</script>
