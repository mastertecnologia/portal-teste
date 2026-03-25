<?php
  	use Cake\Routing\Router;
	$this->append('css', $this->Html->css('/dist/css/orcamentos-premium'));
	$this->Html->script('/js/orcamentos', ['block' => true]);
	// Breadcumbs
	$this->Breadcrumbs->add('Orçamentos', ['controller' => 'Orcamentos', 'action' => 'index'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Novo Orçamento', [], ['class' => 'breadcrumb-item active']);
?>
<script>
	tinymce.init({
		selector: '.editor',  // change this value according to your HTML
		plugins : 'advlist autolink link image imagetools lists advlist charmap media preview autoresize hr jbimages textcolor fullscreen table help paste',
		height: 300,
		language: 'pt_BR',
		entity_encoding : "raw",
		menubar: false,
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
	.dtp table.dtp-picker-days tr > td{
		font-weight: 700	 !important;
		font-size: 0.8em	 !important;
		text-align: center	 !important;
		padding: 0.5em 0.3em !important;
	}
</style>
<div class="col-md-12 orc-premium-wrap">
	<div class="card orc-premium-card-inner">
		<div class="card-body">
			<h3 class="text-center orc-premium-page-title">Proposta de Orçamento</h3>
			<?= $this->Form->hidden('item_edit_id', ['id' => 'item_edit_id']); ?> <!-- Pega o ID do item para edição -->
			<?= $this->Form->create($orcamento, ['url' => ['action' => 'add'], 'enctype' => 'multipart/form-data', 'type' => 'file', 'class' => 'form-material']); //floating-labels?> 
				<div class="row">
					<br>
					<div class="col-lg-10 col-sm-12">
						<label class="control-label">Cliente</label>
						<?= $this->Form->control('idcliente', ['class' => 'form-control selectpicker', 'data-live-search' => true, 'options' => $clientes, 'title' => 'Selecione um cliente', 'label' => false, 'required' => true]) ?>
					</div>
					<div class="col-lg-2 col-sm-12">
						<div class="form-group">
							<label class="control-label text-muted">Válido até</label>
							<?= $this->Form->text('validoate', ['class' => 'form-control datepicker ', 'id' => 'validoate', 'default' => date('d/m/Y'), 'placeholder' => 'Insira a data', 'required' => true]) ?>
						</div>
					</div>
				</div>
				<div class="row m-t-10">
					<div class="col-12">
						<label class="control-label">Observação</label>
						<?= $this->Form->textarea('solicitacao', ['novalidate' => true, 'id' => 'observacoes', 'class' => 'editor form-control', 'label' => false]) ?>
					</div>
				</div>
				<h4 class='texte-center m-t-10'>Produtos e Serviços</h4>
				<div class="row m-t-10">
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
				<div class="row">
					<div class="col-12">
						<button class="btn btn-secondary float-right" id='btn-addservico'>Adicionar</button>
						<!-- botoes para edição -->
						<button class="btn btn-warning float-right mr-2" id='btn-editarservico' style="display: none;">Atualizar</button>
						<button class="btn btn-secondary float-right mr-2" id='btn-cancelaredicao' style="display: none;">Cancelar</button>
					</div>
				</div>
				<div id="carrinho" class='m-t-10'> </div>
				<div class="row">
					<div class="col-12">
						<?= $this->Form->button('Gerar Orçamento', ['class' => 'btn btn-success btn-orc-premium-primary']) ?>
					</div>
				</div>
			<?= $this->Form->end(); ?>
		</div>
	</div>
</div>

<script>
	// Carrinho
		carrinho();
		function carrinho(){
			$.ajax({
				type: "POST",
				url: "<?= Router::url(['controller'=>'Orcamentos','action'=>'carrinho', $idcarrinho]);?>",
				dataType: "html",
				success : function(data) {
					$("#carrinho").html(data);
					$("#carrinho").fadeIn();
				},
				error : function(error) {
					alert(error);
				}
			});
		}
	// Só número
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
					type:"post",
					url: "<?= Router::url(['controller'=>'Produtos','action'=>'produto']);?>" + '/' + $(this).val(),
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
								error: function() {
									$('.qtdEstoque').text('Estoque: indisponível').show();
								}
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
			} else {
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
		$('#quantidade, #valoruni, #idproduto, #valormensal').keyup(function(e){
			if( $('#quantidade').val().indexOf(':') > -1  ) {
				quantidadeArray = $('#quantidade').val().split(':');
				quantidade =( parseFloat(quantidadeArray[0]) + ( parseFloat(quantidadeArray[1]) / 6 / 10 )).toFixed(2);
			}else quantidade = $('#quantidade').val().replaceAll('.', '').replaceAll(',', '.')

			
			valoruni = $('#valoruni').val().replaceAll('.', '').replaceAll(',', '.')
			valormensal = $('#valormensal').val().replaceAll('.', '').replaceAll(',', '.')
			valor = 0;
			if(valoruni != '') valor = valoruni;
			//else valor = valormensal;
			if(quantidade > 0 && valor > 0){
				valortotal = quantidade * valor;
				$('#valordoservico').val(numberToReal(valortotal));
			}
			else $('#valordoservico').val('');
		});

	// Add serviço
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

			var url = "<?= Router::url(['controller'=>'Orcamentos','action'=>'addservico']);?>";
			$.ajax({
				url: url,
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

	// Double submit
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

	var editando = false;

	// Função para preencher formulário com dados do item
	function preencherFormularioEdicao(dados) {
		$('#servico').val(dados.servico);
		$('#quantidade').val(dados.quantidade);
		$('#valoruni').val(numberToReal(dados.valoruni));
		$('#observacao').val(dados.observacao);
		$('#valormensal').val(numberToReal(dados.valormensal));
		$('#idproduto').val(dados.idproduto);
		$('#tipo').val(dados.tipo);
		$('#item_edit_id').val(dados.id);
		
		// Aplicar máscara baseada no tipo
		if(dados.tipo == 1) {
			$('#quantidade').mask('99:99');
		} else {
			$('#quantidade').mask('0000000');
		}
		
		// Calcular valor total
		calcularValorTotal();
		
		// Atualizar selects
		$('#idproduto').selectpicker('refresh');
	}

	// Função para limpar formulário
	function limparFormularioEdicao() {
		$('#servico').val('');
		$('#quantidade').val('');
		$('#valoruni').val('');
		$('#observacao').val('');
		$('#valormensal').val('');
		$('#idproduto').val(0);
		$('#tipo').val(0);
		$('#valordoservico').val('');
		$('#item_edit_id').val('');
		
		$('#idproduto').selectpicker('refresh');
		$('#valoruni').prop('disabled', false);
		$('#valormensal').prop('disabled', false);
		$('.qtdEstoque').hide();
		
		// Resetar máscara
		$('#quantidade').mask('0000000');
	}

	// Alternar entre modo adição e edição
	function toggleModoEdicao(modo) {
		editando = modo;
		if (modo) {
			$('#btn-addservico').hide();
			$('#btn-editarservico').show();
			$('#btn-cancelaredicao').show();
			$('h3.text-center').text('Editando Item do Orçamento');
		} else {
			$('#btn-addservico').show();
			$('#btn-editarservico').hide();
			$('#btn-cancelaredicao').hide();
			$('h3.text-center').text('Proposta de Orçamento');
			limparFormularioEdicao();
		}
	}

	// Função para calcular valor total
	function calcularValorTotal() {
		var quantidade = $('#quantidade').val();
		var valoruni = $('#valoruni').val();
		var valormensal = $('#valormensal').val();
		
		if(quantidade.indexOf(':') > -1) {
			quantidadeArray = quantidade.split(':');
			quantidade = (parseFloat(quantidadeArray[0]) + (parseFloat(quantidadeArray[1]) / 6 / 10)).toFixed(2);
		} else {
			quantidade = quantidade.replace(/\./g, '').replace(',', '.');
		}
		
		valoruni = valoruni.replace(/\./g, '').replace(',', '.');
		valormensal = valormensal.replace(/\./g, '').replace(',', '.');
		
		var valor = 0;
		if(valoruni != '' && parseFloat(valoruni) > 0) {
			valor = parseFloat(valoruni);
		} else if(valormensal != '' && parseFloat(valormensal) > 0) {
			valor = parseFloat(valormensal);
		}
		
		if(quantidade > 0 && valor > 0){
			var valortotal = parseFloat(quantidade) * valor;
			$('#valordoservico').val(numberToReal(valortotal));
		} else {
			$('#valordoservico').val('');
		}
	}

	// Evento para editar item (deve ser adicionado após o carregamento do carrinho)
	$(document).on('click', '.editaitemcarrinho', function(e) {
		e.preventDefault();
		
		var dados = {
			id: $(this).data('id'),
			servico: $(this).data('servico'),
			quantidade: $(this).data('quantidade'),
			valoruni: $(this).data('valoruni'),
			observacao: $(this).data('observacao'),
			valormensal: $(this).data('valormensal'),
			idproduto: $(this).data('idproduto'),
			tipo: $(this).data('tipo')
		};
		
		console.log('Editando item:', dados); // Para debug
		
		preencherFormularioEdicao(dados);
		toggleModoEdicao(true);
		
		// Scroll para formulário
		$('html, body').animate({
			scrollTop: $('.card-body').offset().top
		}, 500);
	});

	// Evento para atualizar item
	$('#btn-editarservico').click(function(e) {
		e.preventDefault();
		
		var id = $('#item_edit_id').val();
		var servico = $('#servico').val();
		var quantidade = $('#quantidade').val();
		var valoruni = $('#valoruni').val();
		var valordoservico = $('#valordoservico').val();
		var observacao = $('#observacao').val();
		var valormensal = $('#valormensal').val();
		var idproduto = $('#idproduto').val();
		var tipo = $('#tipo').val();

		// Validações
		if (servico == '') {
			bootbox.alert('Preencha o campo "Descrição".');
			return false;
		}

		if (quantidade == '' || (valoruni == '' && valormensal == '')) {
			bootbox.alert('Preencha o campo "Quantidade" e o campo de valor respectivo.');
			return false;
		}

		var url = "<?= Router::url(['controller'=>'Orcamentos','action'=>'editaitemcarrinho']);?>";
		$.ajax({
			url: url,
			dataType: "html",
			type: 'POST',
			data: { 
				id: id,
				servico: servico, 
				quantidade: quantidade, 
				valoruni: valoruni, 
				valordoservico: valordoservico, 
				observacao: observacao, 
				valormensal: valormensal, 
				idproduto: idproduto, 
				tipo: tipo 
			},
			success: function(data) {
				console.log('Resposta:', data); // Para debug
				if (data == 'success') {
					carrinho();
					toggleModoEdicao(false);
					bootbox.alert('Item atualizado com sucesso!');
				} else {
					bootbox.alert('Erro ao atualizar item.');
				}
			},
			error: function(error) {
				console.log('Erro:', error); // Para debug
				bootbox.alert('Erro ao atualizar item: ' + error);
			}
		});
	});

	// Evento para cancelar edição
	$('#btn-cancelaredicao').click(function(e) {
		e.preventDefault();
		toggleModoEdicao(false);
	});

	// Atualizar cálculo quando campos mudarem
	$('#quantidade, #valoruni, #valormensal').on('keyup change', function() {
		calcularValorTotal();
	});

</script>