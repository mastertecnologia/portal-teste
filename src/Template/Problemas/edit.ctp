<?php
    // Breadcumbs
    $this->Breadcrumbs->add('Tipo de OS', ['controller' => 'Problemas', 'action' => 'index'], ['class' => 'breadcrumb-item']);
    $this->Breadcrumbs->add('Editar', [], ['class' => 'breadcrumb-item active']);
?>

<div class="col-md-12">
    <div class="card">
        <div class="card-body">
            <?= $this->Form->create($problema, ['class' => 'form-material']) ?>
            <div class="row">
                <div class="col-12">
                    <div class="form-group ">
                        <label class="control-label ">Descrição</label>
                        <?= $this->Form->control('descricao', ['class' => 'form-control', 'label' => false, 'required' => true, 'placeholder' => 'Descrição do Tipo de OS', 'required' => true,]);?>
                    </div>
                </div>
            </div>
            <?= $this->Form->button('Salvar Tipo de OS', ['class' => 'btn btn-pgm btn-pgm-salvar btn-success m-t-20']) ?>
            <?= $this->Html->link('Excluir', ["controller" => "Problemas", "action" => "delete", $problema->id], ['class' => 'btn btn-danger m-t-20']); ?>
            <div class="clearfix"></div>
            <?= $this->Form->end(); ?>
        </div>
    </div>
</div>
