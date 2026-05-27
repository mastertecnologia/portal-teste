<?php
	$this->append('css', $this->element('pgm_premium_css', ['name' => 'orcamentos-premium']));
	$this->Html->script('/js/orcamentos', ['block' => true]);
	$orcListRoute = \App\Utility\PortalUi::listRoute('orcamentos') ?? ['controller' => 'Orcamentos', 'action' => 'index'];
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
<div class="orcamento-page">
<div class="orc-premium-wrap orc-premium-form orcamento-content">

	<?= $this->Form->create($orcamento, ['url' => ['action' => 'add'], 'enctype' => 'multipart/form-data', 'type' => 'file', 'class' => 'form-material', 'id' => 'form-orc-add', 'data-turbo' => 'false']); ?>
	<?= $this->Form->hidden('item_edit_id', ['id' => 'item_edit_id']); ?>
	<?= $this->Form->hidden('desconto_valor', ['id' => 'orc-desconto-valor-hidden', 'value' => '0']) ?>
	<?= $this->Form->hidden('desconto_tipo', ['id' => 'orc-desconto-tipo-hidden', 'value' => 'pct']) ?>

	<?= $this->element('orcamentos_proposta_topbar', [
		'orcListRoute' => $orcListRoute,
		'crumbCurrent' => 'Novo',
		'orcNumero' => (int)($orcPreviewNumero ?? 0),
		'cancelUrl' => $orcListRoute,
	]) ?>

	<section class="orcamento-stepper-card" aria-label="Etapas do orçamento">
		<?= $this->element('orcamentos_stepper') ?>
	</section>

	<?= $this->element('orcamentos_card_cliente', [
		'clientes' => $clientes,
		'orcFormaPagamentoOpcoes' => $orcFormaPagamentoOpcoes ?? [],
		'orcValidoateDefault' => date('d/m/Y'),
	]) ?>

	<section class="orcamento-card orcamento-card--produtos">
		<?= $this->element('orcamentos_secao_produtos_form', ['orcModo' => 'add']); ?>
		<div id="carrinho" class="orc-carrinho-slot m-t-10"></div>
		<?= $this->element('orcamentos_secao_produtos_rodape', ['orcModo' => 'add']); ?>
	</section>

	<section class="orcamento-card orc-obs-card">
		<div class="orc-sec-title">Observações</div>
		<?= $this->Form->textarea('solicitacao', [
			'novalidate' => true,
			'id' => 'observacoes',
			'class' => 'form-control orc-obs-textarea',
			'label' => false,
			'rows' => 3,
			'placeholder' => 'Condições, prazos, garantias...',
		]) ?>
	</section>

	<?= $this->element('orcamentos_footer_proposta', [
		'cancelUrl' => $orcListRoute,
		'submitLabel' => 'Avançar →',
		'showLimpar' => true,
	]) ?>

	<?= $this->Form->end(); ?>

</div>
</div>
</div>

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

<?= $this->element('orcamentos_form_shared_js', [
	'orcItemDescontoEnabled' => !empty($orcItemDescontoEnabled),
	'mode' => 'add',
	'idcarrinho' => $idcarrinho,
	'clientesMetaJson' => $clientesMetaJson ?? '{}',
	'produtosCatalogoJson' => $produtosCatalogoJson ?? '[]',
]) ?>
