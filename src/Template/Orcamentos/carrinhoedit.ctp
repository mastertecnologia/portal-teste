<?php

use Cake\Routing\Router;

error_reporting(E_ERROR | E_PARSE);
$carrinhoLinhasExtra = $carrinhoLinhasExtra ?? [];
$role = isset($role) ? (int)$role : 0;
$mostrarAcoesItens = isset($orcamento)
	&& (int)$orcamento->get('status') === (int)C_OrcamentoStatusPendente
	&& $role === 0;
?>
<div class="row">
	<div class="col-lg-12">
		<div class="orc-premium-carrinho-tbl-wrap table-responsive">
			<table class="table orc-premium-carrinho-tbl" id="tableCarrinho">
				<thead>
					<tr>
						<th style="width:5%;">Ordem</th>
						<th style="width:6%;">Código</th>
						<th style="width:18%;">Produto/Serviço</th>
						<th style="width:16%;">Descrição</th>
						<th class="text-right">Pagamento</th>
						<th class="text-right">Qtde.</th>
						<th class="text-right">Vl. Mensal</th>
						<th class="text-right">Vl. Unit.</th>
						<th class="text-right">Valor Total</th>
						<th class="text-right" style="width:8%;">Custo</th>
						<th class="text-right" style="width:7%;">Margem</th>
						<?php if ($mostrarAcoesItens) : ?>
							<th class="text-center" style="width:90px;">Ações</th>
						<?php endif; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($carrinho as $reg) :
						$ex = $carrinhoLinhasExtra[(int)$reg->id] ?? ['custoLinha' => 0.0, 'margemPct' => null];
						$custoLinha = (float)($ex['custoLinha'] ?? 0);
						$margemPct = $ex['margemPct'] ?? null;
						?>
						<tr id="<?= $reg->id ?>">
							<td><?= (isset($reg->virouitemordem) && (int)$reg->virouitemordem === 1) ? 'Sim' : 'Não' ?></td>
							<td><?= h($reg->idproduto) ?></td>
							<td><?= h($reg->servico) ?></td>
							<td><?= h($reg->observacao) ?></td>
							<td class="text-right"><?= $reg->valormensal > 0 ? 'Mensal' : 'Único'; ?></td>
							<td class="text-right"><?= h($reg->quantidade) ?></td>
							<td class="text-right valormensal"><?php echo 'R$ ' . number_format($reg->valormensal, 2, ',', '.'); ?></td>
							<td class="text-right valorunit"><?php echo 'R$ ' . number_format($reg->valoruni, 2, ',', '.'); ?></td>
							<td class="text-right valordoservico"><?php echo ($reg->valormensal > 0) ? 'R$ 0,00' : 'R$ ' . number_format($reg->valordoservico, 2, ',', '.'); ?></td>
							<td class="text-right orc-line-custo" data-custo="<?= h((string)$custoLinha) ?>"><?= 'R$ ' . number_format($custoLinha, 2, ',', '.'); ?></td>
							<td class="text-right orc-line-margem"><?= $margemPct !== null ? h((string)$margemPct) . '%' : '—' ?></td>
							<?php if ($mostrarAcoesItens) : ?>
								<td class="text-center btn-actions">
									<?= $this->Html->link('<i class="fa fa-edit"></i>', [], [
										'rel' => 'tooltip',
										'title' => 'Editar',
										'data-id' => $reg->id,
										'data-servico' => $reg->servico,
										'data-quantidade' => $reg->quantidade,
										'data-valoruni' => $reg->valoruni,
										'data-observacao' => $reg->observacao,
										'data-valormensal' => $reg->valormensal,
										'data-idproduto' => $reg->idproduto,
										'data-tipo' => $reg->valormensal > 0 ? 1 : 0,
										'class' => 'editaitemcarrinho btn btn-orc-tbl-icon btn-orc-tbl-icon--edit',
										'escape' => false,
									]) ?>
									<?= $this->Html->link('<i class="fa fa-times"></i>', [], [
										'rel' => 'tooltip',
										'title' => 'Excluir',
										'id' => $reg->id,
										'class' => 'excluiitemcarrinho btn btn-orc-tbl-icon btn-orc-tbl-icon--del',
										'escape' => false,
									]) ?>
								</td>
							<?php endif; ?>
						</tr>
					<?php endforeach; ?>
					<tr>
						<th class="text-right"></th>
						<th class="text-right"></th>
						<th class="text-right"></th>
						<th class="text-right"></th>
						<th class="text-right"></th>
						<th class="text-right">Valor Mensal:</th>
						<th class="text-right valormensaltotal"></th>
						<th class="text-right">Valor Total:</th>
						<th class="text-right valortotal"></th>
						<th class="text-right"></th>
						<th class="text-right"></th>
						<?php if ($mostrarAcoesItens) : ?>
							<th></th>
						<?php endif; ?>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</div>
<script>
	valortotal();

	function numberToReal(numero) {
		if (!isNaN(numero)) {
			var numero = numero.toFixed(2).split('.');
			numero[0] = numero[0].split(/(?=(?:...)*$)/).join('.');
			return numero.join(',');
		}
	}

	function parseBrFloat(txt) {
		if (!txt) return 0;
		return parseFloat(String(txt).split('R$').join('').replace(/\./g, '').replace(',', '.').trim()) || 0;
	}

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
				qtde = parseFloat(strQtde.replace(/\./g, '').replace(',', '.')) || 0;
			}

			var strMensal = $(this).text().split('R$').join('');
			var vMensal = parseFloat(strMensal.replace(/\./g, '').replace(',', '.')) || 0;
			var strUnit = linha.find('.valorunit').text().split('R$').join('');
			var vUnit = parseFloat(strUnit.replace(/\./g, '').replace(',', '.')) || 0;
			if (vMensal > 0) {
				valormensaltotal += (vMensal * qtde);
			} else {
				valortotal += (vUnit * qtde);
			}
		});

		$('.valortotal').html('R$ ' + numberToReal(valortotal));
		$('.valormensaltotal').html('R$ ' + numberToReal(valormensaltotal));

		if (typeof window.orcNovoAfterCarrinhoTotals === 'function') {
			window.orcNovoAfterCarrinhoTotals();
		}
	}

	$('.excluiitemcarrinho').click(function(e) {
		e.preventDefault();
		id = $(this).attr('id');
		$.ajax({
			type: 'POST',
			url: "<?= Router::url(['controller' => 'Orcamentos', 'action' => 'excluiitemcarrinho']); ?>/" + id,
			dataType: 'html',
			error: function(error) {
				alert(error);
			},
			complete: function(data) {
				carrinho(e);
			},
		});
	});
</script>
