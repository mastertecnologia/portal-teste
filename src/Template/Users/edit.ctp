<?php use Cake\Routing\Router; ?>
<?php $this->Breadcrumbs->add('Usuários', ['controller' => 'users', 'action' => 'index'], ['class' => 'breadcrumb-item']); ?>
<?php $this->Breadcrumbs->add('Editar', [], ['class' => 'breadcrumb-item active']); ?>
<style>
	.btn-disabled { background: #eee !important; cursor: not-allowed; }
</style>
<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<h5 class="card-title m-b-10">Editar usuário da equipe</h5>
			<p class="text-muted m-b-20">Atualize os dados cadastrais, status de acesso e assinaturas utilizadas em comunicações oficiais.</p>

			<?= $this->Form->create($user, ['class' => 'form-material m-t-10', 'enctype' => 'multipart/form-data', 'type' => 'file']) ?>

			<h6 class="text-muted m-t-10 m-b-10">Dados básicos</h6>
			<div class="row">
				<div class="col-lg-3 col-md-6">
					<div class="form-group">
						<label class="control-label text-muted">Usuário</label>
						<?= $this->Form->control('username', ['class' => 'form-control', 'label' => false, 'required' => true, 'placeholder' => 'Usuário de acesso']) ?>
					</div>
				</div>
				<div class="col-lg-3 col-md-6">
					<div class="form-group">
						<label class="control-label text-muted">CPF</label>
						<?=  $this->Form->control('cpf', ['id' => 'cpf', 'class' => 'form-control', 'label' => false, 'placeholder' => '000.000.000-00']) ?>
					</div>
				</div>
				<div class="col-lg-4 col-md-8">
					<div class="form-group">
						<label class="control-label text-muted">E-mail</label>
						<?= $this->Form->control('email', ['id' => 'email', 'class' => 'form-control', 'label' => false, 'required' => true, 'placeholder' => 'nome@empresa.com']) ?>
					</div>
				</div>
				<div class="col-lg-2 col-md-4 col-sm-12">
					<div class="custom-control custom-checkbox m-t-30">
						<?= $this->Form->checkbox('inativo', ['class' => 'custom-control-input', 'id' => 'inativo']) ?>
						<label class="custom-control-label text-muted" for="inativo">Inativo</label>
						<small class="form-text text-muted m-t-5">Usuário sem acesso ao portal.</small>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-12">
					<div class="form-group">
						<label class="control-label text-muted">Nome completo</label>
						<?= $this->Form->control('name', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Nome exibido em relatórios e assinaturas']) ?>
					</div>
				</div>
			</div>

			<h6 class="text-muted m-t-20 m-b-10">Assinaturas de e-mail</h6>
			<div class="row">
				<div class="col-md-6">
					<div class="form-group">
						<label class="control-label text-muted">Assinatura Master</label>
						<?= $this->Form->control('assinaturamaster', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Cole aqui o link da assinatura Master']) ?>
					</div>
				</div>
				<div class="col-md-6">
					<div class="form-group">
						<label class="control-label text-muted">Assinatura PGM</label>
						<?= $this->Form->control('assinaturapgm', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Cole aqui o link da assinatura PGM']) ?>
					</div>
				</div>
			</div>

			<div class="row m-t-20">
				<div class="col-md-12 d-flex align-items-center">
					<?= $this->Form->button('Salvar usuário', ['class' => 'btn btn-success m-r-10']) ?>
					<?= $this->Form->end(); ?>
					<?= $this->Html->link('Alterar senha', ['action' => 'changePassword', $user->id], ['class' => 'btn btn-warning m-r-10']) ?>
					<?= $this->Html->link('Excluir usuário', ['action' => 'delete', $user->id], ['class' => 'btn btn-danger']) ?>
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
