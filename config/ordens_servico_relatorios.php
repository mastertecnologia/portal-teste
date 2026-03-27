<?php
/**
 * Modelos de relatório do módulo Ordens de Serviço.
 * Cada item: id (slug estável), título e descrição exibidos na tela de relatórios.
 *
 * Telas do módulo (mapeamento):
 * - index     — lista / KPIs / filtros / impressão em lote (imprimirordens)
 * - edit/view — cadastro da OS
 * - add       — nova OS
 * - imprimir  — impressão/PDF de uma OS (layout detalhado)
 * - imprimirordens — várias OS selecionadas (POST ids)
 * - relatorios — hub desta configuração (visualizar / PDF / e-mail)
 */
return [
	[
		'id' => 'lista_filtrada',
		'titulo' => 'Lista de ordens',
		'descricao' => 'Grade com número, datas, cliente, contrato, técnico, valor e situação. Respeita os filtros abaixo (mesma lógica da lista principal).',
	],
	[
		'id' => 'resumo_situacao',
		'titulo' => 'Resumo por situação',
		'descricao' => 'Contagem de ordens agrupadas pela situação atual, com os mesmos filtros de cliente, problema e tipo (exceto filtro de situação da lista).',
	],
];
