<?php use Cake\Routing\Router; ?>
<style>
	.form-control {
		border: none;
		height: 50px;
		border: 1px solid transparent;
		background: #004640;
		border-radius: 40px;
		padding-left: 20px;
		padding-right: 20px;
		-webkit-transition: 0.3s;
		-o-transition: 0.3s;
		transition: 0.3s;
	}
	.form-control:focus { 
		color: white !important; 
		background-color: #00ab9e !important; 
	}
	.btn-login, .comeceausar {
		color: #fff;
		background-color: #098479 !important;
		border-color: #098479 !important;
	}
	.btn-login:hover { 
		color: white !important; 
		background-color: #00ab9e !important; 
	}
</style>
<section id="wrapper">
	<div class="login-register">
		<section class="ftco-section">
			<div class="container">
				<div class="card">
					<div class="card-body">
						<div class="col-12">
							<div class="login-wrap p-0">
								<?= $this->Form->create($user, ['class' => 'form-material']) ?>
									<div class="row">
										<div class="col-12">
											<div class="form-group ">
												<label class="control-label text-muted"> Nova senha </label>
												<?= $this->Form->control('password1', ['class' => 'form-control', 'label' => false, 'required' => true, 'type' => 'password', 'placeholder' => 'Insira a nova senha']) ?>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-12">
											<div class="form-group ">
												<label class="control-label text-muted"> Confirmar senha </label>
												<?= $this->Form->control('password2', ['class' => 'form-control', 'label' => false, 'required' => true, 'type' => 'password', 'placeholder' => 'Insira novamente a nova senha']) ?>
											</div>
										</div>
									</div>
									<?= $this->Form->button('Salvar senha', ['class' => 'btn btn-success m-t-10']) ?>
								<?= $this->Form->end(); ?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>
	</div>
</section>