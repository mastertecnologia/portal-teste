<?php
    use Cake\Routing\Router;
	// Breadcumbs
    $this->Breadcrumbs->add('Produtos', ['controller' => 'Produtos', 'action' => 'index'], ['class' => 'breadcrumb-item']);
    $this->Breadcrumbs->add('Estoque', [], ['class' => 'breadcrumb-item active']);
?>
<link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet">
<style>
    @media print {
        body * , .main-wrapper{ visibility: hidden; }
        #printable, #printable * { visibility: visible; }
        #printable, button  * { visibility: hidden; }
        .topbar, .page-titles, .navbar{
            visibility: hidden;
            overflow: hidden;
        }
        .page-wrapper{ padding: 0; }
        #printable {
            position: relative;
            margin-top: -165px;
            margin-left: -100px;
            overflow: visible;
            height: 105%;
            width: 112%;
            font-size: 85%;
        }
        #printable.printMini {
            position: relative;
            margin-top: -165px;
            margin-left: -40px;
            overflow: visible;
            height: 105%;
            width: 106%;
            font-size: 85%;
        }
        
        table { page-break-inside:auto }
        tr    { page-break-inside:avoid; page-break-after:auto }
        thead { display:table-header-group }
    }

    .table td, .table th {
        padding: 0.8rem;
        vertical-align: top;
        border-top: 1px solid #dee2e6;
    }
    
</style>
<div class="col-md-12">
    <?= $this->Html->Link('Imprimir', [], ['id' => 'btn-imprimir', 'class' => 'btn btn-orange m-l-5 m-b-5']) ?>
    <?php if(!$bApenasComSaldo) echo $this->html->Link('Exibir apenas produtos com estoque', [], ['id' => 'btn-saldo', 'class' => 'btn btn-info m-l-5 m-b-5']) ?>
    <?php if($bApenasComSaldo) echo $this->html->Link('Exibir todos os produtos', [], ['id' => 'btn-todos', 'class' => 'btn btn-queequaseinfo m-l-5 m-b-5']) ?>
    <br>
    <?= $this->Form->create(null, ['class' => 'form']) ?>
        <div class="row">
            <div class="col-md-3 col-xs-12">
                <label class="control-label">Filtro por código</label>
                <?= $this->Form->control('sCodProduto', ['id' => 'sCodProduto', 'empty' => 'Todos' , 'title' => 'Todos', 'class' => 'form-control selectpicker', 'data-live-search' => true, 'options' => $produtosOpt, 'value' => $sCodProduto, 'label' => false]) ?>
            </div>
            <div class="col-md-3 col-xs-12">
                <label class="control-label">Filtro por descrição</label>
                <?= $this->Form->control('sDescricao', ['id' => 'sDescricao',  'class' => 'form-control', 'value' => $sDescricao, 'label' => false]) ?>
            </div>
            <div class="col-md-4 col-xs-12">
                <?= $this->Form->button('Buscar', ['class' => 'btn btn-info m-l-5 m-t-30']) ?>
                <?= $this->Form->button('Limpar filtros', ['id' => 'btn-limpar', 'class' => 'btn btn-warning m-t-30']) ?>
            </div>
        </div>
    <?= $this->Form->end(); ?>
    <br>
    <div id="printable">
        <div class="card">
            <div class="card-body">
				<?php if(isset($produtos)) { 
                    if(!empty($produtos[0])) { ?>
					<h2 class='titulo bg-dark text-white text-center p-2'> Produtos em Estoque </h2><br>
					<br>
					<div class="table-responsive">
						<table class="table" id="tableCarrinho">
							<thead class="text-primary">
								<th>Código</th>
								<th>Descrição</th>
								<th class="text-right">Quantidade Atual</th>
								<th class="text-right">Preço Custo</th>
								<th class="text-right">Preço Venda</th>
							</thead>
							<tbody>
								<?php foreach($produtos as $reg){ ?>
									<tr>
										<td><?= $reg->sCodProduto ?></td>
										<td><?= $reg->sDescProduto ?></td>
										<td class="text-right"><?= $reg->nQtdeAtual ?></td>
										<td class="text-right"><?= number_format($reg->nPrecoCusto, 2, ',', '.') ?></td>
										<td class="text-right"><?= number_format($reg->nPrecoVenda, 2, ',', '.') ?></td>
									</tr>
								<?php }?>
							</tbody>
						</table>
					</div>
				<?php } else { ?>
					<h2 class='titulo bg-dark text-white text-center p-2'> Produto fora de estoque! </h2><br>
				<?php } }?>
            </div>
        </div>
    </div>
</div>
<script>
	$('#btn-imprimir').click(function(e) {
        e.preventDefault();
		$('.titulo').removeClass('bg-dark').removeClass('text-white');
        if ($("body").hasClass("mini-sidebar")) $("#printable").removeClass('printMini');
        else $("#printable").addClass('printMini'); 
        var $print = $('#printable')
            .clone()
            .addClass('print')
        window.print();
        $print.remove();
		$('.titulo').addClass('bg-dark').addClass('text-white');
    });
	$('#btn-todos').click(function(e) {
        e.preventDefault();
		window.location = "<?= Router::url(['controller'=>'Produtos','action'=>'estoque']);?>" + '/f'
    });
	$('#btn-saldo').click(function(e) {
        e.preventDefault();
		window.location = "<?= Router::url(['controller'=>'Produtos','action'=>'estoque']);?>" + '/t'
    });
	$('#btn-limpar').click(function(e) {
        e.preventDefault();
        $('#sCodProduto').val(0).selectpicker('refresh');
        $('#sDescricao').val('');
        $('.form').submit();
    });
</script>