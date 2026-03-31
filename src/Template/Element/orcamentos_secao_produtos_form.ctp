<?php
/**
 * Seção "Produtos e serviços" compartilhada entre Novo e Editar orçamento.
 *
 * @var string $orcModo 'add'|'edit'
 * @var \Cake\Datasource\EntityInterface|null $orcamento Em edit
 * @var int|null $role Perfil (0 = equipe)
 */
$orcModo = $orcModo ?? 'add';
$isAdd = ($orcModo === 'add');
$role = isset($role) ? (int)$role : 0;
$showItemForm = $isAdd
	|| ($orcamento !== null
		&& (int)$orcamento->get('status') !== (int)C_OrcamentoStatusAprovado
		&& $role === 0);
?>
<?php if (!$showItemForm && !$isAdd) : ?>
	<div class="orc-sec-title">Itens do orçamento</div>
<?php else : ?>
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
			<div class="orc-margin-card-val" id="ms-lucro" style="color:#00c08b;">R$ 0,00</div>
			<div class="orc-margin-card-lbl">Lucro bruto</div>
		</div>
		<div class="orc-margin-card">
			<div class="orc-margin-card-val" id="ms-margem" style="color:#00c08b;">0%</div>
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
				<?= $this->Form->control('observacao', ['class' => 'form-control orc-item-obs-field', 'label' => false, 'placeholder' => 'Detalhes...', 'id' => 'observacao']) ?>
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
<?php endif; ?>
