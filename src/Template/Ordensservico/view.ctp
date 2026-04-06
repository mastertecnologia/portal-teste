<?php
	use Cake\Routing\Router;
    $this->Breadcrumbs->add('Ordens de Serviço', ['controller' => 'Ordensservico', 'action' => 'index'], ['class' => 'breadcrumb-item']);
    $this->Breadcrumbs->add('View', [], ['class' => 'breadcrumb-item active']);

    $disabled = true;
?>
<style>
	.table td{padding: 0.7rem !important;}
	.os-view-fat-heading {
		font-size: 12px;
		text-transform: uppercase;
		letter-spacing: 0.06em;
		color: #7d8590;
	}
	.os-view-fat-table {
		font-size: 13px;
	}
</style>
<div class="col-md-12">
    <div class="card">
        <div class="card-body">
			<ul class="nav nav-tabs customtab m-b-20" role="tablist">
                <li class="nav-item"> <a class="nav-link active " data-toggle="tab" href="#ordem" role="tab" aria-selected="true"><span class="hidden-sm-up"><i class="ti-user"></i></span> <span class="hidden-xs-down">Ordem de Serviço</span></a> </li>
                <li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#movimentacoes" role="tab" aria-selected="false"><span class="hidden-sm-up"><i class="ti ti-reload"></i></span> <span class="hidden-xs-down">Movimentações (<?= count($movimentacoes) ?>)</span></a> </li>
				<li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#horas" role="tab" aria-selected="false"><span class="hidden-sm-up"><i class="ti-time"></i></span> <span class="hidden-xs-down">Horas Cadastradas (<?= count($ordemhoras) ?>)</span></a> </li>
				<li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#parcelas" role="tab" aria-selected="false"><span class="hidden-sm-up"><i class="ti-receipt"></i></span> <span class="hidden-xs-down">Pagamentos</span></a> </li>
            </ul>
			<div class="tab-content">
				<div class="tab-pane active show" id="ordem">
					<?= $this->Form->create($ordem, ['class' => 'form-material']);
						if(!empty($ordem->idorcamento)){ ?>
							<div class="row">
								<div class="col-12">
									<legend>Orçamento nº: <?= $ordem->idorcamento ?></legend>
								</div>
							</div>
						<?php } if(!empty($ordem->idticket)){ ?>
							<div class="row">
								<div class="col-12">
									<legend>Ticket nº: <?= $ordem->idticket ?></legend>
								</div>
							</div>
						<?php }	 ?>
						<div class="row m-b-5">
							<div class="col-md-3 col-xs-12">
								<label class="control-label">Cliente</label>
								<?= $this->Form->control('idcliente', ['id' => 'idcliente', 'data-live-search' => true, 'options' => $clientes, 'title' => 'Selecione um cliente', 'class' => 'form-control selectpicker', 'label' => false, 'required' => true, 'disabled' => true]) ?>
							</div>
							<div class="col-md-3 col-xs-12">
								<label class="control-label">Solicitante</label>
								<?= $this->Form->control('idsolicitante', ['class' => 'selectpicker form-control', 'title' => 'Solicitante (opcional)', 'data-live-search' => true, 'options' => '', 'label' => false, 'required' => false, 'disabled' => true]) ?>
								
								<!-- Campo para "Outros" -->
								<div id="solicitante-outros-container" class="pgm-solic-outros-wrap">
									<label class="control-label">Nome do Solicitante (Outros)</label>
									<?= $this->Form->control('solicitante_outros', [
										'class' => 'form-control', 
										'label' => false, 
										'placeholder' => 'Digite o nome do solicitante',
										'maxlength' => 255,
										'disabled' => true
									]) ?>
								</div>
							</div>
							<div class="col-md-2 col-xs-6 clienteTelemail">
								<label class="control-label">Telefone para contato</label>
								<?= $this->Form->control('telefone', ['class' => 'telefone form-control', 'label' => false, 'placeholder' => 'Nenhum telefone', 'disabled' => true]) ?>
							</div>
							<div class="col-md-2 col-xs-6 clienteTelemail">
								<label class="control-label">Celular para contato</label>
								<?= $this->Form->control('celular', ['class' => 'celular form-control', 'label' => false, 'placeholder' => 'Nenhum celular', 'disabled' => true]) ?>
							</div>
							<div class="col-md-2 col-xs-12 clienteTelemail">
								<label class="control-label">E-mail para contato</label>
								<?= $this->Form->email('email', ['type' => 'text', 'class' => 'email form-control', 'label' => false, 'placeholder' =>'Nenhum email', 'disabled' => true]) ?>
							</div>
						</div>
						<div class="row">
							<div class="col-md-2 col-xs-12">
								<div class="form-group ">
									<label class="control-label">Data de Abertura</label>
									<?= $this->Form->text('dataabertura', ['placeholder' => 'Data', 'class' => 'form-control datepicker', 'label' => false, 'required' => true, 'disabled' => true]) ?>
								</div>
							</div>
							<div class="col-md-2 col-xs-12">
								<div class="form-group ">
									<label class="control-label">Data de Previsão</label>
									<?= $this->Form->text('dataprevisao', ['placeholder' => 'Data', 'class' => 'form-control datepicker', 'label' => false, 'required' => true, 'disabled' => true]) ?>
								</div>
							</div>
							<div class="col-md-2 col-xs-12">
								<label class="control-label">Prioridade</label>
								<?= $this->Form->control('prioridade', ['placeholder' => 'Data', 'options' => C_OrdensPrioridade,  'class' => 'form-control', 'label' => false, 'required' => true, 'disabled' => true]) ?>
							</div>
							<div class="col-md-2 col-xs-12">
								<label class="control-label">Contrato</label>
								<?= $this->Form->control('contrato', ['placeholder' => 'Data', 'options' => C_OrdensContrato,  'class' => 'form-control', 'label' => false, 'required' => true, 'disabled' => true]) ?>
							</div>
							<div class="col-md-2 col-xs-12">
								<label class="control-label">Status</label>
								<?= $this->Form->control('idarea', ['options' => $areas, 'class' => 'form-control', 'label' => false, 'required' => true, 'disabled' => true]) ?>
							</div>
							<div class="col-md-2 col-xs-12">
								<label class="control-label">Tipo de OS</label>
								<?= $this->Form->control('idproblema', ['data-live-search' => true, 'options' => $problemas, 'title' => 'Selecione um problema', 'class' => 'form-control selectpicker', 'label' => false, 'required' => true, 'disabled' => true]) ?>
							</div>
						</div>
						<div class="row">
							<div class="col-md-2 col-xs-12">
								<label class="control-label">Situação</label><br>
								<?= SituacaoOrdem($ordem->situacao) ?>
							</div>
							<div class="col-md-2 col-xs-12">
								<label class="control-label">Atendimento</label>
								<?= $this->Form->control('atendimento', ['placeholder' => 'Data', 'options' => C_OrdensAtendimento,  'class' => 'form-control', 'label' => false, 'required' => true, 'disabled' => true]) ?>
							</div>
							<div class="col-md-2 col-xs-12">
								<label class="control-label">Modelo</label>
								<?= $this->Form->control('modelo', ['placeholder' => 'Insira o modelo',  'class' => 'form-control', 'label' => false, 'required' => false, 'disabled' => true]) ?>
							</div>
							<div class="col-md-2 col-xs-12">
								<label class="control-label">N/S</label>
								<?= $this->Form->control('nmrserie', ['placeholder' => 'Insira o número de série', 'class' => 'form-control', 'label' => false, 'required' => false, 'disabled' => true]) ?>
							</div>
							<?php if(!empty($ordem->nrodestino)){ ?>
								<div class="col-md-2 col-xs-12">
									<label class="control-label">Nro. Destino</label>
									<p> <?= $ordem->nrodestino ?> </p> 
								</div>
							<?php } ?>
						</div>
						<br>
						<div class="row">
							<div class="col-md-6 col-xs-12">
								<label class="control-label">Descrição do Problema</label>
								<?= $this->Form->textarea('relato', ['placeholder' => 'Insira a descrição do problema da ordem', 'class' => 'form-control', 'label' => false, 'required' => true, 'rows' => 3, 'disabled' => true]) ?>
							</div>
							<div class="col-md-6 col-xs-12">
								<label class="control-label">Observação</label>
								<?= $this->Form->textarea('observacao', ['placeholder' => 'Observação', 'class' => 'form-control', 'label' => false, 'required' => true, 'rows' => 3, 'disabled' => true]) ?>
							</div>
						</div>
						<hr>
						<div class="row">
							<div class="col-12">
								<h3 class='bg-dark text-white text-center p-2'> Produtos e Serviços </h3><br>
							</div>
						</div>
						<div class="table-responsive">
							<table class="table" id="tableCarrinho">
								<thead class="text-primary">
									<th>Tipo</th>
									<th>Código</th>
									<th>Descrição</th>
									<th>Observação</th>
									<th>Unidade</th>
									<th width="7%" class="text-right">Qtde.</th>
									<th width="7%" class="text-right">Vl. Mensal</th>
									<th width="7%" class="text-right">Vl. Unit.</th>
									<th width="7%" class="text-right">Valor Total</th>
								</thead>
								<tbody>
									<!-- Serviços -->
									<?php if(isset($carrinho)){ foreach($carrinho as $reg){ ?>
										<tr id='<?= $reg->id ?>'>
											<td><?= ProdutosTipo($reg->tipo) ?></td>
											<td><?= $reg->codproduto ?></td>
											<td><?= $reg->descricao ?></td>
											<td><?= $reg->observacao ?></td>
											<td><?= $reg->observacao ?></td>
											<td class="text-right"><?= $reg->quantidade ?></td>
											<td class="text-right valorunit"><?php echo 'R$ ' . number_format($reg->valorunitario, 2, ",", ".") ?></td>
											<td class="text-right"><?php echo 'R$ ' . number_format($reg->valordesconto, 2, ",", ".") ?></td>
											<td class="text-right valordoservico"><?php echo 'R$ ' . number_format($reg->valortotal, 2, ",", ".") ?></td>
										</tr>
									<?php } } ?>
									<!-- Fim Serviços -->
								</tbody>
							</table>
						</div>
						<!-- valortotal que é exibido e o input hidden dele -->
						<?= '<h5 class="text-right text-success font-weight-bold m-r-15 valortotalordem"> </h5>' ?>
					<?= $this->Form->end() ?>

					<?php
					// ── Botão Gerar Faturamento (OS finalizada ou liberada para faturamento) ──
					if ($role == 0 && in_array($ordem->situacao, [C_OrdensSituacaoFinalizada, C_OrdensSituacaoLiberadaParaFaturamento], true)):
					?>
					<div class="row m-t-10">
						<div class="col-12 text-right">
							<?= $this->Html->link(
								'<i class="fas fa-file-alt"></i> Gerar Faturamento',
								['controller' => 'Faturamento', 'action' => 'gerarDeOS', $ordem->id],
								['class' => 'btn btn-pgm btn-pgm-salvar btn-sm', 'escape' => false, 'title' => 'Criar documento de faturamento a partir desta OS']
							) ?>
						</div>
					</div>
					<?php endif; ?>

					<?php if ($role == 0 && !empty($faturamentos)): ?>
					<div class="row m-t-15">
						<div class="col-12">
							<h6 class="m-b-5 os-view-fat-heading">
								<i class="fas fa-file-alt"></i> Documentos de Faturamento
							</h6>
							<table class="table table-sm os-view-fat-table">
								<thead>
									<tr>
										<th>Número</th>
										<th>Status</th>
										<th>Emissão</th>
										<th>Vencimento</th>
										<th class="text-right">Total</th>
										<th></th>
									</tr>
								</thead>
								<tbody>
									<?php
									$statusLabels = [
										'rascunho'  => ['label' => 'Rascunho',  'class' => 'badge-secondary'],
										'pendente'  => ['label' => 'Pendente',  'class' => 'badge-warning'],
										'enviado'   => ['label' => 'Enviado',   'class' => 'badge-info'],
										'pago'      => ['label' => 'Pago',      'class' => 'badge-success'],
										'cancelado' => ['label' => 'Cancelado', 'class' => 'badge-danger'],
									];
									foreach ($faturamentos as $fat):
										$stInfo = $statusLabels[$fat->status] ?? ['label' => $fat->status, 'class' => 'badge-secondary'];
									?>
									<tr>
										<td><?= h($fat->numero ?? '#' . $fat->id) ?></td>
										<td><span class="badge <?= $stInfo['class'] ?>"><?= $stInfo['label'] ?></span></td>
										<td><?= $fat->data_emissao ? $fat->data_emissao->format('d/m/Y') : '—' ?></td>
										<td><?= $fat->data_vencimento ? $fat->data_vencimento->format('d/m/Y') : '—' ?></td>
										<td class="text-right">R$ <?= number_format((float)$fat->valor_total, 2, ',', '.') ?></td>
										<td>
											<?= $this->Html->link('<i class="fas fa-eye"></i>', ['controller' => 'Faturamento', 'action' => 'view', $fat->id], ['class' => 'btn btn-xs btn-default', 'escape' => false, 'title' => 'Visualizar']) ?>
										</td>
									</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>
					<?php endif; ?>

        		</div>
				<div class="tab-pane" id="movimentacoes">
					<?php
					foreach (array_reverse($movimentacoes) as $reg):
						$data = $reg['data'];
						echo "<br><div class='col-lg-12'>";
						if(!empty($reg->user)) echo "<strong>" . $reg->user->name . "</strong>  - " ;
						echo $data->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('d/m/Y') . " às " . $data->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('H:i') . "<br>";
						echo "<p>";
						if ($reg['sitnova'] == C_OrdensSituacaoAberta) {
							if($reg['sitantiga'] == C_OrdensSituacaoLiberadaParaFaturamento) echo "Voltou a ordem de serviço";
							else echo "Pausou a ordem de serviço";
						}
						if ($reg['sitnova'] == C_OrdensSituacaoEmExecucao) {
							if ($reg['sitantiga'] == C_OrdensSituacaoEmExecucao) echo "Abriu a ordem de serviço.";
							elseif ($reg['sitantiga'] == C_OrdensSituacaoAberta) echo "Re-abriu a ordem de serviço.";
							else echo "Alterou a situação do chamado para 'Em execução'";
						}
						if ($reg['sitnova'] == C_OrdensSituacaoCancelada) echo "Cancelou a ordem de serviço.";
						if ($reg['sitnova'] == C_OrdensSituacaoFinalizada) echo "Finalizou a ordem de serviço.";
						if ($reg['sitnova'] == C_OrdensSituacaoLiberadaParaFaturamento) echo "Liberou a ordem de serviço para sincronização.";
						if ($reg['sitnova'] == C_OrdensSituacaoSincronizadaPeloGrid) echo "A ordem de serviço foi sincronizada pelo Grid.";
						if ($reg['sitnova'] == C_OrdensSituacaoFaturada) echo "A ordem de serviço foi faturada.";
						echo "</p><hr></div>";
					endforeach;
					?>
				</div>
				<div class="tab-pane" id="horas">
					<?php if (sizeof($ordemhoras) > 0) { ?>
						<div class="table-responsive">
							<table class="table table-hover" id="tableProgramas">
								<thead class="text-primary">
									<th>Usuário</th>
									<th>Data</th>
									<th>Horário</th>
								</thead>
								<tbody>
									<?php foreach ($ordemhoras as $reg): ?>
										<tr>
											<td><?= $reg->user->username ?></td>
											<td><?= date_format($reg->data, 'd/m/Y'); ?></td>
											<td><?= date_format($reg->horaini, 'H:i') . " - " . date_format($reg->horafin, 'H:i'); ?></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php } else echo "<br><center><h4>Nenhum registro de horas encontrado</h4></center><br>"; ?>
				</div>
				<div class="tab-pane" id="parcelas">
					<div class="table-responsive">
					<?php if(isset($ordemparcelas)){ ?>
						<table class="table table-hover" id="tablePagamento">
							<thead class="text-primary">
								<th>Usuário</th>
								<th>Pagamento</th>
								<th>Número de Parcelas</th>
								<th>Parcela de Entrada</th>
								<th>Data</th>
							</thead>
							<tbody>
								<tr>
								<td><?= $ordemparcelas->user->name ?></td>
								<td><?= OrdensPagamento($ordemparcelas->pagamento) ?></td>
								<td id='nmrparcelasText'><?= $ordemparcelas->nmrparcelas ?></td>
								<td><?= $ordemparcelas->entrada ? 'Sim' : 'Não' ?></td>
								<td><?= date_format($ordemparcelas->data, 'd/m/Y'); ?></td>
								</tr>
							</tbody>
						</table>
						<table class="table table-hover" id="tableParcelas">
							<thead class="text-primary">
								<th class="sideh">Parcelas:</th>
								<th>Parcela 1</th>
								<th>Parcela 2</th>
								<?php if($ordemparcelas->nmrparcelas > 2) echo "<th>Parcela 3</th>"; ?>
								<?php if($ordemparcelas->nmrparcelas > 3) echo "<th>Parcela 4</th>"; ?>
								<?php if($ordemparcelas->nmrparcelas > 4) echo "<th>Parcela 5</th>"; ?>
							</thead>
							<tbody>
								<tr>
									<td class="sideh">Valor:</td>
									<td class="valorparcela"></td>
									<td class="valorparcela"></td>
									<?php if($ordemparcelas->nmrparcelas > 2) echo "<td class='valorparcela'></td>"; ?>
									<?php if($ordemparcelas->nmrparcelas > 3) echo "<td class='valorparcela'></td>"; ?>
									<?php if($ordemparcelas->nmrparcelas > 4) echo "<td class='valorparcela'></td>"; ?>
								</tr>
								<tr>
									<td class="sideh">Data:</td>
									<td><?= date_format($ordemparcelas->dataval1, 'd/m/Y'); ?></td>
									<td><?= date_format($ordemparcelas->dataval2, 'd/m/Y'); ?></td>
									<?php if($ordemparcelas->nmrparcelas > 2) echo "<td>".date_format($ordemparcelas->dataval3, 'd/m/Y')."</td>"; ?>
									<?php if($ordemparcelas->nmrparcelas > 3) echo "<td>".date_format($ordemparcelas->dataval4, 'd/m/Y')."</td>"; ?>
									<?php if($ordemparcelas->nmrparcelas > 4) echo "<td>".date_format($ordemparcelas->dataval5, 'd/m/Y')."</td>"; ?>
								</tr>
							</tbody>
						</table>
					<?php }else echo "<br><center><h4>Nenhum registro de parcelas encontrado</h4></center><br>"; ?>
					</div>					
				</div>
			</div>
            <div class="clearfix"></div>
        </div>
    </div>
