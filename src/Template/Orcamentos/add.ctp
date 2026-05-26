<?php
	$this->append('css', $this->element('pgm_premium_css', ['name' => 'orcamentos-premium']));
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
			<div class="orc-form-crumb">
				<?php
				$orcListRoute = \App\Utility\PortalUi::listRoute('orcamentos') ?? ['controller' => 'Orcamentos', 'action' => 'index'];
			?>
			<?= $this->Html->link('Orçamentos', $orcListRoute, ['escape' => false]) ?> › <span class="orc-form-crumb-current">Novo</span>
			</div>
			<h1 class="orc-h1" id="orc-novo-proposta-title">Proposta de Orçamento</h1>
		</div>
		<div class="orc-page-head-actions">
			<?= $this->Html->link('Cancelar', $orcListRoute, ['class' => 'btn btn-orc-form-secondary']) ?>
			<?= $this->Form->button('Avançar para revisão →', [
				'type' => 'submit',
				'class' => 'btn btn-orc-premium-primary',
				'escape' => false,
			]) ?>
		</div>
	</div>

	<?= $this->element('orcamentos_stepper') ?>

	<div class="card orc-premium-card-inner orc-card-mb-14">
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
								'class' => 'form-control orc-native-select',
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
					<input type="text" class="form-control orc-input-readonly-fill" id="orc-cli-doc" readonly placeholder="Auto-preenchido" />
				</div>
				<div class="col-md-4 col-sm-12">
					<label class="control-label">E-mail do cliente</label>
					<input type="email" class="form-control" id="orc-cli-email" autocomplete="email" placeholder="Preenchido ao escolher o cliente (editável)" />
				</div>
				<div class="col-md-4 col-sm-12">
					<label class="control-label">Contato / responsável</label>
					<input type="text" class="form-control orc-input-readonly-fill" id="orc-cli-contato" readonly placeholder="Auto-preenchido" />
				</div>
			</div>
		</div>
	</div>

	<div class="card orc-premium-card-inner orc-card-mb-14">
		<div class="card-body">
			<?= $this->element('orcamentos_secao_produtos_form', ['orcModo' => 'add']); ?>

			<div id="carrinho" class="m-t-10"></div>

			<?= $this->element('orcamentos_secao_produtos_rodape', ['orcModo' => 'add']); ?>
		</div>
	</div>

	<div class="orc-obs-block">
		<div class="orc-sec-title">Observações</div>
		<label class="control-label" for="observacoes">Condições, prazos, garantias</label>
		<p class="orc-obs-source-hint">Pré-visualização do texto (inclui HTML gravado no orçamento). Edite o conteúdo no campo abaixo.</p>
		<iframe id="orc-obs-solicitacao-preview" class="orc-obs-preview-frame" title="Pré-visualização das condições" sandbox=""></iframe>
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
			<?= $this->Html->link('Cancelar', $orcListRoute, ['class' => 'btn btn-orc-form-secondary']) ?>
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

<?= $this->element('orcamentos_form_shared_js', [
	'mode' => 'add',
	'idcarrinho' => $idcarrinho,
	'clientesMetaJson' => $clientesMetaJson ?? '{}',
	'produtosCatalogoJson' => $produtosCatalogoJson ?? '[]',
]) ?>