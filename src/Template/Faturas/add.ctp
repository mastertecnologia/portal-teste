<?php
  	use Cake\Routing\Router;
	$this->Breadcrumbs->add('Locações', ['controller' => 'Faturas', 'action' => 'index'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Nova locação', [], ['class' => 'breadcrumb-item active']);
?>
<style>
	.dtp table.dtp-picker-days tr > td{
		font-weight: 700	 !important;
		font-size: 0.8em	 !important;
		text-align: center	 !important;
		padding: 0.5em 0.3em !important;
	}
</style>
<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<?= $this->Form->create($fatura, ['class' => 'form-material']); ?> 
				<div class="row">
					<div class="col-xl-6 col-lg-6 com-md-6 col-sm-12 col-xs-12">
						<label class="control-label"> Cliente </label>
						<?= $this->Form->control('idcliente', ['class' => 'form-control selectpicker', 'data-live-search' => true, 'options' => $clientes, 'title' => 'Selecione um cliente', 'label' => false, 'required' => true]) ?>
					</div>
					<div class="col-xl-2 col-lg-3 com-md-4 col-sm-6 col-xs-12">
						<label class="control-label"> Contrato: </label>
						<?= $this->Form->control('contrato', ['class' => 'form-control', 'value' => $fatura->nro, 'label' => false, 'required' => true, 'readonly' => true]) ?>
					</div>
					<div class="col-xl-2 col-lg-2 com-md-3 col-sm-6 col-xs-12">
						<label class="control-label"> Forma de Pagamento </label>
						<?= $this->Form->control('pagamento', ['class' => 'form-control', 'label' => false, 'options' => C_OrdensPagamento]) ?>
					</div>
				</div>
				<div class="row m-t-5">
					<div class="col-xl-2 col-lg-2 com-md-3 col-sm-6 col-xs-12">
						<div class="form-group">
							<label class="control-label text-muted"> Data emissão </label>
							<?= $this->Form->text('dtemissao', ['class' => 'form-control datepicker ', 'id' => 'dtemissao', 'default' => date('d/m/Y'), 'placeholder' => 'Insira a data', 'required' => true]) ?>
						</div>
					</div>
					<div class="col-xl-2 col-lg-2 com-md-3 col-sm-6 col-xs-12">
						<div class="form-group">
							<label class="control-label text-muted"> Previsão retorno </label>
							<?= $this->Form->text('dtretorno', ['class' => 'form-control datepicker ', 'id' => 'dtretorno', 'default' => date('d/m/Y'), 'placeholder' => 'Insira a data', 'required' => true]) ?>
						</div>
					</div>
					<div class="col-xl-2 col-lg-2 com-md-3 col-sm-6 col-xs-12">
						<div class="form-group">
							<label class="control-label text-muted"> Data devolução </label>
							<?= $this->Form->text('dtdevolucao', ['class' => 'form-control datepicker ', 'id' => 'dtdevolucao', 'default' => date('d/m/Y'), 'placeholder' => 'Insira a data', 'required' => true]) ?>
						</div>
					</div>
					<div class="col-xl-2 col-lg-2 com-md-3 col-sm-6 col-xs-12">
						<div class="form-group">
							<label class="control-label text-muted"> Data vencimento </label>
							<?= $this->Form->text('vencimento', ['class' => 'form-control datepicker ', 'id' => 'vencimento', 'default' => date('d/m/Y'), 'placeholder' => 'Insira a data', 'required' => true]) ?>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-xl-3 col-lg-3 com-md-4 col-sm-6 col-xs-12">
						<label class="control-label"> Equipamento(s) instalado(s) em: </label>
						<?= $this->Form->control('local', ['data-live-search' => 'true', 'class' => 'selectpicker form-control', 'options' => $cidades, 'label' => false]) ?>
					</div>
					<div class="col-xl-2 col-lg-3 com-md-4 col-sm-6 col-xs-12">
						<label class="control-label"> Referente: </label>
						<?= $this->Form->control('referente', ['class' => 'form-control', 'label' => false, 'required' => true]) ?>
					</div>
					<div class="col-xl-2 col-lg-3 com-md-4 col-sm-6 col-xs-12">
						<label class="control-label"> Tipo: </label>
						<?= $this->Form->control('tipo', ['class' => 'form-control', 'label' => false, 'required' => true, 'options' => C_LocacaoTipoArray]) ?>
					</div>
				</div>
				<hr>
				<div class="row">
					<div class="col-lg-2 col-sm-6">
						<div class="form-group ">
							<label class="control-label text-muted"> Desconto </label>
							<?= $this->Form->text('desconto', ['onkeypress' => 'return SomenteNumero(event, "#desconto")', 'id' => 'desconto', 'class' => 'form-control mascaramonetaria', 'label' => false]) ?>
						</div>
					</div>
					<div class="col-lg-2 col-sm-6">
						<div class="form-group ">
							<label class="control-label text-muted"> Outros gastos/frete </label>
							<?= $this->Form->text('outrosgastos', ['onkeypress' => 'return SomenteNumero(event, "#outrosgastos")', 'id' => 'outrosgastos', 'class' => 'form-control mascaramonetaria', 'label' => false]) ?>
						</div>
					</div>
				</div>
				<h4 class='texte-center m-t-10'> Itens </h4>
				<div class="row m-t-10">
					<div class="col-lg-2 col-md-12">
						<label class="control-label text-muted"> Código </label>
						<?= $this->Form->control('codigo', ['class' => 'form-control selectpicker', 'data-live-search', 'options' => $produtosOpt, 'label' => false]) ?>
					</div>
					<div class="col-lg-5 col-md-12">
						<label class="control-label text-muted"> Descrição da Locação </label>
						<?= $this->Form->control('descricao', ['class' => 'descricao form-control', 'label' => false]) ?>
					</div>
					<div class="col-lg-1 col-md-6">
						<div class="form-group ">
							<label class="control-label text-muted"> Quantidade </label>
							<?= $this->Form->control('quantidade', ['onkeypress' => 'return SomenteNumero(event, "#quantidade")', 'class' => 'quantidade form-control', 'label' => false]) ?>
						</div>
					</div>
					<div class="col-lg-1 col-md-6">
						<div class="form-group ">
							<label class="control-label text-muted"> Valor Unitário </label>
							<?= $this->Form->control('valoritem', ['onkeypress' => 'return SomenteNumero(event, "#valoritem")', 'class' => 'form-control mascaramonetaria', 'label' => false]) ?>
						</div>
					</div>
					<div class="col-lg-2 col-md-12">
						<div class="form-group ">
							<label class="control-label text-muted"> Valor Total </label>
							<?= $this->Form->control('valortotal', ['onkeypress' => 'return SomenteNumero(event, "#valortotal")', 'class' => 'form-control mascaramonetaria', 'label' => false]) ?>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-12">
						<button class="btn btn-secondary float-right" id='btn-additem'> Adicionar </button>
					</div>
				</div>
				<div id="carrinho" class='m-t-10'> </div>
				<div class="row">
					<div class="col-12">
						<?= $this->Form->button('Gerar Fatura', ['class' => 'btn btn-pgm btn-pgm-salvar btn-success float-right m-t-10']) ?>
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
				url: "<?= Router::url(['controller' => 'Faturas', 'action' => 'carrinho', $idcarrinho]);?>",
				dataType: "html",
				success : function(data) {
					$("#carrinho").html(data);
					$("#carrinho").fadeIn();
				},
				error : function(error) { (error); }
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
		$('#codigo').change(function(e){
			if( $(this).val() != 0){
				$.ajax({
					type:"post",
					url: "<?= Router::url(['controller' => 'Produtos', 'action' => 'produto']);?>" + '/' + $(this).val(),
					dataType: "json",
					success: function(data){
						$('#quantidade').val('');
						$('#descricao').val(data.descricao.trim());
						if($('#tipo').val() == <?= C_LocacaoTipoDiaria ?>) $('#valoritem').val(numberToReal(data.vllocdiario));
						if($('#tipo').val() == <?= C_LocacaoTipoSemanal ?>) $('#valoritem').val(numberToReal(data.vllocsemanal));
						if($('#tipo').val() == <?= C_LocacaoTipoQuinzenal ?>) $('#valoritem').val(numberToReal(data.vllocquinzenal));
						if($('#tipo').val() == <?= C_LocacaoTipoMensal ?>) $('#valoritem').val(numberToReal(data.vllocmensal));
					},
					error: function (error) { console.log(error); }
				});
			} else {
				$('#quantidade').val('');
				$('#descricao').val('');
				$('#valoritem').val('');
				$('#valortotal').val('');
			}
		});
	// Cálculo valor total 
		$('#quantidade, #valoritem').change(function(e){
			valoritem = $('#valoritem').val().replaceAll('.', '').replaceAll(',', '.')
			quantidade = $('#quantidade').val()
			if(quantidade > 0 && valoritem){
				valortotal = quantidade * valoritem;
				$('#valortotal').val(numberToReal(valortotal));
			}
			else $('#valortotal').val('');
		});
	// Add item 
		$('#btn-additem').click(function(e){
			e.preventDefault();
			codigo =       	$('#codigo').val();
			descricao = 	$('#descricao').val();
			quantidade =	$('#quantidade').val();
			valoritem = 	$('#valoritem').val();
			valortotal = 	$('#valortotal').val();

			if(descricao == ''){
				bootbox.alert('Preencha o campo "Descrição".');
				return false;
			}

			if(quantidade == '' || (valoritem == '')){
				bootbox.alert('Preencha o campo "Quantidade" e o campo "Valor Unitário".');
				return false;
			}

			$.ajax({
				url: "<?= Router::url(['controller' => 'Faturas', 'action' => 'additem']);?>",
				dataType: "JSON",
				type: 'POST',
				data: { codigo: codigo, descricao: descricao, quantidade: quantidade, valoritem: valoritem, valortotal: valortotal},
				success : function(data) {
					carrinho();
					$('#codigo').val('');
					$('#descricao').val('');
					$('#quantidade').val('');
					$('#valoritem').val('');
					$('#valortotal').val('');
				},
				error : function(error) {
					alert(error.responseJSON.msg);
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
</script>