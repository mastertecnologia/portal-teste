<?php 
	use Cake\Routing\Router; 
	require_once (ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'UserConstants.php');
	//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/UserConstants.php';
?>
<style>
	.message-center { height: 420px !important; }
	.mailbox .message-center a {
		padding-top: 5px !important;
		padding-bottom: 5px !important;
	}
	.user-profile .dropdown-menu {
		margin-right: 3% !important;
	}
	ul li ul.dropdown {
		width: 18%; /* Set width of the dropdown */
		background: #b3b3b3;
		display: none;
		position: absolute;
		top: 3rem;
		z-index: 999;
		border-radius: 5%;
	}
	ul li:hover ul.dropdown { display: block;	/* Display the dropdown */ }
	ul li ul.dropdown li {
		display: block;
		color: #ffffff;
		line-height: 3;
	}
	.link-btn:hover {
		color: #85e4ff !important;
		cursor: pointer;
	}
	.topbar .top-navbar .navbar-nav>.nav-item>.nav-link { padding-top: 0px; }
	.m-t-13 { margin-top:13px; }
</style>
<header class="topbar skin-pgm">
	<nav class="navbar top-navbar navbar-expand-md skin-pgm">
		<!-- logo -->
		<div class="navbar-header">
			<a class="navbar-brand" href="<?= Router::url(['controller'=>'users','action'=>'dashboard']);?>">
				<img src="<?=$this->request->getAttribute('webroot') . 'assets/images/pgm.png' ?>"  alt="homepage" class="logo imagem-sidebar-mini">
			</a>
		</div>
		<div class="navbar-collapse">
			<!-- Abrir/Fechar menu lateral -->
			<ul class="navbar-nav mr-auto">
				<li class="nav-item"> <a class="text-white nav-link nav-toggler d-block d-md-none waves-effect waves-dark" href="javascript:void(0)"><i class="ti-menu"></i></a> </li>
				<li class="nav-item"> <a class="text-white nav-link sidebartoggler d-none d-lg-block d-md-block waves-effect waves-dark" href="javascript:void(0)"><i class="icon-menu"></i></a> </li>
				<li class="nav-item text-white m-l-20">
					<div class="row <?= count($empresasOptSidebar) > 1 ? 'm-t-13' : 'm-t-10' ?>  m-b-0" style='padding-bottom: 0px !important;'>
						<?php if(count($empresasOptSidebar) > 1) { ?>
							<?= $this->Form->control('empresaSidebar', ['id' => 'empresaSidebar', 'class' => 'p-0 text-white', 'label' => false, 'value' => $empresa, 'options' => $empresasOptSidebar, 'style' => 'height: 21px; background-color: #004640; border: none']) ?>
						<?php } else { ?>
							<p class="text-white nav-link h5 m-t-5 mb-0 p-0" > <?= EmpresaNome($empresa) ?>  </p>
						<?php }  ?>
					</div>
					<div class="row">
						<small <?= count($empresasOptSidebar) > 1 ? "style='margin-left:4px'" : '' ?> > Empresa </small>
					</div>
				</li>
				<li class="nav-item text-white" style='margin-left: 80px;'>
					<div class="row m-t-5 p-b-0">
						<p class="text-white nav-link h5 m-t-10 mb-0 p-0"> <?= date('d/m/Y') ?>  </p>
					</div>
					<div class="row">
						<small> Data </small>
					</div>
				</li>
			</ul>
			<!-- Perfil do usuário -->
			<ul class="navbar-nav my-lg-0 p-r-30">
				<li class="nav-item" style='margin-top:-20%'>
					<div class="user-profile">
						<div class="user-pro-body">
							<div class="dropdown">
								<a href="javascript:void(0)" class="dropdown-toggle u-dropdown link hide-menu text-white" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"> <i class="fas fa-user"></i> <?= $name ?> <span class="caret"></span></a>
								<div class="dropdown-menu animated flipInY">
								<!-- Alterar Perfil -->
								<?= $this->Html->link('<i class="fas fa-user"></i> Alterar Perfil', ['controller' => 'Users', 'action' => 'change_profile'], ['class' => 'dropdown-item', 'escape' => false]); ?>
								<!-- Alterar Senha -->
								<?= $this->Html->link('<i class="fa fa-lock"></i> Alterar Senha', ['controller' => 'Users', 'action' => 'change_password'], ['class' => 'dropdown-item', 'escape' => false]); ?>
								<!-- Acesso Remoto -->
								<?= $this->Html->link('<i class="ti-rss-alt"></i> Acesso Remoto', ['controller' => 'normasempresa', 'action' => 'acessoremoto'], ['class' => 'dropdown-item', 'escape' => false]); ?>
								<!-- Verificação em 2 etapas -->
								<?= $this->Html->link('<i class="ti-lock"></i> Verificação login', ['controller' => 'users', 'action' => 'loginduasetapas'], ['class' => 'dropdown-item', 'escape' => false]); ?>
								<!-- Logout -->
								<?= $this->Html->link('<i class="fa fa-power-off"></i> Logout', ['controller' => 'Users', 'action' => 'logout'], ['class' => 'dropdown-item', 'escape' => false]); ?>
							</div>
						</div>
					</div>
				</li>
			</ul>
		</div>
	</nav>
</header>