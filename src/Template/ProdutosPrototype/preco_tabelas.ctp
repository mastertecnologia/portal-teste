<?php
/**
 * Escolha de tabela de preços — gerenciar tabelas.
 *
 * @var array<int,array<string,mixed>> $precosTabelasGerenciar
 */
$tabelas = $precosTabelasGerenciar ?? [];
$nav = $this->ErpPrototype->navLinkOpts();
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;"><?= h(__('Produtos › Tabela de Preços › Gerenciar tabelas')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">📋 <?= h(__('Tabelas de Preços')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);"><?= h(__('Escolha a tabela para ver itens, margens e reajustar valores')) ?></div>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;">
		<?= $this->Html->link('← ' . __('Voltar'), ['controller' => 'ProdutosPrototype', 'action' => 'view', 'precos'], array_merge($nav, ['class' => 'btn btn-ghost btn-sm'])) ?>
		<?= $this->Html->link('+ ' . __('Nova tabela'), ['controller' => 'ProdutosPrototype', 'action' => 'view', 'preco-tabela-nova'], array_merge($nav, ['class' => 'btn btn-primary btn-sm'])) ?>
	</div>
</div>

<?php if ($tabelas === []) : ?>
	<div class="alert-box alert-blue"><?= h(__('Nenhuma tabela cadastrada. Crie uma nova tabela ou use o catálogo de produtos na listagem principal.')) ?></div>
<?php else : ?>
	<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px;">
		<?php foreach ($tabelas as $tb) :
			$urlDetalhe = ['controller' => 'ProdutosPrototype', 'action' => 'view', 'preco-tabela-detalhe', '?' => ['tabela' => (int)$tb['id']]];
			$urlReajuste = ['controller' => 'ProdutosPrototype', 'action' => 'view', 'preco-reajuste-massa', '?' => ['tabela' => (int)$tb['id']]];
		?>
			<div class="card" style="display:flex;flex-direction:column;gap:10px;">
				<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
					<div>
						<div style="font-size:15px;font-weight:600;"><?= h((string)$tb['nome']) ?></div>
						<div style="font-size:11px;color:var(--text-muted);margin-top:2px;"><?= h((string)$tb['codigo']) ?></div>
					</div>
					<?php if (!empty($tb['vigente'])) : ?>
						<span class="badge b-paga" style="font-size:10px;">✓ <?= h(__('Vigente')) ?></span>
					<?php endif; ?>
				</div>
				<div style="font-size:12px;color:var(--text-muted);line-height:1.6;">
					<div><strong><?= (int)$tb['total_itens'] ?></strong> <?= h(__('itens')) ?></div>
					<div><?= h((string)$tb['vigencia_label']) ?></div>
				</div>
				<div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:auto;">
					<?= $this->Html->link(__('Abrir tabela →'), $urlDetalhe, array_merge($nav, ['class' => 'btn btn-primary btn-sm'])) ?>
					<?= $this->Html->link('📈 ' . __('Reajustar'), $urlReajuste, array_merge($nav, ['class' => 'btn btn-ghost btn-sm'])) ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
<?php endif; ?>
