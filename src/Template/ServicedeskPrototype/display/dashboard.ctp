<?php
/**
 * @var \App\View\AppView $this
 * @var array<string,mixed> $proto
 */
$uFila = $this->Url->build(['controller' => 'ServicedeskPrototype', 'action' => 'fila']);
$uClientes = $this->Url->build(['controller' => 'ClientesPrototype', 'action' => 'lista']);
$uOperacional = $this->Url->build(['controller' => 'Tickets', 'action' => 'operacional']);
echo $this->element('ServicedeskPrototype/dashboard_markup', [
	'uFila' => $uFila,
	'uClientes' => $uClientes,
	'uOperacional' => $uOperacional,
	'proto' => $proto,
]);
