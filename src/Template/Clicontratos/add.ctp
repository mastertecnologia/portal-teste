<?php
	use Cake\Routing\Router;
	$this->Breadcrumbs->add('Cliente', ['controller' => 'Clientes', 'action' => 'edit', $idcliente], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Cadastrar contrato', [], ['class' => 'breadcrumb-item active']);
?>
<?= $this->element('Pgm/form_shell_dark', ['formId' => 'form-clicontrato-add']) ?>
<div class="col-md-12 clictr-edit-page">
	<div class="clictr-card">
			<?= $this->Form->create($contrato, ['class' => 'form-material clictr-form', 'id' => 'form-clicontrato-add']) ?>
				<div class="clictr-section">
					<div class="clictr-section-title">Identificação do item</div>
					<div class="row">
						<div class="col-lg-4 col-md-12">
							<label class="clictr-label" for="codproduto">Código</label>
							<?= $this->Form->control('codproduto', ['class' => 'form-control selectpicker', 'data-live-search' => true, 'options' => $produtos, 'value' => 0, 'label' => false]) ?>
						</div>
						<div class="col-lg-8 col-md-12">
							<label class="clictr-label" for="descricao">Descrição</label>
							<?= $this->Form->control('descricao', ['class' => 'form-control', 'label' => false]) ?>
						</div>
					</div>
					<div class="row">
						<div class="col-12">
							<label class="clictr-label" for="infadicional">Informação adicional</label>
							<?= $this->Form->control('infadicional', ['class' => 'form-control', 'label' => false]) ?>
						</div>
					</div>
				</div>
				<div class="clictr-section">
					<div class="clictr-section-title">Datas</div>
					<div class="row">
						<div class="col-md-4 col-sm-6">
							<label class="clictr-label" for="dtcontratacao">Data de inclusão</label>
							<?= $this->Form->text('dtcontratacao', ['value' => date('d/m/Y'), 'class' => 'mensal form-control datepicker', 'label' => false, 'id' => 'dtcontratacao']) ?>
						</div>
						<div class="col-md-4 col-sm-6">
							<label class="clictr-label" for="dtvalidade">Data de validade</label>
							<?= $this->Form->text('dtvalidade', ['class' => 'mensal form-control datepicker', 'label' => false, 'id' => 'dtvalidade']) ?>
						</div>
						<div class="col-md-4 col-sm-6">
							<label class="clictr-label" for="dtcancelamento">Data de cancelamento</label>
							<?= $this->Form->text('dtcancelamento', ['class' => 'mensal form-control datepicker', 'label' => false, 'id' => 'dtcancelamento']) ?>
						</div>
					</div>
				</div>
				<div class="clictr-section">
					<div class="clictr-section-title">Quantidade e valores</div>
					<div class="row">
						<div class="col-md-4 col-sm-6">
							<label class="clictr-label" for="qtde">Qtde.</label>
							<?= $this->Form->control('qtde', ['onkeypress' => 'return SomenteNumero(event, "#qtde")', 'class' => 'qtde form-control', 'label' => false]) ?>
						</div>
						<div class="col-md-4 col-sm-6">
							<label class="clictr-label" for="vlunit">Vl. unitário</label>
							<?= $this->Form->text('vlunit', ['id' => 'vlunit', 'class' => 'form-control mascaramonetaria', 'label' => false]) ?>
						</div>
						<div class="col-md-4 col-sm-6">
							<label class="clictr-label" for="vltotal">Vl. total</label>
							<?= $this->Form->text('vltotal', ['id' => 'vltotal', 'class' => 'form-control', 'label' => false, 'readonly' => true]) ?>
						</div>
					</div>
				</div>
				<div class="clictr-actions row">
					<div class="col-12">
						<?= $this->Form->control('idcliente', ['value' => $idcliente, 'type' => 'hidden', 'label' => false]) ?>
						<?= $this->Form->button('Cadastrar', ['class' => 'btn btn-pgm btn-pgm-salvar btn-success']) ?>
					</div>
				</div>
			<?= $this->Form->end() ?>
	</div>
</div>

<script>
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
		$('#codproduto').change(function(e){
			$.ajax({
				type:"post",
				url: "<?= Router::url(['controller'=>'Produtos','action'=>'produto']);?>" + '/' + $(this).val(),
				dataType: "json",
				success: function(data){
					$('#descricao').val(data.descricao.trim());
					$('#vlunit').val(numberToReal(data.vlunitario));
					$('#qtde').val('');
					$('#vltotal').val('');
				},
				error: function (error) { console.log(error); }
			});
		
		});
	// Cálculos
		$('#qtde, #vlunit, #idproduto').keyup(function(e){
			if( $('#qtde').val().indexOf(':') > -1  ) {
				qtdeArray = $('#qtde').val().split(':');
				qtde =( parseFloat(qtdeArray[0]) + ( parseFloat(qtdeArray[1]) / 6 / 10 )).toFixed(2);
			}else qtde = $('#qtde').val().replaceAll('.', '').replaceAll(',', '.')

			vlunit = $('#vlunit').val().replaceAll('.', '').replaceAll(',', '.')
			if(vlunit != '') valor = vlunit;
			if(qtde > 0 && valor){
				valortotal = qtde * valor;
				$('#vltotal').val(numberToReal(valortotal));
			}
			else $('#vltotal').val('');
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

		$('#form-clicontrato-add').preventDoubleSubmission();
	// 
</script>
