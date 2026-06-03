<?php
/**
 * Novo produto — paridade pg-produto-novo (referência pgm_erp_completo.html).
 *
 * @var string $novoCodigoSugerido
 * @var array<int,array<string,mixed>> $novoTabelas
 * @var int $novoTabelaAtivaId
 */
$nav = $this->ErpPrototype->navLinkOpts();
$urlLista = ['controller' => 'ProdutosPrototype', 'action' => 'lista'];
$urlSave = ['controller' => 'ProdutosPrototype', 'action' => 'produtoSave'];
$urlPrecos = ['controller' => 'ProdutosPrototype', 'action' => 'view', 'precos'];
$tabelas = (array)($novoTabelas ?? []);
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;">
			← <?= $this->Html->link(__('Produtos'), $urlLista, array_merge($nav, ['style' => 'color:var(--teal);'])) ?> › <?= h(__('Novo produto')) ?>
		</div>
		<h1 style="font-size:22px;font-weight:600;margin:0;"><?= h(__('Novo cadastro de produto')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);margin-top:2px;"><?= h(__('Preencha as informações principais · Você pode complementar depois')) ?></div>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;">
		<?= $this->Html->link(__('Cancelar'), $urlLista, array_merge($nav, ['class' => 'btn btn-ghost btn-sm'])) ?>
		<button type="submit" form="form-produto-novo" class="btn btn-primary btn-sm">✓ <?= h(__('Salvar produto')) ?></button>
	</div>
</div>

<div class="card" style="margin-bottom:14px;padding:14px 16px;">
	<div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
		<div style="display:flex;align-items:center;gap:8px;"><div style="width:28px;height:28px;border-radius:50%;background:var(--teal);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;">1</div><span style="font-size:12px;font-weight:600;"><?= h(__('Identificação')) ?></span></div>
		<div style="flex:1;height:2px;background:var(--teal);min-width:40px;"></div>
		<div style="display:flex;align-items:center;gap:8px;"><div style="width:28px;height:28px;border-radius:50%;background:var(--teal);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;">2</div><span style="font-size:12px;font-weight:600;"><?= h(__('Preço & Margem')) ?></span></div>
		<div style="flex:1;height:2px;background:var(--teal);min-width:40px;"></div>
		<div style="display:flex;align-items:center;gap:8px;"><div style="width:28px;height:28px;border-radius:50%;background:var(--teal);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;">3</div><span style="font-size:12px;font-weight:600;"><?= h(__('Estoque')) ?></span></div>
	</div>
</div>

<?= $this->Form->create(null, ['url' => $urlSave, 'id' => 'form-produto-novo']) ?>

