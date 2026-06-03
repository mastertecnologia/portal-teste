<?php
/**
 * Tabela de Preços & Margem — pg-precos (paridade mock + dados reais).
 *
 * @var \App\View\AppView $this
 * @var array<int,array<string,mixed>> $precosItems
 * @var int $precosItemsTotal
 * @var int $precosTotalCatalogo
 * @var array<string,mixed> $precosKpi
 * @var string $precosFiltro
 * @var string $precosFiltroMargem
 * @var array<int,array<string,mixed>> $precosTimeline
 * @var array{inicio:string,fim:string} $precosVigencia
 */
$H = $this->ErpPrototype;
$k = $precosKpi;
$vig = $precosVigencia;
$urlPrecos = ['controller' => 'ProdutosPrototype', 'action' => 'view', 'precos'];
$filtroAtual = (string)$precosFiltroMargem;
$mkFiltro = static function (string $f) use ($urlPrecos, $precosFiltro, $filtroAtual) {
	$q = $precosFiltro !== '' ? ['q' => $precosFiltro] : [];
	if ($f !== 'todos') {
		$q['f'] = $f;
	}
	$active = ($f === 'todos' && $filtroAtual === 'todos') || $filtroAtual === $f;
	$style = $active ? 'background:var(--teal-light);color:var(--teal-dark);' : '';

	return ['url' => $urlPrecos + ($q !== [] ? ['?' => $q] : []), 'style' => $style];
};
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;"><?= h(__('Produtos')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;"><?= h(__('Tabela de Preços & Margem')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);margin-top:2px;"><?= h(__('Análise de precificação · Simulação de margens · Reajustes em massa')) ?></div>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;">
		<?= $this->Html->link('📊 ' . __('Exportar'), ['controller' => 'ProdutosPrototype', 'action' => 'exportCsv'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('📜 ' . __('Histórico'), ['controller' => 'ProdutosPrototype', 'action' => 'view', 'historico-precos'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('📈 ' . __('Reajuste em massa'), ['controller' => 'ProdutosPrototype', 'action' => 'view', 'preco-reajuste-massa'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('⚙ ' . __('Gerenciar tabela'), ['controller' => 'ProdutosPrototype', 'action' => 'view', 'preco-tabela-detalhe'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('+ ' . __('Nova tabela'), ['controller' => 'ProdutosPrototype', 'action' => 'view', 'preco-tabela-nova'], ['class' => 'btn btn-primary btn-sm']) ?>
	</div>
</div>

<div class="card" style="margin-bottom:14px;">
	<div style="display:flex;gap:14px;align-items:flex-start;flex-wrap:wrap;">
		<div style="flex:1;min-width:240px;">
			<label style="font-size:11px;color:var(--text-muted);text-transform:uppercase;font-weight:600;letter-spacing:.4px;"><?= h(__('Tabela ativa')) ?></label>
			<select style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:13px;font-weight:600;background:#fff;margin-top:4px;" disabled title="<?= h(__('Tabela única vinculada ao catálogo de produtos')) ?>">
				<option><?= sprintf('📋 %s · %d %s', h(__('Tabela Padrão · Preço de varejo (vigente)')), (int)$k['total'], h(__('produtos'))) ?></option>
			</select>
		</div>
		<div style="flex:1;min-width:200px;">
			<label style="font-size:11px;color:var(--text-muted);text-transform:uppercase;font-weight:600;letter-spacing:.4px;"><?= h(__('Período de vigência')) ?></label>
			<div style="display:flex;gap:6px;align-items:center;margin-top:4px;">
				<input type="date" value="<?= h($vig['inicio']) ?>" style="flex:1;padding:7px 10px;border:1px solid var(--border);border-radius:var(--radius);font-size:12px;" readonly/>
				<span style="color:var(--text-muted);font-size:12px;"><?= h(__('até')) ?></span>
				<input type="date" value="<?= h($vig['fim']) ?>" style="flex:1;padding:7px 10px;border:1px solid var(--border);border-radius:var(--radius);font-size:12px;" readonly/>
			</div>
		</div>
		<div>
			<label style="font-size:11px;color:var(--text-muted);text-transform:uppercase;font-weight:600;letter-spacing:.4px;"><?= h(__('Status')) ?></label>
			<div style="margin-top:4px;"><span class="badge b-paga" style="font-size:12px;padding:6px 10px;">✓ <?= sprintf(h(__('Vigente · %d produtos')), (int)$k['total']) ?></span></div>
		</div>
	</div>
</div>

<div class="summary-grid" style="margin-bottom:14px;">
	<div class="summary-card" style="border-left:3px solid var(--teal);">
		<div class="lbl"><?= h(__('Margem média')) ?></div>
		<div class="val" style="color:var(--teal-dark);"><?= (int)$k['margem_media'] ?>%</div>
	</div>
	<div class="summary-card" style="border-left:3px solid var(--blue);">
		<div class="lbl"><?= h(__('Margem maior')) ?></div>
		<div class="val" style="color:#0C447C;"><?= (int)$k['margem_max'] ?>%</div>
		<div style="font-size:11px;color:var(--text-muted);"><?= h((string)$k['margem_max_cod']) ?> · <?= h((string)$k['margem_max_desc']) ?></div>
	</div>
	<div class="summary-card" style="background:#FAEEDA;border-left:3px solid var(--amber);">
		<div class="lbl"><?= h(__('Margem menor')) ?></div>
		<div class="val" style="color:#8A4D02;"><?= (int)$k['margem_min'] ?>%</div>
		<div style="font-size:11px;color:#8A4D02;"><?= h((string)$k['margem_min_cod']) ?> · <?= h(__('revisar?')) ?></div>
	</div>
	<div class="summary-card" style="border-left:3px solid #D946A0;">
		<div class="lbl"><?= h(__('Markup médio')) ?></div>
		<div class="val" style="color:#7A1B5C;"><?= (int)$k['markup_medio'] ?>%</div>
		<div style="font-size:11px;color:var(--text-muted);"><?= h(__('sobre o custo')) ?></div>
	</div>
	<div class="summary-card" style="border-left:3px solid #6B5B95;">
		<div class="lbl"><?= h(__('Itens reajustados')) ?></div>
		<div class="val" style="color:#3D2D63;"><?= (int)$k['reajustados_30d'] ?></div>
		<div style="font-size:11px;color:var(--text-muted);"><?= h(__('últimos 30 dias')) ?></div>
	</div>
	<div class="summary-card" style="border-left:3px solid var(--teal-mid);">
		<div class="lbl"><?= h(__('Próx. revisão')) ?></div>
		<div class="val" style="color:var(--teal-dark);font-size:14px;line-height:1.3;"><?= h((string)$k['prox_revisao']) ?><br><?= h((string)$k['prox_revisao_label']) ?></div>
		<div style="font-size:11px;color:var(--text-muted);"><?= sprintf(h(__('em %d dias')), (int)$k['prox_revisao_dias']) ?></div>
	</div>
</div>

<div class="card" style="padding:0;overflow:hidden;">
	<div style="padding:12px 14px;border-bottom:1px solid var(--border-light);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
		<div style="display:flex;gap:6px;flex-wrap:wrap;">
			<?php
			$fTodos = $mkFiltro('todos');
			$fBaixa = $mkFiltro('baixa');
			$fAlta = $mkFiltro('alta');
			$fDes = $mkFiltro('desatua');
			?>
			<?= $this->Html->link(h(__('Todos')), $fTodos['url'], ['class' => 'btn btn-ghost btn-xs', 'style' => $fTodos['style']]) ?>
			<?= $this->Html->link('⚠ ' . h(__('Margem < 30%')), $fBaixa['url'], ['class' => 'btn btn-ghost btn-xs', 'style' => $fBaixa['style']]) ?>
			<?= $this->Html->link('★ ' . h(__('Margem > 60%')), $fAlta['url'], ['class' => 'btn btn-ghost btn-xs', 'style' => $fAlta['style']]) ?>
			<?= $this->Html->link('📅 ' . h(__('Sem reajuste 6m+')), $fDes['url'], ['class' => 'btn btn-ghost btn-xs', 'style' => $fDes['style']]) ?>
		</div>
		<div style="font-size:11px;color:var(--text-muted);">💡 <?= h(__('Clique nas células para editar inline')) ?></div>
	</div>
	<form method="get" style="padding:8px 14px;border-bottom:1px solid var(--border-light);display:flex;gap:8px;">
		<?php if ($filtroAtual !== 'todos') : ?><input type="hidden" name="f" value="<?= h($filtroAtual) ?>"><?php endif; ?>
		<input type="search" name="q" value="<?= h($precosFiltro) ?>" placeholder="🔍 <?= h(__('Buscar produto...')) ?>" style="flex:1;padding:6px 10px;border:1px solid var(--border);border-radius:var(--radius);font-size:12px;">
		<button type="submit" class="btn btn-ghost btn-xs"><?= h(__('Filtrar')) ?></button>
	</form>
	<div style="overflow-x:auto;">
		<table style="width:100%;border-collapse:collapse;font-size:12px;">
			<thead>
				<tr style="background:var(--bg-surface);border-bottom:1px solid var(--border);">
					<th style="padding:10px 12px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Código')) ?></th>
					<th style="padding:10px 12px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Produto')) ?></th>
					<th style="padding:10px 12px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Custo')) ?></th>
					<th style="padding:10px 12px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Markup')) ?></th>
					<th style="padding:10px 12px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Preço atual')) ?></th>
					<th style="padding:10px 12px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Margem')) ?></th>
					<th style="padding:10px 12px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Sugestão')) ?></th>
					<th style="padding:10px 12px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Última alt.')) ?></th>
					<th style="padding:10px 12px;text-align:center;font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;"><?= h(__('Ações')) ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ($precosItems === []) : ?>
					<tr><td colspan="9" style="padding:24px;text-align:center;color:var(--text-muted);"><?= h(__('Nenhum produto neste filtro.')) ?></td></tr>
				<?php else : foreach ($precosItems as $p) :
					$margem = $p['margem'];
					$margemColor = $margem !== null && $margem < 30 ? '#8A4D02' : 'var(--teal-dark)';
					$btnClass = ($p['btn_ajuste'] ?? 'ghost') === 'amber' ? 'btn btn-amber btn-xs' : 'btn btn-ghost btn-xs';
					$btnLbl = ($p['btn_ajuste'] ?? '') === 'amber' ? '✏ ' . __('Ajustar') : '✏';
				?>
					<tr style="border-bottom:1px solid var(--border-light);<?= h((string)($p['row_style'] ?? '')) ?>">
						<td style="padding:10px 12px;"><span class="titulo-cod"><?= h((string)$p['codigo']) ?></span></td>
						<td style="padding:10px 12px;">
							<div style="font-weight:600;"><?= h(\Cake\Utility\Text::truncate((string)$p['descricao'], 60, ['ellipsis' => '…'])) ?></div>
							<?php if (!empty($p['nota'])) : ?>
								<div style="font-size:11px;color:<?= h((string)$p['nota']['color']) ?>;"><?= h((string)$p['nota']['text']) ?></div>
							<?php endif; ?>
						</td>
						<td style="padding:10px 12px;text-align:right;color:var(--text-muted);"><?= h($H->brl((float)$p['custo'])) ?></td>
						<td style="padding:10px 12px;text-align:right;"><?= !empty($p['markup_inf']) ? '∞' : h($p['markup'] !== null ? (string)$p['markup'] . '%' : '—') ?></td>
						<td style="padding:10px 12px;text-align:right;">
							<form method="post" action="<?= h($this->Url->build(['controller' => 'ProdutosPrototype', 'action' => 'precoSave'])) ?>" style="display:inline-flex;gap:4px;align-items:center;justify-content:flex-end;margin:0;">
								<input type="hidden" name="_csrfToken" value="<?= h($this->request->getAttribute('csrfToken')) ?>">
								<input type="hidden" name="produto_id" value="<?= (int)$p['id'] ?>">
								<input type="hidden" name="q" value="<?= h($precosFiltro) ?>">
								<input type="number" step="0.01" min="0" name="vlunitario" value="<?= h(number_format((float)$p['venda'], 2, '.', '')) ?>" style="width:96px;padding:4px 8px;border:1px solid var(--border);border-radius:4px;font-size:12px;font-weight:600;text-align:right;">
								<button type="submit" class="btn btn-ghost btn-xs" title="<?= h(__('Salvar')) ?>">💾</button>
							</form>
						</td>
						<td style="padding:10px 12px;text-align:right;color:<?= h($margemColor) ?>;"><strong><?= $margem !== null ? h((string)$margem) . '%' : '—' ?></strong></td>
						<td style="padding:10px 12px;text-align:right;font-size:11px;<?= !empty($p['sugestao_destaque']) ? 'color:var(--teal-dark);' : 'color:var(--text-muted);' ?>">
							<?php if (!empty($p['sugestao_destaque'])) : ?><strong><?php endif; ?>
							<?= h($H->brl((float)$p['sugestao_preco'])) ?> (<?= h((string)$p['sugestao_label']) ?>)
							<?php if (!empty($p['sugestao_destaque'])) : ?></strong><?php endif; ?>
						</td>
						<td style="padding:10px 12px;font-size:11px;color:var(--text-muted);"><?= h((string)$p['modified_fmt']) ?></td>
						<td style="padding:10px 12px;text-align:center;">
							<?= $this->Html->link($btnLbl, ['controller' => 'ProdutosPrototype', 'action' => 'view', 'preco-ajuste', '?' => ['id' => (int)$p['id']]], ['class' => $btnClass]) ?>
						</td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
	<div style="padding:10px 14px;background:var(--bg-surface);display:flex;justify-content:space-between;align-items:center;font-size:12px;border-top:1px solid var(--border-light);flex-wrap:wrap;gap:8px;">
		<span style="color:var(--text-muted);"><?= sprintf(h(__('Mostrando %1$d de %2$d produtos da tabela')), count($precosItems), (int)$precosItemsTotal) ?></span>
		<span style="font-size:13px;font-weight:600;color:var(--teal-dark);"><?= sprintf(h(__('Margem média da tabela: %s%%')), (int)$k['margem_media']) ?></span>
	</div>
</div>

<div class="g2" style="margin-top:14px;">
	<?= $this->Html->link(
		'<div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;color:inherit;text-decoration:none;">
			<div style="font-size:48px;flex-shrink:0;">🧮</div>
			<div style="flex:1;min-width:200px;">
				<div style="font-size:18px;font-weight:700;margin-bottom:4px;color:#fff;">' . h(__('Centro de Cálculo de Precificação')) . '</div>
				<div style="font-size:13px;opacity:.9;line-height:1.5;color:#fff;">' . h(__('Simulador profissional com os 3 regimes tributários · Tabelas atualizadas 2026')) . '</div>
			</div>
			<span class="btn" style="background:#fff;color:var(--teal-dark);font-weight:600;flex-shrink:0;">' . h(__('Abrir simulador →')) . '</span>
		</div>',
		['controller' => 'ProdutosPrototype', 'action' => 'view', 'precificacao'],
		['class' => 'card', 'escape' => false, 'style' => 'background:linear-gradient(135deg,#0a3d2c 0%,#1D9E75 100%);color:#fff;border:none;display:block;text-decoration:none;']
	) ?>

	<div class="card">
		<div class="sec-title">📜 <?= h(__('Histórico de reajustes recentes')) ?></div>
		<div style="position:relative;padding-left:30px;">
			<div style="position:absolute;left:14px;top:0;bottom:0;width:2px;background:var(--border);"></div>
			<?php foreach ($precosTimeline as $ev) : ?>
				<div style="position:relative;margin-bottom:14px;">
					<div style="position:absolute;left:-23px;top:2px;width:20px;height:20px;border-radius:50%;background:<?= h((string)$ev['color']) ?>;border:3px solid #fff;display:flex;align-items:center;justify-content:center;font-size:10px;color:#fff;"><?= h((string)$ev['icon']) ?></div>
					<div>
						<div style="display:flex;justify-content:space-between;font-size:12px;flex-wrap:wrap;gap:4px;">
							<strong><?= h((string)$ev['titulo']) ?></strong>
							<span style="color:var(--text-muted);"><?= h((string)$ev['data']) ?></span>
						</div>
						<div style="font-size:11px;color:var(--text-muted);margin-top:2px;"><?= h((string)$ev['desc']) ?></div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?= $this->Html->link(__('Ver histórico completo →'), ['controller' => 'ProdutosPrototype', 'action' => 'view', 'historico-precos'], ['class' => 'btn btn-ghost btn-xs', 'style' => 'margin-top:8px;']) ?>
	</div>
</div>
