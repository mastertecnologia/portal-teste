<?php

use Cake\Routing\Router;

$this->Html->css('/dist/css/pages/ordensservico-edit-shell-fixed.css?v=3', ['block' => true]);

$this->Breadcrumbs->add('Ordens de Serviço', ['controller' => 'Ordensservico', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Edit', [], ['class' => 'breadcrumb-item active']);

if ($ordem->situacao != C_OrdensSituacaoAberta && $ordem->situacao != C_OrdensSituacaoEmExecucao) $disabled = true;
else $disabled = false;

$osTiposComEstoqueErp = [];
foreach (['C_ProdutosTipoProduto', 'C_ProdutosTipoLicenca', 'C_ProdutosTipoLocacao'] as $osConst) {
	if (defined($osConst)) {
		$osTiposComEstoqueErp[] = (int)constant($osConst);
	}
}
?>
<style>
	.os-edit-shell #grid_table .jsgrid-grid-header,
	.os-edit-shell #grid_table .jsgrid-grid-body {
		overflow-x: hidden;
		overflow-y: auto;
	}

	.jsgrid-cell {
		height: 50px;
		overflow: hidden;
	}

	.jsgrid-cell>select>option {
		text-align: left;
	}

	.os-pesquisa-produto-sem-estoque {
		background-color: #f8d7da !important;
	}

	.os-pesquisa-produto-sem-estoque td {
		color: #721c24;
	}

	.hide {
		display: none !important;
	}
</style>
<div class="col-md-12 p-0">
	<div class="os-edit-shell form-material">
			<ul class="nav nav-tabs os-edit-tabs customtab" role="tablist">
				<li class="nav-item"> <a class="nav-link active " data-toggle="tab" href="#ordem" role="tab" aria-selected="true"><span class="hidden-sm-up"><i class="ti-user"></i></span> <span class="hidden-xs-down">Ordem de Serviço</span></a> </li>
				<li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#movimentacoes" role="tab" aria-selected="false"><span class="hidden-sm-up"><i class="ti ti-reload"></i></span> <span class="hidden-xs-down">Movimentações (<?= count($movimentacoes) ?>)</span></a> </li>
				<li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#horas" role="tab" aria-selected="false"><span class="hidden-sm-up"><i class="ti-time"></i></span> <span class="hidden-xs-down">Horas Cadastradas (<?= count($ordemhoras) ?>)</span></a> </li>
				<li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#parcelas" role="tab" aria-selected="false"><span class="hidden-sm-up"><i class="ti-receipt"></i></span> <span class="hidden-xs-down">Pagamentos</span></a> </li>
			</ul>
			<div class="tab-content">
				<div class="tab-pane active show" id="ordem">
					<div class="row">
						<div class="col-12">
							<legend class="os-edit-os-legend">
								OS nº: <?= $ordem->id ?>

								<?php
								if (!empty($ordem->modelo) || !empty($ordem->nmrserie)):
								?>
									<button type="button" class="btn btn-sm btn-outline-info waves-effect waves-light m-l-10" data-toggle="modal" data-target="#modal-dados-legado" title="Visualizar dados do equipamento (Legado)">
										<i class="fa fa-info-circle"></i> Info. Equipamento
									</button>
								<?php endif; ?>
							</legend>
						</div>
					</div>
					<?= $this->Form->create($ordem, ['class' => 'form-material', 'id' => 'form-os-edit']);
					if (!empty($ordem->idorcamento)) { ?>
						<div class="row">
							<div class="col-12">
								<legend> <?= $this->Html->link("Orçamento nº: $ordem->idorcamento ", ["controller" => "orcamentos", "action" => "edit", $ordem->idorcamento], ['class' => 'link', 'target' => '_blank']); ?> </legend>
							</div>
						</div>
					<?php }
					if (!empty($ordem->idticket)) { ?>
						<div class="row">
							<div class="col-12">
								<legend> <?= $this->Html->link("Ticket nº: $ordem->idticket ", ["controller" => "tickets", "action" => "edit", $ordem->idticket], ['class' => 'link', 'target' => '_blank']); ?> </legend>
							</div>
						</div>
					<?php }	 ?>
					<div class="row m-b-5">
						<div class="col-md-3 col-xs-12">
							<label class="control-label">Cliente</label>
							<?= $this->Form->control('idcliente', ['id' => 'idcliente', 'data-live-search' => true, 'options' => $clientes, 'title' => 'Selecione um cliente', 'class' => 'form-control selectpicker', 'label' => false, 'required' => true, 'disabled' => !in_array($ordem->situacao, [C_OrdensSituacaoAberta, C_OrdensSituacaoEmExecucao, C_OrdensSituacaoLiberadaParaFaturamento])]) ?>
						</div>
						<div class="col-md-3 col-xs-12">
							<label class="control-label">Solicitante</label>
							<?= $this->Form->control('idsolicitante', ['id' => 'idsolicitante', 'data-live-search' => true, 'options' => '', 'class' => 'form-control selectpicker', 'label' => false, 'required' => false, 'disabled' => !in_array($ordem->situacao, [C_OrdensSituacaoAberta, C_OrdensSituacaoEmExecucao, C_OrdensSituacaoLiberadaParaFaturamento])]) ?>

							<!-- Campo para "Outros" -->
							<div id="solicitante-outros-container" class="pgm-solic-outros-wrap">
								<label class="control-label">Nome do Solicitante (Outros)</label>
								<?= $this->Form->control('solicitante_outros', [
									'class' => 'form-control',
									'label' => false,
									'placeholder' => 'Digite o nome do solicitante',
									'maxlength' => 255,
									'disabled' => !in_array($ordem->situacao, [C_OrdensSituacaoAberta, C_OrdensSituacaoEmExecucao, C_OrdensSituacaoLiberadaParaFaturamento])
								]) ?>
							</div>
						</div>
						<div class="col-md-2 col-xs-6 clienteTelemail">
							<label class="control-label">Telefone para contato</label>
							<?= $this->Form->control('telefone', ['class' => 'telefone form-control', 'label' => false, 'placeholder' => 'Nenhum telefone', 'readonly' => true]) ?>
						</div>
						<div class="col-md-2 col-xs-6 clienteTelemail">
							<label class="control-label">Celular para contato</label>
							<?= $this->Form->control('celular', ['class' => 'celular form-control', 'label' => false, 'placeholder' => 'Nenhum celular', 'readonly' => true]) ?>
						</div>
						<div class="col-md-2 col-xs-12 clienteTelemail">
							<label class="control-label">E-mail para contato</label>
							<?= $this->Form->email('email', ['type' => 'text', 'class' => 'email form-control', 'label' => false, 'placeholder' => 'Nenhum email', 'readonly' => true]) ?>
						</div>
					</div>
					<div class="row">
						<div class="col-md-2 col-xs-12">
							<div class="form-group ">
								<label class="control-label">Data de Abertura</label>
								<?= $this->Form->text('dataabertura', ['placeholder' => 'Data', 'class' => 'form-control datepicker', 'label' => false, 'required' => true, 'disabled' => $disabled]) ?>
							</div>
						</div>
						<div class="col-md-2 col-xs-12">
							<div class="form-group ">
								<label class="control-label">Data de Previsão</label>
								<?= $this->Form->text('dataprevisao', ['placeholder' => 'Data', 'class' => 'form-control datepicker', 'label' => false, 'required' => true, 'disabled' => $disabled]) ?>
							</div>
						</div>
						<div class="col-md-2 col-xs-12">
							<label class="control-label">Prioridade</label>
							<?= $this->Form->control('prioridade', ['placeholder' => 'Data', 'options' => C_OrdensPrioridade,  'class' => 'form-control', 'label' => false, 'required' => true, 'disabled' => $disabled]) ?>
						</div>
						<div class="col-md-2 col-xs-12">
							<label class="control-label">Contrato</label>
							<?= $this->Form->control('contrato', ['placeholder' => 'Data', 'options' => C_OrdensContrato,  'class' => 'form-control', 'label' => false, 'required' => true, 'disabled' => $disabled]) ?>
						</div>
						<div class="col-md-2 col-xs-12">
							<label class="control-label">Status</label>
							<?= $this->Form->control('idarea', ['options' => $areas, 'class' => 'form-control os-edit-native-select', 'label' => false, 'required' => true, 'disabled' => $disabled]) ?>
						</div>
						<div class="col-md-2 col-xs-12">
							<label class="control-label">Tipo de OS</label>
							<?= $this->Form->control('idproblema', ['data-live-search' => true, 'options' => $problemas, 'title' => 'Selecione um problema', 'class' => 'form-control selectpicker', 'label' => false, 'required' => true, 'disabled' => $disabled]) ?>
						</div>
					</div>
					<div class="row">
						<div class="col-md-2 col-xs-12">
							<label class="control-label">Situação</label><br>
							<?= SituacaoOrdem($ordem->situacao) ?>
						</div>
						<div class="col-md-2 col-xs-12">
							<label class="control-label">Atendimento</label>
							<?= $this->Form->control('atendimento', ['placeholder' => 'Data', 'options' => C_OrdensAtendimento,  'class' => 'form-control', 'label' => false, 'required' => true]) ?>
						</div>
						<div>
							<?= $this->Form->control('idEmpresaAtual', ['id' => 'idEmpresaAtual', 'class' => 'form-control inputMobile', 'label' => false, 'type' => 'hidden', 'value' => (int)($authIdempresa ?? 0)]) ?>
						</div>
						<?php if (!empty($ordem->nrodestino)) { ?>
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
							<?= $this->Form->textarea('relato', ['maxlength' => 200, 'placeholder' => 'Insira a descrição do problema da ordem', 'class' => 'form-control', 'label' => false, 'required' => false, 'rows' => 3]) ?>
						</div>
						<div class="col-md-6 col-xs-12">
							<label class="control-label">Observação</label>
							<?= $this->Form->textarea('observacao', ['maxlength' => 200, 'placeholder' => 'Observação', 'class' => 'form-control', 'label' => false, 'required' => false, 'rows' => 3]) ?>
						</div>
					</div>
					<br>
					<!-- Mobile -->
					<h4 class="os-edit-section-title text-center">Adicionar Produtos/Serviços</h4>
					<?php if (isMobile() && $disabled == false) { ?>
						<div class="row">
							<div class="col-3">
								<label class="control-label text-muted">Código</label>
								<?= $this->Form->control('codproduto', ['data-live-search' => true, 'options' => $produtosMobile, 'title' => 'Código', 'class' => 'inputMobile form-control selectpicker p-0', 'label' => false, 'disabled' => $disabled]) ?>
							</div>
							<div class="col-9">
								<div class="form-group ">
									<label class="control-label text-muted">Descrição</label>
									<?= $this->Form->control('descricao', ['class' => 'form-control inputMobile', 'label' => false, 'readonly', 'disabled' => $disabled]) ?>
								</div>
							</div>
							<div class="col-2">
								<div class="form-group ">
									<label class="control-label text-muted">Unidade</label>
									<?= $this->Form->control('unidade', ['class' => 'form-control inputMobile', 'label' => false, 'readonly', 'disabled' => $disabled]) ?>
								</div>
							</div>
							<div class="col-5">
								<div class="form-group ">
									<label class="control-label text-muted">Quantidade</label>
									<?= $this->Form->control('quantidade', ['class' => 'aquisicao form-control inputMobile', 'label' => false, 'disabled' => $disabled]) ?>
								</div>
							</div>
							<div class="col-5">
								<div class="form-group ">
									<label class="control-label text-muted">Valor Unitário (R$)</label>
									<?= $this->Form->control('valorunitario', ['class' => 'aquisicao form-control inputMobile mascaramonetaria ', 'label' => false, 'readonly', 'disabled' => $disabled]) ?>
								</div>
							</div>
							<div class="col-6">
								<div class="form-group ">
									<label class="control-label text-muted">Valor Desconto (R$)</label>
									<?= $this->Form->control('valordesconto', ['class' => 'mensal form-control inputMobile mascaramonetaria', 'label' => false, 'disabled' => $disabled]) ?>
								</div>
							</div>
							<div class="col-6">
								<div class="form-group ">
									<label class="control-label text-muted">Valor Total (R$)</label>
									<?= $this->Form->text('valortotal', ['id' => 'valortotal', 'class' => 'mensal form-control inputMobile', 'label' => false, 'readonly', 'disabled' => $disabled]) ?>
								</div>
							</div>
							<div class="col-6">
								<div class="form-group ">
									<label class="control-label text-muted">Serial Number</label>
									<?= $this->Form->control('serialnumber', ['list' => 'listaSN', 'id' => 'serialnumber', 'class' => 'form-control inputMobile', 'label' => false]) ?>
									<datalist id="listaSN"> </datalist>
								</div>
							</div>
						</div>
						<?= $this->Html->link('Adicionar item', [], ['class' => 'btn btn-pgm btn-pgm-situacao btn-info btn-additem m-b-20', 'disabled' => $disabled]) ?>
					<?php } ?>
					<?= $this->Form->end() ?>
					<!-- jsGrid fora do form da OS (evita submit acidental). Campos abaixo: form="form-os-edit". -->
					<div id="grid_table"></div>
					<?= '<h5 class="text-right text-success font-weight-bold m-r-15 valortotalordem"> </h5>' ?>
					<input type="hidden" name="valortotalordem" id="valortotalordem" value="<?= h($ordem->valortotalordem ?? '') ?>" form="form-os-edit">

					<?php
					echo '<button type="submit" class="btn btn-pgm btn-pgm-salvar btn-success m-t-20" form="form-os-edit">' . h(__('Salvar Ordem de Serviço')) . '</button>';
					echo $this->Html->link('Imprimir', ['action' => 'imprimir', $ordem->id], ['class' => 'btn btn-pgm btn-pgm-imprimir btn-orange text-white m-l-5 m-t-20', 'data-turbo' => 'false']);
					echo $this->Html->link('Cadastrar Horas', ["action" => "cadhoras", $ordem->id], ['class' => 'btn btn-pgm btn-pgm-salvar text-white m-l-5 m-t-20', 'data-turbo' => 'false']);
					if (!$ordem->locacao) echo $this->Html->link('Locação', ['action' => 'locacao', $ordem->id, 1], ['class' => 'btn btn-pink m-r-5 m-t-20 float-right', 'data-turbo' => 'false']);
					else echo $this->Html->link('Remover locação', ['action' => 'locacao', $ordem->id, 0], ['class' => 'btn btn-pink m-r-5 m-t-20 float-right', 'data-turbo' => 'false']);
					if ($ordem->situacao == C_OrdensSituacaoEmExecucao) echo $this->Html->link('Liberar para sincronização', ['action' => 'liberar', $ordem->id], ['class' => 'btn btn-pgm btn-pgm-situacao btn-info m-r-5 m-t-20 float-right', 'data-turbo' => 'false']);
					if ($ordem->situacao == C_OrdensSituacaoEmExecucao) echo $this->Html->link('Cancelar', ['action' => 'cancelar', $ordem->id], ['class' => 'btn btn-danger m-r-5 m-t-20 float-right', 'data-turbo' => 'false']);
					if ($ordem->situacao == C_OrdensSituacaoLiberadaParaFaturamento) echo $this->Html->link('Voltar ordem', ['action' => 'pausar', $ordem->id], ['class' => 'btn btn-warning m-r-5 m-t-20 float-right', 'data-turbo' => 'false']);
					if ($ordem->situacao == C_OrdensSituacaoAberta) echo $this->Html->link('Em execução', ['action' => 'emexec', $ordem->id], ['class' => 'btn btn-pgm btn-pgm-situacao btn-info m-r-5 m-t-20 float-right', 'data-turbo' => 'false']);
					if ($ordem->situacao == C_OrdensSituacaoCancelada) echo $this->Html->link('Reabrir', ['action' => 'emexec', $ordem->id], ['class' => 'btn btn-pgm btn-pgm-salvar btn-success m-r-5 m-t-20 float-right', 'data-turbo' => 'false']);
					if ($ordem->situacao != C_OrdensSituacaoFinalizada) echo $this->Html->link('Finalizar', ['action' => 'finalizar', $ordem->id], ['class' => 'btn btn-pgm btn-pgm-salvar btn-success m-r-5 m-t-20 float-right', 'data-turbo' => 'false']);
					?>
				</div>
				<div class="tab-pane" id="movimentacoes">
					<?php
					foreach (array_reverse($movimentacoes) as $reg):
						$data = $reg['data'];
						echo "<br><div class='col-md-12'>";
						if (!empty($reg->user)) echo "<strong>" . $reg->user->name . "</strong>  - ";
						echo $data->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('d/m/Y') . " às " . $data->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('H:i') . "<br>";
						echo "<p>";
						//0 - Aberta
						if ($reg['sitnova'] == C_OrdensSituacaoAberta) {
							if ($reg['sitantiga'] == C_OrdensSituacaoLiberadaParaFaturamento) echo "Voltou a ordem de serviço";
							else echo "Pausou a ordem de serviço";
						}
						//1 - Em Execução
						else if ($reg['sitnova'] == C_OrdensSituacaoEmExecucao) {
							if ($reg['sitantiga'] == C_OrdensSituacaoEmExecucao) echo "Abriu a ordem de serviço com atendimento " . OrdensAtendimento($ordem->atendimento) . ".";
							elseif ($reg['sitantiga'] == C_OrdensSituacaoAberta) echo "Re-abriu a ordem de serviço.";
							else echo "Alterou a situação do chamado para 'Em execução'";
						}
						//2 - Cancelada
						else if ($reg['sitnova'] == C_OrdensSituacaoCancelada) {
							echo "Cancelou a ordem de serviço.";
						}
						//3 - Finalizada
						else if ($reg['sitnova'] == C_OrdensSituacaoFinalizada) {
							echo "Finalizou a ordem de serviço.";
						}
						//4 - Liberada para sincronização
						else if ($reg['sitnova'] == C_OrdensSituacaoLiberadaParaFaturamento) {
							if ($reg['sitantiga'] == C_OrdensSituacaoLiberadaParaFaturamento) echo "gerou a Ordem de Serviço através do Orçamento " . $reg['obs'] . ".";
							else echo "Liberou a ordem de serviço para sincronização.";
						}
						//5 - Sincronizada
						else if ($reg['sitnova'] == C_OrdensSituacaoSincronizadaPeloGrid) {
							echo "A ordem de serviço foi sincronizada pelo Grid.";
						}
						//5 - Faturada
						else if ($reg['sitnova'] == C_OrdensSituacaoFaturada) {
							echo "A ordem de serviço foi faturada.";
						} else if ($reg['sitnova'] == C_OrdensSituacaoAtendInterno) {
							echo "Tipo de atendimento alterado para Interno.";
						} else if ($reg['sitnova'] == C_OrdensSituacaoAtendExterno) {
							echo "Tipo de atendimento alterado para Externo.";
						}
						echo "</p><hr></div>";
					endforeach;
					?>
				</div>
				<div class="tab-pane" id="horas">
					<?php if (sizeof($ordemhoras)) { ?>
						<div class="table-responsive">
							<table class="table table-hover" id="tableHoras">
								<thead class="text-primary">
									<th>Usuário</th>
									<th>Data</th>
									<th>Horário</th>
									<?php if ($admin == 1) echo "<th width='10%'>Ações</th>"; ?>
								</thead>
								<tbody>
									<?php foreach ($ordemhoras as $reg): ?>
										<tr>
											<td><?= $reg->user->username ?></td>
											<td><?= date_format($reg->data, 'd/m/Y'); ?></td>
											<td><?= date_format($reg->horaini, 'H:i') . " - " . date_format($reg->horafin, 'H:i'); ?></td>
											<td class="td-actions">
												<?= $this->Html->link('<i class="fa fa-times"></i><div class="ripple-container"></div>', ['controller' => 'ordemhoras', "action" => "delete", $reg->id, 1], ['confirm' => 'Corfirmar deleção?', 'rel' => 'tooltip', 'title' => 'Excluir', 'class' => 'btn btn-danger btn-simple btn-xs', 'escape' => false]) ?>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php } else echo "<br><center><h4>Nenhum registro de horas encontrado</h4></center><br>"; ?>
				</div>
				<div class="tab-pane" id="parcelas">
					<?php if ($ordem->situacao == C_OrdensSituacaoAberta || $ordem->situacao == C_OrdensSituacaoEmExecucao) { ?>
						<?= $this->Form->create(null, ['class' => 'form-material', 'url' => ['controller' => 'Ordemparcelas', 'action' => 'add', $ordem->id]]); ?>
						<div class="row">
							<div class="col-md-4 col-xs-12">
								<label class="control-label">Pagamento</label>
								<?= $this->Form->control('pagamento', ['options' => C_OrdensPagamento, 'class' => 'form-control os-edit-native-select', 'label' => false, 'required' => true, 'disabled' => $disabled]) ?>
							</div>
							<div class="col-md-3 col-xs-12">
								<div class="custom-control custom-checkbox mr-sm-2 m-r-10 m-l-10 m-t-10">
									<br><?= $this->Form->checkbox('entrada', ['class' => 'custom-control-input', 'id' => 'entrada']); ?>
									<label class="custom-control-label text-muted" for="entrada">Primeira parcela recebida como Entrada</label>
								</div>
							</div>
						</div>
						<br>
						<div class="row">
							<div class="col-md-2 col-xs-6 ">
								<label class="control-label">Parcelas</label>
								<?= $this->Form->control('nmrparcelas', ['id' => 'nmrparcelas', 'options' => C_OrdensParcelas, 'class' => 'form-control os-edit-native-select', 'label' => false, 'required' => true, 'disabled' => $disabled]) ?>
							</div>
							<div class="col-md-2 col-xs-6 ">
								<label class="control-label text-muted dataval1">Parcela 1 </label>
								<?= $this->Form->text('dataval1', ['id' => 'dataval1', 'default' => date('d/m/Y'), 'class' => 'form-control datepicker dataval1', 'label' => false, 'required' => true, 'disabled' => $disabled]) ?>
							</div>
							<div class="col-md-2 col-xs-6 ">
								<label class="control-label text-muted dataval2">Parcela 2 </label>
								<?= $this->Form->text('dataval2', ['id' => 'dataval2', 'default' => date('d/m/Y'), 'class' => 'form-control datepicker dataval2', 'label' => false, 'disabled' => $disabled]) ?>
							</div>
							<div class="col-md-2 col-xs-6 ">
								<label class="control-label text-muted dataval3">Parcela 3 </label>
								<?= $this->Form->text('dataval3', ['id' => 'dataval3', 'default' => date('d/m/Y'), 'class' => 'form-control datepicker dataval3', 'label' => false, 'disabled' => $disabled]) ?>
							</div>
							<div class="col-md-2 col-xs-6 ">
								<label class="control-label text-muted dataval4">Parcela 4 </label>
								<?= $this->Form->text('dataval4', ['id' => 'dataval4', 'default' => date('d/m/Y'), 'class' => 'form-control datepicker dataval4', 'label' => false, 'disabled' => $disabled]) ?>
							</div>
							<div class="col-md-2 col-xs-6 ">
								<label class="control-label text-muted dataval5">Parcela 5 </label>
								<?= $this->Form->text('dataval5', ['id' => 'dataval5', 'default' => date('d/m/Y'), 'class' => 'form-control datepicker dataval5', 'label' => false, 'disabled' => $disabled]) ?>
							</div>
						</div>
						<div class="row">
							<div class="col-md-2 col-md-3 col-xs-6  m-t-20">
								<?= $this->Form->button('Enviar', ['class' => 'btn btn-pgm btn-pgm-salvar btn-primary ']) ?>
							</div>
						</div>
						<div class="clearfix"></div>
						<?= $this->Form->end(); ?>
						<hr>
					<?php } ?>
					<div class="table-responsive">
						<?php if (isset($ordemparcelas)) { ?>
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
									<th class="sideh" width='10%'>Parcelas:</th>
									<th>Parcela 1</th>
									<?php if ($ordemparcelas->nmrparcelas > 1) echo "<th>Parcela 2</th>"; ?>
									<?php if ($ordemparcelas->nmrparcelas > 2) echo "<th>Parcela 3</th>"; ?>
									<?php if ($ordemparcelas->nmrparcelas > 3) echo "<th>Parcela 4</th>"; ?>
									<?php if ($ordemparcelas->nmrparcelas > 4) echo "<th>Parcela 5</th>"; ?>
								</thead>
								<tbody>
									<tr>
										<td class="sideh">Valor:</td>
										<td class="valorparcela"></td>
										<?php if ($ordemparcelas->nmrparcelas > 1) echo "<td class='valorparcela'></td>"; ?>
										<?php if ($ordemparcelas->nmrparcelas > 2) echo "<td class='valorparcela'></td>"; ?>
										<?php if ($ordemparcelas->nmrparcelas > 3) echo "<td class='valorparcela'></td>"; ?>
										<?php if ($ordemparcelas->nmrparcelas > 4) echo "<td class='valorparcela'></td>"; ?>
									</tr>
									<tr>
										<td class="sideh">Data:</td>
										<td><?= date_format($ordemparcelas->dataval1, 'd/m/Y'); ?></td>
										<?php if ($ordemparcelas->nmrparcelas > 1) echo "<td>" . date_format($ordemparcelas->dataval2, 'd/m/Y') . "</td>"; ?>
										<?php if ($ordemparcelas->nmrparcelas > 2) echo "<td>" . date_format($ordemparcelas->dataval3, 'd/m/Y') . "</td>"; ?>
										<?php if ($ordemparcelas->nmrparcelas > 3) echo "<td>" . date_format($ordemparcelas->dataval4, 'd/m/Y') . "</td>"; ?>
										<?php if ($ordemparcelas->nmrparcelas > 4) echo "<td>" . date_format($ordemparcelas->dataval5, 'd/m/Y') . "</td>"; ?>
									</tr>
								</tbody>
							</table>
							<?php if ($ordem->situacao == C_OrdensSituacaoAberta || $ordem->situacao == C_OrdensSituacaoEmExecucao) { ?>
								<?= $this->Html->link('Excluir', ["controller" => "Ordemparcelas", "action" => "delete", $ordemparcelas->id], ['class' => 'btn btn-danger m-t-20']); ?>
							<?php } ?>
						<?php } else echo "<br><center><h4>Nenhum registro de parcelas encontrado</h4></center><br>"; ?>
					</div>
				</div>
			</div>
			<div class="clearfix"></div>
		</div>
</div>
<!-- Modal Observacao -->
<div class="modal fade none-border" id="modal-observacao">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title">Detalhes do Item</h4>
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
			</div>
			<div class="modal-body">
				<div class="row m-20 m-b-0 form-material">
					<div class="col-12">
						<div class="form-group">
							<label class="control-label m-b-0">Modelo</label>
							<?= $this->Form->control('modelomodal', ['placeholder' => 'Insira o modelo do item', 'id' => 'modelomodal', 'class' => 'form-control', 'label' => false]); ?>
						</div>
					</div>
					<div class="col-12">
						<div class="form-group">
							<label class="control-label m-b-0">Serial Number (N/S)</label>
							<?= $this->Form->text('serialnumbermodal', ['list' => 'listaSN', 'maxlength' => 100, 'placeholder' => 'Insira o serial number', 'id' => 'serialnumbermodal', 'class' => 'form-control', 'label' => false]); ?>
							<datalist id="listaSN"> </datalist>
						</div>
					</div>
					<div class="col-12">
						<div class="form-group">
							<label class="control-label m-b-0">Product key</label>
							<?= $this->Form->text('productkeymodal', ['list' => 'listaPk', 'maxlength' => 100, 'placeholder' => 'Insira a chave do produto', 'id' => 'productkeymodal', 'class' => 'form-control', 'label' => false]); ?>
							<datalist id="listaSN"> </datalist>
						</div>
					</div>
					<div class="col-12">
						<div class="form-group">
							<label class="control-label m-b-0">Observação NF-e</label>
							<?= $this->Form->textarea('observacaomodal', ['placeholder' => 'Insira a observação do item', 'id' => 'observacaomodal', 'class' => 'form-control', 'label' => false]); ?>
						</div>
					</div>
					<div class="col-12">
						<div class="form-group">
							<label class="control-label m-b-0">Observação Interna</label>
							<?= $this->Form->textarea('observacainternaomodal', ['placeholder' => 'Insira a observação do item', 'id' => 'observacainternaomodal', 'class' => 'form-control', 'label' => false]); ?>
						</div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<?= $this->Html->link('Salvar Detalhes', ['#'], ['class' => 'btn btn-pgm btn-pgm-salvar btn-success text-white btn-observacao m-l-5']) ?>
				<button type="button" class="btn btn-danger waves-effect" data-dismiss="modal">Fechar</button>
			</div>
		</div>
	</div>
</div>
<!-- Modal Serial Number -->
<div class="modal fade none-border" id="modal-serialnumber">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="row m-20 m-b-0 form-material">
				<div class="col-12">
					<div class="form-group ">
						<label class="control-label">Serial Number</label>
						<?= $this->Form->text('serialnumbermodal', ['list' => 'listaSN', 'maxlength' => 100, 'placeholder' => 'Insira o serial number do item', 'id' => 'serialnumbermodal', 'class' => 'form-control', 'label' => false]); ?>
						<datalist id="listaSN"> </datalist>
						<small>*Informe apenas um Serial Number. Para mais códigos, adicione o produto novamente.</small>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<?= $this->Html->link('Salvar serial number', ['#'], ['class' => 'btn btn-pgm btn-pgm-salvar btn-success text-white btn-serialnumber m-l-5']) ?>
				<button type="button" class="btn btn-danger waves-effect" data-dismiss="modal">Fechar</button>
			</div>
		</div>
	</div>
</div>
<div class="modal fade" id="modal-pesquisa-produto" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title">Pesquisar Produto</h4>
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="col-md-12">
						<div class="input-group">
							<input type="text" id="termo-pesquisa-produto" class="form-control" placeholder="Digite o nome ou código do produto...">
							<span class="input-group-btn">
								<button class="btn btn-pgm btn-pgm-situacao btn-info" type="button" onclick="buscarProdutos()">Pesquisar</button>
							</span>
						</div>
					</div>
				</div>
				<br>
				<div class="table-responsive">
					<table class="table table-hover table-bordered">
						<thead>
							<tr>
								<th>Código</th>
								<th>Descrição</th>
								<th>Preço</th>
								<th>Ação</th>
							</tr>
						</thead>
						<tbody id="resultado-pesquisa-produtos">
						</tbody>
					</table>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-danger" data-dismiss="modal">Fechar</button>
			</div>
		</div>
	</div>
</div>
<div class="modal fade" id="modal-dados-legado" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title">Dados do Equipamento (Registro Antigo)</h4>
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="col-md-12">
						<div class="form-group">
							<label class="control-label text-info">Modelo</label>
							<input type="text" class="form-control" value="<?= $ordem->modelo ?>" readonly>
						</div>
					</div>
					<div class="col-md-12">
						<div class="form-group">
							<label class="control-label text-info">Número de Série</label>
							<input type="text" class="form-control" value="<?= $ordem->nmrserie ?>" readonly>
						</div>
					</div>
				</div>
				<div class="alert alert-warning m-t-10">
					<small>
						<i class="fa fa-exclamation-circle"></i>
						<strong>Atenção:</strong> Estes dados são de um cadastro antigo onde as informações ficavam na capa da OS.
						Para editar ou adicionar novos equipamentos, utilize a tabela de "Produtos/Serviços" abaixo.
					</small>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
			</div>
		</div>
	</div>
</div>

<script>
	var osTiposComEstoqueErp = <?= json_encode($osTiposComEstoqueErp) ?>;
	var osEstoquesLoteUrl = <?= json_encode(Router::url(['controller' => 'Produtos', 'action' => 'estoquesLote']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
	// --- Configurações Iniciais ---
	var pgmAuthIdempresa = <?= json_encode((int)($authIdempresa ?? 0)); ?>;
	function pgmEmpresaAtualAjax() {
		var v = $('#empresaSidebar').val();
		if (v !== undefined && v !== null && v !== '') {
			var n = parseInt(String(v), 10);
			if (!isNaN(n) && n > 0) {
				return n;
			}
		}
		return pgmAuthIdempresa;
	}
	var idcliente = $("#idcliente").val();
	var idsolicitante = $("#idsolicitante").val();

	$(document).ready(function() {
		$('#idEmpresaAtual').val(pgmEmpresaAtualAjax());
		$('#idsolicitante').append("<option value='' >Indefinido</option>");
		$('.dataval2, .dataval3, .dataval4, .dataval5').hide();

		loadSolicitantes($('#idcliente').val());

		var currentSolicitanteOutros = '<?= $ordem->solicitante_outros ?>';
		var currentSolicitante = '<?= $ordem->idsolicitante ?>';

		if (currentSolicitante == 0 && currentSolicitanteOutros) {
			$('#solicitante-outros-container').show();
			$('#solicitante-outros').val(currentSolicitanteOutros);
		}
		$('#idsolicitante').selectpicker("refresh");
	});

	$("#idcliente").change(function() {
		var idcliente = $(this).val();
		loadSolicitantes(idcliente);
		loadCliTelemail(idcliente);
		$.ajax({
			url: "<?= Router::url(['controller' => 'Clientes', 'action' => 'contrato']); ?>/" + idcliente,
			success: function(data) {
				if (data == 1) $('#contrato').val(1);
				else $('#contrato').val(0);
			},
		});
	});

	function loadSolicitantes(idcliente) {
		$.ajax({
			dataType: "json",
			url: "<?= Router::url(['controller' => 'Clientes', 'action' => 'solicitantes']); ?>/" + idcliente,
			success: function(data) {
				$('#idsolicitante').find('option').remove().end();
				$('#idsolicitante').append($('<option>', {
					value: 0,
					text: 'Outros'
				}));
				$.each(data, function(key, array) {
					$('#idsolicitante').append($('<option>', {
						value: key,
						text: array
					}));
				});

				var currentSolicitante = '<?= $ordem->idsolicitante ?>';
				var currentSolicitanteOutros = '<?= $ordem->solicitante_outros ?>';

				if (currentSolicitante == 0 && currentSolicitanteOutros) {
					$('#idsolicitante').val(0);
					$('#solicitante-outros-container').show();
					$('#solicitante-outros').val(currentSolicitanteOutros);
				} else {
					$('#idsolicitante').val(currentSolicitante);
					$('#solicitante-outros-container').hide();
				}

				$('#idsolicitante').selectpicker("refresh");

				if ($("#idsolicitante").val() != null && $("#idsolicitante").val() != 0) {
					loadSolTelemail($("#idsolicitante").val());
				} else {
					loadCliTelemail($("#idcliente").val());
				}
			},
		});
	}

	$("#idsolicitante").change(function() {
		if ($(this).val() == 0) {
			$('#solicitante-outros-container').show();
			$('#solicitante-outros').focus();
			$('.clienteTelemail').show();
			$('.email, .telefone, .celular').val('');
		} else {
			$('#solicitante-outros-container').hide();
			$('#solicitante-outros').val('');
			loadSolTelemail($(this).val());
		}
	});

	function loadCliTelemail(idcliente) {
		$.ajax({
			dataType: "json",
			type: "get",
			url: "<?= Router::url(array('controller' => 'Clientes', 'action' => 'cliemail')); ?>/" + idcliente,
			success: function(data) {
				$('.clienteTelemail').show();
				$('.email').val(data.email);
				$('.telefone').val(data.fone);
				$('.celular').val(data.fone2);
			},
		});
	}

	function loadSolTelemail(idsolicitante) {
		if (idsolicitante == 0) return;
		$.ajax({
			dataType: "json",
			url: "<?= Router::url(['controller' => 'Clientes', 'action' => 'solemail']); ?>/" + idsolicitante,
			success: function(data) {
				$('.clienteTelemail').show();
				$('.email').val(data.email);
				$('.telefone').val(data.telefone);
				$('.celular').val(data.celular);
			},
		});
	}


	var urlLoadData = "<?= Router::url(['controller' => 'Ordensservico', 'action' => 'carrinho', $ordem->id]); ?>";
	var urlAdd = "<?= Router::url(['controller' => 'Ordensservico', 'action' => 'carrinhoadd', $ordem->id]); ?>";
	var urlEdit = "<?= Router::url(['controller' => 'Ordensservico', 'action' => 'carrinhoedititem']); ?>";
	var urlDelete = "<?= Router::url(['controller' => 'Ordensservico', 'action' => 'carrinhodelitem']); ?>";
	var osGridAjaxVerbose = <?= !empty($osGridAjaxVerbose) ? 'true' : 'false' ?>;
	function pgmOsGridEscapeHtml(s) {
		if (s == null || s === '') return '';
		return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
	}
	function pgmOsGridExplainXhr(xhr, defaultMsg) {
		var parts = [];
		parts.push('<p><strong>' + pgmOsGridEscapeHtml(defaultMsg || 'Erro na requisição.') + '</strong></p>');
		parts.push('<p>HTTP <code>' + pgmOsGridEscapeHtml(String(xhr.status || '?')) + '</code>' +
			(xhr.statusText ? ' — ' + pgmOsGridEscapeHtml(xhr.statusText) : '') + '</p>');
		var j = xhr.responseJSON;
		if (j) {
			if (j.code) parts.push('<p><strong>Código:</strong> ' + pgmOsGridEscapeHtml(j.code) + '</p>');
			if (j.msg) parts.push('<p>' + pgmOsGridEscapeHtml(j.msg) + '</p>');
			if (j.warning) parts.push('<p><strong>Aviso:</strong> ' + pgmOsGridEscapeHtml(j.warning) + '</p>');
			if (osGridAjaxVerbose && j.debug) {
				parts.push('<pre class="pgm-pre-json pgm-pre-json--wrap">' +
					pgmOsGridEscapeHtml(JSON.stringify(j.debug, null, 2)) + '</pre>');
			}
			if (osGridAjaxVerbose && j.validation) {
				parts.push('<pre class="pgm-pre-json pgm-pre-json--wrap">' +
					pgmOsGridEscapeHtml(JSON.stringify(j.validation, null, 2)) + '</pre>');
			}
		} else if (xhr.responseText) {
			var t = xhr.responseText;
			if (t.length < 800) {
				parts.push('<pre class="pgm-pre-json pgm-pre-json--wrap pgm-pre-json--h200">' +
					pgmOsGridEscapeHtml(t) + '</pre>');
			} else {
				parts.push('<p>' + pgmOsGridEscapeHtml(t.substring(0, 300)) + '…</p>');
			}
		}
		return parts.join('');
	}
	function pgmOsGridAlertHtml(html) {
		if (typeof bootbox !== 'undefined') {
			bootbox.alert({ message: html });
		} else {
			alert($('<div/>').html(html).text());
		}
	}

	var tiposOpt = <?= $tiposOpt ?>;
	var produtosOpt = <?= $produtosOpt ?>;
	produtosOpt.sort(function(a, b) {
		if (a.descricao < b.descricao) return -1;
		if (a.descricao > b.descricao) return 1;
		return 0;
	});

	var editing = <?= $disabled == true ? 'false' : 'true' ?>;

	$('#grid_table').jsGrid({
		width: "100%",
		height: "800px",
		filtering: false,
		inserting: editing,
		editing: editing,
		sorting: true,
		paging: true,
		autoload: true,
		pageSize: 10,
		pageButtonCount: 5,
		deleteConfirm: "Tem certeza que deseja remover o item?",
		noDataContent: "Nenhum item adicionado",
		invalidNotify: function(args) {
			var lines = $.map(args.errors || [], function(e) {
				return (e.field && e.field.name ? e.field.name + ': ' : '') + (e.message || '');
			}).filter(Boolean);
			var msg = '<p><strong>Preencha os campos obrigatórios do item.</strong></p>';
			if (lines.length) {
				msg += '<ul><li>' + lines.map(pgmOsGridEscapeHtml).join('</li><li>') + '</li></ul>';
			}
			pgmOsGridAlertHtml(msg);
		},

		controller: {
			loadData: function() {
				return $.ajax({
					url: urlLoadData,
					dataType: "json",
					success: function(result) {
						var urlTotal = "<?= Router::url(['controller' => 'Ordensservico', 'action' => 'valortotal', $ordem->id]); ?>";
						$.ajax({
							url: urlTotal,
							dataType: "json",
							success: function(data) {
								var valortotal = parseFloat(data.valortotal);
								$('#valortotalordem').val(valortotal);
								var $celulas = $('.valorparcela');
								var numParcelas = $celulas.length;

								if (numParcelas === 0) {
									numParcelas = parseInt($('#nmrparcelas').val()) || 1;
								}

								if (valortotal > 0 && numParcelas > 0) {
									var valorBase = Math.floor((valortotal / numParcelas) * 100) / 100;
									var diferenca = valortotal - (valorBase * numParcelas);
									diferenca = Math.round(diferenca * 100) / 100; 
									$celulas.each(function(index) {
										var valorFinal = 0;
										if (index === 0) {
											valorFinal = valorBase + diferenca;
										} else {
											valorFinal = valorBase;
										}
										$(this).text('R$ ' + numberToReal(valorFinal));
									});
								} else {
									$celulas.text('R$ 0,00');
								}
								$('.valortotalordem').html('<font color="#212529"> Total geral:</font> R$ ' + numberToReal(valortotal));
								if (data && data.warning === 'sessao_carrinho' && data.msg) {
									console.warn('[OS grid valortotal]', data.msg);
								}
								setTimeout(function() {
									tdcommuitotexto();
								}, 500);
							},
							error: function(xhr) {
								pgmOsGridAlertHtml(pgmOsGridExplainXhr(xhr, 'Não foi possível obter o valor total da ordem.'));
							}
						});
					},
					error: function(xhr) {
						pgmOsGridAlertHtml(pgmOsGridExplainXhr(xhr, 'Não foi possível carregar os itens da ordem.'));
					}
				});
			},
			insertItem: function(item) {
				if (item && typeof item === 'object') {
					delete item.id;
				}
				item['idEmpresaAtual'] = pgmEmpresaAtualAjax();
				return $.ajax({
					type: "POST",
					dataType: "json",
					url: urlAdd + '/' + encodeURIComponent(item.codproduto),
					data: item,
					success: function(data) {
						if (data && data.ok === true) {
							$("#grid_table").jsGrid("loadData");
							return;
						}
						if (typeof data === 'string') {
							var t = $.trim(data);
							if (t === 'boa') {
								$("#grid_table").jsGrid("loadData");
								return;
							}
							if (t === 'naopode') {
								bootbox.alert('Este produto já foi adicionado.');
								$("#grid_table").jsGrid("loadData");
								return;
							}
						}
						if (data && typeof data === 'object' && data.ok === false) {
							if (data.code === 'os_grid_produto_duplicado' && data.msg) {
								bootbox.alert(pgmOsGridEscapeHtml(data.msg));
								$("#grid_table").jsGrid("loadData");
								return;
							}
							var p = ['<p><strong>' + pgmOsGridEscapeHtml('Não foi possível adicionar o item.') + '</strong></p>'];
							if (data.code) p.push('<p><strong>Código:</strong> ' + pgmOsGridEscapeHtml(data.code) + '</p>');
							if (data.msg) p.push('<p>' + pgmOsGridEscapeHtml(data.msg) + '</p>');
							if (osGridAjaxVerbose && data.debug) {
								p.push('<pre class="pgm-pre-json">' +
									pgmOsGridEscapeHtml(JSON.stringify(data.debug, null, 2)) + '</pre>');
							}
							if (osGridAjaxVerbose && data.validation) {
								p.push('<pre class="pgm-pre-json">' +
									pgmOsGridEscapeHtml(JSON.stringify(data.validation, null, 2)) + '</pre>');
							}
							pgmOsGridAlertHtml(p.join(''));
							$("#grid_table").jsGrid("loadData");
							return;
						}
						var snippet = (data === null || data === undefined) ? '(resposta vazia)' : (typeof data === 'string' ? data : JSON.stringify(data));
						pgmOsGridAlertHtml('<p><strong>Resposta inesperada ao incluir item.</strong></p><pre class="pgm-pre-json pgm-pre-json--wrap">' +
							pgmOsGridEscapeHtml(String(snippet).substring(0, 1500)) + '</pre>');
						$("#grid_table").jsGrid("loadData");
					},
					error: function(xhr) {
						pgmOsGridAlertHtml(pgmOsGridExplainXhr(xhr, 'Não foi possível adicionar o item.'));
						$("#grid_table").jsGrid("loadData");
					}
				});
			},
			updateItem: function(item) {
				item['idEmpresaAtual'] = pgmEmpresaAtualAjax();
				return $.ajax({
					type: "PUT",
					url: urlEdit,
					data: item,
					success: function(data) {
						$("#grid_table").jsGrid("loadData");
					},
					error: function(xhr) {
						pgmOsGridAlertHtml(pgmOsGridExplainXhr(xhr, 'Não foi possível atualizar o item.'));
						$("#grid_table").jsGrid("loadData");
					}
				});
			},
			deleteItem: function(item) {
				item['idEmpresaAtual'] = pgmEmpresaAtualAjax();
				return $.ajax({
					type: "DELETE",
					url: urlDelete,
					data: item,
					success: function() {
						$("#grid_table").jsGrid("loadData");
					},
					error: function(xhr) {
						pgmOsGridAlertHtml(pgmOsGridExplainXhr(xhr, 'Não foi possível remover o item.'));
						$("#grid_table").jsGrid("loadData");
					}
				});
			},
		},

		fields: [{
				name: "codprodutosoocod",
				title: "cod",
				type: "text",
				width: 0,
				css: 'hide td-codproduto-soocod',
				editing: false
			},
			{
				name: "id",
				title: "id",
				type: "text",
				css: 'hide',
				visible: false,
				editing: false
			},
			{
				name: "tipo",
				title: "Tipo",
				type: "select",
				width: 96,
				items: tiposOpt,
				editing: false,
				insertcss: 'cellInput inputTipo',
				editcss: "editTipo"
			},
			{
				name: "codproduto",
				title: "Código do Produto",
				type: "text",
				width: 188,
				css: 'inputCodproduto',
				validate: "required",
				itemTemplate: function(value) {
					var item = produtosOpt.find(function(produto) {
						return produto.codigo == value;
					});
					return item ? item.descricao : value;
				},
				insertTemplate: function() {
					var $input = $("<input>").addClass("form-control input-codigo-val").prop("readonly", true);
					var $btn = $("<button>").attr("type", "button").addClass("btn btn-secondary btn-sm").html('<i class="fa fa-search"></i>');

					$btn.on("click", function() {
						window.activeInputCode = $input;
						$('#termo-pesquisa-produto').val('');
						$('#resultado-pesquisa-produtos').html('');
						$('#modal-pesquisa-produto').modal('show');
						buscarProdutos();
						setTimeout(function() {
							$('#termo-pesquisa-produto').focus();
						}, 500);
					});

					var $group = $("<div>").addClass("input-group").append($input).append(
						$("<div>").addClass("input-group-append").append($btn)
					);
					var $estoque = $("<small>").addClass("qtdEstoque d-block").html("&nbsp;");
					this.insertControl = $input;
					return $("<div>").append($group).append($estoque);
				},
				insertValue: function() {
					return this.insertControl.val();
				},

				editTemplate: function(value) {
					var $input = $("<input>").addClass("form-control input-codigo-val").prop("readonly", true).val(value);
					var $btn = $("<button>").attr("type", "button").addClass("btn btn-secondary btn-sm").html('<i class="fa fa-search"></i>');

					$btn.on("click", function() {
						window.activeInputCode = $input;
						$('#termo-pesquisa-produto').val('');
						$('#resultado-pesquisa-produtos').html('');
						$('#modal-pesquisa-produto').modal('show');
						buscarProdutos();
						setTimeout(function() {
							$('#termo-pesquisa-produto').focus();
						}, 500);
					});

					var $group = $("<div>").addClass("input-group").append($input).append(
						$("<div>").addClass("input-group-append").append($btn)
					);

					// --- CORREÇÃO: Cria o elemento para mostrar o estoque ---
					var $estoque = $("<small>").addClass("qtdEstoque text-info font-weight-bold d-block").html("&nbsp;");

					this.editControl = $input;

					// Retorna o grupo + o label de estoque envolvidos em uma div
					return $("<div>").append($group).append($estoque);
				},
				editValue: function() {
					return this.editControl.val();
				}
			},
			{
				name: "descricao",
				title: "Descrição",
				type: "text",
				width: "auto",
				editing: false,
				headercss: "os-col-desc",
				css: "os-cell-desc",
				insertcss: "cellInput inputDescricao os-cell-desc",
				editcss: "editDescricao os-cell-desc"
			},

			{
				name: "observacao",
				title: "Referenciar",
				type: "text",
				align: "center",
				width: 118,
				itemTemplate: function(value, item) {
					return $("<div>")
						.addClass("btn btn-sm btn-link os-edit-ref-link")
						.html('<i class="fa fa-file-text-o"></i> Referenciar')
						.on("click", function(e) {
							e.stopPropagation();
							$('#observacaomodal').val(item.observacao);
							$('#modelomodal').val(item.modelo);
							$('#serialnumbermodal').val(item.serialnumber);
							$('#productkeymodal').val(item.productkey);
							$('#observacainternaomodal').val(item.obsinterna);
							var disabled = <?= $disabled ? 'true' : 'false' ?>;

							if (disabled) {
								$('.btn-observacao').hide();
								$('.modal-title').text('Detalhes do Item (Apenas Leitura)');
								$('#modal-observacao input, #modal-observacao textarea').prop('readonly', true);
							} else {
								window.currentRowContext = null;
								$('.btn-observacao').show();
								$('.modal-title').text('Detalhes do Item');
								$('#modal-observacao input, #modal-observacao textarea').prop('readonly', false);
							}
							$('#modal-observacao').modal('show');
						});
				},
				// Mantém os templates de inserção e edição originais para quando a ordem ESTIVER aberta
				insertTemplate: function() {
					return this.insertControl = $("<input>").prop("type", "text").val("").addClass('form-control btn-modal-obs').attr('placeholder', 'Clique para editar');
				},
				editTemplate: function(value) {
					return this.editControl = $("<input>").prop("type", "text").val(value).addClass('form-control btn-modal-obs');
				},
				insertValue: function() {
					return this.insertControl.val();
				},
				editValue: function() {
					return this.editControl.val();
				}
			},

			{
				name: "unidade",
				title: "Unidade",
				type: "text",
				width: 56,
				editing: false,
				insertcss: 'cellInput inputUnidade',
				editcss: "editUnidade"
			},
			{
				name: "quantidade",
				title: "Qtde",
				type: "text",
				width: 60,
				insertcss: 'cellInput inputQuantidade',
				editcss: "editQuantidade"
			},
			{
				name: "valorunitario",
				title: "Vl. Unit.",
				type: "text",
				width: 88,
				insertcss: 'cellInput inputValorunitario',
				editcss: "editValorunitario"
			},
			{
				name: "valordesconto",
				title: "Desc.",
				type: "text",
				width: 88,
				insertcss: 'cellInput inputValordesconto',
				editcss: "editValordesconto"
			},
			{
				name: "valortotal",
				title: "Total",
				type: "text",
				width: 82,
				editing: true,
				insertcss: 'cellInput inputValortotal',
				editcss: "editValortotal"
			},
			{
				name: "modelo",
				type: "text",
				css: 'hide',
				width: 0,
				insertTemplate: function() {
					return this.insertControl = $("<input>").addClass('classe-modelo-input');
				},
				editTemplate: function(value) {
					return this.editControl = $("<input>").val(value).addClass('classe-modelo-input');
				},
				insertValue: function() {
					return this.insertControl.val();
				},
				editValue: function() {
					return this.editControl.val();
				}
			},
			{
				name: "serialnumber",
				type: "text",
				css: 'hide',
				width: 0,
				insertTemplate: function() {
					return this.insertControl = $("<input>").addClass('classe-serial-input');
				},
				editTemplate: function(value) {
					return this.editControl = $("<input>").val(value).addClass('classe-serial-input');
				},
				insertValue: function() {
					return this.insertControl.val();
				},
				editValue: function() {
					return this.editControl.val();
				}
			},
			{
				name: "productkey",
				type: "text",
				css: "hide",
				width: 0,
				insertTemplate: function() {
					return this.insertControl = $("<input>").addClass('classe-pk-input');
				},
				editTemplate: function(value) {
					return this.editControl = $("<input>").val(value).addClass('classe-pk-input');
				},
				insertValue: function() {
					return this.insertControl.val();
				},
				editValue: function() {
					return this.editControl.val();
				}
			},
			{
				name: "obsinterna",
				type: "text",
				css: "hide",
				width: 0,
				insertTemplate: function() {
					return this.insertControl = $("<input>").addClass('classe-obsinterna-input');
				},
				editTemplate: function(value) {
					return this.editControl = $("<input>").val(value).addClass('classe-obsinterna-input');
				},
				insertValue: function() {
					return this.insertControl.val();
				},
				editValue: function() {
					return this.editControl.val();
				}
			},

			{
				type: "control",
				width: 64,
				deleteButton: editing
			}
		],
		onRefreshed: function(args) {
			$(".jsgrid-select2").select2();
			var $g = $('#grid_table');
			$g.css({ width: '100%', maxWidth: '100%', minWidth: 0 });
			$g.find('.jsgrid-grid-header, .jsgrid-grid-body').css({ width: '100%', minWidth: 0 });
		}
	});
	$('#grid_table').on('keydown', 'input, select, textarea', function (e) {
		if (e.key === 'Enter' || e.which === 13) {
			e.preventDefault();
			return false;
		}
	});

	window.currentRowContext = null;
	$(document).on("click", ".btn-modal-obs", function(e) {
		e.stopPropagation();
		window.currentRowContext = $(this).closest('tr');

		var obs = $(this).val();
		var mod = window.currentRowContext.find('.classe-modelo-input').val();
		var sn = window.currentRowContext.find('.classe-serial-input').val();
		var pk = window.currentRowContext.find('.classe-pk-input').val();
		var obsInt = window.currentRowContext.find('.classe-obsinterna-input').val();


		$('#observacaomodal').val(obs);
		$('#modelomodal').val(mod);
		$('#serialnumbermodal').val(sn);
		$('#productkeymodal').val(pk);
		$('#observacainternaomodal').val(obsInt)
		var codProd = "";
		if (window.currentRowContext.hasClass('jsgrid-edit-row')) {
			codProd = window.currentRowContext.find('.td-codproduto-soocod').text();
		} else {
			codProd = $('.inputCodproduto > select').val();
		}
		if (codProd) serialnumbers(codProd);

		$('#modal-observacao').modal('show');
	});

	// Caso necessite retornar como era antes sem a concatenação do product key na obsNfe
	/*     $(document).on("click", ".btn-observacao", function(e){ 
	        e.preventDefault();
	        
	        if(window.currentRowContext){
	            var obs = $('#observacaomodal').val();
	            var mod = $('#modelomodal').val();
	            var sn  = $('#serialnumbermodal').val();
				var pk  = $('#productkeymodal').val();
				var obsInt = $('#observacainternaomodal').val();
	            window.currentRowContext.find('.btn-modal-obs').val(obs).trigger('change');
	            window.currentRowContext.find('.classe-modelo-input').val(mod).trigger('change');
	            window.currentRowContext.find('.classe-serial-input').val(sn).trigger('change');
				window.currentRowContext.find('.classe-pk-input').val(pk).trigger('change');
				window.currentRowContext.find('.classe-obsinterna-input').val(obsInt).trigger('change');
	        }
	        
	        $('#modal-observacao').modal('hide');
	    });
	 */


	/* Para concatenar product key em obs nfe */

	$(document).on("click", ".btn-observacao", function(e) {
		e.preventDefault();
		if (window.currentRowContext) {
			var obs = $('#observacaomodal').val();
			var mod = $('#modelomodal').val();
			var sn = $('#serialnumbermodal').val();
			var pk = $('#productkeymodal').val();
			var obsInt = $('#observacainternaomodal').val();
			if (pk && pk.trim() !== "") {
				var textoPK = "Product Key: " + pk;
				if (obs.indexOf(pk) === -1) {
					if (obs.trim() !== "") {
						obs += " - ";
					}
					obs += textoPK;
				}
			}
			window.currentRowContext.find('.btn-modal-obs').val(obs).trigger('change');
			window.currentRowContext.find('.classe-modelo-input').val(mod).trigger('change');
			window.currentRowContext.find('.classe-serial-input').val(sn).trigger('change');
			window.currentRowContext.find('.classe-pk-input').val(pk).trigger('change');
			window.currentRowContext.find('.classe-obsinterna-input').val(obsInt).trigger('change');
		}

		$('#modal-observacao').modal('hide');
	});

	function numberToReal(numero) {
		if (!isNaN(numero)) {
			var numero = numero.toFixed(2).split('.');
			numero[0] = numero[0].split(/(?=(?:...)*$)/).join('.');
			return numero.join(',');
		}
	}

	function serialnumbers(codproduto) {
		$('#listaSN').html('');
		$.ajax({
			url: "<?= Router::url(['controller' => 'Produtos', 'action' => 'serialnumberproduto']); ?>/" + codproduto,
			dataType: "json",
			success: function(data) {
				$.each(data, function(key, reg) {
					$('#listaSN').append('<option value="' + reg.sSerialNumber + '">');
				})
			}
		});
	}

	<?php if (isMobile()) { ?>
		$(document).on('change', '#tipo', function() {
			var url = "<?= Router::url(array('controller' => 'Produtos', 'action' => 'produtostipo')); ?>" + '/' + $(this).val();
			$.ajax({
				url: url,
				dataType: "json",
				success: function(data) {
					$('.inputCodproduto > option').remove();
					$.each(data, function(key, array) {
						$('.inputCodproduto > select').append($('<option>', {
							value: key,
							text: array
						}));
					})
				}
			});
		});
		$(document).on('change', '#codproduto', function() {
			var url = "<?= Router::url(array('controller' => 'Produtos', 'action' => 'produto')); ?>" + '/' + $(this).val();
			if ($('#tipo').val() != '') url = url + '/' + $('#tipo').val();
			$.ajax({
				url: url,
				dataType: "json",
				success: function(data) {
					$('#tipo').val(data.tipo);
					$('#descricao').val(data.descricao);
					$('#unidade').val(data.unidade);
					$('#valorunitario').val(numberToReal(data.vlunitario));
					$("#quantidade").val("");
					$("#valortotal").val("");
					$("#valordesconto").val("");
					$("#serialnumber").val("");
					serialnumbers(data.codigo)
				}
			});
		});
		$(document).on('change', '#tipo', function() {
			calculoAddMobile();
		});
		$(document).on('change', '#codproduto', function() {
			calculoAddMobile();
		});
		$(document).on('change', '#quantidade', function() {
			calculoAddMobile();
		});
		$(document).on('change', '#valordesconto', function() {
			calculoAddMobile();
		});
		$(document).on('change', '#valorunitario', function() {
			calculoAddMobile();
		});

		function calculoAddMobile() {
			var qtde = $('#quantidade') == "" ? 0 : $('#quantidade').val();
			var vldesconto = $('#valordesconto') == "" ? 0 : $('#valordesconto').val().replace('.', '').replace(',', '.');
			var vlunidade = $('#valorunitario') == "" ? 0 : $('#valorunitario').val().replace('.', '').replace(',', '.');
			valortotal = qtde * vlunidade - vldesconto;
			$('#valortotal').val(numberToReal(valortotal));
		}

		$(".btn-additem").click(function(e) {
			e.preventDefault();
			$.ajax({
				url: urlAdd,
				type: 'POST',
				contentType: false,
				processData: false,
				data: function() {
					var data = new FormData();
					var j = 1;
					$(".inputMobile").each(function() {
						data.append($(this).attr('id'), $(this).val());
						j++;
					});
					return data;
				}(),
				success: function(result) {
					if (result == 'naopode') bootbox.alert('<p class="text-center pgm-bootbox-msg-md">Este produto já foi adicionado à ordem de serviço, não é possível adicioná-lo novamente.</p>');
					$("#grid_table").jsGrid("loadData");
					$(".inputMobile").each(function() {
						$(this).val('');
					});
				},
			});
		});
	<?php } else { ?>
		$(document).on('change', '.inputQuantidade > input, .inputValordesconto > input, .inputValorunitario > input', function() {
			calculoAdd();
		});

		function calculoAdd() {
			var qtde = $('.inputQuantidade > input').val() || 0;
			var vldesconto = ($('.inputValordesconto > input').val() || "0").replace('.', '').replace(',', '.');
			var vlunidade = ($('.inputValorunitario > input').val() || "0").replace('.', '').replace(',', '.');
			var valortotal = (qtde * vlunidade) - vldesconto;
			$('.inputValortotal > input').val(numberToReal(valortotal));
		}
	<?php } ?>

	// Calculos de Edição
	$(document).on('change', '.editQuantidade > input, .editValorunitario > input, .editValordesconto > input', function() {
		calculoEdit();
	});

	function calculoEdit() {
		var qtde = $('.editQuantidade > input').val() || 0;
		var vldesconto = ($('.editValordesconto > input').val() || "0").replace('.', '').replace(',', '.');
		var vlunidade = ($('.editValorunitario > input').val() || "0").replace('.', '').replace(',', '.');
		var valortotal = (qtde * vlunidade) - vldesconto;
		$('.editValortotal > input').val(numberToReal(valortotal));
	}

	// Máscaras e UI
	$(document).ready(function() {
		$('.inputValordesconto > input, .inputValorunitario > input').addClass('mascaramonetaria');
	});

	// Nmrparcelas 
	$("#nmrparcelas").change(function() {
		var nmrparcelas = $(this).val();
		switch (nmrparcelas) {
			case '1':
				$('.dataval2, .dataval3, .dataval4, .dataval5').hide();
				break;
			case '2':
				$('.dataval3, .dataval4, .dataval5').hide();
				$('.dataval2').show();
				break;
			case '3':
				$('.dataval4, .dataval5').hide();
				$('.dataval2, .dataval3').show();
				break;
			case '4':
				$('.dataval5').hide();
				$('.dataval2, .dataval3, .dataval4').show();
				break;
			case '5':
				$('.dataval2, .dataval3, .dataval4, .dataval5').show();
				break;
			default:
				break;
		}
	});

	// Funções de Texto Longo (evitar .jsgrid-button: interfere no insert/update do jsGrid)
	$('#grid_table').on('click', 'th', function () {
		tdcommuitotexto();
	});

	function tdcommuitotexto() {
		var i = 0;
		$('.jsgrid-cell').each(function() {
			if (!$(this).hasClass('cellInput') && $(this).text().length > 50) {
				$(this).attr('data-textointeiro', $(this).text());
				$(this).html($(this).text().substr(0, 49) + '... <div class="btn btn-sm btn-pgm btn-pgm-situacao btn-primary btn-exapndemuitotexto btn-' + i + '"><i class="fa fa-search"></i></div>');
				i++;
			}
		});
	}
	$(document).on('click', '.btn-exapndemuitotexto', function(e) {
		e.preventDefault();
		bootbox.alert({
			message: $(this).parent().attr('data-textointeiro'),
			size: 'xl'
		});
	});

	$(document).on('change', '.inputCodproduto input.input-codigo-val', function() {
		var $inputAtual = $(this);
		var codigo = $inputAtual.val();

		if (!codigo) return;
		$.ajax({
			url: "<?= Router::url(['controller' => 'Produtos', 'action' => 'produto']) ?>/" + codigo,
			dataType: "json",
			success: function(data) {
				if (data.tipo == <?= C_ProdutosTipoProduto ?>) {
					$(".inputSerialnumber > input, .editSerialnumber > input").prop('disabled', false);
					serialnumbers(data.codigo);
					$.ajax({
						url: "<?= Router::url(['controller' => 'Produtos', 'action' => 'qtdestoque']) ?>" + '/' + data.codigo,
						success: function(qtd) {
							if (qtd != -999) $('.qtdEstoque').text('Qtd. em estoque: ' + qtd);
						},
					});
				} else {
					$('.qtdEstoque').text('⠀⠀⠀');
					$(".inputSerialnumber > input, .editSerialnumber > input").prop('disabled', 'disabled');
				}
				// Preenche os campos da linha do Grid
				var $row = $inputAtual.closest('tr');
				$row.find('.inputTipo select, .editTipo select').val(data.tipo);
				$row.find('.inputDescricao input, .editDescricao input').val(data.descricao);
				$row.find('.inputUnidade input, .editUnidade input').val(data.unidade);
				$row.find('.inputValorunitario input, .editValorunitario input').val(numberToReal(data.vlunitario));
				$row.find('.inputQuantidade input, .editQuantidade input').val("");
				$row.find('.inputValortotal input, .editValortotal input').val("");
				$row.find('.inputValordesconto input, .editValordesconto input').val("");
				if ($row.hasClass('jsgrid-insert-row')) calculoAdd();
				else calculoEdit();
			},
		});
	});


	window.activeInputCode = null;

	$('#termo-pesquisa-produto').on('keypress', function(e) {
		if (e.which === 13) {
			buscarProdutos();
		}
	});

	function buscarProdutos() {
		var termo = $('#termo-pesquisa-produto').val();
		var tbody = $('#resultado-pesquisa-produtos');
		tbody.html('<tr><td colspan="4" class="text-center">Buscando...</td></tr>');

		$.ajax({
			url: "<?= Router::url(['controller' => 'Produtos', 'action' => 'pesquisar']); ?>",
			method: "GET",
			data: {
				termo: termo
			},
			dataType: "json",
			success: function(data) {
				tbody.empty();
				if (data.length > 0) {
					$.each(data, function(index, prod) {
						var tipoInt = parseInt(prod.tipo, 10);
						var precisaEstoque = (osTiposComEstoqueErp || []).indexOf(tipoInt) !== -1;
						var tr = $('<tr>').attr('data-codigo', prod.codigo != null ? String(prod.codigo) : '').attr('data-tipo', prod.tipo != null ? String(prod.tipo) : '')
							.attr('data-estoque-status', precisaEstoque ? 'loading' : 'na');
						tr.append('<td>' + prod.codigo + '</td>');
						tr.append('<td>' + prod.descricao + '</td>');
						tr.append('<td>R$ ' + numberToReal(prod.vlunitario) + '</td>');

						var btn = $('<button>').attr('type', 'button').addClass('btn btn-pgm btn-pgm-salvar btn-success btn-sm btn-os-modal-add').text('Adicionar à OS');
						btn.on('click', function (e) {
							e.preventDefault();
							e.stopPropagation();
							selecionarProduto(prod.codigo);
						});

						tr.append($('<td>').append(btn));
						tbody.append(tr);
					});
					osModalAplicarEstoqueLinhas(data);
				} else {
					tbody.html('<tr><td colspan="4" class="text-center">Nenhum produto encontrado.</td></tr>');
				}
			},
			error: function() {
				tbody.html('<tr><td colspan="4" class="text-center text-danger">Erro ao buscar produtos.</td></tr>');
			}
		});
	}

	function osModalTipoConsultaEstoque(tipo) {
		var t = parseInt(tipo, 10);
		if (isNaN(t)) {
			return false;
		}
		return (osTiposComEstoqueErp || []).indexOf(t) !== -1;
	}

	function osModalAplicarEstoqueLinhas(data) {
		if (!data || !data.length) {
			return;
		}
		if (!osEstoquesLoteUrl) {
			$('#resultado-pesquisa-produtos tr[data-estoque-status="loading"]').attr('data-estoque-status', 'err');
			return;
		}
		var cods = [];
		data.forEach(function (prod) {
			if (!osModalTipoConsultaEstoque(prod.tipo)) {
				return;
			}
			var c = (prod.codigo != null && prod.codigo !== '') ? String(prod.codigo).trim() : '';
			if (c && cods.indexOf(c) === -1) {
				cods.push(c);
			}
		});
		if (!cods.length) {
			return;
		}
		if (cods.length > 150) {
			cods = cods.slice(0, 150);
		}
		$.ajax({
			type: 'POST',
			url: osEstoquesLoteUrl,
			data: { codigos: cods.join(',') },
			dataType: 'json',
			success: function (map) {
				if (!map || typeof map !== 'object' || map.erro) {
					$('#resultado-pesquisa-produtos tr[data-estoque-status="loading"]').attr('data-estoque-status', 'err');
					return;
				}
				$('#resultado-pesquisa-produtos tr').each(function () {
					var $tr = $(this);
					var cod = ($tr.attr('data-codigo') || '').trim();
					if (!cod) {
						return;
					}
					$tr.removeClass('os-pesquisa-produto-sem-estoque');
					$tr.find('.btn-os-modal-add').prop('disabled', false);
					if (!osModalTipoConsultaEstoque($tr.attr('data-tipo'))) {
						return;
					}
					if (map[cod] === undefined || map[cod] === null) {
						$tr.attr('data-estoque-status', 'err');
						return;
					}
					var q = map[cod];
					if (q === 0) {
						$tr.addClass('os-pesquisa-produto-sem-estoque').attr('data-estoque-status', 'zero');
						$tr.find('.btn-os-modal-add').prop('disabled', true);
					} else if (q === -999 || (typeof q === 'number' && q < 0)) {
						$tr.attr('data-estoque-status', 'err');
					} else {
						$tr.attr('data-estoque-status', 'ok');
					}
				});
			},
			error: function () {
				$('#resultado-pesquisa-produtos tr[data-estoque-status="loading"]').attr('data-estoque-status', 'err');
			}
		});
	}

	function selecionarProduto(codigo) {
		var $tr = $('#resultado-pesquisa-produtos tr').filter(function () {
			return String($(this).attr('data-codigo')) === String(codigo);
		});
		var st = $tr.attr('data-estoque-status');
		if (st === 'loading') {
			if (typeof bootbox !== 'undefined') {
				bootbox.alert('Aguarde a consulta de estoque antes de adicionar o item.');
			} else {
				alert('Aguarde a consulta de estoque antes de adicionar o item.');
			}
			return;
		}
		if (st === 'zero') {
			if (typeof bootbox !== 'undefined') {
				bootbox.alert('Este produto está com estoque zerado no ERP e não pode ser incluído na ordem de serviço.');
			} else {
				alert('Este produto está com estoque zerado no ERP e não pode ser incluído na ordem de serviço.');
			}
			return;
		}
		if (st === 'err') {
			if (typeof bootbox !== 'undefined') {
				bootbox.alert('Não foi possível confirmar o estoque deste item. A inclusão não é permitida.');
			} else {
				alert('Não foi possível confirmar o estoque deste item. A inclusão não é permitida.');
			}
			return;
		}
		if (window.activeInputCode) {
			window.activeInputCode.val(codigo);
			$('#modal-pesquisa-produto').modal('hide');
			window.activeInputCode.trigger('change');
		}
	}
</script>