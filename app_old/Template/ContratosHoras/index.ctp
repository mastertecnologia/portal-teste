<?php
$this->Breadcrumbs->add('Clientes', ['controller' => 'Clientes', 'action' => 'index']);
$this->Breadcrumbs->add('Contratos de Horas', [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<h4 class="card-title">Contratos de Horas Técnicas</h4>
			<?= $this->Html->link('Novo contrato', ['action' => 'add', $idcliente], ['class' => 'btn btn-success m-b-20']) ?>
			<?= $this->Html->link('Voltar ao cliente', ['controller' => 'Clientes', 'action' => 'edit', $idcliente], ['class' => 'btn btn-secondary m-b-20']) ?>
			<div class="table-responsive">
				<table class="table table-hover">
					<thead class="text-primary">
						<tr>
							<th>Vigência</th>
							<th>Horas contratadas</th>
							<th>Saldo</th>
							<th>Valor hora comercial</th>
							<th>Ativo</th>
							<th>Ações</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($contratos as $c): ?>
						<tr>
							<td><?= $c->data_inicio->format('d/m/Y') ?> a <?= $c->data_fim->format('d/m/Y') ?></td>
							<td><?= number_format($c->horas_contratadas, 2, ',', '.') ?> h</td>
							<td><?= number_format($c->saldo_horas, 2, ',', '.') ?> h</td>
							<td><?= $c->valor_hora_comercial !== null ? 'R$ ' . number_format($c->valor_hora_comercial, 2, ',', '.') : '-' ?></td>
							<td><?= $c->ativo ? 'Sim' : 'Não' ?></td>
							<td>
								<?= $this->Html->link('Editar', ['action' => 'edit', $c->id], ['class' => 'btn btn-warning btn-simple btn-xs']) ?>
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
