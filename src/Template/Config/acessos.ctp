<?php
  	use Cake\Routing\Router;
	// Breadcumbs
	$this->Breadcrumbs->add('Configurações', ['controller' => 'config', 'action' => 'index'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Acessos', [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<?= $this->Form->create($config, ['class' => 'form-material']); ?>
				<div class="row">
					<div class="col-lg-12">
						<div class="form-group">
							<label class="control-label text-muted">URL de acesso externo</label>
							<?= $this->Form->text('urlfora', ['class' => 'form-control', 'label' => false, 'required' => true]) ?>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-12">
						<div class="form-group">
							<label class="control-label text-muted">E-mails diretores (relatório pós-atendimento – separados por ;)</label>
							<?= $this->Form->textarea('email_diretores_relatorio', ['class' => 'form-control', 'label' => false, 'rows' => 2]) ?>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-4">
						<div class="form-group">
							<label class="control-label text-muted">Horário comercial início (ex: 08:00)</label>
							<?= $this->Form->text('horario_comercial_inicio', ['class' => 'form-control', 'label' => false]) ?>
						</div>
					</div>
					<div class="col-lg-4">
						<div class="form-group">
							<label class="control-label text-muted">Horário comercial fim (ex: 18:00)</label>
							<?= $this->Form->text('horario_comercial_fim', ['class' => 'form-control', 'label' => false]) ?>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-12">
						<?= $this->Form->button('Salvar', ['class' => 'btn btn-pgm btn-pgm-salvar btn-success ']) ?>
						<?= $this->Html->link('Voltar para as configurações', ["action" => "index"], ['class' => 'btn btn-pgm btn-pgm-situacao btn-info m-l-5']); ?>
					</div>
				</div>
			<?= $this->Form->end(); ?>
		</div>
	</div>
</div>
