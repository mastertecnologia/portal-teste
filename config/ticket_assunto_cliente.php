<?php
/**
 * Opções do select "Assunto / categoria" na abertura de chamado (Tickets::add),
 * quando C_TicketCategoriaClienteQuery (PGMPackages) não estiver definida ou estiver vazia.
 *
 * Chaves numéricas devem alinhar com AssuntoTicket() / legado; a chave 5 é usada no add.ctp
 * para exibir o campo "Data da Visita".
 */
return [
	1 => 'Dúvida',
	2 => 'Solicitação',
	3 => 'Problema / erro',
	4 => 'Requisição de acesso',
	5 => 'Visita / agendamento',
];
