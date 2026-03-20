<?php 
	use Cake\Routing\Router; 
	require_once (ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'UserConstants.php');
	//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/UserConstants.php';
?>
<style>
	.imagem-sidebar-expandida {
		display: block;
		margin-left: 90%;
		width: 65px !important;
	}
	.imagem-sidebar-mini { width: 50px !important; }
	.topbar .top-navbar .navbar-header { line-height: 0px !important; }
	.user-profile .dropdown-menu { 
		margin-right: -20% !important;
		width: 220px !important;
	}
</style>
<aside class="left-sidebar skin-pgm">
	<!-- Sidebar scroll-->
	<div class="scroll-sidebar ps ps--theme_default ps--active-y" data-ps-id="5c23612c-2012-1d1a-2b77-a7091df065d9">
		<!-- Menu -->
		<nav class="sidebar-nav">
			<ul id="sidebarnav">
				<li class="<?= $dashboard ?>"> <?= $this->Html->link('<i class="fa fa-columns"></i><span class="hide-menu"> Dashboard </span>', ['controller' => 'Users', 'action' => 'dashboard'], ['class' => 'waves-effect waves-dark', 'aria-expanded' => 'false', 'escape' => false, ]); ?> </li>
				<li class="<?= $clientesActive ?>"> <?= $this->Html->link('<i class="fa fa-building"></i><span class="hide-menu"> Clientes </span>', ['controller' => 'Clientes', 'action' => 'index'], ['class' => 'waves-effect waves-dark m-r-0', 'aria-expanded' => 'false', 'escape' => false, ]); ?> </li>
				<li class="<?= $produtosActive ?>"> <?= $this->Html->link('<i class="fa fa-boxes"></i><span class="hide-menu"> Produtos </span>', ['controller' => 'Produtos', 'action' => 'index'], ['class' => 'waves-effect waves-dark m-r-0', 'aria-expanded' => 'false', 'escape' => false, ]); ?> </li>
				<li class="<?= $ordensActive ?>"> <?= $this->Html->link('<i class="fas fa-file-signature"></i><span class="hide-menu"> Ordens de Serviço </span>', ['controller' => 'Ordensservico', 'action' => 'index'], ['class' => 'waves-effect m-r-0 waves-dark', 'aria-expanded' => 'false', 'escape' => false, ]); ?> </li>
				<li class="<?= $ticketsActive ?>"> <?= $this->Html->link('<i class="fas fa-ticket-alt"></i><span class="hide-menu"> Tickets </span>', ['controller' => 'Servicedesk', 'action' => 'index'], ['class' => 'waves-effect waves-dark m-r-0', 'aria-expanded' => 'false', 'escape' => false, 'target' => '_blank', 'rel' => 'noopener noreferrer']); ?> </li>
				<?php if (!empty($admin)) : ?>
				<li class="<?= $queuesAtendimentoActive ?>"> <?= $this->Html->link('<i class="fas fa-layer-group"></i><span class="hide-menu"> Filas / técnicos </span>', ['controller' => 'Queues', 'action' => 'adminIndex'], ['class' => 'waves-effect waves-dark m-r-0', 'aria-expanded' => 'false', 'escape' => false]); ?> </li>
				<?php endif; ?>
				<li class="<?= $orcamentosActive ?>"> <?= $this->Html->link('<i class="fas fa-file-invoice-dollar"></i><span class="hide-menu"> Orçamentos </span>', ['controller' => 'Orcamentos', 'action' => 'index'], ['class' => 'waves-effect waves-dark', 'aria-expanded' => 'false', 'escape' => false, ]); ?> </li>
				<li class="<?= $faturasActive ?>"> <?= $this->Html->link('<i class="fas fa-file-invoice"></i><span class="hide-menu"> Locação </span>', ['controller' => 'Faturas', 'action' => 'index'], ['class' => 'waves-effect waves-dark', 'aria-expanded' => 'false', 'escape' => false, ]); ?> </li>
				<li class="<?= $visitasActive ?>"> <?= $this->Html->link('<i class="fa fa-calendar"></i><span class="hide-menu"> Agenda </span>', ['controller' => 'Agenda', 'action' => 'calendario'], ['class' => 'waves-effect waves-dark', 'aria-expanded' => 'false', 'escape' => false, ]); ?> </li>
				<li class="<?= $senhasActive ?>"> <?= $this->Html->link('<i class="fa fa-lock"></i><span class="hide-menu"> Banco de Senhas </span>', ['controller' => 'Bancosenhas', 'action' => 'index'], ['class' => 'waves-effect waves-dark', 'aria-expanded' => 'false', 'escape' => false, ]); ?> </li>
				<?php $display = $sidebar != 'mini-sidebar' ? 'none' : ''; ?>
				<li id="mini-logout" style="display:<?= $display ?>;"><?= $this->Html->link('<i class="far fa-circle text-danger"></i><span class="hide-menu">Sair</span>', '/users/logout', ['class' => 'waves-effect waves-dark', 'aria-expanded' => 'false', 'escape' => false]); ?></li>
			</ul>
		</nav>
	</div>
</aside>
<script>
	// Bloqueia caracteres 
		document.onkeydown = function(e) {
			if (e.ctrlKey &&
				(e.keyCode === 85 ||
				e.keyCode === 117)) {
				return false;
			} else return true;
		};
	// Pesquisa funções 
		$('#pesquisa-funcoes').keyup(function(e){
			e.preventDefault();
			$.ajax({
				url:  "<?= Router::url(['controller'=>'Pesquisa','action'=>'pesquisa']);?>/" + $('#pesquisa-funcoes').val(),
				dataType: "json",
				success: function(data){
					$('.htmlpesquisa').html('');
					$.each(data, function(key, array) {
						$('.htmlpesquisa').append('<li><a class="link link-btn" data-controller="'+array.Controller+'"data-action="'+array.Action+'" >'+array.ControllerQueAparece+ ' > ' +array.ActionQueAparece+'</a></li>');
					})
				},
			});
		})

		$(document).on("click", ".link-btn",function(e) {
			controller = $(this).attr('data-controller');
			action = $(this).attr('data-action');
			$.ajax({
				url: "<?= Router::url(['controller'=>'Pesquisa','action'=>'link']);?>/" + controller + '/' + action,
				success: function(data){ window.location = data; },
			});
		});	
	// 
</script>