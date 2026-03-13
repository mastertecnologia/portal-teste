<section id="wrapper" class="login-register login-sidebar" style="background-image:url(<?=$this->request->getAttribute('webroot') . 'assets/images/background/login-register.jpg'?>)">

	<div class="login-box card">
		<div class="card-body">
			<?= $this->Form->create("", ['class' => 'form-horizontal form-material text-center']) ?>
			<!-- <form class="form-horizontal form-material text-center" id="loginform" action="index.html"> -->
				<a href="javascript:void(0)" class="db"><img width="100px" src="<?=$this->request->getAttribute('webroot') . 'assets/images/favicon.png'?>" alt="Home"/></a>
				<div class="form-group m-t-40">
					<div class="col-xs-12">
						<?= $this->Form->control('idempresa', ['data-live-search' => 'true', 'class' => 'selectpicker form-control', 'options' => $empresas, 'label' => false]) ?>
						<small class='minsuculaOnly'>Letras maiúsculas não são permitidas</small>
					</div>
				</div>
				<div class="form-group text-center m-t-40">
					<div class="col-xs-12">
						<?= $this->Flash->render() ?>
						<?= $this->Form->button('Selecionar empresa', ['class' => 'btn btn-lg btn-login btn-block text-uppercase btn-rounded']) ?>
						<br>
					</div>
				</div>
			<?= $this->Form->end() ?>
		</div>
	</div>
</section>
<script>
	$('.comeceausar').hover(function(){
		$(this).css('cursor', 'pointer');
	});
	$('.comeceausar').click(function(){
		window.location = 'cadastrocliente';
	});
	
	$('.minsuculaOnly').hide()

	function SemMaisuclaEEspaco(e){
		var tecla=(window.event)?event.keyCode:e.which;  
		if((tecla >= 65 && tecla <= 90) || (tecla >= 192 && tecla <= 220 )) {
			$('.minsuculaOnly').show()
			return false;
		}
		$('.minsuculaOnly').hide()
	}
	$('#username').change(function(){
		issominusculo = $(this).val().toLowerCase(); 
		$(this).val(issominusculo);
	});




</script>