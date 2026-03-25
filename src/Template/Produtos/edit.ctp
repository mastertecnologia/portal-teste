<?php
    // Breadcumbs
    $this->Breadcrumbs->add('Produtos e Serviços', ['controller' => 'Produtos', 'action' => 'index'], ['class' => 'breadcrumb-item']);
    $this->Breadcrumbs->add('Editar', [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12">
    <div class="card">
        <div class="card-body">
            <?= $this->Form->create($produto, ['class' => 'form-material']) ?>
                <div class="row">
                    <div class="col-lg-2 col-sm-12">
                        <div class="form-group ">
                            <label class="control-label ">Código</label>
                            <?= $this->Form->text('codigo', ['class' => 'form-control', 'label' => false, 'required' => true, 'placeholder' => 'Código do produto/serviço']) ?>
                        </div>
                    </div>
                    <div class="col-lg-5 col-sm-12">
                        <div class="form-group ">
                            <label class="control-label ">Descrição</label>
                            <?= $this->Form->control('descricao', ['class' => 'form-control', 'label' => false, 'required' => true, 'placeholder' => 'Descrição do produto/serviço', 'required' => true,]);?>
                        </div>
                    </div>
                    <div class="col-lg-1 col-sm-12">
                        <div class="form-group ">
                            <label class="control-label ">Tipo</label>
                            <?= $this->Form->control('tipo', ['options' => C_ProdutosTipo, 'class' => 'form-control', 'label' => false, 'required' => true,]);?>
                        </div>
                    </div>
                    <div class="col-lg-1 col-sm-12">
                        <div class="form-group ">
                            <label class="control-label ">Unidade</label>
                            <?= $this->Form->text('unidade', ['id' => 'vlunitario', 'class' => 'form-control', 'label' => false, 'required' => true, 'placeholder' => 'Unidade']);?>
                        </div>
                    </div>
                    <div class="col-lg-2 col-sm-12">
                        <div class="form-group ">
                            <label class="control-label ">Vl. Unitário</label>
                            <?= $this->Form->text('vlunitario', ['id' => 'vlunitario', 'class' => 'form-control mascaramonetaria', 'label' => false, 'required' => true, 'placeholder' => 'Valor']);?>
                        </div>
                    </div>
                    <div class="col-lg-1 col-sm-12">
                        <div class="form-group ">
                            <label class="control-label ">Ativo</label>
                            <?= $this->Form->control('ativo', ['options' => C_ProdutosAtivo, 'class' => 'form-control', 'label' => false, 'required' => true,]);?>
                        </div>
                    </div>
                </div>
                <hr>
				<div class="row">
					<div class="col-lg-3 col-sm-12">
						<div class="form-group">
							<label class="control-label"> Valor Locação - Diário </label>
							<?= $this->Form->text('vllocdiario', ['id' => 'vlunitario', 'class' => 'mascaramonetaria form-control', 'label' => false, 'required' => true, 'placeholder' => 'Valor']);?>
						</div>
					</div>
					<div class="col-lg-3 col-sm-12">
						<div class="form-group">
							<label class="control-label"> Valor Locação - Semanal </label>
							<?= $this->Form->text('vllocsemanal', ['id' => 'vlunitario', 'class' => 'mascaramonetaria form-control', 'label' => false, 'required' => true, 'placeholder' => 'Valor']);?>
						</div>
					</div>
					<div class="col-lg-3 col-sm-12">
						<div class="form-group">
							<label class="control-label"> Valor Locação - Quinzenal </label>
							<?= $this->Form->text('vllocquinzenal', ['id' => 'vlunitario', 'class' => 'mascaramonetaria form-control', 'label' => false, 'required' => true, 'placeholder' => 'Valor']);?>
						</div>
					</div>
					<div class="col-lg-3 col-sm-12">
						<div class="form-group">
							<label class="control-label"> Valor Locação - Mensal </label>
							<?= $this->Form->text('vllocmensal', ['id' => 'vlunitario', 'class' => 'mascaramonetaria form-control', 'label' => false, 'required' => true, 'placeholder' => 'Valor']);?>
						</div>
					</div>
				</div>
                <?= $this->Form->button('Salvar', ['class' => 'btn btn-pgm btn-pgm-salvar btn-success m-t-20']) ?>
            <?= $this->Form->end(); ?>
        </div>
    </div>
</div>
<script>
</script>
