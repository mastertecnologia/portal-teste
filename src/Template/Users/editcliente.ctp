<?php use Cake\Routing\Router; ?>
<?php $this->Breadcrumbs->add('Usuários', ['controller' => 'users', 'action' => 'index'], ['class' => 'breadcrumb-item']); ?>
<?php $this->Breadcrumbs->add('Editar', [], ['class' => 'breadcrumb-item active']); ?>
<style>
	.btn-disabled { background: #eee !important; /*Simular campo inativo*/ }
</style>
<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<?= $this->Form->create($user, ['class' => 'form-material  m-t-10']) ?>
				<div class="row">
					<div class="col-lg-3 col-md-12">
						<div class="form-group">
							<label class="control-label text-muted"> Usuário </label>
							<?= $this->Form->control('username', ['class' => 'form-control', 'label' => false, 'required' => true, 'placeholder' => 'Insira o usuário']) ?>
						</div>
					</div>
					<div class="col-lg-4 col-md-12">
						<div class="form-group">
							<label class="control-label text-muted"> E-mail </label>
							<?= $this->Form->email('email', ['id' => 'email', 'class' => 'form-control', 'label' => false, 'required' => true, 'placeholder' => 'Insira o e-mail']) ?>
						</div>
					</div>
					<div class="col-md-2 col-xs-12">
						<div class="form-group">
							<label class="control-label text-muted"> CPF </label>
							<?= $this->Form->control('cpf', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o cpf']) ?>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-4 col-xs-12">
						<div class="form-group">
							<label class="control-label text-muted"> Nome do usuário </label>
							<?= $this->Form->control('name', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o nome']) ?>
						</div>
					</div>
					<div class="col-md-6 col-xs-12">
						<label class="control-label">Cliente</label>
						<?= $this->Form->control('idcliente', ['data-live-search' => true, 'options' => $clientes, 'title' => 'Selecione um cliente', 'class' => 'form-control selectpicker', 'label' => false, 'required' => true]) ?>
					</div>
				</div>
				<div class="row">
					<div class="col-xl-2 col-lg-2 col-xs-12">
						<div class="custom-control custom-checkbox mr-sm-2 m-r-10 m-l-10">
							<?= $this->Form->checkbox('inativo', ['class' => 'custom-control-input', 'id' => 'inativo']); ?>
							<label class="custom-control-label text-muted" for="inativo"> Inativo </label>
						</div>
					</div>
					<div class="col-xl-2 col-lg-2 col-xs-12">
						<div class="custom-control custom-checkbox mr-sm-2 m-r-10 m-l-10">
							<?= $this->Form->checkbox('bloqueado', ['class' => 'custom-control-input', 'id' => 'bloqueado']); ?>
							<label class="custom-control-label text-muted" for="bloqueado"> Bloqueado </label>
						</div>
					</div>
					<div class="col-xl-4 col-lg-4 col-xs-12">
						<div class="custom-control custom-checkbox mr-sm-2 m-r-10 m-l-10">
							<?= $this->Form->checkbox('permissaoacesso', ['class' => 'custom-control-input', 'id' => 'permissaoacesso']); ?>
							<label class="custom-control-label text-muted" for="permissaoacesso"> Permissões administrativas </label>
						</div>
					</div>
					<?php if(!empty($user->secret)) { ?>
						<div class="col-xl-2 col-lg-2 col-xs-12">
							<div class="custom-control custom-checkbox mr-sm-2 m-r-10 m-l-10">
								<?= $this->Form->checkbox('desativasecret', ['class' => 'custom-control-input', 'id' => 'desativasecret']); ?>
								<label class="custom-control-label text-muted" for="desativasecret"> Desativar verificação em 2 fatores </label>
							</div>
						</div>
					<?php } ?>
				</div>
				<div class="row m-t-20">
					<div class="col-12">
						<?= $this->Form->button('Salvar usuário', ['class' => 'btn btn-success']) ?>
						<?= $this->Html->link('Alterar senha', ['action' => 'changePasswordAdmin', $user->id], ['class' => 'btn btn-warning']) ?>
						<?= $this->Html->link('Redefinir senha', ['action' => 'resetPassword', $user->id], ['class' => 'btn-reset-password btn btn-orange']) ?>
						<?= $this->Html->link('Excluir usuário', ['#'], ['class' => 'btn btn-danger btn-delete']) ?>
					</div>
				</div>
			<?= $this->Form->end(); ?>
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
						<div class="custom-control custom-checkbox mr-sm-2 m-r-10">
							<?= $this->Form->checkbox('exibirsenha', ['checked' => true, 'class' => 'custom-control-input', 'id' => 'exibirsenha']); ?>
							<label class="custom-control-label text-muted" for="exibirsenha">Exibir Senha</label>
						</div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<?= $this->Html->link('Confirmar', ['action' => 'delete', $user->id], ['class' => 'btn btn-success text-white btn-verificasenha m-l-5']) ?>
				<button type="button" class="btn btn-danger waves-effect" data-dismiss="modal">Fechar</button>
			</div>
		</div>
	</div>
</div>
<script>
	// Masks 
		jQuery(function($){
			$("#cpf").mask("999.999.999-99");
		});
	// Email 
		var email = $('#email').val();
		$('#email').change(function() {
			if(email != $(this).val()) {
				$.ajax({
					url: "<?= Router::url(['controller'=>'Users','action'=>'verificaloginedit']);?>/" + $('#email').val(),
					success: function(data){
						if(data == 'podecadastrar') {
							$('.btn-success').prop('disabled', false);
							$('.btn-success').removeClass('btn-disabled');
						}else{
							bootbox.alert('<p class="text-center" style="font-size: 1.2rem">Já existe um usuário com este e-mail no sistema, verifique e inative o usuário "'+data+'".</p>');
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
	// Delete 
		$('.btn-delete').click(function(e) {
			e.preventDefault();
			$('#modal-senha').modal('toggle');
		})

		$('.btn-verificasenha').click(function(e) {
			e.preventDefault()
			href = $(this).attr('href');
			senha = $('#senhaadministrativa').val();
			$.ajax({
				dataType: 'json',
				url: "<?= Router::url(['action'=>'verificasenha']);?>/" + senha,
				success: function(data){ window.location = href },
				error: function (data) { bootbox.alert(data.responseJSON.Mensagem); }
			});
		});

		$('#exibirsenha').change(function(){
			if ($(this).is(':checked')) $('#senhaadministrativa').attr('type', 'text');
			else $('#senhaadministrativa').attr('type', 'password');
		});

		$('#exibirsenhacliente').change(function(){
			if ($(this).is(':checked')) $('#senha').attr('type', 'text');
			else $('#senha').attr('type', 'password');
		});
	// Reset Password 
		$('.btn-reset-password').click(function(e) {
			e.preventDefault();
			href = $(this).attr('href');
			bootbox.dialog({
				title: 'Confirmar a redefinição da senha?',
				message: "<p> Será enviado um email para o usuário, que deverá redefinir a sua senha </p>",
				size: 'large',
				buttons: {
					cancel: {
						label: "Cancelar",
						className: 'btn-danger',
						callback: function(){ }
					},
					ok: {
						label: "Confirmar",
						className: 'btn-success',
						callback: function(){
							window.location = href;
						}
					}
				}
			});
		})
	// 
</script>
