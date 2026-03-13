<?php
$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index']);
$this->Breadcrumbs->add('Feriados', [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<h4 class="card-title">Feriados (horário especial)</h4>
			<?= $this->Html->link('Novo feriado', ['action' => 'add'], ['class' => 'btn btn-success m-b-20']) ?>
			<div class="table-responsive">
				<table class="table table-hover">
					<thead class="text-primary">
						<tr>
							<th>Data</th>
							<th>Descrição</th>
							<th>Ações</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($feriados as $f): ?>
						<tr>
							<td><?= $f->data->format('d/m/Y') ?></td>
							<td><?= h($f->descricao) ?></td>
							<td>
								<?= $this->Html->link('Editar', ['action' => 'edit', $f->id], ['class' => 'btn btn-warning btn-simple btn-xs']) ?>
								<?= $this->Form->postLink('Excluir', ['action' => 'delete', $f->id], ['class' => 'btn btn-danger btn-simple btn-xs', 'confirm' => 'Excluir?']) ?>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
