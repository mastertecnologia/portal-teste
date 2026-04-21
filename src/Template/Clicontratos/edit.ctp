<?php
	use Cake\Routing\Router;

	$this->append('css', $this->element('pgm_premium_css', ['name' => 'clientes-premium']));
	$this->append('css', $this->element('pgm_premium_css', ['name' => 'clientes-layout-unificado']));
	$this->Breadcrumbs->add('Cliente', ['controller' => 'Clientes', 'action' => 'edit', $idcliente], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Alterar contrato', [], ['class' => 'breadcrumb-item active']);
	$urlFichaCliente = Router::url(['controller' => 'Clientes', 'action' => 'edit', $idcliente]) . '#contratos';
?>
<div class="col-md-12 p-0 clictr-cli-page">
	<div class="cli-form-root cli-layout-unificado">
		<?= $this->Form->create($contrato, ['class' => 'form-material', 'id' => 'form-clicontrato-edit']) ?>
		<div class="cli-form-body cli-form-body--cadastro-lead">
			<div class="d-flex justify-content-end flex-wrap mb-2">
				<?= $this->Html->link(
					'<i class="fas fa-arrow-left"></i> Voltar à ficha (contratos)',
					$urlFichaCliente,
					['class' => 'btn btn-sm btn-cli-outline', 'escape' => false, 'data-turbo' => 'false']
				) ?>
			</div>

			<div class="cli-section">
				<div class="cli-section-head">
					<div class="cli-section-icon"><i class="fas fa-file-contract" aria-hidden="true"></i></div>
					<div class="cli-section-title">Identificação do item</div>
				</div>
				<div class="cli-section-body">
					<div class="cli-fg cli-fg-3-2">
						<div class="cli-fgroup">
							<label for="codproduto">Código</label>
							<?= $this->Form->control('codproduto', ['class' => 'form-control selectpicker', 'data-live-search' => true, 'options' => $produtos, 'label' => false]) ?>
						</div>
						<div class="cli-fgroup">
							<label for="descricao">Descrição</label>
							<?= $this->Form->control('descricao', ['class' => 'form-control', 'label' => false]) ?>
						</div>
					</div>
					<div class="cli-fg cli-fg-1">
						<div class="cli-fgroup">
							<label for="infadicional">Informação adicional</label>
							<?= $this->Form->control('infadicional', ['class' => 'form-control', 'label' => false]) ?>
						</div>
					</div>
				</div>
			</div>

			<div class="cli-section">
				<div class="cli-section-head">
					<div class="cli-section-icon"><i class="fas fa-calendar-alt" aria-hidden="true"></i></div>
					<div class="cli-section-title">Datas</div>
				</div>
				<div class="cli-section-body">
					<div class="cli-fg cli-fg-3">
						<div class="cli-fgroup">
							<label for="dtcontratacao">Data de inclusão</label>
							<?= $this->Form->text('dtcontratacao', ['class' => 'mensal form-control datepicker', 'label' => false, 'id' => 'dtcontratacao']) ?>
						</div>
						<div class="cli-fgroup">
							<label for="dtvalidade">Data de validade</label>
							<?= $this->Form->text('dtvalidade', ['class' => 'mensal form-control datepicker', 'label' => false, 'id' => 'dtvalidade']) ?>
						</div>
						<div class="cli-fgroup">
							<label for="dtcancelamento">Data de cancelamento</label>
							<?= $this->Form->text('dtcancelamento', ['class' => 'mensal form-control datepicker', 'label' => false, 'id' => 'dtcancelamento']) ?>
						</div>
					</div>
				</div>
			</div>

			<div class="cli-section">
				<div class="cli-section-head">
					<div class="cli-section-icon"><i class="fas fa-calculator" aria-hidden="true"></i></div>
					<div class="cli-section-title">Quantidade e valores</div>
				</div>
				<div class="cli-section-body">
					<div class="cli-fg cli-fg-3">
						<div class="cli-fgroup">
							<label for="qtde">Qtde.</label>
							<?= $this->Form->control('qtde', ['onkeypress' => 'return SomenteNumero(event, "#qtde")', 'class' => 'qtde form-control', 'label' => false]) ?>
						</div>
						<div class="cli-fgroup">
							<label for="vlunit">Vl. unitário</label>
							<?= $this->Form->text('vlunit', ['id' => 'vlunit', 'class' => 'form-control mascaramonetaria', 'label' => false]) ?>
						</div>
						<div class="cli-fgroup">
							<label for="vltotal">Vl. total</label>
							<?= $this->Form->text('vltotal', ['id' => 'vltotal', 'class' => 'form-control', 'label' => false, 'readonly' => true]) ?>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="cli-form-footer">
			<div class="cli-form-footer-left text-muted small">
				Item #<?= (int)$contrato->id ?>
			</div>
			<div class="cli-form-footer-right">
				<?= $this->Form->control('idcliente', ['value' => $idcliente, 'type' => 'hidden', 'label' => false]) ?>
				<?= $this->Form->button('<i class="fas fa-check"></i> Salvar', ['class' => 'btn-cli-primary', 'escape' => false, 'type' => 'submit']) ?>
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

		$('#form-clicontrato-edit').preventDoubleSubmission();
	//
</script>
