<?php use Cake\Routing\Router; ?>
<div class="col-md-12">
	<div class="card">
		<div class="card-body">
            <div class="table-responsive">	
                <?= $this->Html->link('Adicionar Senha', ['action' => 'add'], ['class' => 'btn btn-pgm btn-pgm-salvar btn-success', 'target' => '_blank']) ?>
                <table class="table table-hover table-row-clickable" id="tableSenhas">
                    <thead class="text-primary">
                        <th>Serviço</th>
                        <th>Provedor</th>
                        <th>IP</th>
                        <th>Porta</th>
                        <th>Usuário</th>
                        <th>URL</th>
                        <th>Protocolo</th>
                        <th>Senha</th>
                        <th width="10%">Ações</th>
                    </thead>
                    <tbody>
                        <?php foreach ($senhas as $reg): ?>
                            <tr>
                                <td><?= $reg->nomeservico ?></td>
                                <td><?= $reg->provedor ?></td>
                                <td><?= $reg->ip ?></td>
                                <td><?= $reg->porta ?></td>
                                <td><?= $reg->usuario ?></td>
                                <td><?= $reg->url ?></td>
                                <td><?= $reg->protocolo ?></td>
                                <td><a class="link senha" data-id="<?=$reg->id?>" href="#"> ********** </a></td>
                                <td>
                                    <?= $this->Html->link('<i class="fa fa-edit"></i><div class="ripple-container"></div>', ["action" => "edit", $reg->id], ['rel' => 'tooltip', 'title' => 'Editar', 'class' => 'btn btn-warning btn-simple btn-xs', 'escape' => false, 'target' => '_blank']); ?>
                                    <?= $this->Html->link('<i class="fa fa-times"></i><div class="ripple-container"></div>', ["action" => "delete", $reg->id], ['rel' => 'tooltip', 'title' => 'Excluir', 'class' => 'btn btn-danger btn-simple btn-xs', 'escape' => false]) ; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
	    </div>
	</div>
</div>
<!-- Modal Senha -->
<div class="modal fade none-border" id="modal-senha">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="row m-20">
				<div class="col-12">
					<div class="form-material">
                        <div class="form-group ">
                            <label class="control-label ">Senha Administrativa</label>
                            <?= $this->Form->control('senhaadministrativa', ['type' => 'text', 'class' => 'form-control', 'label' => false, 'required' => true, 'placeholder' => 'Insira a senha administrativa']);?>
                        </div>
                        <?= $this->Form->control('idsenha', ['class' => 'idsenha', 'value' => null, 'label' => false, 'type' => 'hidden']) ?>
                        <div class="custom-control custom-checkbox mr-sm-2 m-r-10">
                            <?= $this->Form->checkbox('exibirsenha', ['checked' => true, 'class' => 'custom-control-input', 'id' => 'exibirsenha']); ?>
                            <label class="custom-control-label text-muted" for="exibirsenha">Exibir Senha</label>
                        </div>
                    </div>
				</div>
			</div>
			<div class="modal-footer">
				<?= $this->Html->link('Confirmar', ['#'], ['class' => 'btn btn-pgm btn-pgm-salvar btn-success text-white btn-verificasenha m-l-5']) ?>
				<button type="button" class="btn btn-danger waves-effect" data-dismiss="modal">Fechar</button>
			</div>
		</div>
	</div>
</div>
<script>
    $(document).ready(function() {
        var $window = $(window);

        table = $('#tableSenhas');
        table.on( 'length.dt', function ( e, settings, len ) {
            pagelength(len);
        } )
        table.DataTable({
            "pageLength": <?= $pagelength ?>,
            "language": {
                "sProcessing":    "Procesando...",
                "sLengthMenu":    "Mostrar _MENU_ registros",
                "sZeroRecords":   "Nenhum registro encontrado",
                "sEmptyTable":    "Nenhum dado disponível",
                "sInfo":          "Mostrando registros de _START_ até _END_ de um total de _TOTAL_ registros",
                "sInfoEmpty":     "Mostrando registros de 0 a 0 de um total de 0 registros",
                "sInfoFiltered":  "(filtrado de um total de _MAX_ registros)",
                "sInfoPostFix":   "",
                "sSearch":        "Buscar:",
                "sUrl":           "",
                "sInfoThousands":  ",",
                "sLoadingRecords": "Carregando...",
                "oPaginate": {
                    "sFirst":    "<<",
                    "sLast":    ">>",
                    "sNext":    ">",
                    "sPrevious": "<"
                },
                "oAria": {
                    "sSortAscending":  ": Ordem Ascendente",
                    "sSortDescending": ": Ordem descendente"
                },
                "drawCallback": function( settings ) {
                    if ($('body').hasClass('dark-mode') ) $('td').each(function(){$(this).addClass('dark-mode');});
                    else $('td').each(function(){$(this).removeClass('dark-mode');});
                },
            }
        });
        table.search(filters).draw();
    });

    $('.senha').click(function(e) {
        var id = $(this).attr('data-id');
        $('#idsenha').val(id);
        $('#modal-senha').modal('toggle');
		$('#modal-senha').modal('show');;
    });

    $('.btn-verificasenha').click(function(e) {
        e.preventDefault()
        id = $('#idsenha').val();
        senha = $('#senhaadministrativa').val();
        $.ajax({
            type:"post",
            url: "<?= Router::url(['controller'=>'Bancosenhas','action'=>'verificasenha']);?>/" + id + '/' + senha,
            success: function(data){
                $('#modal-senha').modal('toggle');
		        $('#modal-senha').modal('hide');;
                bootbox.alert(data);
            },
            error: function (data) { alert('erro'); }
        });
    });

    $('#exibirsenha').change(function(){
        if ($(this).is(':checked')) $('#senhaadministrativa').attr('type', 'text');
        else $('#senhaadministrativa').attr('type', 'password');
    });

</script>
