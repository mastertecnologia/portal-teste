<?php
  	use Cake\Routing\Router;
	$this->append('css', $this->Html->css('/css/orcamentos-premium', ['timestamp' => true]));
	$this->Html->script('/js/orcamentos', ['block' => true]);
?>
<style>
	.dtp table.dtp-picker-days tr > td{
		font-weight: 700	 !important;
		font-size: 0.8em	 !important;
		text-align: center	 !important;
		padding: 0.5em 0.3em !important;
	}
</style>
<div class="col-md-12 orc-premium-page-root">
<div class="orc-premium-wrap orc-premium-form">
	<?= $this->Form->create($orcamento, ['url' => ['action' => 'add'], 'enctype' => 'multipart/form-data', 'type' => 'file', 'class' => 'form-material', 'id' => 'form-orc-add']); ?>
	<?= $this->Form->hidden('item_edit_id', ['id' => 'item_edit_id']); ?>

	<div class="orc-page-head">
		<div>
			<div class="orc-eyebrow">Módulo comercial</div>
			<div style="font-size:11px;color:var(--orc-text-muted,#6b6a65);margin-bottom:3px;">
				<?= $this->Html->link('Orçamentos', ['action' => 'index'], ['escape' => false]) ?> › <span style="color:#1d9e75;">Novo</span>
			</div>
			<h1 class="orc-h1" id="orc-novo-proposta-title">Proposta de Orçamento</h1>
		</div>
		<div class="orc-page-head-actions">
			<?= $this->Html->link('Cancelar', ['action' => 'index'], ['class' => 'btn btn-orc-form-secondary']) ?>
			<?= $this->Form->button('Avançar para revisão →', [
				'type' => 'submit',
				'class' => 'btn btn-orc-premium-primary',
				'escape' => false,
			]) ?>
		</div>
	</div>

	<?= $this->element('orcamentos_stepper') ?>

	<div class="card orc-premium-card-inner" style="margin-bottom:14px;">
		<div class="card-body">
			<div class="orc-sec-title">Dados do cliente</div>
			<div class="row">
				<div class="col-lg-6 col-md-12">
					<label class="control-label">Cliente</label>
					<?= $this->Form->control('idcliente', ['class' => 'form-control selectpicker', 'data-live-search' => true, 'options' => $clientes, 'title' => 'Selecione um cliente', 'label' => false, 'required' => true, 'id' => 'idcliente']) ?>
				</div>
				<div class="col-lg-6 col-md-12">
					<div class="row">
						<div class="col-sm-6">
							<label class="control-label">Pagamento</label>
							<?= $this->Form->control('formapagamento', [
								'type' => 'select',
								'options' => $orcFormaPagamentoOpcoes ?? [],
								'class' => 'form-control selectpicker',
								'label' => false,
								'id' => 'formapagamento',
								'empty' => false,
							]) ?>
						</div>
						<div class="col-sm-6">
							<div class="form-group">
								<label class="control-label">Válido até</label>
								<?= $this->Form->text('validoate', ['class' => 'form-control datepicker', 'id' => 'validoate', 'default' => date('d/m/Y'), 'placeholder' => 'Insira a data', 'required' => true]) ?>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row m-t-10">
				<div class="col-md-4 col-sm-12">
					<label class="control-label" id="orc-cli-doc-lbl">CNPJ / CPF</label>
					<input type="text" class="form-control" id="orc-cli-doc" readonly style="background:#f2f1ee;color:#6b6a65;" placeholder="Auto-preenchido" />
				</div>
				<div class="col-md-4 col-sm-12">
					<label class="control-label">E-mail do cliente</label>
					<input type="email" class="form-control" id="orc-cli-email" readonly style="background:#f2f1ee;color:#6b6a65;" placeholder="Auto-preenchido" />
				</div>
				<div class="col-md-4 col-sm-12">
					<label class="control-label">Contato / responsável</label>
					<input type="text" class="form-control" id="orc-cli-contato" readonly style="background:#f2f1ee;color:#6b6a65;" placeholder="Auto-preenchido" />
				</div>
			</div>
		</div>
	</div>

	<div class="card orc-premium-card-inner" style="margin-bottom:14px;">
		<div class="card-body">
			<div class="orc-sec-title orc-sec-title--split">
				<span>Produtos e serviços</span>
				<button type="button" class="btn btn-orc-outline-teal" onclick="orcCatalogOpen();">
					<i class="fa fa-list"></i> Buscar no catálogo
				</button>
			</div>

			<div class="orc-margin-summary" id="orc-margin-summary">
				<div class="orc-margin-card">
					<div class="orc-margin-card-val" id="ms-subtotal" style="color:#1a1a18;">R$ 0,00</div>
					<div class="orc-margin-card-lbl">Subtotal venda</div>
				</div>
				<div class="orc-margin-card">
					<div class="orc-margin-card-val" id="ms-custo" style="color:#6b6a65;">R$ 0,00</div>
					<div class="orc-margin-card-lbl">Custo total</div>
				</div>
				<div class="orc-margin-card">
					<div class="orc-margin-card-val" id="ms-lucro" style="color:#1d9e75;">R$ 0,00</div>
					<div class="orc-margin-card-lbl">Lucro bruto</div>
				</div>
				<div class="orc-margin-card">
					<div class="orc-margin-card-val" id="ms-margem" style="color:#1d9e75;">0%</div>
					<div class="orc-margin-card-lbl">Margem bruta</div>
					<div class="orc-margin-bar"><div class="orc-margin-fill" id="ms-bar" style="width:0%;"></div></div>
				</div>
			</div>

			<div class="row">
				<div class="col-lg-2 col-md-12">
					<label class="control-label">Código</label>
					<?= $this->Form->control('idproduto', ['class' => 'form-control selectpicker', 'data-live-search' => true, 'options' => $produtos, 'value' => 0, 'label' => false]) ?>
				</div>
				<div class="col-lg-5 col-md-12">
					<div class="form-group">
						<label class="control-label">Produto/Serviço</label>
						<?= $this->Form->control('servico', ['class' => 'form-control', 'label' => false]) ?>
						<small class="qtdEstoque text-muted"></small>
					</div>
				</div>
				<div class="col-lg-1 col-md-6">
					<div class="form-group">
						<label class="control-label">Tipo</label>
						<?= $this->Form->control('tipo', ['class' => 'quantidade form-control', 'options' => ['Unidade', 'Hora'], 'label' => false]) ?>
					</div>
				</div>
				<div class="col-lg-1 col-md-6">
					<div class="form-group">
						<label class="control-label">Qtde.</label>
						<?= $this->Form->control('quantidade', ['onkeypress' => 'return SomenteNumero(event, "#quantidade")', 'class' => 'quantidade form-control', 'label' => false]) ?>
					</div>
				</div>
				<div class="col-lg-1 col-md-6">
					<div class="form-group">
						<label class="control-label">Vl. Mensal</label>
						<?= $this->Form->control('valormensal', ['onkeypress' => 'return SomenteNumero(event, "#valormensal")', 'class' => 'mensal form-control mascaramonetaria', 'label' => false]) ?>
					</div>
				</div>
				<div class="col-lg-1 col-md-6">
					<div class="form-group">
						<label class="control-label">Vl. Unitário</label>
						<?= $this->Form->control('valoruni', ['onkeypress' => 'return SomenteNumero(event, "#valoruni")', 'class' => 'form-control mascaramonetaria', 'label' => false]) ?>
					</div>
				</div>
				<div class="col-lg-1 col-md-12">
					<div class="form-group">
						<label class="control-label">Vl. Total</label>
						<?= $this->Form->control('valordoservico', ['class' => 'form-control', 'label' => false, 'disabled' => true]) ?>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-lg-12 col-md-12">
					<div class="form-group">
						<label class="control-label">Descrição adicional</label>
						<?= $this->Form->control('observacao', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Detalhes...']) ?>
					</div>
				</div>
			</div>

			<button type="button" class="orc-add-row" id="btn-addservico">
				<i class="fa fa-plus orc-add-row-ic"></i> Adicionar item manualmente
			</button>
			<div class="orc-inline-actions" id="orc-item-edit-actions" style="display:none;">
				<button type="button" class="btn btn-orc-form-secondary btn-orc-compact" id="btn-cancelaredicao">
					Cancelar edição
				</button>
				<button type="button" class="btn btn-orc-premium-primary btn-orc-compact" id="btn-editarservico">
					<i class="fa fa-check"></i> Atualizar
				</button>
			</div>

			<div id="carrinho" class="m-t-10"></div>

			<div class="orc-discount-row" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-top:12px;padding:10px 14px;background:var(--orc-bg-surface,#f9f9f8);border-radius:8px;border:1px solid var(--orc-border-light,#f0efec);">
				<span style="font-size:11px;color:var(--orc-text-muted,#6b6a65);">Desconto:</span>
				<input type="number" id="disc-val" value="0" min="0" step="0.01" style="width:70px;padding:5px 8px;border:1px solid #e5e4e0;border-radius:6px;font-size:12px;text-align:right;" />
				<select id="disc-tipo" style="padding:5px 8px;border:1px solid #e5e4e0;border-radius:6px;font-size:12px;">
					<option value="pct">%</option>
					<option value="fix">R$</option>
				</select>
				<span style="font-size:11px;color:var(--orc-text-muted,#6b6a65);">| Desconto aplicado:</span>
				<span style="font-size:12px;font-weight:600;color:#E24B4A;" id="disc-show">R$ 0,00</span>
			</div>

			<div class="orc-tot-wrap">
				<div class="orc-tot-inner">
					<div class="orc-tot-l"><span>Subtotal</span><span id="t-sub">R$ 0,00</span></div>
					<div class="orc-tot-l"><span>Custo total</span><span id="t-cus" style="color:#6b6a65;">R$ 0,00</span></div>
					<div class="orc-tot-l"><span>Desconto</span><span class="orc-tot-rd" id="t-disc">— R$ 0,00</span></div>
					<div class="orc-tot-l"><span>Margem após desconto</span><span id="t-marg" style="color:#1d9e75;">0%</span></div>
					<div class="orc-tot-l"><span>Total geral</span><span class="orc-tot-g" id="t-tot">R$ 0,00</span></div>
				</div>
			</div>
		</div>
	</div>

	<div class="orc-obs-block">
		<div class="orc-sec-title">Observações</div>
		<label class="control-label" for="observacoes">Condições, prazos, garantias</label>
		<?= $this->Form->textarea('solicitacao', [
			'novalidate' => true,
			'id' => 'observacoes',
			'class' => 'form-control orc-obs-textarea',
			'label' => false,
			'rows' => 6,
			'placeholder' => 'Condições, prazos, garantias...',
		]) ?>
	</div>

	<div class="orc-footer-bar">
		<button type="button" class="btn btn-orc-outline-danger" id="btn-orc-limpar-novo">
			<i class="fa fa-trash"></i> Limpar
		</button>
		<div class="orc-footer-bar-actions">
			<?= $this->Html->link('Cancelar', ['action' => 'index'], ['class' => 'btn btn-orc-form-secondary']) ?>
			<?= $this->Form->button('Avançar →', [
				'type' => 'submit',
				'class' => 'btn btn-orc-premium-primary',
				'escape' => false,
			]) ?>
		</div>
	</div>

	<?= $this->Form->end(); ?>
</div>
</div>

<!-- Catálogo (layout alinhado ao protótipo “novo frontend”) -->
<div class="orc-catalog-overlay" id="orc-catalog-overlay" onclick="if(event.target===this)$(this).removeClass('open');">
	<div class="orc-catalog-modal" onclick="event.stopPropagation();">
		<div class="orc-catalog-header">
			<div class="orc-catalog-header-text">
				<h2 class="orc-catalog-h2">Catálogo de produtos</h2>
				<p class="orc-catalog-sub">Clique para adicionar ao orçamento</p>
			</div>
			<button type="button" class="btn btn-orc-catalog-fechar" onclick="$('#orc-catalog-overlay').removeClass('open');" aria-label="Fechar">
				<i class="fa fa-times"></i> Fechar
			</button>
		</div>
		<div class="orc-catalog-search">
			<div class="orc-catalog-search-inner">
				<i class="fa fa-search orc-catalog-search-ic" aria-hidden="true"></i>
				<input type="text" id="orc-catalog-search-input" placeholder="Buscar produto, código ou descrição..." autocomplete="off" oninput="orcCatalogFilter(this.value)" />
			</div>
		</div>
		<div class="orc-catalog-body" id="orc-catalog-body">
			<div class="orc-catalog-loading">
				<i class="fa fa-spinner fa-spin"></i> Carregando catálogo...
			</div>
		</div>
	</div>
</div>

<script>
	window.orcClientesMeta = <?= isset($clientesMetaJson) ? $clientesMetaJson : '{}' ?>;
	window.orcProdutosCatalogo = <?= isset($produtosCatalogoJson) ? $produtosCatalogoJson : '[]' ?>;
	window.orcEstoquesLoteUrl = <?= json_encode(Router::url(['controller' => 'Produtos', 'action' => 'estoquesLote']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

	function numberToReal(n) {
		if (isNaN(n)) return '0,00';
		var x = Number(n).toFixed(2).split('.');
		x[0] = x[0].split(/(?=(?:...)*$)/).join('.');
		return x.join(',');
	}

	function orcEscapeHtmlAttr(s) {
		return String(s == null ? '' : s)
			.replace(/&/g, '&amp;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#39;')
			.replace(/</g, '&lt;');
	}

	function parseBrFloat(txt) {
		if (!txt) return 0;
		return parseFloat(String(txt).split('R$').join('').replace(/\./g, '').replace(',', '.').trim()) || 0;
	}

	function orcClienteMetaFill() {
		var id = $('#idcliente').val();
		var m = window.orcClientesMeta && window.orcClientesMeta[id];
		if (!m) {
			$('#orc-cli-doc, #orc-cli-email, #orc-cli-contato').val('');
			$('#orc-cli-doc-lbl').text('CNPJ / CPF');
			return;
		}
		var jur = <?= (int)(defined('C_ClientesTipoJuridica') ? C_ClientesTipoJuridica : 1) ?>;
		if (parseInt(m.tipo, 10) === jur) {
			$('#orc-cli-doc-lbl').text('CNPJ');
			$('#orc-cli-doc').val(m.cnpj || '');
		} else {
			$('#orc-cli-doc-lbl').text('CPF');
			$('#orc-cli-doc').val(m.cpf || '');
		}
		$('#orc-cli-email').val(m.email || '');
		var cont = (m.nome || '').trim();
		if (!cont && (m.razaosocial || '').trim()) cont = (m.razaosocial || '').trim();
		$('#orc-cli-contato').val(cont);
	}

	function orcApplyDiscountRow(subVenda, subCusto) {
		var dv = parseFloat($('#disc-val').val());
		if (isNaN(dv)) dv = 0;
		var tipo = $('#disc-tipo').val();
		var discAbs = tipo === 'pct' ? subVenda * (dv / 100) : dv;
		if (discAbs < 0) discAbs = 0;
		if (discAbs > subVenda) discAbs = subVenda;
		var afterDisc = Math.max(0, subVenda - discAbs);
		var lucro = afterDisc - subCusto;
		var margem = afterDisc > 0.01 ? Math.round((lucro / afterDisc) * 100) : 0;
		$('#disc-show').text('R$ ' + numberToReal(discAbs));
		$('#t-sub').text('R$ ' + numberToReal(subVenda));
		$('#t-cus').text('R$ ' + numberToReal(subCusto));
		$('#t-disc').text('— R$ ' + numberToReal(discAbs));
		$('#t-marg').text(margem + '%').css('color', margem >= 15 ? '#1d9e75' : '#FFC107');
		$('#t-tot').text('R$ ' + numberToReal(afterDisc));
	}

	window.orcNovoAfterCarrinhoTotals = function() {
		var vu = parseBrFloat($('.valortotal').first().text());
		var vm = parseBrFloat($('.valormensaltotal').first().text());
		var subVenda = vu + vm;
		var subCusto = 0;
		$('#tableCarrinho tbody tr').each(function() {
			var $td = $(this).find('.orc-line-custo');
			if ($td.length) {
				var c = parseFloat($td.data('custo'));
				if (!isNaN(c)) subCusto += c;
			}
		});
		var lucro = subVenda - subCusto;
		var margem = subVenda > 0.01 ? Math.round((lucro / subVenda) * 100) : 0;
		$('#ms-subtotal').text('R$ ' + numberToReal(subVenda));
		$('#ms-custo').text('R$ ' + numberToReal(subCusto));
		$('#ms-lucro').text('R$ ' + numberToReal(lucro));
		$('#ms-margem').text(margem + '%');
		var w = Math.min(100, Math.max(0, margem));
		$('#ms-bar').css('width', w + '%');
		orcApplyDiscountRow(subVenda, subCusto);
	};

	// Carrinho
		carrinho();
		function carrinho(){
			$.ajax({
				type: "POST",
				url: "<?= Router::url(['controller'=>'Orcamentos','action'=>'carrinho', $idcarrinho]);?>",
				dataType: "html",
				success : function(data) {
					$("#carrinho").html(data);
					$("#carrinho").fadeIn();
				},
				error : function(error) {
					alert(error);
				}
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
		$('#idproduto').change(function(e){
			if( $(this).val() != 0){
				$('#valoruni').attr('disabled', true);
				$('.mensal').attr('disabled', true);
				$.ajax({
					type:"post",
					url: "<?= Router::url(['controller'=>'Produtos','action'=>'produto']);?>" + '/' + $(this).val(),
					dataType: "json",
					success: function(data){
						if (data.mensagem) {
							$('#servico').val('');
							$('#valoruni').val('');
							$('.qtdEstoque').text(data.mensagem).show();
							$('#valoruni').prop('disabled', false);
							$('.mensal').prop('disabled', false);
							return;
						}
						$('#servico').val((data.descricao || '').toString().trim());
						$('#quantidade').val('');
						$('#valordoservico').val('');
						if(data.tipo == <?= C_ProdutosTipoServico ?>) {
							$('#valormensal').prop('disabled', false);
							$('#valoruni').prop('disabled', false);
							$('#valormensal').val('');
							$('#valoruni').val(numberToReal(data.vlunitario));
							$('#tipo').val(1);
							$('#quantidade').mask('99:99');
							$('.qtdEstoque').hide();
						} else if (data.tipo == <?= C_ProdutosTipoProduto  ?>) {
							$('#valormensal').prop('disabled', 'disabled');
							$('#valoruni').prop('disabled', false);
							$('#valoruni').val(numberToReal(data.vlunitario));
							$('#valormensal').val('');
							$('#tipo').val(0);
							$('#quantidade').mask('0000000');
							$.ajax({
								type:"post",
								url: "<?= Router::url(['controller'=>'Produtos','action'=>'qtdestoque']);?>/" + data.codigo,
								dataType: "json",
								success:function(qtdestoque) {
									var msg = (qtdestoque === -999 || qtdestoque === null || (typeof qtdestoque === 'number' && qtdestoque < 0))
										? 'Estoque: indisponível (consulte o ERP)'
										: ('Qtd. em estoque: ' + qtdestoque);
									$('.qtdEstoque').text(msg).show();
								},
								error: function() {
									$('.qtdEstoque').text('Estoque: indisponível').show();
								}
							});
						} else {
							$('#valormensal').prop('disabled', false);
							$('#valoruni').prop('disabled', false);
							$('#valormensal').val(numberToReal(data.vlunitario));
							$('#valoruni').val(numberToReal(data.vlunitario));
							$('#tipo').val(0);
							$('#quantidade').mask('0000000');
							$('.qtdEstoque').hide();
						}
					},
					error: function (xhr) {
						var msg = 'Produto/serviço não encontrado.';
						if (xhr.responseJSON && xhr.responseJSON.mensagem) msg = xhr.responseJSON.mensagem;
						$('.qtdEstoque').text(msg).show();
						$('#valoruni').val('').prop('disabled', false);
						$('.mensal').prop('disabled', false);
					}
				});
			} else {
				$('#servico').val('');
				$('#valoruni').val('');
				$('#valoruni').attr('disabled', false);
				$('.mensal').attr('disabled', false);
			}
		});

	// Tipo
		$('#tipo').change(function(){
			if($(this).val() == 1) $('#quantidade').mask('99:99');
			else $('#quantidade').mask('0000000');
		});

	// Valores
		$('#valoruni').keydown(function(){
			valor = $(this).val() .replaceAll('.', '').replaceAll(',', '.');
			if(valor > 0) $('#valormensal').val('');
		});

		$('#valormensal').keydown(function(){
			valor = $(this).val() .replaceAll('.', '').replaceAll(',', '.');
			if(valor > 0) $('#valoruni').val('');
		});

	// Cálculos
		$('#quantidade, #valoruni, #idproduto, #valormensal').keyup(function(e){
			if( $('#quantidade').val().indexOf(':') > -1  ) {
				quantidadeArray = $('#quantidade').val().split(':');
				quantidade =( parseFloat(quantidadeArray[0]) + ( parseFloat(quantidadeArray[1]) / 6 / 10 )).toFixed(2);
			}else quantidade = $('#quantidade').val().replaceAll('.', '').replaceAll(',', '.')

			
			valoruni = $('#valoruni').val().replaceAll('.', '').replaceAll(',', '.')
			valormensal = $('#valormensal').val().replaceAll('.', '').replaceAll(',', '.')
			valor = 0;
			if(valoruni != '') valor = valoruni;
			//else valor = valormensal;
			if(quantidade > 0 && valor > 0){
				valortotal = quantidade * valor;
				$('#valordoservico').val(numberToReal(valortotal));
			}
			else $('#valordoservico').val('');
		});

	// Add serviço
		$('#btn-addservico').click(function(e){
			e.preventDefault();
			servico =       $('#servico').val();
			quantidade =	$('#quantidade').val();
			valoruni =      $('#valoruni').val();
			valordoservico= $('#valordoservico').val();
			observacao = 	$('#observacao').val();
			valormensal = 	$('#valormensal').val();
			idproduto =		$('#idproduto').val();
			tipo =			$('#tipo').val();

			if(servico == ''){
				bootbox.alert('Preencha o campo "Descrição".');
				return false;
			}

			if(quantidade == '' || (valoruni == '' && valormensal == '')){
				bootbox.alert('Preencha o campo "Quantidade" e o campo de valor respectivo.');
				return false;
			}

			if(valoruni == '') valoruni = 0;
			if(valormensal == '') valormensal = 0;

			var url = "<?= Router::url(['controller'=>'Orcamentos','action'=>'addservico']);?>";
			$.ajax({
				url: url,
				dataType: "html",
				type: 'POST',
				data: { servico: servico, quantidade: quantidade, valoruni: valoruni, valordoservico: valordoservico, observacao: observacao, valormensal: valormensal, idproduto: idproduto, tipo : tipo},
				success : function(data) {
					console.log(data);
					if(data == 'nao pode'){
						bootbox.alert('O serviço já está no carrinho');
						return false;
					}
					carrinho();
					$('#servico').val('');
					$('#quantidade').val('');
					$('#valoruni').val('');
					$('#valordoservico').val('');
					$('#observacao').val('');
					$('#valormensal').val('');
					$('#idproduto').val(0);
					$('#tipo').val(0);
					$('#idproduto').selectpicker('refresh');
					$('.qtdEstoque').text('').hide();
					$('#valormensal').attr('disabled', false);
					$('#valoruni').attr('disabled', false);
					$('#servico').focus();
				},
				error : function(xhr) {
					var msg = 'Erro ao adicionar item. Tente novamente.';
					if (xhr.responseJSON && xhr.responseJSON.mensagem) msg = xhr.responseJSON.mensagem;
					else if (xhr.responseText && xhr.responseText.length < 200) msg = xhr.responseText;
					if (typeof bootbox !== 'undefined') bootbox.alert(msg); else alert(msg);
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

	var editando = false;

	// Função para preencher formulário com dados do item
	function preencherFormularioEdicao(dados) {
		$('#servico').val(dados.servico);
		$('#quantidade').val(dados.quantidade);
		$('#valoruni').val(numberToReal(dados.valoruni));
		$('#observacao').val(dados.observacao);
		$('#valormensal').val(numberToReal(dados.valormensal));
		$('#idproduto').val(dados.idproduto);
		$('#tipo').val(dados.tipo);
		$('#item_edit_id').val(dados.id);
		
		// Aplicar máscara baseada no tipo
		if(dados.tipo == 1) {
			$('#quantidade').mask('99:99');
		} else {
			$('#quantidade').mask('0000000');
		}
		
		// Calcular valor total
		calcularValorTotal();
		
		// Atualizar selects
		$('#idproduto').selectpicker('refresh');
	}

	// Função para limpar formulário
	function limparFormularioEdicao() {
		$('#servico').val('');
		$('#quantidade').val('');
		$('#valoruni').val('');
		$('#observacao').val('');
		$('#valormensal').val('');
		$('#idproduto').val(0);
		$('#tipo').val(0);
		$('#valordoservico').val('');
		$('#item_edit_id').val('');
		
		$('#idproduto').selectpicker('refresh');
		$('#valoruni').prop('disabled', false);
		$('#valormensal').prop('disabled', false);
		$('.qtdEstoque').hide();
		
		// Resetar máscara
		$('#quantidade').mask('0000000');
	}

	// Alternar entre modo adição e edição
	function toggleModoEdicao(modo) {
		editando = modo;
		if (modo) {
			$('#btn-addservico').hide();
			$('#orc-item-edit-actions').show();
			$('#orc-novo-proposta-title').text('Editando item do orçamento');
		} else {
			$('#btn-addservico').show();
			$('#orc-item-edit-actions').hide();
			$('#orc-novo-proposta-title').text('Proposta de Orçamento');
			limparFormularioEdicao();
		}
	}

	// Função para calcular valor total
	function calcularValorTotal() {
		var quantidade = $('#quantidade').val();
		var valoruni = $('#valoruni').val();
		var valormensal = $('#valormensal').val();
		
		if(quantidade.indexOf(':') > -1) {
			quantidadeArray = quantidade.split(':');
			quantidade = (parseFloat(quantidadeArray[0]) + (parseFloat(quantidadeArray[1]) / 6 / 10)).toFixed(2);
		} else {
			quantidade = quantidade.replace(/\./g, '').replace(',', '.');
		}
		
		valoruni = valoruni.replace(/\./g, '').replace(',', '.');
		valormensal = valormensal.replace(/\./g, '').replace(',', '.');
		
		var valor = 0;
		if(valoruni != '' && parseFloat(valoruni) > 0) {
			valor = parseFloat(valoruni);
		} else if(valormensal != '' && parseFloat(valormensal) > 0) {
			valor = parseFloat(valormensal);
		}
		
		if(quantidade > 0 && valor > 0){
			var valortotal = parseFloat(quantidade) * valor;
			$('#valordoservico').val(numberToReal(valortotal));
		} else {
			$('#valordoservico').val('');
		}
	}

	// Evento para editar item (deve ser adicionado após o carregamento do carrinho)
	$(document).on('click', '.editaitemcarrinho', function(e) {
		e.preventDefault();
		
		var dados = {
			id: $(this).data('id'),
			servico: $(this).data('servico'),
			quantidade: $(this).data('quantidade'),
			valoruni: $(this).data('valoruni'),
			observacao: $(this).data('observacao'),
			valormensal: $(this).data('valormensal'),
			idproduto: $(this).data('idproduto'),
			tipo: $(this).data('tipo')
		};
		
		console.log('Editando item:', dados); // Para debug
		
		preencherFormularioEdicao(dados);
		toggleModoEdicao(true);
		
		// Scroll para formulário
		$('html, body').animate({
			scrollTop: $('.card.orc-premium-card-inner').first().offset().top
		}, 500);
	});

	// Evento para atualizar item
	$('#btn-editarservico').click(function(e) {
		e.preventDefault();
		
		var id = $('#item_edit_id').val();
		var servico = $('#servico').val();
		var quantidade = $('#quantidade').val();
		var valoruni = $('#valoruni').val();
		var valordoservico = $('#valordoservico').val();
		var observacao = $('#observacao').val();
		var valormensal = $('#valormensal').val();
		var idproduto = $('#idproduto').val();
		var tipo = $('#tipo').val();

		// Validações
		if (servico == '') {
			bootbox.alert('Preencha o campo "Descrição".');
			return false;
		}

		if (quantidade == '' || (valoruni == '' && valormensal == '')) {
			bootbox.alert('Preencha o campo "Quantidade" e o campo de valor respectivo.');
			return false;
		}

		var url = "<?= Router::url(['controller'=>'Orcamentos','action'=>'editaitemcarrinho']);?>";
		$.ajax({
			url: url,
			dataType: "html",
			type: 'POST',
			data: { 
				id: id,
				servico: servico, 
				quantidade: quantidade, 
				valoruni: valoruni, 
				valordoservico: valordoservico, 
				observacao: observacao, 
				valormensal: valormensal, 
				idproduto: idproduto, 
				tipo: tipo 
			},
			success: function(data) {
				console.log('Resposta:', data); // Para debug
				if (data == 'success') {
					carrinho();
					toggleModoEdicao(false);
					bootbox.alert('Item atualizado com sucesso!');
				} else {
					bootbox.alert('Erro ao atualizar item.');
				}
			},
			error: function(error) {
				console.log('Erro:', error); // Para debug
				bootbox.alert('Erro ao atualizar item: ' + error);
			}
		});
	});

	// Evento para cancelar edição
	$('#btn-cancelaredicao').click(function(e) {
		e.preventDefault();
		toggleModoEdicao(false);
	});

	// Atualizar cálculo quando campos mudarem
	$('#quantidade, #valoruni, #valormensal').on('keyup change', function() {
		calcularValorTotal();
	});

	$('#idcliente').on('changed.bs.select', function() {
		orcClienteMetaFill();
	});
	orcClienteMetaFill();

	$('#disc-val, #disc-tipo').on('change input', function() {
		if (typeof window.orcNovoAfterCarrinhoTotals === 'function') {
			window.orcNovoAfterCarrinhoTotals();
		}
	});

	$('#btn-orc-limpar-novo').on('click', function(e) {
		e.preventDefault();
		$('#idcliente').val('').selectpicker('refresh');
		$('#formapagamento').val('À vista').selectpicker('refresh');
		orcClienteMetaFill();
		$('#disc-val').val(0);
		$('#disc-tipo').val('pct');
		$('#observacoes').val('');
		$.ajax({
			type: 'POST',
			url: "<?= Router::url(['controller'=>'Orcamentos','action'=>'limpacarrinho']);?>",
			dataType: 'html',
			complete: function() { carrinho(); }
		});
	});

	$(function() {
		$('#ms-subtotal').text('R$ ' + numberToReal(0));
		$('#ms-custo').text('R$ ' + numberToReal(0));
		$('#ms-lucro').text('R$ ' + numberToReal(0));
		$('#ms-margem').text('0%');
		$('#ms-bar').css('width', '0%');
		orcApplyDiscountRow(0, 0);
	});

	// Catálogo overlay (dados completos via JSON do servidor — ver OrcamentosController::add)
	var orcCatalogData = [];
	var orcCatalogRenderedItems = [];

	function orcCatalogOpen() {
		$('#orc-catalog-search-input').val('');
		$('#orc-catalog-overlay').addClass('open');
		orcCatalogEnsureLoaded();
	}

	function orcCatalogEnsureLoaded() {
		var src = window.orcProdutosCatalogo || [];
		if (!src.length) {
			$('#orc-catalog-body').html('<div class="orc-catalog-empty">Nenhum produto ou serviço ativo cadastrado.</div>');
			return;
		}
		orcCatalogData = src.slice();
		orcCatalogFilter('');
	}

	function orcCatalogBadgeClass(badge) {
		var b = (badge || 'outro').toLowerCase();
		if (b === 'prod') return 'orc-cat-badge orc-cat-badge--prod';
		if (b === 'srv') return 'orc-cat-badge orc-cat-badge--srv';
		if (b === 'lic') return 'orc-cat-badge orc-cat-badge--lic';
		if (b === 'loc') return 'orc-cat-badge orc-cat-badge--loc';
		return 'orc-cat-badge orc-cat-badge--outro';
	}

	function orcCatalogUnidadeHint(p) {
		if ((p.badge || '') === 'srv') return 'usr/mês';
		return (p.unidade || 'un').toString();
	}

	/** Produto, licença e locação: ERP pode devolver saldo (GetProdutoEstoque). Serviço/outros: só margem. */
	function orcCatalogBadgeConsultaEstoque(badge) {
		var b = (badge || '').toLowerCase();
		return b === 'prod' || b === 'lic' || b === 'loc';
	}

	function orcCatalogFetchEstoques(items) {
		var url = window.orcEstoquesLoteUrl;
		if (!url || !items || !items.length) return;
		var cods = [];
		items.forEach(function(p) {
			if (!orcCatalogBadgeConsultaEstoque(p.badge)) return;
			var c = (p.codigo != null && p.codigo !== '') ? String(p.codigo).trim() : '';
			if (c && cods.indexOf(c) === -1) {
				cods.push(c);
			}
		});
		if (!cods.length) return;
		if (cods.length > 150) cods = cods.slice(0, 150);
		$.ajax({
			type: 'POST',
			url: url,
			data: { codigos: cods.join(',') },
			dataType: 'json',
			success: function(map) {
				if (!map || typeof map !== 'object') return;
				$('#orc-catalog-body .orc-catalog-item').each(function() {
					var cod = ($(this).attr('data-codigo') || '').trim();
					if (!cod) return;
					var $el = $(this).find('.orc-catalog-stock-line');
					if (!$el.length) return;
					if (map[cod] === undefined || map[cod] === null) {
						$el.removeClass('orc-catalog-stock-line--loading').addClass('orc-catalog-stock-line--err');
						$el.text('Estoque indisponível');
						return;
					}
					var q = map[cod];
					$el.removeClass('orc-catalog-stock-line--loading');
					if (q === -999 || (typeof q === 'number' && q < 0)) {
						$el.addClass('orc-catalog-stock-line--err');
						$el.text('Estoque indisponível');
					} else {
						$el.text('Em estoque (' + q + ')');
					}
				});
			},
			error: function() {
				$('#orc-catalog-body .orc-catalog-stock-line.orc-catalog-stock-line--loading').each(function() {
					$(this).removeClass('orc-catalog-stock-line--loading').addClass('orc-catalog-stock-line--err');
					$(this).text('Estoque indisponível');
				});
			}
		});
	}

	function orcRenderCatalog(items) {
		var $body = $('#orc-catalog-body');
		orcCatalogRenderedItems = items;
		if (!items.length) {
			$body.html('<div class="orc-catalog-empty">Nenhum resultado para a busca.</div>');
			return;
		}
		var html = '';
		items.forEach(function(p, idx) {
			var nome = $('<div>').text(p.descricao || p.nome || '').html();
			var cod = $('<div>').text(p.codigo || '').html();
			var codRaw = (p.codigo != null && p.codigo !== '') ? String(p.codigo) : '';
			var tipoLb = $('<div>').text(p.tipoLabel || 'Item').html();
			var spec = 'Cód. ' + cod + ' · ' + $('<div>').text(orcCatalogUnidadeHint(p)).html();
			var preco = 'R$ ' + numberToReal(parseFloat(p.vlunitario) || 0);
			var badgeClass = orcCatalogBadgeClass(p.badge);
			var margemTxt = (p.margemPct !== null && p.margemPct !== undefined) ? ('Margem: ' + p.margemPct + '%') : 'Margem: —';
			var margemHtml = '<span class="orc-catalog-margem-line">' + $('<div>').text(margemTxt).html() + '</span>';
			var stockHtml = '';
			if (orcCatalogBadgeConsultaEstoque(p.badge)) {
				stockHtml = '<span class="orc-catalog-stock-line orc-catalog-stock-line--loading">Carregando estoque…</span><span class="orc-catalog-meta-sep">·</span>';
			}
			var metaRow = '<div class="orc-catalog-item-meta orc-catalog-stock-margem">' + stockHtml + margemHtml + '</div>';
			var custoNum = parseFloat(p.custoUnit);
			var custoBlock = (!isNaN(custoNum) && custoNum > 0)
				? ('<div class="orc-catalog-item-cost">Custo: R$ ' + numberToReal(custoNum) + '</div>')
				: '';
			html += '<div class="orc-catalog-item" data-idx="' + idx + '" data-codigo="' + orcEscapeHtmlAttr(codRaw.trim()) + '" role="button" tabindex="0">' +
				'<div class="orc-catalog-item-main">' +
					'<div class="orc-catalog-item-title-row">' +
						'<span class="orc-catalog-item-name">' + nome + '</span>' +
						'<span class="' + badgeClass + '">' + tipoLb + '</span>' +
					'</div>' +
					'<div class="orc-catalog-item-spec">' + spec + '</div>' +
					metaRow +
				'</div>' +
				'<div class="orc-catalog-item-prices">' +
					'<div class="orc-catalog-item-price">' + preco + '</div>' +
					custoBlock +
					'<div class="orc-catalog-item-unit">' + $('<div>').text(orcCatalogUnidadeHint(p)).html() + '</div>' +
				'</div>' +
			'</div>';
		});
		$body.html(html);
		orcCatalogFetchEstoques(items);
	}

	function orcCatalogFilter(q) {
		q = (q || '').toLowerCase().trim();
		var filtered = !q ? orcCatalogData.slice() : orcCatalogData.filter(function(p) {
			var d = ((p.descricao || p.nome || '') + ' ' + (p.codigo || '') + ' ' + (p.tipoLabel || '')).toLowerCase();
			return d.indexOf(q) > -1;
		});
		orcRenderCatalog(filtered);
	}

	$('#orc-catalog-overlay').on('click', '.orc-catalog-item', function() {
		var idx = $(this).data('idx');
		var p = orcCatalogRenderedItems[idx];
		if (!p) return;
		$('#idproduto').val(p.id).trigger('change');
		setTimeout(function() {
			if ($('#servico').val() === '' && (p.descricao || p.nome)) {
				$('#servico').val(p.descricao || p.nome);
			}
			if (!$('#valoruni').val() && parseFloat(p.vlunitario) > 0) {
				$('#valoruni').val(numberToReal(parseFloat(p.vlunitario)));
			}
		}, 450);
		$('#orc-catalog-overlay').removeClass('open');
	});

	$('#orc-catalog-search-input').on('keydown', function(e) {
		if (e.key === 'Enter') e.preventDefault();
	});

</script>