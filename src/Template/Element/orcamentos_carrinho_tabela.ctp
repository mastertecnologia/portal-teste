<?php
/**
 * Tabela de itens do carrinho — colunas pg-novo / pg-revisao.
 *
 * @var array $carrinho
 * @var array<int,array<string,mixed>> $carrinhoLinhasExtra
 * @var bool $mostrarAcoesItens
 * @var bool $orcCarrinhoReadonly
 * @var bool $orcItemDescontoEnabled
 */
use App\Utility\OrcamentoDescontoUtil;

$carrinho = $carrinho ?? [];
$carrinhoLinhasExtra = $carrinhoLinhasExtra ?? [];
$mostrarAcoesItens = !empty($mostrarAcoesItens);
$orcCarrinhoReadonly = !empty($orcCarrinhoReadonly);
$orcCarrinhoLayout = $orcCarrinhoLayout ?? 'form';
$orcItemDescontoEnabled = !empty($orcItemDescontoEnabled);
$isRevisao = ($orcCarrinhoLayout === 'revisao');
$emptyMsg = $carrinhoEmptyMessage ?? ($isRevisao ? 'Sem itens.' : 'Use o catálogo ou adicione itens manualmente acima.');
$colCount = ($isRevisao ? 7 : 8) + ($orcItemDescontoEnabled ? 1 : 0) + ($mostrarAcoesItens ? 1 : 0);
$badgeCls = ['prod' => 'orc-badge-tipo--prod', 'srv' => 'orc-badge-tipo--serv', 'serv' => 'orc-badge-tipo--serv', 'lic' => 'orc-badge-tipo--lic', 'loc' => 'orc-badge-tipo--loc'];
?>
<div class="orc-tbl-ref-wrap">
	<table class="orc-tbl-ref" id="tableCarrinho">
		<thead>
			<tr>
				<?php if (!$isRevisao) : ?>
				<th style="width:80px;">Código</th>
				<?php endif; ?>
				<th><?= $isRevisao ? 'Produto / Serviço' : 'Produto' ?></th>
				<th style="width:65px;">Tipo</th>
				<th class="r" style="width:45px;">Qtd.</th>
				<th class="r" style="width:90px;">Vl. Unit.</th>
				<th class="r" style="width:80px;">Custo</th>
				<th class="r" style="width:70px;">Margem</th>
				<?php if ($orcItemDescontoEnabled) : ?>
				<th class="r" style="width:88px;">Desc. item</th>
				<?php endif; ?>
				<th class="r" style="width:95px;">Vl. Total</th>
				<?php if ($mostrarAcoesItens) : ?>
					<th style="width:30px;"></th>
				<?php endif; ?>
			</tr>
		</thead>
		<tbody>
			<?php if ($carrinho === []) : ?>
				<tr class="orc-carrinho-empty">
					<td colspan="<?= (int)$colCount ?>" class="orc-carrinho-empty-cell">
						<?= h($emptyMsg) ?>
					</td>
				</tr>
			<?php else : ?>
				<?php foreach ($carrinho as $reg) :
					$ex = $carrinhoLinhasExtra[(int)$reg->id] ?? [
						'custoLinha' => 0.0,
						'margemPct' => null,
						'tipoBadge' => 'serv',
						'tipoLabel' => 'Serviço',
						'descontoValor' => 0.0,
						'descontoTipo' => 'pct',
						'vlLiquido' => OrcamentoDescontoUtil::linhaLiquido($reg, $orcItemDescontoEnabled),
						'valorUnitDisplay' => (float)($reg->valoruni ?? 0),
						'linhaBruto' => OrcamentoDescontoUtil::linhaBruto($reg),
					];
					$custoLinha = (float)($ex['custoLinha'] ?? 0);
					$margemPct = $ex['margemPct'] ?? null;
					$tipoBadge = (string)($ex['tipoBadge'] ?? 'serv');
					$tipoLbl = (string)($ex['tipoLabel'] ?? 'Serviço');
					$tipoClass = $badgeCls[$tipoBadge] ?? 'orc-badge-tipo--serv';
					$vlTotal = (float)($ex['vlLiquido'] ?? OrcamentoDescontoUtil::linhaLiquido($reg, $orcItemDescontoEnabled));
					$vuDisplay = (float)($ex['valorUnitDisplay'] ?? ($reg->valoruni ?? 0));
					$linhaBruto = (float)($ex['linhaBruto'] ?? OrcamentoDescontoUtil::linhaBruto($reg));
					$discVal = (float)($ex['descontoValor'] ?? ($reg->desconto_valor ?? 0));
					$discTipo = (string)($ex['descontoTipo'] ?? ($reg->desconto_tipo ?? 'pct'));
					if (!in_array($discTipo, ['pct', 'fix'], true)) {
						$discTipo = 'pct';
					}
					?>
					<tr id="<?= (int)$reg->id ?>" data-item-id="<?= (int)$reg->id ?>" data-linha-bruto="<?= h((string)$linhaBruto) ?>">
						<?php if (!$isRevisao) : ?>
						<td><?= h($reg->idproduto) ?></td>
						<?php endif; ?>
						<td>
							<?= h($reg->servico) ?>
							<?php if (!empty($reg->observacao)) : ?>
								<div class="orc-carrinho-desc-sub"><?= h($reg->observacao) ?></div>
							<?php endif; ?>
						</td>
						<td><span class="orc-badge-tipo <?= h($tipoClass) ?>"><?= h($tipoLbl) ?></span></td>
						<td class="r orc-col-qtd"><?= h($reg->quantidade) ?></td>
						<td class="r valorunit"><?= 'R$ ' . number_format($vuDisplay, 2, ',', '.') ?></td>
						<td class="r orc-line-custo" data-custo="<?= h((string)$custoLinha) ?>"><?= 'R$ ' . number_format($custoLinha, 2, ',', '.') ?></td>
						<td class="r orc-line-margem"><?= $margemPct !== null ? h((string)$margemPct) . '%' : '—' ?></td>
						<?php if ($orcItemDescontoEnabled) : ?>
						<td class="r orc-line-disc-cell">
							<?php if ($mostrarAcoesItens && !$orcCarrinhoReadonly) : ?>
								<div class="orc-line-disc-edit">
									<input type="number" class="orc-line-disc-val" value="<?= h((string)$discVal) ?>" min="0" step="0.01" data-id="<?= (int)$reg->id ?>" aria-label="Desconto do item" />
									<select class="orc-line-disc-tipo orc-native-select" data-id="<?= (int)$reg->id ?>">
										<option value="pct"<?= $discTipo === 'pct' ? ' selected' : '' ?>>%</option>
										<option value="fix"<?= $discTipo === 'fix' ? ' selected' : '' ?>>R$</option>
									</select>
								</div>
							<?php else : ?>
								<span class="orc-line-disc-lbl"><?= h(OrcamentoDescontoUtil::rotuloDesconto($discVal, $discTipo)) ?></span>
							<?php endif; ?>
						</td>
						<?php endif; ?>
						<td class="r valordoservico"><?= 'R$ ' . number_format($vlTotal, 2, ',', '.') ?></td>
						<td class="r valormensal orc-is-hidden"><?php echo 'R$ ' . number_format((float)$reg->valormensal, 2, ',', '.'); ?></td>
						<?php if ($mostrarAcoesItens) : ?>
							<td class="text-center btn-actions">
								<?= $this->Html->link('<i class="fa fa-edit"></i>', [], [
									'rel' => 'tooltip',
									'title' => 'Editar',
									'data-id' => $reg->id,
									'data-servico' => $reg->servico,
									'data-quantidade' => $reg->quantidade,
									'data-valoruni' => $vuDisplay,
									'data-observacao' => $reg->observacao,
									'data-valormensal' => $reg->valormensal,
									'data-idproduto' => $reg->idproduto,
									'data-tipo' => $reg->valormensal > 0 ? 1 : 0,
									'data-orc-disc-v' => number_format($discVal, 2, '.', ''),
									'data-orc-disc-t' => $discTipo,
									'class' => 'editaitemcarrinho btn btn-orc-tbl-icon btn-orc-tbl-icon--edit',
									'escape' => false,
								]) ?>
								<?= $this->Html->link('<i class="fa fa-times"></i>', [], [
									'rel' => 'tooltip',
									'title' => 'Excluir',
									'id' => $reg->id,
									'class' => 'excluiitemcarrinho btn btn-orc-tbl-icon btn-orc-tbl-icon--del',
									'escape' => false,
								]) ?>
							</td>
						<?php endif; ?>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			<tr class="orc-carrinho-sum-row orc-is-hidden" aria-hidden="true">
				<td colspan="<?= (int)$colCount ?>">
					<span class="valortotal">R$ 0,00</span>
					<span class="valormensaltotal">R$ 0,00</span>
				</td>
			</tr>
		</tbody>
	</table>
</div>
<?php if ($mostrarAcoesItens && !$orcCarrinhoReadonly) : ?>
	<button type="button" class="btn btn-sm btn-secondary float-right m-b-10 btn-limpacarrinho orc-btn-limpa-carrinho">Limpar carrinho</button>
<?php endif; ?>
