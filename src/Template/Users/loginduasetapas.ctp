<?php $this->Breadcrumbs->add('Verificação de login', [], ['class' => 'breadcrumb-item active']); ?>
<div class="col-md-12">
	<?= $this->Html->css('dist/css/pages/mfa-shell.css') ?>
	<div class="card mfa-shell-card">
		<div class="card-body mfa-shell-body">
			<?php if(!$bAutenticacao) { ?>
				<?php if(empty($urlQRCode)) {
					echo "<p class='mfa-shell-title'>Para ativar a verificação em duas etapas, utilize o Google Authenticator.</p>";
					echo $this->Form->create(null, ['class' => 'form-material']);
					echo $this->Form->control('duasetapas', ['type' => 'hidden', 'value' => 'ativa', 'label' => false]);
					echo $this->Form->button('Ativar verificação em duas etapas', ['class' => 'btn btn-primary btn-lg']);
					echo $this->Form->end();
				} ?>
			<?php } else if($bAutenticacao) { ?>
				<?= $this->Html->link('Desativar verificação em duas etapas', ['controller' => 'Users', 'action' => 'desativaverificacao'], ['class' => 'btn btn-outline-warning btn-lg']) ?>
			<?php } ?>
			<?php if(!empty($urlQRCode)) { ?>
				<p class='mfa-shell-title m-t-20'>Escaneie o QR Code no aplicativo:</p>
				<img src="<?=$urlQRCode ?>" alt="qrcode" class="mfa-shell-qrcode">
			<?php } ?>
			<p class='mfa-shell-help m-t-15'>
				Baixe o Google Authenticator para <a target="_blank" rel="noopener" class='link' href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2">Android</a> ou para
				<a target="_blank" rel="noopener" class='link' href="https://apps.apple.com/br/app/google-authenticator/id388497605">iOS</a>.
			</p>
			<!-- <legend class=''> 
				Baixe o Duo Authenticator para Android <a target="_blank" class='link text-success text-bold' href="https://play.google.com/store/apps/details?id=com.duosecurity.duomobile&hl=pt"> Android </a> ou para
				<a target="_blank" class='link text-success text-bold' href="https://apps.apple.com/br/app/duo-mobile/id422663827"> IOS </a>
			</legend> -->
		</div>
	</div>
</div>
