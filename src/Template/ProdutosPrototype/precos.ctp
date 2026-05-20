<?php
/**
 * Tabela de preços — mockup pg-precos.
 *
 * @var \App\View\AppView $this
 * @var array<int,array<string,mixed>> $precosItems
 * @var array{total:int,media:float,min:float,max:float} $precosKpi
 * @var string $precosFiltro
 */
$H = $this->ErpPrototype;
$tipoLbls = ['prod' => __('Produto'), 'serv' => __('Serviço'), 'lic' => __('Licença'), 'loc' => __('Locação')];
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Produtos')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">💲 <?= h(__('Tabela de preços')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);"><?= sprintf(h(__('%d itens ativos · ticket médio %s')), (int)$precosKpi['total'], $H->brl((float)$precosKpi['media'])) ?></div>
	</div>
	<div style="display:flex;gap:8px;">
		<?= $this->Html->link('← ' . __('Voltar'), ['controller' => 'ProdutosPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('📊 ' . __('Histórico'), ['controller' => 'ProdutosPrototype', 'action' => 'view', 'historico-precos'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('🧮 ' . __('Centro de cálculo'), ['controller' => 'ProdutosPrototype', 'action' => 'view', 'precificacao'], ['class' => 'btn btn-primary btn-sm']) ?>
	</div>
</div>

<div class="summary-grid" style="margin-bottom:14px;">
	<div class="summary-card" style="border-left:3px solid var(--teal);"><div class="lbl"><?= h(__('Itens')) ?></div><div class="val" style="color:var(--teal-dark);"><?= (int)$precosKpi['total'] ?></div></div>
	<div class="summary-card" style="border-left:3px solid var(--blue);"><div class="lbl"><?= h(__('Médio')) ?></div><div class="val" style="color:#0C447C;font-size:16px;"><?= h($H->brl((float)$precosKpi['media'])) ?></div></div>
	<div class="summary-card" style="border-left:3px solid var(--teal-mid);"><div class="lbl"><?= h(__('Menor preço')) ?></div><div class="val" style="color:var(--teal-dark);font-size:16px;"><?= h($H->brl((float)$precosKpi['min'])) ?></div></div>
	<div class="summary-card" style="border-left:3px solid var(--amber);"><div class="lbl"><?= h(__('Maior preço')) ?></div><div class="val" style="color:#8A4D02;font-size:16px;"><?= h($H->brl((float)$precosKpi['max'])) ?></div></div>
</div>

<div class="card" style="padding:0;overflow:hidden;">
	<form method="get" style="padding:12px 14px;background:var(--bg-surface);border-bottom:1px solid var(--border-light);display:flex;gap:8px;flex-wrap:wrap;">
		<input type="search" name="q" value="<?= h((string)$precosFiltro) ?>" placeholder="🔍 <?= h(__('Buscar por descrição ou código...')) ?>" style="flex:1;min-width:280px;padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:13px;">
		<button type="submit" class="btn btn-primary btn-sm"><?= h(__('Filtrar')) ?></button>
	</form>
	<div style="overflow-x:auto;">
		<table class="tbl" style="margin:0;">
			<thead>
				<tr>
					<th><?= h(__('Código')) ?></th>
					<th><?= h(__('Descrição')) ?></th>
					<th><?= h(__('Tipo')) ?></th>
					<th><?= h(__('Un')) ?></th>
					<th class="r"><?= h(__('Venda')) ?></th>
					<th class="r"><?= h(__('Loc. dia')) ?></th>
					<th class="r"><?= h(__('Loc. semana')) ?></th>
					<th class="r"><?= h(__('Loc. mês')) ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php if ($precosItems === []) : ?>
					<tr><td colspan="9" style="padding:24px;text-align:center;color:var(--text-muted);"><?= h(__('Nenhum item.')) ?></td></tr>
				<?php else : foreach ($precosItems as $p) :
					$tipo = (string)$p['tipo'];
					$tipoLbl = (string)($tipoLbls[$tipo] ?? ucfirst($tipo ?: '—'));
					$badge = 'arq';
					if ($tipo === 'prod') $badge = 'prod';
					elseif ($tipo === 'serv') $badge = 'serv';
					elseif ($tipo === 'lic') $badge = 'lic';
					elseif ($tipo === 'loc') $badge = 'loc';
				?>
					<tr>
						<td style="font-family:monospace;font-size:11px;font-weight:600;"><?= h((string)$p['codigo']) ?></td>
						<td><?= h(\Cake\Utility\Text::truncate((string)$p['descricao'], 70, ['ellipsis' => '…'])) ?></td>
						<td><span class="badge b-<?= h($badge) ?>" style="font-size:9px;"><?= h($tipoLbl) ?></span></td>
						<td class="mu"><?= h((string)$p['unidade']) ?></td>
						<td class="r">
							<form method="post" action="<?= h($this->Url->build(['controller' => 'ProdutosPrototype', 'action' => 'precoSave'])) ?>" style="display:flex;gap:4px;align-items:center;justify-content:flex-end;margin:0;">
								<input type="hidden" name="_csrfToken" value="<?= h($this->request->getAttribute('csrfToken')) ?>">
								<input type="hidden" name="produto_id" value="<?= (int)$p['id'] ?>">
								<input type="hidden" name="q" value="<?= h((string)$precosFiltro) ?>">
								<input type="number" step="0.01" min="0" name="vlunitario" value="<?= h(number_format((float)$p['venda'], 2, '.', '')) ?>" style="width:90px;padding:4px 8px;border:1px solid var(--border);border-radius:4px;font-size:12px;text-align:right;">
								<button type="submit" class="btn btn-ghost btn-xs" title="<?= h(__('Salvar')) ?>">💾</button>
							</form>
						</td>
						<td class="r" style="color:var(--text-muted);"><?php if ((float)$p['loc_diaria'] > 0) : ?><?= h($H->brl((float)$p['loc_diaria'])) ?><?php else : ?>—<?php endif; ?></td>
						<td class="r" style="color:var(--text-muted);"><?php if ((float)$p['loc_semanal'] > 0) : ?><?= h($H->brl((float)$p['loc_semanal'])) ?><?php else : ?>—<?php endif; ?></td>
						<td class="r" style="color:var(--text-muted);"><?php if ((float)$p['loc_mensal'] > 0) : ?><?= h($H->brl((float)$p['loc_mensal'])) ?><?php else : ?>—<?php endif; ?></td>
						<td class="r"><?= $this->Html->link(__('Editar'), ['controller' => 'Produtos', 'action' => 'edit', (int)$p['id']], ['class' => 'btn btn-ghost btn-xs']) ?></td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
</div>

<div class="alert-box alert-blue" style="margin-top:14px;">
	<?= h(__('Edite o preço de venda diretamente na coluna acima e clique em 💾 para salvar. Locação ainda usa o módulo clássico. O centro de cálculo permite simular margens e impostos.')) ?>
</div>
