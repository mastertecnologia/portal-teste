<?php 
	use Cake\Routing\Router; 
	require_once (ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'UserConstants.php');
	//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/UserConstants.php';
?>
<style>
	.fixed-layout .page-wrapper, .left-sidebar { padding-top: 0 !important; }
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
<aside class="left-sidebar skin-pgm" style='margin-top:50px;'>
    <div class="scroll-sidebar ps ps--theme_default ps--active-y" data-ps-id="5c23612c-2012-1d1a-2b77-a7091df065d9">
        <nav class="sidebar-nav">
			<ul id="sidebarnav" class='p-t-30'>
				<?php if($permissaoacesso) { ?>
					<li class="<?= $dashboard ?>"> <?= $this->Html->link('<i class="fa fa-columns"></i><span class="hide-menu">Dashboard</span>', '/users/dashboard', ['class' => 'waves-effect waves-dark', 'aria-expanded' => 'false', 'escape' => false]); ?> </li>
					<li class="<?= $clientesActive ?>"> <?= $this->Html->link('<i class="fa fa-building"></i><span class="hide-menu">Empresa</span>', "/clientes/edit/$idcliente", ['class' => 'waves-effect waves-dark', 'aria-expanded' => 'false', 'escape' => false]); ?> </li>
					<li class="<?= $orcamentosActive ?>"> <?= $this->Html->link('<i class="fa fa-file-invoice-dollar"></i><span class="hide-menu">Orçamentos</span>', "/orcamentos/index", ['class' => 'waves-effect waves-dark', 'aria-expanded' => 'false', 'escape' => false]); ?> </li>
				<?php } ?>
				<li class="<?= $ticketsActive ?>"> <?= $this->Html->link('<i class="fa fa-ticket-alt"></i><span class="hide-menu">Tickets</span>', "/tickets/indexcliente", ['class' => 'waves-effect waves-dark', 'aria-expanded' => 'false', 'escape' => false]); ?> </li>
                <?php $display = $sidebar != 'mini-sidebar' ? 'none' : ''; ?>
                <li id="mini-logout" style="display:<?= $display ?>;"> <?= $this->Html->link('<i class="far fa-circle text-danger"></i><span class="hide-menu">Sair</span>', '/users/logout', ['class' => 'waves-effect waves-dark', 'aria-expanded' => 'false', 'escape' => false]); ?> </li>
				<?php if(count($empresasOptSidebar) > 1) { ?>
					<div style='max-width: 90%' class='m-l-10'>
						<label class='text-white mini-itens'> Empresa: </label>
						<?= $this->Form->control('empresaRightSidebar', ['id' => 'empresaRightSidebar', 'class' => 'form-control mini-itens', 'label' => false, 'value' => $empresa, 'options' => $empresasOptSidebar]) ?>
					</div>
				<?php } ?>
            </ul>
        </nav>
	</div>
</aside>