</div>
<script>
	var idcliente = $("#idcliente").val();
	var idsolicitante = $("#idsolicitante").val();

	$(document).ready(function(){
		$('#idsolicitante').append("<option value='' >Indefinido</option>");
		$('#modelo, #nmrserie').prop("disabled", true);
		$('.dataval2, .dataval3, .dataval4, .dataval5').hide();
		loadSolicitantes($('#idcliente').val());
		
		var currentSolicitanteOutros = '<?= $ordem->solicitante_outros ?>';
		var currentSolicitante = '<?= $ordem->idsolicitante ?>';
		
		if (currentSolicitante == 0 && currentSolicitanteOutros) {
			$('#solicitante-outros-container').show();
			$('#solicitante-outros').val(currentSolicitanteOutros);
		}
	});

	$("#idcliente").change(function() {
		var idcliente = $(this).val();
		loadSolicitantes(idcliente);
		loadCliTelemail(idcliente);
		$.ajax({
			url: "<?= Router::url(['controller'=>'Clientes','action'=>'contrato']);?>/"+idcliente,
			success:function(data){
				if(data == 1) $('#contrato').val(1); 
				else $('#contrato').val(0); 
			},
		});
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
				
				// Configura o valor atual
				var currentSolicitante = '<?= $ordem->idsolicitante ?>';
				var currentSolicitanteOutros = '<?= $ordem->solicitante_outros ?>';
				
				if (currentSolicitante == 0 && currentSolicitanteOutros) {
					// Se é "Outros" e tem valor no campo outros, seleciona "Outros" e mostra o campo
					$('#idsolicitante').val(0);
					$('#solicitante-outros-container').show();
					$('#solicitante-outros').val(currentSolicitanteOutros);
				} else {
					// Seleciona o solicitante normal
					$('#idsolicitante').val(currentSolicitante);
					$('#solicitante-outros-container').hide();
				}
				
				$('#idsolicitante').selectpicker("refresh");
				
				// Carrega os dados de contato
				if($("#idsolicitante").val() != null && $("#idsolicitante").val() != 0) {
					loadSolTelemail($("#idsolicitante").val());
				} else {
					loadCliTelemail($("#idcliente").val());
				}
			},
		});
	}
	
	function loadCliTelemail(idcliente) {
		$.ajax({
			dataType: "json",
			type:"get",
			url: "<?= Router::url(array('controller'=>'Clientes','action'=>'cliemail'));?>/" + idcliente,
			success: function(data){
				$('.clienteTelemail').show();
				$('.email').val(data.email);
				$('.telefone').val(data.fone);
				$('.celular').val(data.fone2);
			},
		});
	}

	function loadSolTelemail(idsolicitante) {
		console.log(idsolicitante);
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

</script>
