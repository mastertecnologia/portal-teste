<?php
  	use Cake\Routing\Router;
	// Breadcumbs
	$this->Breadcrumbs->add('Orçamentos', ['controller' => 'Orcamentos', 'action' => 'index'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Editar Orçamento', [], ['class' => 'breadcrumb-item active']);

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
        readonly: <?= in_array($orcamento->status, [C_OrcamentoStatusPendente, C_OrcamentoStatusEnviado]) ? '0' : '1' ?>,
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
<style>
	.bg {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: space-between;
		font-family: 'Lato', sans-serif;
    }
    .file-drop-area {
		position: relative;
		display: flex;
		align-items: center;
		width: 100%;
		max-width: 100%;
		padding: 4px;
		border-bottom: 1px solid #E9ECEF;
		/* border-radius: 3px; */
		transition: 0.2s;
    }
    .fake-btn {
		flex-shrink: 0;
		border-radius: 3px;
		padding: 5px;
		margin-right: 30px;
		font-size: 12px;
		text-transform: uppercase;
    }
    .file-msg {
		font-size: small;
		font-weight: 300;
		line-height: 1.4;
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
    }
    .file-input {
		position: absolute;
		left: 0;
		top: 0;
		height: 100%;
		width: 100%;
		cursor: pointer;
		opacity: 0;
    }
</style>
<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<h3 class='text-center'>Proposta de Orçamento</h3>
			<?= $this->Form->create($orcamento, ['class' => 'form-material']); //floating-labels?> 
				<div class="row m-t-10">
					<div class="col-lg-9 col-md-9 col-sm-12 col-xs-12">
						<label class="control-label">Cliente</label> <br>
						<?= empty($orcamento->cliente->razaosocial) ? $orcamento->cliente->nome : $orcamento->cliente->razaosocial ?>
					</div>
					<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
						<label class="control-label text-muted">Válido até</label>
						<?= $this->Form->text('validoate', ['class' => 'form-control datepicker ', 'id' => 'validoate', 'default' => date('d/m/Y'), 'placeholder' => 'Insira a data', 'required' => true, 'data-mask' => '99/99/9999']) ?>
					</div>
				</div>
				<div class="row m-t-10">
					<div class="col-12">
						<label class="control-label">Observação</label>
						<?= $this->Form->textarea('solicitacao', ['novalidate' => true, 'id' => 'observacoes', 'class' => 'editor form-control', 'label' => false]) ?>
					</div>
				</div>
				<div class="row m-t-10">
					<div class="col-12">
						<label class="control-label text-muted">Status do orçamento</label>
						
						<h5> <?= orcamentoStatus($orcamento->status) ?> </h5>

						<?php if(isset($temordem) && $temordem != 'nao'): ?>
							<h5 class="m-t-10"> 
								<?= $this->Html->link(
									'<i class="fa fa-wrench"></i> Ordem de serviço gerada: Nº ' . $temordem, 
									["controller" => "ordensservico", "action" => "edit", $temordem], 
									['class' => 'btn btn-sm btn-info text-white m-r-5', 'escape' => false]
								); ?> 
							</h5>
						<?php endif; ?>
					</div>
				</div>
				<?php if($orcamento->status != C_OrcamentoStatusAprovado && $role == 0) { ?> 
					<h4 class='texte-center'>Produtos e Serviços</h4><br>
					<div class="row">
						<div class="col-lg-2 col-md-12">
							<label class="control-label">Código</label>
							<?= $this->Form->control('idproduto', ['class' => 'form-control selectpicker', 'data-live-search' => true, 'options' => $produtos, 'value' => 0, 'label' => false]) ?>
						</div>
						<div class="col-lg-5 col-md-12">
							<div class="form-group ">
								<label class="control-label text-muted">Produto/Serviço</label>
								<?= $this->Form->control('servico', ['class' => 'form-control', 'label' => false]) ?>
								<small class="qtdEstoque">  </small>
							</div>
						</div>
						<div class="col-lg-1 col-md-6">
							<div class="form-group ">
								<label class="control-label text-muted">Tipo</label>
								<?= $this->Form->control('tipo', ['class' => 'quantidade form-control', 'options' => ['Unidade', 'Hora'], 'label' => false]) ?>
							</div>
						</div>
						<div class="col-lg-1 col-md-6">
							<div class="form-group ">
								<label class="control-label text-muted">Qtde.</label>
								<?= $this->Form->control('quantidade', ['onkeypress' => 'return SomenteNumero(event, "#quantidade")', 'class' => 'quantidade form-control', 'label' => false]) ?>
							</div>
						</div>
						<div class="col-lg-1 col-md-6 ">
							<div class="form-group">
								<label class="control-label text-muted">Vl. Mensal</label>
								<?= $this->Form->control('valormensal', ['onkeypress' => 'return SomenteNumero(event, "#valormensal")', 'class' => 'mensal form-control mascaramonetaria', 'label' => false]) ?>
							</div>
						</div>
						<div class="col-lg-1 col-md-6">
							<div class="form-group ">
								<label class="control-label text-muted">Vl. Unitário </label>
								<?= $this->Form->control('valoruni', ['onkeypress' => 'return SomenteNumero(event, "#valoruni")', 'class' => 'form-control mascaramonetaria', 'label' => false]) ?>
							</div>
						</div>
						<div class="col-lg-1 col-md-12">
							<div class="form-group ">
								<label class="control-label text-muted">Vl. Total</label>
								<?= $this->Form->control('valordoservico', ['class' => 'form-control', 'label' => false, 'disabled' => true]) ?>
							</div>
						</div>
					</div>
					<div class='row'>
						<div class="col-lg-12 col-md-12">
							<div class="form-group ">
								<label class="control-label text-muted">Descrição</label>
								<?= $this->Form->control('observacao', ['class' => 'form-control', 'label' => false]) ?>
							</div>
						</div>
					</div>
					<button class="btn btn-secondary float-right" id='btn-addservico'>Adicionar</button>
					<br><br>
				<?php }
				if($orcamento->status == C_OrcamentoStatusAprovado && $role == 0 && !empty($orcamento->ipaprovacao)) { ?> 
					<div class="row">
						<div class="col-lg-2 col-md-3 col-sm-4 col-xs-12">
							<label class="control-label">IP aprovação: </label> <br>
							<?= $orcamento->ipaprovacao ?>
						</div>
						<div class="col-lg-2 col-md-3 col-sm-4 col-xs-12">
							<label class="control-label">Navegador aprovação: </label> <br>
							<?= $orcamento->navegadoraprovacao ?>
						</div>
					</div>
				<?php } ?>
				<div id="carrinho" class='m-t-10'> </div>
				<div class="row m-t-10">
					<div class="col-12">
						<?php
							if(in_array($orcamento->status, [C_OrcamentoStatusPendente, C_OrcamentoStatusEnviado])) echo $this->Form->button('Salvar alterações realizadas', ['class' => 'btn btn-success m-l-5']);
							echo $this->Html->Link('Alterar Situação', ['action' => 'alterarsituacao', $orcamento->id], ['class' => 'btn btn-queequaseinfo m-l-5']);
							echo $this->Html->Link('Imprimir', ['action' => 'imprimir', $orcamento->id], ['class' => 'btn btn-orange m-l-5']);
							echo $this->Html->Link('Enviar e-mail', ['#'], ['class' => 'btn btn-purple btn-email m-l-5']);
							if($temordem == 'nao') echo $this->html->Link('Transformar em ordem', ['action' => 'novaordem', $orcamento->id], ['class' => 'btn btn-warning m-l-5']);
						?>
					</div>
				</div>
			<?= $this->Form->end(); ?>
		</div>
	</div>
</div>
<!-- Modal Email -->
<div class="modal fade none-border" id="modal-email">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="row m-20">
				<div class="col-12">
					<?= $this->Form->create(null, ['url' => ['controller' => 'Orcamentos', 'action' => 'email'], 'enctype' => 'multipart/form-data', 'type' => 'file', 'class' => 'form-material']); //floating-labels?> 
						<div class="form-group">
							<label class="control-label text-muted">Destinatário</label>
							<?= $this->Form->control('emailemail', ['id' => 'emailemail', 'value' => $orcamento->cliente->email, 'class' => 'form-control', 'label' => false, 'required' => true]) ?>
							<br>
							<label class="control-label text-muted">Adicionar Anexo</label>
							<div class="bg">
								<div class="file-drop-area">
									<span class="fake-btn text-muted">Escolha o(s) arquivo(s) ou arraste-o(s) aqui</span>
									<input multiple class="file-input form-control"  name="file[]" id="file" type="file">
								</div>
							</div>
							<br>
							<?= $this->Form->control('idorcamento', ['value' => $orcamento->id, 'label' => false, 'type' => 'hidden']) ?>
						</div>
						<?= $this->Form->button('Enviar', ['class' => 'btn btn-purple text-white float-right m-l-10']) ?>
						<button type="button" class="btn btn-danger waves-effect float-right" data-dismiss="modal">Fechar</button>
					<?= $this->Form->end(); ?>
				</div>
			</div>
			<div class="modal-footer">
			</div>
		</div>
	</div>
</div>
<script>
	// Carrinho
		carrinho();
		function carrinho(){
			$.ajax({
				url: "<?= Router::url(['controller'=>'Orcamentos','action'=>'carrinhoedit']);?>/" + <?= $orcamento->id ?>,
				dataType: "html",
				success : function(data) {
					$("#carrinho").html(data);
					$("#carrinho").fadeIn();
					$('#valormensal-modal, #valoruni-modal').prop('disabled', false).removeAttr('disabled').prop('readonly', false).removeAttr('readonly');
				},
				error : function(error) { alert(error);}
			});
		}
		$(document).on('shown.bs.modal', '#modal-edit-item', function() {
			$('#valormensal-modal, #valoruni-modal').prop('disabled', false).removeAttr('disabled').prop('readonly', false).removeAttr('readonly');
		});
		$(document).on('input focus', '#valormensal-modal, #valoruni-modal', function() {
			$(this).prop('disabled', false).removeAttr('disabled').prop('readonly', false).removeAttr('readonly');
		});

	// Limpa Session
		$(window).bind('beforeunload', function(){
			$.ajax({ url: "<?= Router::url(['controller'=>'Orcamentos','action'=>'limpasession']);?>" });
		});

	// E-mail
		$('.btn-email').click(function(e){
			e.preventDefault();
			$('#modal-email').modal('toggle');
		});
	// Files
		$(document).on('change', '.file-input', function() {
			var filesCount = $(this)[0].files.length;
			var $textContainer = $(this).prev();
			var fileName = $(this).val().split('\\').pop();
			if (filesCount === 1) {
				var fileName = $(this).val().split('\\').pop();
				$textContainer.text(fileName);
			} else $textContainer.text(filesCount + ' arquivos selecionados');
		});
	// Só numero
		function SomenteNumero(e, campo){
			var tecla=(window.event)?event.keyCode:e.which;  

			if((tecla>47 && tecla<58)) return true;
			else if (tecla==8 || tecla==0) return true;
			else if (tecla == 46)  return false;    
			else if( $(campo).val().indexOf(',') > -1 && tecla == 44 ) return false
			else if( $(campo).val().indexOf(',') <= -1 && tecla == 44 ) return true
			else  return false;
		}
	// Produto
		$('#idproduto').change(function(e){
			if( $(this).val() != 0){
				$('#valoruni').attr('disabled', true);
				$('.mensal').attr('disabled', true);
				$.ajax({
					type: "post",
					url: "<?= Router::url(['controller'=>'Produtos','action'=>'produto']);?>/" + $(this).val(),
					dataType: "json",
					success: function(data){
						if (data.mensagem) {
							$('#servico').val('');
							$('#valoruni').val('');
							$('.qtdEstoque').text(data.mensagem).show();
							$('#valoruni').prop('disabled', false);
							$('.mensal').prop('disabled', false);
							return;
						}
						$('#servico').val((data.descricao || '').toString().trim());
						$('#quantidade').val('');
						$('#valordoservico').val('');
						if(data.tipo == <?= C_ProdutosTipoServico ?>) {
							$('#valormensal').prop('disabled', false);
							$('#valoruni').prop('disabled', false);
							$('#valormensal').val('');
							$('#valoruni').val(numberToReal(data.vlunitario));
							$('#tipo').val(1);
							$('#quantidade').mask('99:99');
							$('.qtdEstoque').hide();
						} else if (data.tipo == <?= C_ProdutosTipoProduto  ?>) {
							$('#valormensal').prop('disabled', 'disabled');
							$('#valoruni').prop('disabled', false);
							$('#valoruni').val(numberToReal(data.vlunitario));
							$('#valormensal').val('');
							$('#tipo').val(0);
							$('#quantidade').mask('0000000');
							$.ajax({
								type:"post",
								url: "<?= Router::url(['controller'=>'Produtos','action'=>'qtdestoque']);?>/" + data.codigo,
								dataType: "json",
								success:function(qtdestoque) {
									var msg = (qtdestoque === -999 || qtdestoque === null || (typeof qtdestoque === 'number' && qtdestoque < 0))
										? 'Estoque: indisponível (consulte o ERP)'
										: ('Qtd. em estoque: ' + qtdestoque);
									$('.qtdEstoque').text(msg).show();
								},
								error: function() { $('.qtdEstoque').text('Estoque: indisponível').show(); }
							});
						} else {
							$('#valormensal').prop('disabled', false);
							$('#valoruni').prop('disabled', false);
							$('#valormensal').val(numberToReal(data.vlunitario));
							$('#valoruni').val(numberToReal(data.vlunitario));
							$('#tipo').val(0);
							$('#quantidade').mask('0000000');
							$('.qtdEstoque').hide();
						}
					},
					error: function (xhr) {
						var msg = 'Produto/serviço não encontrado.';
						if (xhr.responseJSON && xhr.responseJSON.mensagem) msg = xhr.responseJSON.mensagem;
						$('.qtdEstoque').text(msg).show();
						$('#valoruni').val('').prop('disabled', false);
						$('.mensal').prop('disabled', false);
					}
				});
			}else{
				$('#servico').val('');
				$('#valoruni').val('');
				$('#valoruni').attr('disabled', false);
				$('.mensal').attr('disabled', false);
			}
		});

	// Tipo
		$('#tipo').change(function(){
			if($(this).val() == 1) $('#quantidade').mask('99:99');
			else $('#quantidade').mask('0000000');
		});
	// Valores
		$('#valoruni').keydown(function(){
			valor = $(this).val() .replaceAll('.', '').replaceAll(',', '.');
			if(valor > 0) $('#valormensal').val('');
		});

		$('#valormensal').keydown(function(){
			valor = $(this).val() .replaceAll('.', '').replaceAll(',', '.');
			if(valor > 0) $('#valoruni').val('');
		});
	// Cálculos
		$('#quantidade, #valoruni, #idproduto').keyup(function(e){
			if( $('#quantidade').val().indexOf(':') > -1  ) {
				quantidadeArray = $('#quantidade').val().split(':');
				quantidade =( parseFloat(quantidadeArray[0]) + ( parseFloat(quantidadeArray[1]) / 6 / 10 )).toFixed(2);
			}else quantidade = $('#quantidade').val().replaceAll('.', '').replaceAll(',', '.')
			
			valoruni = $('#valoruni').val().replaceAll('.', '').replaceAll(',', '.')
			valormensal = $('#valormensal').val().replaceAll('.', '').replaceAll(',', '.')
			valor = 0;
			if(valoruni != '') valor = valoruni;
			//else valor = valormensal;
			if(quantidade > 0 && valor){
				valortotal = quantidade * valor;
				$('#valordoservico').val(numberToReal(valortotal));
			}
			else $('#valordoservico').val('');
		});

	// Add Serviço
		$('#btn-addservico').click(function(e){
			e.preventDefault();
			servico =       $('#servico').val();
			quantidade =	$('#quantidade').val();
			valoruni =      $('#valoruni').val();
			valordoservico= $('#valordoservico').val();
			observacao = 	$('#observacao').val();
			valormensal = 	$('#valormensal').val();
			idproduto =		$('#idproduto').val();
			tipo =			$('#tipo').val();

			if(servico == ''){
				bootbox.alert('Preencha o campo "Descrição".');
				return false;
			}

			if(quantidade == '' || (valoruni == '' && valormensal == '')){
				bootbox.alert('Preencha o campo "Quantidade" e o campo de valor respectivo.');
				return false;
			}

			if(valoruni == '') valoruni = 0;
			if(valormensal == '') valormensal = 0;
			
			$.ajax({
				url: "<?= Router::url(['controller'=>'Orcamentos','action'=>'addservico']);?>/edit",
				dataType: "html",
				type: 'POST',
				data: { servico: servico, quantidade: quantidade, valoruni: valoruni, valordoservico: valordoservico, observacao: observacao, valormensal: valormensal, idproduto: idproduto, tipo : tipo},
				success : function(data) {
					console.log(data);
					if(data == 'nao pode'){
						bootbox.alert('O serviço já está no carrinho');
						return false;
					}
					carrinho();
					$('#servico').val('');
					$('#quantidade').val('');
					$('#valoruni').val('');
					$('#valordoservico').val('');
					$('#observacao').val('');
					$('#valormensal').val('');
					$('#idproduto').val(0);
					$('#tipo').val(0);
					$('#idproduto').selectpicker('refresh');
					$('.qtdEstoque').text('').hide();
					$('#valormensal').attr('disabled', false);
					$('#valoruni').attr('disabled', false);
					$('#servico').focus();
				},
				error : function(xhr) {
					var msg = 'Erro ao adicionar item. Tente novamente.';
					if (xhr.responseJSON && xhr.responseJSON.mensagem) msg = xhr.responseJSON.mensagem;
					else if (xhr.responseText && xhr.responseText.length < 200) msg = xhr.responseText;
					if (typeof bootbox !== 'undefined') bootbox.alert(msg); else alert(msg);
				}
			});
		});

	// Double Submit
		jQuery.fn.preventDoubleSubmission = function() {
			$(this).on('submit',function(e){
				var $form = $(this);
				if ($form.data('submitted') === true) {
					e.preventDefault();
				} else {
					$form.data('submitted', true);
				}
			});
			return this;
		};

		$('form').preventDoubleSubmission();
	// 
</script>