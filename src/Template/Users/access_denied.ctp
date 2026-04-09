<?php $this->Breadcrumbs->add('Dashboard', ['controller' => 'users', 'action' => 'dashboard'], ['class' => 'breadcrumb-item']); ?>
<?php $this->Breadcrumbs->add('Acesso não autorizado', [], ['class' => 'breadcrumb-item active']); ?>

<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<p class="m-b-15">
				Você não tem permissão para acessar a página solicitada. Se precisar desta função, fale com um administrador.
			</p>
			<p class="m-b-0">
				<?= $this->Html->link('Ir ao painel inicial', ['controller' => 'Users', 'action' => 'dashboard'], ['class' => 'btn btn-pgm btn-success']) ?>
			</p>
		</div>
	</div>
</div>
