<?php
/**
 * @var \App\View\AppView $this
 * @var string $title
 */
$this->assign('title', $title);
$this->Breadcrumbs->add('Gestão de Incidentes', ['controller' => 'Servicedesk', 'action' => 'index']);
$this->Breadcrumbs->add('Service Desk protótipo', ['controller' => 'ServicedeskPrototype', 'action' => 'index'], ['class' => 'breadcrumb-item active']);

$w = $this->request->getAttribute('webroot');
$this->Html->css($w . 'dist/css/pages/pgm-servicedesk-prototype.css', ['block' => true]);

$uFila = $this->Url->build(['controller' => 'ServicedeskPrototype', 'action' => 'fila']);
$uClientes = $this->Url->build(['controller' => 'Clientes', 'action' => 'index']);
?>
<div class="row">
	<div class="col-12 pgm-sd-prototype">
		<p class="text-muted small" style="margin-bottom:12px;">
			<strong>Protótipo (β):</strong> dados ilustrativos do mockup · não substitui o Service Desk existente.
			<?= $this->Html->link('Voltar ao Service Desk clássico', ['controller' => 'Servicedesk', 'action' => 'index']) ?>
		</p>
		<?= $this->element('ServicedeskPrototype/dashboard_markup', ['uFila' => $uFila, 'uClientes' => $uClientes]) ?>
	</div>
</div>
