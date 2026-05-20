<?php
/**
 * Conciliação Bancária — mockup pg-conciliacao.
 *
 * @var \App\View\AppView $this
 * @var array{conciliados:int,pendentes:int,divergentes:int,total_extrato:int,total_lancamentos:int} $concKpi
 * @var array<int,array<string,mixed>> $concItems
 */
$H = $this->ErpPrototype;
$csrf = (string)$this->request->getAttribute('csrfToken');
$urlConc = $this->Url->build(['controller' => 'BancosPrototype', 'action' => 'conciliar']);
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Bancos · Conciliação')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">🔄 <?= h(__('Conciliação Bancária')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);">
			<?= sprintf(h(__('%d movimentos no extrato · %d lançamentos no financeiro')), (int)$concKpi['total_extrato'], (int)$concKpi['total_lancamentos']) ?>
		</div>
	</div>
	<?= $this->Html->link('← ' . __('Voltar'), ['controller' => 'BancosPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
</div>

<div class="stats" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));">
	<div class="stat" style="--sc:var(--teal);"><div class="stat-l"><?= h(__('Conciliados')) ?></div><div class="stat-n"><?= (int)$concKpi['conciliados'] ?></div></div>
	<div class="stat" style="--sc:var(--blue);"><div class="stat-l"><?= h(__('Sugeridos (matching)')) ?></div><div class="stat-n"><?= (int)$concKpi['divergentes'] ?></div></div>
	<div class="stat" style="--sc:var(--amber);"><div class="stat-l"><?= h(__('Pendentes')) ?></div><div class="stat-n"><?= (int)$concKpi['pendentes'] ?></div></div>
</div>

<div class="alert-box alert-blue">
	<strong><?= h(__('Matching automático:')) ?></strong>
	<?= h(__('para cada movimento pendente do extrato, o sistema procura lançamentos no financeiro com mesmo valor e data ±3 dias. Aceite ou rejeite no fluxo clássico.')) ?>
</div>

<div class="card" style="padding:0;overflow:hidden;">
	<div style="overflow-x:auto;">
		<table class="tbl" style="margin:0;">
			<thead>
				<tr>
					<th><?= h(__('Data')) ?></th>
					<th><?= h(__('Descrição (banco)')) ?></th>
					<th><?= h(__('Conta')) ?></th>
					<th class="r"><?= h(__('Valor')) ?></th>
					<th><?= h(__('Status')) ?></th>
					<th><?= h(__('Sugestão de match')) ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ($concItems === []) : ?>
					<tr><td colspan="6" style="padding:24px;text-align:center;color:var(--text-muted);"><?= h(__('Sem movimentos no extrato.')) ?></td></tr>
				<?php else : foreach ($concItems as $it) :
					$st = (string)$it['status'];
					$badgeClass = $st === 'conciliado' ? 'paga' : ($st === 'sugerido' ? 'aprov' : 'pendente');
					$lblStatus = $st === 'conciliado' ? __('Conciliado') : ($st === 'sugerido' ? __('Match sugerido') : __('Pendente'));
					$valorColor = strpos((string)$it['tipo'], 'cr') !== false || strpos((string)$it['tipo'], 'entrada') !== false || (float)$it['valor'] > 0 ? 'var(--teal-dark)' : '#7A1822';
				?>
					<tr<?= $st === 'sugerido' ? ' style="background:#F0F9F5;"' : '' ?>>
						<td class="mu"><?= h($H->dt($it['data'])) ?></td>
						<td><?= h(\Cake\Utility\Text::truncate((string)$it['descricao'], 70, ['ellipsis' => '…'])) ?></td>
						<td style="font-family:monospace;font-size:11px;color:var(--text-muted);"><?= h((string)$it['conta']) ?></td>
						<td class="r" style="color:<?= h($valorColor) ?>;font-weight:700;"><?= h($H->brl((float)$it['valor'])) ?></td>
						<td><?= $H->badge($lblStatus, $badgeClass) ?></td>
						<td>
							<?php if (!empty($it['match'])) :
								$m = (array)$it['match'];
							?>
								<div style="font-size:11px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
									<div style="flex:1;min-width:120px;">
										<strong>#<?= (int)$m['id'] ?></strong>
										· <?= h(\Cake\Utility\Text::truncate((string)$m['descricao'], 40, ['ellipsis' => '…'])) ?>
										<div style="color:var(--text-muted);font-size:10px;"><?= h($H->dt($m['data'])) ?></div>
									</div>
									<?php if ($st !== 'conciliado') : ?>
										<form method="post" action="<?= h($urlConc) ?>" style="margin:0;display:inline;" onsubmit="return confirm('<?= h(__('Conciliar movimento ao lançamento #{0}?', (int)$m['id'])) ?>')">
											<input type="hidden" name="_csrfToken" value="<?= h($csrf) ?>">
											<input type="hidden" name="extrato_id" value="<?= (int)$it['id'] ?>">
											<input type="hidden" name="lancamento_id" value="<?= (int)$m['id'] ?>">
											<button type="submit" class="btn btn-primary btn-xs">✓ <?= h(__('Aceitar')) ?></button>
										</form>
									<?php endif; ?>
								</div>
							<?php else : ?>
								<span style="color:var(--text-muted);font-size:11px;">—</span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
</div>

<div class="footer-bar">
	<?= $this->Html->link('← ' . __('Voltar a Bancos'), ['controller' => 'BancosPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
	<?= $this->Html->link(__('Conciliação clássica'), ['controller' => 'FinanceiroBancos', 'action' => 'index'], ['class' => 'btn btn-primary btn-sm']) ?>
</div>
