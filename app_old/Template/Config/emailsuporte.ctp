<?php
  	use Cake\Routing\Router;
	$this->Breadcrumbs->add('Configurações', ['controller' => 'config', 'action' => 'index'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Email suporte', [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12">
    <div class="card">
        <div class="card-body">
            <?= $this->Form->create($config, ['class' => 'form-material']); ?>
            <div class="row">
                <div class="col-12">
                    <div class="form-group">
                        <label class="control-label text-muted">E-mail do suporte:</label>
                        <?= $this->Form->text('emailtickets', ['class' => 'form-control', 'label' => false]) ?>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <?= $this->Form->button('Salvar', ['class' => 'btn btn-success ']) ?>
                    <?= $this->Html->link('Voltar para as configurações', ["action" => "index"], ['class' => 'btn btn-info m-l-5']); ?>
                </div>
            </div>
            <div class="clearfix"></div>
            <?= $this->Form->end(); ?>
        </div>
    </div>
</div>
