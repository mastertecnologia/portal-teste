<?php $this->Breadcrumbs->add('Verificação de login', [], ['class' => 'breadcrumb-item active']); ?>
<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<?php if(!$bAutenticacao) { ?>
				<?php if(empty($urlQRCode)) {
					echo "<p class=' h3'> Para ativar a verificação em duas etapas, utilize o Google Authenticator App</p>";
					echo $this->Form->create(null, ['class' => 'form-material']);
					echo $this->Form->control('duasetapas', ['type' => 'hidden', 'value' => 'ativa', 'label' => false]);
					echo $this->Form->button('Ativar verificação em duas etapas', ['class' => 'btn btn-success btn-lg m-l-10']);
					echo $this->Form->end();
				} ?>
			<?php } else if($bAutenticacao) { ?>
				<?= $this->Html->link('Desativar verificação em duas etapas', ['controller' => 'Users', 'action' => 'desativaverificacao'], ['class' => 'btn btn-success btn-lg m-l-10']) ?>
			<?php } ?>
			<?php if(!empty($urlQRCode)) { ?>
				<p class='h3 m-t-20 m-l-15'> Seu código:</p>
				<img src="<?=$urlQRCode ?>" alt="qrcode" class="m-l-15">
			<?php } ?>
			<legend class='m-t-10'>
				Baixe o Goole Authenticator para <a target="_blank" class='link text-success text-bold' href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2"> Android</a> ou para
				<a target="_blank" class='link text-success text-bold' href="https://apps.apple.com/br/app/google-authenticator/id388497605"> IOS </a>
			</legend>
			<!-- <legend class=''> 
				Baixe o Duo Authenticator para Android <a target="_blank" class='link text-success text-bold' href="https://play.google.com/store/apps/details?id=com.duosecurity.duomobile&hl=pt"> Android </a> ou para
				<a target="_blank" class='link text-success text-bold' href="https://apps.apple.com/br/app/duo-mobile/id422663827"> IOS </a>
			</legend> -->
		</div>
	</div>
</div>
