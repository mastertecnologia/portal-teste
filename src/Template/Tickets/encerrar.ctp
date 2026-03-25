<section class="content-header">
    <h1>Encerrar Atendimento #<?= $ticket->id ?></h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <?= $this->Form->create($ticket) ?>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Cliente:</strong> <?= $ticket->cliente->razaosocial ?></p>
                            <p><strong>Início:</strong> <?= $ticket->data_inicio->format('d/m/Y H:i') ?></p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Descrição do Problema (Relatado)</label>
                        <?= $this->Form->control('descricao_atendimento', ['label' => false, 'class' => 'form-control', 'readonly' => true]) ?>
                    </div>

                    <div class="form-group">
                        <label>Solução Técnica (O que foi feito?)</label>
                        <?= $this->Form->control('solucao_tecnica', [
                            'label' => false, 
                            'class' => 'form-control', 
                            'rows' => 5, 
                            'required' => true,
                            'placeholder' => 'Detalhe a solução para o cliente...'
                        ]) ?>
                    </div>
                </div>
                <div class="box-footer">
                    <?= $this->Form->button(__('Finalizar e Notificar Diretores'), ['class' => 'btn btn-pgm btn-pgm-salvar btn-success']) ?>
                    <?= $this->Html->link(__('Cancelar'), ['action' => 'index'], ['class' => 'btn btn-default']) ?>
                </div>
                <?= $this->Form->end() ?>
            </div>
        </div>
    </div>
</section>