<?php
/**
 * Wizard · 1/5 Novo orçamento — mockup pg-novo.
 *
 * @var \App\View\AppView $this
 * @var array<int,array{label:string,state:string}> $wizardSteps
 * @var array<int,array<string,mixed>> $orcCatalogo
 * @var array<int,string> $orcClientesOptions
 */
$H = $this->ErpPrototype;
$catalogo = (array)($orcCatalogo ?? []);
$clientes = (array)($orcClientesOptions ?? []);
$tipoLbls = ['prod' => __('Produto'), 'serv' => __('Serviço'), 'lic' => __('Licença'), 'loc' => __('Locação')];
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Comercial · Novo orçamento')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">📝 <?= h(__('Cabeçalho e cliente')) ?></h1>
	</div>
	<?= $this->Html->link('← ' . __('Cancelar'), ['controller' => 'OrcamentosPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
</div>

<?= $H->stepper($wizardSteps) ?>

<div class="card">
	<div class="sec-title"><?= h(__('Dados do cliente')) ?></div>
	<div class="g2">
		<div class="field">
			<label><?= h(__('Cliente')) ?></label>
			<select>
				<option><?= h(__('— Selecione um cliente cadastrado —')) ?></option>
				<?php foreach ($clientes as $id => $nome) : ?>
					<option value="<?= (int)$id ?>"><?= h((string)$nome) ?></option>
				<?php endforeach; ?>
			</select>
			<small style="color:var(--text-muted);font-size:11px;"><?= sprintf(h(__('%d clientes ativos no escopo da empresa')), count($clientes)) ?></small>
		</div>
		<div class="field">
			<label><?= h(__('Vendedor')) ?></label>
			<select><option><?= h(__('— Selecione —')) ?></option></select>
		</div>
		<div class="field">
			<label><?= h(__('Centro de custo')) ?></label>
			<select><option><?= h(__('Comercial')) ?></option></select>
		</div>
		<div class="field">
			<label><?= h(__('Validade (dias)')) ?></label>
			<input type="number" value="30" min="1" max="180">
		</div>
	</div>
</div>

<div class="card">
	<div class="sec-title"><?= h(__('Catálogo · escolha itens iniciais')) ?></div>
	<form method="get" style="margin-bottom:12px;">
		<input type="hidden" name="page" value="novo">
		<div style="display:flex;gap:8px;flex-wrap:wrap;">
			<input type="search" name="q" value="<?= h((string)$this->request->getQuery('q', '')) ?>" placeholder="🔍 <?= h(__('Buscar produto/serviço por código ou descrição...')) ?>" style="flex:1;min-width:240px;padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:13px;">
			<button type="submit" class="btn btn-ghost btn-sm"><?= h(__('Filtrar')) ?></button>
		</div>
	</form>
	<?php if ($catalogo === []) : ?>
		<p style="color:var(--text-muted);margin:0;font-size:12px;"><?= h(__('Nenhum produto ativo encontrado. Cadastre via módulo Produtos.')) ?></p>
	<?php else : ?>
		<div style="max-height:340px;overflow-y:auto;border:1px solid var(--border-light);border-radius:var(--radius);">
			<?php foreach ($catalogo as $p) :
				$est = (float)$p['estoque'];
				$estColor = $est <= 0 ? '#7A1822' : ($est < 5 ? '#8A4D02' : 'var(--teal-dark)');
				$tipoLbl = (string)($tipoLbls[$p['tipo']] ?? ucfirst((string)$p['tipo']));
			?>
				<div style="padding:10px 14px;display:flex;justify-content:space-between;align-items:center;gap:12px;border-bottom:1px solid var(--border-light);">
					<div style="flex:1;min-width:0;">
						<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
							<span style="font-family:monospace;font-size:11px;font-weight:600;color:var(--teal-dark);background:var(--teal-light);padding:2px 6px;border-radius:4px;"><?= h((string)$p['codigo']) ?></span>
							<strong style="font-size:12px;"><?= h(\Cake\Utility\Text::truncate((string)$p['descricao'], 64, ['ellipsis' => '…'])) ?></strong>
							<span class="badge b-<?= h((string)$p['tipo'] ?: 'arq') ?>" style="font-size:9px;"><?= h($tipoLbl) ?></span>
						</div>
						<div style="font-size:11px;color:var(--text-muted);margin-top:3px;">
							<?= h(__('Unidade')) ?>: <?= h((string)$p['unidade'] ?: '—') ?>
							· <?= h(__('Estoque')) ?>: <span style="color:<?= h($estColor) ?>;font-weight:600;"><?= number_format($est, 2, ',', '.') ?></span>
						</div>
					</div>
					<div style="text-align:right;min-width:110px;">
						<div style="font-size:14px;font-weight:700;color:var(--teal-dark);"><?= h($H->brl((float)$p['preco'])) ?></div>
						<button type="button" class="btn btn-ghost btn-xs" disabled><?= h(__('+ Adicionar')) ?></button>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<div style="font-size:11px;color:var(--text-muted);margin-top:8px;">
			<?= sprintf(h(__('Mostrando %d produtos · ajuste o filtro para refinar')), count($catalogo)) ?>
		</div>
	<?php endif; ?>
</div>

<div class="card">
	<div class="sec-title"><?= h(__('Observações iniciais')) ?></div>
	<div class="field">
		<textarea rows="3" placeholder="<?= h(__('Ex.: condições comerciais, prazo de entrega, garantia...')) ?>"></textarea>
	</div>
</div>

<div class="footer-bar">
	<?= $this->Html->link('← ' . __('Voltar à lista'), ['controller' => 'OrcamentosPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
	<div style="display:flex;gap:8px;">
		<?= $this->Html->link(__('Salvar rascunho'), ['controller' => 'Orcamentos', 'action' => 'add'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link(__('Avançar para itens') . ' →', ['controller' => 'OrcamentosPrototype', 'action' => 'view', 'revisao'], ['class' => 'btn btn-primary btn-sm']) ?>
	</div>
</div>

<div class="alert-box alert-blue" style="margin-top:14px;">
	<?= h(__('Wizard em modo demonstração: a gravação real continua no fluxo clássico até a integração final.')) ?>
</div>
