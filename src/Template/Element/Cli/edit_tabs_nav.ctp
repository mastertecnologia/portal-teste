<?php
/**
 * Abas da ficha Clientes/edit — mesmas rotas/IDs de painel; destaque ativo via Bootstrap + hash (ver Cli/edit_tabs_js).
 *
 * @var bool $isEquipe
 * @var bool $isClientePortal
 * @var mixed $permissaoacesso
 */
$showAcessos = !empty($isEquipe) || !empty($permissaoacesso);
$showUsuarios = !empty($isEquipe);
$showContratosToken = $showAcessos;
$acessosPaneId = !empty($isClientePortal) ? 'acessosCliente' : 'acessos';
?>
<ul class="nav cli-tabs-nav cli-edit-tabs-nav" role="tablist" id="cli-edit-tabs-nav" aria-label="Seções do cadastro de cliente">
	<li class="nav-item" role="presentation">
		<a class="nav-link active" id="cli-tab-cliente" data-toggle="tab" href="#cliente" role="tab" aria-controls="cliente" aria-selected="true">
			<i class="fas fa-user" aria-hidden="true"></i> Cliente
		</a>
	</li>
	<?php if ($showAcessos) : ?>
	<li class="nav-item" role="presentation">
		<a class="nav-link" id="cli-tab-<?= h($acessosPaneId) ?>" data-toggle="tab" href="#<?= h($acessosPaneId) ?>" role="tab" aria-controls="<?= h($acessosPaneId) ?>" aria-selected="false">
			<i class="fas fa-desktop" aria-hidden="true"></i> Acessos
		</a>
	</li>
	<?php endif; ?>
	<?php if ($showUsuarios) : ?>
	<li class="nav-item" role="presentation">
		<a class="nav-link" id="cli-tab-usuarios" data-toggle="tab" href="#usuarios" role="tab" aria-controls="usuarios" aria-selected="false">
			<i class="fas fa-users" aria-hidden="true"></i> Usuários
		</a>
	</li>
	<?php endif; ?>
	<?php if ($showContratosToken) : ?>
	<li class="nav-item" role="presentation">
		<a class="nav-link" id="cli-tab-contratos" data-toggle="tab" href="#contratos" role="tab" aria-controls="contratos" aria-selected="false">
			<i class="fas fa-file-contract" aria-hidden="true"></i> Contratos
		</a>
	</li>
	<li class="nav-item" role="presentation">
		<a class="nav-link" id="cli-tab-token" data-toggle="tab" href="#token" role="tab" aria-controls="token" aria-selected="false">
			<i class="fas fa-key" aria-hidden="true"></i> Token
		</a>
	</li>
	<?php endif; ?>
</ul>
