<?php
$this->assign('title', $title ?? 'Modelos de contrato');
$params = $this->Paginator->params();
$count = isset($params['count']) ? (int)$params['count'] : (is_countable($templates) ? count($templates) : 0);
?>
<div class="col-12 pgm-adv-page">
	<div class="pgm-adv-panel card mb-3">
		<div class="card-body">
			<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
				<h4 class="card-title mb-0"><?= h($title) ?></h4>
				<?= $this->Html->link('Novo modelo', '/contract-templates/add', ['class' => 'btn btn-sm btn-primary']) ?>
			</div>
			<p class="text-muted small mb-3">
				Modelos reutilizáveis para contratos do módulo avançado. JSON em <code>cláusulas</code> e <code>variáveis</code> deve ser array válido (ex.: <code>[]</code>).
			</p>
			<div class="table-responsive">
				<table class="table table-sm table-striped mb-0">
					<thead>
						<tr>
							<th>Nome</th>
							<th>Tipo</th>
							<th>Versão</th>
							<th>Ativo</th>
							<th>Alterado</th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($templates as $t): ?>
						<tr>
							<td><?= h($t->nome) ?></td>
							<td><?= h($t->tipo_contrato) ?></td>
							<td><?= (int)$t->versao ?></td>
							<td><?= !empty($t->ativo) ? '<span class="badge badge-success">sim</span>' : '<span class="badge badge-secondary">não</span>' ?></td>
							<td class="small"><?= h($t->modified ? $t->modified->format('d/m/Y H:i') : '') ?></td>
							<td class="text-nowrap">
								<?= $this->Html->link(__('Ver'), '/contract-templates/preview/' . (int)$t->id, ['class' => 'btn btn-sm btn-outline-secondary']) ?>
								<?= $this->Html->link(__('Clonar'), '/contract-templates/clonar/' . (int)$t->id, ['class' => 'btn btn-sm btn-outline-info', 'confirm' => __('Criar uma cópia deste modelo?')]) ?>
								<?= $this->Html->link('Editar', '/contract-templates/edit/' . (int)$t->id, ['class' => 'btn btn-sm btn-outline-primary']) ?>
								<?= $this->Form->postLink(
									'Excluir',
									'/contract-templates/delete/' . (int)$t->id,
									['class' => 'btn btn-sm btn-outline-danger', 'confirm' => 'Excluir este modelo?']
								) ?>
							</td>
						</tr>
						<?php endforeach; ?>
						<?php if ($count === 0): ?>
						<tr>
							<td colspan="6" class="text-muted">Nenhum modelo cadastrado para esta empresa.</td>
						</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
			<?php
			$__pg = $this->Paginator->params();
			if (!empty($__pg['pageCount']) && (int)$__pg['pageCount'] > 1) :
			?>
			<nav class="mt-2"><?= $this->Paginator->numbers(['prev' => true, 'next' => true]) ?></nav>
			<?php endif; ?>
			<p class="mb-0 mt-2">
				<?= $this->Html->link('← Contratos', '/modulo-contratos', ['class' => 'btn btn-sm btn-secondary']) ?>
			</p>
		</div>
	</div>
</div>
