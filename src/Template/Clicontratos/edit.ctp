<?php
  	use Cake\Routing\Router;
	$this->Breadcrumbs->add('Cliente', ['controller' => 'Clientes', 'action' => 'edit', $idcliente], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Alterar contrato', [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<?= $this->Form->create($contrato, ['class' => 'form-material']);?> 
				<div class="row">
					<div class="col-md-3 col-xs-12">
						<label class="control-label">Código</label>
						<?= $this->Form->control('codproduto', ['class' => 'form-control selectpicker', 'data-live-search' => true, 'options' => $produtos, 'label' => false]) ?>
					</div>
					<div class="col-md-9 col-xs-12">
						<div class="form-group ">
							<label class="control-label text-muted">Descrição</label>
							<?= $this->Form->control('descricao', ['class' => 'form-control', 'label' => false]) ?>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-12">
						<div class="form-group ">
							<label class="control-label text-muted">Informação adicional</label>
							<?= $this->Form->control('infadicional', ['class' => 'form-control', 'label' => false]) ?>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-2 col-xs-6">
						<div class="form-group">
							<label class="control-label text-muted">Data de inclusão</label>
							<?= $this->Form->text('dtcontratacao', ['class' => 'mensal form-control datepicker', 'label' => false]) ?>
						</div>
					</div>
					<div class="col-md-2 col-xs-6">
						<div class="form-group">
							<label class="control-label text-muted">Data de validade</label>
							<?= $this->Form->text('dtvalidade', ['class' => 'mensal form-control datepicker', 'label' => false]) ?>
						</div>
					</div>
					<div class="col-md-2 col-xs-6">
						<div class="form-group">
							<label class="control-label text-muted">Data de cancelamento</label>
							<?= $this->Form->text('dtcancelamento', ['class' => 'mensal form-control datepicker', 'label' => false]) ?>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-2 col-xs-6">
						<div class="form-group ">
							<label class="control-label text-muted">Qtde.</label>
							<?= $this->Form->control('qtde', ['onkeypress' => 'return SomenteNumero(event, "#qtde")', 'class' => 'qtde form-control', 'label' => false]) ?>
						</div>
					</div>
					<div class="col-md-2 col-xs-6">
						<div class="form-group ">
							<label class="control-label text-muted">Vl. Unitário </label>
							<?= $this->Form->text('vlunit', ['id' => 'vlunit', 'class' => 'form-control mascaramonetaria', 'label' => false]) ?>
						</div>
					</div>
					<div class="col-md-2 col-xs-6">
						<div class="form-group ">
							<label class="control-label text-muted">Vl. Total</label>
							<?= $this->Form->text('vltotal', ['id' => 'vltotal', 'class' => 'form-control', 'label' => false, 'readonly' => true]) ?>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-12">
						<?= $this->Form->control('idcliente', ['value' => $idcliente, 'type' => 'hidden', 'label' => false]) ?>
						<?= $this->Form->button('Salvar', ['class' => 'btn btn-success']) ?>
					</div>
				</div>
			<?= $this->Form->end(); ?>
		</div>
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

		$('form').preventDoubleSubmission();
	// 
</script>