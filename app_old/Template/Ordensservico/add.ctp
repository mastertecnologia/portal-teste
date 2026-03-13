<?php
	use Cake\Routing\Router;
    $this->Breadcrumbs->add('Ordens de Serviço', ['controller' => 'Ordensservico', 'action' => 'index'], ['class' => 'breadcrumb-item']);
    $this->Breadcrumbs->add('Cadastrar', [], ['class' => 'breadcrumb-item active']);
?>
<style>
	.jsgrid-grid-header, .jsgrid-grid-body {
		overflow: auto; 
	} 
	.jsgrid-cell {
		height: 50px; 
		overflow: hidden;
	}
	.jsgrid-cell > select > option { text-align: left; }
</style>
<div class="col-md-12">
    <div class="card">
        <div class="card-body">
            <?= $this->Form->create($ordem, ['class' => 'form-material']) ?>
			<div class="row">
				<div class="col-lg-6 col-sm-12">
					<label class="control-label m-b-0">Cliente</label>
					<?= $this->Form->control('idcliente', ['data-live-search' => true, 'options' => $clientes, 'title' => 'Selecione um cliente', 'class' => 'form-control selectpicker', 'label' => false, 'required' => true]) ?>
				</div>
				<div class="col-lg-6 col-sm-12">
					<label class="control-label m-b-0">Solicitante</label>
					<?= $this->Form->control('idsolicitante', ['class' => 'selectpicker form-control', 'title' => 'Solicitante (opcional)', 'data-live-search' => true, 'options' => '', 'label' => false, 'required' => false]) ?>
					
					<!-- Campo para "Outros" - inicialmente escondido -->
					<div id="solicitante-outros-container" style="display: none; margin-top: 10px;">
						<label class="control-label m-b-0">Nome do Solicitante (Outros)</label>
						<?= $this->Form->control('solicitante_outros', [
							'class' => 'form-control', 
							'label' => false, 
							'placeholder' => 'Digite o nome do solicitante',
							'maxlength' => 255
						]) ?>
					</div>
				</div>
			</div>
			<div class="row clienteTelemail m-t-10">
					<div class="col-lg-3 col-sm-6">
						<label class="control-label m-b-0">Telefone para contato</label>
						<?= $this->Form->control('telefone', ['class' => 'telefone form-control clienteTelemail', 'label' => false, 'placeholder' => 'Nenhum telefone']) ?>
					</div>
					<div class="col-lg-3 col-sm-6">
						<label class="control-label m-b-0">Celular para contato</label>
						<?= $this->Form->control('celular', ['class' => 'celular form-control clienteTelemail', 'label' => false, 'placeholder' => 'Nenhum celular']) ?>
					</div>
					<div class="col-lg-6 col-sm-12">
						<label class="control-label m-b-0">E-mail para contato</label>
						<?= $this->Form->email('email', ['type' => 'text', 'class' => 'email form-control clienteTelemail', 'label' => false, 'placeholder' =>'Nenhum email']) ?>
					</div>
				</div>
				<div class="row m-t-10">
					<div class="col-lg-3 col-sm-12">
						<div class="form-group ">
							<label class="control-label m-b-0">Data de Abertura</label>
							<?= $this->Form->text('dataabertura', ['value' => date('d/m/Y'), 'class' => 'form-control datepicker', 'label' => false, 'required' => true]) ?>
						</div>
					</div>
					<div class="col-lg-3 col-sm-12">
						<div class="form-group ">
							<label class="control-label m-b-0">Data de Previsão</label>
							<?= $this->Form->text('dataprevisao', ['placeholder' => 'Data', 'class' => 'form-control datepicker', 'label' => false, 'required' => true]) ?>
						</div>
					</div>
					<div class="col-lg-3 col-sm-12">
						<label class="control-label m-b-0">Prioridade</label>
						<?= $this->Form->control('prioridade', ['placeholder' => 'Data', 'options' => C_OrdensPrioridade,  'class' => 'form-control', 'label' => false, 'required' => true]) ?>
					</div>
					<div class="col-lg-3 col-sm-12">
						<label class="control-label m-b-0">Contrato</label>
						<?= $this->Form->control('contrato', ['placeholder' => 'Data', 'options' => C_OrdensContrato,  'class' => 'form-control', 'label' => false, 'required' => true]) ?>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-6 col-sm-12">
						<label class="control-label m-b-0">Status</label>
						<?= $this->Form->control('idarea', ['data-live-search' => true, 'options' => $areas, 'title' => 'Selecione um status', 'class' => 'form-control selectpicker', 'label' => false, 'required' => true]) ?>
					</div>
					<div class="col-lg-6 col-sm-12">
						<label class="control-label m-b-0">Tipo de OS</label>
						<?= $this->Form->control('idproblema', ['data-live-search' => true, 'options' => $problemas, 'title' => 'Selecione um Tipo de OS', 'class' => 'form-control selectpicker', 'label' => false, 'required' => true]) ?>
					</div>
				</div>
				<div class="row m-t-10">
					<div class="col-lg-6 col-md-12">
						<label class="control-label m-b-0">Descrição do Problema</label>
						<?= $this->Form->textarea('relato', ['maxlength' => 200, 'placeholder' => 'Insira a descrição do problema da ordem', 'class' => 'form-control', 'label' => false, 'required' => false]) ?>
					</div>
					<div class="col-lg-6 col-md-12">
						<label class="control-label m-b-0">Observação</label>
						<?= $this->Form->textarea('observacao', ['maxlength' => 200, 'placeholder' => 'Obs.', 'class' => 'form-control', 'label' => false, 'required' => false]) ?>
					</div>
				</div>
				<div class="row m-t-10">
					<div class="col-lg-4 col-sm-12">
						<label class="control-label m-b-0">Atendimento</label>
						<?= $this->Form->control('atendimento', ['placeholder' => 'Data', 'options' => C_OrdensAtendimento,  'class' => 'form-control', 'label' => false, 'required' => true]) ?>
					</div>
					<div>
						<?= $this->Form->control('idEmpresaAtual', ['id' => 'idEmpresaAtual', 'class' => 'form-control inputMobile', 'label' => false, 'type'=>'hidden']) ?>
					</div>
				</div>
				<hr>
				<!-- Campos pro mobile  -->
					<br><h4 class='text-center'>Adicionar Produtos/Serviços</h4><br>
					<?php if(isMobile()){ ?>
						<div class="row">
							<div class="col-2">
								<label class="form-group ">Tipo</label>
								<?= $this->Form->control('tipo', ['data-live-search' => true, 'options' => $tiposMobile, 'title' => 'Código', 'class' => 'inputMobile form-control selectpicker p-0', 'label' => false]) ?>
							</div>
							<div class="col-2">
								<label class="control-label m-b-0 text-muted">Código</label>
								<?= $this->Form->control('codproduto', ['data-live-search' => true, 'options' => $produtosMobile, 'title' => 'Código', 'class' => 'inputMobile form-control selectpicker p-0', 'label' => false]) ?>
							</div>
							<div class="col-2">
								<label class="control-label m-b-0 text-muted">Qtd. Estoque: <span class="qtdEstoque"></span></label>
							</div>
							<div class="col-7">
								<div class="form-group ">
									<label class="control-label m-b-0 text-muted">Descrição</label>
									<?= $this->Form->control('descricao', ['class' => 'form-control inputMobile', 'label' => false, 'readonly']) ?>
								</div>
							</div>
							<div class="col-2">
								<div class="form-group ">
									<label class="control-label m-b-0 text-muted">Unidade</label>
									<?= $this->Form->control('unidade', ['class' => 'form-control inputMobile', 'label' => false, 'readonly']) ?>
								</div>
							</div>
							<div class="col-5">
								<div class="form-group ">
									<label class="control-label m-b-0 text-muted">Quantidade</label>
									<?= $this->Form->control('quantidade', ['class' => 'aquisicao form-control inputMobile', 'label' => false]) ?>
								</div>
							</div>
							<div class="col-5">
								<div class="form-group ">
									<label class="control-label m-b-0 text-muted">Valor Unitário (R$)</label>
									<?= $this->Form->control('valorunitario', ['class' => 'aquisicao form-control inputMobile mascaramonetaria', 'label' => false,]) ?>
								</div>
							</div>
							<div class="col-6">
								<div class="form-group ">
									<label class="control-label m-b-0 text-muted">Valor Desconto (R$)</label>
									<?= $this->Form->control('valordesconto', ['class' => 'mensal form-control inputMobile mascaramonetaria', 'label' => false]) ?>
								</div>
							</div>
							<div class="col-6">
								<div class="form-group ">
									<label class="control-label m-b-0 text-muted">Valor Total (R$)</label>
									<?= $this->Form->text('valortotal', ['id' => 'valortotal', 'class' => 'form-control inputMobile', 'label' => false, 'readonly']) ?>
								</div>
							</div>
							<div class="col-6">
								<div class="form-group ">
									<label class="control-label m-b-0 text-muted">Serial Number</label>
									<?= $this->Form->control('serialnumber', ['list' => 'listaSN', 'id' => 'serialnumber', 'class' => 'form-control inputMobile', 'label' => false]) ?>
									<datalist id="listaSN"> </datalist>
								</div>
							</div>
						</div>
						<?= $this->Html->link('Adicionar item', [], ['class' => 'btn btn-info btn-additem m-b-20']) ?>
					<?php } ?>
				<!-- Tabela -->
				<div id="grid_table"></div>
				<!-- valortotal que é exibido e o input hidden dele -->
				<?= '<h5 class="text-right text-success font-weight-bold m-r-15 valortotalordem"> </h5>' ?>
				<?= $this->Form->control('valortotalordem', ['type' => 'hidden', 'label' => false, ]) ?>
					
				<p class='m-t-10'><i>O cadastro de horas e parcelas ficará disponível apenas após a abertura da Ordem de Serviço.</i></p>
				<?= $this->Form->button('Abrir Ordem de Serviço', ['class' => 'btn btn-success']) ?>
            <?= $this->Form->end() ?>
            <div class="clearfix"></div>
        </div>
    </div>
