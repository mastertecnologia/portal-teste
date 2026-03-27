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
 *
 * Documentação funcional da guia de relatórios:
 * - Filtros disponíveis: situação, problema, cliente, tipo, técnico responsável, período (de/até) e mês.
 * - Integração de datas: ao selecionar "mês", a tela preenche automaticamente "de" e "até" com os limites do mês.
 * - Ano padrão: mês inicia no ano corrente automaticamente; pode ser alterado manualmente pelo usuário.
 * - Seleção manual de OS: a grade da tela permite marcar ordens específicas para PDF/e-mail.
 * - Performance: a grade de seleção lista até 300 OS mais recentes conforme o filtro atual.
 * - E-mail: ao enviar, o sistema gera PDF do modelo e anexa; se houver IDs selecionados, envia apenas as OS marcadas.
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
