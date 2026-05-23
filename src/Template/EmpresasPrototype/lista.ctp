<?php
/**
 * Lista de empresas (multi-empresa) — mockup pg-empresas.
 *
 * @var \App\View\AppView $this
 * @var array<int,array<string,mixed>> $empItems
 * @var array{total:int,ativas:int,inativas:int} $empKpi
 */
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Sistema')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">🏢 <?= h(__('Empresas cadastradas')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);"><?= sprintf(h(__('%d empresas · %d ativas')), (int)$empKpi['total'], (int)$empKpi['ativas']) ?></div>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;">
		<?= $this->Html->link(__('Módulo clássico'), ['controller' => 'Empresas', 'action' => 'index'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('+ ' . __('Nova empresa'), ['controller' => 'Empresas', 'action' => 'add'], ['class' => 'btn btn-primary btn-sm']) ?>
	</div>
</div>

<div class="stats" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));">
	<div class="stat" style="--sc:var(--teal);"><div class="stat-l"><?= h(__('Total')) ?></div><div class="stat-n"><?= (int)$empKpi['total'] ?></div></div>
	<div class="stat" style="--sc:var(--teal-dark);"><div class="stat-l"><?= h(__('Ativas')) ?></div><div class="stat-n"><?= (int)$empKpi['ativas'] ?></div></div>
	<div class="stat" style="--sc:var(--gray-400);"><div class="stat-l"><?= h(__('Inativas')) ?></div><div class="stat-n"><?= (int)$empKpi['inativas'] ?></div></div>
</div>

<div class="card" style="padding:0;overflow:hidden;">
	<div style="overflow-x:auto;">
		<table class="tbl" style="margin:0;">
			<thead>
				<tr>
					<th></th>
					<th><?= h(__('Razão social / Fantasia')) ?></th>
					<th><?= h(__('CNPJ')) ?></th>
					<th><?= h(__('Contato')) ?></th>
					<th class="r"><?= h(__('Usuários')) ?></th>
					<th><?= h(__('URL ERP')) ?></th>
					<th><?= h(__('Status')) ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php if ($empItems === []) : ?>
					<tr><td colspan="8" style="padding:24px;text-align:center;color:var(--text-muted);"><?= h(__('Nenhuma empresa cadastrada.')) ?></td></tr>
				<?php else : foreach ($empItems as $it) :
					$sigla = strtoupper(mb_substr((string)$it['nome'], 0, 3));
				?>
					<tr<?= !empty($it['current']) ? ' style="background:var(--teal-light);"' : '' ?>>
						<td><div style="width:32px;height:32px;background:var(--teal-dark);color:#fff;border-radius:6px;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:11px;"><?= h($sigla) ?></div></td>
						<td>
							<strong><?= h((string)$it['nome']) ?></strong>
							<?php if (!empty($it['fantasia'])) : ?>
								<div style="font-size:11px;color:var(--text-muted);"><?= h((string)$it['fantasia']) ?></div>
							<?php endif; ?>
						</td>
						<td style="font-family:monospace;font-size:11px;"><?= h((string)$it['cnpj']) ?></td>
						<td style="font-size:11px;">
							<?php if (!empty($it['email'])) : ?>📧 <?= h((string)$it['email']) ?><br><?php endif; ?>
							<?php if (!empty($it['fone'])) : ?>📞 <?= h((string)$it['fone']) ?><?php endif; ?>
						</td>
						<td class="r"><?= (int)$it['usuarios'] ?></td>
						<td class="mu" style="font-size:11px;"><?= h(\Cake\Utility\Text::truncate((string)$it['erp'], 32, ['ellipsis' => '…'])) ?></td>
						<td>
							<?php if (!empty($it['current'])) : ?>
								<span class="badge b-paga">✓ <?= h(__('Ativa')) ?></span>
							<?php elseif (!empty($it['inativa'])) : ?>
								<span class="badge b-arq"><?= h(__('Inativa')) ?></span>
							<?php else : ?>
								<span class="badge b-env"><?= h(__('Pronta')) ?></span>
							<?php endif; ?>
						</td>
						<td class="r" style="white-space:nowrap;">
							<?php if (empty($it['current']) && empty($it['inativa'])) : ?>
								<?= $this->Html->link(__('Usar'), ['controller' => 'Empresasusers', 'action' => 'switchempresa', (int)$it['id'], '?' => ['redirect' => $this->request->getRequestTarget()]], ['class' => 'btn btn-ghost btn-xs']) ?>
							<?php endif; ?>
							<?= $this->Html->link(__('Editar'), ['controller' => 'Empresas', 'action' => 'edit', (int)$it['id']], ['class' => 'btn btn-ghost btn-xs']) ?>
						</td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
</div>
