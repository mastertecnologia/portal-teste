<?php
  	use Cake\Routing\Router;
	$this->Breadcrumbs->add('Locações', ['controller' => 'Faturas', 'action' => 'index'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add("Locação $fatura->nro", ['controller' => 'Faturas', 'action' => 'edit', $fatura->id], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Receber', [], ['class' => 'breadcrumb-item active']);
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
			<?= $this->Form->create($recibo, ['class' => 'form-material']); ?> 
				<div class="row">
					<div class="col-xl-2 col-lg-3 com-md-4 col-sm-6 col-xs-12">
						<label class="control-label"> Contrato: </label>
						<?= $this->Form->control('contrato', ['class' => 'form-control', 'value' => $fatura->nro, 'label' => false, 'required' => true, 'readonly' => true]) ?>
					</div>
					<div class="col-xl-2 col-lg-2 com-md-3 col-sm-6 col-xs-12">
						<label class="control-label"> Forma de Pagamento </label>
						<?= $this->Form->control('pagamento', ['class' => 'form-control', 'label' => false, 'options' => C_OrdensPagamento, 'value' => $fatura->pagamento]) ?>
					</div>
				</div>
				<div class="row m-t-5">
					<div class="col-xl-2 col-lg-2 com-md-3 col-sm-6 col-xs-12">
						<div class="form-group">
							<label class="control-label text-muted"> Data recebimento </label>
							<?= $this->Form->text('datarecebimento', ['class' => 'form-control datepicker ', 'id' => 'dtemissao', 'default' => date('d/m/Y'), 'placeholder' => 'Insira a data', 'required' => true]) ?>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-2 col-sm-6">
						<div class="form-group ">
							<label class="control-label text-muted"> Valor da fatura: </label>
							<?= $this->Form->text('valorfatura', ['class' => 'form-control mascaramonetaria', 'id' => 'valorfatura', 'label' => false, 'value' => $fatura->valor, 'disabled']) ?>
						</div>
					</div>
					<div class="col-lg-2 col-sm-6">
						<div class="form-group ">
							<label class="control-label text-muted"> Desconto </label>
							<?= $this->Form->text('desconto', ['onkeypress' => 'return SomenteNumero(event, "#desconto")', 'id' => 'desconto', 'class' => 'form-control mascaramonetaria', 'value' => '00,00', 'label' => false]) ?>
						</div>
					</div>
					<div class="col-lg-2 col-sm-6">
						<div class="form-group ">
							<label class="control-label text-muted"> Juros </label>
							<?= $this->Form->text('juros', ['onkeypress' => 'return SomenteNumero(event, "#juros")', 'id' => 'juros', 'class' => 'form-control mascaramonetaria', 'value' => '00,00', 'label' => false]) ?>
						</div>
					</div>
					<div class="col-lg-2 col-sm-6">
						<div class="form-group ">
							<label class="control-label text-muted"> Valor pago </label>
							<?= $this->Form->text('valorpago', ['onkeypress' => 'return SomenteNumero(event, "#valorpago")', 'id' => 'valorpago', 'class' => 'form-control mascaramonetaria', 'label' => false]) ?>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-12">
						<?= $this->Form->button('Receber', ['class' => 'btn btn-success float-right m-t-10']) ?>
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
	// Cálculo valor total 
		$('#desconto, #juros').change(function(e){
			if($('#desconto').val() === "") $('#desconto').val('0,00');
			if($('#juros').val() === "") $('#juros').val('0,00');

			valorFatura = $('#valorfatura').val().replaceAll('.', '').replaceAll(',', '.');
			desconto = $('#desconto').val().replaceAll('.', '').replaceAll(',', '.');
			juros = $('#juros').val().replaceAll('.', '').replaceAll(',', '.');

			valor = parseFloat(valorFatura) + parseFloat(juros) - parseFloat(desconto);

			$('#valorpago').val(numberToReal(valor));
		});
	// 
</script>