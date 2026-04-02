<?php
$this->assign('title', $title ?? __('Contratos'));
$clicontratosLegado = $clicontratosLegado ?? [];
$params = $this->Paginator->params();
$advCount = isset($params['count']) ? (int)$params['count'] : (is_countable($contracts) ? count($contracts) : 0);
?>
<div class="col-12 pgm-adv-page">
	<div class="pgm-adv-panel card mb-3">
		<div class="card-body">
			<h4 class="card-title"><?= h($title) ?></h4>
			<p class="text-muted small"><?= __('Contratos vinculados à sua empresa.') ?></p>
			<p class="small mb-2">
				<?= $this->Html->link(__('Franquia de horas (por mês)'), '/cliente/contratos/franquia', ['class' => '']) ?>
				<span class="text-muted"> · </span>
				<?= $this->Html->link(__('Faturas'), '/cliente/contratos/faturas', ['class' => '']) ?>
			</p>
			<div class="table-responsive">
				<table class="table table-sm table-striped mb-0">
					<thead><tr><th><?= __('Código') ?></th><th><?= __('Nome') ?></th><th><?= __('Status') ?></th><th></th></tr></thead>
					<tbody>
						<?php foreach ($contracts as $c): ?>
						<tr>
							<td><?= h($c->code) ?></td>
							<td><?= h($c->name) ?></td>
							<td><?= h($c->status_label) ?></td>
							<td><?= $this->Html->link(__('Ver'), ['action' => 'view', $c->id], ['class' => 'btn btn-sm btn-outline-primary']) ?></td>
						</tr>
						<?php endforeach; ?>
						<?php if ($advCount === 0): ?>
						<tr>
							<td colspan="4" class="text-muted"><?= __('Nenhum contrato cadastrado. Veja abaixo os itens do seu cadastro, se houver.') ?></td>
						</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
			<?php
			$__pgAdv = $this->Paginator->params();
			if (!empty($__pgAdv['pageCount']) && (int)$__pgAdv['pageCount'] > 1) :
			?>
			<nav class="mt-2"><?= $this->Paginator->numbers(['prev' => true, 'next' => true]) ?></nav>
			<?php endif; ?>
		</div>
	</div>
	<?php if (!empty($clicontratosLegado) && count($clicontratosLegado) > 0): ?>
	<div class="pgm-adv-section-label"><?= __('Itens de contrato (cadastro PGM)') ?></div>
	<div class="pgm-adv-panel card">
		<div class="card-body">
			<div class="table-responsive">
				<table class="table table-sm table-striped mb-0">
					<thead>
						<tr><th><?= __('Descrição') ?></th><th><?= __('Produto') ?></th><th><?= __('Vigência') ?></th><th></th></tr>
					</thead>
					<tbody>
						<?php foreach ($clicontratosLegado as $row): ?>
						<tr>
							<td><?= h($row->descricao ?? '') ?></td>
							<td><?= h($row->codproduto ?? '') ?></td>
							<td>
								<?php
								$dc = $row->dtcontratacao ?? null;
								$dv = $row->dtvalidade ?? null;
								echo h($dc instanceof \DateTimeInterface ? $dc->format('d/m/Y') : '');
								echo ' — ';
								echo h($dv instanceof \DateTimeInterface ? $dv->format('d/m/Y') : '');
								?>
							</td>
							<td><?= !empty($idcliente) ? $this->Html->link(__('Ver na empresa'), ['controller' => 'Clientes', 'action' => 'edit', $idcliente], ['class' => 'btn btn-sm btn-outline-primary', 'title' => __('Contratos na ficha do cliente')]) : '—' ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
	<?php endif; ?>
</div>
