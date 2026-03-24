<?php
	use Cake\Routing\Router;
	require_once (ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'UserConstants.php');

	$nameTrim = trim((string)($name ?? ''));
	$partsName = $nameTrim !== '' ? preg_split('/\s+/', $nameTrim, -1, PREG_SPLIT_NO_EMPTY) : [];
	$u0 = $partsName[0] ?? '';
	$u1 = $partsName[1] ?? '';
	$userInitials = '';
	if ($u0 !== '') {
		$userInitials .= strtoupper($u0[0]);
	}
	if ($u1 !== '') {
		$userInitials .= strtoupper($u1[0]);
	} elseif (strlen($u0) > 1) {
		$userInitials = strtoupper(substr($u0, 0, 2));
	}
?>
<aside class="left-sidebar skin-pgm pgm-sidebar-shell">
	<div class="pgm-sidebar-brand">
		<a href="javascript:void(0)" class="nav-toggler d-block d-md-none waves-effect waves-dark pgm-sidebar-toggler" aria-label="Abrir menu">
			<i class="ti-menu"></i>
		</a>
		<a href="javascript:void(0)" class="sidebartoggler d-none d-md-block waves-effect waves-dark pgm-sidebar-toggler" aria-label="Recolher menu">
			<i class="icon-menu"></i>
		</a>
		<?= $this->Html->link(
			'<div class="pgm-sidebar-mark">PGM</div><div class="pgm-sidebar-titles hide-menu"><strong>PGM Soluções</strong><div class="pgm-sidebar-sub">ERP Enterprise</div></div>',
			['controller' => 'Users', 'action' => 'dashboard'],
			['class' => 'pgm-sidebar-logo-link navbar-brand', 'escape' => false]
		) ?>
	</div>

	<div class="pgm-sidebar-meta">
		<div class="pgm-meta-row">
			<div style="flex:1;min-width:0">
				<label>Empresa</label>
				<?= $this->Form->control('empresaSidebar', [
					'id' => 'empresaSidebar',
					'class' => 'form-control',
					'label' => false,
					'value' => $empresa,
					'options' => $empresasOptSidebar,
					'readonly' => count($empresasOptSidebar) <= 1,
				]) ?>
			</div>
			<div>
				<small>Data</small>
				<p class="pgm-meta-date"><?= h(date('d/m/Y')) ?></p>
			</div>
		</div>
	</div>

	<div class="scroll-sidebar ps ps--theme_default ps--active-y" data-ps-id="5c23612c-2012-1d1a-2b77-a7091df065d9">
		<nav class="sidebar-nav">
			<ul id="sidebarnav">
				<li class="pgm-nav-section-label" aria-hidden="true"><span>Menu principal</span></li>
				<li class="<?= $dashboard ?>"><?= $this->Html->link('<i class="fa fa-columns"></i><span class="hide-menu"> Dashboard </span>', ['controller' => 'Users', 'action' => 'dashboard'], ['class' => 'waves-effect waves-dark', 'aria-expanded' => 'false', 'escape' => false]); ?></li>
				<li class="<?= $clientesActive ?>"><?= $this->Html->link('<i class="fa fa-building"></i><span class="hide-menu"> Clientes </span>', ['controller' => 'Clientes', 'action' => 'index'], ['class' => 'waves-effect waves-dark m-r-0', 'aria-expanded' => 'false', 'escape' => false]); ?></li>
				<li class="<?= $produtosActive ?>"><?= $this->Html->link('<i class="fa fa-boxes"></i><span class="hide-menu"> Produtos </span>', ['controller' => 'Produtos', 'action' => 'index'], ['class' => 'waves-effect waves-dark m-r-0', 'aria-expanded' => 'false', 'escape' => false]); ?></li>
				<li class="<?= $ordensActive ?>"><?= $this->Html->link('<i class="fas fa-file-signature"></i><span class="hide-menu"> Ordens de Serviço </span>', ['controller' => 'Ordensservico', 'action' => 'index'], ['class' => 'waves-effect m-r-0 waves-dark', 'aria-expanded' => 'false', 'escape' => false]); ?></li>
				<li class="<?= $ticketsActive ?>"><?= $this->Html->link('<i class="fas fa-ticket-alt"></i><span class="hide-menu"> Tickets </span>', ['controller' => 'Servicedesk', 'action' => 'index'], ['class' => 'waves-effect waves-dark m-r-0', 'aria-expanded' => 'false', 'escape' => false, 'target' => '_blank', 'rel' => 'noopener noreferrer']); ?></li>

				<li class="pgm-nav-section-label" aria-hidden="true"><span>Operações</span></li>
				<?php if (!empty($admin)) : ?>
				<li class="<?= $queuesAtendimentoActive ?>"><?= $this->Html->link('<i class="fas fa-layer-group"></i><span class="hide-menu"> Filas / técnicos </span>', ['controller' => 'Queues', 'action' => 'adminIndex'], ['class' => 'waves-effect waves-dark m-r-0', 'aria-expanded' => 'false', 'escape' => false]); ?></li>
				<?php endif; ?>
				<li class="<?= $orcamentosActive ?>"><?= $this->Html->link('<i class="fas fa-file-invoice-dollar"></i><span class="hide-menu"> Orçamentos </span>', ['controller' => 'Orcamentos', 'action' => 'index'], ['class' => 'waves-effect waves-dark', 'aria-expanded' => 'false', 'escape' => false]); ?></li>
				<li class="<?= $faturasActive ?>"><?= $this->Html->link('<i class="fas fa-file-invoice"></i><span class="hide-menu"> Locação </span>', ['controller' => 'Faturas', 'action' => 'index'], ['class' => 'waves-effect waves-dark', 'aria-expanded' => 'false', 'escape' => false]); ?></li>
				<li class="<?= $visitasActive ?>"><?= $this->Html->link('<i class="fa fa-calendar"></i><span class="hide-menu"> Agenda </span>', ['controller' => 'Agenda', 'action' => 'calendario'], ['class' => 'waves-effect waves-dark', 'aria-expanded' => 'false', 'escape' => false]); ?></li>
				<li class="<?= $senhasActive ?>"><?= $this->Html->link('<i class="fa fa-lock"></i><span class="hide-menu"> Banco de Senhas </span>', ['controller' => 'Bancosenhas', 'action' => 'index'], ['class' => 'waves-effect waves-dark', 'aria-expanded' => 'false', 'escape' => false]); ?></li>
				<?php $display = $sidebar != 'mini-sidebar' ? 'none' : ''; ?>
				<li id="mini-logout" style="display:<?= $display ?>;"><?= $this->Html->link('<i class="far fa-circle text-danger"></i><span class="hide-menu">Sair</span>', '/users/logout', ['class' => 'waves-effect waves-dark', 'aria-expanded' => 'false', 'escape' => false]); ?></li>
			</ul>
		</nav>
	</div>

	<div class="pgm-sidebar-footer">
		<div class="user-profile">
			<div class="user-pro-body">
				<div class="dropdown dropup">
					<a href="javascript:void(0)" class="dropdown-toggle u-dropdown link hide-menu text-white d-flex align-items-center" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
						<span class="pgm-user-av"><?= h($userInitials ?: '?') ?></span>
						<span class="hide-menu text-truncate" style="max-width:140px"><?= h($name) ?></span>
						<span class="caret hide-menu"></span>
					</a>
					<div class="dropdown-menu animated flipInY">
						<?= $this->Html->link('<i class="fas fa-user"></i> Alterar Perfil', ['controller' => 'Users', 'action' => 'change_profile'], ['class' => 'dropdown-item', 'escape' => false]); ?>
						<?= $this->Html->link('<i class="fa fa-lock"></i> Alterar Senha', ['controller' => 'Users', 'action' => 'change_password'], ['class' => 'dropdown-item', 'escape' => false]); ?>
						<?= $this->Html->link('<i class="ti-rss-alt"></i> Acesso Remoto', ['controller' => 'normasempresa', 'action' => 'acessoremoto'], ['class' => 'dropdown-item', 'escape' => false]); ?>
						<?= $this->Html->link('<i class="ti-lock"></i> Verificação login', ['controller' => 'users', 'action' => 'loginduasetapas'], ['class' => 'dropdown-item', 'escape' => false]); ?>
						<?php if (!empty($admin)) {
							echo $this->Html->link('<i class="ti-settings"></i> Painel Administrativo', ['controller' => 'config', 'action' => 'index'], ['class' => 'dropdown-item', 'escape' => false]);
						} ?>
						<?= $this->Html->link('<i class="fa fa-power-off"></i> Logout', ['controller' => 'Users', 'action' => 'logout'], ['class' => 'dropdown-item', 'escape' => false]); ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</aside>
<script>
	document.onkeydown = function(e) {
		if (e.ctrlKey && (e.keyCode === 85 || e.keyCode === 117)) {
			return false;
		}
		return true;
	};
	$('#pesquisa-funcoes').keyup(function(e){
		e.preventDefault();
		$.ajax({
			url:  "<?= Router::url(['controller'=>'Pesquisa','action'=>'pesquisa']);?>/" + $('#pesquisa-funcoes').val(),
			dataType: "json",
			success: function(data){
				$('.htmlpesquisa').html('');
				$.each(data, function(key, array) {
					$('.htmlpesquisa').append('<li><a class="link link-btn" data-controller="'+array.Controller+'"data-action="'+array.Action+'" >'+array.ControllerQueAparece+ ' > ' +array.ActionQueAparece+'</a></li>');
				});
			},
		});
	});
	$(document).on("click", ".link-btn",function(e) {
		var controller = $(this).attr('data-controller');
		var action = $(this).attr('data-action');
		$.ajax({
			url: "<?= Router::url(['controller'=>'Pesquisa','action'=>'link']);?>/" + controller + '/' + action,
			success: function(data){ window.location = data; },
		});
	});
</script>
