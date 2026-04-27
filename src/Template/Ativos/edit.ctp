<?php
/**
 * Editar Ativo de TI.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Asset $asset
 * @var array $clientesOpts
 * @var array $usersOpts
 * @var array $tiposOpts
 * @var array $statusOpts
 * @var array $propriedadeOpts
 * @var array $ticketsHist
 */
$this->Breadcrumbs->add('Cadastros', '#', ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Ativos', ['controller' => 'Ativos', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add($asset->descricao ?: ('#' . $asset->id), '#', ['class' => 'breadcrumb-item active']);
$this->append('css', $this->element('pgm_premium_css', ['name' => 'clientes-premium']));
$this->append('css', $this->element('pgm_premium_css', ['name' => 'clientes-layout-unificado']));
?>
<?= $this->element('pgm_premium_css', ['name' => 'clientes-premium']) ?>
<?= $this->element('pgm_premium_css', ['name' => 'clientes-layout-unificado']) ?>
<?php
echo $this->element('Ativos/form', [
	'asset' => $asset,
	'clientesOpts' => $clientesOpts,
	'usersOpts' => $usersOpts,
	'tiposOpts' => $tiposOpts,
	'statusOpts' => $statusOpts,
	'propriedadeOpts' => $propriedadeOpts,
	'isEdit' => true,
	'ticketsHist' => $ticketsHist ?? [],
]);