<div class="g2">
	<div style="display:flex;flex-direction:column;gap:14px;">
		<div class="card">
			<div class="sec-title"><?= h(__('Identificação')) ?></div>
			<div class="g2" style="margin-bottom:10px;">
				<div class="field">
					<label><?= h(__('Categoria')) ?> *</label>
					<select name="tipo" id="novo-tipo">
						<option value="prod">🖥️ <?= h(__('Hardware (produto físico)')) ?></option>
						<option value="lic">💿 <?= h(__('Software / Licença')) ?></option>
						<option value="serv">🛠️ <?= h(__('Serviço')) ?></option>
						<option value="loc">📦 <?= h(__('Locação')) ?></option>
					</select>
				</div>
				<div class="field">
					<label><?= h(__('Código do produto')) ?> *</label>
					<input type="text" name="codigo" value="<?= h($novoCodigoSugerido) ?>" style="font-family:monospace;" required/>
				</div>
			</div>
			<div class="field" style="margin-bottom:10px;">
				<label><?= h(__('Nome do produto')) ?> *</label>
				<input type="text" name="descricao" required placeholder="<?= h(__('Ex: Access Point dual band PoE')) ?>"/>
			</div>
			<div class="g2">
				<div class="field">
					<label><?= h(__('Unidade')) ?> *</label>
					<select name="unidade">
						<option value="un">un · <?= h(__('unidade')) ?></option>
						<option value="h">h · <?= h(__('hora')) ?></option>
						<option value="mês">mês</option>
						<option value="ano">ano</option>
					</select>
				</div>
				<div class="field">
					<label><?= h(__('Status')) ?></label>
					<select name="ativo">
						<option value="1" selected>✓ <?= h(__('Ativo')) ?></option>
						<option value="0">⏸ <?= h(__('Inativo')) ?></option>
					</select>
				</div>
			</div>
		</div>

		<div class="card">
			<div class="sec-title"><?= h(__('Preço de venda & margem')) ?></div>
			<div class="g2" style="margin-bottom:10px;">
				<div class="field">
					<label><?= h(__('Custo de aquisição')) ?></label>
					<input type="text" name="custo_aquisicao" id="novo-custo" placeholder="0,00" style="text-align:right;font-weight:600;"/>
				</div>
				<div class="field">
					<label><?= h(__('Preço de venda')) ?> *</label>
					<input type="text" name="vlunitario" id="novo-venda" placeholder="0,00" style="text-align:right;font-weight:700;font-size:16px;color:var(--teal-dark);" required/>
					<div style="font-size:11px;color:var(--text-muted);margin-top:4px;"><?= h(__('Gravado em produtos.vlunitario')) ?></div>
				</div>
			</div>
			<?php if ($tabelas !== []) : ?>
				<div style="margin-top:10px;">
					<div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;font-weight:600;margin-bottom:8px;"><?= h(__('Tabelas de preço da empresa')) ?></div>
					<div style="border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;font-size:12px;">
						<?php foreach ($tabelas as $tb) : ?>
							<div style="display:grid;grid-template-columns:1fr auto;gap:8px;padding:8px 12px;border-bottom:1px solid var(--border-light);">
								<div>
									<strong><?= h((string)$tb['nome']) ?></strong>
									<?php if (!empty($tb['vigente'])) : ?><span class="badge b-paga" style="font-size:9px;margin-left:6px;"><?= h(__('Vigente')) ?></span><?php endif; ?>
								</div>
								<?= $this->Html->link(__('Abrir →'), ['controller' => 'ProdutosPrototype', 'action' => 'view', 'preco-tabela-detalhe', '?' => ['tabela' => (int)$tb['id']]], array_merge($nav, ['class' => 'btn btn-ghost btn-xs'])) ?>
							</div>
						<?php endforeach; ?>
					</div>
					<div style="font-size:11px;color:var(--text-muted);margin-top:6px;"><?= h(__('Após salvar, vincule o item na tabela ou use o Centro de Cálculo para ajustar.')) ?></div>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<div style="display:flex;flex-direction:column;gap:14px;">
		<div class="card">
			<div class="sec-title"><?= h(__('Controle de estoque')) ?></div>
			<div class="field">
				<label><?= h(__('Estoque atual')) ?></label>
				<input type="text" name="estoque_atual" value="0" style="text-align:right;font-weight:700;"/>
			</div>
		</div>
		<div class="card">
			<div class="sec-title"><?= h(__('Fluxo de preços')) ?></div>
			<div style="display:flex;flex-direction:column;gap:8px;font-size:12px;">
				<?= $this->Html->link('📋 ' . __('Tabela de preços'), $urlPrecos, array_merge($nav, ['class' => 'btn btn-ghost btn-sm'])) ?>
				<?= $this->Html->link('🧮 ' . __('Centro de cálculo (após salvar)'), ['controller' => 'ProdutosPrototype', 'action' => 'view', 'precificacao'], array_merge($nav, ['class' => 'btn btn-ghost btn-sm'])) ?>
			</div>
		</div>
	</div>
</div>

<?= $this->Form->end() ?>

<script>
(function () {
	var custo = document.getElementById('novo-custo');
	var venda = document.getElementById('novo-venda');
	function calcMargem() {
		if (!custo || !venda) return;
		var c = parseFloat(String(custo.value).replace(/\./g, '').replace(',', '.')) || 0;
		var v = parseFloat(String(venda.value).replace(/\./g, '').replace(',', '.')) || 0;
		if (v > 0 && c >= 0) {
			var m = ((1 - c / v) * 100).toFixed(1).replace('.', ',');
			venda.title = 'Margem estimada: ' + m + '%';
		}
	}
	if (custo) { custo.addEventListener('input', calcMargem); }
	if (venda) { venda.addEventListener('input', calcMargem); }
})();
</script>
