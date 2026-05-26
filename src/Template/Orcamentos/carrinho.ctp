<?php

use Cake\Routing\Router;

error_reporting(E_ERROR | E_PARSE);
$carrinhoLinhasExtra = $carrinhoLinhasExtra ?? [];
?>
<div class="row">
	<div class="col-lg-12">
		<?= $this->element('orcamentos_carrinho_tabela', [
			'carrinho' => $carrinho ?? [],
			'carrinhoLinhasExtra' => $carrinhoLinhasExtra,
			'mostrarAcoesItens' => true,
		]) ?>
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
		$('#tableCarrinho tbody tr').each(function () {
			var linha = $(this);
			if (linha.hasClass('orc-carrinho-empty') || linha.hasClass('orc-carrinho-sum-row')) {
				return;
			}
			var $vm = linha.find('.valormensal');
			if (!$vm.length) {
				return;
			}
			var strQtde = linha.find('.orc-col-qtd').first().text().trim();
			var qtde = parseFloat(strQtde.replace(/\./g, '').replace(',', '.')) || 0;
			var vMensal = parseBrFloat($vm.text());
			var vUnit = parseBrFloat(linha.find('.valorunit').text());
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

	$('.btn-limpacarrinho').click(function(e) {
		e.preventDefault();
		$.ajax({
			type: 'POST',
			url: "<?= Router::url(['controller' => 'Orcamentos', 'action' => 'limpacarrinho']); ?>",
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
