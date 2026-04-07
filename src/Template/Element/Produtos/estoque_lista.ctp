<?php
/**
 * Listagem estoque: tabela + vista catálogo (cartões). AJAX e carga inicial.
 *
 * @var array $produtos Objetos ERP (sCodProduto, sDescProduto, nQtdeAtual, nPrecoCusto, nPrecoVenda)
 * @var bool $bApenasComSaldo
 * @var array<string,int> $mapCodigoId código ERP => id portal (para editar / precificar)
 */
use Cake\Routing\Router;

$mapCodigoId = $mapCodigoId ?? [];
$produtos = $produtos ?? [];
?>
<?php if (empty($produtos)) : ?>
	<div class="est-table-view">
		<div class="est-table-wrap">
			<div class="est-empty">Nenhum produto encontrado com os filtros atuais.</div>
		</div>
	</div>
	<div class="est-catalog-view" hidden aria-hidden="true"></div>
<?php else : ?>
	<div class="est-table-view">
		<div class="est-table-wrap">
			<table class="est-table">
				<thead>
					<tr>
						<th scope="col" class="est-col-check"><input type="checkbox" id="est-check-all" class="est-check" aria-label="Selecionar todos os produtos listados" /></th>
						<th scope="col" class="est-col-code">Código</th>
						<th scope="col" class="est-col-desc">Descrição</th>
						<th scope="col" class="est-num">Quantidade atual</th>
						<th scope="col" class="est-num">Preço custo</th>
						<th scope="col" class="est-num">Preço venda</th>
						<th scope="col" class="est-col-actions">Ações</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($produtos as $reg) :
						$cod = trim((string)($reg->sCodProduto ?? ''));
						$pid = $mapCodigoId[$cod] ?? null;
						?>
					<tr class="est-row" data-codigo="<?= h($cod) ?>">
						<td class="est-col-check"><input type="checkbox" class="est-check est-check-item" data-codigo="<?= h($cod) ?>" aria-label="<?= h('Selecionar ' . $cod) ?>" /></td>
						<td class="est-col-code"><?= h($cod) ?></td>
						<td class="est-col-desc"><?= h($reg->sDescProduto) ?></td>
						<td class="est-num"><?= h($reg->nQtdeAtual) ?></td>
						<td class="est-num"><?= number_format((float)$reg->nPrecoCusto, 2, ',', '.') ?></td>
						<td class="est-num"><?= number_format((float)$reg->nPrecoVenda, 2, ',', '.') ?></td>
						<td class="est-col-actions">
							<?php if ($pid) : ?>
								<a href="<?= h(Router::url(['action' => 'edit', $pid])) ?>" class="est-act est-act--edit" title="Editar cadastro no portal" target="_blank" rel="noopener noreferrer"><span class="sr-only">Editar cadastro</span><i class="fas fa-pen" aria-hidden="true"></i></a>
								<a href="<?= h(Router::url(['action' => 'precificacao', '?' => ['codigo' => $cod]])) ?>" class="est-act est-act--price" title="Ajustar preços (precificação)" target="_blank" rel="noopener noreferrer"><span class="sr-only">Preços</span><i class="fas fa-tags" aria-hidden="true"></i></a>
							<?php else : ?>
								<span class="est-act est-act--muted" title="Sem item correspondente no cadastro do portal para este código ERP"><i class="fas fa-minus" aria-hidden="true"></i></span>
							<?php endif; ?>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
	<div class="est-catalog-view" hidden aria-hidden="true">
		<div class="est-catalog-grid">
			<?php foreach ($produtos as $reg) :
				$cod = trim((string)($reg->sCodProduto ?? ''));
				$pid = $mapCodigoId[$cod] ?? null;
				?>
			<article class="est-card" data-codigo="<?= h($cod) ?>">
				<header class="est-card__head">
					<label class="est-card__check">
						<input type="checkbox" class="est-check est-check-item" data-codigo="<?= h($cod) ?>" aria-label="<?= h('Selecionar ' . $cod) ?>" />
					</label>
					<span class="est-card__code"><?= h($cod) ?></span>
				</header>
				<div class="est-card__desc"><?= h($reg->sDescProduto) ?></div>
				<dl class="est-card__meta">
					<div><dt>Quantidade</dt><dd class="est-num"><?= h($reg->nQtdeAtual) ?></dd></div>
					<div><dt>Preço custo</dt><dd class="est-num"><?= number_format((float)$reg->nPrecoCusto, 2, ',', '.') ?></dd></div>
					<div><dt>Preço venda</dt><dd class="est-num"><?= number_format((float)$reg->nPrecoVenda, 2, ',', '.') ?></dd></div>
				</dl>
				<footer class="est-card__foot">
					<?php if ($pid) : ?>
						<a href="<?= h(Router::url(['action' => 'edit', $pid])) ?>" class="est-btn est-btn--outline est-btn--sm" target="_blank" rel="noopener noreferrer">Editar</a>
						<a href="<?= h(Router::url(['action' => 'precificacao', '?' => ['codigo' => $cod]])) ?>" class="est-btn est-btn--secondary est-btn--sm" target="_blank" rel="noopener noreferrer">Preços</a>
					<?php else : ?>
						<span class="est-card__no-portal">Sem cadastro no portal</span>
					<?php endif; ?>
				</footer>
			</article>
			<?php endforeach; ?>
		</div>
	</div>
<?php endif; ?>

<div class="est-footer">
	<div><?= $bApenasComSaldo ? 'Modo: apenas produtos com estoque.' : 'Modo: todos os produtos.' ?></div>
	<div class="est-footer__stats">
		<span>Total listado: <strong><?= (int)count($produtos) ?></strong></span>
		<span>Selecionados: <strong id="est-selected-count">0</strong></span>
	</div>
</div>
