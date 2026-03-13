<?php use Cake\Routing\Router; ?>
<?php $this->Breadcrumbs->add('Usuários', ['controller' => 'users', 'action' => 'index'], ['class' => 'breadcrumb-item']); ?>
<?php $this->Breadcrumbs->add('Editar', [], ['class' => 'breadcrumb-item active']); ?>
<style>
	.btn-disabled { background: #eee !important; /*Simular campo inativo*/ }
</style>
<div class="col-md-12">
	<div class="card" >
		<div class="card-body">
			<?= $this->Form->create($user, ['class' => 'form-material  m-t-10', 'enctype' => 'multipart/form-data', 'type' => 'file',]) ?>
			<div class="row">
				<div class="col-lg-3 col-md-12">
					<div class="form-group">
						<label class="control-label text-muted"> Usuário </label>
						<?= $this->Form->control('username', ['class' => 'form-control', 'label' => false, 'required' => true, 'placeholder' => 'Insira o usuário']) ?>
					</div>
				</div>
				<div class="col-lg-3 col-md-12">
					<div class="form-group">
						<label class="control-label text-muted"> CPF </label>
						<?=  $this->Form->control('cpf', ['id' => 'cpf', 'class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o CPF']); ?>
					</div>
				</div>
				<div class="col-lg-4 col-md-12">
					<div class="form-group">
						<label class="control-label text-muted"> E-mail </label>
						<?= $this->Form->control('email', ['id' => 'email', 'class' => 'form-control', 'label' => false, 'required' => true, 'placeholder' => 'Insira o e-mail']) ?>
					</div>
				</div>
				<div class="col-lg-1 col-md-4 col-sm-12 col-xs-12">
					<div class="custom-control custom-checkbox mr-sm-2 m-t-30 m-r-10 m-l-10">
						<?= $this->Form->checkbox('inativo', ['class' => 'custom-control-input', 'id' => 'inativo']); ?>
						<label class="custom-control-label text-muted" for="inativo">Inativo </label>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-12">
					<div class="form-group">
						<label class="control-label text-muted"> Nome </label>
						<?= $this->Form->control('name', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o nome']) ?>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-12">
					<div class="form-group">
						<span class="fake-btn text-muted"> Assinatura Master </span>
						<?= $this->Form->control('assinaturamaster', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o link da assinatura']) ?>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-12">
					<div class="form-group">
						<span class="fake-btn text-muted"> Assinatura PGM </span>
						<?= $this->Form->control('assinaturapgm', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o link da assinatura']) ?>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<?= $this->Form->button('Salvar usuário', ['class' => 'btn btn-success']) ?>
					<?= $this->Form->end(); ?>
					<?= $this->Html->link('Alterar senha', ['action' => 'changePassword', $user->id], ['class' => 'btn btn-warning m-t-20 m-b-20']) ?>
					<?= $this->Html->link('Excluir usuário', ['action' => 'delete', $user->id], ['class' => 'btn btn-danger m-t-20 m-b-20']) ?>
					<div class="clearfix"></div>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
	jQuery(function($){
		$("#cpf").mask("999.999.999-99");
	});

	// troca o texto de dentro
	$(document).on('change', '.file-input', function() {
        var filesCount = $(this)[0].files.length;
        var $textContainer = $(this).prev();
        var fileName = $(this).val().split('\\').pop();
        if (filesCount === 1) {
            var fileName = $(this).val().split('\\').pop();
            $textContainer.text(fileName);
        } 
    });

	var email = $('#email').val();

	$('#email').change(function() {
		if(email != $(this).val()) {
			$.ajax({
				url: "<?= Router::url(['controller'=>'Users','action'=>'verificalogincadastro']);?>/" + $('#email').val(),
				success: function(data){
					if(data == 'podecadastrar') {
						$('.btn-success').prop('disabled', false);
						$('.btn-success').removeClass('btn-disabled');
					}else{
						bootbox.alert('<p class="text-center" style="font-size: 1.2rem">Já existe um usuário com este e-mail no sistema.</p>');
						$('.btn-success').prop('disabled', 'disabled');
						$('.btn-success').addClass('btn-disabled');
					}
				},
			});
		} else {
			$('.btn-success').prop('disabled', false);
			$('.btn-success').removeClass('btn-disabled');
		}
	});
</script>
