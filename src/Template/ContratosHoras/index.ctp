<?php
	use Cake\Routing\Router;

	$this->append('css', $this->element('pgm_premium_css', ['name' => 'clientes-premium']));
	$this->append('css', $this->element('pgm_premium_css', ['name' => 'clientes-layout-unificado']));
	$this->Breadcrumbs->add('Clientes', ['controller' => 'Clientes', 'action' => 'index']);
	$this->Breadcrumbs->add('Contratos de Horas', [], ['class' => 'breadcrumb-item active']);
	$urlFichaCliente = Router::url(['controller' => 'Clientes', 'action' => 'edit', $idcliente]);
?>
<div class="col-md-12 p-0">
	<div class="cli-root cli-layout-unificado">
		<div class="cli-section">
			<div class="cli-section-head">
				<div class="cli-section-icon"><i class="fas fa-clock" aria-hidden="true"></i></div>
				<div class="cli-section-title">Contratos de Horas Técnicas</div>
			</div>
			<div class="cli-section-body">
				<div class="d-flex flex-wrap align-items-center m-b-20">
					<?= $this->Html->link(
						'<i class="fas fa-plus"></i> Novo contrato',
						['action' => 'add', $idcliente],
						['class' => 'btn btn-sm btn-cli-primary m-r-5 m-b-5', 'escape' => false, 'data-turbo' => 'false']
					) ?>
					<?= $this->Html->link(
						'<i class="fas fa-arrow-left"></i> Voltar ao cliente',
						$urlFichaCliente,
						['class' => 'btn btn-sm btn-cli-outline m-b-5', 'escape' => false, 'data-turbo' => 'false']
					) ?>
				</div>
				<div class="table-responsive">
					<table class="table table-hover mb-0">
						<thead>
							<tr>
								<th>Vigência</th>
								<th>Horas contratadas</th>
								<th>Saldo</th>
								<th>Valor hora comercial</th>
								<th>Ativo</th>
								<th class="text-right">Ações</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($contratos as $c): ?>
							<tr>
								<td><?= $c->data_inicio->format('d/m/Y') ?> a <?= $c->data_fim->format('d/m/Y') ?></td>
								<td><?= number_format($c->horas_contratadas, 2, ',', '.') ?> h</td>
								<td><?= number_format($c->saldo_horas, 2, ',', '.') ?> h</td>
								<td><?= $c->valor_hora_comercial !== null ? 'R$ ' . number_format($c->valor_hora_comercial, 2, ',', '.') : '—' ?></td>
								<td><?= $c->ativo ? 'Sim' : 'Não' ?></td>
								<td class="text-right text-nowrap">
									<?= $this->Html->link('Editar', ['action' => 'edit', $c->id], ['class' => 'btn btn-warning btn-simple btn-xs', 'data-turbo' => 'false']) ?>
									<?= $this->Form->postLink('Excluir', ['action' => 'delete', $c->id], ['class' => 'btn btn-danger btn-simple btn-xs', 'confirm' => 'Excluir este contrato?']) ?>
								</td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>
