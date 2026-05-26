<?php
/**
 * Seção "Produtos e serviços" — layout pg-novo (pgm_orcamentos_premium.html).
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

$orcFieldTpl = [
	'templates' => [
		'inputContainer' => '{{content}}',
		'selectContainer' => '{{content}}',
		'textareaContainer' => '{{content}}',
	],
];
?>
<?php if (!$showItemForm && !$isAdd) : ?>
	<div class="orc-sec-title">Itens do orçamento</div>
<?php else : ?>
	<div class="orc-sec-title orc-sec-title--split">
		<span>Produtos e serviços</span>
		<button type="button" class="btn btn-orc-outline-teal btn-sm" onclick="orcCatalogOpen();">
			<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" width="13" height="13" aria-hidden="true"><rect x="2" y="2" width="12" height="12" rx="1.5"/><line x1="5" y1="6" x2="11" y2="6"/><line x1="5" y1="9" x2="11" y2="9"/></svg>
			Buscar no catálogo
		</button>
	</div>

	<div class="orc-margin-summary" id="orc-margin-summary">
		<div class="orc-margin-card">
			<div class="orc-margin-card-val" id="ms-subtotal">R$ 0,00</div>
			<div class="orc-margin-card-lbl">Subtotal venda</div>
		</div>
		<div class="orc-margin-card orc-margin-card--with-bar">
			<div class="orc-margin-card-val orc-margin-card-val--muted" id="ms-custo">R$ 0,00</div>
			<div class="orc-margin-card-lbl">Custo total</div>
			<div class="orc-margin-bar"><div class="orc-margin-fill orc-margin-fill--muted" id="ms-bar-custo"></div></div>
		</div>
		<div class="orc-margin-card orc-margin-card--with-bar">
			<div class="orc-margin-card-val orc-margin-card-val--teal" id="ms-lucro">R$ 0,00</div>
			<div class="orc-margin-card-lbl">Lucro bruto</div>
			<div class="orc-margin-bar"><div class="orc-margin-fill" id="ms-bar-lucro"></div></div>
		</div>
		<div class="orc-margin-card orc-margin-card--with-bar">
			<div class="orc-margin-card-val orc-margin-card-val--teal" id="ms-margem">0%</div>
			<div class="orc-margin-card-lbl">Margem bruta</div>
			<div class="orc-margin-bar"><div class="orc-margin-fill" id="ms-bar"></div></div>
		</div>
	</div>

	<div class="orc-manual-grid orc-manual-grid--row1">
		<div class="orc-field">
			<label class="control-label">Código</label>
			<?= $this->Form->control('idproduto', array_merge($orcFieldTpl, ['class' => 'form-control selectpicker', 'data-live-search' => true, 'options' => $produtos, 'value' => 0, 'label' => false, 'title' => 'SKU'])) ?>
		</div>
		<div class="orc-field orc-field--grow">
			<label class="control-label">Produto / Serviço</label>
			<?= $this->Form->control('servico', array_merge($orcFieldTpl, ['class' => 'form-control', 'label' => false, 'placeholder' => 'Descrição...'])) ?>
			<small class="qtdEstoque text-muted"></small>
		</div>
		<div class="orc-field orc-field--tipo orc-field--compact">
			<label class="control-label">Tipo</label>
			<select id="orc-f-tip" class="form-control orc-native-select orc-input-compact" title="Tipo do item">
				<option value="prod">Produto</option>
				<option value="serv">Serviço</option>
				<option value="lic">Licença</option>
				<option value="loc">Locação</option>
			</select>
		</div>
		<div class="orc-field orc-field--qtd orc-field--compact">
			<label class="control-label">Qtde.</label>
			<?= $this->Form->control('quantidade', array_merge($orcFieldTpl, [
				'type' => 'number',
				'min' => 0,
				'step' => 'any',
				'onkeypress' => 'return SomenteNumero(event, "#quantidade")',
				'class' => 'quantidade form-control orc-input-compact',
				'label' => false,
				'value' => '1',
			])) ?>
		</div>
		<div class="orc-field orc-field--vl orc-field--compact">
			<label class="control-label">Vl. Unit. (R$)</label>
			<?= $this->Form->control('valoruni', array_merge($orcFieldTpl, [
				'onkeypress' => 'return SomenteNumero(event, "#valoruni")',
				'class' => 'form-control mascaramonetaria orc-input-compact',
				'label' => false,
				'placeholder' => '0,00',
			])) ?>
		</div>
	</div>
	<div class="orc-manual-grid orc-manual-grid--row2">
		<div class="orc-field">
			<label class="control-label">Custo unit. (R$)</label>
			<input type="text" class="form-control orc-input-readonly-fill orc-custo-unit-inp" id="orc-custo-unit" readonly placeholder="0,00" />
		</div>
		<div class="orc-field">
			<label class="control-label">Vl. Mensal (R$)</label>
			<?= $this->Form->control('valormensal', array_merge($orcFieldTpl, ['onkeypress' => 'return SomenteNumero(event, "#valormensal")', 'class' => 'mensal form-control mascaramonetaria', 'label' => false, 'placeholder' => '0,00'])) ?>
		</div>
		<div class="orc-field orc-field--disc">
			<label class="control-label">Desconto item</label>
			<div class="orc-item-discount-wrap">
				<input type="number" id="orc-item-disc-val" class="form-control orc-discount-inp" value="0" min="0" step="0.01" title="Desconto neste item" />
				<select id="orc-item-disc-tipo" class="form-control orc-native-select orc-discount-select">
					<option value="pct">%</option>
					<option value="fix">R$</option>
				</select>
			</div>
		</div>
		<div class="orc-field orc-field--grow">
			<label class="control-label">Descrição adicional</label>
			<?= $this->Form->control('observacao', array_merge($orcFieldTpl, ['class' => 'form-control orc-item-obs-field', 'label' => false, 'placeholder' => 'Detalhes...', 'id' => 'observacao'])) ?>
		</div>
	</div>
	<div class="orc-is-hidden">
		<?= $this->Form->control('tipo', array_merge($orcFieldTpl, ['class' => 'quantidade form-control', 'options' => ['Unidade', 'Hora'], 'label' => false])) ?>
		<?= $this->Form->control('valordoservico', array_merge($orcFieldTpl, ['class' => 'form-control', 'label' => false, 'disabled' => true])) ?>
	</div>

	<button type="button" class="orc-add-manual-row" id="btn-addservico" title="Adicionar item ao orçamento">
		<svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><line x1="8" y1="2" x2="8" y2="14"/><line x1="2" y1="8" x2="14" y2="8"/></svg>
		Adicionar item manualmente
	</button>

	<div class="orc-inline-actions orc-is-hidden" id="orc-item-edit-actions">
		<button type="button" class="btn btn-orc-form-secondary btn-orc-compact" id="btn-cancelaredicao">
			Cancelar edição
		</button>
		<button type="button" class="btn btn-orc-premium-primary btn-orc-compact" id="btn-editarservico">
			<i class="fa fa-check"></i> Atualizar
		</button>
	</div>
<?php endif; ?>
