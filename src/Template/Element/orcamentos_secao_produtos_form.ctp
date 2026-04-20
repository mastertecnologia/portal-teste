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

/** Evita div.form-group dentro de <td> (quebra alinhamento da tabela) */
$orcTblFieldTpl = [
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
		<button type="button" class="btn btn-orc-outline-teal" onclick="orcCatalogOpen();">
			<i class="fa fa-list"></i> Buscar no catálogo
		</button>
	</div>

	<div class="orc-margin-summary" id="orc-margin-summary">
		<div class="orc-margin-card">
			<div class="orc-margin-card-val" id="ms-subtotal">R$ 0,00</div>
			<div class="orc-margin-card-lbl">Subtotal venda</div>
		</div>
		<div class="orc-margin-card">
			<div class="orc-margin-card-val orc-margin-card-val--muted" id="ms-custo">R$ 0,00</div>
			<div class="orc-margin-card-lbl">Custo total</div>
		</div>
		<div class="orc-margin-card">
			<div class="orc-margin-card-val orc-margin-card-val--teal" id="ms-lucro">R$ 0,00</div>
			<div class="orc-margin-card-lbl">Lucro bruto</div>
		</div>
		<div class="orc-margin-card">
			<div class="orc-margin-card-val orc-margin-card-val--teal" id="ms-margem">0%</div>
			<div class="orc-margin-card-lbl">Margem bruta</div>
			<div class="orc-margin-bar"><div class="orc-margin-fill" id="ms-bar"></div></div>
		</div>
	</div>

	<div class="orc-premium-carrinho-tbl-wrap table-responsive orc-item-insert-wrap">
		<table class="table orc-premium-carrinho-tbl orc-item-insert-tbl" id="orcItemInsertTable">
			<?= $this->element('orcamentos_carrinho_colgroup') ?>
			<thead>
				<tr>
					<th>Ordem</th>
					<th>Código</th>
					<th>Produto/Serviço</th>
					<th>Descrição</th>
					<th class="text-right">Pagamento</th>
					<th class="text-right">Qtde.</th>
					<th class="text-right">Vl. Mensal</th>
					<th class="text-right">Vl. Unit.</th>
					<th class="text-right">Valor Total</th>
					<th class="text-right">Custo</th>
					<th class="text-right">Margem</th>
					<th class="text-center">Ações</th>
				</tr>
			</thead>
			<tbody>
				<tr class="orc-item-insert-row">
					<td class="orc-insert-muted">—</td>
					<td class="orc-insert-td-input"><?= $this->Form->control('idproduto', array_merge($orcTblFieldTpl, ['class' => 'form-control selectpicker', 'data-live-search' => true, 'options' => $produtos, 'value' => 0, 'label' => false])) ?></td>
					<td class="orc-insert-td-input"><?= $this->Form->control('servico', array_merge($orcTblFieldTpl, ['class' => 'form-control', 'label' => false])) ?>
						<small class="qtdEstoque text-muted"></small>
					</td>
					<td class="orc-insert-desc-preview" id="orc-insert-desc-preview" title="Descrição adicional do item (espelha o campo abaixo da tabela)">—</td>
					<td class="orc-insert-td-input text-right"><?= $this->Form->control('tipo', array_merge($orcTblFieldTpl, ['class' => 'quantidade form-control', 'options' => ['Unidade', 'Hora'], 'label' => false])) ?></td>
					<td class="orc-insert-td-input text-right"><?= $this->Form->control('quantidade', array_merge($orcTblFieldTpl, ['onkeypress' => 'return SomenteNumero(event, "#quantidade")', 'class' => 'quantidade form-control', 'label' => false])) ?></td>
					<td class="orc-insert-td-input text-right"><?= $this->Form->control('valormensal', array_merge($orcTblFieldTpl, ['onkeypress' => 'return SomenteNumero(event, "#valormensal")', 'class' => 'mensal form-control mascaramonetaria', 'label' => false])) ?></td>
					<td class="orc-insert-td-input text-right"><?= $this->Form->control('valoruni', array_merge($orcTblFieldTpl, ['onkeypress' => 'return SomenteNumero(event, "#valoruni")', 'class' => 'form-control mascaramonetaria', 'label' => false])) ?></td>
					<td class="orc-insert-td-input text-right"><?= $this->Form->control('valordoservico', array_merge($orcTblFieldTpl, ['class' => 'form-control', 'label' => false, 'disabled' => true])) ?></td>
					<td class="orc-insert-muted text-right">—</td>
					<td class="orc-insert-muted text-right">—</td>
					<td class="orc-insert-acoes text-center">
						<button type="button" class="orc-add-row" id="btn-addservico" title="Adicionar item ao orçamento">
							<i class="fa fa-plus orc-add-row-ic"></i><span class="orc-add-row-txt"> Adicionar</span>
						</button>
					</td>
				</tr>
				<tr class="orc-item-insert-obs-row">
					<td colspan="12" class="orc-insert-obs-cell">
						<label class="control-label">Descrição adicional</label>
						<?= $this->Form->control('observacao', array_merge($orcTblFieldTpl, ['class' => 'form-control orc-item-obs-field', 'label' => false, 'placeholder' => 'Detalhes...', 'id' => 'observacao'])) ?>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
	<div class="orc-inline-actions orc-is-hidden" id="orc-item-edit-actions">
		<button type="button" class="btn btn-orc-form-secondary btn-orc-compact" id="btn-cancelaredicao">
			Cancelar edição
		</button>
		<button type="button" class="btn btn-orc-premium-primary btn-orc-compact" id="btn-editarservico">
			<i class="fa fa-check"></i> Atualizar
		</button>
	</div>
<?php endif; ?>
