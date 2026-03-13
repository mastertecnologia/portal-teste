<?php use Cake\Routing\Router; ?>
<?php $this->Breadcrumbs->add('Dashboard', ['controller' => 'users', 'action' => 'dashboard'], ['class' => 'breadcrumb-item']); ?>
<?php if($role == 0) $this->Breadcrumbs->add('Editar', ['controller' => 'users', 'action' => 'edit'], ['class' => 'breadcrumb-item']); ?>
<?php $this->Breadcrumbs->add('Alterar perfil', [], ['class' => 'breadcrumb-item active']); ?>
<style>
	.btn-disabled { background: #eee !important; /*Simular campo inativo*/ }
</style>
<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<?= $this->Form->create($user, ['class' => 'form-material']) ?>
				<div class="row padding-20">
					<div class="col-md-4 col-xs-12">
						<div class="form-group ">
							<label class="control-label text-muted float-left">Nome</label>
							<?= $this->Form->control('name', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o nome', 'required' => true]) ?>
						</div>
					</div>
					<div class="col-md-4 col-xs-12">
						<div class="form-group ">
							<label class="control-label text-muted float-left">E-mail</label>
							<?= $this->Form->email('email', ['id' => 'email', 'class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o e-mail', 'required' => false]) ?>
						</div>
					</div>
				</div>
				<div class="row padding-20">
					<div class="col-md-2 col-xs-12">
						<div class="form-group ">
							<label class="control-label text-muted float-left">Celular</label>
							<?= $this->Form->control('celular', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o número de celular', 'required' => false]) ?>
						</div>
					</div>
					<div class="col-md-2 col-xs-12">
						<div class="form-group ">
							<label class="control-label text-muted float-left">Telefone</label>
							<?= $this->Form->control('telefone', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o número de telefone', 'required' => false]) ?>
						</div>
					</div>
					<div class="col-md-2 col-xs-12">
						<div class="form-group ">
							<label class="control-label text-muted float-left">Setor</label>
							<?= $this->Form->control('setor', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o setor', 'required' => false]) ?>
						</div>
					</div>
				</div>
				<?= $this->Form->button('Salvar perfil', ['class' => 'btn btn-success m-t-10']) ?>
				<div class="clearfix"></div>
			<?= $this->Form->end(); ?>
		</div>
	</div>
</div>
<script>
	jQuery(function($) {
		$("#celular").mask("(99) 99999-9999");
		$("#telefone").mask("(99) 9999-9999");
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
