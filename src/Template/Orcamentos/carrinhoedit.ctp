<?php

use Cake\Routing\Router;

error_reporting(E_ERROR | E_PARSE);
?>
<style>
	.titulosessao {
		padding: 0.5rem !important;
	}

	.table td,
	.table th {
		padding: 0.7rem !important;
	}
</style>
<div class="row">
	<div class="col-lg-12">
		<div class="table-responsive">
			<table class="table table-hover table-row-clickable" id="tableCarrinho">
				<thead class="text-primary">
					<th width="5%">Ordem</th>
					<th width="6%">Código</th>
					<th width="20%">Produto/Serviço</th>
					<th width="20%">Descrição</th>
					<th class="text-right">Pagamento</th>
					<th class="text-right">Qtde.</th>
					<th class="text-right">Vl. Mensal</th>
					<th class="text-right">Vl. Unit.</th>
					<th class="text-right">Valor Total</th>
					<?php if ($orcamento->status != C_OrcamentoStatusAprovado && $role == 0) { ?>
						<th class="text-center">Ações</th>
					<?php } ?>
				</thead>
				<tbody>
					<!-- Serviços -->
					<?php foreach ($carrinho as $reg): ?>
						<tr id='<?= $reg->id ?>'>
							<td><?php echo $reg->virouitemordem == 1 ? 'Sim' : 'Não' ?></td>
							<td><?= $reg->idproduto ?></td>
							<td><?= $reg->servico ?></td>
							<td><?= $reg->observacao ?></td>
							<td class="text-right"> <?php if ($reg->valormensal > 0) {
														echo 'Mensal';
													} else {
														echo 'Único';
													}; ?> </td>
							<td class="text-right"><?= $reg->quantidade ?></td>
							<td class="text-right valormensal"><?php echo 'R$ ' . number_format($reg->valormensal, 2, ",", ".") ?></td>
							<td class="text-right valorunit"><?php echo 'R$ ' . number_format($reg->valoruni, 2, ",", ".") ?></td>
							<td class="text-right valordoservico"><?php echo ($reg->valormensal > 0) ? 'R$ 0,00' : 'R$ ' . number_format($reg->valordoservico, 2, ",", ".") ?></td>
							<?php if ($orcamento->status == C_OrcamentoStatusPendente && $role == 0) { ?>
								<td class="text-center">
									<?= $this->Html->link('<i class="fas fa-edit"></i><div class="ripple-container"></div>', ['#'], ['rel' => 'tooltip', 'title' => 'Editar', 'class' => 'btn btn-warning btn-edit-item btn-simple btn-xs', 'id' => $reg->id, 'escape' => false]); ?>
									<?= $this->Html->link('<i class="fa fa-times"></i><div class="ripple-container"></div>', [], ['rel' => 'tooltip', 'title' => 'Excluir', 'id' => $reg->id, 'class' => 'excluiitemcarrinho btn btn-danger btn-simple btn-xs', 'escape' => false]) ?>
								</td>
							<?php } ?>
						</tr>
					<?php endforeach; ?>
					<!-- Fim Serviços -->
					<!-- Outros -->
					<tr>
						<th class="text-right"> </th>
						<th class="text-right"> </th>
						<th class="text-right"> </th>
						<th class="text-right"> </th>
						<th class="text-right"> </th>
						<th class="text-right"> Valor Mensal: </th>
						<th class="text-right valormensaltotal">
							</p>
						</th>
						<th class="text-right"> Valor Total: </th>
						<th class="text-right valortotal">
							</p>
						</th>
					</tr>
					<!-- Fim Outros -->
				</tbody>
			</table>
		</div>
	</div>
