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
	/* ── Seções do formulário — cores transparentes p/ dark theme ── */
	.erp-section {
		border: 1px solid rgba(255,255,255,.1);
		border-radius: 4px;
		margin-bottom: 14px;
		overflow: hidden;
	}
	.erp-section-title {
		background: rgba(255,255,255,.06);
		border-bottom: 1px solid rgba(255,255,255,.08);
		padding: 6px 14px;
		font-size: 11px;
		font-weight: 700;
		text-transform: uppercase;
		letter-spacing: .6px;
		color: rgba(255,255,255,.55);
	}
	.erp-section-body {
		padding: 12px 14px 4px;
	}
	/* Linha de ação premium */
	.erp-action-bar {
		display: flex;
		align-items: center;
		justify-content: flex-end;
		flex-wrap: wrap;
		gap: 6px;
		padding: 10px 0 4px;
		border-top: 1px solid rgba(255,255,255,.1);
		margin-top: 8px;
	}
	/* Tabela de itens */
	#carrinho .table thead th { font-size: 12px; color: #26c6da; font-weight: 600; border-bottom: 2px solid #26c6da; }
	#carrinho .table td { font-size: 13px; vertical-align: middle; }
	/* Adicionar item */
	.erp-additem-bar {
		background: rgba(255,255,255,.04);
		border: 1px dashed rgba(255,255,255,.15);
		border-radius: 4px;
		padding: 10px 14px 6px;
		margin-bottom: 10px;
	}
	.erp-additem-bar .control-label { font-size: 11px; color: rgba(255,255,255,.45); }

	/* ── Card principal + tabs: fundo escuro do sistema ── */
	.pgm-adv-panel.card {
		background: #161b22 !important;
		border: 1px solid rgba(255,255,255,.08) !important;
		border-radius: 12px !important;
		box-shadow: 0 4px 24px rgba(0,0,0,.25) !important;
		color: #e6edf3 !important;
	}
	.pgm-adv-panel .card-body { background: transparent !important; color: #e6edf3 !important; }
	/* Tabs */
	.pgm-adv-panel .nav-tabs { border-color: rgba(255,255,255,.1) !important; background: transparent !important; }
	.pgm-adv-panel .nav-tabs .nav-link { color: rgba(230,237,243,.55) !important; background: transparent !important; border-color: transparent !important; }
	.pgm-adv-panel .nav-tabs .nav-link.active { color: #26c6da !important; border-color: rgba(255,255,255,.1) rgba(255,255,255,.1) transparent !important; background: rgba(255,255,255,.04) !important; }
	.pgm-adv-panel .nav-tabs .nav-link:hover:not(.active) { color: rgba(230,237,243,.85) !important; background: rgba(255,255,255,.04) !important; }
	/* Tab panes */
	.pgm-adv-panel .tab-content,
	.pgm-adv-panel .tab-pane { background: transparent !important; color: #e6edf3 !important; }
	/* Inputs / selects / textarea */
	.pgm-adv-panel .form-control,
	.pgm-adv-panel select.form-control,
	.pgm-adv-panel input.form-control,
	.pgm-adv-panel textarea.form-control {
		background: #13161d !important;
		border-color: rgba(255,255,255,.12) !important;
		color: #e6edf3 !important;
	}
	.pgm-adv-panel .form-control:focus { border-color: rgba(38,198,218,.45) !important; }
	.pgm-adv-panel .form-control[readonly] { background: rgba(255,255,255,.05) !important; color: rgba(230,237,243,.5) !important; }
	/* Labels */
	.pgm-adv-panel label,
	.pgm-adv-panel .control-label { color: rgba(230,237,243,.75) !important; }
	.pgm-adv-panel .text-muted { color: rgba(230,237,243,.45) !important; }
	/* Tabela */
	.pgm-adv-panel .table { color: #e6edf3 !important; }
	.pgm-adv-panel .table thead th { color: #26c6da !important; border-bottom: 2px solid rgba(38,198,218,.4) !important; background: rgba(255,255,255,.03) !important; }
	.pgm-adv-panel .table td,
	.pgm-adv-panel .table th { border-color: rgba(255,255,255,.07) !important; }
	.pgm-adv-panel .table-hover tbody tr:hover td { background: rgba(255,255,255,.04) !important; }
	/* Alert */
	.pgm-adv-panel .alert-info { background: rgba(38,198,218,.12) !important; border-color: rgba(38,198,218,.25) !important; color: #9ef0f8 !important; }
	.pgm-adv-panel .alert-secondary { background: rgba(255,255,255,.06) !important; border-color: rgba(255,255,255,.1) !important; color: rgba(230,237,243,.7) !important; }
	/* HR */
	.pgm-adv-panel hr { border-color: rgba(255,255,255,.1) !important; }
</style>
<div class="col-md-12 pgm-adv-page">
	<div class="card pgm-adv-panel">
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
				<li class="nav-item"> <a class="nav-link <?= $aba == 3 ? 'active' : '' ?>" data-toggle="tab" href="#escritafiscal" role="tab" aria-selected="false"><span class="hidden-sm-up"></span> <span class="hidden-xs-down"> Escrita fiscal </span></a> </li>
			</ul>
			<div class="tab-content">
				<div class="tab-pane <?= $aba == 1 ? 'active' : '' ?>" id="contratos">
					<?= $this->Form->create($fatura, ['class' => 'form-material']); ?>

						<!-- ── Identificação ── -->
						<div class="erp-section">
							<div class="erp-section-title">Identificação</div>
							<div class="erp-section-body">
								<div class="row">
									<div class="col-xl-6 col-lg-6 com-md-6 col-sm-12 col-xs-12">
										<label class="control-label"> Cliente </label>
										<?= $this->Form->control('idcliente', ['class' => 'form-control selectpicker', 'data-live-search' => true, 'options' => $clientes, 'title' => 'Selecione um cliente', 'label' => false, 'required' => true]) ?>
									</div>
									<div class="col-xl-2 col-lg-3 com-md-4 col-sm-6 col-xs-12">
										<label class="control-label"> Contrato </label>
										<?= $this->Form->control('contrato', ['class' => 'form-control', 'value' => $fatura->nro, 'label' => false, 'required' => true, 'readonly' => true]) ?>
									</div>
									<div class="col-xl-2 col-lg-3 com-md-4 col-sm-6 col-xs-12">
										<label class="control-label"> Forma de Pagamento </label>
										<?= $this->Form->control('pagamento', ['class' => 'form-control', 'label' => false, 'options' => C_OrdensPagamento]) ?>
									</div>
								</div>
							</div>
						</div>

						<!-- ── Datas ── -->
						<div class="erp-section">
							<div class="erp-section-title">Datas</div>
							<div class="erp-section-body">
								<div class="row">
									<div class="col-xl-2 col-lg-3 com-md-3 col-sm-6 col-xs-12">
										<div class="form-group">
											<label class="control-label text-muted"> Data emissão </label>
											<?= $this->Form->text('dtemissao', ['class' => 'form-control datepicker ', 'id' => 'dtemissao', 'default' => date('d/m/Y'), 'placeholder' => 'Insira a data', 'required' => true]) ?>
										</div>
									</div>
									<div class="col-xl-2 col-lg-3 com-md-3 col-sm-6 col-xs-12">
										<div class="form-group">
											<label class="control-label text-muted"> Previsão retorno </label>
											<?= $this->Form->text('dtretorno', ['class' => 'form-control datepicker ', 'id' => 'dtretorno', 'default' => date('d/m/Y'), 'placeholder' => 'Insira a data', 'required' => true]) ?>
										</div>
									</div>
									<div class="col-xl-2 col-lg-3 com-md-3 col-sm-6 col-xs-12">
										<div class="form-group">
											<label class="control-label text-muted"> Data devolução </label>
											<?= $this->Form->text('dtdevolucao', ['class' => 'form-control datepicker ', 'id' => 'dtdevolucao', 'default' => date('d/m/Y'), 'placeholder' => 'Insira a data', 'required' => true]) ?>
										</div>
									</div>
									<div class="col-xl-2 col-lg-3 com-md-3 col-sm-6 col-xs-12">
										<div class="form-group">
											<label class="control-label text-muted"> Data vencimento </label>
											<?= $this->Form->text('vencimento', ['class' => 'form-control datepicker ', 'id' => 'vencimento', 'default' => date('d/m/Y'), 'placeholder' => 'Insira a data', 'required' => true]) ?>
										</div>
									</div>
								</div>
							</div>
						</div>

						<!-- ── Configuração ── -->
						<div class="erp-section">
							<div class="erp-section-title">Configuração</div>
							<div class="erp-section-body">
								<div class="row">
									<div class="col-xl-3 col-lg-3 com-md-4 col-sm-6 col-xs-12">
										<label class="control-label"> Equipamento(s) instalado(s) em </label>
										<?= $this->Form->control('local', ['data-live-search' => 'true', 'class' => 'selectpicker form-control', 'options' => $cidades, 'label' => false]) ?>
									</div>
									<div class="col-xl-2 col-lg-3 com-md-4 col-sm-6 col-xs-12">
										<label class="control-label"> Referente </label>
										<?= $this->Form->control('referente', ['class' => 'form-control', 'label' => false, 'required' => true]) ?>
									</div>
									<div class="col-xl-2 col-lg-3 com-md-4 col-sm-6 col-xs-12">
										<label class="control-label"> Tipo </label>
										<?= $this->Form->control('tipo', ['class' => 'form-control', 'label' => false, 'required' => true, 'options' => C_LocacaoTipoArray]) ?>
									</div>
									<div class="col-xl-2 col-lg-3 com-md-4 col-sm-6 col-xs-12">
										<label class="control-label"> Status </label><br>
										<?= LocacaoStatus($fatura->status) ?>
									</div>
								</div>
							</div>
						</div>

						<!-- ── Valores ── -->
						<div class="erp-section">
							<div class="erp-section-title">Valores</div>
							<div class="erp-section-body">
								<div class="row">
									<div class="col-lg-2 col-sm-6">
										<div class="form-group">
											<label class="control-label text-muted"> Desconto </label>
											<?= $this->Form->text('desconto', ['onkeypress' => 'return SomenteNumero(event, "#desconto")', 'id' => 'desconto', 'class' => 'form-control mascaramonetaria', 'label' => false]) ?>
										</div>
									</div>
									<div class="col-lg-2 col-sm-6">
										<div class="form-group">
											<label class="control-label text-muted"> Outros gastos/frete </label>
											<?= $this->Form->text('outrosgastos', ['onkeypress' => 'return SomenteNumero(event, "#outrosgastos")', 'id' => 'outrosgastos', 'class' => 'form-control mascaramonetaria', 'label' => false]) ?>
										</div>
									</div>
								</div>
							</div>
						</div>

						<!-- Salvar -->
						<div class="erp-action-bar" style="border-top:none;margin-top:0;padding-top:0;">
							<?= $this->Form->button('Salvar', ['class' => 'btn btn-pgm btn-pgm-salvar btn-success']) ?>
						</div>

						<!-- ── Itens (somente status pendente) ── -->
						<?php if ($fatura->status == C_LocacaoStatusPendente) : ?>
						<div class="erp-section m-t-15">
							<div class="erp-section-title">Adicionar item</div>
							<div class="erp-section-body erp-additem-bar" style="border:none;border-radius:0;">
								<div class="row">
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
							</div>
						</div>
						<?php endif; ?>

						<div id="carrinho" class='m-t-10'> </div>

						<?php if ($fatura->status == C_LocacaoStatusPendente) : ?>
						<div class="row m-t-5">
							<div class="col-12">
								<p class="alert alert-secondary small m-b-5" role="status">
									Para registrar pagamento e gerar <strong>recibo de locação</strong> para o cliente: clique em <strong>Aprovar</strong> abaixo. Com a locação <strong>aprovada</strong>, o botão <strong>Receber</strong> aparece nesta mesma área; depois de salvar, os recibos ficam na aba <strong>Recibos</strong> (ícone de impressão).
								</p>
							</div>
						</div>
						<?php endif; ?>

						<div class="erp-action-bar">
							<?php if ($fatura->status == C_LocacaoStatusPendente): ?>
							<?= $this->Html->link('Rejeitar',  ['action' => 'rejeitar', $fatura->id], ['id' => 'btn-rejeitar', 'class' => 'btn btn-danger']) ?>
							<?= $this->Html->link('Aprovar',   ['action' => 'aprovar',  $fatura->id], ['id' => 'btn-aprovar',  'class' => 'btn btn-pgm btn-pgm-salvar btn-success']) ?>
							<?php endif; ?>
							<?php if ($fatura->status == C_LocacaoStatusAprovado): ?>
							<?= $this->Html->link('Receber', ['action' => 'receber', $fatura->id], ['target' => '_blank', 'id' => 'btn-receber', 'class' => 'btn btn-pgm btn-pgm-situacao btn-info']) ?>
							<?php endif; ?>
							<?= $this->Html->link('Compartilhar', ['action' => 'view', $fatura->hash], ['class' => 'btn btn-pgm btn-pgm-email btn-purple btn-compartilhar']) ?>
							<?= $this->Html->link('Imprimir',     ['action' => 'imprimir', $fatura->id], ['target' => '_blank', 'class' => 'btn btn-pgm btn-pgm-imprimir btn-orange']) ?>
						</div>

					<?= $this->Form->end(); ?>
				</div>
				<div class="tab-pane <?= $aba == 2 ? 'active' : '' ?>" id="recibos">
					<table class="table table-hover table-row-clickable" id="tableRecibos">
						<thead>
							<tr>
								<th style="color:#26c6da;font-weight:600;border-bottom:2px solid #26c6da;"> Número </th>
								<th style="color:#26c6da;font-weight:600;border-bottom:2px solid #26c6da;"> Pagamento </th>
								<th style="color:#26c6da;font-weight:600;border-bottom:2px solid #26c6da;"> Data Recebimento </th>
								<th style="color:#26c6da;font-weight:600;border-bottom:2px solid #26c6da;"> Descontos </th>
								<th style="color:#26c6da;font-weight:600;border-bottom:2px solid #26c6da;"> Juros </th>
								<th style="color:#26c6da;font-weight:600;border-bottom:2px solid #26c6da;"> Valor Pago </th>
								<th style="color:#26c6da;font-weight:600;border-bottom:2px solid #26c6da;"> Ações </th>
							</tr>
						</thead>
						<tbody>
							<?php if (empty($recibos)) : ?>
								<tr>
									<td colspan="7" class="text-muted text-center p-15">
										Nenhum recibo ainda. Na aba <strong>Contrato</strong>, com status <strong>Aprovado</strong>, use <strong>Receber</strong> para registrar o pagamento; o recibo impresso aparece aqui.
									</td>
								</tr>
							<?php else : ?>
								<?php foreach ($recibos as $reg): ?>
									<tr>
										<td> <?= h($reg->nro) ?> </td>
										<td> <?= OrdensPagamento($reg->pagamento) ?> </td>
										<td data-order="<?= date_format($reg->datarecebimento, 'Ymd') ?>"> <?= date_format($reg->datarecebimento, 'd/m/Y') ?> </td>
										<td> <?= number_format($reg->desconto, 2, ',', '.') ?> </td>
										<td> <?= number_format($reg->juros, 2, ',', '.') ?> </td>
										<td> <?= number_format($reg->valorpago, 2, ',', '.') ?> </td>
										<td>
											<?= $this->Html->link('<i class="fa fa-print"></i>', ['action' => 'recibo', $reg->id], ['rel' => 'tooltip', 'title' => 'Imprimir recibo', 'id' => $reg->id, 'class' => 'btn btn-pgm btn-pgm-imprimir btn-orange btn-xs', 'target' => '_blank', 'escape' => false]) ?>
										</td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
				<div class="tab-pane <?= $aba == 3 ? 'active' : '' ?>" id="escritafiscal" role="tabpanel">
					<?= $this->element('Faturas/escrita_fiscal_form', ['escritaFiscal' => $escritaFiscal, 'fatura' => $fatura]) ?>
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
				error : function(xhr) {
					var j = xhr.responseJSON || {};
					var m = j.msg || j.message || (xhr.statusText && xhr.status ? (xhr.status + ' ' + xhr.statusText) : null);
					bootbox.alert(m || 'Não foi possível adicionar o item. Tente novamente.');
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