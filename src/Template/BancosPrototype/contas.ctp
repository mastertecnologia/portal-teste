<?php
/**
 * Bancos — mockup pg-contas (lista tabular).
 *
 * @var \App\View\AppView $this
 * @var array<int,array<string,mixed>> $bcItems
 * @var array<string,mixed> $bcKpi
 * @var array<string,mixed> $integracoes
 * @var array<int,array<string,string>> $bancosCatalogo
 * @var bool $abrirModalConta
 */
$H = $this->ErpPrototype;
$urlBancos = ['controller' => 'BancosPrototype', 'action' => 'lista'];
$urlExtrato = ['controller' => 'BancosPrototype', 'action' => 'view', 'extrato'];
echo $this->element('BancosPrototype/modal_stub');
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;">
			← <?= $this->Html->link(__('Bancos'), $urlBancos, ['style' => 'color:var(--teal);']) ?>
			› <span style="color:var(--teal);"><?= h(__('Contas Bancárias')) ?></span>
		</div>
		<h1 style="font-size:20px;font-weight:600;margin:0;"><?= h(__('Contas Bancárias')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);margin-top:2px;">
			<?= sprintf(
				h(__('%d contas cadastradas · %d ativas · Saldo total %s')),
				(int)$bcKpi['total'],
				(int)$bcKpi['ativas'],
				h($H->brl($bcKpi['saldo_total'] ?? 0))
			) ?>
		</div>
	</div>
	<button type="button" class="btn btn-primary" data-pgm-open-conta-modal onclick="return abrirCadastroConta();">+ <?= h(__('Nova conta bancária')) ?></button>
</div>

<?php if ($bcItems === []) : ?>
	<div class="card" style="text-align:center;padding:32px 22px;color:var(--text-muted);">
		<?= h(__('Nenhuma conta bancária cadastrada.')) ?>
		<div style="margin-top:14px;">
			<button type="button" class="btn btn-primary btn-sm" data-pgm-open-conta-modal onclick="return abrirCadastroConta();">+ <?= h(__('Nova conta bancária')) ?></button>
		</div>
	</div>
<?php else : ?>
	<div class="card" style="padding:0;overflow:hidden;">
		<div class="tbl-wrap">
			<table class="tbl">
				<thead>
					<tr>
						<th style="width:60px;"><?= h(__('Banco')) ?></th>
						<th><?= h(__('Instituição')) ?></th>
						<th style="width:120px;"><?= h(__('Agência')) ?></th>
						<th style="width:140px;"><?= h(__('Conta')) ?></th>
						<th style="width:100px;"><?= h(__('Tipo')) ?></th>
						<th class="r" style="width:140px;"><?= h(__('Saldo atual')) ?></th>
						<th style="width:110px;"><?= h(__('Última sync')) ?></th>
						<th style="width:90px;"><?= h(__('Status')) ?></th>
						<th style="width:100px;"><?= h(__('Ações')) ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($bcItems as $b) :
						$br = $b['brand'];
						$extratoUrl = $urlExtrato + ['?' => ['conta' => (string)($b['conta_extrato'] ?: $b['nome'])]];
						?>
						<tr>
							<td>
								<div style="width:32px;height:32px;border-radius:8px;background:<?= h($br['logo_bg']) ?>;color:<?= h($br['logo_fg']) ?>;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:11px;"><?= h($br['sigla']) ?></div>
							</td>
							<td>
								<div style="font-weight:600;font-size:13px;"><?= h((string)$b['nome']) ?></div>
								<div style="font-size:11px;color:var(--text-muted);"><?= h(__('Cód.')) ?> <?= h((string)$b['codigo_fmt']) ?></div>
							</td>
							<td><?= h((string)$b['agencia']) ?></td>
							<td><strong style="font-family:monospace;"><?= h((string)$b['conta']) ?></strong></td>
							<td><?= $H->badge((string)$b['tipo_label'], (string)$b['tipo_kind'], ['style' => 'font-size:10px;']) ?></td>
							<td class="r"><strong style="color:var(--teal-dark);"><?= h($H->brl($b['saldo'])) ?></strong></td>
							<td style="font-size:11px;color:var(--text-muted);"><?= h((string)$b['sync_label']) ?></td>
							<td>
								<?php if (!empty($b['ativo'])) : ?>
									<?php if (!empty($b['sync_stale'])) : ?>
										<?= $H->badge('⏰ ' . (string)$b['sync_label'], 'vencendo') ?>
									<?php else : ?>
										<?= $H->badge('✓ ' . __('Ativa'), 'paga') ?>
									<?php endif; ?>
								<?php else : ?>
									<?= $H->badge(__('Inativa'), 'arq') ?>
								<?php endif; ?>
							</td>
							<td>
								<?= $this->Html->link('✏', ['controller' => 'FinanceiroBancos', 'action' => 'edit', (int)$b['id']], ['class' => 'btn btn-ghost btn-xs', 'escape' => false, 'title' => __('Editar')]) ?>
								<?= $this->Html->link('⋯', $extratoUrl, ['class' => 'btn btn-ghost btn-xs', 'escape' => false, 'title' => __('Extrato')]) ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>

	<div class="card" style="margin-top:14px;">
		<div class="sec-title"><?= h(__('Configurações & integrações ativas')) ?></div>
		<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px;">
			<div style="padding:12px;background:var(--bg-surface);border-radius:var(--radius);">
				<div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;font-weight:600;"><?= h(__('CNAB 240')) ?></div>
				<div style="font-size:13px;font-weight:600;margin-top:2px;"><?= sprintf(h(__('%d bancos integrados')), (int)($integracoes['cnab_bancos'] ?? 0)) ?></div>
				<?= $H->badge('✓ ' . __('Operacional'), 'paga', ['style' => 'margin-top:6px;']) ?>
			</div>
			<div style="padding:12px;background:var(--bg-surface);border-radius:var(--radius);">
				<div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;font-weight:600;"><?= h(__('Open Banking (PIX)')) ?></div>
				<div style="font-size:13px;font-weight:600;margin-top:2px;"><?= sprintf(h(__('%d contas ativas')), (int)($integracoes['contas_ativas'] ?? 0)) ?></div>
				<?= $H->badge('✓ ' . __('Conectado'), 'paga', ['style' => 'margin-top:6px;']) ?>
			</div>
			<div style="padding:12px;background:var(--bg-surface);border-radius:var(--radius);">
				<div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;font-weight:600;"><?= h(__('API extrato')) ?></div>
				<div style="font-size:13px;font-weight:600;margin-top:2px;"><?= !empty($integracoes['extrato_auto']) ? h(__('OFX / extrato')) : h(__('Não configurado')) ?></div>
				<?= $H->badge(!empty($integracoes['extrato_auto']) ? '✓ ' . __('Sincronizado') : __('Pendente'), !empty($integracoes['extrato_auto']) ? 'paga' : 'pendente', ['style' => 'margin-top:6px;']) ?>
			</div>
			<div style="padding:12px;background:var(--bg-surface);border-radius:var(--radius);">
				<div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;font-weight:600;"><?= h(__('Certificados ICP')) ?></div>
				<div style="font-size:13px;font-weight:600;margin-top:2px;"><?= h(__('Consulte o módulo fiscal')) ?></div>
				<?= $H->badge(__('—'), 'arq', ['style' => 'margin-top:6px;']) ?>
			</div>
		</div>
	</div>
<?php endif; ?>

<?= $this->element('BancosPrototype/modal_conta', [
	'bancosCatalogo' => $bancosCatalogo,
	'abrirModalConta' => !empty($abrirModalConta),
]) ?>