</div>
<?php if ($orcamento->status == C_OrcamentoStatusPendente && $role == 0) { ?>
	<!-- Modal Edit Item -->
	<div class="modal fade none-border" id="modal-edit-item">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="row m-20">
					<div class="col-12">
						<?= $this->Form->create(null, ['url' => ['controller' => 'Orcamentos', 'action' => 'edititemcarrinho'], 'class' => 'form-material']); ?>
						<div class="row">
							<div class="col-lg-12 col-md-12">
								<label class="control-label">Código</label>
								<?= $this->Form->control('idproduto', ['id' => 'idproduto-modal', 'disabled' => false, 'class' => 'form-control selectpicker', 'data-live-search' => true, 'options' => $produtos, 'value' => 0, 'label' => false]) ?>
							</div>
							<div class="col-lg-12 col-md-12">
								<div class="form-group ">
									<label class="control-label text-muted">Produto/Serviço</label>
									<?= $this->Form->control('servico', ['id' => 'servico-modal', 'class' => 'form-control', 'label' => false]) ?>
									<small class="qtdEstoque"> </small>
								</div>
							</div>
							<div class="col-lg-6 col-md-12">
								<div class="form-group ">
									<label class="control-label text-muted">Tipo</label>
									<?= $this->Form->control('tipo', ['id' => 'tipo-modal', 'class' => 'quantidade form-control', 'options' => ['Unidade', 'Hora'], 'label' => false]) ?>
								</div>
							</div>
							<div class="col-lg-6 col-md-12">
								<div class="form-group ">
									<label class="control-label text-muted">Qtde.</label>
									<?= $this->Form->control('quantidade', ['id' => 'quantidade-modal', 'onkeypress' => 'return SomenteNumero(event, "#quantidade")', 'class' => 'quantidade form-control', 'label' => false]) ?>
								</div>
							</div>
							<div class="col-lg-4 col-md-12 ">
								<div class="form-group">
									<label class="control-label text-muted">Vl. Mensal</label>
									<input type="text" name="valormensal" id="valormensal-modal" class="mensal form-control" placeholder="0,00" autocomplete="off" />
								</div>
							</div>
							<div class="col-lg-4 col-md-12">
								<div class="form-group ">
									<label class="control-label text-muted">Vl. Unitário </label>
									<input type="text" name="valoruni" id="valoruni-modal" class="aquisicao form-control" placeholder="0,00" autocomplete="off" />
								</div>
							</div>
							<div class="col-lg-4 col-md-12">
								<div class="form-group ">
									<label class="control-label text-muted">Vl. Total</label>
									<?= $this->Form->control('valordoservico', ['id' => 'valordoservico-modal', 'class' => 'aquisicao form-control', 'label' => false, 'readonly' => true]) ?>
								</div>
							</div>
						</div>
						<div class='row'>
							<div class="col-lg-12 col-md-12">
								<div class="form-group ">
									<label class="control-label text-muted">Descrição</label>
									<?= $this->Form->control('observacao', ['id' => 'observacao-modal', 'class' => 'form-control', 'label' => false]) ?>
								</div>
							</div>
						</div>
						<?= $this->Form->control('iditem', ['id' => 'iditem-modal', 'label' => false, 'type' => 'hidden']) ?>
						<?= $this->Form->control('idorcamentofind', ['id' => 'idorcamentofind-modal', 'value' => $orcamento->id, 'label' => false, 'type' => 'hidden']) ?>
						<?= $this->Form->control('idorcamento', ['id' => 'idorcamento-modal',  'label' => false, 'type' => 'hidden']) ?>
						<button type="button" class="btn btn-danger waves-effect float-right" data-dismiss="modal">Fechar</button>
						<?= $this->Form->button('Salvar', ['class' => 'btn btn-info text-white m-r-5 float-right']) ?>
						<?= $this->Form->end(); ?>
					</div>
				</div>
				<div class="modal-footer">
				</div>
			</div>
		</div>
	</div>
<?php } ?>
<script>
	valortotal();

	function valortotal() {
		var valortotal = 0;
		var valormensaltotal = 0;
		$('.valormensal').each(function() {
			var linha = $(this).closest('tr');
			var strQtde = $(this).prev().text().trim();
			var qtde = 0;
			if (strQtde.indexOf(':') > -1) {
				var arr = strQtde.split(':');
				qtde = parseFloat(arr[0]) + (parseFloat(arr[1]) / 6 / 10);
			} else {
				qtde = parseFloat(strQtde.replaceAll(".", "").replaceAll(",", ".")) || 0;
			}
			var strMensal = $(this).text().split('R$').join('');
			var vMensal = parseFloat(strMensal.replaceAll(".", "").replaceAll(",", ".")) || 0;
			var strUnit = linha.find('.valorunit').text().split('R$').join('');
			var vUnit = parseFloat(strUnit.replaceAll(".", "").replaceAll(",", ".")) || 0;

			if (vMensal > 0) {
				valormensaltotal += (vMensal * qtde);
			} else {
				valortotal += (vUnit * qtde);
			}
		});
		$(".valortotal").html('R$ ' + numberToReal(valortotal));
		$(".valormensaltotal").html('R$ ' + numberToReal(valormensaltotal));
	}

	$('.tempoestimado').each(function() {
		var body = $(this).html();
		var h = body.search("h");
		var min = body.search("min");
		if (h < 0 && min < 0) {
			$(this).text($(this).text() + 'h');
		}
	})

	<?php if ($orcamento->status == C_OrcamentoStatusPendente && $role == 0) { ?>
		var carregandoItemModal = false;
		$('.btn-edit-item').click(function(e) {
			$('#modal-edit-item').removeData('unitMonthly');
			$('#idproduto-modal, .idproduto1-modal, #servico-modal, #tipo-modal, #quantidade-modal, #valormensal-modal, #valoruni-modal, #valordoservico-modal, #observacao-modal, #idorcamento-modal, #iditem-modal').val('');
			var id = $(this).attr('id');
			carregandoItemModal = true;
			$.ajax({
				type: "POST",
				url: "<?= Router::url(['controller' => 'Orcamentos', 'action' => 'getitemcarrinho']); ?>/" + id,
				dataType: "json",
				success: function(data) {
					$('#idproduto-modal').val(data.idproduto).selectpicker('refresh');
					$('.idproduto1-modal').val(data.idproduto);
					$('#servico-modal').val(data.servico);
					$('#tipo-modal').val(data.tipo);
					$('#quantidade-modal').val(data.quantidade);
					$('#valormensal-modal').val(numberToReal(data.valormensal));
					$('#valoruni-modal').val(numberToReal(data.valoruni));
					if (parseFloat(data.valormensal) > 0) {
						$('#valordoservico-modal').val('R$ 0,00');
						var qtd = parseFloat(data.quantidade) || 1;
						$('#modal-edit-item').data('unitMonthly', parseFloat(data.valormensal) / qtd);
					} else {
						$('#valordoservico-modal').val(numberToReal(data.valordoservico));
						$('#modal-edit-item').removeData('unitMonthly');
					}
					$('#observacao-modal').val(data.observacao);
					$('#idorcamento-modal').val(data.idorcamento);
					$('#iditem-modal').val(id);
					$('#valormensal-modal').prop('disabled', false).removeAttr('disabled');
					$('#valoruni-modal').prop('disabled', false).removeAttr('disabled');
					carregandoItemModal = false;
				},
				complete: function() { carregandoItemModal = false; }
			});
			e.preventDefault();
			$('#modal-edit-item').modal('toggle');
		});
		$('#modal-edit-item').on('shown.bs.modal', function() {
			$('#valormensal-modal').prop('disabled', false).removeAttr('disabled').prop('readonly', false).removeAttr('readonly');
			$('#valoruni-modal').prop('disabled', false).removeAttr('disabled').prop('readonly', false).removeAttr('readonly');
		});

		function parseQuantidadeModal() {
			var qtdStr = $('#quantidade-modal').val();
			if (!qtdStr) return 0;
			if (qtdStr.indexOf(':') > -1) {
				var arr = qtdStr.split(':');
				return parseFloat(arr[0]) + (parseFloat(arr[1]) / 6 / 10) || 0;
			}
			return parseFloat(qtdStr.replaceAll(".", "").replaceAll(",", ".")) || 0;
		}
		$('#quantidade-modal, #valoruni-modal, #valormensal-modal').on('change keyup', function(e) {
			var idAlvo = e.target.id;
			var soAtualizarTotal = (idAlvo === 'valormensal-modal' || idAlvo === 'valoruni-modal');
			var valormensalStr = $('#valormensal-modal').val();
			var valormensal = 0;
			if (valormensalStr) {
				valormensal = parseFloat(valormensalStr.replaceAll(".", "").replaceAll(",", ".")) || 0;
			}
			var quantidade = parseQuantidadeModal();
			var unitMonthly = $('#modal-edit-item').data('unitMonthly');
			var idproduto = $('#idproduto-modal').val();

			if (quantidade === 0 || quantidade === '' || isNaN(quantidade)) {
				if (!soAtualizarTotal) {
					$('#valormensal-modal').val('');
					$('#modal-edit-item').removeData('unitMonthly');
				}
				$('#valordoservico-modal').val('');
				return;
			}
			if (unitMonthly != null && unitMonthly !== undefined && !isNaN(unitMonthly) && !soAtualizarTotal) {
				var novoTotal = quantidade * unitMonthly;
				$('#valormensal-modal').val(numberToReal(novoTotal));
				$('#valordoservico-modal').val('R$ 0,00');
				return;
			}
			if (unitMonthly != null && unitMonthly !== undefined && !isNaN(unitMonthly) && soAtualizarTotal) {
				$('#valordoservico-modal').val('R$ 0,00');
				return;
			}
			if (quantidade > 0 && idproduto && idproduto != '0' && valormensal <= 0 && !soAtualizarTotal) {
				var url = "<?= Router::url(['controller' => 'Produtos', 'action' => 'produto']); ?>" + '/' + idproduto;
				$.ajax({
					type: "post",
					url: url,
					dataType: "json",
					success: function(data) {
						if (data.mensagem) return;
						var qtd = parseQuantidadeModal();
						if (data.tipo == <?= C_ProdutosTipoServico ?>) {
							var vlUnit = parseFloat(data.vlunitario) || 0;
							$('#modal-edit-item').data('unitMonthly', vlUnit);
							$('#valormensal-modal').val(numberToReal(qtd * vlUnit));
							$('#valordoservico-modal').val('R$ 0,00');
						} else {
							var vlUnit = parseFloat(data.vlunitario) || 0;
							$('#valoruni-modal').val(numberToReal(vlUnit));
							$('#valordoservico-modal').val(numberToReal(qtd * vlUnit));
						}
					},
					error: function() { }
				});
				return;
			}
			if (valormensal > 0 && quantidade > 0 && !soAtualizarTotal) {
				$('#modal-edit-item').data('unitMonthly', valormensal / quantidade);
				$('#valordoservico-modal').val('R$ 0,00');
				return;
			}
			if (valormensal > 0) {
				$('#valordoservico-modal').val('R$ 0,00');
				return;
			}
			var valoruni = $('#valoruni-modal').val().replaceAll(".", "").replaceAll(",", ".");
			var valor = valoruni ? parseFloat(valoruni) || 0 : 0;
			if (quantidade > 0 && valor > 0) {
				$('#valordoservico-modal').val(numberToReal(quantidade * valor));
			} else {
				$('#valordoservico-modal').val('');
			}
		});

		$('#idproduto-modal').change(function(e) {
			if (carregandoItemModal) return;
			$('#modal-edit-item').removeData('unitMonthly');
			if ($(this).val() != 0) {
				var url = "<?= Router::url(['controller' => 'Produtos', 'action' => 'produto']); ?>" + '/' + $(this).val();
				$.ajax({
					type: "post",
					url: url,
					dataType: "json",
					success: function(data) {
						if (data.mensagem) return;
						console.log(data);
						$('#servico-modal').val((data.descricao || '').toString().trim());
						$('#quantidade-modal').val('');
						$('#valordoservico-modal').val('');
						$('#valormensal-modal').prop('disabled', false).removeAttr('disabled');
						$('#valoruni-modal').prop('disabled', false).removeAttr('disabled');
						if (data.tipo == 2) {
							$('#valormensal-modal').val('');
							$('#valoruni-modal').val(numberToReal(data.vlunitario));
							$('#tipo-modal').val(1);
							$('#quantidade-modal').mask('99:99');
						} else if (data.tipo == 1) {
							$('#valoruni-modal').val(numberToReal(data.vlunitario));
							$('#valormensal-modal').val('');
							$('#tipo-modal').val(0);
							$('#quantidade-modal').mask('0000000');
						} else {
							$('#valormensal-modal').val(numberToReal(data.vlunitario));
							$('#valoruni-modal').val(numberToReal(data.vlunitario));
							$('#tipo-modal').val(0);
							$('#quantidade-modal').mask('0000000');
						}
					},
					error: function(error) {
						console.log(error);
						$('#valormensal-modal').prop('disabled', false).removeAttr('disabled');
						$('#valoruni-modal').prop('disabled', false).removeAttr('disabled');
					}
				});
			} else {
				$('#servico-modal').val('');
				$('#valoruni-modal').val('');
			}
		});
	<?php } ?>

	$('.excluiitemcarrinho').click(function(e) {
		e.preventDefault();
		id = $(this).attr('id');
		$.ajax({
			type: "POST",
			url: "<?= Router::url(['controller' => 'Orcamentos', 'action' => 'excluiitemcarrinho']); ?>/" + id,
			dataType: "html",
			success: function(data) {},
			error: function(error) {
				alert(error);
			},
			complete: function(data) {
				carrinho(e);
			}
		});
	});

	$(function() {
		$(".mascaramonetaria").maskMoney({
			allowNegative: true,
			thousands: '.',
			decimal: ','
		});
	});

	function numberToReal(numero) {
		if (!isNaN(numero)) {
			if (numero != null) {
				var numero = numero.toFixed(2).split('.');
				numero[0] = numero[0].split(/(?=(?:...)*$)/).join('.');
				return numero.join(',');
			} else return numero;
		}
	}
</script>