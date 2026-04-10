<?php
/**
 * ABAC: colunas por tabela lógica para isolamento empresa / cliente / próprio usuário.
 *
 * - empresa: filtra por Auth idempresa na coluna indicada.
 * - cliente: filtra por Auth idcliente (portal role=1 ou permissão RBAC abac_scope=cliente).
 * - own: filtra por Auth id na coluna user_id_column (ex.: edição de perfil).
 *
 * O escopo efetivo vem de rbacAbacScope (permissão RBAC que casou) ou padrão legado
 * (equipe → empresa; portal → cliente quando a tabela suporta).
 */
return [
	'Abac' => [
		'enabled' => true,
		'tables' => [
			'Clientes' => [
				'alias' => 'Clientes',
				'empresa_column' => 'idempresa',
				'cliente_row_id' => true,
			],
			'Tickets' => [
				'alias' => 'Tickets',
				'cliente_column' => 'idcliente',
				'empresa_column' => 'idempresa',
			],
			'Users' => [
				'alias' => 'Users',
				'empresa_column' => 'idempresa',
				'user_id_column' => 'id',
			],
			'Queues' => [
				'alias' => 'Queues',
				'empresa_column' => 'idempresa',
			],
			'Ticketsusers' => [
				'alias' => 'Ticketsusers',
				'empresa_column' => 'idempresa',
			],
			'Ticketsanexos' => [
				'alias' => 'Ticketsanexos',
				'empresa_column' => 'idempresa',
			],
			'ContratosHoras' => [
				'alias' => 'ContratosHoras',
				'empresa_column' => 'idempresa',
				'cliente_column' => 'idcliente',
			],
			'Ordensservico' => [
				'alias' => 'Ordensservico',
				'empresa_column' => 'idempresa',
				'cliente_column' => 'idcliente',
			],
			'Orcamentos' => [
				'alias' => 'Orcamentos',
				'empresa_column' => 'idempresa',
				'cliente_column' => 'idcliente',
			],
			'Visitas' => [
				'alias' => 'Visitas',
				'empresa_column' => 'idempresa',
				'cliente_column' => 'idcliente',
			],
			'Clicontratos' => [
				'alias' => 'Clicontratos',
				'empresa_column' => 'idempresa',
				'cliente_column' => 'idcliente',
			],
			// Módulo fiscal (escopo empresa nas permissões fiscal.*; CFOP/NCM são globais)
			'FiscalNotas' => [
				'alias' => 'FiscalNotas',
				'empresa_column' => 'idempresa',
				'cliente_column' => 'idcliente',
			],
			'FiscalNotasEntrada' => [
				'alias' => 'FiscalNotas',
				'empresa_column' => 'idempresa',
				'cliente_column' => 'idcliente',
			],
			'FiscalCertificados' => [
				'alias' => 'FiscalCertificados',
				'empresa_column' => 'idempresa',
			],
			'FiscalEmpresasConfig' => [
				'alias' => 'FiscalEmpresasConfig',
				'empresa_column' => 'idempresa',
			],
			'FiscalNaturezaOperacao' => [
				'alias' => 'FiscalNaturezaOperacao',
				'empresa_column' => 'idempresa',
			],
			'FiscalAliquotas' => [
				'alias' => 'FiscalAliquotas',
				'empresa_column' => 'idempresa',
			],
			'FiscalDfeRecebidos' => [
				'alias' => 'FiscalDfeRecebidos',
				'empresa_column' => 'idempresa',
			],
		],
	],
];
