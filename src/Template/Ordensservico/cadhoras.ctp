<?php

date_default_timezone_set('America/Sao_Paulo');
// CRIA UMA VARIAVEL E ARMAZENA A HORA ATUAL DO FUSO-HORÀRIO DEFINIDO (BRASÍLIA)
$horaLocal = date('H:i', time());

// Breadcumbs
$this->Breadcrumbs->add('Ordensservico', ['controller' => 'Ordensservico', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Editar', ['controller' => 'Ordensservico', 'action' => 'edit', $idordem], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Cadastrar Horas', [], ['class' => 'breadcrumb-item active']);


?>
<style>
    .os-cadhoras-shell {
        background: #fff;
        border: 1px solid #e5e4e0;
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
        padding: 16px;
    }
    .os-cadhoras-shell .form-group {
        margin-bottom: 12px;
    }
    .os-cadhoras-shell .os-cadhoras-table-wrap {
        margin-top: 12px;
    }
    .os-cadhoras-shell .os-cadhoras-actions {
        margin-top: 12px;
    }
    .os-cadhoras-shell .os-cadhoras-empty-state {
        margin: 12px 0;
        text-align: center;
    }
</style>

<div class="col-md-12">
    <div class="os-cadhoras-shell">
            <?= $this->Form->create(null, ['class' => 'form-material', 'url' => ['controller' => 'Ordemhoras', 'action' => 'add', $idordem]]); ?>
            <div class="row">
                <div class="col-lg-4 col-md-4 col-sm-4 col-12">
                    <div class="form-group">
                        <label class="control-label text-muted">Data </label>
                        <?= $this->Form->text('data', ['id' => 'data', 'default' => date('d/m/Y'), 'class' => 'form-control datepicker', 'label' => false, 'required' => true]) ?>
                    </div>
                </div>
                <div class="col-lg-3 col-md-3 col-sm-4 col-6">
                    <div class="form-group">
                        <label class="control-label text-muted">Hora inicial </label>
                        <?= $this->Form->text('horaini', ['autocomplete' => "off", 'id' => 'horaini',  'class' => 'form-control clockpicker', 'label' => false, 'required' => true]) ?>
                    </div>
                </div>
                <div class="col-lg-3 col-md-3 col-sm-4 col-6">
                    <div class="form-group">
                        <label class="control-label text-muted">Hora final </label>
                        <?= $this->Form->text('horafin', ['autocomplete' => "off", 'id' => 'horafin', 'default' => $horaLocal, 'class' => 'form-control clockpicker', 'label' => false, 'required' => true]) ?>
                    </div>
                </div>
                <div class="col-lg-2 col-md-3 col-sm-6 col-12 m-t-20">
                    <?= $this->Form->button('Cadastrar', ['class' => 'btn btn-pgm btn-pgm-salvar ']) ?>
                </div>
            </div>
            <div class="clearfix"></div>
            <?= $this->Form->end(); ?>
            <hr>
            <div class="table-responsive os-cadhoras-table-wrap">
                <table class="table table-hover" id="tableProgramas">
                    <thead class="text-primary">
                        <th>Usuário</th>
                        <th>Data</th>
                        <th>Horário</th>
                    </thead>
                    <tbody>
                        <?php if (!empty($horas)): ?>
                            <?php foreach ($horas as $reg): ?>
                                <tr>
                                    <td><?= $reg->user->username ?></td>
                                    <td><?= date_format($reg->data, 'd/m/Y'); ?></td>
                                    <td><?= date_format($reg->horaini, 'H:i') . " - " . date_format($reg->horafin, 'H:i'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="os-cadhoras-empty-state">Nenhum registro de horas encontrado</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="os-cadhoras-actions">
                <?= $this->Html->link('Voltar para a ordem', ["action" => "edit", $idordem], ['class' => 'btn btn-pgm btn-pgm-situacao btn-info']); ?>
            </div>
    </div>
</div>

<script>
$('.datepicker').bootstrapMaterialDatePicker({ format : 'DD/MM/YYYY', lang : 'pt-br', time : false, switchOnClick : true, nowButton : true, cancelText : 'Cancelar' , 'setDate' : 'currentDate', nowText : 'Hoje'});

$('.clockpicker').clockpicker({
    donetext: 'Confirmar',
})

jQuery(function($){
    $("#data").mask("99/99/9999");
    $("#horaini").mask("99:99");
    $("#horafin").mask("99:99");
});


</script>
