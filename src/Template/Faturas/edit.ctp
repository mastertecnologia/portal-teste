<?php
  	use Cake\Routing\Router;
	$this->Breadcrumbs->add('Locações', ['controller' => 'Faturas', 'action' => 'index'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add("Locação $fatura->nro", [], ['class' => 'breadcrumb-item active']);
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
			<?php if (!empty($prefatOsIds)) : ?>
			<div class="alert alert-info m-b-15" role="status">
				<strong>Pré-faturamento:</strong> OS vinculadas —
				<?php
				$links = [];
				foreach ($prefatOsIds as $oid) {
					$links[] = $this->Html->link('#' . (int)$oid, ['controller' => 'Ordensservico', 'action' => 'edit', (int)$oid], ['target' => '_blank', 'rel' => 'noopener noreferrer']);
				}
				echo implode(', ', $links);
				?>
			</div>
			<?php endif; ?>
			<ul class="nav nav-tabs customtab" role="tablist">
				<li class="nav-item"> <a class="nav-link <?= $aba == 1 ? 'active' : '' ?>" data-toggle="tab" href="#contratos" role="tab" aria-selected="true"><span class="hidden-sm-up"></span> <span class="hidden-xs-down"> Contrato </span></a> </li>
				<li class="nav-item"> <a class="nav-link <?= $aba == 2 ? 'active' : '' ?>" data-toggle="tab" href="#recibos" role="tab" aria-selected="false"><span class="hidden-sm-up"></span> <span class="hidden-xs-down"> Recibos </span></a> </li>
			</ul>
			<div class="tab-content">
				<div class="tab-pane <?= $aba == 1 ? 'active' : '' ?>" id="contratos">
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
							<div class="col-xl-2 col-lg-3 com-md-4 col-sm-6 col-xs-12">
								<label class="control-label"> Status: </label> <br>
								<?= LocacaoStatus($fatura->status) ?>
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
						<div class="row">
							<div class="col-12">
								<?= $this->Form->button('Salvar', ['class' => 'btn btn-pgm btn-pgm-salvar btn-success float-right m-t-10']) ?>
							</div>
						</div>
						<?php if($fatura->status == C_LocacaoStatusPendente) { ?>
							<h4 class='texte-center m-t-10'> Itens </h4>
							<div class="row m-t-10">
								<div class="col-xl-1 col-lg-2 com-md-4 col-sm-6 col-xs-12">
									<label class="control-label text-muted"> Código </label>
									<?= $this->Form->control('codigo', ['class' => 'form-control selectpicker', 'data-live-search', 'options' => $produtosOpt, 'label' => false]) ?>
								</div>
								<div class="col-xl-5 col-lg-5 com-md-4 col-sm-6 col-xs-12">
									<label class="control-label text-muted"> Descrição da Locação </label>
									<?= $this->Form->control('descricao', ['class' => 'descricao form-control', 'label' => false]) ?>
								</div>
								<div class="col-xl-1 col-lg-1 com-md-4 col-sm-6 col-xs-12">
									<div class="form-group">
										<label class="control-label text-muted"> Quantidade </label>
										<?= $this->Form->control('quantidade', ['onkeypress' => 'return SomenteNumero(event, "#quantidade")', 'class' => 'quantidade form-control', 'label' => false]) ?>
									</div>
								</div>
								<div class="col-xl-2 col-lg-2 com-md-4 col-sm-6 col-xs-12">
									<div class="form-group">
										<label class="control-label text-muted"> Valor Unitário </label>
										<?= $this->Form->control('valoritem', ['onkeypress' => 'return SomenteNumero(event, "#valoritem")', 'class' => 'form-control mascaramonetaria', 'label' => false]) ?>
									</div>
								</div>
								<div class="col-xl-2 col-lg-2 com-md-4 col-sm-6 col-xs-12">
									<div class="form-group">
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
						<?php } ?>
						<div id="carrinho" class='m-t-10'> </div>
						<div class="row">
							<div class="col-12">
								<?= $this->Html->link('Imprimir', ['action' => 'imprimir', $fatura->id], ['target' => '_blank', 'class' => 'btn btn-pgm btn-pgm-imprimir btn-orange float-right m-t-10']) ?>
								<?= $this->Html->link('Compartilhar', ['action' => 'view', $fatura->hash], ['class' => 'btn btn-pgm btn-pgm-email btn-purple btn-compartilhar float-right m-t-10 m-r-5']) ?>
								<?php
									if($fatura->status == C_LocacaoStatusPendente) {
										echo $this->Html->link('Aprovar', ['action' => 'aprovar', $fatura->id], ['id' => 'btn-aprovar', 'class' => 'btn btn-pgm btn-pgm-salvar btn-success float-right m-t-10 m-r-5']);
										echo $this->Html->link('Rejeitar', ['action' => 'rejeitar', $fatura->id], ['id' => 'btn-rejeitar', 'class' => 'btn btn-danger float-right m-t-10 m-r-5']);
									}
									if($fatura->status == C_LocacaoStatusAprovado) {
										echo $this->Html->link('Receber', ['action' => 'receber', $fatura->id], ['target' => '_blank', 'id' => 'btn-receber', 'class' => 'btn btn-pgm btn-pgm-situacao btn-info float-right m-t-10 m-r-5']);
									}
								?>
							</div>
						</div>
					<?= $this->Form->end(); ?>
				</div>
				<div class="tab-pane <?= $aba == 2 ? 'active' : '' ?>" id="recibos">
					<table class="table table-hover table-row-clickable" id="tableRecibos">
						<thead class="text-primary">
							<th> Número </th>
							<th> Pagamento </th>
							<th> Data Recebimento </th>
							<th> Descontos </th>
							<th> Juros </th>
							<th> Valor Pago </th>
							<th> Ações </th>
						</thead>
						<tbody>
							<?php foreach ($recibos as $reg): ?>
								<tr>
									<td> <?= $reg->nro ?></a> </td>
									<td> <?= OrdensPagamento($reg->pagamento) ?> </td>
									<td data-order="<?= date_format($reg->datarecebimento, 'Ymd') ?>"> <?= date_format($reg->datarecebimento, 'd/m/Y') ?> </td>
									<td> <?= number_format($reg->desconto, 2, ',', '.') ?> </td>
									<td> <?= number_format($reg->juros, 2, ',', '.') ?> </td>
									<td> <?= number_format($reg->valorpago, 2, ',', '.') ?> </td>
									<td> 
										<?= $this->Html->link('<i class="fa fa-print"></i>', ['action' => 'recibo', $reg->id], ['rel' => 'tooltip', 'title' => 'Imprimir', 'id' => $reg->id, 'class' => 'btn btn-pgm btn-pgm-imprimir btn-orange btn-xs', 'target' => '_blank', 'escape' => false]) ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
	// Carrinho 
		carrinho();
		function carrinho(){
			$.ajax({
				type: "POST",
				url: "<?= Router::url(['controller' => 'Faturas', 'action' => 'carrinhoedit', $fatura->id]);?>",
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
	// Cálculos 
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
				url: "<?= Router::url(['controller' => 'Faturas', 'action' => 'additem', $fatura->id]);?>",
				dataType: "html",
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
					alert(error.msg);
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
	// Btns 
		$('.btn-compartilhar').click(function(e) {
			e.preventDefault()
			navigator.clipboard.writeText('<?= $linkCompartilhar ?>')
			.then(function() {
				console.log("Texto copiado com sucesso!");
			})
			.catch(function(err) {
				console.error("Erro ao copiar o texto: ", err);
			});
		})

		$('#btn-aprovar, #btn-rejeitar').click(function(e) {
			e.preventDefault();
			var href = $(this).attr('href');
			bootbox.confirm({
				message: 'Confirmar a ação?',
				buttons: {
					confirm: {
						label: 'Sim',
						className: 'btn btn-pgm btn-pgm-salvar btn-success'
					},
					cancel: {
						label: 'Não',
						className: 'btn-danger'
					}
				},
				callback: function (result) {
					if(result == true) {
						window.location = href;
					}
				}
			});
		});
	// Recibos 
		$(document).ready(function() {
			var $window = $(window);
			table = $('#tableRecibos');
			table.on( 'length.dt', function ( e, settings, len ) { pagelength(len);	});
			table.DataTable({
				"order": [[ 0, "ASC" ]],
				"pageLength": <?= $pagelength ?>,
				"language": {
					"sProcessing":    "Procesando...",
					"sLengthMenu":    "Mostrar _MENU_ registros",
					"sZeroRecords":   "Nenhum registro encontrado",
					"sEmptyTable":    "Nenhum dado disponível",
					"sInfo":          "Mostrando registros de _START_ até _END_ de um total de _TOTAL_ registros",
					"sInfoEmpty":     "Mostrando registros de 0 a 0 de um total de 0 registros",
					"sInfoFiltered":  "(filtrado de um total de _MAX_ registros)",
					"sInfoPostFix":   "",
					"sSearch":        "Buscar:",
					"sUrl":           "",
					"sInfoThousands":  ",",
					"sLoadingRecords": "Carregando...",
					"oPaginate": {
						"sFirst":    "<<",
						"sLast":    ">>",
						"sNext":    ">",
						"sPrevious": "<"
					},
					"oAria": {
						"sSortAscending":  ": Ordem Ascendente",
						"sSortDescending": ": Ordem descendente"
					},
				}
			});
		});
	// 
</script>