</div>
<div class="modal fade none-border" id="modal-observacao">
    <div class="modal-dialog">
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
                            <label class="control-label m-b-0">Product Key</label>
                            <?= $this->Form->text('productkeymodal', ['maxlength' => 100, 'placeholder' => 'Insira a chave do produto', 'id' => 'productkeymodal', 'class' => 'form-control', 'label' => false]);?>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label class="control-label m-b-0">Observação NF-e</label>
                            <?= $this->Form->textarea('observacaomodal', ['placeholder' => 'Insira a observação do item', 'id' => 'observacaomodal', 'class' => 'form-control', 'label' => false]);?>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label class="control-label m-b-0">Observação Interna</label>
                            <?= $this->Form->textarea('observacainternaomodal', ['placeholder' => 'Insira a observação interna', 'id' => 'observacainternaomodal', 'class' => 'form-control', 'label' => false]);?>
                        </div>
                    </div>
                    </div>
            </div>
            <div class="modal-footer">
                <?= $this->Html->link('Salvar Detalhes', ['#'], ['class' => 'btn btn-success text-white btn-observacao m-l-5']) ?>
                <button type="button" class="btn btn-danger waves-effect" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Serial Number -->
<div class="modal fade none-border" id="modal-serialnumber">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="row m-20 m-b-0 form-material">
				<div class="col-12">
					<div class="form-group ">
						<label class="control-label m-b-0">Serial Number</label>
						<?= $this->Form->text('serialnumbermodal', ['list' => 'listaSN', 'maxlength' => 100, 'placeholder' => 'Insira o serial number do item', 'id' => 'serialnumbermodal', 'class' => 'form-control', 'label' => false]);?>
						<datalist id="listaSN"> </datalist>
						<small>*Informe apenas um Serial Number. Para mais códigos, adicione o produto novamente.</small>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<?= $this->Html->link('Salvar serial number', ['#'], ['class' => 'btn btn-success text-white btn-serialnumber m-l-5']) ?>
				<button type="button" class="btn btn-danger waves-effect" data-dismiss="modal">Fechar</button>
			</div>
		</div>
	</div>
