<?php
/**
 * @var \App\View\AppView $this
 * @var array<string,mixed> $proto
 */
$uFila = $this->Url->build(['controller' => 'ServicedeskPrototype', 'action' => 'fila']);
$uClientes = $this->Url->build(['controller' => 'Clientes', 'action' => 'index']);
echo $this->element('ServicedeskPrototype/dashboard_markup', ['uFila' => $uFila, 'uClientes' => $uClientes, 'proto' => $proto]);
