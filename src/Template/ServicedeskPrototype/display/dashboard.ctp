<?php
/**
 * @var \App\View\AppView $this
 * @var string $title
 * @var array<string,mixed> $proto
 */
$this->assign('title', $title);
$this->Breadcrumbs->add(__('Gestão de incidentes'), ['controller' => 'Servicedesk', 'action' => 'index']);
$this->Breadcrumbs->add(__('SD protótipo'), ['controller' => 'ServicedeskPrototype', 'action' => 'index'], ['class' => 'breadcrumb-item active']);
?>
<div class="row">
	<div class="col-12 pgm-sd-prototype">
		<?php
		$uFila = $this->Url->build(['controller' => 'ServicedeskPrototype', 'action' => 'fila']);
		$uClientes = $this->Url->build(['controller' => 'Clientes', 'action' => 'index']);
		echo $this->element('ServicedeskPrototype/dashboard_markup', ['uFila' => $uFila, 'uClientes' => $uClientes, 'proto' => $proto]);
		?>
	</div>
</div>