</div>
<!-- Modal para pesquisa de produtos -->
<div class="modal fade" id="modal-pesquisa-produto" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Pesquisar Produto</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="input-group">
                            <input type="text" id="termo-pesquisa-produto" class="form-control" placeholder="Digite o nome ou código do produto...">
                            <span class="input-group-btn">
                                <button class="btn btn-info" type="button" onclick="buscarProdutos()">Pesquisar</button>
                            </span>
                        </div>
                    </div>
                </div>
                <br>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Descrição</th>
                                <th>Preço</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody id="resultado-pesquisa-produtos">
                            </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>
<script>
	idEmpresAtual = $("#empresaSidebar").val()
	$('#idEmpresaAtual').val(idEmpresAtual) 
		
	// Solicitantes e telemail
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
					
					// Adiciona a opção "Outros" como primeira opção
					$('#idsolicitante').append($('<option>', {
						value: 0,
						text: 'Outros'
					}));
					
					// Adiciona os demais solicitantes
					$.each(data, function(key, array) {
						$('#idsolicitante').append($('<option>', {
							value: key,
							text: array
						}));
					});
					
					$('#idsolicitante').selectpicker("refresh");
					
					// Esconde o campo "Outros" inicialmente
					$('#solicitante-outros-container').hide();
				},
			});
		}

		$("#idsolicitante").change(function() {
			if($(this).val() == 0) {
				// Se for "Outros", mostra o campo de texto
				$('#solicitante-outros-container').show();
				$('#solicitante-outros').focus();
			} else {
				// Se for um solicitante da lista, esconde o campo
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
				// Se for "Outros", limpa os campos de contato
				$('.clienteTelemail').show();
				$('.email').val('');
				$('.telefone').val('');
				$('.celular').val('');
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

		$("#idsolicitante").change(function() {
			loadSolTelemail($(this).val());
		})

	// URLsF
		var urlLoadData = "<?= Router::url(['controller'=>'Ordensservico','action'=>'carrinho']);?>";
		var urlAdd = "<?= Router::url(['controller'=>'Ordensservico','action'=>'carrinhoadd']);?>";
		var urlEdit = "<?= Router::url(['controller'=>'Ordensservico','action'=>'carrinhoedititem']);?>";
		var urlDelete = "<?= Router::url(['controller'=>'Ordensservico','action'=>'carrinhodelitem']);?>";
	// Tipos e Produtos
		var tiposOpt = <?= $tiposOpt ?>;
		var produtosOpt = <?= $produtosOpt ?>;
		produtosOpt.sort(function(a, b){
			if(a.descricao < b.descricao) { return -1; }
			if(a.descricao > b.descricao) { return 1; }
			return 0;
		})

	// jsGrid
		$('#grid_table').jsGrid({
			// Options
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
				pagerFormat: "Pages: {prev} {pages} {next} {pageIndex} of {pageCount}",
				pagePrevText: "Anterior",
				pageNextText: "Próxima",
				pageFirstText: "Primeira",
				pageLastText: "Última",
			// 
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
								$('#valortotalordem').val(valortotal); //formulário hidden
								$('.valortotalordem').html( '<font color="#212529"> Total geral:</font> R$ ' + numberToReal(valortotal)); //lugar que aparece escrito
								tdcommuitotexto();
								$('.inputTipo, .inputValordesconto, .inputValorunitario, .inputQuantidade, .inputValordesconto, .inputDescricao, .inputQuantidade, .inputUnidade, .inputObservacao, .inputValortotal, .inputSerialnumber').append('<small class="vazio">⠀⠀⠀</small>')
								$('.inputCodproduto').append('<small class="qtdEstoque"> ⠀⠀⠀</small>')
							},
						});
					}
					});
				},
				insertItem: function(item){
					idEmpresAtual = $("#empresaSidebar option:selected").val()
					item['idEmpresaAtual'] = idEmpresAtual
					return $.ajax({
					type: "POST",
					url: urlAdd+'/null/'+item.codproduto,
					data: item,
					success: function(data){
						$(".qtdEstoque, .vazio").remove();
						$("#grid_table").jsGrid("loadData");
						if(data == 'naopode') bootbox.alert('<p style="font-weight: 300; font-size: 1.1rem" class="text-center">Este produto já foi adicionado à ordem de serviço, não é possível adicioná-lo novamente.</p>');
					}, 
					error: function(data) {
						location. reload()
					}
					});
				},
				updateItem: function(item){
					idEmpresAtual = $("#empresaSidebar option:selected").val()
					item['idEmpresaAtual'] = idEmpresAtual
					return $.ajax({
					type: "PUT",
					url: urlEdit,
					data: item,
					success: function(data){
						$(".qtdEstoque, .vazio").remove();
						$("#grid_table").jsGrid("loadData");
					},
					error: function(data) {
						location. reload()
					}
					});
				},
				deleteItem: function(item){
					idEmpresAtual = $("#empresaSidebar option:selected").val()
					item['idEmpresaAtual'] = idEmpresAtual
					return $.ajax({
						type: "DELETE",
						url: urlDelete,
						data: item,
						success: function(){
							$(".qtdEstoque, .vazio").remove();
							$("#grid_table").jsGrid("loadData");
						},
						error: function(data) {
							location. reload()
						}
					});
				},
			},
			fields: [
				{ name: "id", title: "id", type: "text", css: 'hide', validade: 'required',  editing: false,},
				{ name: "tipo", title: "Tipo", type: "select", items: tiposOpt, validade: 'required',  editing: false, insertcss: 'cellInput inputTipo', editcss: "editTipo",},
				{
					name: "codproduto",
					title: "Código do Produto",
					type: "text", 
					width: 200,
					css: 'inputCodproduto',
					validate: "required",

					itemTemplate: function(value) {
						var item = produtosOpt.find(function(produto) {
							return produto.codigo == value;
						});
						return item ? item.descricao : value;
					},
					
					// Template para INSERÇÃO
					insertTemplate: function() {
						var $input = $("<input>").addClass("form-control input-codigo-val").prop("readonly", true);
						var $btn = $("<button>").addClass("btn btn-secondary btn-sm").html('<i class="fa fa-search"></i>');
						
						$btn.on("click", function() {
							window.activeInputCode = $input; 
							$('#termo-pesquisa-produto').val('');
							$('#resultado-pesquisa-produtos').html('');
							$('#modal-pesquisa-produto').modal('show');
							buscarProdutos();
							setTimeout(function() { $('#termo-pesquisa-produto').focus(); }, 500);
						});
						var $group = $("<div>").addClass("input-group").append($input).append(
							$("<div>").addClass("input-group-append").append($btn)
						);
						
						this.insertControl = $input;
						return $group;
					},
					insertValue: function() {
						return this.insertControl.val();
					},

					// Template para EDIÇÃO
					editTemplate: function(value) {
						var $input = $("<input>").addClass("form-control input-codigo-val").prop("readonly", true).val(value);
						var $btn = $("<button>").addClass("btn btn-secondary btn-sm").html('<i class="fa fa-search"></i>');
						
						$btn.on("click", function() {
							window.activeInputCode = $input;
							$('#termo-pesquisa-produto').val('');
							$('#resultado-pesquisa-produtos').html('');
							$('#modal-pesquisa-produto').modal('show');
							setTimeout(function() { $('#termo-pesquisa-produto').focus(); }, 500);
						});

						var $group = $("<div>").addClass("input-group").append($input).append(
							$("<div>").addClass("input-group-append").append($btn)
						);

						this.editControl = $input;
						return $group;
					},
					editValue: function() {
						return this.editControl.val();
					}
				},
				{ name: "descricao",  fixed: true, title: "Descrição", type: "text",  validate: "required", editing: false, readOnly: true, insertcss: 'cellInput inputDescricao', editcss: "editDescricao", validade: 'required', },
                { name: "observacao",  title: "Referenciar ▼", type: "text",  validate: "", insertcss: 'cellInput inputObservacao', editcss: "editObservacao", itemTemplate: function(value) { return "Detalhes/Obs"; } },
                { name: "unidade",  title: "Unidade", type: "text",  editing: false, readOnly: true, insertcss: 'cellInput inputUnidade', editcss: "editUnidade", validade: 'required', },
                { name: "quantidade",  title: "Qtde", type: "text",  insertcss: 'cellInput inputQuantidade', editcss: "editQuantidade", validate: { message: "A quantidade não pode ser igual ou inferior a 0!", validator: function(value) { return value.replace('.', '').replace(',', '.') > 0; }},},
                { name: "valorunitario",  title: "Vl. Unitário", type: "text",  insertcss: 'cellInput inputValorunitario', editcss: "editValorunitario", validate: { message: "O valor unitário não pode ser igual ou inferior a 0!", validator: function(value) { return value.replace('.', '').replace(',', '.') > 0; }},},
                { name: "valordesconto",  title: "Vl. Desconto", type: "text",  insertcss: 'cellInput inputValordesconto', editcss: "editValordesconto",},
                { name: "valortotal",  title: "Vl. Total", type: "text",  readOnly: true, insertcss: 'cellInput inputValortotal', editcss: "editValortotal", headercss: 'sai', css: 'fieldValortotal', validate: { message: "O valor total não pode ser igual ou inferior a 0!", validator: function(value) { return value.replace('.', '').replace(',', '.') > 0; }},},
                { name: "modelo", type: "text", css: 'hide', insertcss: 'hide inputModelo', editcss: 'hide editModelo' },
                { name: "serialnumber", type: "text", css: 'hide', insertcss: 'hide inputSerialnumber', editcss: 'hide editSerialnumber' },
				{ name: "productkey", type: "text", css: 'hide', insertcss: 'hide inputProductKey', editcss: 'hide editProductKey'},
				{ name: "obsinterna", type: "text", css: 'hide', insertcss: 'hide inputObsInterna', editcss: 'hide editObsInterna'},
                { type: "control" }
			], 
			onRefreshed: function(args) {
				$(".jsgrid-select2").select2();
			}
		});
	// numberToReal
		function numberToReal(numero) {
			if(!isNaN(numero)){
				var numero = numero.toFixed(2).split('.');
				numero[0] = numero[0].split(/(?=(?:...)*$)/).join('.');
				return numero.join(',');
			}
		}

	// Mobile
		<?php if (isMobile()){ ?>
		$(document).on('change', '#tipo', function(){
			$.ajax({
				url: "<?= Router::url(['controller'=>'Produtos','action'=>'produtostipo']);?>/" + $(this).val(),
				dataType: "json",
				success:function(data){
					$('#codproduto > option').remove();
					$.each(data, function(key, array) {
						$('#codproduto').append($('<option>', {
							value: key,
							text: array
						}));
					})
				}
			});
		});
		$(document).on('change', '#codproduto', function(){
			$.ajax({
				url: "<?= Router::url(['controller'=>'Produtos','action'=>'produto']);?>/" + $(this).val(),
				dataType: "json",
				success:function(data){
					$('#tipo').val(data.tipo);
					$('#descricao').val(data.descricao);
					$('#unidade').val(data.unidade);
					$('#valorunitario').val(numberToReal(data.vlunitario));
					$("#quantidade").val("");
					$("#valortotal").val("");
					$("#valordesconto").val("");
					$("#serialnumber").val("");
					serialnumbers(data.codigo);
				}
			});
			$.ajax({
				url: "<?= Router::url(['controller'=>'Produtos','action'=>'qtdestoque']) ?>/" + $(this).val(),
				success:function(data){
					$('.qtdEstoque').text('Qtd. em estoque: ' + data);
				},
			});
		});
		$(document).on('change', '#tipo', function(){calculoAddMobile(); });
		$(document).on('change', '#codproduto', function(){calculoAddMobile(); });
		$(document).on('change', '#quantidade', function(){calculoAddMobile(); });
		$(document).on('change', '#valordesconto', function(){calculoAddMobile(); });
		$(document).on('change', '#valorunitario', function(){calculoAddMobile(); });

		function calculoAddMobile(){
			var qtde = $('#quantidade') == "" ? 0 : $('#quantidade').val();
			var vldesconto = $('#valordesconto') == "" ? 0 :  $('#valordesconto').val().replace('.', '').replace(',', '.');
			var vlunidade = $('#valorunitario') == "" ? 0 :  $('#valorunitario').val().replace('.', '').replace(',', '.');
			valortotal = qtde * vlunidade - vldesconto;
			$('#valortotal').val(numberToReal(valortotal));
		}

		$(".btn-additem").click(function(e) {
			e.preventDefault();
			$.ajax({
				url: urlAdd,
				type: 'POST',
				contentType:false,
				processData: false,
				data: function(){
					var data = new FormData();
					var j = 1;
					$( ".inputMobile" ).each(function() {
						data.append( $( this ).attr('id'), $( this ).val() ); 
						j++;
					});
					return data;
				}(),
				success: function(result) {
					if(result == 'naopode') bootbox.alert('<p style="font-weight: 300; font-size: 1.1rem" class="text-center">Este produto já foi adicionado à ordem de serviço, não é possível adicioná-lo novamente.</p>');
					$("#grid_table").jsGrid("loadData");
					$( ".inputMobile" ).each(function() {
						$( this ).val(''); 
					});
				},
			});
		});
	// Desktop
		<?php }else{  ?>
			$(document).ready(function() {
				$('.inputTipo > select').append($('<option>', {value: 0,text: 'Tipo',class: 'hide',}));
				$('.inputTipo > select').val(0);
				//$('.inputCodproduto > select').append($('<option>', {value: 0,text: 'Código',class: 'hide',}));
				$('.inputCodproduto > select').val(0);
				$(document).on('change', '.inputTipo > select', function(){
					$.ajax({
						url: "<?= Router::url(['controller'=>'Produtos','action'=>'produtostipo']);?>/" + $(this).val(),
						dataType: "json",
						success:function(data){
							data.sort(function(a, b){
								if(a.descricao < b.descricao) { return -1; }
								if(a.descricao > b.descricao) { return 1; }
								return 0;
							})
							$('.inputCodproduto option').remove();
							$.each(data, function(key, array) {
								$('.inputCodproduto > select').append($('<option>', {
									value: array.codigo,
									text: array.descricao
								}));
							})
						}
					});
				});
				
				$(document).on('change', '.inputCodproduto input.input-codigo-val', function(){
					$.ajax({
						url: "<?= Router::url(['controller'=>'Produtos','action'=>'produto']) ?>/" + $(this).val(),
						dataType: "json",
						success:function(data){
							if(data.tipo == <?= C_ProdutosTipoProduto ?>) {
								$(".inputSerialnumber > input").prop('disabled', false);
								serialnumbers(data.codigo);
								$.ajax({
									url: "<?= Router::url(['controller'=>'Produtos','action'=>'qtdestoque']) ?>" + '/' + data.codigo,
									success:function(qtd){ if(qtd != -999) $('.qtdEstoque').text('Qtd. em estoque: ' + qtd); },
								});
							}else {
								$('.qtdEstoque').text('⠀⠀⠀');
								$(".inputSerialnumber > input").prop('disabled', 'disabled');
							}
							$('.inputTipo > select').val(data.tipo);
							$('.inputDescricao > input').val(data.descricao);
							$('.inputUnidade > input').val(data.unidade);
							$('.inputValorunitario > input').val(numberToReal(data.vlunitario));
							$(".inputQuantidade > input").val("");
							$(".inputValortotal > input").val("");
							$(".inputValordesconto > input").val("");
							$(".inputSerialnumber > input").val("");
						},
					});
				});
				
				$('.inputValordesconto > input').addClass('mascaramonetaria');
				$('.inputValorunitario > input').addClass('mascaramonetaria');

				$(document).on('change', '.inputTipo > select', function(){calculoAdd(); });
				$(document).on('change', '.inputCodproduto > select', function(){calculoAdd(); });
				$(document).on('change', '.inputQuantidade > input', function(){calculoAdd(); });
				$(document).on('change', '.inputValordesconto > input', function(){calculoAdd(); });
				$(document).on('change', '.inputValorunitario > input', function(){calculoAdd(); });

				function calculoAdd(){
					var qtde = $('.inputQuantidade > input') == "" ? 0 : $('.inputQuantidade > input').val() ;
					var vldesconto = $('.inputValordesconto > input').val() == "" ? 0 :  $('.inputValordesconto > input').val().replace('.', '').replace(',', '.');
					var vlunidade = $('.inputValorunitario > input').val() == "" ? 0 :  $('.inputValorunitario > input').val().replace('.', '').replace(',', '.');
					valortotal = qtde * vlunidade - vldesconto;
					$('.inputValortotal > input').val(numberToReal(valortotal));
				}
			});
		<?php }  ?>
	// Cálculo Edit
		$(document).on('change', '.editQuantidade > input', function(){   calculoEdit(); });
		$(document).on('change', '.editValordesconto > input', function(){calculoEdit(); });
		$(document).on('change', '.editValorunitario > input', function(){calculoEdit(); });
		function calculoEdit(){
			var qtde = $('.editQuantidade > input').val() == "" ? 0 : $('.editQuantidade > input').val() ;
			var vldesconto = $('.editValordesconto > input').val() == "" ? 0 :  $('.editValordesconto > input').val().replace('.', '').replace(',', '.');
			var vlunidade = $('.editValorunitario > input').val() == "" ? 0 :  $('.editValorunitario > input').val().replace('.', '').replace(',', '.');
			valortotal = qtde * vlunidade - vldesconto;
			$('.editValortotal > input').val(numberToReal(valortotal));
		}

		window.targetRow = null;
        window.isEditMode = false;

        // Ao clicar no input de observação (Grid)
        $(document).on("click, focus", ".inputObservacao > input, .editObservacao > input", function(e){ 
            // Identifica se é insert ou edit baseado na classe do pai
            window.isEditMode = $(this).parent().hasClass('editObservacao');
            
            // Pega a linha (TR) inteira
            window.targetRow = $(this).closest('tr');

            // Pega os valores atuais dos inputs ocultos na mesma linha
            var currentObs = $(this).val();
            var currentModelo = "";
            var currentSerial = "";
			var currentproducykey = "";
			var currentobsinterna = "";

            if(window.isEditMode){
                currentModelo = window.targetRow.find('.editModelo input').val();
                currentSerial = window.targetRow.find('.editSerialnumber input').val();
                currentProductkey = window.targetRow.find('.editProductkey input').val();
                currentObsInterna = window.targetRow.find('.editobsinterna input').val();
            } else {
                currentModelo = window.targetRow.find('.inputModelo input').val();
                currentSerial = window.targetRow.find('.inputSerialnumber input').val();
				currentProductkey = window.targetRow.find('.inputProductkey input').val();
                currentObsInterna = window.targetRow.find('.inputobsinterna input').val();
            }

            // Preenche o modal
            $('#observacaomodal').val(currentObs);
            $('#modelomodal').val(currentModelo);
            $('#serialnumbermodal').val(currentSerial);
            $('#productkeymodal').val(currentProductkey);
            $('#obsInternamodal').val(currentObsInterna);

            // Abre o modal
            $('#modal-observacao').modal('show');
        });

        // Ao clicar em Salvar no Modal
        $(document).on("click", ".btn-observacao", function(e){ 
            e.preventDefault();
            
            var obs = $('#observacaomodal').val();
            var mod = $('#modelomodal').val();
            var sn  = $('#serialnumbermodal').val();
			var pk 	= $('#productkeymodal').val();
			var obsInt = $('#obsinternamodal').val();

            if(window.targetRow){
                if(window.isEditMode){
                    window.targetRow.find('.editObservacao input').val(obs).trigger('change');
                    window.targetRow.find('.editModelo input').val(mod).trigger('change');
                    window.targetRow.find('.editSerialnumber input').val(sn).trigger('change');
                    window.targetRow.find('.editProductKey input').val(sn).trigger('change');
                    window.targetRow.find('.editObsInterna input').val(sn).trigger('change');
                } else {
                    window.targetRow.find('.inputObservacao input').val(obs).trigger('change');
                    window.targetRow.find('.inputModelo input').val(mod).trigger('change');
                    window.targetRow.find('.inputSerialnumber input').val(sn).trigger('change');
                    window.targetRow.find('.inputProductKey input').val(sn).trigger('change');
                    window.targetRow.find('.inputObsInterna input').val(sn).trigger('change');
                    window.targetRow.find('.input input').val(sn).trigger('change');
                }
            }
            
            $('#modal-observacao').modal('hide');
        });

        // Função auxiliar para carregar SN se necessário (mantida do original, ajustada se preciso)
        function serialnumbers(codproduto) {
            $('#listaSN').html('');
            $.ajax({
                url: "<?= Router::url(['controller'=>'Produtos','action'=>'serialnumberproduto']);?>/" + codproduto,
                dataType: "json",
                success: function(data){
                    $.each(data, function(key, reg) {
                        $('#listaSN').append('<option value="'+reg.sSerialNumber+'">');
                    })
                },
                error: function (error) { console.log(error); }
            });
        }
	// TDs com mto texto
		$('.jsgrid-button').click(function(){tdcommuitotexto()});
		$('th').click(function(){tdcommuitotexto()});
		function tdcommuitotexto () {
			i = 0;
			$('.jsgrid-cell').each(function() {
				if(!$(this).hasClass('cellInput')){
					if($(this).text().length > 50) {
						$(this).attr('data-textointeiro', $(this).text());
						$(this).html($(this).text().substr(0, 49))
						$(this).append('... <div class="btn btn-sm btn-primary btn-exapndemuitotexto btn-'+i+'"><i class="fa fa-search "></i></div>')
					}
				}
				$('.btn-'+i).attr('data-textointeiro', $(this).attr('data-textointeiro'));
				i++;
			});
		}
		$(document).on('click', '.btn-exapndemuitotexto', function(e) {
			e.preventDefault();
			bootbox.alert({ 
				message: $(this).attr('data-textointeiro'), 
				size: 'xl',
			})
		})




		// Variável global para armazenar qual input está chamando o modal (Insert ou Edit)
		window.activeInputCode = null;

		// Função para buscar produtos (Enter no input ou clique no botão)
		$('#termo-pesquisa-produto').on('keypress', function (e) {
			if(e.which === 13) { buscarProdutos(); }
		});

		function buscarProdutos() {
			var termo = $('#termo-pesquisa-produto').val();
			var tbody = $('#resultado-pesquisa-produtos');
			tbody.html('<tr><td colspan="4" class="text-center">Buscando...</td></tr>');

			// AJUSTE A URL ABAIXO PARA O SEU CONTROLLER DE PESQUISA REAL
			// Exemplo de URL: Produtos/pesquisar?termo=X
			$.ajax({
				url: "<?= Router::url(['controller'=>'Produtos','action'=>'pesquisar']);?>", 
				method: "GET",
				data: { termo: termo },
				dataType: "json",
				success: function(data) {
					tbody.empty();
					if(data.length > 0) {
						$.each(data, function(index, prod) {
							// Cria a linha da tabela
							var tr = $('<tr>');
							tr.append('<td>' + prod.codigo + '</td>');
							tr.append('<td>' + prod.descricao + '</td>');
							tr.append('<td>R$ ' + numberToReal(prod.vlunitario) + '</td>');
							
							var btn = $('<button>').addClass('btn btn-success btn-sm').text('Selecionar');
							
							// Evento de clique para selecionar
							btn.click(function() {
								selecionarProduto(prod.codigo);
							});
							
							tr.append($('<td>').append(btn));
							tbody.append(tr);
						});
					} else {
						tbody.html('<tr><td colspan="4" class="text-center">Nenhum produto encontrado.</td></tr>');
					}
				},
				error: function() {
					tbody.html('<tr><td colspan="4" class="text-center text-danger">Erro ao buscar produtos.</td></tr>');
				}
			});
		}

		function selecionarProduto(codigo) {
			if(window.activeInputCode) {
				// 1. Define o valor no input do Grid
				window.activeInputCode.val(codigo);
				
				// 2. Fecha o modal
				$('#modal-pesquisa-produto').modal('hide');
				
				// 3. IMPORTANTE: Dispara o evento 'change' manualmente
				// O seu código original escuta "change" para preencher descrição, preço, etc.
				// Como o JSGrid não dispara change nativo ao alterar valor via código, forçamos.
				window.activeInputCode.trigger('change');
			}
		}

</script>
