<?php
  	use Cake\Routing\Router;
	// Breadcumbs
	$this->Breadcrumbs->add('Configurações', ['controller' => 'config', 'action' => 'index'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Pastas', [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12">
    <div class="card">
        <div class="card-body">
            <?= $this->Form->create($config, ['class' => 'form-material']); ?>
            <div class="tab-content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="form-group">
                            <label class="control-label text-muted">Diretório de origem dos programas</label>
                            <?= $this->Form->text('dirorigem', ['class' => 'form-control', 'label' => false, 'required' => true]) ?>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="form-group">
                            <label class="control-label text-muted">Diretório de destino dos programas</label>
                            <?= $this->Form->text('dirdestino', ['class' => 'form-control', 'label' => false, 'required' => true]) ?>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <?= $this->Form->button('Salvar', ['class' => 'btn btn-success ']) ?>
                        <?= $this->Html->link('Voltar para as configurações', ["action" => "index"], ['class' => 'btn btn-info m-l-5']); ?>
                    </div>
                </div>
            </div>
            <div class="clearfix"></div>
            <?= $this->Form->end(); ?>
        </div>
    </div>
</div>
