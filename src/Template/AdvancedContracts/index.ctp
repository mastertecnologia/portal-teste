<?php
$this->assign('title', $title ?? 'Contratos');
$clicontratosLegado = $clicontratosLegado ?? [];
$params = $this->Paginator->params();
$advContractsCount = isset($params['count']) ? (int)$params['count'] : (is_countable($contracts) ? count($contracts) : 0);
?>
<div class="col-12 pgm-adv-page">
	<div class="pgm-adv-panel card mb-3">
		<div class="card-body">
			<h4 class="card-title"><?= h($title) ?></h4>
			<p class="text-muted small mb-3">
				Contratos no modelo avançado (<code>contracts</code>). Filtro opcional: <code>?idcliente=</code>.
				Itens de contrato já usados no cadastro de clientes aparecem na segunda tabela.
				<?= $this->Html->link('Modelos de contrato', '/modulo-avancado/modelos-contrato', ['class' => 'd-block mt-1']) ?>
			</p>
			<div class="table-responsive">
				<table class="table table-sm table-striped mb-0">
					<thead>
						<tr>
							<th>Código</th>
							<th>Nome</th>
							<th>Cliente</th>
							<th>Status</th>
							<th>Vigência</th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($contracts as $c): ?>
						<tr>
							<td><?= h($c->code) ?></td>
							<td><?= h($c->name) ?></td>
							<td><?= !empty($c->cliente) ? h($c->cliente->razaosocial ?: $c->cliente->nome) : '—' ?></td>
							<td><?= h($c->status) ?></td>
							<td><?= h($c->start_date ? $c->start_date->format('d/m/Y') : '') ?> — <?= h($c->end_date ? $c->end_date->format('d/m/Y') : '') ?></td>
							<td><?= $this->Html->link('Ver', ['action' => 'view', $c->id], ['class' => 'btn btn-sm btn-outline-primary']) ?></td>
						</tr>
						<?php endforeach; ?>
						<?php if ($advContractsCount === 0): ?>
						<tr>
							<td colspan="6" class="text-muted">Nenhum contrato no módulo avançado para esta empresa. Utilize a listagem abaixo (cadastro de clientes) ou cadastre em <code>contracts</code> após a migration.</td>
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
	<div class="pgm-adv-section-label">Itens de contrato no cadastro de clientes (dados atuais do ERP)</div>
	<div class="pgm-adv-panel card">
		<div class="card-body">
			<div class="table-responsive">
				<table class="table table-sm table-striped mb-0">
					<thead>
						<tr>
							<th>ID</th>
							<th>Descrição</th>
							<th>Cliente</th>
							<th>Produto</th>
							<th>Vigência</th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($clicontratosLegado as $row): ?>
						<tr>
							<td><?= (int)$row->id ?></td>
							<td><?= h($row->descricao ?? '') ?></td>
							<td><?= !empty($row->cliente) ? h($row->cliente->razaosocial ?: $row->cliente->nome) : '—' ?></td>
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
							<td><?= $this->Html->link('Abrir', ['controller' => 'Clicontratos', 'action' => 'view', $row->id], ['class' => 'btn btn-sm btn-outline-primary']) ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
	<?php endif; ?>
</div>
